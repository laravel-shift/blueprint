<?php


namespace Blueprint\Models\Statements;


use Illuminate\Support\Str;

class QueryStatement
{
    /**
     * @var string
     */
    private $operation;

    /**
     * @var string
     */
    private $reference;

    /**
     * @var array
     */
    private $clauses;

    public function __construct(string $operation, string $reference, array $clauses = [])
    {
        $this->operation = $operation;
        $this->reference = $reference;
        $this->clauses = $clauses;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function model(): string
    {
        return Str::studly(Str::singular($this->reference()));
    }

    public function clauses()
    {
        return $this->clauses;
    }

    public function output()
    {
        if ($this->operation() === 'all') {
            return '$' . $this->reference() . ' = ' . $this->model() . '::all();';
        }

        $methods = [];
        $pluck_field = null;
        foreach ($this->clauses as $clause) {
            [$method, $value] = explode(':', $clause);

            if (in_array($method, ['where', 'order', 'pluck'])) {
                $value = $this->columnName($value);
            }

            if ($method === 'where') {
                $methods[] = $method . '(' . "'{$value}', $" . $value . ')';
            } elseif ($method === 'pluck') {
                $pluck_field = $value;
                $methods[] = "pluck('{$value}')";
            } elseif ($method === 'order') {
                $methods[] = "orderBy('{$value}')";
            } else {
                $methods[] = $method . '(' . $value . ')';
            }
        }

        // TODO: leverage model/context...
        $model = 'Post';

        if ($pluck_field) {
            $variable_name = $this->pluckName($pluck_field);
        } else {
            $variable_name = Str::lower(Str::plural($model));
        }

        $code = '$' . $variable_name . ' = ' . $model . '::';

        $code .= implode('->', $methods);

        if (!$pluck_field) {
            $code .= '->get()';
        }

        $code .= ';';

        return $code;
    }

    private function columnName($value)
    {
        if (Str::contains($value, '.')) {
            return Str::after($value, '.');
        }

        return $value;
    }

    private function pluckName(string $field)
    {
        if (Str::contains($field, '.')) {
            dump('here');
            return Str::lower(Str::plural(str_replace('.', '_', $field)));
        }

        return Str::lower('Post' . '_' . Str::plural($field));
    }
}