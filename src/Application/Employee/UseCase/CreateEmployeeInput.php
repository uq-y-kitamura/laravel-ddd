<?php

declare(strict_types=1);

namespace Application\Employee\UseCase;

/**
 * 社員登録の入力 DTO。値の妥当性検証はドメイン層の値オブジェクトが担う。
 */
final readonly class CreateEmployeeInput
{
    public function __construct(
        public string $employeeNumber,
        public string $lastName,
        public string $firstName,
        public string $email,
        public string $department,
        public string $hireDate,
        public ?string $status = null,
    ) {}
}
