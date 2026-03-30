<?php

use Carbon\Carbon;
use LaravelEnso\Filters\Enums\Intervals;
use LaravelEnso\Filters\Exceptions\Interval as Exception;
use LaravelEnso\Filters\Services\Interval;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IntervalValidatorTest extends TestCase
{
    #[Test]
    public function validates_type()
    {
        $type = 'unknown_type';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::type($type)->getMessage());

        new Interval($type);
    }

    #[Test]
    public function validates_limit_requirement_on_manual_interval()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::limit()->getMessage());

        new Interval(Intervals::Custom);
    }

    #[Test]
    public function validates_interval_on_manual_interval()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::interval()->getMessage());

        new Interval(Intervals::Custom, Carbon::tomorrow(), Carbon::today());
    }
}
