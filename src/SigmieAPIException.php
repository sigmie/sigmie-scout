<?php

namespace Sigmie\Scout;

use Exception;

class SigmieAPIException extends Exception
{
    public function __construct(public array $json, int $code)
    {
        parent::__construct(json_encode($json, JSON_PRETTY_PRINT), $code);
    }

    public function json(null|int|string $key = null): int|bool|string|array|null|float
    {
        return dot($this->json)->get($key);
    }

    public function toRaw(): array
    {
        return json_decode($this->message, true);
    }
}
