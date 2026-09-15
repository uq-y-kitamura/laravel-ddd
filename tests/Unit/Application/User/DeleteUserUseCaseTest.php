<?php

declare(strict_types=1);

namespace Tests\Unit\Application\User;

use Application\Shared\Transaction\TransactionManagerInterface;
use Application\User\UseCase\DeleteUser\DeleteUserInput;
use Application\User\UseCase\DeleteUser\DeleteUserUseCase;
use DateTimeImmutable;
use Domain\Shared\ValueObject\EmailAddress;
use Domain\User\Entity\User;
use Domain\User\Exception\LastAdminCannotBeRemovedException;
use Domain\User\Exception\UserNotFoundException;
use Domain\User\ValueObject\HashedPassword;
use Domain\User\ValueObject\UserId;
use Domain\User\ValueObject\UserName;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryUserRepository;

final class DeleteUserUseCaseTest extends TestCase
{
    private InMemoryUserRepository $users;

    private DeleteUserUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = new InMemoryUserRepository;
        $this->useCase = new DeleteUserUseCase($this->users, $this->transactionManager());
    }

    public function test_ユーザーを削除できる(): void
    {
        $user = $this->givenUser();

        $this->useCase->execute(new DeleteUserInput($user->id()->value()));

        $this->assertSame(0, $this->users->count());
        $this->assertNull($this->users->findById($user->id()));
    }

    public function test_対象が存在しない場合は例外になる(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->useCase->execute(new DeleteUserInput(UserId::generate()->value()));
    }

    public function test_最後の管理者は削除できない(): void
    {
        $admin = $this->givenUser(role: UserRole::Admin);
        $this->givenUser(email: 'member@example.com', role: UserRole::Member);

        $this->expectException(LastAdminCannotBeRemovedException::class);
        $this->expectExceptionMessage('管理者が 0 人になる操作は実行できません。');

        try {
            $this->useCase->execute(new DeleteUserInput($admin->id()->value()));
        } finally {
            $this->assertSame(2, $this->users->count());
        }
    }

    public function test_他に管理者がいれば削除できる(): void
    {
        $admin = $this->givenUser(role: UserRole::Admin);
        $this->givenUser(email: 'admin2@example.com', role: UserRole::Admin);

        $this->useCase->execute(new DeleteUserInput($admin->id()->value()));

        $this->assertSame(1, $this->users->count());
    }

    public function test_停止中の管理者でも最後の1人なら削除できない(): void
    {
        $admin = $this->givenUser(role: UserRole::Admin, status: UserStatus::Suspended);

        $this->expectException(LastAdminCannotBeRemovedException::class);

        $this->useCase->execute(new DeleteUserInput($admin->id()->value()));
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
            new DateTimeImmutable('2024-04-01 09:00:00'),
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
