<?php

use Carbon\Carbon;
use LaravelEnso\Filters\Enums\Intervals;
use LaravelEnso\Filters\Enums\TimeSegments;
use LaravelEnso\Filters\Services\Interval;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IntervalAllTest extends TestCase
{
    #[Test]
    public function chooses_hourly_segments_for_same_day_ranges(): void
    {
        $interval = new Interval(
            Intervals::All,
            Carbon::parse('2026-01-01 08:00:00'),
            Carbon::parse('2026-01-01 16:00:00')
        );

        $this->assertSame(TimeSegments::Hourly, $interval->timeSegment());
    }

    #[Test]
    public function chooses_yearly_segments_for_large_ranges(): void
    {
        $interval = new Interval(
            Intervals::All,
            Carbon::parse('2020-01-01 00:00:00'),
            Carbon::parse('2026-01-01 00:00:00')
        );

        $this->assertSame(TimeSegments::Yearly, $interval->timeSegment());
    }
}
