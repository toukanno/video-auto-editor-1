# VideoFactory AI

動画アップロードで、字幕・テロップ・ショート化・投稿まで自動化する Laravel 製パイプライン基盤。

## 概要

1本の動画をアップロードするだけで、以下を自動で処理します：

1. **音声抽出** - FFmpeg で動画から音声を分離
2. **文字起こし** - OpenAI Whisper API でセグメント単位の文字起こし
3. **テロップ整形** - LLM で口癖削除・改行最適化・強調抽出
4. **無音検出** - FFmpeg silencedetect で無音区間を自動検出
5. **字幕生成** - SRT / ASS ファイルを自動生成（スタイル適用）
6. **レンダリング** - テロップ焼き込み + 9:16 ショート動画変換 + 無音カット
7. **YouTube 投稿** - YouTube Data API で動画アップロード
8. **TikTok 下書き** - TikTok Content Posting API で下書き投稿

## 技術スタック

- **Backend**: Laravel 12 / PHP 8.3+
- **Queue**: Redis + Laravel Queue (database driver も対応)
- **DB**: SQLite (dev) / MySQL 8 / PostgreSQL
- **動画処理**: FFmpeg / FFprobe
- **文字起こし**: OpenAI Whisper API
- **テロップ整形**: OpenAI GPT-4o-mini (LLM)
- **Frontend**: Blade + Tailwind CSS + Alpine.js
- **投稿連携**: YouTube Data API / TikTok Content Posting API

## セットアップ

```bash
# 1. 依存インストール
composer install

# 2. 環境設定
cp .env.example .env
php artisan key:generate

# 3. .env を編集（API キーを設定）
# OPENAI_API_KEY=sk-...
# YOUTUBE_CLIENT_ID=...
# TIKTOK_CLIENT_KEY=...

# 4. データベースマイグレーション
php artisan migrate

# 5. 開発サーバ起動
php artisan serve

# 6. キューワーカー起動（別ターミナル）
php artisan queue:work --queue=default --tries=3
```

## 必要な外部ツール

- **FFmpeg** (動画処理に必須)
  ```bash
  # Ubuntu/Debian
  sudo apt install ffmpeg
  # macOS
  brew install ffmpeg
  ```

## ディレクトリ構成

```
app/
├── Http/Controllers/
│   ├── VideoController.php       # 動画 CRUD + パイプライン起動
│   ├── PublishController.php     # YouTube/TikTok 投稿
│   ├── RenderController.php      # レンダリング結果・プレビュー
│   └── CaptionStyleController.php # テロップスタイル管理
├── Jobs/
│   ├── ExtractAudioJob.php       # 音声抽出
│   ├── TranscribeVideoJob.php    # 文字起こし
│   ├── NormalizeTranscriptJob.php # テロップ整形
│   ├── DetectSilenceJob.php      # 無音検出
│   ├── BuildCaptionFileJob.php   # SRT/ASS 生成
│   ├── RenderVideoJob.php        # レンダリング
│   ├── GenerateThumbnailJob.php  # サムネイル生成
│   ├── PublishYoutubeJob.php     # YouTube 投稿
│   └── PublishTikTokDraftJob.php # TikTok 下書き
├── Models/
│   ├── Video.php
│   ├── TranscriptSegment.php
│   ├── SilenceSegment.php
│   ├── CaptionStyle.php
│   ├── RenderTask.php
│   ├── PublishTask.php
│   └── PlatformAccount.php
├── Services/
│   ├── Video/
│   │   ├── VideoPipelineService.php    # パイプライン管理
│   │   ├── AudioExtractService.php     # FFmpeg 音声抽出
│   │   ├── SilenceDetectionService.php # 無音検出
│   │   ├── RenderService.php           # レンダリング
│   │   └── ThumbnailService.php        # サムネイル
│   ├── Caption/
│   │   ├── TranscriptNormalizerService.php # LLM テロップ整形
│   │   ├── CaptionFileBuilderService.php   # SRT/ASS 生成
│   │   └── CaptionStyleService.php         # スタイル管理
│   └── Integrations/
│       ├── TranscriptionService.php  # Whisper API
│       ├── YoutubeService.php        # YouTube Data API
│       └── TikTokService.php         # TikTok API
└── Policies/
    ├── VideoPolicy.php
    └── CaptionStylePolicy.php
```

## 処理パイプライン

```
動画アップロード
   ↓
ExtractAudioJob (FFmpeg で音声抽出 + メタデータ取得)
   ↓
TranscribeVideoJob (Whisper API で文字起こし)
   ↓
NormalizeTranscriptJob (LLM で口癖削除・整形)
   ↓
DetectSilenceJob (FFmpeg で無音区間検出)
   ↓
BuildCaptionFileJob (ASS/SRT 生成)
   ↓
RenderVideoJob (テロップ焼き込み + 9:16 変換 + 無音カット)
   ↓
GenerateThumbnailJob (サムネイル生成)
   ↓
[ユーザー操作] PublishYoutubeJob / PublishTikTokDraftJob
```

## 画面一覧

- **動画一覧** `/videos` - アップロード済み動画の管理
- **アップロード** `/videos/create` - 新規動画のアップロード
- **動画詳細** `/videos/{id}` - 処理状況・文字起こし・投稿操作
- **レンダリング一覧** `/renders` - 出力動画の一覧
- **テロップスタイル** `/caption-styles` - テロップデザインの管理

## 設定可能なテロップスタイル

- フォント (Noto Sans JP, M PLUS Rounded 1c, etc.)
- フォントサイズ
- 文字色 / 縁取り色 / 背景色
- 縁取り太さ
- 表示位置（画面下部 %）
- 1行文字数 / 最大行数

## ステータス遷移

```
uploaded → extracting_audio → transcribing → normalizing
→ detecting_silence → building_caption → rendering
→ rendered → publishing → completed

失敗時: any → failed (last_failed_step に記録、UI から再実行可能)
```

## 制限事項（MVP）

- 動画は 30 分以内 / 2GB 以下を推奨
- 顔追従クロップは未実装（中央クロップのみ）
- 複数話者の色分けは未実装
- TikTok は下書き投稿を優先
