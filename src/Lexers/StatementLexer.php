<?php


namespace Blueprint\Lexers;

use Blueprint\Contracts\Lexer;
use Blueprint\Models\Statements\DispatchStatement;
use Blueprint\Models\Statements\EloquentStatement;
use Blueprint\Models\Statements\FireStatement;
use Blueprint\Models\Statements\QueryStatement;
use Blueprint\Models\Statements\RedirectStatement;
use Blueprint\Models\Statements\RenderStatement;
use Blueprint\Models\Statements\ResourceStatement;
use Blueprint\Models\Statements\RespondStatement;
use Blueprint\Models\Statements\SendStatement;
use Blueprint\Models\Statements\SessionStatement;
use Blueprint\Models\Statements\ValidateStatement;
use Illuminate\Support\Str;

class StatementLexer implements Lexer
{
    public function analyze(array $tokens): array
    {
        $statements = [];

        foreach ($tokens as $command => $statement) {
            switch ($command) {
                case 'query':
                    $statements[] = $this->analyzeQuery($statement);
                    break;
                case 'render':
                    $statements[] = $this->analyzeRender($statement);
                    break;
                case 'fire':
                    $statements[] = $this->analyzeEvent($statement);
                    break;
                case 'dispatch':
                    $statements[] = $this->analyzeDispatch($statement);
                    break;
                case 'send':
                    $statements[] = $this->analyzeSend($statement);
                    break;
                case 'notify':
                    $statements[] = $this->analyzeNotify($statement);
                    break;
                case 'validate':
                    $statements[] = $this->analyzeValidate($statement);
                    break;
                case 'redirect':
                    $statements[] = $this->analyzeRedirect($statement);
                    break;
                case 'respond':
                    $statements[] = $this->analyzeRespond($statement);
                    break;
                case 'resource':
                    $statements[] = $this->analyzeResource($statement);
                    break;
                case 'save':
                case 'delete':
                case 'find':
                    $statements[] = new EloquentStatement($command, $statement);
                    break;
                case 'update':
                    $statements[] = $this->analyzeUpdate($statement);
                    break;
                case 'flash':
                case 'store':
                    $statements[] = new SessionStatement($command, $statement);
                    break;
            }
        }

        return $statements;
    }

    private function analyzeRender(string $statement)
    {
        [$view, $data] = $this->parseWithStatement($statement);

        return new RenderStatement($view, $data);
    }

    private function analyzeEvent(string $statement)
    {
        [$event, $data] = $this->parseWithStatement($statement);

        return new FireStatement($event, $data);
    }

    private function analyzeDispatch(string $statement)
    {
        [$job, $data] = $this->parseWithStatement($statement);

        return new DispatchStatement($job, $data);
    }

    private function analyzeRedirect(string $statement)
    {
        [$route, $data] = $this->parseWithStatement($statement);

        return new RedirectStatement($route, $data);
    }

    private function analyzeRespond(string $statement)
    {
        return new RespondStatement($statement);
    }

    private function analyzeSend($statement)
    {
        $to = null;

        $found = preg_match('/\\s+to:(\\S+)/', $statement, $matches);
        if ($found) {
            $to = $matches[1];
            $statement = str_replace($matches[0], '', $statement);
        }

        [$object, $with] = $this->extractTokens($statement, 2);

        $data = [];
        if (!empty($with)) {
            $data = preg_split('/,([ \t]+)?/', substr($with, 5));
        }

        $type = SendStatement::TYPE_MAIL;
        if (Str::endsWith($object, 'Notification')) {
            $type = SendStatement::TYPE_NOTIFICATION_WITH_FACADE;
        }

        return new SendStatement($object, $to, $data, $type);
    }

    private function analyzeNotify($statement)
    {
        [$model, $notification, $with] = $this->extractTokens($statement, 3);

        $data = [];
        if (!empty($with)) {
            $data = preg_split('/,([ \t]+)?/', substr($with, 5));
        }

        return new SendStatement($notification, $model, $data, SendStatement::TYPE_NOTIFICATION_WITH_MODEL);
    }

    private function analyzeValidate($statement)
    {
        return new ValidateStatement(preg_split('/,([ \t]+)?/', $statement));
    }

    private function parseWithStatement(string $statement)
    {
        [$object, $with] = $this->extractTokens($statement, 2);

        $data = [];

        if (!empty($with)) {
            $data = preg_split('/,([ \t]+)?/', substr($with, 5));
        }

        return [$object, $data];
    }

    private function extractTokens(string $statement, int $limit = -1)
    {
        return array_pad(preg_split('/[ \t]+/', $statement, $limit), $limit, null);
    }

    private function analyzeQuery($statement)
    {
        if ($statement === 'all') {
            return new QueryStatement('all');
        }

        $found = preg_match('/^all:(\\S+)$/', $statement, $matches);
        if ($found) {
            return new QueryStatement('all', [$matches[1]]);
        }

        if (Str::contains($statement, 'pluck:')) {
            return new QueryStatement('pluck', $this->extractTokens($statement));
        }

        $found = preg_match('/\b(count|exists)\b/', $statement, $matches);
        if ($found) {
            return new QueryStatement($matches[1], $this->extractTokens(trim(str_replace($matches[1], '', $statement))));
        }

        return new QueryStatement('get', $this->extractTokens($statement));
    }

    private function analyzeResource($statement)
    {
        $reference = $statement;
        $collection = null;

        if (Str::contains($statement, ':')) {
            $collection = Str::before($reference, ':');
            $reference = Str::after($reference, ':');
        }

        return new ResourceStatement($reference, !is_null($collection), $collection === 'paginate');
    }

    private function analyzeUpdate($statement)
    {
        if (!Str::contains($statement, ',')) {
            return new EloquentStatement('update', $statement);
        }

        $columns = preg_split('/,([ \t]+)?/', $statement);

        return new EloquentStatement('update', null, $columns);
    }
}
