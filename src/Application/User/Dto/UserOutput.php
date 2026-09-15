<?php

declare(strict_types=1);

namespace Application\User\Dto;

use DateTimeImmutable;
use DateTimeInterface;
use Domain\User\Entity\User;
use Domain\User\ValueObject\UserRole;
use Domain\User\ValueObject\UserStatus;

/**
 * ユーザー 1 件の出力 DTO。
 *
 * パスワード（平文・ハッシュのいずれも）は絶対に含めない。
 */
final readonly class UserOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public UserRole $role,
        public UserStatus $status,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->id()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            role: $user->role(),
            status: $user->status(),
            createdAt: $user->createdAt(),
            updatedAt: $user->updatedAt(),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     role: string,
     *     role_label: string,
     *     status: string,
     *     status_label: string,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
