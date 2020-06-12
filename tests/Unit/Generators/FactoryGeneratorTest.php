<?php

namespace Tests\Unit\Generators;

use Blueprint\Generators\FactoryGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Blueprint\Generators\FactoryGenerator
 */
class FactoryGeneratorTest extends TestCase
{
    /**
     * @test
     * @dataProvider commonNameDataProvider
     */
    public function it_translates_a_common_name_to_fake_data($name, $faker)
    {
        $this->assertEquals(FactoryGenerator::fakerData($name), $faker);
    }

    /**
     * @test
     * @dataProvider dataTypeDataProvider
     */
    public function it_translates_a_data_type_to_fake_data($type, $faker)
    {
        $this->assertEquals(FactoryGenerator::fakerDataType($type), $faker);
    }

    public function commonNameDataProvider()
    {
        return [
            ['address1', 'streetAddress'],
            ['address2', 'secondaryAddress'],
            ['city', 'city'],
            ['company', 'company'],
            ['content', 'paragraphs(3, true)'],
            ['country', 'country'],
            ['description', 'text'],
            ['email', 'safeEmail'],
            ['first_name', 'firstName'],
            ['firstname', 'firstName'],
            ['guid', 'uuid'],
            ['last_name', 'lastName'],
            ['lastname', 'lastName'],
            ['lat', 'latitude'],
            ['latitude', 'latitude'],
            ['lng', 'longitude'],
            ['longitude', 'longitude'],
            ['name', 'name'],
            ['password', 'password'],
            ['phone', 'phoneNumber'],
            ['phone_number', 'phoneNumber'],
            ['postal_code', 'postcode'],
            ['postcode', 'postcode'],
            ['slug', 'slug'],
            ['ssn', 'ssn'],
            ['street', 'streetName'],
            ['summary', 'text'],
            ['title', 'sentence(4)'],
            ['url', 'url'],
            ['user_name', 'userName'],
            ['username', 'userName'],
            ['uuid', 'uuid'],
            ['zip', 'postcode'],
        ];
    }

    public function dataTypeDataProvider()
    {
        return [
            ['biginteger', 'randomNumber()'],
            ['boolean', 'boolean'],
            ['date', 'date()'],
            ['datetime', 'dateTime()'],
            ['datetimetz', 'dateTime()'],
            ['double', 'randomFloat(/** double_attributes **/)'],
            ['decimal', 'randomFloat(/** decimal_attributes **/)'],
            ['enum', 'randomElement(/** enum_attributes **/)'],
            ['float', 'randomFloat(/** float_attributes **/)'],
            ['guid', 'uuid'],
            ['id', 'randomDigitNotNull'],
            ['integer', 'randomNumber()'],
            ['longtext', 'text'],
            ['set', 'randomElement(/** set_attributes **/)'],
            ['smallint', 'randomNumber()'],
            ['smallinteger', 'randomNumber()'],
            ['string', 'word'],
            ['text', 'text'],
            ['time', 'time()'],
            ['timestamp', 'dateTime()'],
            ['tinyinteger', 'randomNumber()'],
            ['unsignedsmallinteger', 'randomDigitNotNull'],
            ['uuid', 'uuid'],
        ];
    }
}
