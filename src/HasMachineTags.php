<?php
namespace Steerpike\Machinetags;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasMachineTags
{
    protected $queuedTags = [];
    public static function bootHasMachineTags()
    {
        static::created(function (Model $taggableModel) {
            $taggableModel->attachMachineTags($taggableModel->queuedTags);
            $taggableModel->queuedTags = [];
        });
        static::deleted(function (Model $deletedModel) {
            $tags = $deletedModel->machineTags()->get();
            $deletedModel->detachMachineTags($tags);
        });
    }
    public static function getMachineTagClassName(): string
    {
        return \Steerpike\MachineTags\MachineTag::class;
    }
    public function machineTags(): MorphToMany
    {
        return $this
            ->morphToMany(self::getMachineTagClassName(), 'machine_taggables');
    }
    public function scopeWithAllMachineTags(Builder $query, $tags): Builder
    {
        $tags = static::convertToMachineTags($tags);
        collect($tags)->each(function ($tag) use ($query) {
            $query->whereIn("{$this->getTable()}.{$this->getKeyName()}", function ($query) use ($tag) {
                $query->from('machine_taggables')
                    ->select('machine_taggables.machine_taggables_id')
                    ->where('machine_taggables.machine_tag_id', $tag ? $tag->id : 0);
            });
        });
        return $query;
    }
    public function scopeWithAnyMachineTags(Builder $query, $tags): Builder
    {
        $tags = static::convertToMachineTags($tags);
        return $query->whereHas('machineTags', function (Builder $query) use ($tags) {
            $tagIds = collect($tags)->pluck('id');
            $query->whereIn('machine_tags.id', $tagIds);
        });
    }
    public function scopeWithSearchString(Builder $query, $searchString): Builder
    {
        $className = static::getMachineTagClassName();
        $search = $className::splitStringMachineTagForSearch($searchString);
        $namespace = $search['namespace'];
        $predicate = $search['predicate'];
        $value = $search['value'];
        if($namespace && $predicate && $value) {
            $tags = $className::findFromArray($search);
        } elseif($namespace && $predicate) {
            $tags = $className::getValues($search);
        } elseif($predicate) {
            $tags = $className::getNamespacesWithPredicate($predicate);
        } elseif($namespace) {
            $tags = $className::getPredicatesWithNamespace($namespace);
        } elseif($value) {
            $tags = $className::getByValue($value);
        } elseif(!$namespace && !$predicate && !$value) {
            return $query;
        }
        $tags = static::convertToMachineTags($tags);
        return $query->whereHas('machineTags', function (Builder $query) use ($tags) {
            $tagIds = collect($tags)->pluck('id');
            $query->whereIn('machine_tags.id', $tagIds);
        });
    }
    public function attachMachineTags($tags)
    {
        $className = static::getMachineTagClassName();
        $tags = collect($className::findOrCreate($tags));
        $this->machineTags()->syncWithoutDetaching($tags->pluck('id')->toArray());
        return $this;
    }
    public function attachMachineTag($tag)
    {
        return $this->attachMachineTags([$tag]);
    }
    public function setMachineTagsAttribute($tags)
    {
        if (! $this->exists) {
            $this->queuedTags = $tags;
            return;
        }
        $this->attachMachineTags($tags);
    }
    public function detachMachineTags($tags)
    {
        $tags = static::convertToMachineTags($tags);
        collect($tags)
            ->filter()
            ->each(function (MachineTag $tag) {
                $this->machineTags()->detach($tag);
            });
        return $this;
    }
    
    public function detachMachineTag($tag)
    {
        return $this->detachMachineTags([$tag]);
    }
    protected static function convertToMachineTags($values)
    {
        return collect($values)->map(function ($value) {
            if ($value instanceof MachineTag) {
                return $value;
            }
            $className = static::getMachineTagClassName();
            return $className::findFromString($value);
        });
    }
    public function syncMachineTags($tags)
    {
        $className = static::getMachineTagClassName();
        $tags = collect($className::findOrCreate($tags));
        $this->machineTags()->sync($tags->pluck('id')->toArray());
        return $this;
    }
}