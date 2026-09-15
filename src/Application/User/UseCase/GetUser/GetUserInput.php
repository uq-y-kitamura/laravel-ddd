<?php

declare(strict_types=1);

namespace Application\User\UseCase\GetUser;

/**
 * ユーザー 1 件取得の入力 DTO。
 */
final readonly class GetUserInput
{
    public function __construct(public string $id) {}
}
