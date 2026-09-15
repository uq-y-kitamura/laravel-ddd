<?php

declare(strict_types=1);

namespace Presentation\Api\Request\User;

use Application\User\UseCase\ListUsers\ListUsersInput;
use Domain\Shared\Criteria\Pagination;
use Domain\User\Criteria\UserSearchCriteria;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ユーザー一覧取得リクエスト。
 */
final class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.Pagination::MAX_PER_PAGE],
            'keyword' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(UserRole::values())],
            'status' => ['nullable', Rule::in(UserStatus::values())],
            'sort_by' => ['nullable', Rule::in(UserSearchCriteria::ALLOWED_SORT_BY)],
            'sort_direction' => ['nullable', Rule::in(UserSearchCriteria::ALLOWED_SORT_DIRECTION)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'page' => 'ページ番号',
            'per_page' => '1 ページあたりの件数',
            'keyword' => '検索キーワード',
            'role' => '権限',
            'status' => '状態',
            'sort_by' => '並び替え項目',
            'sort_direction' => '並び順',
        ];
    }

    public function toInput(): ListUsersInput
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return new ListUsersInput(
            page: $this->optionalInt($validated, 'page'),
            perPage: $this->optionalInt($validated, 'per_page'),
            keyword: $this->optionalString($validated, 'keyword'),
            role: $this->optionalString($validated, 'role'),
            status: $this->optionalString($validated, 'status'),
            sortBy: $this->optionalString($validated, 'sort_by'),
            sortDirection: $this->optionalString($validated, 'sort_direction'),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function optionalInt(array $validated, string $key): ?int
    {
        $value = $validated[$key] ?? null;

        return $value === null ? null : (int) $value;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function optionalString(array $validated, string $key): ?string
    {
        $value = $validated[$key] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
