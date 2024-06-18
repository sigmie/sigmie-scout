<?php

declare(strict_types=1);

namespace Sigmie\Scout;

use Laravel\Scout\Searchable as ScoutSearchable;

trait Searchable
{
    public readonly array $hit;

    use ScoutSearchable;

    public function searchableAs()
    {
        return static::class;
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();

        $array['created_at'] = $this->created_at?->format('Y-m-d H:i:s.u');
        $array['updated_at'] = $this->updated_at?->format('Y-m-d H:i:s.u');

        return $array;
    }

    public function hit(array $hit)
    {
        $this->hit = $hit;
    }
}
