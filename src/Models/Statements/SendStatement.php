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

    /**
     * @var string
     */
    private $type;

    public function __construct(string $mail, string $to = null, array $data = [], string $type)
    {
        $this->mail = $mail;
        $this->data = $data;
        $this->to = $to;
        $this->type = $type;
    }

    public function mail()
    {
        return $this->mail;
    }

    public function to()
    {
        return $this->to;
    }

    public function type()
    {
        return $this->type;
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
        return $this->type() === 'mail' ? $this->mailOutput() : $this->notificationOutput();
    }

    private function mailOutput()
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

    private function notificationOutput()
    {
        $code = 'Notification::';

        if ($this->to()) {
            $code .= 'send($' . str_replace('.', '->', $this->to()) . ', new ' . $this->mail() . '(';
        }

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
