<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Employee\ValueObject;

use Domain\Employee\ValueObject\EmployeeNumber;
use Domain\Shared\Exception\InvalidValueException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmployeeNumberTest extends TestCase
{
    #[DataProvider('正常な社員番号')]
    public function test_正規化された社員番号を保持する(string $input, string $expected): void
    {
        $number = EmployeeNumber::fromString($input);

        $this->assertSame($expected, $number->value());
        $this->assertSame($expected, (string) $number);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function 正常な社員番号(): array
    {
        return [
            '大文字英数字とハイフン' => ['EMP-0001', 'EMP-0001'],
            '小文字は大文字へ正規化される' => ['emp-0001', 'EMP-0001'],
            '前後の空白は除去される' => ['  EMP-0001  ', 'EMP-0001'],
            '最小長 3 文字' => ['A01', 'A01'],
            '最大長 20 文字' => [str_repeat('A', 20), str_repeat('A', 20)],
        ];
    }

    #[DataProvider('不正な社員番号')]
    public function test_不正な社員番号は例外になる(string $input): void
    {
        $this->expectException(InvalidValueException::class);

        EmployeeNumber::fromString($input);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function 不正な社員番号(): array
    {
        return [
            '空文字' => [''],
            '空白のみ' => ['   '],
            '3 文字未満' => ['A1'],
            '20 文字超' => [str_repeat('A', 21)],
            '記号を含む' => ['EMP@001'],
            '空白を含む' => ['EMP 001'],
            '全角文字を含む' => ['ＥＭＰ０００１'],
        ];
    }

    public function test_同じ値の社員番号は等価になる(): void
    {
        $this->assertTrue(EmployeeNumber::fromString('emp-1')->equals(EmployeeNumber::fromString('EMP-1')));
        $this->assertFalse(EmployeeNumber::fromString('EMP-1')->equals(EmployeeNumber::fromString('EMP-2')));
    }
}
