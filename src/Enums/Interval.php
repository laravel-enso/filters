<?php

namespace LaravelEnso\Filters\Enums;

enum Interval: string
{
    case Today = 'today';
    case ThisWeek = 'thisWeek';
    case ThisMonth = 'thisMonth';
    case ThisYear = 'thisYear';
    case Yesterday = 'yesterday';
    case LastWeek = 'lastWeek';
    case LastMonth = 'lastMonth';
    case LastYear = 'lastYear';
    case Tomorrow = 'tomorrow';
    case NextWeek = 'nextWeek';
    case NextMonth = 'nextMonth';
    case NextYear = 'nextYear';
    case Custom = 'custom';
    case All = 'all';

    public function adjustment(): ?Operation
    {
        return match ($this) {
            self::Today => Operation::None,
            self::ThisWeek => Operation::None,
            self::ThisMonth => Operation::None,
            self::ThisYear => Operation::None,
            self::Yesterday => Operation::Decrease,
            self::LastWeek => Operation::Decrease,
            self::LastMonth => Operation::Decrease,
            self::LastYear => Operation::Decrease,
            self::Tomorrow => Operation::Increase,
            self::NextWeek => Operation::Increase,
            self::NextMonth => Operation::Increase,
            self::NextYear => Operation::Increase,
            default => null,
        };
    }

    public function isManual(): bool
    {
        return $this === self::Custom || $this === self::All;
    }
}
