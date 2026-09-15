<?php

declare(strict_types=1);

namespace Presentation\Api\Resource;

use Application\Employee\Dto\EmployeeListOutput;
use Application\Employee\Dto\EmployeeOutput;

/**
 * 社員 API のレスポンス封筒を組み立てるプレゼンター。
 * 単体は {"data": {...}}、一覧は {"data": [...], "meta": {...}} で返す。
 */
final class EmployeeJsonPresenter
{
    private function __construct() {}

    /**
     * @return array{data: array<string, mixed>}
     */
    public static function item(EmployeeOutput $output): array
    {
        return ['data' => $output->toArray()];
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array{total: int, page: int, per_page: int, total_pages: int}}
     */
    public static function collection(EmployeeListOutput $output): array
    {
        return [
            'data' => array_values(array_map(
                static fn (EmployeeOutput $item): array => $item->toArray(),
                $output->items
            )),
            'meta' => [
                'total' => $output->total,
                'page' => $output->page,
                'per_page' => $output->perPage,
                'total_pages' => $output->totalPages,
            ],
        ];
    }
}
