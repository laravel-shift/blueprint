<?php

namespace Tests\Unit;

use Blueprint\Blueprint;
use Blueprint\Builder;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

/**
 * @covers \Blueprint\EnumType
 */
class EnumTypeTest extends TestCase
{
    /**
     * @test
     * @dataProvider enumOptionsDataProvider
     */
    public function it_returns_options_for_enum($definition, $expected)
    {
        $this->assertEquals($expected, \Blueprint\EnumType::extractOptions($definition));
    }

    public function enumOptionsDataProvider()
    {
        return [
            ["enum('1','2','3')", [1, 2, 3]],
            ["enum('One','Two','Three')", ['One', 'Two', 'Three']],
            ["enum('Spaced and quoted names','John Doe','Connon O''Brien','O''Doul')", ['"Spaced and quoted names"', '"John Doe"','"Connon O\'Brien"', 'O\'Doul']],
        ];
    }
}
