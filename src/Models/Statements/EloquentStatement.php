<?php


namespace Blueprint\Models\Statements;


use Illuminate\Support\Str;

class EloquentStatement
{
    /**
     * @var string
     */
    private $operation;

    /**
     * @var string
     */
    private $reference;

    public function __construct(string $operation, string $reference)
    {
        $this->operation = $operation;
        $this->reference = $reference;
    }

    public function operation(): string
    {
        return $this->operation;
    }
    
    public function reference(): string
    {
        return $this->reference;
    }

    public function output()
    {
        if ($this->operation() == 'save') {
            $code = "$" . Str::lower(Str::singular($this->reference()));
            $code .= ' = ';
            $code .= Str::studly($this->reference());
            $code .= '::create($request->all());';
        }

        if ($this->operation() == 'find') {
            if ($this->usesQualifiedReference()) {
                $model = $this->extractModel();
            } else {
                // TODO: this needs to be a real model reference
                $model = 'Model';
            }
            $code = "$" . Str::lower(Str::singular($model));
            $code .= ' = ';
            $code .= $model;
            $code .= '::find($' . $this->columnName($this->reference()) . ');';
        }

        // TODO: handle other operations: destroy

        return $code;
    }

    // TODO: Share this so all other places can use it (Column::columnName($qualifiedName))
    private function columnName($value)
    {
        if (Str::contains($value, '.')) {
            return Str::after($value, '.');
        }

        return $value;
    }

    private function usesQualifiedReference()
    {
        return Str::contains($this->reference(), '.');
    }

    private function extractModel()
    {
        return Str::studly(Str::before($this->reference(), '.'));
    }
}