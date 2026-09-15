<?php

declare(strict_types=1);

namespace Domain\Employee\Repository;

use Domain\Employee\Criteria\EmployeeSearchCriteria;
use Domain\Employee\Entity\Employee;
use Domain\Employee\ValueObject\EmployeeId;
use Domain\Employee\ValueObject\EmployeeNumber;
use Domain\Shared\Criteria\PaginatedResult;
use Domain\Shared\ValueObject\EmailAddress;

/**
 * 社員集約の永続化ポート。
 */
interface EmployeeRepositoryInterface
{
    /**
     * 新しい社員 ID を採番する。
     */
    public function nextIdentity(): EmployeeId;

    public function findById(EmployeeId $id): ?Employee;

    /**
     * 社員番号の重複有無を返す。$excludeId を渡すとその社員自身は対象外とする。
     */
    public function existsByEmployeeNumber(EmployeeNumber $number, ?EmployeeId $excludeId = null): bool;

    /**
     * メールアドレスの重複有無を返す。$excludeId を渡すとその社員自身は対象外とする。
     */
    public function existsByEmail(EmailAddress $email, ?EmployeeId $excludeId = null): bool;

    /**
     * @return PaginatedResult<Employee>
     */
    public function search(EmployeeSearchCriteria $criteria): PaginatedResult;

    /**
     * 新規登録・更新の双方を行う。
     */
    public function save(Employee $employee): void;

    /**
     * 存在しない場合は EmployeeNotFoundException を送出する。
     */
    public function delete(EmployeeId $id): void;
}
