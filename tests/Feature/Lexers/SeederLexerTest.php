<?php

namespace Tests\Feature\Lexers;

use Blueprint\Lexers\SeederLexer;
use PHPUnit\Framework\TestCase;

/**
 * @see SeederLexer
 */
class SeederLexerTest extends TestCase
{
    /**
     * @var SeederLexer
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new SeederLexer();
    }

    /**
     * @test
     */
    public function it_returns_nothing_without_seeders_token()
    {
        $this->assertEquals([
            'seeders' => []
        ], $this->subject->analyze([]));
    }

    /**
     * @test
     */
    public function it_returns_seeders()
    {
        $tokens = [
            'seeders' => 'Post'
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['seeders']);
        $this->assertCount(1, $actual['seeders']);

        $this->assertSame(['Post'], $actual['seeders']);
    }

    /**
     * @test
     */
    public function it_returns_multiple_seeders()
    {
        $tokens = [
            'seeders' => 'Post, Comment'
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['seeders']);
        $this->assertCount(2, $actual['seeders']);

        $this->assertSame(['Post', 'Comment'], $actual['seeders']);
    }
}
