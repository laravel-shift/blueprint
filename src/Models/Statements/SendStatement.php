<?php


namespace Blueprint\Models\Statements;

class SendStatement
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

    public function output()
    {
        $code = 'Mail::';

        if ($this->to()) {
            $code .= 'to($' . str_replace('.', '->', $this->to()) . ')->';
        }

        $code .= 'send(new ' . $this->mail() . '(';

        if ($this->data()) {
            $code .= $this->buildParameters($this->data());
        }

        $code .= '));';

        return $code;
    }

    private function buildParameters(array $data)
    {
        $parameters = array_map(function ($parameter) {
            return '$' . $parameter;
        }, $data);

        return implode(', ', $parameters);
    }
}
