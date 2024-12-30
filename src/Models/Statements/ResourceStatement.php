<?php

namespace Blueprint\Models\Statements;

use Illuminate\Support\Str;

class ResourceStatement
{
    private string $reference;

    private bool $collection = false;

    private bool $paginate = false;

    public function __construct(string $reference, bool $collection = false, bool $paginate = false)
    {
        $this->reference = $reference;
        $this->collection = $collection;
        $this->paginate = $paginate;
    }

    public function name(): string
    {
        if ($this->collection() && $this->generateCollectionClass()) {
            return Str::studly(Str::singular($this->reference)) . 'Collection';
        }

        return Str::studly(Str::singular($this->reference)) . 'Resource';
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function collection(): bool
    {
        return $this->collection;
    }

    public function paginate(): bool
    {
        return $this->paginate;
    }

    public function generateCollectionClass(): bool
    {
        return config('blueprint.generate_resource_collection_classes', true);
    }

    public function output(array $properties = []): string
    {
        if ($this->collection() && !$this->generateCollectionClass()) {
            return sprintf('return %s::collection(%s);', $this->name(), $this->buildArgument($properties));
        }

        return sprintf('return new %s(%s);', $this->name(), $this->buildArgument($properties));
    }

    private function buildArgument(array $properties): string
    {
        if (in_array($this->reference(), $properties)) {
            return '$this->' . $this->reference();
        }

        return '$' . $this->reference();
    }
}
