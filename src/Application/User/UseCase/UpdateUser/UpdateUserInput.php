<?php

declare(strict_types=1);

namespace Application\User\UseCase\UpdateUser;

use SensitiveParameter;

/**
 * ユーザー更新の入力 DTO。null は「変更なし」を意味する（部分更新）。
 */
final readonly class UpdateUserInput
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $email = null,
        #[SensitiveParameter] public ?string $password = null,
        public ?string $role = null,
        public ?string $status = null,
    ) {}
}
