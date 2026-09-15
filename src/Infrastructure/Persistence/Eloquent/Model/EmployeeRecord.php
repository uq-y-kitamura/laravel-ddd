<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Eloquent\Model;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * employees テーブルに対応する Eloquent モデル（永続化の詳細）。
 * ドメイン層からは直接参照せず、リポジトリ実装とマッパーからのみ利用する。
 *
 * @property string $id
 * @property string $employee_number
 * @property string $last_name
 * @property string $first_name
 * @property string $email
 * @property string $department
 * @property CarbonImmutable $hire_date
 * @property string $status
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class EmployeeRecord extends Model
{
    protected $table = 'employees';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'hire_date' => 'immutable_date',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
