<?php

declare(strict_types=1);

namespace Presentation\Api\Request\User;

use Application\User\UseCase\UpdateUser\UpdateUserInput;
use Domain\User\ValueObject\PlainPassword;
use Domain\User\ValueObject\UserName;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ユーザー更新リクエスト（部分更新）。
 * 送信されなかった項目は「変更なし」として扱う。
 */
final class UpdateUserRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:'.UserName::MAX_LENGTH],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'password' => [
                'sometimes',
                'required',
                'string',
                'min:'.PlainPassword::MIN_LENGTH,
                'max:'.PlainPassword::MAX_BYTES,
            ],
            'role' => ['sometimes', 'required', Rule::in(UserRole::values())],
            'status' => ['sometimes', 'required', Rule::in(UserStatus::values())],
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

    public function toInput(string $id): UpdateUserInput
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return new UpdateUserInput(
            id: $id,
            name: $this->optionalString($validated, 'name'),
            email: $this->optionalString($validated, 'email'),
            password: $this->optionalPassword($validated),
            role: $this->optionalString($validated, 'role'),
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

    /**
     * パスワードは trim せずそのまま受け渡す。
     *
     * @param  array<string, mixed>  $validated
     */
    private function optionalPassword(array $validated): ?string
    {
        if (! array_key_exists('password', $validated) || $validated['password'] === null) {
            return null;
        }

        return (string) $validated['password'];
    }
}
