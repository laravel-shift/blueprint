<?php

namespace Tests\Feature\Lexers;

use Blueprint\Lexers\SeederLexer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @see SeederLexer
 */
final class SeederLexerTest extends TestCase
{
    /**
     * @var SeederLexer
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new SeederLexer;
    }

    #[Test]
    public function it_returns_nothing_without_seeders_token(): void
    {
        $this->assertEquals([
            'seeders' => [],
        ], $this->subject->analyze([]));
    }

    #[Test]
    public function it_returns_seeders(): void
    {
        $tokens = [
            'seeders' => 'Post',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['seeders']);
        $this->assertCount(1, $actual['seeders']);

        $this->assertSame(['Post'], $actual['seeders']);
    }

    #[Test]
    public function it_returns_multiple_seeders(): void
    {
        $tokens = [
            'seeders' => 'Post, Comment',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['seeders']);
        $this->assertCount(2, $actual['seeders']);

        $this->assertSame(['Post', 'Comment'], $actual['seeders']);
    }
}
