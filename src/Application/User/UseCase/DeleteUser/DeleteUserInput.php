<?php

declare(strict_types=1);

namespace Application\User\UseCase\DeleteUser;

/**
 * ユーザー削除の入力 DTO。
 */
final readonly class DeleteUserInput
{
    public function __construct(public string $id) {}
}
