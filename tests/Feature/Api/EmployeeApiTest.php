<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Domain\Employee\ValueObject\EmployeeId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Persistence\Eloquent\Model\EmployeeRecord;
use Tests\TestCase;

final class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_社員を登録できる(): void
    {
        $response = $this->postJson('/api/employees', [
            'employee_number' => 'emp-0001',
            'last_name' => '山田',
            'first_name' => '太郎',
            'email' => 'TARO@Example.com',
            'department' => '開発部',
            'hire_date' => '2024-04-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'employee_number',
                    'name' => ['last_name', 'first_name', 'full_name'],
                    'email',
                    'department',
                    'hire_date',
                    'status',
                    'status_label',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.employee_number', 'EMP-0001')
            ->assertJsonPath('data.name.full_name', '山田 太郎')
            ->assertJsonPath('data.email', 'taro@example.com')
            ->assertJsonPath('data.hire_date', '2024-04-01')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.status_label', '在籍中');

        $this->assertDatabaseHas('employees', [
            'employee_number' => 'EMP-0001',
            'email' => 'taro@example.com',
            'status' => 'active',
        ]);
    }

    public function test_社員一覧を取得できる(): void
    {
        $this->createEmployeeRecord('EMP-0001', 'taro@example.com', lastName: '山田');
        $this->createEmployeeRecord('EMP-0002', 'hanako@example.com', lastName: '鈴木', department: '営業部');

        $response = $this->getJson('/api/employees');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total_pages', 1);
    }

    public function test_キーワードと部署で絞り込める(): void
    {
        $this->createEmployeeRecord('EMP-0001', 'taro@example.com', lastName: '山田');
        $this->createEmployeeRecord('EMP-0002', 'hanako@example.com', lastName: '鈴木', department: '営業部');

        $this->getJson('/api/employees?keyword=鈴木')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.employee_number', 'EMP-0002');

        $this->getJson('/api/employees?department=開発部')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee_number', 'EMP-0001');

        $this->getJson('/api/employees?keyword=EMP-0001')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee_number', 'EMP-0001');
    }

    public function test_社員を1件取得できる(): void
    {
        $id = $this->createEmployeeRecord('EMP-0001', 'taro@example.com');

        $this->getJson("/api/employees/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.employee_number', 'EMP-0001');
    }

    public function test_社員を更新できる(): void
    {
        $id = $this->createEmployeeRecord('EMP-0001', 'taro@example.com');

        $this->patchJson("/api/employees/{$id}", [
            'department' => '営業部',
            'status' => 'on_leave',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.department', '営業部')
            ->assertJsonPath('data.status', 'on_leave')
            ->assertJsonPath('data.status_label', '休職中')
            ->assertJsonPath('data.employee_number', 'EMP-0001');

        $this->assertDatabaseHas('employees', [
            'id' => $id,
            'department' => '営業部',
            'status' => 'on_leave',
        ]);
    }

    public function test_社員を削除できる(): void
    {
        $id = $this->createEmployeeRecord('EMP-0001', 'taro@example.com');

        $this->deleteJson("/api/employees/{$id}")
            ->assertStatus(204)
            ->assertNoContent();

        $this->assertDatabaseMissing('employees', ['id' => $id]);
    }

    public function test_存在しない社員は404になる(): void
    {
        $missingId = EmployeeId::generate()->value();

        $this->getJson("/api/employees/{$missingId}")->assertStatus(404);
        $this->patchJson("/api/employees/{$missingId}", ['department' => '営業部'])->assertStatus(404);
        $this->deleteJson("/api/employees/{$missingId}")->assertStatus(404);
    }

    public function test_社員番号が重複すると409になる(): void
    {
        $this->createEmployeeRecord('EMP-0001', 'taro@example.com');

        $this->postJson('/api/employees', [
            'employee_number' => 'EMP-0001',
            'last_name' => '鈴木',
            'first_name' => '花子',
            'email' => 'hanako@example.com',
            'department' => '営業部',
            'hire_date' => '2024-05-01',
        ])->assertStatus(409);
    }

    public function test_メールアドレスが重複すると409になる(): void
    {
        $this->createEmployeeRecord('EMP-0001', 'taro@example.com');

        $this->postJson('/api/employees', [
            'employee_number' => 'EMP-0002',
            'last_name' => '鈴木',
            'first_name' => '花子',
            'email' => 'TARO@example.com',
            'department' => '営業部',
            'hire_date' => '2024-05-01',
        ])->assertStatus(409);
    }

    public function test_必須項目が欠けていると422になる(): void
    {
        $this->postJson('/api/employees', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'employee_number',
                'last_name',
                'first_name',
                'email',
                'department',
                'hire_date',
            ]);
    }

    public function test_不正な形式の入力は422になる(): void
    {
        $this->postJson('/api/employees', [
            'employee_number' => 'EMP-0001',
            'last_name' => '山田',
            'first_name' => '太郎',
            'email' => 'not-an-email',
            'department' => '開発部',
            'hire_date' => '2024/04/01',
            'status' => 'unknown',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'hire_date', 'status']);
    }

    /**
     * テスト用の社員レコードを直接作成し、その ID を返す。
     */
    private function createEmployeeRecord(
        string $employeeNumber,
        string $email,
        string $lastName = '山田',
        string $firstName = '太郎',
        string $department = '開発部',
        string $hireDate = '2024-04-01',
        string $status = 'active',
    ): string {
        $id = EmployeeId::generate()->value();

        EmployeeRecord::create([
            'id' => $id,
            'employee_number' => $employeeNumber,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'email' => $email,
            'department' => $department,
            'hire_date' => $hireDate,
            'status' => $status,
        ]);

        return $id;
    }
}
