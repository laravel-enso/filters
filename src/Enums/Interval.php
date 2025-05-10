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

    public static function isManual(string $type): bool
    {
        return in_array($type, [self::Custom, self::All]);
    }
}
