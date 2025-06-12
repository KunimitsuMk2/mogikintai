主な機能

認証機能: 一般ユーザー・管理者の認証（Laravel Fortify使用）
勤怠打刻: 出勤・休憩開始/終了・退勤の打刻
勤怠管理: 月次勤怠一覧、詳細表示
修正申請: 勤怠データの修正申請・承認フロー
管理者機能: スタッフ管理、勤怠データ管理、CSV出力
レスポンシブデザイン: モバイル・PC対応

対象ユーザー

一般ユーザー: 勤怠打刻、勤怠確認、修正申請
管理者: 全ユーザーの勤怠管理、申請承認、データ出力

🚀 技術スタック

フレームワーク: Laravel 10.x
PHP: ^8.1
データベース: MySQL 8.0
認証: Laravel Fortify
フロントエンド: Blade, CSS, JavaScript
開発環境: Docker (nginx, php, mysql, phpmyadmin)

🛠️ セットアップ
前提条件

Docker Desktop
Git

インストール手順

リポジトリクローン

bashgit clone git@github.com:KunimitsuMk2/mogikintai.git
cd mogikintai

Docker環境起動

bashdocker-compose up -d

依存関係インストール

bashdocker-compose exec php composer install

環境設定

bash# .envファイルをコピー
docker-compose exec php cp .env.example .env

# アプリケーションキー生成
docker-compose exec php php artisan key:generate

データベース設定

bash# マイグレーション実行
docker-compose exec php php artisan migrate

# シーダー実行（ダミーデータ）
docker-compose exec php php artisan db:seed

アクセス確認


アプリケーション: http://localhost
phpMyAdmin: http://localhost:8080

👥 ユーザーアカウント
デフォルトアカウント（シーダー実行後）
管理者

メール: admin@gmail.com
パスワード: password

一般ユーザー（例）

メール: tanaka@example.com
パスワード: password

※ 他にも7人の一般ユーザーアカウントが作成されます
📱 主要画面
一般ユーザー

会員登録・ログイン画面
勤怠打刻画面: リアルタイム時刻表示、ステータス管理
勤怠一覧画面: 月次勤怠データ、前月・翌月ナビゲーション
勤怠詳細画面: 詳細確認、修正申請
申請一覧画面: 承認待ち・承認済み申請の管理

管理者

管理者ログイン画面
日次勤怠一覧: 全ユーザーの日別勤怠確認
スタッフ一覧: 全ユーザー管理
スタッフ別勤怠一覧: 個別ユーザーの月次勤怠、CSV出力
申請承認画面: 修正申請の詳細確認・承認

🔄 業務フロー
勤怠打刻フロー

出勤: 勤務外 → 出勤中
休憩開始: 出勤中 → 休憩中
休憩終了: 休憩中 → 出勤中
退勤: 出勤中 → 退勤済

修正申請フロー

一般ユーザー: 勤怠詳細画面で修正申請
申請状態: 承認待ち（修正内容がプレビュー表示）
管理者: 申請詳細確認・承認
承認後: 勤怠データ更新、ステータス変更

🧪 テスト
テスト実行
bash# 全テスト実行
docker-compose exec php php artisan test

# 特定テスト実行
docker-compose exec php php artisan test tests/Feature/AttendanceListTest.php
テストカバレッジ

認証機能: 会員登録・ログインのバリデーション
勤怠打刻機能: ステータス遷移、時間記録
勤怠表示機能: 一覧・詳細表示、ナビゲーション
修正申請機能: 申請・承認フロー、権限制御
管理者機能: スタッフ管理、データ管理、CSV出力

📊 データベース設計
主要テーブル

users: ユーザー情報（role: user/admin）
attendances: 勤怠データ（日付、時間、ステータス）
rest_times: 休憩時間詳細
attendance_correction_requests: 修正申請データ


権限制御

一般ユーザー: 自分の勤怠データのみアクセス可能
管理者: 全ユーザーデータアクセス、直接修正可能
AdminMiddleware: 未認証時は管理者ログインページへリダイレクト

🔧 開発・デバッグ
ログ確認
bash# Laravelログ
docker-compose exec php tail -f storage/logs/laravel.log

# nginxログ
docker-compose logs nginx
データベース操作
bash# マイグレーション再実行
docker-compose exec php php artisan migrate:refresh --seed

# tinker（REPL）
docker-compose exec php php artisan tinker
