<?php

declare(strict_types=1);

namespace Sigmie\Scout;

use Carbon\Carbon;
use DateTime;
use Laravel\Scout\Searchable as ScoutSearchable;

trait Searchable
{
    public readonly array $hit;

    use ScoutSearchable;

    public function searchableAs()
    {
        return static::class;
    }

    public function indexName()
    {
        return strtolower(class_basename($this->searchableAs()));
    }

    public function searchableId()
    {
        return $this->id;
    }

    public function sigmieIndex(): array
    {
        $mappings = collect($this->sigmieMappings ?? [
            'created_at' => 'date',
            'updated_at' => 'date',
        ])->map(function ($type, $name) {
            return [
                'name' => $name,
                'type' => $type,
            ];
        })->values()
            ->toArray();

        return  [
            'mappings' => $mappings,
        ];
    }
}
