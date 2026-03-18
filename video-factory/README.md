# VideoFactory AI

動画アップロードで、字幕・テロップ・ショート化・投稿まで自動化する Laravel 製パイプライン基盤です。ローカルでの確認を進めやすいように、**デモユーザー自動ログイン** と **文字起こしフォールバック** を追加しています。

## 現在できること

- 動画アップロード
- 動画一覧 / 動画詳細 / レンダリング一覧
- 自動編集設定（字幕、無音カット、短尺/長尺、リサイズ）
- テロップスタイル選択 / 管理
- BGM / SE ファイル指定
- エクスポート設定（ビットレート / FPS）
- 進捗表示と処理ログ表示
- 失敗ステップからの再実行
- YouTube / TikTok 投稿ジョブの作成
- OpenAI API 未設定時の仮字幕フォールバック

## セットアップ

```bash
cd video-factory
cp .env.example .env
```

`.env` のローカル推奨値:

```env
APP_ENV=local
QUEUE_CONNECTION=sync
DB_CONNECTION=sqlite
```

SQLite ファイルを作成後、依存関係とDBを準備します。

```bash
touch database/database.sqlite
composer install
npm install
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

## ローカル動作のポイント

- 認証UIは未搭載ですが、ローカルでは `demo@example.com` のデモユーザーへ自動ログインします。
- `OPENAI_API_KEY` が空でも、仮字幕を生成して画面・パイプラインの確認ができます。
- FFmpeg / FFprobe が必要です。
- 投稿連携を本当に使うには `platform_accounts` テーブルへのOAuthトークン保存が必要です。

## 必須外部ツール

- FFmpeg
- FFprobe

## 主要画面

- `/videos` 動画一覧
- `/videos/create` アップロード + 自動編集設定
- `/videos/{id}` 詳細 / ログ / 再実行 / 出力確認
- `/renders` 出力一覧
- `/caption-styles` テロップ設定
- `/settings` ローカル実行状況 / 連携状況

## 残課題

- OAuth接続UI（YouTube / TikTok）は未実装
- 本番用認証は未実装（ローカルデモログインのみ）
- Composer install はネットワーク制約下だと失敗する場合があります
- FFmpeg 未導入環境ではレンダリング不可です
