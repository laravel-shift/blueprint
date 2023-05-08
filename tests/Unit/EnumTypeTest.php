<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \Blueprint\EnumType
 */
final class EnumTypeTest extends TestCase
{
    #[Test]
    #[DataProvider('enumOptionsDataProvider')]
    public function it_returns_options_for_enum($definition, $expected): void
    {
        $this->assertEquals($expected, \Blueprint\EnumType::extractOptions($definition));
    }

    public static function enumOptionsDataProvider(): array
    {
        return [
            ["enum('1','2','3')", [1, 2, 3]],
            ["enum('One','Two','Three')", ['One', 'Two', 'Three']],
            ["enum('Spaced and quoted names','John Doe','Connon O''Brien','O''Doul')", ['"Spaced and quoted names"', '"John Doe"', '"Connon O\'Brien"', 'O\'Doul']],
        ];
    }
}
