<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Article;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HasMachineTagsScopesTest extends TestCase
{
    use RefreshDatabase;
    public function setUp()
    {
        parent::setUp();
        Article::create([
            'title' => 'model1',
            'machineTags' => ['dc:author=sam'],
        ]);
        Article::create([
            'title' => 'model2',
            'machineTags' => ['dc:author=sam', 'dc:title=my second test title'],
        ]);
        Article::create([
            'title' => 'model3',
            'machineTags' => ['dc:author=sophie', 'rdf:sameAs=http://www.google.com'],
        ]);
        Article::create([
            'title' => 'model4',
            'machineTags' => ['rdf:author=john', 'rdf:sameAs=http://www.google.com'],
        ]);
    }
    /** @test */
    public function it_provides_as_scope_to_get_all_models_that_have_any_of_the_given_machine_tags()
    {
        $testModels = Article::withAnyMachineTags(['dc:author=sam'])->get();
        $this->assertEquals(['model1', 'model2'], $testModels->pluck('title')->toArray());
    }
    /** @test */
    public function it_provides_as_scope_to_get_all_models_that_have_any_of_the_given_namespace_and_predicate()
    {
        $testModels = Article::withSearchString('dc:author=*')->get();
        $this->assertEquals(['model1', 'model2', 'model3'], $testModels->pluck('title')->toArray());
    }
    /** @test */
    public function it_provides_as_scope_to_get_all_models_that_have_any_of_the_given_predicate()
    {
        $testModels = Article::withSearchString('*:author=*')->get();
        $this->assertEquals(['model1', 'model2', 'model3', 'model4'], $testModels->pluck('title')->toArray());
    }
    /** @test */
    public function it_provides_as_scope_to_get_all_models_that_have_any_of_the_given_value()
    {
        $testModels = Article::withSearchString('*:*=http://www.google.com')->get();
        $this->assertEquals(['model3', 'model4'], $testModels->pluck('title')->toArray());
    }
    /** @test */
    public function it_provides_as_scope_to_get_all_models_that_have_no_values()
    {
        $testModels = Article::withSearchString('*:*=*')->get();
        $this->assertEquals(['model1', 'model2', 'model3', 'model4'], $testModels->pluck('title')->toArray());
    }
}
