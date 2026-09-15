<?php

declare(strict_types=1);

namespace Presentation\Api\Request\Employee;

use Application\Employee\UseCase\CreateEmployeeInput;
use Domain\Employee\ValueObject\EmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 社員登録リクエスト。
 */
final class StoreEmployeeRequest extends FormRequest
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
            'employee_number' => ['required', 'string', 'max:20'],
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'department' => ['required', 'string', 'max:100'],
            'hire_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(EmploymentStatus::values())],
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

    public function toInput(): CreateEmployeeInput
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        $status = $validated['status'] ?? null;

        return new CreateEmployeeInput(
            employeeNumber: (string) $validated['employee_number'],
            lastName: (string) $validated['last_name'],
            firstName: (string) $validated['first_name'],
            email: (string) $validated['email'],
            department: (string) $validated['department'],
            hireDate: (string) $validated['hire_date'],
            status: $status === null ? null : (string) $status,
        );
    }
}
