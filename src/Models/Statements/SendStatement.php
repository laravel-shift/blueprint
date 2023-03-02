<?php

namespace Blueprint\Models\Statements;

use Illuminate\Support\Str;

class SendStatement
{
    const TYPE_MAIL = 'mail';

    const TYPE_NOTIFICATION_WITH_FACADE = 'notification_with_facade';

    const TYPE_NOTIFICATION_WITH_MODEL = 'notification_with_model';

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

    /**
     * @var string
     */
    private $view;

    /**
     * @var string
     */
    private $subject;

    public function __construct(string $mail, ?string $to, array $data, string $type, string $view = null)
    {
        $this->mail = $mail;
        $this->data = $data;
        $this->to = $to;
        $this->type = $type;
        $this->view = $view ?? 'emails.' . Str::kebab($this->mail);
        $this->subject = $type === self::TYPE_MAIL ? Str::headline($this->mail) : '';
    }

    public function subject(): string
    {
        return $this->subject;
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

    public function data(): array
    {
        return $this->data;
    }

    public function output()
    {
        if ($this->type() === self::TYPE_NOTIFICATION_WITH_FACADE) {
            return $this->notificationFacadeOutput();
        }

        if ($this->type() === self::TYPE_NOTIFICATION_WITH_MODEL) {
            return $this->notificationModelOutput();
        }

        return $this->mailOutput();
    }

    public function isNotification()
    {
        return $this->type() === SendStatement::TYPE_NOTIFICATION_WITH_FACADE || $this->type() === SendStatement::TYPE_NOTIFICATION_WITH_MODEL;
    }

    public function view()
    {
        return $this->view;
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

    private function notificationFacadeOutput()
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

    private function notificationModelOutput()
    {
        $code = '';

        if ($this->to()) {
            $code .= sprintf('$%s->', str_replace('.', '->', $this->to()));
            $code .= 'notify(new ' . $this->mail() . '(';
        }

        if ($this->data()) {
            $code .= $this->buildParameters($this->data());
        }

        $code .= '));';

        return $code;
    }

    private function buildParameters(array $data)
    {
        $parameters = array_map(fn ($parameter) => '$' . $parameter, $data);

        return implode(', ', $parameters);
    }
}
