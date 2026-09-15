<?php

declare(strict_types=1);

namespace Application\User\UseCase\CreateUser;

use Application\Shared\Transaction\TransactionManagerInterface;
use Application\User\Dto\UserOutput;
use DateTimeImmutable;
use Domain\Shared\ValueObject\EmailAddress;
use Domain\User\Entity\User;
use Domain\User\Exception\DuplicateUserEmailException;
use Domain\User\Repository\UserRepositoryInterface;
use Domain\User\Service\PasswordHasherInterface;
use Domain\User\ValueObject\PlainPassword;
use Domain\User\ValueObject\UserName;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;

/**
 * ユーザーを新規登録するユースケース。
 */
final readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $passwordHasher,
        private TransactionManagerInterface $transaction,
    ) {}

    /**
     * @throws DuplicateUserEmailException メールアドレスが既に使用されている場合
     */
    public function execute(CreateUserInput $input): UserOutput
    {
        $name = new UserName($input->name);
        $email = EmailAddress::fromString($input->email);
        $role = UserRole::fromString($input->role);
        $status = $input->status === null ? UserStatus::Active : UserStatus::fromString($input->status);

        // ハッシュ化はコストが高いため、トランザクション境界の外で行う。
        $hashedPassword = $this->passwordHasher->hash(new PlainPassword($input->password));

        return $this->transaction->run(function () use ($name, $email, $hashedPassword, $role, $status): UserOutput {
            if ($this->users->existsByEmail($email)) {
                throw DuplicateUserEmailException::fromEmail($email);
            }

            $user = User::create(
                $this->users->nextIdentity(),
                $name,
                $email,
                $hashedPassword,
                $role,
                $status,
                $this->currentTime(),
            );

            $this->users->save($user);

            return UserOutput::fromEntity($user);
        });
    }

    private function currentTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}
