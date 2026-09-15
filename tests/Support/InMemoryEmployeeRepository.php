<?php

declare(strict_types=1);

namespace Tests\Support;

use Domain\Employee\Criteria\EmployeeSearchCriteria;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Exception\EmployeeNotFoundException;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Employee\ValueObject\EmployeeId;
use Domain\Employee\ValueObject\EmployeeNumber;
use Domain\Shared\Criteria\PaginatedResult;
use Domain\Shared\ValueObject\EmailAddress;

/**
 * ユースケースのユニットテスト用インメモリリポジトリ。
 * 永続化境界を模倣するため、保存・取得時にエンティティを複製する。
 */
final class InMemoryEmployeeRepository implements EmployeeRepositoryInterface
{
    /**
     * @var array<string, Employee>
     */
    private array $employees = [];

    public function __construct(Employee ...$employees)
    {
        foreach ($employees as $employee) {
            $this->save($employee);
        }
    }

    public function nextIdentity(): EmployeeId
    {
        return EmployeeId::generate();
    }

    public function findById(EmployeeId $id): ?Employee
    {
        $employee = $this->employees[$id->value()] ?? null;

        return $employee === null ? null : clone $employee;
    }

    public function existsByEmployeeNumber(EmployeeNumber $number, ?EmployeeId $excludeId = null): bool
    {
        foreach ($this->employees as $employee) {
            if (! $employee->employeeNumber()->equals($number)) {
                continue;
            }

            if ($excludeId !== null && $employee->id()->equals($excludeId)) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function existsByEmail(EmailAddress $email, ?EmployeeId $excludeId = null): bool
    {
        foreach ($this->employees as $employee) {
            if (! $employee->email()->equals($email)) {
                continue;
            }

            if ($excludeId !== null && $employee->id()->equals($excludeId)) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function search(EmployeeSearchCriteria $criteria): PaginatedResult
    {
        $matched = array_values(array_filter(
            $this->employees,
            fn (Employee $employee): bool => $this->matches($employee, $criteria)
        ));

        usort($matched, fn (Employee $a, Employee $b): int => $this->compare($a, $b, $criteria));

        $total = count($matched);
        $page = array_slice($matched, $criteria->pagination->offset(), $criteria->pagination->limit());

        return new PaginatedResult(
            array_map(static fn (Employee $employee): Employee => clone $employee, $page),
            $total,
            $criteria->pagination,
        );
    }

    public function save(Employee $employee): void
    {
        $this->employees[$employee->id()->value()] = clone $employee;
    }

    public function delete(EmployeeId $id): void
    {
        if (! array_key_exists($id->value(), $this->employees)) {
            throw EmployeeNotFoundException::fromId($id);
        }

        unset($this->employees[$id->value()]);
    }

    public function count(): int
    {
        return count($this->employees);
    }

    private function matches(Employee $employee, EmployeeSearchCriteria $criteria): bool
    {
        if ($criteria->department !== null && ! $employee->department()->equals($criteria->department)) {
            return false;
        }

        if ($criteria->status !== null && $employee->status() !== $criteria->status) {
            return false;
        }

        if ($criteria->keyword === null) {
            return true;
        }

        $keyword = mb_strtolower($criteria->keyword);
        $haystacks = [
            $employee->name()->lastName(),
            $employee->name()->firstName(),
            $employee->employeeNumber()->value(),
            $employee->email()->value(),
        ];

        foreach ($haystacks as $haystack) {
            if (str_contains(mb_strtolower($haystack), $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function compare(Employee $a, Employee $b, EmployeeSearchCriteria $criteria): int
    {
        $result = strcmp($this->sortKey($a, $criteria->sortBy), $this->sortKey($b, $criteria->sortBy));

        if ($result === 0) {
            $result = strcmp($a->id()->value(), $b->id()->value());

            return $result;
        }

        return $criteria->sortDirection === EmployeeSearchCriteria::SORT_DIRECTION_DESC ? -$result : $result;
    }

    private function sortKey(Employee $employee, string $sortBy): string
    {
        return match ($sortBy) {
            EmployeeSearchCriteria::SORT_BY_EMPLOYEE_NUMBER => $employee->employeeNumber()->value(),
            EmployeeSearchCriteria::SORT_BY_HIRE_DATE => $employee->hireDate()->toString(),
            default => $employee->createdAt()->format('Y-m-d H:i:s.u'),
        };
    }
}
