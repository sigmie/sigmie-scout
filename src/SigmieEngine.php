<?php

declare(strict_types=1);

namespace Sigmie\Scout;

use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Sigmie\Application\Client;

class SigmieEngine extends Engine
{
    public function __construct(
        protected Client $sigmie
    ) {
    }

    public function deleteAllIndexes()
    {
        $prefix = config('scout.prefix') . '*';

        $indices = $this->sigmie->listIndices($prefix);

        foreach ($indices->json() as $index) {
            $this->sigmie->deleteIndex($index['name']);
        }
    }

    public function lazyMap(Builder $builder, $results, $model)
    {
        $ids = array_map(fn ($hit) => $hit['_id'], $results['hits']);
        $hits = collect($results['hits'])->mapWithKeys(fn ($hit) => [$hit['_id'] => $hit]);

        $models = $model->whereIn('id', $ids)
            ->get()
            ->map(function ($model) use ($hits) {
                $hit = $hits[$model->searchableId()];

                $model->hit($hit);

                return  $model;
            })->sortByDesc(fn ($model) => (float) $model->hit['_score'])
            ->values();

        return  $models;
    }

    public function createIndex($model, array $options = [])
    {
        $model = new $model();

        $indexName = config('scout.prefix') . $model->indexName();

        $response = $this->sigmie->createIndex(
            $indexName,
            $model->sigmieIndex()
        );

        if ($response->failed()) {
            throw new SigmieAPIException($response->json(), $response->psr()->getStatusCode());
        }
    }

    public function deleteIndex($model)
    {
        $model = new $model();

        $indexName = config('scout.prefix') . $model->indexName();

        $response = $this->sigmie->deleteIndex($indexName);

        if ($response->failed()) {
            throw new SigmieAPIException(
                $response->json(),
                $response->psr()->getStatusCode()
            );
        }
    }

    public function update($models)
    {
        $indexName = config('scout.prefix') . $models->first()->indexName();

        $batch = $models->map(fn ($model) => [
            'action' => 'upsert',
            '_id' => $model->searchableId(),
            'body' => $model->toSearchableArray(),
        ])
            ->values()
            ->toArray();

        $response = $this->sigmie->batchWrite($indexName, $batch);

        if ($response->failed()) {
            throw new SigmieAPIException($response->json(), $response->psr()->getStatusCode());
        }
    }

    public function delete($models)
    {
        $indexName = config('scout.prefix') . $models->first()->indexName();

        $batch = $models->map(fn ($model) => [
            'action' => 'delete',
            '_id' => $model->searchableId(),
        ])->toArray();

        $response = $this->sigmie->batchWrite($indexName, $batch);

        if ($response->failed()) {
            throw new SigmieAPIException($response->json(), $response->psr()->getStatusCode());
        }
    }

    public function search(Builder $builder)
    {
        $model = $builder->model;

        $indexName = config('scout.prefix') . $model->indexName();

        $limit = $builder->limit ? $builder->limit : 10;

        $params = [
            'query' => $builder->query ?? '',
            'size' => $limit,
        ];

        if (!is_null($builder->callback)) {
            $params = ($builder->callback)($params);
        }

        $res = $this->sigmie->search($indexName, $params);

        if ($res->failed()) {
            throw new SigmieAPIException($res->json(), $res->psr()->getStatusCode());
        }

        return $res->json();
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        $model = $builder->model;

        $indexName = config('scout.prefix') . $model->indexName();

        $params = [
            'query' => $builder->query ?? '',
            'per_page' => $perPage,
            'page' => $page,
        ];

        if (!is_null($builder->callback)) {
            $params = ($builder->callback)($params);
        }

        $response = $this->sigmie->search($indexName, $params);

        if ($response->failed()) {
            throw new SigmieAPIException($response->json(), $response->psr()->getStatusCode());
        }

        return $response->json();
    }

    public function mapIds($results)
    {
        $ids = array_map(fn ($hit) => $hit['_id'], $results['hits']);

        return collect($ids);
    }

    public function map(Builder $builder, $results, $model)
    {
        $ids = array_map(fn ($hit) => $hit['_id'], $results['hits']);
        $hits = collect($results['hits'])->mapWithKeys(fn ($hit) => [$hit['_id'] => $hit]);

        $models = $model->whereIn('id', $ids)
            ->get()
            ->map(function ($model) use ($hits) {
                $hit = $hits[$model->searchableId()];

                $model->hit($hit);

                return  $model;
            })
            ->sortByDesc(fn ($model) => (float) $model->hit['_score'])
            ->values();

        return  $models;
    }

    public function getTotalCount($results)
    {
        return $results['total'];
    }

    public function flush($model)
    {
        $indexName = config('scout.prefix') . $model->indexName();

        $response = $this->sigmie->clearIndex($indexName);

        if ($response->failed()) {
            throw new SigmieAPIException($response->json(), $response->psr()->getStatusCode());
        }
    }
}
