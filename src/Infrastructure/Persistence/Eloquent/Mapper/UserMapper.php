<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Eloquent\Mapper;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Domain\Shared\Exception\InvalidValueException;
use Domain\Shared\ValueObject\EmailAddress;
use Domain\User\Entity\User;
use Domain\User\ValueObject\HashedPassword;
use Domain\User\ValueObject\UserId;
use Domain\User\ValueObject\UserName;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;
use Infrastructure\Persistence\Eloquent\Model\UserRecord;

/**
 * Eloquent モデルとユーザー集約の相互変換を行うマッパー。
 */
final class UserMapper
{
    /**
     * レコードからユーザー集約を復元する。
     */
    public function toEntity(UserRecord $record): User
    {
        return User::reconstruct(
            UserId::fromString((string) $record->getAttribute('id')),
            new UserName((string) $record->getAttribute('name')),
            EmailAddress::fromString((string) $record->getAttribute('email')),
            new HashedPassword((string) $record->getAttribute('password_hash')),
            UserRole::fromString((string) $record->getAttribute('role')),
            UserStatus::fromString((string) $record->getAttribute('status')),
            $this->toDateTimeImmutable($record->getAttribute('created_at'), 'created_at'),
            $this->toDateTimeImmutable($record->getAttribute('updated_at'), 'updated_at'),
        );
    }

    /**
     * ユーザー集約をテーブルの属性値へ変換する。
     *
     * @return array<string, string>
     */
    public function toAttributes(User $user): array
    {
        return [
            'id' => $user->id()->value(),
            'name' => $user->name()->value(),
            'email' => $user->email()->value(),
            'password_hash' => $user->password()->value(),
            'role' => $user->role()->value,
            'status' => $user->status()->value,
            'created_at' => $this->toStorageFormat($user->createdAt()),
            'updated_at' => $this->toStorageFormat($user->updatedAt()),
        ];
    }

    /**
     * アプリケーションのタイムゾーンに合わせた文字列へ変換する。
     * Eloquent は保存済みの日時文字列を同じタイムゾーンとして読み戻すため、往復で一致する。
     */
    private function toStorageFormat(DateTimeImmutable $value): string
    {
        return $value->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
    }

    /**
     * ドメイン層が Carbon に依存しないよう、素の DateTimeImmutable へ変換する。
     */
    private function toDateTimeImmutable(mixed $value, string $column): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (is_string($value) && $value !== '') {
            return new DateTimeImmutable($value);
        }

        throw new InvalidValueException(sprintf('日時の復元に失敗しました。: %s', $column));
    }
}
