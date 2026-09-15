<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User\Entity;

use Closure;
use DateTimeImmutable;
use Domain\Shared\Exception\InvalidValueException;
use Domain\Shared\ValueObject\EmailAddress;
use Domain\User\Entity\User;
use Domain\User\ValueObject\HashedPassword;
use Domain\User\ValueObject\PlainPassword;
use Domain\User\ValueObject\UserId;
use Domain\User\ValueObject\UserName;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakePasswordHasher;

final class UserTest extends TestCase
{
    private const CREATED_AT = '2024-04-01 09:00:00';

    private const UPDATED_AT = '2024-05-01 10:30:00';

    public function test_ユーザーを生成できる(): void
    {
        $now = new DateTimeImmutable(self::CREATED_AT);
        $id = UserId::generate();

        $user = User::create(
            $id,
            new UserName('山田 太郎'),
            EmailAddress::fromString('taro@example.com'),
            new HashedPassword('hashed:password1'),
            UserRole::Admin,
            UserStatus::Active,
            $now,
        );

        $this->assertTrue($user->id()->equals($id));
        $this->assertSame('山田 太郎', $user->name()->value());
        $this->assertSame('taro@example.com', $user->email()->value());
        $this->assertSame('hashed:password1', $user->password()->value());
        $this->assertSame(UserRole::Admin, $user->role());
        $this->assertSame(UserStatus::Active, $user->status());
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isSuspended());
        $this->assertEquals($now, $user->createdAt());
        $this->assertEquals($now, $user->updatedAt());
    }

    public function test_永続化データから復元できる(): void
    {
        $createdAt = new DateTimeImmutable(self::CREATED_AT);
        $updatedAt = new DateTimeImmutable(self::UPDATED_AT);

        $user = User::reconstruct(
            UserId::generate(),
            new UserName('山田 太郎'),
            EmailAddress::fromString('taro@example.com'),
            new HashedPassword('hashed:password1'),
            UserRole::Member,
            UserStatus::Suspended,
            $createdAt,
            $updatedAt,
        );

        $this->assertEquals($createdAt, $user->createdAt());
        $this->assertEquals($updatedAt, $user->updatedAt());
        $this->assertTrue($user->isSuspended());
    }

    public function test_表示名を変更すると更新日時が更新される(): void
    {
        $user = $this->activeUser();
        $now = new DateTimeImmutable(self::UPDATED_AT);

        $user->rename(new UserName('山田 次郎'), $now);

        $this->assertSame('山田 次郎', $user->name()->value());
        $this->assertEquals($now, $user->updatedAt());
        $this->assertEquals(new DateTimeImmutable(self::CREATED_AT), $user->createdAt());
    }

    public function test_同じ値への変更では更新日時が変わらない(): void
    {
        $user = $this->activeUser();
        $later = new DateTimeImmutable(self::UPDATED_AT);

        $user->rename(new UserName('山田 太郎'), $later);
        $user->changeEmail(EmailAddress::fromString('TARO@Example.com'), $later);
        $user->changePassword(new HashedPassword('hashed:password1'), $later);
        $user->changeRole(UserRole::Member, $later);

        $this->assertEquals(new DateTimeImmutable(self::CREATED_AT), $user->updatedAt());
    }

    public function test_メールアドレスを変更できる(): void
    {
        $user = $this->activeUser();
        $now = new DateTimeImmutable(self::UPDATED_AT);

        $user->changeEmail(EmailAddress::fromString('NEW@Example.com'), $now);

        $this->assertSame('new@example.com', $user->email()->value());
        $this->assertEquals($now, $user->updatedAt());
    }

    public function test_パスワードを変更できる(): void
    {
        $user = $this->activeUser();
        $now = new DateTimeImmutable(self::UPDATED_AT);

        $user->changePassword(new HashedPassword('hashed:newpassword1'), $now);

        $this->assertSame('hashed:newpassword1', $user->password()->value());
        $this->assertEquals($now, $user->updatedAt());
    }

    public function test_権限を変更できる(): void
    {
        $user = $this->activeUser();
        $now = new DateTimeImmutable(self::UPDATED_AT);

        $user->changeRole(UserRole::Manager, $now);

        $this->assertSame(UserRole::Manager, $user->role());
        $this->assertEquals($now, $user->updatedAt());
    }

    public function test_有効なユーザーを停止できる(): void
    {
        $user = $this->activeUser();
        $now = new DateTimeImmutable(self::UPDATED_AT);

        $user->suspend($now);

        $this->assertSame(UserStatus::Suspended, $user->status());
        $this->assertEquals($now, $user->updatedAt());
    }

    public function test_停止中のユーザーへの停止は冪等に何もしない(): void
    {
        $user = $this->suspendedUser();

        $user->suspend(new DateTimeImmutable(self::UPDATED_AT));

        $this->assertSame(UserStatus::Suspended, $user->status());
        $this->assertEquals(new DateTimeImmutable(self::CREATED_AT), $user->updatedAt());
    }

    public function test_停止中のユーザーを有効化できる(): void
    {
        $user = $this->suspendedUser();
        $now = new DateTimeImmutable(self::UPDATED_AT);

        $user->activate($now);

        $this->assertSame(UserStatus::Active, $user->status());
        $this->assertEquals($now, $user->updatedAt());
    }

    public function test_有効なユーザーへの有効化は冪等に何もしない(): void
    {
        $user = $this->activeUser();

        $user->activate(new DateTimeImmutable(self::UPDATED_AT));

        $this->assertEquals(new DateTimeImmutable(self::CREATED_AT), $user->updatedAt());
    }

    public function test_有効化した直後は他の変更も適用できる(): void
    {
        $user = $this->suspendedUser();
        $now = new DateTimeImmutable(self::UPDATED_AT);

        $user->activate($now);
        $user->rename(new UserName('山田 次郎'), $now);

        $this->assertSame('山田 次郎', $user->name()->value());
    }

    #[DataProvider('停止中に拒否される操作')]
    public function test_停止中のユーザーは変更を拒否する(Closure $operation): void
    {
        $user = $this->suspendedUser();

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('停止中のユーザーは変更できません。');

        $operation($user, new DateTimeImmutable(self::UPDATED_AT));
    }

    /**
     * @return array<string, array{Closure}>
     */
    public static function 停止中に拒否される操作(): array
    {
        return [
            '表示名の変更' => [
                static fn (User $user, DateTimeImmutable $now) => $user->rename(new UserName('山田 次郎'), $now),
            ],
            'メールアドレスの変更' => [
                static fn (User $user, DateTimeImmutable $now) => $user->changeEmail(
                    EmailAddress::fromString('new@example.com'),
                    $now
                ),
            ],
            'パスワードの変更' => [
                static fn (User $user, DateTimeImmutable $now) => $user->changePassword(
                    new HashedPassword('hashed:newpassword1'),
                    $now
                ),
            ],
            '権限の変更' => [
                static fn (User $user, DateTimeImmutable $now) => $user->changeRole(UserRole::Admin, $now),
            ],
        ];
    }

    public function test_パスワードを検証できる(): void
    {
        $hasher = new FakePasswordHasher;
        $user = $this->activeUser();

        $this->assertTrue($user->verifyPassword(new PlainPassword('password1'), $hasher));
        $this->assertFalse($user->verifyPassword(new PlainPassword('password2'), $hasher));
    }

    private function activeUser(): User
    {
        return $this->userWith(UserStatus::Active);
    }

    private function suspendedUser(): User
    {
        return $this->userWith(UserStatus::Suspended);
    }

    private function userWith(UserStatus $status): User
    {
        return User::create(
            UserId::generate(),
            new UserName('山田 太郎'),
            EmailAddress::fromString('taro@example.com'),
            new HashedPassword('hashed:password1'),
            UserRole::Member,
            $status,
            new DateTimeImmutable(self::CREATED_AT),
        );
    }
}
