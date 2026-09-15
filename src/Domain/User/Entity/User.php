<?php

declare(strict_types=1);

namespace Domain\User\Entity;

use DateTimeImmutable;
use Domain\Shared\Exception\InvalidValueException;
use Domain\Shared\ValueObject\EmailAddress;
use Domain\User\Service\PasswordHasherInterface;
use Domain\User\ValueObject\HashedPassword;
use Domain\User\ValueObject\PlainPassword;
use Domain\User\ValueObject\UserId;
use Domain\User\ValueObject\UserName;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;

/**
 * ユーザー集約のルートエンティティ。
 */
final class User
{
    private function __construct(
        private readonly UserId $id,
        private UserName $name,
        private EmailAddress $email,
        private HashedPassword $password,
        private UserRole $role,
        private UserStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    /**
     * 新規ユーザーを生成する。
     */
    public static function create(
        UserId $id,
        UserName $name,
        EmailAddress $email,
        HashedPassword $password,
        UserRole $role,
        UserStatus $status,
        DateTimeImmutable $now,
    ): self {
        return new self($id, $name, $email, $password, $role, $status, $now, $now);
    }

    /**
     * 永続化済みのデータからユーザーを復元する。
     */
    public static function reconstruct(
        UserId $id,
        UserName $name,
        EmailAddress $email,
        HashedPassword $password,
        UserRole $role,
        UserStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $email, $password, $role, $status, $createdAt, $updatedAt);
    }

    /**
     * 表示名を変更する。
     */
    public function rename(UserName $name, DateTimeImmutable $now): void
    {
        $this->assertModifiable();

        if ($this->name->equals($name)) {
            return;
        }

        $this->name = $name;
        $this->touch($now);
    }

    /**
     * メールアドレスを変更する。
     */
    public function changeEmail(EmailAddress $email, DateTimeImmutable $now): void
    {
        $this->assertModifiable();

        if ($this->email->equals($email)) {
            return;
        }

        $this->email = $email;
        $this->touch($now);
    }

    /**
     * ハッシュ済みパスワードを変更する。
     */
    public function changePassword(HashedPassword $password, DateTimeImmutable $now): void
    {
        $this->assertModifiable();

        if ($this->password->equals($password)) {
            return;
        }

        $this->password = $password;
        $this->touch($now);
    }

    /**
     * 権限を変更する。
     */
    public function changeRole(UserRole $role, DateTimeImmutable $now): void
    {
        $this->assertModifiable();

        if ($this->role === $role) {
            return;
        }

        $this->role = $role;
        $this->touch($now);
    }

    /**
     * ユーザーを停止する。既に停止中の場合は何もしない（冪等）。
     */
    public function suspend(DateTimeImmutable $now): void
    {
        if ($this->status === UserStatus::Suspended) {
            return;
        }

        $this->status = UserStatus::Suspended;
        $this->touch($now);
    }

    /**
     * ユーザーを有効化する。既に有効な場合は何もしない（冪等）。
     */
    public function activate(DateTimeImmutable $now): void
    {
        if ($this->status === UserStatus::Active) {
            return;
        }

        $this->status = UserStatus::Active;
        $this->touch($now);
    }

    /**
     * 平文パスワードが登録済みのハッシュと一致するかを検証する。
     */
    public function verifyPassword(PlainPassword $plain, PasswordHasherInterface $hasher): bool
    {
        return $hasher->verify($plain, $this->password);
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function email(): EmailAddress
    {
        return $this->email;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }

    public function role(): UserRole
    {
        return $this->role;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * 停止中のユーザーは activate() 以外の変更を受け付けない。
     */
    private function assertModifiable(): void
    {
        if ($this->status === UserStatus::Suspended) {
            throw new InvalidValueException('停止中のユーザーは変更できません。');
        }
    }

    private function touch(DateTimeImmutable $now): void
    {
        $this->updatedAt = $now;
    }
}
