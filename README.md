# laravel-ddd

Laravel 13 / PHP 8.3+ で、**ドメイン駆動設計（DDD）** と **クリーンアーキテクチャ** に基づいて構築したバックエンド API。

対象ドメインは 2 つで、それぞれ CRUD を提供する。

| ドメイン | 集約ルート | エンドポイント |
| --- | --- | --- |
| 社員管理 | `Employee` | `/api/employees` |
| ユーザー管理 | `User` | `/api/users` |

---

## アーキテクチャ

```
┌────────────────────────────────────────────────────────────┐
│  Presentation  (src/Presentation)                          │
│    Controller / FormRequest / JsonPresenter                │
│    - HTTP と JSON だけを知る。業務ロジックを持たない。      │
└───────────────────────┬────────────────────────────────────┘
                        │ 入力 DTO
┌───────────────────────▼────────────────────────────────────┐
│  Application  (src/Application)                            │
│    UseCase / Input DTO / Output DTO                        │
│    - ユースケースを 1 クラス 1 操作で表現。                 │
│    - トランザクション境界を宣言する。                       │
│    - Laravel に一切依存しない。                             │
└───────────────────────┬────────────────────────────────────┘
                        │ エンティティ / リポジトリのポート
┌───────────────────────▼────────────────────────────────────┐
│  Domain  (src/Domain)                     ← 依存の終着点    │
│    Entity / ValueObject / Enum / Criteria                  │
│    Repository Interface / Service Interface / Exception    │
│    - 不変条件と業務ルールの唯一の置き場所。                 │
│    - PHP 標準ライブラリ以外に依存しない。                   │
└───────────────────────▲────────────────────────────────────┘
                        │ implements（依存性逆転）
┌───────────────────────┴────────────────────────────────────┐
│  Infrastructure  (src/Infrastructure)                      │
│    Eloquent Model(Record) / Mapper / Repository 実装        │
│    PasswordHasher 実装 / TransactionManager 実装            │
│    - Eloquent モデルは永続化の都合（Record）であり、        │
│      ドメインモデルではない。Mapper で相互変換する。         │
└────────────────────────────────────────────────────────────┘
```

依存の向きは常に **Presentation → Application → Domain ← Infrastructure**。
ドメイン層・アプリケーション層のソースに `Illuminate\...` の `use` は存在しない。

ポート（インターフェース）とアダプタ（実装）の結線は
[app/Providers/DomainServiceProvider.php](app/Providers/DomainServiceProvider.php) に集約している。

### ディレクトリ構成

```
src/
├── Domain/
│   ├── Shared/                    共有カーネル
│   │   ├── Criteria/              Pagination, PaginatedResult
│   │   ├── Exception/             DomainException（基底）/ InvalidValue / EntityNotFound /
│   │   │                          BusinessRuleViolation / DuplicateEntity
│   │   └── ValueObject/           Uuid（基底）, EmailAddress
│   ├── Employee/
│   │   ├── Criteria/              EmployeeSearchCriteria
│   │   ├── Entity/                Employee（集約ルート）
│   │   ├── Exception/             EmployeeNotFound / DuplicateEmployeeNumber / DuplicateEmployeeEmail
│   │   ├── Repository/            EmployeeRepositoryInterface（ポート）
│   │   └── ValueObject/           EmployeeId, EmployeeNumber, PersonName, Department,
│   │                              HireDate, EmploymentStatus(enum)
│   └── User/
│       ├── Criteria/              UserSearchCriteria
│       ├── Entity/                User（集約ルート）
│       ├── Exception/             UserNotFound / DuplicateUserEmail / LastAdminCannotBeRemoved
│       ├── Repository/            UserRepositoryInterface（ポート）
│       ├── Service/               PasswordHasherInterface（ポート）
│       └── ValueObject/           UserId, UserName, PlainPassword, HashedPassword,
│                                  UserRole(enum), UserStatus(enum)
├── Application/
│   ├── Shared/Transaction/        TransactionManagerInterface（ポート）
│   ├── Employee/{Dto,UseCase}/    Create / Update / Delete / Get / List
│   └── User/{Dto,UseCase}/        Create / Update / Delete / Get / List
├── Infrastructure/
│   ├── Persistence/Eloquent/
│   │   ├── Model/                 EmployeeRecord, UserRecord（永続化用。ドメインモデルではない）
│   │   ├── Mapper/                EmployeeMapper, UserMapper
│   │   └── Repository/            EloquentEmployeeRepository, EloquentUserRepository
│   ├── Security/                  BcryptPasswordHasher
│   └── Shared/Transaction/        DatabaseTransactionManager
└── Presentation/Api/
    ├── Controller/                EmployeeController, UserController
    ├── Request/{Employee,User}/   FormRequest（入力検証 → 入力 DTO 変換）
    └── Resource/                  EmployeeJsonPresenter, UserJsonPresenter
```

