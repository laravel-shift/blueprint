<?php

namespace Tests\Feature\Lexers;

use Blueprint\Lexers\ConfigLexer;
use Tests\TestCase;

/**
 * @see ConfigLexer
 */
class ConfigLexerTest extends TestCase
{
    /**
     * @var ConfigLexer
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ConfigLexer();
    }

    /**
     * @test
     */
    public function it_updates_config(): void
    {
        $tokens = ['config' => ['key' => 'value']];

        $this->subject->analyze($tokens);

        $this->assertSame($tokens['config']['key'], config('blueprint.key'));
    }
}
