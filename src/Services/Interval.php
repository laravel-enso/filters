<?php

namespace LaravelEnso\Filters\Services;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Config;
use Iterator;
use LaravelEnso\Filters\DTOs\Segment;
use LaravelEnso\Filters\Enums\Interval as Enum;
use LaravelEnso\Filters\Enums\TimeSegment;
use LaravelEnso\Filters\Exceptions\Interval as Exception;

class Interval implements Iterator
{
    private array $labels;
    private ?int $adjustment;
    private TimeSegment $timeSegment;
    private Carbon $start;
    private Carbon $end;
    private Closure $incrementer;
    private string $labelFormat;
    private int $key;

    public function __construct(
        private Enum $type,
        private ?Carbon $min = null,
        private ?Carbon $max = null
    ) {
        $this->validate();

        $this->labels = [];
        $this->adjustment = $this->type->adjustment()?->value;

        $this->scenario()->init();
    }

    public function labels(): array
    {
        return $this->labels;
    }

    public function min(): Carbon
    {
        return $this->min->copy();
    }

    public function max(): Carbon
    {
        return $this->max->copy();
    }

    public function current(): Segment
    {
        return new Segment($this->start->copy(), $this->end->copy());
    }

    public function key(): int
    {
        return $this->key;
    }

    public function next(): void
    {
        $this->labels[] = $this->label();

        $incrementer = $this->incrementer;
        $incrementer($this->start);
        $incrementer($this->end);
        $this->key++;
    }

    public function rewind(): void
    {
        $this->scenario()->init();
    }

    public function valid(): bool
    {
        return $this->start->isBefore($this->max);
    }

    public function timeSegment(): TimeSegment
    {
        return $this->timeSegment;
    }

    private function scenario(): self
    {
        return match ($this->type) {
            Enum::Today, Enum::Yesterday, Enum::Tomorrow => $this->days()->hourly(),
            Enum::ThisWeek, Enum::LastWeek, Enum::NextWeek => $this->weeks()->daily(),
            Enum::ThisMonth, Enum::LastMonth, Enum::NextMonth => $this->months()->daily(),
            Enum::ThisYear, Enum::LastYear, Enum::NextYear => $this->years()->monthly(),
            Enum::Custom => $this->custom(),
            Enum::All => $this->all(),
        };
    }

    private function init(): void
    {
        $incrementer = $this->incrementer;
        $this->start = $this->min->copy();
        $this->end = $incrementer($this->start->copy());
        $this->key = 0;
    }

    private function hourly(): self
    {
        $this->incrementer = fn (Carbon $date) => $date->addHour();
        $this->labelFormat = 'H';
        $this->timeSegment = TimeSegment::Hourly;

        return $this;
    }

    private function days(): self
    {
        $this->min = Carbon::today()->addDays($this->adjustment)->startOfDay();
        $this->max = $this->min->copy()->addDay();

        return $this;
    }

    private function daily(): self
    {
        $this->incrementer = fn (Carbon $date) => $date->addDay();
        $this->labelFormat = Config::get('enso.config.dateFormat');
        $this->timeSegment = TimeSegment::Daily;

        return $this;
    }

    private function weeks(): self
    {
        $this->min = Carbon::today()->addWeeks($this->adjustment)->startOfWeek();
        $this->max = $this->min->copy()->addWeek();

        return $this;
    }

    private function months(): self
    {
        $this->min = Carbon::today()->addMonths($this->adjustment)->startOfMonth();
        $this->max = $this->min->copy()->addMonth();

        return $this;
    }

    private function monthly(): self
    {
        $this->incrementer = fn (Carbon $date) => $date->addMonth();
        $this->labelFormat = 'M-y';
        $this->timeSegment = TimeSegment::Monthly;

        return $this;
    }

    private function years(): self
    {
        $this->min = Carbon::today()->addYears($this->adjustment)->startOfYear();
        $this->max = $this->min->copy()->addYear();

        return $this;
    }

    private function yearly(): self
    {
        $this->incrementer = fn (Carbon $date) => $date->addYear();
        $this->labelFormat = 'Y';
        $this->timeSegment = TimeSegment::Yearly;

        return $this;
    }

    private function custom(): self
    {
        $days = $this->max->diffInDays($this->min);

        if ($days === 1) {
            $this->hourly();
        } elseif ($days <= 31) {
            $this->daily();
        } elseif ($days <= 365) {
            $this->monthly();
        } else {
            $this->yearly();
        }

        return $this;
    }

    private function all(): self
    {
        if ($this->min->isSameDay($this->max)) {
            $this->min->startOfHour();
            $this->max->startOfHour()->addHour();
            $this->hourly();
        } elseif ($this->min->copy()->addMonth()->gte($this->max)) {
            $this->min->startOfDay();
            $this->max->startOfDay()->addDay();
            $this->daily();
        } elseif ($this->min->copy()->addYear()->subMonth()->gte($this->max)) {
            $this->min->startOfMonth();
            $this->max->startOfMonth()->addMonth();
            $this->monthly();
        } else {
            $this->min->startOfYear();
            $this->max->startOfYear()->addYear();
            $this->yearly();
        }

        return $this;
    }

    private function label(): string
    {
        $dailyInterval = $this->type === Enum::Today
            || $this->type === Enum::Yesterday
            || $this->type === Enum::Tomorrow;

        return $dailyInterval
            ? $this->end->format($this->labelFormat)
            : $this->start->format($this->labelFormat);
    }

    private function validate(): void
    {
        if ($this->type->isManual()) {
            if (! $this->min || ! $this->max) {
                throw Exception::limit();
            }

            if ($this->min->isAfter($this->max)) {
                throw Exception::interval();
            }
        }
    }
}
