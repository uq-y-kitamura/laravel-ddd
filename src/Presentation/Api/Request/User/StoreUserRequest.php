<?php

declare(strict_types=1);

namespace Presentation\Api\Request\User;

use Application\User\UseCase\CreateUser\CreateUserInput;
use Domain\User\ValueObject\PlainPassword;
use Domain\User\ValueObject\UserName;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ユーザー新規登録リクエスト。
 */
final class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:'.UserName::MAX_LENGTH],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:'.PlainPassword::MIN_LENGTH, 'max:'.PlainPassword::MAX_BYTES],
            'role' => ['required', Rule::in(UserRole::values())],
            'status' => ['nullable', Rule::in(UserStatus::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ユーザー名',
            'email' => 'メールアドレス',
            'password' => 'パスワード',
            'role' => '権限',
            'status' => '状態',
        ];
    }

    public function toInput(): CreateUserInput
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        $status = $validated['status'] ?? null;

        return new CreateUserInput(
            name: (string) $validated['name'],
            email: (string) $validated['email'],
            password: (string) $validated['password'],
            role: (string) $validated['role'],
            status: $status === null ? null : (string) $status,
        );
    }
}
