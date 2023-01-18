<?php

use Carbon\Carbon;
use LaravelEnso\Filters\Enums\Intervals;
use LaravelEnso\Filters\Exceptions\Interval as Exception;
use LaravelEnso\Filters\Services\Interval;
use Tests\TestCase;

class IntervalValidatorTest extends TestCase
{
    /** @test */
    public function validates_limit_requirement_on_manual_interval()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::limit()->getMessage());

        new Interval(Intervals::Custom);
    }

    /** @test */
    public function validates_interval_on_manual_interval()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::interval()->getMessage());

        new Interval(Intervals::Custom, Carbon::tomorrow(), Carbon::today());
    }
}
