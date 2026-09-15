<?php

declare(strict_types=1);

namespace Tests\Support;

use Domain\Shared\Criteria\PaginatedResult;
use Domain\Shared\ValueObject\EmailAddress;
use Domain\User\Criteria\UserSearchCriteria;
use Domain\User\Entity\User;
use Domain\User\Exception\UserNotFoundException;
use Domain\User\Repository\UserRepositoryInterface;
use Domain\User\ValueObject\UserId;
use Domain\User\ValueObject\UserRole;

/**
 * ユースケースのユニットテスト用インメモリリポジトリ。
 * 永続化境界を模倣するため、保存・取得時にエンティティを複製する。
 */
final class InMemoryUserRepository implements UserRepositoryInterface
{
    /**
     * @var array<string, User>
     */
    private array $users = [];

    public function __construct(User ...$users)
    {
        foreach ($users as $user) {
            $this->save($user);
        }
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }

    public function findById(UserId $id): ?User
    {
        $user = $this->users[$id->value()] ?? null;

        return $user === null ? null : clone $user;
    }

    public function findByEmail(EmailAddress $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email()->equals($email)) {
                return clone $user;
            }
        }

        return null;
    }

    public function existsByEmail(EmailAddress $email, ?UserId $excludeId = null): bool
    {
        foreach ($this->users as $user) {
            if (! $user->email()->equals($email)) {
                continue;
            }

            if ($excludeId !== null && $user->id()->equals($excludeId)) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function countByRole(UserRole $role, ?UserId $excludeId = null): int
    {
        $count = 0;

        foreach ($this->users as $user) {
            if ($user->role() !== $role) {
                continue;
            }

            if ($excludeId !== null && $user->id()->equals($excludeId)) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    /**
     * @return PaginatedResult<User>
     */
    public function search(UserSearchCriteria $criteria): PaginatedResult
    {
        $matched = array_values(array_filter(
            $this->users,
            fn (User $user): bool => $this->matches($user, $criteria)
        ));

        usort($matched, fn (User $a, User $b): int => $this->compare($a, $b, $criteria));

        $total = count($matched);
        $page = array_slice($matched, $criteria->pagination->offset(), $criteria->pagination->limit());

        return new PaginatedResult(
            array_map(static fn (User $user): User => clone $user, $page),
            $total,
            $criteria->pagination,
        );
    }

    public function save(User $user): void
    {
        $this->users[$user->id()->value()] = clone $user;
    }

    public function delete(UserId $id): void
    {
        if (! array_key_exists($id->value(), $this->users)) {
            throw UserNotFoundException::fromId($id);
        }

        unset($this->users[$id->value()]);
    }

    public function count(): int
    {
        return count($this->users);
    }

    private function matches(User $user, UserSearchCriteria $criteria): bool
    {
        if ($criteria->role !== null && $user->role() !== $criteria->role) {
            return false;
        }

        if ($criteria->status !== null && $user->status() !== $criteria->status) {
            return false;
        }

        if ($criteria->keyword === null) {
            return true;
        }

        $keyword = mb_strtolower($criteria->keyword);
        $haystacks = [
            $user->name()->value(),
            $user->email()->value(),
        ];

        foreach ($haystacks as $haystack) {
            if (str_contains(mb_strtolower($haystack), $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function compare(User $a, User $b, UserSearchCriteria $criteria): int
    {
        $result = strcmp($this->sortKey($a, $criteria->sortBy), $this->sortKey($b, $criteria->sortBy));

        if ($result === 0) {
            return strcmp($a->id()->value(), $b->id()->value());
        }

        return $criteria->sortDirection === UserSearchCriteria::SORT_DIRECTION_DESC ? -$result : $result;
    }

    private function sortKey(User $user, string $sortBy): string
    {
        return match ($sortBy) {
            UserSearchCriteria::SORT_BY_NAME => $user->name()->value(),
            UserSearchCriteria::SORT_BY_EMAIL => $user->email()->value(),
            default => $user->createdAt()->format('Y-m-d H:i:s.u'),
        };
    }
}
