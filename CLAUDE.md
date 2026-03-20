# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

VideoFactory AI: a Laravel 12 pipeline that automates video subtitle generation, rendering, and publishing. Upload a video and it automatically extracts audio, transcribes (Whisper API), normalizes captions (GPT-4o-mini), detects silence (FFmpeg), renders captioned short/long videos, and publishes to YouTube/TikTok.

**Tech stack:** PHP 8.2+ / Laravel 12, SQLite (dev), Blade + Tailwind CSS 4 + Alpine.js, Vite 7, FFmpeg/FFprobe, OpenAI Whisper + GPT-4o-mini, YouTube Data API, TikTok Content Posting API.

The Laravel project lives in `video-factory/` (not the repo root).

## Commands

All commands must be run from `video-factory/`.

```bash
# Setup
cd video-factory && composer setup    # install, key:gen, migrate, npm install+build

# Dev (starts server, queue worker, pail logs, vite concurrently)
composer dev

# Run all tests
composer test
# Or directly:
php artisan test

# Run a single test file
php artisan test --filter=ExampleTest
php artisan test tests/Feature/ExampleTest.php

# Run a single test method
php artisan test --filter=test_the_root_path_redirects_to_videos_index

# Lint (pint)
./vendor/bin/pint

# Queue worker (standalone)
php artisan queue:work --queue=default --tries=3

# Migrations
php artisan migrate
```

## Architecture

### Directory Layout (`video-factory/app/`)

| Directory | Role |
|---|---|
| `Http/Controllers/` | Web request handling. No business logic — delegates to services/jobs. |
| `Jobs/` | Queued pipeline steps. Each job calls one service, updates status, dispatches the next job. |
| `Services/Video/` | FFmpeg operations (audio extract, silence detect, render, thumbnail) and pipeline orchestration. |
| `Services/Caption/` | Caption file generation (SRT/ASS), LLM-based transcript normalization, style management. |
| `Services/Integrations/` | External API wrappers (Whisper, YouTube, TikTok). |
| `Models/` | Eloquent models. `Video` is the central aggregate. |
| `Policies/` | Authorization (VideoPolicy, CaptionStylePolicy). |

### Key Config Files

- `config/videofactory.php` — FFmpeg paths, silence detection thresholds, caption defaults, render dimensions, LLM settings.
- `config/services.php` — API credentials (OpenAI, YouTube, TikTok).

## Dependency Direction

```
Controllers --> VideoPipelineService --> Jobs --> Services --> Models
                                                     |
                                              External APIs (Whisper, YouTube, TikTok)
                                              FFmpeg (shell)
```

- **Controllers** validate input, create models, call `VideoPipelineService::start()`.
- **VideoPipelineService** dispatches the first job; each job chains to the next on completion.
- **Jobs** are thin wrappers: update video status, call a service method, dispatch the next job, handle failure via `failed()`.
- **Services** contain all business logic. Jobs inject services via constructor DI.
- **Models** are passive data holders; `Video` has status helpers (`markStatus`, `markFailed`).

## Entry Points

### Job Pipeline (sequential, each dispatches the next)

```
ExtractAudioJob -> TranscribeVideoJob -> NormalizeTranscriptJob -> DetectSilenceJob
-> BuildCaptionFileJob -> RenderVideoJob (per render task) -> GenerateThumbnailJob
```

Publishing is user-triggered: `PublishYoutubeJob` / `PublishTikTokDraftJob`.

### Routes (`routes/web.php`)

All behind `demo.auth` middleware (auto-login in dev).

- `POST /videos` — upload + start pipeline
- `POST /videos/{video}/rerun` — retry from failed step or specific step
- `POST /videos/{video}/publish/youtube|tiktok` — publish
- `GET /renders/{renderTask}/preview|download` — output files
- Resource: `caption-styles` (CRUD + duplicate)
- `GET /settings` — system health / dependency check

### Video Status Flow

```
uploaded -> extracting_audio -> transcribing -> normalizing -> detecting_silence
-> building_caption -> rendering -> rendered -> [publishing -> completed]
Any step -> failed (last_failed_step recorded, rerun from UI)
```

## Testing Notes

- PHPUnit 11, two suites: `tests/Unit/` and `tests/Feature/`.
- Test DB: SQLite `:memory:` (configured in `phpunit.xml`).
- Queue set to `sync` in tests.
- Tests extend `Tests\TestCase` (standard Laravel base).
- Currently only example tests exist. When adding tests:
  - Use `RefreshDatabase` trait for DB tests.
  - Mock external services (`TranscriptionService`, `YoutubeService`, `TikTokService`) and FFmpeg calls.
  - Jobs accept `int $videoId` (not model instances) to test dispatch independently.

## Change Rules

### Do Not Modify

- **Existing migrations** — create new migrations for schema changes.
- **`config/services.php` credential structure** — external services depend on these key names.
- **`config/videofactory.php` key names** — referenced throughout services.
- **`phpunit.xml` env overrides** — test isolation depends on these.
- **`composer.json` script names** (`setup`, `dev`, `test`) — CI and documentation depend on them.
- **Job chaining order** in individual jobs (e.g., `TranscribeVideoJob` dispatching `NormalizeTranscriptJob`) — the pipeline sequence is implicit in each job's `handle()` method.

### Before Any Change

1. Run `composer test` from `video-factory/` to confirm green baseline.
2. If modifying a job, trace the full chain: which job dispatches it, and which job it dispatches next.
3. If modifying a service, check which jobs depend on it (jobs inject services in `handle()`).
4. If adding an env var, add it to `.env.example` with a sensible default.
5. If changing video status values, update `Video::STATUS_*` constants and `progressPercent()`.
6. External API calls (Whisper, YouTube, TikTok) must remain behind their respective service classes in `Services/Integrations/`.
