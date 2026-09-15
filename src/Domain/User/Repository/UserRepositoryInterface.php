<?php

declare(strict_types=1);

namespace Domain\User\Repository;

use Domain\Shared\Criteria\PaginatedResult;
use Domain\Shared\ValueObject\EmailAddress;
use Domain\User\Criteria\UserSearchCriteria;
use Domain\User\Entity\User;
use Domain\User\Exception\UserNotFoundException;
use Domain\User\ValueObject\UserId;
use Domain\User\ValueObject\UserRole;

/**
 * ユーザー集約の永続化を抽象化するポート。
 */
interface UserRepositoryInterface
{
    /**
     * 新しいユーザー ID を採番する。
     */
    public function nextIdentity(): UserId;

    public function findById(UserId $id): ?User;

    public function findByEmail(EmailAddress $email): ?User;

    /**
     * メールアドレスの重複を判定する。
     *
     * @param  ?UserId  $excludeId  判定から除外するユーザー ID（自分自身の更新時に指定する）
     */
    public function existsByEmail(EmailAddress $email, ?UserId $excludeId = null): bool;

    /**
     * 指定権限のユーザー数を数える（最後の管理者チェックで使用する）。
     *
     * @param  ?UserId  $excludeId  集計から除外するユーザー ID
     */
    public function countByRole(UserRole $role, ?UserId $excludeId = null): int;

    /**
     * @return PaginatedResult<User>
     */
    public function search(UserSearchCriteria $criteria): PaginatedResult;

    /**
     * 新規・更新のいずれも受け付ける（upsert）。
     */
    public function save(User $user): void;

    /**
     * @throws UserNotFoundException 対象が存在しない場合
     */
    public function delete(UserId $id): void;
}
