<?php

namespace Blueprint\Models\Statements;

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

    public function output(): string
    {
        if ($this->content()) {
            return 'return $' . $this->content . ';';
        }

        return sprintf('return response()->noContent(%s);', $this->status() === 204 ? '' : $this->status());
    }
}
