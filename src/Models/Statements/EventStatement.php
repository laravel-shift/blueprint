<?php


namespace Blueprint\Models\Statements;


class EventStatement
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
}