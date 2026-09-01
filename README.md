# 楽ペア（Rakupair）

Apple 端末の修理店を想定した予約管理システムです。
PHP 研修の自作課題として、個人で作成しました。

顧客は端末・症状・日時を順に選んで修理の予約ができます。
症状をうまく言葉にできない場合は、文章で入力すると Gemini API が候補の中から近い症状を選びます。
管理者は管理画面から、予約・予約枠・端末・症状・部品在庫を操作できます。

## 使用技術

- PHP 8.2 / Laravel 12
- Blade
- SQLite
- Vite
- PHPUnit 11
- Google Gemini API

## 主な機能

顧客

- 会員登録、ログイン / ログアウト
- 予約の作成（端末 → 症状 → 日時 → 確認）
- 症状の自然文入力と、AI による症状の候補表示
- 予約の一覧・詳細表示
- 自分の予約のキャンセル

管理者

- 予約の一覧・詳細表示、キャンセル
- 予約枠の追加・開閉・削除
- 端末・症状・部品の追加・編集・停止・復元、在庫の調整

## 工夫した点

- 同じ予約枠が同時に取られないよう、`time_slots` にバージョン番号を持たせて、予約確定時に一致するかを確認しています。
- 症状ごとに必要な部品を紐づけていて、在庫が足りない症状は予約できないようにしています。予約時は在庫を減らし、キャンセル時は戻します。
- AI が返した症状 ID はそのまま使わず、実在するか・選んだ端末の症状か・在庫があるかをサーバ側で確認しています。API キーが無くても、症状の一覧から手動で選べます。

## 動かし方

PHP 8.2 以上、Composer、Node.js が必要です。

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

`http://localhost:8000` で表示されます。

AI 機能を使う場合は `.env` に API キーを設定してください。

```dotenv
GEMINI_API_KEY=your-api-key
GEMINI_MODEL=gemini-2.5-flash
```

未設定でも予約自体はできます。その場合は症状を一覧から選ぶ形になります。

## デモ用アカウント

シーダーで作成される確認用のアカウントです。

- 顧客: `customer@example.com` / `password`
- 管理者: `admin@example.com` / `password`

## テスト

```bash
php artisan test
```

登録・ログイン、予約の作成とキャンセル、在庫の増減、予約枠の管理、AI の症状選択などを
Feature テストで確認しています。

## 今後やりたいこと

- 予約ステータスの型付け（現在は文字列）
- 未到店の判定を画面アクセス時ではなくスケジューラで行う
- コントローラに集まっている処理をサービス層に分ける
- メール通知、修理の進捗ステータス
