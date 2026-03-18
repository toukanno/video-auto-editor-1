# VideoFactory AI

動画アップロードから、字幕生成・無音カット・BGM 合成・リサイズ・書き出し・投稿準備までをローカルで一通り確認できる Laravel 製の動画自動化アプリです。

## 今回の完成ポイント

- デモユーザーへ自動ログインするため、認証セットアップなしで画面確認可能
- アップロード時に以下の自動編集設定を保存可能
  - 出力比率（9:16 / 16:9 / 1:1）
  - テロップ生成 ON/OFF
  - 無音カット ON/OFF
  - テロップスタイル選択
  - BGM / SE 素材アップロードと音量設定
- 動画一覧 / 詳細でジョブ進捗・保存済み設定・レンダリング結果を確認可能
- 設定 / ログ画面で FFmpeg・FFprobe・API キー・アプリログを確認可能
- FFmpeg / FFprobe 未設定時はアップロード自体は保存し、後から再実行可能
- 再実行処理の不具合（render / thumbnail の再投入失敗）を修正

## 技術スタック

- Backend: Laravel 12 / PHP 8.3+
- Frontend: Blade + Tailwind CSS + Alpine.js
- Queue: Laravel Queue（database driver）
- DB: SQLite / MySQL / PostgreSQL
- 動画処理: FFmpeg / FFprobe
- 文字起こし: OpenAI Whisper API
- テロップ整形: OpenAI GPT-4o-mini
- 投稿連携: YouTube Data API / TikTok Content Posting API

## セットアップ

```bash
cd video-factory
cp .env.example .env
npm install
composer install
php artisan key:generate
php artisan migrate
```

### 必須ツール

```bash
# Ubuntu / Debian
sudo apt install ffmpeg

# macOS
brew install ffmpeg
```

> `ffmpeg` と `ffprobe` は PATH 上にあるか、`.env` の `FFMPEG_PATH`, `FFPROBE_PATH` で指定してください。

### 開発起動

```bash
php artisan serve
php artisan queue:work --tries=3
```

ブラウザで `http://localhost:8000` を開くと、初回アクセス時にデモユーザーで自動ログインします。

## 利用フロー

1. **動画アップロード**
   - 動画ファイルを選択
   - 必要に応じて BGM / SE 素材を追加
   - 出力比率・字幕・無音カットなどの設定を保存
2. **自動処理**
   - 音声抽出
   - 文字起こし
   - テロップ整形
   - 無音検出
   - 字幕生成
   - レンダリング
   - サムネイル生成
3. **結果確認**
   - 動画詳細で字幕結果・無音区間・レンダリング済み動画を確認
   - エラー時は同じ設定で再実行
4. **配信準備**
   - 完成動画をダウンロード
   - YouTube / TikTok への投稿ジョブを起動

## 画面一覧

- `/videos` 動画一覧・ジョブ進捗
- `/videos/create` 動画アップロード + 自動編集設定
- `/videos/{id}` 動画詳細・字幕結果・再実行・エクスポート
- `/renders` レンダリング結果一覧
- `/caption-styles` テロップスタイル管理
- `/settings` システム状態・ログ確認

## 既知の制限

- OpenAI API キー未設定時は文字起こしを実行できません
- YouTube / TikTok 投稿には各 API キーと OAuth 接続が必要です
- 顔追従クロップや話者別カラーリングは未実装です
- このリポジトリ単体では FFmpeg 本体は同梱していません
