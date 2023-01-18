<?php

use Faker\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use LaravelEnso\Filters\Enums\SearchMode;
use LaravelEnso\Filters\Services\Search;
use Tests\TestCase;

class SearchTest extends TestCase
{
    private SearchTestModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();

        $this->createTestTable();

        $this->model = SearchTestModel::create([
            'name' => $this->faker->name,
            'email' => $this->faker->email,
        ]);
    }

    /** @test */
    public function can_search_by_attribute()
    {
        $search = $this->model->name;
        $query = (new Search(SearchTestModel::query(), ['name'], $search))->handle();

        $this->assertTrue($this->model->is($query->first()));
    }

    /** @test */
    public function can_search_by_multiple_attributes()
    {
        $search = "{$this->model->name} {$this->model->email}";
        $query = (new Search(SearchTestModel::query(), ['name', 'email'], $search))->handle();

        $this->assertTrue($this->model->is($query->first()));
    }

    /** @test */
    public function can_search_by_relation()
    {
        $this->createRelationTable();
        $this->relation = $this->model->relation()->create(['name' => $this->faker->name]);

        $search = $this->relation->name;
        $query = (new Search(SearchTestModel::query(), [], $search))
            ->relations(['relation.name'])->handle();

        $this->assertTrue($this->model->is($query->first()));
    }

    /** @test */
    public function can_search_full()
    {
        $search = $this->model->name[1];
        $query = (new Search(SearchTestModel::query(), ['name'], $search))
            ->searchMode(SearchMode::Full)
            ->handle();

        $this->assertTrue($this->model->is($query->first()));
    }

    /** @test */
    public function can_search_exact_match()
    {
        $search = $this->model->name;
        $query = (new Search(SearchTestModel::query(), ['name'], $search))
            ->searchMode(SearchMode::ExactMatch)
            ->handle();

        $this->assertTrue($this->model->is($query->first()));
    }

    /** @test */
    public function does_not_return_results_if_searching_exact_match_with_partial_argument()
    {
        $search = $this->model->name[1];
        $query = (new Search(SearchTestModel::query(), ['name'], $search))
            ->searchMode(SearchMode::ExactMatch)
            ->handle();

        $this->assertEmpty($query->get());
    }

    /** @test */
    public function can_search_doesnt_contain()
    {
        $search = $this->model->name[1];
        $query = (new Search(SearchTestModel::query(), ['name'], $search))
            ->searchMode(SearchMode::DoesntContain)
            ->handle();

        $this->assertEmpty($query->get());
    }

    /** @test */
    public function can_search_if_starts_with()
    {
        $search = $this->model->name[0];
        $query = (new Search(SearchTestModel::query(), ['name'], $search))
            ->searchMode(SearchMode::StartsWith)
            ->handle();

        $this->assertTrue($this->model->is($query->first()));
    }

    /** @test */
    public function can_search_if_ends_with()
    {
        $search = substr($this->model->name, -1);
        $query = (new Search(SearchTestModel::query(), ['name'], $search))
            ->searchMode(SearchMode::EndsWith)
            ->handle();

        $this->assertTrue($this->model->is($query->first()));
    }

    private function createTestTable()
    {
        Schema::create('search_test_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });
    }

    private function createRelationTable()
    {
        Schema::create('search_test_relations', function ($table) {
            $table->increments('id');
            $table->integer('parent_id');
            $table->foreign('parent_id')->references('id')->on('search_test_models');
            $table->string('name');
            $table->timestamps();
        });
    }
}

class SearchTestModel extends Model
{
    protected $fillable = ['name', 'email'];

    public function relation()
    {
        return $this->hasOne(SearchTestRelation::class, 'parent_id');
    }
}
class SearchTestRelation extends Model
{
    protected $fillable = ['name', 'parent_id'];
}
