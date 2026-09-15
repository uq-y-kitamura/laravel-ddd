<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| バリデーションメッセージ（日本語）
|--------------------------------------------------------------------------
|
| 本 API で実際に使用しているルールを中心に定義する。未定義のキーは
| config/app.php の fallback_locale（en）にフォールバックする。
| 属性名は各 FormRequest の attributes() で日本語化している。
|
*/

return [
    'accepted' => ':attributeを承認してください。',
    'after' => ':attributeには:dateより後の日付を指定してください。',
    'after_or_equal' => ':attributeには:date以降の日付を指定してください。',
    'alpha' => ':attributeは英字のみで入力してください。',
    'alpha_dash' => ':attributeは英数字とハイフン、アンダースコアのみで入力してください。',
    'alpha_num' => ':attributeは英数字のみで入力してください。',
    'array' => ':attributeは配列で指定してください。',
    'before' => ':attributeには:dateより前の日付を指定してください。',
    'before_or_equal' => ':attributeには:date以前の日付を指定してください。',
    'boolean' => ':attributeには true か false を指定してください。',
    'confirmed' => ':attributeの確認用の値が一致しません。',
    'date' => ':attributeは正しい日付形式で入力してください。',
    'date_equals' => ':attributeには:dateと同じ日付を指定してください。',
    'date_format' => ':attributeは:format形式で入力してください。',
    'different' => ':attributeと:otherには異なる値を指定してください。',
    'digits' => ':attributeは:digits桁で入力してください。',
    'digits_between' => ':attributeは:min〜:max桁で入力してください。',
    'email' => ':attributeは正しいメールアドレス形式で入力してください。',
    'exists' => '選択された:attributeは存在しません。',
    'filled' => ':attributeは必須です。',
    'gt' => [
        'numeric' => ':attributeは:valueより大きい値を指定してください。',
        'string' => ':attributeは:value文字より長く入力してください。',
    ],
    'gte' => [
        'numeric' => ':attributeは:value以上の値を指定してください。',
        'string' => ':attributeは:value文字以上で入力してください。',
    ],
    'in' => '選択された:attributeは許可されていません。',
    'integer' => ':attributeは整数で指定してください。',
    'lt' => [
        'numeric' => ':attributeは:valueより小さい値を指定してください。',
        'string' => ':attributeは:value文字より短く入力してください。',
    ],
    'lte' => [
        'numeric' => ':attributeは:value以下の値を指定してください。',
        'string' => ':attributeは:value文字以内で入力してください。',
    ],
    'max' => [
        'array' => ':attributeは:max個以内で指定してください。',
        'file' => ':attributeは:maxKB 以内のファイルを指定してください。',
        'numeric' => ':attributeは:max以下の値を指定してください。',
        'string' => ':attributeは:max文字以内で入力してください。',
    ],
    'min' => [
        'array' => ':attributeは:min個以上で指定してください。',
        'file' => ':attributeは:minKB 以上のファイルを指定してください。',
        'numeric' => ':attributeは:min以上の値を指定してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'not_in' => '選択された:attributeは許可されていません。',
    'numeric' => ':attributeは数値で指定してください。',
    'present' => ':attributeが指定されていません。',
    'prohibited' => ':attributeは指定できません。',
    'regex' => ':attributeの形式が正しくありません。',
    'required' => ':attributeは必須です。',
    'required_if' => ':otherが:valueの場合、:attributeは必須です。',
    'required_with' => ':valuesが指定されている場合、:attributeは必須です。',
    'required_without' => ':valuesが指定されていない場合、:attributeは必須です。',
    'same' => ':attributeと:otherには同じ値を指定してください。',
    'size' => [
        'array' => ':attributeは:size個で指定してください。',
        'file' => ':attributeは:sizeKB のファイルを指定してください。',
        'numeric' => ':attributeは:sizeを指定してください。',
        'string' => ':attributeは:size文字で入力してください。',
    ],
    'string' => ':attributeは文字列で入力してください。',
    'unique' => ':attributeは既に使用されています。',
    'uuid' => ':attributeは正しい UUID 形式で入力してください。',

    'custom' => [],
    'attributes' => [],
];
