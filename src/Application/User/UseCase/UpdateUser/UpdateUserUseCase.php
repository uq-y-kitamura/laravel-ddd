<?php

declare(strict_types=1);

namespace Application\User\UseCase\UpdateUser;

use Application\Shared\Transaction\TransactionManagerInterface;
use Application\User\Dto\UserOutput;
use DateTimeImmutable;
use Domain\Shared\ValueObject\EmailAddress;
use Domain\User\Entity\User;
use Domain\User\Exception\DuplicateUserEmailException;
use Domain\User\Exception\LastAdminCannotBeRemovedException;
use Domain\User\Exception\UserNotFoundException;
use Domain\User\Repository\UserRepositoryInterface;
use Domain\User\Service\PasswordHasherInterface;
use Domain\User\ValueObject\PlainPassword;
use Domain\User\ValueObject\UserId;
use Domain\User\ValueObject\UserName;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;

/**
 * ユーザーを更新するユースケース（部分更新）。
 */
final readonly class UpdateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $passwordHasher,
        private TransactionManagerInterface $transaction,
    ) {}

    /**
     * @throws UserNotFoundException 対象のユーザーが存在しない場合
     * @throws DuplicateUserEmailException メールアドレスが他のユーザーと重複する場合
     * @throws LastAdminCannotBeRemovedException 最後の管理者を降格・停止しようとした場合
     */
    public function execute(UpdateUserInput $input): UserOutput
    {
        $id = UserId::fromString($input->id);
        $name = $input->name === null ? null : new UserName($input->name);
        $email = $input->email === null ? null : EmailAddress::fromString($input->email);
        $role = $input->role === null ? null : UserRole::fromString($input->role);
        $status = $input->status === null ? null : UserStatus::fromString($input->status);

        // ハッシュ化はコストが高いため、トランザクション境界の外で行う。
        $hashedPassword = $input->password === null
            ? null
            : $this->passwordHasher->hash(new PlainPassword($input->password));

        return $this->transaction->run(
            function () use ($id, $name, $email, $hashedPassword, $role, $status): UserOutput {
                $user = $this->users->findById($id);

                if ($user === null) {
                    throw UserNotFoundException::fromId($id);
                }

                $now = $this->currentTime();

                // 停止中ユーザーは activate 以外の変更を受け付けないため、有効化を最初に適用する。
                if ($status === UserStatus::Active) {
                    $user->activate($now);
                }

                if ($email !== null && $this->users->existsByEmail($email, $id)) {
                    throw DuplicateUserEmailException::fromEmail($email);
                }

                if ($name !== null) {
                    $user->rename($name, $now);
                }

                if ($email !== null) {
                    $user->changeEmail($email, $now);
                }

                if ($hashedPassword !== null) {
                    $user->changePassword($hashedPassword, $now);
                }

                if ($role !== null && $role !== $user->role()) {
                    // 管理者から降格させる場合は、他に管理者が残っているかを確認する。
                    if ($role !== UserRole::Admin) {
                        $this->assertAnotherAdminRemains($user);
                    }

                    $user->changeRole($role, $now);
                }

                if ($status === UserStatus::Suspended && ! $user->isSuspended()) {
                    $this->assertAnotherAdminRemains($user);
                    $user->suspend($now);
                }

                $this->users->save($user);

                return UserOutput::fromEntity($user);
            }
        );
    }

    /**
     * 対象が管理者の場合、他に管理者が残っていなければ例外を投げる。
     *
     * @throws LastAdminCannotBeRemovedException
     */
    private function assertAnotherAdminRemains(User $user): void
    {
        if (! $user->isAdmin()) {
            return;
        }

        if ($this->users->countByRole(UserRole::Admin, $user->id()) === 0) {
            throw LastAdminCannotBeRemovedException::occurred();
        }
    }

    private function currentTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}
