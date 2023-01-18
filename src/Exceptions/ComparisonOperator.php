<?php

namespace LaravelEnso\Filters\Exceptions;

use InvalidArgumentException;
use LaravelEnso\Filters\Enums\ComparisonOperator as ComparisonEnum;

class ComparisonOperator extends InvalidArgumentException
{
    public static function notInversable(ComparisonEnum $operator)
    {
        return new static(__('The provided operator ":operator" is not inversable', [
            'operator' => $operator->value,
        ]));
    }
}
