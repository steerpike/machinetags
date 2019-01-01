<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Article;
use App\MachineTag;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class HasMachineTagTest extends TestCase
{
    protected $testModel;
  
    public function setUp()
    {
        parent::setUp();
        $this->testModel = Article::create(['title' => 'default']);
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
    public function it_provides_an_article()
    {
        $this->assertInstanceOf(Article::class, $this->testModel);
    }
    /** @test */
    public function it_provides_a_tags_relation()
    {
        $this->assertInstanceOf(MorphToMany::class, $this->testModel->machineTags());
    }
    /** @test */
    
    public function it_can_attach_a_machine_tag_as_string()
    {
        $this->testModel->attachMachineTag('dc:author=me');
        $this->assertCount(1, $this->testModel->machineTags);
        $this->assertEquals(['me'], 
            $this->testModel->machineTags->pluck('value')->toArray());
    }
    
    /** @test */
    
    public function it_can_attach_a_machine_tag_as_array()
    {
        $tag = array('namespace'=>'dc', 'predicate'=>'title', 'value'=>'me');
        $payload = array();
        $payload[] = $tag;
        $this->testModel->attachMachineTag($payload);
        $this->assertCount(1, $this->testModel->machineTags);
        $this->assertEquals(['me'], 
            $this->testModel->machineTags->pluck('value')->toArray());
    }

    /** @test */
    public function it_can_attach_a_machine_tag_multiple_times_without_creating_duplicate_entries()
    {
        $this->testModel->attachMachineTag('dc:author=me');
        $this->testModel->attachMachineTag('dc:author=me');
        $this->assertCount(1, $this->testModel->machineTags);
    }
    /** @test */
    public function it_can_use_a_machine_tag_model_when_attaching_a_machine_tag()
    {
        $tag = MachineTag::findOrCreate('dc:author=me');
        $this->testModel->attachMachineTag($tag);
        $this->assertEquals(['author'], $this->testModel->machineTags->pluck('predicate')->toArray());
    }
    /** @test */
    public function it_can_attach_a_machine_tag_inside_a_static_create_method()
    {
        $testModel = Article::create([
            'title' => 'New test article',
            'machineTags' => ['dc:author=me', 'dc:license=Creative Commons'],
        ]);
        $this->assertCount(2, $testModel->machineTags);
    }
    /** @test */
    public function it_can_attach_a_machine_tag_via_the_machine_tags_mutator()
    {
        $this->testModel->machineTags = ['dc:author=me'];
        $this->testModel->save();
        $this->assertCount(1, $this->testModel->machineTags);
    }
    /** @test */
    public function it_can_attach_multiple_machine_tags_via_the_tags_mutator()
    {
        $this->testModel->machineTags = ['dc:author=me', 'dc:license=Creative Commons'];
        $this->testModel->save();
        $this->assertCount(2, $this->testModel->machineTags);
    }
    /** @test */
    public function it_can_attach_multiple_machine_tags()
    {
        $this->testModel->attachMachineTags(['dc:author=me', 'dc:license=Creative Commons']);
        $this->testModel->save();
        $this->assertCount(2, $this->testModel->machineTags);
    }
    /** @test */
    public function it_can_detach_a_machinetag()
    {
        $this->testModel->attachMachineTags(['dc:author=me', 'dc:author=Sophie', 'dc:author=Max']);
        $this->testModel->detachMachineTag('dc:author=Max');
        $this->assertEquals(['me', 'Sophie'], $this->testModel->machineTags->pluck('value')->toArray());
    }
     /** @test */
     public function it_can_detach_multiple_machinetags()
     {
         $this->testModel->attachMachineTags(['dc:author=me', 'dc:author=Sophie', 'dc:author=Max']);
         $this->testModel->detachMachineTags(['dc:author=Max','dc:author=me']);
         $this->assertEquals(['Sophie'], $this->testModel->machineTags->pluck('value')->toArray());
     }
     /** @test */
    public function it_can_sync_a_single_machine_tag()
    {
        $tag1 = array('namespace'=>'dc', 'predicate'=>'title', 'value'=>'Peter');
        $tag2 = array('namespace'=>'dc', 'predicate'=>'title', 'value'=>'Paul');
        $tag3 = array('namespace'=>'dc', 'predicate'=>'title', 'value'=>'Mary');
        $payload = array();
        $payload[] = $tag1;
        $payload[] = $tag2;
        $payload[] = $tag3;
        $this->testModel->attachMachineTags($payload);
        $this->testModel->syncMachineTags([$tag3]);
        $this->assertEquals(['Mary'], $this->testModel->machineTags->pluck('value')->toArray());
    }
    /** @test */
    public function it_can_sync_multiple_machine_tags()
    {
        $tag1 = array('namespace'=>'dc', 'predicate'=>'title', 'value'=>'Peter');
        $tag2 = array('namespace'=>'dc', 'predicate'=>'title', 'value'=>'Paul');
        $tag3 = array('namespace'=>'dc', 'predicate'=>'title', 'value'=>'Mary');
        $payload = array();
        $payload[] = $tag1;
        $payload[] = $tag2;
        $payload[] = $tag3;
        $this->testModel->attachMachineTags($payload);
        $tag4 = array('namespace'=>'dc', 'predicate'=>'title', 'value'=>'Joseph');
        $this->testModel->syncMachineTags([$tag3, $tag4]);
        $this->assertEquals(['Mary', 'Joseph'], $this->testModel->machineTags->pluck('value')->toArray());
    }
    /** @test */
    public function it_can_detach_machine_tags_on_model_delete()
    {
        $this->testModel->attachMachineTags(['dc:author=Sophie']);
        $this->testModel->delete();
        $this->assertEquals(0, $this->testModel->machineTags()->get()->count());
    }
    /** @test */
    public function it_can_delete_models_without_machine_tags()
    {
        $this->assertTrue($this->testModel->delete());
    }
}
