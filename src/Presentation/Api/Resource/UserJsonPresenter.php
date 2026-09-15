<?php

declare(strict_types=1);

namespace Presentation\Api\Resource;

use Application\User\Dto\UserListOutput;
use Application\User\Dto\UserOutput;
use DateTimeInterface;

/**
 * ユーザー API のレスポンス封筒を組み立てるプレゼンター。
 * 単体は {"data": {...}}、一覧は {"data": [...], "meta": {...}} で返す。
 *
 * パスワード（平文・ハッシュのいずれも）はレスポンスに含めない。
 */
final class UserJsonPresenter
{
    private function __construct() {}

    /**
     * @return array{data: array<string, mixed>}
     */
    public static function item(UserOutput $output): array
    {
        return ['data' => self::toArray($output)];
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array{total: int, page: int, per_page: int, total_pages: int}}
     */
    public static function collection(UserListOutput $output): array
    {
        return [
            'data' => array_values(array_map(
                static fn (UserOutput $item): array => self::toArray($item),
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

    /**
     * ユーザー 1 件分の JSON 表現。
     *
     * @return array<string, mixed>
     */
    public static function toArray(UserOutput $output): array
    {
        return [
            'id' => $output->id,
            'name' => $output->name,
            'email' => $output->email,
            'role' => $output->role->value,
            'role_label' => $output->role->label(),
            'status' => $output->status->value,
            'status_label' => $output->status->label(),
            'created_at' => $output->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $output->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
