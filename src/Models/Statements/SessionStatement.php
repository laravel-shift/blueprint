<?php


namespace Blueprint\Models\Statements;


class SessionStatement
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
}