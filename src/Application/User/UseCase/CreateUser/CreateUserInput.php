<?php

declare(strict_types=1);

namespace Application\User\UseCase\CreateUser;

use SensitiveParameter;

/**
 * ユーザー新規登録の入力 DTO。
 */
final readonly class CreateUserInput
{
    /**
     * @param  ?string  $status  省略時は有効（active）として扱う
     */
    public function __construct(
        public string $name,
        public string $email,
        #[SensitiveParameter] public string $password,
        public string $role,
        public ?string $status = null,
    ) {}
}
