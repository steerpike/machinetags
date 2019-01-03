<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\MachineTag;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

//Todo: figure out how to get tests working from package
class MachineTagTest extends TestCase
{
    use RefreshDatabase;
    public function setUp()
    {
        parent::setUp();
        $this->assertCount(0, MachineTag::all());
    }
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        exec('php artisan migrate');
    }
    public static function tearDownAfterClass()
    {
        exec('php artisan migrate:reset');
        parent::tearDownAfterClass(); 
    }
    /** @test */
    public function it_can_create_a_machine_tag_with_string()
    {
        $tag = MachineTag::findOrCreate('namespace:predicate=value');
        $this->assertCount(1, MachineTag::all());
    }
    /** @test */
    public function it_can_create_a_machine_tag_with_array()
    {
        $tag = array('namespace'=>'dc', 'predicate'=>'TITLE', 'value'=>'YOU');
        $payload = array();
        $payload[] = $tag;
        $t = MachineTag::findOrCreate($payload);
        $this->assertCount(1, MachineTag::all());
    }
    /** @test */
    public function it_can_create_multiple_machine_tags_with_array()
    {
        $tag1 = array('namespace'=>'dc', 'predicate'=>'author', 'value'=>'YOU');
        $tag2 = array('namespace'=>'dc', 'predicate'=>'license', 'value'=>'Creative Commons');
        $payload = array();
        $payload[] = $tag1;
        $payload[] = $tag2;
        $t = MachineTag::findOrCreate($payload);
        $this->assertCount(2, MachineTag::all());
    }
    /** @test */
    public function it_will_not_create_a_machine_tag_if_the_machine_tag_already_exists()
    {
        MachineTag::findOrCreateFromString('dc:title=my test title');
        MachineTag::findOrCreateFromString('dc:title=my test title');
        $this->assertCount(1, MachineTag::all());
    }
    /** @test */
    public function it_will_list_predicates_of_given_namespace()
    {
        MachineTag::findOrCreateFromString('dc:title=my test title');
        MachineTag::findOrCreateFromString('dc:author=my test author');
        MachineTag::findOrCreateFromString('dc:version=version one');
        $this->assertEquals(['author', 'title', 'version'], 
            MachineTag::getPredicatesWithNamespace('dc')->pluck('predicate')->toArray (),
            "\$canonicalize = true", 0.0, 10, true);
    }
    /** @test */
    public function it_will_list_namespaces_with_given_predicate()
    {
        MachineTag::findOrCreateFromString('Person:name=my test person');
        MachineTag::findOrCreateFromString('Event:name=my test event');
        MachineTag::findOrCreateFromString('Recipe:name=my test recipe');
        $this->assertEquals(['Person', 'Event', 'Recipe'], 
            MachineTag::getNamespacesWithPredicate('name')->pluck('namespace')->toArray(),
            "\$canonicalize = true", 0.0, 10, true);
    }
    /** @test */
    public function it_will_list_values_with_given_namespace_and_predicate()
    {
        MachineTag::findOrCreateFromString('Person:name=John');
        MachineTag::findOrCreateFromString('Person:name=Ringo');
        MachineTag::findOrCreateFromString('Person:name=Paul');
        MachineTag::findOrCreateFromString('Person:name=George');
        $this->assertEquals(['John', 'Ringo', 'Paul', 'George'], 
            MachineTag::getValues(array('namespace'=>'Person', 'predicate'=>'name'))
            ->pluck('value')->toArray(),
            "\$canonicalize = true", 0.0, 10, true);
    }
}
