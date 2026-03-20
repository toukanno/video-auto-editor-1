# CLAUDE.md — VideoFactory AI

## Project Overview

VideoFactory AI is a Laravel 12 automated video editing pipeline that processes videos end-to-end: upload → transcription → subtitle generation → silence detection → rendering → publishing (YouTube/TikTok). It is Part 1 of a two-part system (paired with a video-auto-editor-2 Electron desktop app).

All application code lives inside the `video-factory/` directory.

## Quick Reference

```bash
# First-time setup (from video-factory/)
composer run setup

# Start development (runs server, queue, logs, vite concurrently)
composer run dev

# Run tests
composer run test

# PHP linting/formatting
./vendor/bin/pint

# Database migrations
php artisan migrate

# Queue worker (production)
php artisan queue:work --queue=default --tries=3
```

All commands must be run from the `video-factory/` directory.

## Tech Stack

- **Backend**: PHP 8.2+, Laravel 12
- **Frontend**: Blade templates, Tailwind CSS 4, Alpine.js, Vite
- **Database**: SQLite (dev/test), MySQL 8 / PostgreSQL (production)
- **Queue**: sync (dev), Redis or database driver (production)
- **Testing**: PHPUnit 11, Mockery
- **Linting**: Laravel Pint (PSR-12 style)
- **External APIs**: OpenAI Whisper (transcription), GPT-4o-mini (caption normalization), YouTube Data API, TikTok Content API
- **System tools**: FFmpeg, FFprobe

## Directory Structure

```
video-factory/
├── app/
│   ├── Http/Controllers/       # 6 controllers (Video, Publish, Render, Settings, CaptionStyle)
│   ├── Jobs/                   # 9 queue jobs (pipeline steps)
│   ├── Models/                 # 9 Eloquent models
│   ├── Services/
│   │   ├── Video/              # Pipeline orchestration, FFmpeg wrappers
│   │   ├── Caption/            # SRT/ASS builder, transcript normalizer
│   │   └── Integrations/       # OpenAI, YouTube, TikTok API clients
│   ├── Policies/               # Authorization (VideoPolicy, CaptionStylePolicy)
│   ├── Support/                # Constants (VideoProcessingDefaults)
│   └── Providers/              # AppServiceProvider (demo user auto-login)
├── config/
│   └── videofactory.php        # App-specific config (FFmpeg, captions, render, LLM)
├── database/migrations/        # 11 migrations
├── resources/
│   ├── views/                  # Blade templates (layouts, videos, renders, caption-styles, settings)
│   ├── css/app.css             # Tailwind entry
│   └── js/app.js               # Alpine.js entry
├── routes/web.php              # All routes (no API routes)
└── tests/                      # PHPUnit (Feature + Unit)
```

## Processing Pipeline

The core video pipeline runs as chained queue jobs:

1. **ExtractAudioJob** — FFmpeg probe + audio extraction (900s timeout)
2. **TranscribeVideoJob** — OpenAI Whisper API (Japanese language)
3. **NormalizeTranscriptJob** — LLM cleanup/emphasis via GPT-4o-mini
4. **DetectSilenceJob** — FFmpeg silencedetect filter
5. **BuildCaptionFileJob** — Generate SRT + ASS with styling
6. **RenderVideoJob** — FFmpeg filter_complex (subtitles + scale + silence cut)
7. **GenerateThumbnailJob** — Extract thumbnail frame
8. **PublishYoutubeJob / PublishTikTokDraftJob** — Manual trigger from UI

Status flow: `uploaded → extracting_audio → transcribing → normalizing → detecting_silence → building_caption → rendering → rendered → publishing → completed`

On failure: any status → `failed` (with `last_failed_step` recorded; retryable from UI).

## Key Architecture Patterns

