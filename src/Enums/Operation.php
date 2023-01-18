<?php

namespace LaravelEnso\Filters\Enums;

enum Operation: int
{
    case None = 0;
    case Decrease = -1;
    case Increase = 1;
}
