# 勤怠管理アプリ （TimeTrack）

【制作の背景・目的】

- プログラミングスクールにて「提示された仕様書に基づき、正確にシステムを構築する」という要件定義の遂行を目的として制作しました。
  【10年以上の事務経験を活かしたこだわり】
- 開発にあたっては、以下の点を特に意識しました。- 正確なデータ整合性：勤怠データは給与計算に直結するため、バリd〜ション（入力チェック）を徹底し、誤ったデータ登録を防ぐ設計にしました。- 指示への忠実な再現：スクール課題の要件を深く読み込み、漏れや解離がないよう一つずつ確認しながら実装しました。- ルールや可読性を意識したコーディングを行いました。

## 画面見本

![勤怠打刻画面](./attendance.png)

## 主な機能

【管理者】

- ログイン、ログアウト機能
- 勤怠詳細取得
- スタッフ（一般ユーザー）一覧取得機能
- 月次勤怠一覧取得機能
- CSVファイル出力機能（月次勤怠一覧）
- 勤怠修正情報・一覧取得
- 勤怠修正・申請承認機能

【一般ユーザー】

- 会員登録機能
- ログイン・ログアウト機能
- 勤怠打刻機能
- 勤怠一覧・詳細取得機能
- 勤怠修正申請機能
- 勤怠申請情報取得
- 月次勤怠一覧取得機能

## 環境構築

### Docker ビルド

- 1.git clone git@github.com:okumurachie/TimeTrack.git
- 2.docker-compose up -d --build

### laravel 環境構築

- 1.docker-compose exec php bash
- 2.composer install
- 3.cp .env.example .env(.env.example ファイルから.env を作成し、環境変数を変更)

              DB_HOST=mysql
              DB_DATABASE=laravel_db
              DB_USERNAME=laravel_user
              DB_PASSWORD=laravel_pass

              MAIL_MAILER=smtp
              MAIL_HOST=mailhog
              MAIL_PORT=1025
              MAIL_FROM_ADDRESS=hello@example.com

- 4.php artisan key:generate
- 5.php artisan migrate
- 6.php artisan db:seed

## 使用技術（実行環境）

- PHP 8.4.8
- Laravel 10.48.29
- MySQL 8.0
- nginx 1.21.1

## ER 図

![ER図](./index.png)

## URL

- 開発環境：http://localhost/
- 会員登録：http://localhost/register
- phpMyAdmin:http://localhost:8080/

---

## ユーザーのログイン情報

- 管理者ログイン画面(/admin/login)
- 一般ユーザー会員登録画面(/register)
- 一般ユーザーログイン画面(/login)

### 管理者ユーザー

- name:管理者1
- email:admin1@test.com
- password:admin1234

---

- name:管理者2
- email:admin2@test.com
- password:admin5678

### 一般ユーザー

- name:西 伶奈
- email:reina.n@test.com
- password:abcd1234

---

- name:山田 太郎
- email:taro.y@test.com
- password:abcd5678

---

- name:増田 一世
- email:issei.m@test.com
- password:dcba1234

---

- name:山本 敬吉
- email:keikichi.y@test.com
- password:dcba5678

---

- name:秋田 朋美
- email:tomomi.a@test.com
- password:abcd4321

---

- name:中西 教夫
- email:norio.n@test.com
- password:abcd8765

## 勤怠記録情報のダミーデータについて

- 当月、前月、翌月の平日、３ヶ月分で作成。ただし、ダミーユーザーで勤怠打刻などの挙動を確認できるようにするため、今日の日付の勤怠レコードは作らないようにしました。（今日のデータを作成してしまうと、勤怠登録画面のステータスが退勤済になり、打刻ができないため。ただし、テスト環境では、今日の勤怠も作成します。）

# TimeTrack
