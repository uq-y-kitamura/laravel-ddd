<?php

declare(strict_types=1);

namespace Tests\Unit\Application\User;

use Application\Shared\Transaction\TransactionManagerInterface;
use Application\User\UseCase\CreateUser\CreateUserInput;
use Application\User\UseCase\CreateUser\CreateUserUseCase;
use Domain\Shared\Exception\InvalidValueException;
use Domain\Shared\ValueObject\EmailAddress;
use Domain\User\Exception\DuplicateUserEmailException;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakePasswordHasher;
use Tests\Support\InMemoryUserRepository;

final class CreateUserUseCaseTest extends TestCase
{
    private InMemoryUserRepository $users;

    private CreateUserUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = new InMemoryUserRepository;
        $this->useCase = new CreateUserUseCase(
            $this->users,
            new FakePasswordHasher,
            $this->transactionManager(),
        );
    }

    public function test_ユーザーを登録できる(): void
    {
        $output = $this->useCase->execute(new CreateUserInput(
            name: '  山田 太郎  ',
            email: 'TARO@Example.com',
            password: 'password1',
            role: 'admin',
            status: 'suspended',
        ));

        $this->assertSame('山田 太郎', $output->name);
        $this->assertSame('taro@example.com', $output->email);
        $this->assertSame(UserRole::Admin, $output->role);
        $this->assertSame(UserStatus::Suspended, $output->status);
        $this->assertSame($output->createdAt->format('c'), $output->updatedAt->format('c'));
        $this->assertSame(1, $this->users->count());
    }

    public function test_状態を省略すると有効になる(): void
    {
        $output = $this->useCase->execute($this->input());

        $this->assertSame(UserStatus::Active, $output->status);
    }

    public function test_パスワードはハッシュ化して保存される(): void
    {
        $output = $this->useCase->execute($this->input());

        $saved = $this->users->findByEmail(EmailAddress::fromString($output->email));

        $this->assertNotNull($saved);
        $this->assertSame('hashed:password1', $saved->password()->value());
    }

    public function test_出力にパスワードは含まれない(): void
    {
        $output = $this->useCase->execute($this->input());

        $keys = array_keys($output->toArray());

        $this->assertSame([
            'id',
            'name',
            'email',
            'role',
            'role_label',
            'status',
            'status_label',
            'created_at',
            'updated_at',
        ], $keys);
    }

    public function test_メールアドレスが重複する場合は例外になる(): void
    {
        $this->useCase->execute($this->input());

        $this->expectException(DuplicateUserEmailException::class);

        $this->useCase->execute($this->input(email: 'TARO@example.com'));
    }

    public function test_不正な権限は例外になる(): void
    {
        $this->expectException(InvalidValueException::class);

        $this->useCase->execute($this->input(role: 'owner'));
    }

    public function test_不正なパスワードは例外になり保存されない(): void
    {
        try {
            $this->useCase->execute($this->input(password: 'short'));
            $this->fail('InvalidValueException が発生しませんでした。');
        } catch (InvalidValueException) {
            $this->assertSame(0, $this->users->count());
        }
    }

    private function input(
        string $name = '山田 太郎',
        string $email = 'taro@example.com',
        string $password = 'password1',
        string $role = 'member',
        ?string $status = null,
    ): CreateUserInput {
        return new CreateUserInput(
            name: $name,
            email: $email,
            password: $password,
            role: $role,
            status: $status,
        );
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