Laravel 側に残しているのは、フレームワーク設定（`config/`、`bootstrap/`）、
ルート定義（`routes/api.php`）、DI 設定（`app/Providers/`）、マイグレーションのみ。

---

## セットアップ

### Docker で実行する（ローカルに PHP が不要）

```sh
make install   # composer install
make fresh     # DB 作成 + マイグレーション
make test      # テスト実行
make serve     # http://localhost:8000 で起動
```

`make` を使わない場合は以下と同等。

```sh
docker run --rm -e COMPOSER_HOME=/tmp/composer -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -e COMPOSER_HOME=/tmp/composer -v "$PWD":/app -w /app composer:2 php artisan migrate:fresh --force
docker compose up
```

### ローカルの PHP で実行する

PHP 8.3 以上と Composer が入っている場合。

```sh
composer install
php artisan migrate --force
php artisan test
php artisan serve
```

データベースは既定で SQLite（`database/database.sqlite`）。
テストは `phpunit.xml` の設定によりインメモリ SQLite で動作する。

### ロケール設定（初回のみ手動）

バリデーションメッセージを日本語にするため、`.env` の `APP_LOCALE` を `ja` に変更する。

```diff
-APP_LOCALE=en
+APP_LOCALE=ja
```

`config/app.php` の既定値は `ja`、フォールバックは `en` にしてあるため、
`.env` から `APP_LOCALE` の行を削除しても日本語になる。
翻訳は [lang/ja/validation.php](lang/ja/validation.php)、属性名は各 FormRequest の
`attributes()` で定義している。テストは `phpunit.xml` で `ja` に固定済み。

---

## API 仕様

共通事項。

- ベース URL: `/api`
- リクエスト / レスポンスともに JSON（`Accept: application/json` を推奨）
- 単体レスポンス: `{"data": { ... }}`
- 一覧レスポンス: `{"data": [ ... ], "meta": {"total": 0, "page": 1, "per_page": 20, "total_pages": 0}}`
- 削除は `204 No Content`（本文なし）
- 認証・認可は本 API の対象外（要件に含まれないため実装していない）

### エラーレスポンス

| ステータス | 発生条件 | 本文 |
| --- | --- | --- |
| 404 | 対象が存在しない / ID が UUID 形式でない | `{"message": "..."}` |
| 409 | 業務ルール違反（社員番号・メールアドレスの重複、管理者が 0 人になる操作） | `{"message": "..."}` |
| 422 | 入力検証エラー（FormRequest） | `{"message": "...", "errors": {"field": ["..."]}}` |
| 422 | ドメインの不変条件違反 | `{"message": "..."}` |

ドメイン例外から HTTP ステータスへの変換は
[bootstrap/app.php](bootstrap/app.php) の `withExceptions` に一箇所だけ存在する。

### 社員管理 `/api/employees`

| メソッド | パス | 説明 |
| --- | --- | --- |
| GET | `/api/employees` | 一覧・検索（`page`, `per_page`, `keyword`, `department`, `status`, `sort_by`, `sort_direction`） |
| POST | `/api/employees` | 登録（201） |
| GET | `/api/employees/{id}` | 取得 |
| PUT / PATCH | `/api/employees/{id}` | 更新（部分更新可） |
| DELETE | `/api/employees/{id}` | 削除（204） |

