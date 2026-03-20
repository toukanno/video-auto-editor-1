# VideoFactory AI

> Laravel video automation pipeline / 動画自動編集バックエンド

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com/)

1本の動画をアップロードするだけで、**文字起こし → テロップ生成 → 無音カット → レンダリング → YouTube/TikTok投稿** までを自動化する Laravel 製パイプラインです。

Upload a video and automatically transcribe, subtitle, render, and publish to YouTube/TikTok.

## Features / 機能

| Feature | Description |
|---|---|
| 🎤 Transcription | OpenAI Whisper API で文字起こし |
| 📝 Caption Styling | LLM でテロップ整形（口癖削除・強調抽出） |
| 🔇 Silence Detection | FFmpeg で無音区間を自動検出・カット |
| 🎬 Rendering | テロップ焼き込み + 9:16 ショート動画変換 |
| 📤 YouTube Upload | YouTube Data API で自動投稿 |
| 📱 TikTok Draft | TikTok Content Posting API で下書き投稿 |
| 🖼️ Thumbnails | 動画からサムネイルを自動生成 |

## Architecture / アーキテクチャ

```
video-factory/
├── app/
│   ├── Http/Controllers/
│   │   ├── VideoController.php        # 動画 CRUD
│   │   ├── RenderController.php       # レンダリング操作
│   │   ├── PublishController.php      # 投稿操作
│   │   └── CaptionStyleController.php # テロップスタイル管理
│   ├── Jobs/
│   │   ├── ExtractAudioJob.php        # 音声抽出
│   │   ├── TranscribeVideoJob.php     # Whisper 文字起こし
│   │   ├── NormalizeTranscriptJob.php # LLM テロップ整形
│   │   ├── DetectSilenceJob.php       # 無音検出
│   │   ├── BuildCaptionFileJob.php    # SRT/ASS 生成
│   │   ├── RenderVideoJob.php         # FFmpeg レンダリング
│   │   ├── GenerateThumbnailJob.php   # サムネイル生成
│   │   ├── PublishYoutubeJob.php      # YouTube 投稿
│   │   └── PublishTikTokDraftJob.php  # TikTok 下書き
│   ├── Models/                        # Eloquent models
│   └── Services/                      # FFmpeg, YouTube, TikTok
├── config/videofactory.php            # アプリ設定
├── database/migrations/               # DB スキーマ
├── resources/views/                   # Blade + Tailwind UI
└── routes/web.php                     # ルーティング
```

## Quick Start

```bash
git clone https://github.com/toukanno/video-auto-editor-1.git
cd video-auto-editor-1
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

ブラウザで `http://localhost:8000` を開いてください。

## Tech Stack / 技術スタック

| Layer | Technology |
|---|---|
| Backend | Laravel 12 / PHP 8.3+ |
| Video | FFmpeg / FFprobe |
| AI | OpenAI Whisper API + GPT-4o-mini |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| DB | SQLite (dev) / MySQL / PostgreSQL |
| Upload | YouTube Data API / TikTok Content Posting API |

## Installation / インストール

```bash
cd video-factory
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

`.env` を編集:

```
OPENAI_API_KEY=sk-your-key-here
YOUTUBE_CLIENT_ID=...
YOUTUBE_CLIENT_SECRET=...
```

```bash
php artisan serve
# → http://localhost:8000
```

## Related / 関連リポジトリ

| Repository | Role |
|---|---|
| **video-auto-editor-1** (this) | Laravel backend — job processing pipeline |
| [video-auto-editor-2](https://github.com/toukanno/video-auto-editor-2) | Electron desktop app — GUI workflow |

## License

[MIT](LICENSE)
