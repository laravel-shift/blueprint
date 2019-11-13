<?php


namespace Blueprint\Models\Statements;


class FireStatement
{
    /**
     * @var string
     */
    private $event;

    /**
     * @var array
     */
    private $data;

    public function __construct(string $event, array $data = [])
    {
        $this->event = $event;
        $this->data = $data;
    }

    public function event()
    {
        return $this->event;
    }

    /**
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }

    public function isNamedEvent(): bool
    {
        return preg_match('/^[a-z0-9.]+$/', $this->event) === 1;
    }
}