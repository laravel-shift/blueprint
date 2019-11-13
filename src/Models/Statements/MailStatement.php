<?php


namespace Blueprint\Models\Statements;


class MailStatement
{
    /**
     * @var string
     */
    private $mail;

    /**
     * @var string
     */
    private $to;

    /**
     * @var array
     */
    private $data;

    public function __construct(string $mail, string $to = null, array $data = [])
    {
        $this->mail = $mail;
        $this->data = $data;
        $this->to = $to;
    }

    public function mail()
    {
        return $this->mail;
    }

    public function to()
    {
        return $this->to;
    }

    /**
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }
}