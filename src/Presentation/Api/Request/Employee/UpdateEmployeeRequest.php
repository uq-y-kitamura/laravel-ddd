<?php

declare(strict_types=1);

namespace Presentation\Api\Request\Employee;

use Application\Employee\UseCase\UpdateEmployeeInput;
use Domain\Employee\ValueObject\EmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 社員更新リクエスト（部分更新）。送信されなかった項目は変更しない。
 */
final class UpdateEmployeeRequest extends FormRequest
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
            'employee_number' => ['sometimes', 'required', 'string', 'max:20'],
            'last_name' => ['sometimes', 'required', 'string', 'max:50'],
            'first_name' => ['sometimes', 'required', 'string', 'max:50'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'department' => ['sometimes', 'required', 'string', 'max:100'],
            'hire_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'required', Rule::in(EmploymentStatus::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'employee_number' => '社員番号',
            'last_name' => '姓',
            'first_name' => '名',
            'email' => 'メールアドレス',
            'department' => '所属部署',
            'hire_date' => '入社日',
            'status' => '在籍状況',
        ];
    }

    public function toInput(string $employeeId): UpdateEmployeeInput
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return new UpdateEmployeeInput(
            id: $employeeId,
            employeeNumber: $this->optionalString($validated, 'employee_number'),
            lastName: $this->optionalString($validated, 'last_name'),
            firstName: $this->optionalString($validated, 'first_name'),
            email: $this->optionalString($validated, 'email'),
            department: $this->optionalString($validated, 'department'),
            hireDate: $this->optionalString($validated, 'hire_date'),
            status: $this->optionalString($validated, 'status'),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function optionalString(array $validated, string $key): ?string
    {
        if (! array_key_exists($key, $validated) || $validated[$key] === null) {
            return null;
        }

        return (string) $validated[$key];
    }
}
