<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Eloquent\Repository;

use Domain\Employee\Criteria\EmployeeSearchCriteria;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Exception\EmployeeNotFoundException;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Employee\ValueObject\EmployeeId;
use Domain\Employee\ValueObject\EmployeeNumber;
use Domain\Shared\Criteria\PaginatedResult;
use Domain\Shared\ValueObject\EmailAddress;
use Illuminate\Database\Eloquent\Builder;
use Infrastructure\Persistence\Eloquent\Mapper\EmployeeMapper;
use Infrastructure\Persistence\Eloquent\Model\EmployeeRecord;

/**
 * Eloquent による社員リポジトリの実装（アダプタ）。
 */
final readonly class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    /**
     * LIKE 検索のエスケープ文字。
     * バックスラッシュは MySQL と SQLite で文字列リテラルの解釈が異なるため、
     * 両者でそのまま書ける "!" を採用する。
     */
    private const LIKE_ESCAPE_CHARACTER = '!';

    public function __construct(private EmployeeMapper $mapper) {}

    public function nextIdentity(): EmployeeId
    {
        return EmployeeId::generate();
    }

    public function findById(EmployeeId $id): ?Employee
    {
        $record = EmployeeRecord::query()->find($id->value());

        return $record === null ? null : $this->mapper->toEntity($record);
    }

    public function existsByEmployeeNumber(EmployeeNumber $number, ?EmployeeId $excludeId = null): bool
    {
        $query = EmployeeRecord::query()->where('employee_number', $number->value());

        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId->value());
        }

        return $query->exists();
    }

    public function existsByEmail(EmailAddress $email, ?EmployeeId $excludeId = null): bool
    {
        $query = EmployeeRecord::query()->where('email', $email->value());

        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId->value());
        }

        return $query->exists();
    }

    public function search(EmployeeSearchCriteria $criteria): PaginatedResult
    {
        $query = EmployeeRecord::query();

        if ($criteria->keyword !== null) {
            $pattern = '%'.$this->escapeLike($criteria->keyword).'%';

            // OR 条件をグループ化し、他の絞り込み条件と混ざらないようにする。
            $query->where(function (Builder $grouped) use ($pattern): void {
                foreach (['last_name', 'first_name', 'employee_number', 'email'] as $column) {
                    $grouped->orWhereRaw(
                        sprintf('%s LIKE ? ESCAPE \'%s\'', $column, self::LIKE_ESCAPE_CHARACTER),
                        [$pattern]
                    );
                }
            });
        }

        if ($criteria->department !== null) {
            $query->where('department', $criteria->department->value());
        }

        if ($criteria->status !== null) {
            $query->where('status', $criteria->status->value);
        }

        $total = $query->count();

        if ($total === 0) {
            return new PaginatedResult([], 0, $criteria->pagination);
        }

        $records = $query
            ->orderBy($criteria->sortBy, $criteria->sortDirection)
            // 並び順を一意に確定させるための第 2 ソートキー。
            ->orderBy('id')
            ->offset($criteria->pagination->offset())
            ->limit($criteria->pagination->limit())
            ->get();

        $items = [];

        foreach ($records as $record) {
            $items[] = $this->mapper->toEntity($record);
        }

        return new PaginatedResult($items, $total, $criteria->pagination);
    }

    public function save(Employee $employee): void
    {
        $record = EmployeeRecord::query()->find($employee->id()->value()) ?? new EmployeeRecord;

        $this->mapper->fillRecord($record, $employee);

        // created_at / updated_at はエンティティが管理するため Eloquent の自動更新を止める。
        $record->timestamps = false;
        $record->save();
    }

    public function delete(EmployeeId $id): void
    {
        $deleted = EmployeeRecord::query()->whereKey($id->value())->delete();

        if ($deleted === 0) {
            throw EmployeeNotFoundException::fromId($id);
        }
    }

    /**
     * LIKE のワイルドカード（% _）とエスケープ文字自身を無効化する。
     */
    private function escapeLike(string $keyword): string
    {
        return str_replace(
            [self::LIKE_ESCAPE_CHARACTER, '%', '_'],
            [
                self::LIKE_ESCAPE_CHARACTER.self::LIKE_ESCAPE_CHARACTER,
                self::LIKE_ESCAPE_CHARACTER.'%',
                self::LIKE_ESCAPE_CHARACTER.'_',
            ],
            $keyword
        );
    }
}
