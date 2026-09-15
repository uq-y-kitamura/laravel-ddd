<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Eloquent\Repository;

use Domain\Shared\Criteria\PaginatedResult;
use Domain\Shared\ValueObject\EmailAddress;
use Domain\User\Criteria\UserSearchCriteria;
use Domain\User\Entity\User;
use Domain\User\Exception\UserNotFoundException;
use Domain\User\Repository\UserRepositoryInterface;
use Domain\User\ValueObject\UserId;
use Domain\User\ValueObject\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Infrastructure\Persistence\Eloquent\Mapper\UserMapper;
use Infrastructure\Persistence\Eloquent\Model\UserRecord;

/**
 * Eloquent によるユーザーリポジトリの実装（アダプタ）。
 */
final readonly class EloquentUserRepository implements UserRepositoryInterface
{
    /** LIKE 検索のエスケープ文字（DB 方言に依存しない文字を使う）。 */
    private const LIKE_ESCAPE_CHARACTER = '!';

    public function __construct(private UserMapper $mapper) {}

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }

    public function findById(UserId $id): ?User
    {
        $record = UserRecord::query()->find($id->value());

        return $record instanceof UserRecord ? $this->mapper->toEntity($record) : null;
    }

    public function findByEmail(EmailAddress $email): ?User
    {
        $record = UserRecord::query()->where('email', '=', $email->value())->first();

        return $record instanceof UserRecord ? $this->mapper->toEntity($record) : null;
    }

    public function existsByEmail(EmailAddress $email, ?UserId $excludeId = null): bool
    {
        $query = UserRecord::query()->where('email', '=', $email->value());

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId->value());
        }

        return $query->exists();
    }

    public function countByRole(UserRole $role, ?UserId $excludeId = null): int
    {
        $query = UserRecord::query()->where('role', '=', $role->value);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId->value());
        }

        return $query->count();
    }

    /**
     * @return PaginatedResult<User>
     */
    public function search(UserSearchCriteria $criteria): PaginatedResult
    {
        $query = $this->buildSearchQuery($criteria);
        $total = (clone $query)->count();

        $pagination = $criteria->pagination;

        $records = $query
            ->orderBy($criteria->sortBy, $criteria->sortDirection)
            // 同値のときに順序がぶれないよう、主キーで安定させる。
            ->orderBy('id', 'asc')
            ->offset($pagination->offset())
            ->limit($pagination->limit())
            ->get();

        /** @var list<User> $users */
        $users = array_values(array_map(
            fn (UserRecord $record): User => $this->mapper->toEntity($record),
            $records->all()
        ));

        return new PaginatedResult($users, $total, $pagination);
    }

    public function save(User $user): void
    {
        UserRecord::query()->updateOrCreate(
            ['id' => $user->id()->value()],
            $this->mapper->toAttributes($user),
        );
    }

    public function delete(UserId $id): void
    {
        $deleted = UserRecord::query()->whereKey($id->value())->delete();

        if ($deleted === 0) {
            throw UserNotFoundException::fromId($id);
        }
    }

    /**
     * @return Builder<UserRecord>
     */
    private function buildSearchQuery(UserSearchCriteria $criteria): Builder
    {
        $query = UserRecord::query();

        $keyword = $criteria->keyword;
        if ($keyword !== null) {
            $pattern = '%'.$this->escapeLike($keyword).'%';
            $escape = self::LIKE_ESCAPE_CHARACTER;

            $query->where(static function (Builder $inner) use ($pattern, $escape): void {
                $inner
                    ->whereRaw(sprintf("name LIKE ? ESCAPE '%s'", $escape), [$pattern])
                    ->orWhereRaw(sprintf("email LIKE ? ESCAPE '%s'", $escape), [$pattern]);
            });
        }

        $role = $criteria->role;
        if ($role !== null) {
            $query->where('role', '=', $role->value);
        }

        $status = $criteria->status;
        if ($status !== null) {
            $query->where('status', '=', $status->value);
        }

        return $query;
    }

    /**
     * LIKE のワイルドカード（% _）とエスケープ文字自身を無害化する。
     */
    private function escapeLike(string $value): string
    {
        $escape = self::LIKE_ESCAPE_CHARACTER;

        return str_replace(
            [$escape, '%', '_'],
            [$escape.$escape, $escape.'%', $escape.'_'],
            $value
        );
    }
}
