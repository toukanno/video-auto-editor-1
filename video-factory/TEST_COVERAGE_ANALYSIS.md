# Test Coverage Analysis

## Current State

The project has **virtually no test coverage**. Only two placeholder tests exist:

- `tests/Unit/ExampleTest.php` — a trivial `assertTrue(true)` assertion
- `tests/Feature/ExampleTest.php` — verifies the root route redirects

The testing infrastructure (PHPUnit 11.5, Mockery, Faker, in-memory SQLite) is properly configured but unused.

---

## Coverage Gaps & Recommendations

### Priority 1 — Unit Tests (Pure Logic, No External Dependencies)

These are the highest-value, easiest-to-write tests because they exercise pure logic with no FFmpeg, API, or filesystem dependencies.

#### 1. `CaptionFileBuilderService` — Time Formatting & Text Wrapping

| Method | What to test |
|---|---|
| `msToSrtTime()` | 0ms, boundary values (e.g. 3599999ms), large values |
| `msToAssTime()` | Same as above, verify centisecond truncation (not rounding) |
| `hexToAss()` | Valid 6-char hex, with/without `#` prefix, invalid/short hex fallback |
| `getMarginV()` | Various `position_y` percentages (0, 50, 85, 100) |
| `wrapText()` | Short text (no wrap), exact boundary, Japanese punctuation breaks, multi-line overflow, `max_lines` truncation |

These private methods can be tested indirectly through `buildSrt()`/`buildAss()` or made testable via a small refactor to protected/package-level visibility.

#### 2. `TranscriptNormalizerService::ruleBasedClean()` / `normalizeWithRules()`

| Scenario | What to test |
|---|---|
| Japanese filler removal | `えー、こんにちは` → `こんにちは` |
| Multiple fillers | Stacked fillers all removed |
| No fillers | Text unchanged |
| Empty after removal | Graceful handling |
| Whitespace trimming | Leading/trailing spaces |

#### 3. `SilenceDetectionService::parseSilenceOutput()`

| Scenario | What to test |
|---|---|
| Normal FFmpeg output | Multiple silence_start/silence_end pairs parsed correctly |
| Empty output | Returns empty array |
| Partial output | silence_start without matching end |
| Malformed lines | Non-matching lines ignored |

#### 4. `Video` Model

| Method | What to test |
|---|---|
| `progressPercent()` | Every status maps to correct percentage |
| `markStatus()` | Status updated, error_message cleared |
| `markFailed()` | Status set to failed, step and truncated message stored |
| `isFailed()` | True only for failed status |
| `processingOption()` | Nested key access, missing key returns default |

#### 5. `VideoProcessingDefaults`

Test that default constants are reasonable and consistent (e.g., short aspect ratio matches expected dimensions).

---

### Priority 2 — Feature Tests (HTTP / Controller Layer)

#### 6. `VideoController`

| Endpoint | What to test |
|---|---|
| `GET /videos` | Returns paginated list for authenticated user; does not leak other users' videos |
| `POST /videos` | Validates file type, size, title length; creates Video + RenderTask records; dispatches pipeline |
| `POST /videos` | Rejects non-video mimetypes, oversized files |
| `GET /videos/{id}` | Returns 403 for other user's video (policy enforcement) |
| `DELETE /videos/{id}` | Deletes files from storage, removes DB records, respects policy |
| `POST /videos/{id}/rerun` | Dispatches correct job for given step; respects policy |

#### 7. `CaptionStyleController`

| Scenario | What to test |
|---|---|
| CRUD operations | Create, list, update, delete caption styles |
| Authorization | Users can only manage their own styles |
| Duplicate action | `POST /caption-styles/{id}/duplicate` creates a copy |
| Validation | Required fields, color format, numeric ranges |

#### 8. `PublishController`

| Scenario | What to test |
|---|---|
| YouTube publish | Dispatches `PublishYoutubeJob` with correct params |
| TikTok publish | Dispatches `PublishTikTokDraftJob` with correct params |
| Missing credentials | Returns appropriate error |
| Authorization | Only video owner can publish |

#### 9. `SettingsController`

| Scenario | What to test |
|---|---|
| View settings | Returns current settings |
| Update settings | Persists changes |
| Validation | Invalid values rejected |

