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
     * @var bool
     */
    private $collection = false;

    /**
     * @var bool
     */
    private $paginate = false;

    public function __construct(string $reference, bool $collection = false, bool $paginate = false)
    {
        $this->reference = $reference;
        $this->collection = $collection;
        $this->paginate = $paginate;
    }

    public function name(): string
    {
        if ($this->collection()) {
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

    public function output(): string
    {
        return sprintf('return new %s($%s);', $this->name(), $this->reference());
    }
}