```jsonc
// POST /api/employees
{
  "employee_number": "EMP-0001",
  "last_name": "山田",
  "first_name": "太郎",
  "email": "taro@example.com",
  "department": "開発部",
  "hire_date": "2024-04-01",
  "status": "active"          // active | on_leave | retired（省略時 active）
}
```

```jsonc
// レスポンス
{
  "data": {
    "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "employee_number": "EMP-0001",
    "name": { "last_name": "山田", "first_name": "太郎", "full_name": "山田 太郎" },
    "email": "taro@example.com",
    "department": "開発部",
    "hire_date": "2024-04-01",
    "status": "active",
    "status_label": "在籍中",
    "created_at": "2024-04-01T09:00:00+00:00",
    "updated_at": "2024-04-01T09:00:00+00:00"
  }
}
```

主な業務ルール。

- 社員番号は `A-Z`, `0-9`, `-` のみで 3〜20 文字。全社で一意。
- メールアドレスも全社で一意。
- 退職済み（`retired`）の社員は、在籍状態を戻す操作以外の変更を受け付けない。

### ユーザー管理 `/api/users`

| メソッド | パス | 説明 |
| --- | --- | --- |
| GET | `/api/users` | 一覧・検索（`page`, `per_page`, `keyword`, `role`, `status`, `sort_by`, `sort_direction`） |
| POST | `/api/users` | 登録（201） |
| GET | `/api/users/{id}` | 取得 |
| PUT / PATCH | `/api/users/{id}` | 更新（部分更新可） |
| DELETE | `/api/users/{id}` | 削除（204） |

```jsonc
// POST /api/users
{
  "name": "山田 太郎",
  "email": "taro@example.com",
  "password": "password123",  // 8〜72 文字、英字と数字を各 1 文字以上
  "role": "admin",            // admin | manager | member
  "status": "active"          // active | suspended（省略時 active）
}
```

```jsonc
// レスポンス（パスワードは一切返さない）
{
  "data": {
    "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "name": "山田 太郎",
    "email": "taro@example.com",
    "role": "admin",
    "role_label": "管理者",
    "status": "active",
    "status_label": "有効",
    "created_at": "2024-04-01T09:00:00+00:00",
    "updated_at": "2024-04-01T09:00:00+00:00"
  }
}
```

主な業務ルール。

- メールアドレスは一意。
- パスワードは bcrypt でハッシュ化して保存し、API レスポンスには含めない。
- 停止中（`suspended`）のユーザーは、有効化以外の変更を受け付けない。
- 管理者（`admin`）が 0 人になる削除・停止・降格はできない（409）。

---

## テスト

```sh
make test          # または php artisan test
```

- `tests/Unit/Domain/**` … エンティティ・値オブジェクトの不変条件と業務ルール
- `tests/Unit/Application/**` … ユースケース（インメモリリポジトリを注入し、DB なしで検証）
- `tests/Feature/Api/**` … HTTP 経由の CRUD、404 / 409 / 422 の応答

---

## 設計上の判断

- **Eloquent モデルをドメインモデルにしない。** `EmployeeRecord` / `UserRecord` は永続化の都合を表す
  レコードであり、Mapper を介してドメインエンティティと相互変換する。これによりドメイン層が
  Active Record や DB スキーマの変更から切り離される。
- **リポジトリはドメイン層に定義し、実装をインフラ層に置く（依存性逆転）。** ユースケースは
  インターフェースにしか依存しないため、ユニットテストではインメモリ実装を差し替えている。
- **識別子は UUID v4。** 採番のために DB へ往復せず、アプリケーション側で集約を完成させられる。
- **ユースケースは 1 クラス 1 操作。** 肥大化しやすいサービスクラスを避け、入力 DTO と出力 DTO で
  境界を明示する。
- **認証・認可は未実装。** 要件に含まれないため、Laravel 既定の `App\Models\User` と `config/auth.php`
  は削除し、`users` テーブルはユーザー管理ドメインが所有する定義に置き換えている。導入する場合は
  Sanctum 等をインフラ層のアダプタとして追加する想定。
