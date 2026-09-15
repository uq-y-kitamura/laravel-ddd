<?php

declare(strict_types=1);

namespace Application\User\UseCase\GetUser;

use Application\User\Dto\UserOutput;
use Domain\User\Exception\UserNotFoundException;
use Domain\User\Repository\UserRepositoryInterface;
use Domain\User\ValueObject\UserId;

/**
 * ユーザーを 1 件取得するユースケース（読み取り専用）。
 */
final readonly class GetUserUseCase
{
    public function __construct(private UserRepositoryInterface $users) {}

    /**
     * @throws UserNotFoundException 対象のユーザーが存在しない場合
     */
    public function execute(GetUserInput $input): UserOutput
    {
        $id = UserId::fromString($input->id);
        $user = $this->users->findById($id);

        if ($user === null) {
            throw UserNotFoundException::fromId($id);
        }

        return UserOutput::fromEntity($user);
    }
}
