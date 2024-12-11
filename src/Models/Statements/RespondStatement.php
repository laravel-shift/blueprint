<?php

namespace Blueprint\Models\Statements;

use Illuminate\Support\Str;

class RespondStatement
{
    private int $status = 200;

    private ?string $content = null;

    public function __construct(string $data)
    {
        if (ctype_digit($data)) {
            $this->status = (int)$data;
        } else {
            $this->content = $data;
        }
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): ?string
    {
        return $this->content;
    }

    public function output(array $properties = []): string
    {
        if (is_null($this->content())) {
            return sprintf('return response()->noContent(%s);', $this->status() === 204 ? '' : $this->status());
        }

        if (in_array(Str::before($this->content(), '.'), $properties)) {
            return 'return $this->' . $this->content . ';';
        }

        return 'return $' . $this->content . ';';
    }
}
