<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User\ValueObject;

use Domain\Shared\Exception\InvalidValueException;
use Domain\User\ValueObject\UserName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserNameTest extends TestCase
{
    public function test_表示名を保持する(): void
    {
        $name = new UserName('山田 太郎');

        $this->assertSame('山田 太郎', $name->value());
        $this->assertSame('山田 太郎', (string) $name);
    }

    public function test_前後の空白は除去される(): void
    {
        $name = new UserName("\t  山田 太郎  \n");

        $this->assertSame('山田 太郎', $name->value());
    }

    public function test_100文字までは許可される(): void
    {
        $long = str_repeat('あ', 100);

        $this->assertSame($long, (new UserName($long))->value());
    }

    public function test_同じ表示名は等価になる(): void
    {
        $this->assertTrue((new UserName('山田 太郎'))->equals(new UserName('  山田 太郎 ')));
        $this->assertFalse((new UserName('山田 太郎'))->equals(new UserName('山田 次郎')));
    }

    #[DataProvider('不正な表示名')]
    public function test_不正な表示名は例外になる(string $value): void
    {
        $this->expectException(InvalidValueException::class);

        new UserName($value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function 不正な表示名(): array
    {
        return [
            '空文字' => [''],
            '空白のみ' => ['   '],
            'タブと改行のみ' => ["\t\n"],
            '101 文字' => [str_repeat('あ', 101)],
        ];
    }
}
