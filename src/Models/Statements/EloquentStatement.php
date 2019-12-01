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

    public function output(string $controller_prefix, string $context): string
    {
        $model = $this->determineModel($controller_prefix);
        $code = '';

        if ($this->operation() == 'save') {
            if ($context === 'store') {
                $code = "$" . Str::lower($model);
                $code .= ' = ';
                $code .= $model;
                $code .= '::create($request->all());';
            } else {
                $code = "$" . Str::lower($model) . '->save();';
            }
        }

        if ($this->operation() == 'find') {
            if ($this->usesQualifiedReference()) {
                $model = $this->extractModel();
            }

            $code = "$" . Str::lower($model);
            $code .= ' = ';
            $code .= $model;
            $code .= '::find($' . $this->columnName($this->reference()) . ');';
        }

        if ($this->operation() === 'delete') {
            if ($this->usesQualifiedReference()) {
                $code = $this->extractModel();
                $code .= '::destroy($' . str_replace('.', '->', $this->reference()) . ');';
            } else {
                // TODO: only for certain contexts or no matter what given simple reference?
                $code = "$" . Str::lower($model) . '->delete();';
            }
        }

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

    private function determineModel(string $prefix)
    {
        if (empty($this->reference()) || $this->reference() === 'id') {
            return Str::studly(Str::singular($prefix));
        }

        return Str::studly($this->reference());
    }
}