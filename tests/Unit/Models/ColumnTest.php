<?php

namespace Tests\Unit\Models;

use Blueprint\Models\Column;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ColumnTest extends TestCase
{
    #[Test]
    public function it_knows_if_its_nullable(): void
    {
        $this->assertTrue((new Column('foo', 'string', ['nullable']))->isNullable());

        $this->assertFalse((new Column('foo', 'string', []))->isNullable());
        $this->assertFalse((new Column('foo', 'string', ['something']))->isNullable());
    }
}
