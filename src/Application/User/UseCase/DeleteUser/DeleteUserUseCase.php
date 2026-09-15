<?php

declare(strict_types=1);

namespace Application\User\UseCase\DeleteUser;

use Application\Shared\Transaction\TransactionManagerInterface;
use Domain\User\Exception\LastAdminCannotBeRemovedException;
use Domain\User\Exception\UserNotFoundException;
use Domain\User\Repository\UserRepositoryInterface;
use Domain\User\ValueObject\UserId;
use Domain\User\ValueObject\UserRole;

/**
 * ユーザーを削除するユースケース。
 */
final readonly class DeleteUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private TransactionManagerInterface $transaction,
    ) {}

    /**
     * @throws UserNotFoundException 対象のユーザーが存在しない場合
     * @throws LastAdminCannotBeRemovedException 最後の管理者を削除しようとした場合
     */
    public function execute(DeleteUserInput $input): void
    {
        $id = UserId::fromString($input->id);

        $this->transaction->run(function () use ($id): void {
            $user = $this->users->findById($id);

            if ($user === null) {
                throw UserNotFoundException::fromId($id);
            }

            if ($user->isAdmin() && $this->users->countByRole(UserRole::Admin, $id) === 0) {
                throw LastAdminCannotBeRemovedException::occurred();
            }

            $this->users->delete($id);
        });
    }
}
