<?php

namespace Blueprint\Models\Statements;

use Illuminate\Support\Str;

class ResourceStatement
{
    /**
     * @var string
     */
    private $reference;

    /**
     * @var string
     */
    private $collection;

    public function __construct(string $reference, $collection = '')
    {
        $this->reference = $reference;
        $this->collection = $collection;
    }

    public function resource(): string
    {
        return Str::studly(Str::singular($this->reference)) . 'Resource';
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function collection(): string
    {
        return $this->collection;
    }

    public function isCollection(): bool
    {
        return !!$this->collection;
    }

    public function isEmpty(): bool
    {
        return $this->reference === 'empty';
    }

    public function paginate(): string
    {
        return $this->collection == 'collection:paginate' ? '->paginate()' : '';
    }

    public function output(): string
    {
        if ($this->isEmpty()) {
            return 'return response()->json(\'\', 204);';
        }

        if ($this->isCollection()) {
            return 'return ' .
                $this->resource() .
                '::collection($' .
                Str::plural($this->reference) .
                $this->paginate() .
                ');';
        }

        return 'return new ' . $this->resource() . '($' . $this->reference() . ');';
    }
}
