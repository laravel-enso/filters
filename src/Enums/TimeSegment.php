<?php

namespace LaravelEnso\Filters\Enums;

enum TimeSegment: int
{
    case Hourly = 1;
    case Daily = 2;
    case Monthly = 3;
    case Yearly = 4;
}
