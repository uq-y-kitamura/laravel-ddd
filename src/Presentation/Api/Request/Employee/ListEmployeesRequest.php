<?php

declare(strict_types=1);

namespace Presentation\Api\Request\Employee;

use Application\Employee\UseCase\ListEmployeesInput;
use Domain\Employee\Criteria\EmployeeSearchCriteria;
use Domain\Employee\ValueObject\EmploymentStatus;
use Domain\Shared\Criteria\Pagination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 社員一覧取得リクエスト。
 */
final class ListEmployeesRequest extends FormRequest
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
            'department' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(EmploymentStatus::values())],
            'sort_by' => ['nullable', Rule::in([
                EmployeeSearchCriteria::SORT_BY_EMPLOYEE_NUMBER,
                EmployeeSearchCriteria::SORT_BY_HIRE_DATE,
                EmployeeSearchCriteria::SORT_BY_CREATED_AT,
            ])],
            'sort_direction' => ['nullable', Rule::in([
                EmployeeSearchCriteria::SORT_DIRECTION_ASC,
                EmployeeSearchCriteria::SORT_DIRECTION_DESC,
            ])],
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
            'department' => '所属部署',
            'status' => '在籍状況',
            'sort_by' => '並び替え項目',
            'sort_direction' => '並び順',
        ];
    }

    public function toInput(): ListEmployeesInput
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return new ListEmployeesInput(
            page: $this->optionalInt($validated, 'page'),
            perPage: $this->optionalInt($validated, 'per_page'),
            keyword: $this->optionalString($validated, 'keyword'),
            department: $this->optionalString($validated, 'department'),
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
