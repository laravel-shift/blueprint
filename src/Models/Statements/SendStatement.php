<?php

namespace Blueprint\Models\Statements;

use Blueprint\Concerns\HasParameters;
use Illuminate\Support\Str;

class SendStatement
{
    use HasParameters;

    const TYPE_MAIL = 'mail';

    const TYPE_NOTIFICATION_WITH_FACADE = 'notification_with_facade';

    const TYPE_NOTIFICATION_WITH_MODEL = 'notification_with_model';

    private string $mail;

    private ?string $to;

    private string $type;

    private string $view;

    private string $subject;

    public function __construct(string $mail, ?string $to, array $data, string $type, ?string $view = null)
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

    public function mail(): string
    {
        return $this->mail;
    }

    public function to(): ?string
    {
        return $this->to;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function output(): string
    {
        if ($this->type() === self::TYPE_NOTIFICATION_WITH_FACADE) {
            return $this->notificationFacadeOutput();
        }

        if ($this->type() === self::TYPE_NOTIFICATION_WITH_MODEL) {
            return $this->notificationModelOutput();
        }

        return $this->mailOutput();
    }

    public function isNotification(): bool
    {
        return $this->type() === SendStatement::TYPE_NOTIFICATION_WITH_FACADE || $this->type() === SendStatement::TYPE_NOTIFICATION_WITH_MODEL;
    }

    public function view(): string
    {
        return $this->view;
    }

    private function mailOutput(): string
    {
        $code = 'Mail::';

        if ($this->to()) {
            $code .= sprintf('to(%s)->', $this->buildTo());
        }

        $code .= 'send(new ' . $this->mail() . '(';

        if ($this->data()) {
            $code .= $this->buildParameters();
        }

        $code .= '));';

        return $code;
    }

    private function notificationFacadeOutput(): string
    {
        $code = 'Notification::';

        if ($this->to()) {
            $code .= sprintf('send(%s, new %s(', $this->buildTo(), $this->mail());
        }

        if ($this->data()) {
            $code .= $this->buildParameters();
        }

        $code .= '));';

        return $code;
    }

    private function notificationModelOutput(): string
    {
        $code = '';

        if ($this->to()) {
            $code .= sprintf('%s->notify(new %s(', $this->buildTo(), $this->mail());
        }

        if ($this->data()) {
            $code .= $this->buildParameters();
        }

        $code .= '));';

        return $code;
    }

    private function buildTo(): string
    {
        $variable = str_replace('.', '->', $this->to());

        if (in_array(Str::before($this->to(), '.'), $this->properties())) {
            $variable = 'this->' . $variable;
        }

        return '$' . $variable;
    }
}
