<?php

declare(strict_types=1);

namespace Tests\Unit\Application\User;

use Application\Shared\Transaction\TransactionManagerInterface;
use Application\User\UseCase\UpdateUser\UpdateUserInput;
use Application\User\UseCase\UpdateUser\UpdateUserUseCase;
use DateTimeImmutable;
use Domain\Shared\Exception\InvalidValueException;
use Domain\Shared\ValueObject\EmailAddress;
use Domain\User\Entity\User;
use Domain\User\Exception\DuplicateUserEmailException;
use Domain\User\Exception\LastAdminCannotBeRemovedException;
use Domain\User\Exception\UserNotFoundException;
use Domain\User\ValueObject\HashedPassword;
use Domain\User\ValueObject\UserId;
use Domain\User\ValueObject\UserName;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakePasswordHasher;
use Tests\Support\InMemoryUserRepository;

final class UpdateUserUseCaseTest extends TestCase
{
    private const CREATED_AT = '2024-04-01 09:00:00';

    private InMemoryUserRepository $users;

    private UpdateUserUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = new InMemoryUserRepository;
        $this->useCase = new UpdateUserUseCase(
            $this->users,
            new FakePasswordHasher,
            $this->transactionManager(),
        );
    }

    public function test_表示名だけを部分更新できる(): void
    {
        $user = $this->givenUser();

        $output = $this->useCase->execute(new UpdateUserInput(
            id: $user->id()->value(),
            name: '山田 次郎',
        ));

        $this->assertSame('山田 次郎', $output->name);
        $this->assertSame('taro@example.com', $output->email);
        $this->assertSame(UserRole::Member, $output->role);
        $this->assertSame(UserStatus::Active, $output->status);
        $this->assertGreaterThan($output->createdAt, $output->updatedAt);
    }

    public function test_パスワードを更新すると再ハッシュされる(): void
    {
        $user = $this->givenUser();

        $this->useCase->execute(new UpdateUserInput(
            id: $user->id()->value(),
            password: 'newpassword1',
        ));

        $saved = $this->users->findById($user->id());

        $this->assertNotNull($saved);
        $this->assertSame('hashed:newpassword1', $saved->password()->value());
    }

    public function test_自分自身のメールアドレスは重複扱いにならない(): void
    {
        $user = $this->givenUser();

        $output = $this->useCase->execute(new UpdateUserInput(
            id: $user->id()->value(),
            email: 'TARO@Example.com',
        ));

        $this->assertSame('taro@example.com', $output->email);
    }

    public function test_他のユーザーとメールアドレスが重複する場合は例外になる(): void
    {
        $user = $this->givenUser();
        $this->givenUser(email: 'jiro@example.com');

        $this->expectException(DuplicateUserEmailException::class);

        $this->useCase->execute(new UpdateUserInput(
            id: $user->id()->value(),
            email: 'jiro@example.com',
        ));
    }

    public function test_対象が存在しない場合は例外になる(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->useCase->execute(new UpdateUserInput(
            id: UserId::generate()->value(),
            name: '山田 次郎',
        ));
    }

    public function test_最後の管理者は停止できない(): void
    {
        $admin = $this->givenUser(role: UserRole::Admin);
        $this->givenUser(email: 'member@example.com', role: UserRole::Member);

        $this->expectException(LastAdminCannotBeRemovedException::class);
        $this->expectExceptionMessage('管理者が 0 人になる操作は実行できません。');

        $this->useCase->execute(new UpdateUserInput(
            id: $admin->id()->value(),
            status: 'suspended',
        ));
    }

    public function test_他に管理者がいれば停止できる(): void
    {
        $admin = $this->givenUser(role: UserRole::Admin);
        $this->givenUser(email: 'admin2@example.com', role: UserRole::Admin);

        $output = $this->useCase->execute(new UpdateUserInput(
            id: $admin->id()->value(),
            status: 'suspended',
        ));

        $this->assertSame(UserStatus::Suspended, $output->status);
    }

    public function test_最後の管理者は降格できない(): void
    {
        $admin = $this->givenUser(role: UserRole::Admin);

        $this->expectException(LastAdminCannotBeRemovedException::class);

        $this->useCase->execute(new UpdateUserInput(
            id: $admin->id()->value(),
            role: 'member',
        ));
    }

    public function test_停止中のユーザーは有効化と同時に他の項目も更新できる(): void
    {
        $user = $this->givenUser(status: UserStatus::Suspended);

        $output = $this->useCase->execute(new UpdateUserInput(
            id: $user->id()->value(),
            name: '山田 次郎',
            status: 'active',
        ));

        $this->assertSame('山田 次郎', $output->name);
        $this->assertSame(UserStatus::Active, $output->status);
    }

    public function test_停止中のユーザーは有効化せずに変更できない(): void
    {
        $user = $this->givenUser(status: UserStatus::Suspended);

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('停止中のユーザーは変更できません。');

        $this->useCase->execute(new UpdateUserInput(
            id: $user->id()->value(),
            name: '山田 次郎',
        ));
    }

    public function test_変更がなければ更新日時は変わらない(): void
    {
        $user = $this->givenUser();

        $output = $this->useCase->execute(new UpdateUserInput(
            id: $user->id()->value(),
            name: '山田 太郎',
        ));

        $this->assertSame(
            (new DateTimeImmutable(self::CREATED_AT))->format('c'),
            $output->updatedAt->format('c')
        );
    }

    private function givenUser(
        string $email = 'taro@example.com',
        UserRole $role = UserRole::Member,
        UserStatus $status = UserStatus::Active,
    ): User {
        $user = User::create(
            UserId::generate(),
            new UserName('山田 太郎'),
            EmailAddress::fromString($email),
            new HashedPassword('hashed:password1'),
            $role,
            $status,
            new DateTimeImmutable(self::CREATED_AT),
        );

        $this->users->save($user);

        return $user;
    }

    private function transactionManager(): TransactionManagerInterface
    {
        return new class implements TransactionManagerInterface
        {
            public function run(callable $operation): mixed
            {
                return $operation();
            }
        };
    }
}
