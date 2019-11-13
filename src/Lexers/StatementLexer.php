<?php


namespace Blueprint\Lexers;

use Blueprint\Contracts\Lexer;
use Blueprint\Models\Statements\DispatchStatement;
use Blueprint\Models\Statements\FireStatement;
use Blueprint\Models\Statements\SendStatement;
use Blueprint\Models\Statements\QueryStatement;
use Blueprint\Models\Statements\RenderStatement;
use Blueprint\Models\Statements\ValidateStatement;

class StatementLexer implements Lexer
{
    public function analyze(array $tokens): array
    {
        $statements = [];

        foreach ($tokens as $command => $statement) {
            switch ($command) {
                case 'query':
                case 'find':
                    $statements[] = new QueryStatement();
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
                    $statements[] = $this->analyzeMail($statement);
                    break;
                case 'validate':
                    $statements[] = $this->analyzeValidate($statement);
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

    private function parseWithStatement(string $statement)
    {
        [$object, $with] = $this->extractTokens($statement, 2);

        $data = [];

        if (!empty($with)) {
            $data = preg_split('/,([ \t]+)?/', substr($with, 5));
        }

        return [$object, $data];
    }

    private function analyzeMail($statement)
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

        return new SendStatement($object, $to, $data);
    }

    private function analyzeValidate($statement)
    {
        return new ValidateStatement(preg_split('/,([ \t]+)?/', $statement));
    }

    private function extractTokens(string $statement, int $limit)
    {
        return array_pad(preg_split('/[ \t]+/', $statement, $limit), $limit, null);
    }


}