<?php

namespace Steerpike\Machinetags;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as DbCollection;

class MachineTag extends Model
{
    //
    protected $guarded = [];

    public function scopeWithNamespace(Builder $query, string $namespace = null): Builder
    {
        if (is_null($namespace)) {
            return $query;
        }
        return $query->where('namespace', $namespace);
    }
    public function scopeWithPredicate(Builder $query, string $predicate = null): Builder
    {
        if (is_null($predicate)) {
            return $query;
        }
        return $query->where('predicate', $predicate);
    }
    public function scopeWithNamespaceAndPredicate(Builder $query, array $elements = null): Builder
    {
        if (is_null($elements) || !is_array($elements)) {
            return $query;
        }
        return $query
            ->where('namespace', $elements['namespace'])
            ->where('predicate', $elements['predicate']);
    }
    public function scopeWithValue(Builder $query, string $value = null): Builder
    {
        if (is_null($value)) {
            return $query;
        }
        return $query->where('value', $value);
    }
    public static function findFromArray(array $payload)
    {
        $namespace = $payload['namespace'];
        $predicate = $payload['predicate'];
        $value = $payload['value'];
        return static::query()
            ->when($namespace, function($query, $namespace) {
                return $query->where("namespace", $namespace);
            })
            ->when($predicate, function($query, $predicate) {
                return $query->where('predicate', $predicate);
            })
            ->when($value, function($query, $value) {
                return $query->where('value', $value);
            })
            ->first();
            /*
        return static::query()
            ->where("namespace", $payload['namespace'])
            ->where('predicate', $payload['predicate'])
            ->where('value', $payload['value'])
            ->first();
            */
    }
    public static function findOrCreate($values)
    {
        $tags = collect($values)->map(function ($value) {
            if ($value instanceof MachineTag) {
                return $value;
            }
            if(is_array($value)) {
                if(array_key_exists('namespace', $value)) {
                    return static::findOrCreateFromArray($value);
                } else {
                    foreach($value as $tag) {
                        return static::findOrCreateFromArray($tag);
                    }
                }
            }
            if(is_string($value)) {
                return static::findOrCreateFromString($value);
            }
        });
        return is_string($values) ? $tags->first() : $tags;
    }
    public static function findOrCreateFromString(string $name)
    {
        $payload = static::splitStringToMachineTag($name);
        return static::findOrCreateFromArray($payload);
    }
    public static function findFromString(string $name)
    {
        $payload = static::splitStringMachineTagForSearch($name);
        return static::findFromArray($payload);
    }
    public static function findOrCreateFromArray(array $payload)
    {
        $tag = static::findFromArray($payload);
        if (! $tag) {
            $tag = static::create([
                'namespace' => $payload['namespace'],
                'predicate' => $payload['predicate'],
                'value' => $payload['value']
            ]);
        }
        return $tag;
    }
    public static function splitStringToMachineTag(string $name)
    {
        $result = array();
        $split = explode("=", $name);
        $value = $split[1]; 
        $values = explode(":", $split[0]);
        $namespace = $values[0];
        $predicate = $values[1];
        $result['namespace'] = $namespace;
        $result['predicate'] = $predicate;
        $result['value'] = $value;
        return $result;
    }
    public static function splitStringMachineTagForSearch(string $name)
    {
        $result = array();
        $split = explode("=", $name);
        $namespace = $predicate = $value = false;
        if(array_key_exists(1, $split)){
            $value = $split[1];
        }
        $values = explode(":", $split[0]);
        if(array_key_exists(1, $values)) {
            $predicate = $values[1];
        }
        $namespace = $values[0];
        if($namespace == "*") { $namespace= false; }
        if($predicate == "*") { $predicate= false; }
        if($value == "*") { $value= false; }
        $result['namespace'] = $namespace;
        $result['predicate'] = $predicate;
        $result['value'] = $value;
        return $result;
    }
    public static function getPredicatesWithNamespace(string $namespace): DbCollection
    {
        return static::withNamespace($namespace)->get();
    }
    public static function getNamespacesWithPredicate(string $predicate): DbCollection
    {
        return static::withPredicate($predicate)->get();
    }
    public static function getValues(array $elements): DbCollection
    {
        return static::withNamespaceAndPredicate($elements)->get();
    }
    public static function getByValue($value): DbCollection
    {
        return static::withValue($value)->get();
    }

}
