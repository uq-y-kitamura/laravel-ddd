<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Employee\ValueObject;

use Domain\Employee\ValueObject\PersonName;
use Domain\Shared\Exception\InvalidValueException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PersonNameTest extends TestCase
{
    public function test_姓名を保持しフルネームを組み立てる(): void
    {
        $name = new PersonName('山田', '太郎');

        $this->assertSame('山田', $name->lastName());
        $this->assertSame('太郎', $name->firstName());
        $this->assertSame('山田 太郎', $name->fullName());
        $this->assertSame('山田 太郎', (string) $name);
    }

    public function test_前後の空白は除去される(): void
    {
        $name = new PersonName('  山田  ', "\t太郎\n");

        $this->assertSame('山田', $name->lastName());
        $this->assertSame('太郎', $name->firstName());
    }

    public function test_50文字までは許可される(): void
    {
        $long = str_repeat('あ', 50);
        $name = new PersonName($long, $long);

        $this->assertSame($long, $name->lastName());
        $this->assertSame($long, $name->firstName());
    }

    #[DataProvider('不正な氏名')]
    public function test_不正な氏名は例外になる(string $lastName, string $firstName): void
    {
        $this->expectException(InvalidValueException::class);

        new PersonName($lastName, $firstName);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function 不正な氏名(): array
    {
        return [
            '姓が空文字' => ['', '太郎'],
            '名が空文字' => ['山田', ''],
            '姓が空白のみ' => ['   ', '太郎'],
            '名が空白のみ' => ['山田', '   '],
            '姓が 51 文字' => [str_repeat('あ', 51), '太郎'],
            '名が 51 文字' => ['山田', str_repeat('あ', 51)],
        ];
    }

    public function test_同じ姓名は等価になる(): void
    {
        $this->assertTrue((new PersonName('山田', '太郎'))->equals(new PersonName('山田', '太郎')));
        $this->assertFalse((new PersonName('山田', '太郎'))->equals(new PersonName('山田', '次郎')));
    }
}
