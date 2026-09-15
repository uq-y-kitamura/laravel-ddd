<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User\ValueObject;

use Domain\Shared\Exception\InvalidValueException;
use Domain\User\ValueObject\PlainPassword;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PlainPasswordTest extends TestCase
{
    #[DataProvider('有効なパスワード')]
    public function test_有効なパスワードを保持する(string $value): void
    {
        $this->assertSame($value, (new PlainPassword($value))->value());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function 有効なパスワード(): array
    {
        return [
            '最短の 8 文字' => ['abcdefg1'],
            '記号を含む' => ['Passw0rd!#$'],
            '空白を含む（trim されない）' => [' pass word 1 '],
            '最長の 72 バイト' => [str_repeat('a', 71).'1'],
        ];
    }

    public function test_from_stringでも生成できる(): void
    {
        $this->assertSame('password1', PlainPassword::fromString('password1')->value());
    }

    public function test_var_dumpで平文が露出しない(): void
    {
        $password = new PlainPassword('password1');

        $this->assertSame(['value' => '********'], $password->__debugInfo());
    }

    public function test_文字列化メソッドを持たない(): void
    {
        // 平文パスワードが誤ってログ等へ出力されないよう __toString() は実装しない。
        $this->assertFalse(method_exists(PlainPassword::class, '__toString'));
    }

    #[DataProvider('不正なパスワード')]
    public function test_不正なパスワードは例外になる(string $value): void
    {
        $this->expectException(InvalidValueException::class);

        new PlainPassword($value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function 不正なパスワード(): array
    {
        return [
            '空文字' => [''],
            '7 文字で短すぎる' => ['abcde12'],
            '73 バイトで長すぎる' => [str_repeat('a', 72).'1'],
            '数字を含まない' => ['abcdefgh'],
            '英字を含まない' => ['12345678'],
            '記号のみ' => ['!!!!!!!!'],
        ];
    }
}