---

### Priority 3 — Integration Tests (Jobs & Service Orchestration)

#### 10. Job Chain Dispatch Verification

| Job | What to test |
|---|---|
| `ExtractAudioJob` | Calls `AudioExtractService`, updates video metadata, dispatches `TranscribeVideoJob` |
| `TranscribeVideoJob` | Calls `TranscriptionService`, creates `TranscriptSegment` records, dispatches next job |
| `NormalizeTranscriptJob` | Calls normalizer service, dispatches `DetectSilenceJob` |
| `DetectSilenceJob` | Calls silence service, dispatches `BuildCaptionFileJob` |
| `BuildCaptionFileJob` | Builds caption files per render task, dispatches `RenderVideoJob` |
| `RenderVideoJob` | Calls render service, dispatches `GenerateThumbnailJob` |

For each job, also test:
- **Failure handling**: `failed()` method marks video as failed with correct step name
- **Status transitions**: Video status updated before processing
- **Logging**: `ProcessingLogService` called with expected messages

Use `Queue::fake()` to verify dispatch without actually running downstream jobs. Use Mockery to stub external services (FFmpeg, OpenAI).

#### 11. `VideoPipelineService`

| Method | What to test |
|---|---|
| `start()` | Dispatches `ExtractAudioJob` |
| `rerunFrom()` | Each step key dispatches the correct job class |
| `rerunFrom('render')` | Dispatches per render task, only if caption file exists |
| `rerunFrom('thumbnail')` | Dispatches per render task |
| `retry()` | Uses `last_failed_step` if set, otherwise calls `start()` |
| Invalid step | Falls back to `ExtractAudioJob` |

---

### Priority 4 — External Service Mocking Tests

#### 12. `TranscriptionService` (OpenAI Whisper)

| Scenario | What to test |
|---|---|
| Successful transcription | Parses Whisper API response, creates segments |
| API failure | Throws or returns gracefully |
| Empty transcript | Handles zero segments |

#### 13. `YoutubeService` / `TikTokService`

| Scenario | What to test |
|---|---|
| Successful upload | Returns video ID / post ID |
| Auth failure | Handles 401/403 |
| Rate limiting | Handles 429 |
| Network error | Retries or fails gracefully |

Use `Http::fake()` to mock external API responses.

---

### Priority 5 — Missing Infrastructure

#### 14. Database Factories

Only `UserFactory` exists. Create factories for:
- `VideoFactory` (critical — needed for nearly every test)
- `TranscriptSegmentFactory`
- `SilenceSegmentFactory`
- `CaptionStyleFactory`
- `RenderTaskFactory`
- `PublishTaskFactory`
- `PlatformAccountFactory`
- `ProcessingLogFactory`

#### 15. CI Pipeline

No GitHub Actions workflow exists. Add a workflow that:
- Runs `php artisan test` on push/PR
- Generates coverage reports
- Fails on coverage regression

---

## Suggested Implementation Order

1. **Create model factories** — unblocks all other tests
2. **Unit tests for `CaptionFileBuilderService`** — pure logic, high value, validates subtitle generation correctness
3. **Unit tests for `Video` model** — status management is core to the app
4. **Unit tests for `TranscriptNormalizerService` rule-based cleaning** — verifiable without API
5. **Unit tests for `SilenceDetectionService::parseSilenceOutput()`** — pure string parsing
6. **Feature tests for `VideoController` store/show/destroy** — validates the primary user workflow
7. **Feature tests for `CaptionStyleController` CRUD** — validates style management
8. **Job dispatch tests with `Queue::fake()`** — validates pipeline orchestration
9. **Job failure tests** — validates error handling and status transitions
10. **External service tests with `Http::fake()`** — validates API integration handling

## Estimated Impact

| Priority | Tests | Coverage gain |
|---|---|---|
| P1 (Unit) | ~30 tests | Models + core services |
| P2 (Feature) | ~25 tests | All HTTP endpoints |
| P3 (Jobs) | ~20 tests | Pipeline orchestration |
| P4 (External) | ~10 tests | API integrations |
| P5 (Infra) | Factories + CI | Enables everything above |

**Total: ~85 tests** would bring this project from 0% to solid coverage of all critical paths.
