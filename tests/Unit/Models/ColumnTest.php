<?php

namespace Tests\Unit\Models;

use Blueprint\Models\Column;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    /** @test */
    public function it_knows_if_its_nullable()
    {
        $this->assertTrue((new Column('foo', 'string', ['nullable']))->isNullable());

        $this->assertFalse((new Column('foo', 'string', []))->isNullable());
        $this->assertFalse((new Column('foo', 'string', ['something']))->isNullable());
    }
}
