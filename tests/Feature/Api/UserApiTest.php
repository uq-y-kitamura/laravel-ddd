<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Domain\User\ValueObject\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Persistence\Eloquent\Model\UserRecord;
use Tests\TestCase;

final class UserApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レスポンスに含まれてはいけないキー。
     *
     * @var list<string>
     */
    private const FORBIDDEN_KEYS = ['password', 'password_hash', 'plain_password'];

    public function test_ユーザー一覧を取得できる(): void
    {
        $this->givenUserRecord(['name' => '山田 太郎', 'email' => 'taro@example.com', 'created_at' => '2024-04-01 09:00:00']);
        $this->givenUserRecord(['name' => '鈴木 花子', 'email' => 'hanako@example.com', 'created_at' => '2024-05-01 09:00:00']);

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total_pages', 1)
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'email', 'role', 'role_label', 'status', 'status_label', 'created_at', 'updated_at'],
                ],
                'meta' => ['total', 'page', 'per_page', 'total_pages'],
            ]);

        // 既定の並び順は作成日時の降順。
        $this->assertSame('鈴木 花子', $response->json('data.0.name'));
    }

    public function test_キーワードと権限で絞り込める(): void
    {
        $this->givenUserRecord(['name' => '山田 太郎', 'email' => 'taro@example.com', 'role' => 'admin']);
        $this->givenUserRecord(['name' => '鈴木 花子', 'email' => 'hanako@example.com', 'role' => 'member']);

        $this->getJson('/api/users?keyword=hanako')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'hanako@example.com');

        $this->getJson('/api/users?role=admin')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.role', 'admin');
    }

    public function test_ユーザーを登録できる(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => '山田 太郎',
            'email' => 'TARO@Example.com',
            'password' => 'password1',
            'role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', '山田 太郎')
            ->assertJsonPath('data.email', 'taro@example.com')
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.role_label', '管理者')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.status_label', '有効');

        $this->assertDatabaseHas('users', ['email' => 'taro@example.com', 'role' => 'admin']);
        $this->assertResponseHasNoPassword($response->json(), 'password1');
    }

    public function test_ユーザーを取得できる(): void
    {
        $record = $this->givenUserRecord();

        $this->getJson('/api/users/'.$record->getAttribute('id'))
            ->assertOk()
            ->assertJsonPath('data.id', $record->getAttribute('id'))
            ->assertJsonPath('data.email', 'taro@example.com');
    }

    public function test_ユーザーを更新できる(): void
    {
        $record = $this->givenUserRecord();

        $response = $this->patchJson('/api/users/'.$record->getAttribute('id'), [
            'name' => '山田 次郎',
            'password' => 'newpassword1',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', '山田 次郎')
            ->assertJsonPath('data.email', 'taro@example.com');

        $this->assertDatabaseHas('users', ['id' => $record->getAttribute('id'), 'name' => '山田 次郎']);
        $this->assertResponseHasNoPassword($response->json(), 'newpassword1');

        // パスワードは再ハッシュされ、平文のままでは保存されない。
        $this->assertDatabaseMissing('users', ['password_hash' => 'newpassword1']);
    }

    public function test_pu_tでも更新できる(): void
    {
        $record = $this->givenUserRecord();

        $this->putJson('/api/users/'.$record->getAttribute('id'), [
            'name' => '山田 次郎',
            'email' => 'jiro@example.com',
            'role' => 'manager',
            'status' => 'suspended',
        ])
            ->assertOk()
            ->assertJsonPath('data.email', 'jiro@example.com')
            ->assertJsonPath('data.role', 'manager')
            ->assertJsonPath('data.status', 'suspended')
            ->assertJsonPath('data.status_label', '停止中');
    }

    public function test_ユーザーを削除できる(): void
    {
        $record = $this->givenUserRecord();

        $response = $this->deleteJson('/api/users/'.$record->getAttribute('id'));

        $response->assertNoContent();
        $this->assertSame('', $response->getContent());
        $this->assertDatabaseMissing('users', ['id' => $record->getAttribute('id')]);
    }

    public function test_存在しないユーザーは404になる(): void
    {
        $id = UserId::generate()->value();

        $this->getJson('/api/users/'.$id)->assertNotFound();
        $this->patchJson('/api/users/'.$id, ['name' => '山田 次郎'])->assertNotFound();
        $this->deleteJson('/api/users/'.$id)->assertNotFound();
    }

    public function test_メールアドレスが重複すると409になる(): void
    {
        $this->givenUserRecord(['email' => 'taro@example.com']);

        $this->postJson('/api/users', [
            'name' => '別の太郎',
            'email' => 'taro@example.com',
            'password' => 'password1',
            'role' => 'member',
        ])->assertStatus(409);
    }

    public function test_更新時にメールアドレスが重複すると409になる(): void
    {
        $this->givenUserRecord(['email' => 'taro@example.com']);
        $target = $this->givenUserRecord(['email' => 'hanako@example.com']);

        $this->patchJson('/api/users/'.$target->getAttribute('id'), [
            'email' => 'taro@example.com',
        ])->assertStatus(409);
    }

    public function test_入力が不正な場合は422になる(): void
    {
        $this->postJson('/api/users', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);

        $this->postJson('/api/users', [
            'name' => '山田 太郎',
            'email' => 'not-an-email',
            'password' => 'short',
            'role' => 'owner',
            'status' => 'unknown',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'role', 'status']);
    }

    public function test_英数字を含まないパスワードは422になる(): void
    {
        // FormRequest は通過するが、ドメインの不変条件違反として 422 になる。
        $this->postJson('/api/users', [
            'name' => '山田 太郎',
            'email' => 'taro@example.com',
            'password' => 'abcdefghij',
            'role' => 'member',
        ])->assertStatus(422);
    }

    public function test_レスポンスにパスワードが含まれない(): void
    {
        $this->givenUserRecord();

        $index = $this->getJson('/api/users');
        $index->assertOk();
        $this->assertResponseHasNoPassword($index->json(), 'password1');

        $show = $this->getJson('/api/users/'.UserRecord::query()->firstOrFail()->getAttribute('id'));
        $show->assertOk();
        $this->assertResponseHasNoPassword($show->json(), 'password1');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function givenUserRecord(array $overrides = []): UserRecord
    {
        $timestamp = $overrides['created_at'] ?? '2024-04-01 09:00:00';

        return UserRecord::create(array_merge([
            'id' => UserId::generate()->value(),
            'name' => '山田 太郎',
            'email' => 'taro@example.com',
            'password_hash' => password_hash('password1', PASSWORD_BCRYPT),
            'role' => 'member',
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $overrides));
    }

    /**
     * レスポンスにパスワード関連の情報が含まれていないことを検証する。
     */
    private function assertResponseHasNoPassword(mixed $payload, string $plainPassword): void
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString($plainPassword, $encoded);
        $this->assertStringNotContainsString('$2y$', $encoded);

        foreach (self::FORBIDDEN_KEYS as $key) {
            $this->assertStringNotContainsString(sprintf('"%s"', $key), $encoded);
        }
    }
}
