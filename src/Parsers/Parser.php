<?php


namespace Blueprint\Parsers;


use Symfony\Component\Yaml\Yaml;

class Parser
{
    public static function parse(string $text)
    {
        $yaml = self::preprocess($text);

        return Yaml::parse($yaml);
    }

    private static function preprocess($text)
    {
        return preg_replace('/^(\s+)(id|timestamps)$/m', '$1$2: $2', $text);
    }
}