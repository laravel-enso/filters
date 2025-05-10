<?php

use Carbon\Carbon;
use LaravelEnso\Filters\Enums\Interval as Enum;
use LaravelEnso\Filters\Exceptions\Interval as Exception;
use LaravelEnso\Filters\Services\Interval;
use Tests\TestCase;

class IntervalValidatorTest extends TestCase
{
    /** @test */
    public function validates_type()
    {
        $type = 'unknown_type';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::type($type)->getMessage());

        new Interval($type);
    }

    /** @test */
    public function validates_limit_requirement_on_manual_interval()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::limit()->getMessage());

        new Interval(Enum::Custom->value);
    }

    /** @test */
    public function validates_interval_on_manual_interval()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::interval()->getMessage());

        new Interval(Enum::Custom->value, Carbon::tomorrow(), Carbon::today());
    }
}
