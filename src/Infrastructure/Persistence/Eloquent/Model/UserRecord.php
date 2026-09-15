<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Eloquent\Model;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * users テーブルに対応する Eloquent モデル（永続化の詳細）。
 * ドメイン層からは直接参照せず、リポジトリ実装とマッパーからのみ利用する。
 *
 * created_at / updated_at は User エンティティが管理するため、
 * Eloquent の自動タイムスタンプ更新は無効にしている。
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password_hash
 * @property string $role
 * @property string $status
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
final class UserRecord extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    /**
     * パスワードハッシュは配列・JSON 化の対象から除外する。
     *
     * @var array<int, string>
     */
    protected $hidden = ['password_hash'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
