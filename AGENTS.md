# laravel-ddd — 作業ガイドライン

Laravel 13 / PHP 8.3+ のバックエンド API。DDD + クリーンアーキテクチャで構成している。
全体像は [README.md](README.md) を参照。

## 実行環境

このリポジトリを開発しているマシンには **PHP / Composer がローカルに入っていない**。
すべて Docker 経由で実行する（`composer:2` イメージに PHP 8.5 + pdo_sqlite が同梱）。

```sh
make install   # composer install
make dump      # composer dump-autoload（クラスを追加したら実行）
make fresh     # migrate:fresh
make test      # php artisan test
make lint      # vendor/bin/pint
make serve     # http://localhost:8000
```

ローカルに PHP を入れる場合は通常の `php artisan ...` でも動作する。

バリデーションメッセージを日本語にするには `.env` の `APP_LOCALE=ja` が必要
（`config/app.php` の既定値は `ja`、翻訳は `lang/ja/validation.php`）。

## レイヤ規約（最重要）

依存の向きは **Presentation → Application → Domain ← Infrastructure**。

| 層 | 場所 | 依存してよいもの |
| --- | --- | --- |
| Domain | `src/Domain` | PHP 標準ライブラリのみ |
| Application | `src/Application` | Domain のみ |
| Infrastructure | `src/Infrastructure` | Domain / Application / Laravel |
| Presentation | `src/Presentation` | Application / Domain / Laravel |

- `src/Domain` と `src/Application` のファイルに `use Illuminate\...` を書かない。
  必要になったらインターフェース（ポート）をドメイン側に定義し、実装をインフラ層に置く。
- Eloquent モデル（`*Record`）はドメインモデルではない。エンティティとの変換は `Mapper` に閉じる。
- ポートとアダプタの結線は `app/Providers/DomainServiceProvider.php` に集約する。
- ドメイン例外 → HTTP ステータスの変換は `bootstrap/app.php` の `withExceptions` 一箇所のみ。
  コントローラで try/catch して HTTP ステータスを組み立てない。

## コード規約

- 全ファイル先頭に `declare(strict_types=1);`
- 継承を意図しないクラスは `final`、状態を変えない DTO は `final readonly class`
- 値オブジェクトは生成時に不変条件を検証し、不正値は `Domain\Shared\Exception\InvalidValueException`
- ユースケースは 1 クラス 1 操作、public メソッドは `execute()` のみ
- 書き込み系ユースケースは `TransactionManagerInterface::run()` でトランザクション境界を張る
- 例外メッセージ・コメント・PHPDoc は日本語

## テスト

- `tests/Unit/Domain/**` … 不変条件と業務ルール（フレームワーク不要）
- `tests/Unit/Application/**` … ユースケース。`tests/Support` のインメモリリポジトリを注入する
- `tests/Feature/Api/**` … HTTP 経由の CRUD と異常系（`RefreshDatabase`）

新しい業務ルールを足すときは、まず Domain のユニットテストで表現すること。
