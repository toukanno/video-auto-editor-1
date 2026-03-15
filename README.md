# video-auto-editor-1（VideoFactory AI）

動画編集自動化ツールの試作1です。Laravel製のバックエンド処理パイプラインとして、動画のアップロードからテロップ生成・YouTube/TikTok投稿までを自動化します。

## 概要

1本の動画をアップロードするだけで以下を自動処理:

1. 音声抽出（FFmpeg）
2. 文字起こし（OpenAI Whisper API）
3. テロップ整形（LLM）
4. 無音検出・カット
5. 字幕ファイル生成（SRT/ASS）
6. レンダリング（テロップ焼き込み + ショート動画変換）
7. YouTube / TikTok 自動投稿

## 技術スタック

- **Backend**: Laravel 12 / PHP 8.3+
- **動画処理**: FFmpeg / FFprobe
- **文字起こし**: OpenAI Whisper API
- **テロップ整形**: OpenAI GPT-4o-mini
- **Frontend**: Blade + Tailwind CSS + Alpine.js
- **投稿連携**: YouTube Data API / TikTok Content Posting API

## ディレクトリ

```
video-factory/          # Laravelプロジェクト本体
├── app/
│   ├── Http/Controllers/  # VideoController, RenderController 等
│   ├── Jobs/              # TranscribeVideoJob, RenderVideoJob 等
│   ├── Models/            # Video, TranscriptSegment 等
│   └── Services/          # FFmpeg, YouTube, TikTok 連携
├── config/
├── database/migrations/
├── resources/views/       # Blade テンプレート
└── routes/
```

## セットアップ

```bash
cd video-factory
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## 使い分け

| リポジトリ | 役割 |
|---|---|
| **video-auto-editor-1**（このリポ） | Laravel製バックエンド。ジョブ処理・API連携の検証 |
| video-auto-editor-2 | Electron製デスクトップアプリ。GUI操作でのワークフロー実行 |
