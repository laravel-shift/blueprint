<?php

namespace Blueprint\Models\Statements;

use Blueprint\Models\Column;
use Illuminate\Support\Str;

class EloquentStatement
{
    private string $operation;

    private ?string $reference;

    private array $columns;

    public function __construct(string $operation, ?string $reference, array $columns = [])
    {
        $this->operation = $operation;
        $this->reference = $reference;
        $this->columns = $columns;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function output(string $controller_prefix, string $context, bool $using_validation = false): string
    {
        $model = $this->determineModel($controller_prefix);
        $code = '';

        if ($this->operation() == 'save') {
            if ($context === 'store') {
                $code = '$' . Str::camel($model);
                $code .= ' = ';
                $code .= $model;

                if ($using_validation) {
                    $code .= '::create($request->validated());';
                } else {
                    $code .= '::create($request->all());';
                }
            } else {
                $code = '$' . Str::camel($model) . '->save();';
            }
        }

        if ($this->operation() == 'update') {
            if (!empty($this->columns())) {
                $columns = implode(', ', array_map(fn ($column) => sprintf("'%s' => \$%s", $column, $column), $this->columns()));

                $code = '$' . Str::camel($model) . '->update([' . $columns . ']);';
            } elseif ($using_validation) {
                $code = '$' . Str::camel($model) . '->update($request->validated());';
            } else {
                $code = '$' . Str::camel($model) . '->update([]);';
            }
        }

        if ($this->operation() == 'find') {
            if ($this->usesQualifiedReference()) {
                $model = $this->extractModel();
            }

            $code = '$' . Str::camel($model);
            $code .= ' = ';
            $code .= $model;
            $code .= '::find($' . Column::columnName($this->reference()) . ');';
        }

        if ($this->operation() === 'delete') {
            if ($this->usesQualifiedReference()) {
                $code = $this->extractModel();
                $code .= '::destroy($' . str_replace('.', '->', $this->reference()) . ');';
            } else {
                // TODO: only for certain contexts or no matter what given simple reference?
                $code = '$' . Str::camel($model) . '->delete();';
            }
        }

        return $code;
    }

    private function usesQualifiedReference(): bool
    {
        return Str::contains($this->reference(), '.');
    }

    private function extractModel(): string
    {
        return Str::studly(Str::before($this->reference(), '.'));
    }

    private function determineModel(string $prefix): string
    {
        if (empty($this->reference()) || $this->reference() === 'id') {
            return Str::studly(Str::singular($prefix));
        }

        return Str::studly($this->reference());
    }
}
