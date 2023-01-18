<?php

namespace LaravelEnso\Filters\Enums;

use LaravelEnso\Filters\Exceptions\ComparisonOperator as Exception;

enum ComparisonOperator: string
{
    case Like = 'LIKE';
    case ILike = 'ILIKE';
    case Equal = '=';
    case Is = 'IS';
    case IsNot = 'IS NOT';
    case NotLike = 'NOT LIKE';
    case NotILike = 'NOT ILIKE';

    public function invert(): self
    {
        return match ($this) {
            self::Like => self::NotLike,
            self::ILike => self::NotILike,
            self::Is => self::IsNot,
            default => throw Exception::notInversable($this)
        };
    }
}
