<?php

namespace LaravelEnso\Filters\Enums;

use LaravelEnso\Enums\Services\Enum;

class Adjustments extends Enum
{
    protected static array $data = [
        Interval::Today->value => Operation::None->value,
        Interval::ThisWeek->value => Operation::None->value,
        Interval::ThisMonth->value => Operation::None->value,
        Interval::ThisYear->value => Operation::None->value,
        Interval::Yesterday->value => Operation::Decrease->value,
        Interval::LastWeek->value => Operation::Decrease->value,
        Interval::LastMonth->value => Operation::Decrease->value,
        Interval::LastYear->value => Operation::Decrease->value,
        Interval::Tomorrow->value => Operation::Increase->value,
        Interval::NextWeek->value => Operation::Increase->value,
        Interval::NextMonth->value => Operation::Increase->value,
        Interval::NextYear->value => Operation::Increase->value,
    ];
}
