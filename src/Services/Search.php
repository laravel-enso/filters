<?php

namespace LaravelEnso\Filters\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LaravelEnso\Filters\Enums\ComparisonOperator;
use LaravelEnso\Filters\Enums\SearchMode;

class Search
{
    private Builder $query;
    private Collection $attributes;
    private $search;
    private ?Collection $relations;
    private SearchMode $searchMode;
    private ComparisonOperator $comparisonOperator;
    private static array $searchProvider;

    public function __construct(Builder $query, array $attributes, $search)
    {
        $this->query = $query;
        $this->attributes = new Collection($attributes);
        $this->search = $search;
        $this->searchMode = SearchMode::Full;
        $this->comparisonOperator = ComparisonOperator::Like;
        $this->relations = null;
    }

    public function relations(array $relations): self
    {
        $this->relations = new Collection($relations);

        return $this;
    }

    public function searchMode(SearchMode $searchMode): self
    {
        $this->searchMode = $searchMode;

        $this->syncOperator();

        return $this;
    }

    public function comparisonOperator(ComparisonOperator $comparisonOperator): self
    {
        $this->comparisonOperator = $comparisonOperator;

        $this->syncOperator();

        return $this;
    }

    public function handle(): Builder
    {
        if ($this->searchMode === SearchMode::Algolia) {
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
        if ($this->searchMode === SearchMode::ExactMatch) {
            $this->comparisonOperator = ComparisonOperator::Equal;
        } elseif ($this->searchMode === SearchMode::DoesntContain) {
            $this->comparisonOperator = $this->comparisonOperator->invert();
        }
    }

    private function searchArguments(): Collection
    {
        return $this->searchMode === SearchMode::Full
            ? new Collection(explode(' ', $this->search))
            : new Collection($this->search);
    }

    private function matchArgument(Builder $query, $argument): void
    {
        $where = $this->searchMode === SearchMode::DoesntContain
            ? 'where'
            : 'orWhere';

        $this->attributes->each(fn ($attribute) => $query
            ->{$where}(fn ($query) => $this->matchAttribute($query, $attribute, $argument)));

        if (! $this->relations) {
            return;
        }

        $this->relations->each(fn ($attribute) => $query
            ->{$where}(fn ($query) => $this->matchAttribute($query, $attribute, $argument, true)));
    }

    private function matchAttribute(Builder $query, string $attribute, $argument, bool $relation = false): void
    {
        $query->when(
            $relation && $this->isNested($attribute),
            fn ($query) => $this->matchSegments($query, $attribute, $argument),
            fn ($query) => $query->where($attribute, $this->comparisonOperator, $this->wildcards($argument))
        );
    }

    private function isNested($attribute): bool
    {
        return Str::contains($attribute, '.');
    }

    private function matchSegments(Builder $query, string $attribute, $argument)
    {
        $attributes = new Collection(explode('.', $attribute));

        $query->whereHas($attributes->shift(), fn ($query) => $this
            ->matchAttribute($query, $attributes->implode('.'), $argument, true));
    }

    private function wildcards($argument): string
    {
        return match ($this->searchMode) {
            SearchMode::Full,
            SearchMode::DoesntContain => '%'.$argument.'%',
            SearchMode::StartsWith => $argument.'%',
            SearchMode::EndsWith => '%'.$argument,
            SearchMode::ExactMatch => is_bool($argument) ? (int) $argument : $argument,
        };
    }
}
