<?php

namespace LaravelEnso\Filters\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use LaravelEnso\Filters\Enums\ComparisonOperators;
use LaravelEnso\Filters\Enums\SearchModes;
use LaravelEnso\Filters\Exceptions\ComparisonOperator;
use LaravelEnso\Filters\Exceptions\SearchMode;

class Search
{
    private Builder $query;
    private Collection $attributes;
    private $search;
    private Collection $relations;
    private string $searchMode;
    private ComparisonOperators $operators;
    private string $comparisonOperator;
    private static array $searchProvider;

    public function __construct(Builder $query, array $attributes, $search)
    {
        $this->query = $query;
        $this->attributes = new Collection($attributes);
        $this->search = $search;
        $this->searchMode = SearchModes::Full;
        $this->operators = App::make(ComparisonOperators::class);
        $this->comparisonOperator = $this->operators::Like;
        $this->relations = new Collection();
    }

    public function relations(array $relations): self
    {
        $this->relations->push(...$relations);

        return $this;
    }

    public function searchMode(string $searchMode): self
    {
        if (! SearchModes::keys()->contains($searchMode)) {
            throw SearchMode::unknown();
        }

        $this->searchMode = $searchMode;

        $this->syncOperator();

        return $this;
    }

    public function comparisonOperator(string $comparisonOperator): self
    {
        if (! $this->operators::keys()->contains($comparisonOperator)) {
            throw ComparisonOperator::unknown();
        }

        $this->comparisonOperator = $comparisonOperator;

        $this->syncOperator();

        return $this;
    }

    public function handle(): Builder
    {
        if ($this->searchMode === SearchModes::Algolia) {
            return $this->searchProvider();
        }

        $excepted = fn ($argument) => in_array($argument, [null, ''], true);

        $this->searchArguments()->reject($excepted)
            ->each(fn ($argument) => $this->query
                ->where(fn ($query) => $this->matchArgument($query, $argument)));

        return $this->query;
    }

    private function searchProvider(): Builder
    {
        $model = $this->query->getModel();
        $table = $this->query->getModel()->getTable();
        $key = $this->query->getModel()->getKeyName();
        $keys = $this->searchProviderKeys($model, $key);

        return $this->query->whereIn("{$table}.{$key}", $keys);
    }

    private function searchProviderKeys(Model $model, string $key): array
    {
        $table = $model->getTable();

        if (! isset(self::$searchProvider[$table][$this->search])) {
            $paginator = $model::search($this->search)->paginate(100)->toArray();

            $keys = Collection::wrap($paginator['data'])->pluck($key)->toArray();

            self::$searchProvider[$table][$this->search] = array_slice($keys, 0, 10);
        }

        return self::$searchProvider[$table][$this->search];
    }

    private function syncOperator()
    {
        if ($this->searchMode === SearchModes::ExactMatch) {
            $this->comparisonOperator = $this->operators::Equal;
        } elseif ($this->searchMode === SearchModes::DoesntContain) {
            $this->comparisonOperator = $this->operators::invert($this->comparisonOperator);
        }
    }

    private function searchArguments(): Collection
    {
        return $this->searchMode === SearchModes::Full
            ? new Collection(explode(' ', $this->search))
            : new Collection($this->search);
    }

    private function matchArgument(Builder $query, $argument): void
    {
        $where = $this->searchMode === SearchModes::DoesntContain
            ? 'where'
            : 'orWhere';

        $this->attributes->each(fn ($attribute) => $query
            ->{$where}(fn ($query) => $query
                ->where($attribute, $this->comparisonOperator, $this->wildcards($argument))));

        $this->relations->each(fn ($relation) => $query
            ->{$where}(fn ($query) => $query->whereRelation(
                Str::beforeLast($relation, '.'),
                Str::afterLast($relation, '.'),
                $this->comparisonOperator,
                $this->wildcards($argument)
            )));
    }

    private function wildcards($argument): string
    {
        return match ($this->searchMode) {
            SearchModes::Full,
            SearchModes::DoesntContain => '%'.$argument.'%',
            SearchModes::StartsWith => $argument.'%',
            SearchModes::EndsWith => '%'.$argument,
            SearchModes::ExactMatch => is_bool($argument) ? (int) $argument : $argument,
        };
    }
}
