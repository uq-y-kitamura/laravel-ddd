<?php

declare(strict_types=1);

namespace Application\Employee\UseCase;

/**
 * 社員更新の入力 DTO。null は「変更なし」を意味する（部分更新）。
 */
final readonly class UpdateEmployeeInput
{
    public function __construct(
        public string $id,
        public ?string $employeeNumber = null,
        public ?string $lastName = null,
        public ?string $firstName = null,
        public ?string $email = null,
        public ?string $department = null,
        public ?string $hireDate = null,
        public ?string $status = null,
    ) {}

    /**
     * 氏名は姓・名のどちらか一方でも指定されていれば変更対象とする。
     */
    public function hasNameChange(): bool
    {
        return $this->lastName !== null || $this->firstName !== null;
    }
}