- **Service layer**: Business logic in `app/Services/`, controllers are thin
- **Pipeline orchestration**: `VideoPipelineService` chains jobs and manages status transitions
- **Graceful fallbacks**: App works without external APIs/tools (dummy captions if no OpenAI key, copies original video if no FFmpeg)
- **Demo mode**: Local/testing environments auto-create and auto-login a demo user via `AppServiceProvider` and `AuthenticateDemoUser` middleware
- **Policy-based auth**: Data isolation enforced via `VideoPolicy` and `CaptionStylePolicy`
- **Processing logs**: `ProcessingLog` model tracks all pipeline events for debugging

## Routes

All routes are web routes defined in `routes/web.php`:

| Method | Path | Controller | Purpose |
|--------|------|------------|---------|
| GET | `/videos` | VideoController@index | Video list |
| POST | `/videos` | VideoController@store | Upload video |
| GET | `/videos/{id}` | VideoController@show | Video details |
| DELETE | `/videos/{id}` | VideoController@destroy | Delete video |
| POST | `/videos/{id}/rerun` | VideoController@rerun | Retry failed pipeline |
| POST | `/videos/{id}/publish/youtube` | PublishController@youtube | Publish to YouTube |
| POST | `/videos/{id}/publish/tiktok` | PublishController@tiktok | Publish to TikTok |
| GET | `/renders` | RenderController@index | Render list |
| GET | `/renders/{id}/preview` | RenderController@preview | Preview video |
| GET | `/renders/{id}/download` | RenderController@download | Download video |
| GET/POST | `/settings` | SettingsController | Settings page |
| Resource | `/caption-styles` | CaptionStyleController | Caption style CRUD |

## Environment Variables

Key variables (see `.env.example` for full list):

```
OPENAI_API_KEY=            # Required for transcription
LLM_BASE_URL=              # OpenAI-compatible endpoint (default: https://api.openai.com/v1)
LLM_MODEL=                 # Caption normalization model (default: gpt-4o-mini)
YOUTUBE_CLIENT_ID=          # For YouTube publishing
YOUTUBE_CLIENT_SECRET=
TIKTOK_CLIENT_KEY=          # For TikTok publishing
TIKTOK_CLIENT_SECRET=
FFMPEG_PATH=ffmpeg          # Auto-detected if on PATH
FFPROBE_PATH=ffprobe
VIDEO_MAX_FILE_SIZE_MB=2048
VIDEO_MAX_DURATION_SEC=1800
SILENCE_THRESHOLD_DB=-30
SILENCE_MIN_DURATION=0.5
```

## Testing

```bash
cd video-factory
composer run test
```

- Tests use in-memory SQLite and sync queue driver (configured in `phpunit.xml`)
- Test base class: `tests/TestCase.php`
- Feature tests: `tests/Feature/`
- Unit tests: `tests/Unit/`

## Code Style & Conventions

- **PHP formatting**: Use `./vendor/bin/pint` (Laravel Pint, PSR-12 based)
- **Indentation**: 4 spaces for PHP/JS/CSS, 2 spaces for YAML
- **Line endings**: LF
- **Encoding**: UTF-8
- **Models**: Use `$fillable` arrays and `$casts` for type safety
- **Status constants**: Defined as class constants on `Video` model (e.g., `Video::STATUS_UPLOADED`)
- **Jobs**: Each pipeline step is a separate job class with `$timeout` and `$tries` configured
- **Services**: Stateless service classes, instantiated via constructor injection
- **No TypeScript**: Frontend is plain JS (Alpine.js) with Blade templates

## Important Notes for AI Assistants

- Always `cd video-factory/` before running artisan, composer, or npm commands
- The app has no API routes — everything is web routes with Blade views
- Queue jobs chain automatically via `VideoPipelineService`; don't dispatch jobs individually
- The `Video` model is the central entity; most operations flow through it
- When adding new pipeline steps, follow the existing pattern: create a Job, add status constants to `Video` model, update `VideoPipelineService`
- The app is bilingual (Japanese/English) — the README and some UI text include Japanese
- No CI/CD pipelines exist yet
- No Docker configuration; use Laravel Sail or standard PHP server
