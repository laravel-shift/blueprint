<?php

namespace Tests\Feature\Translators;

use Blueprint\Column;
use Blueprint\Translators\Rules;
use Tests\TestCase;

class RulesTest extends TestCase
{
    /**
     * @test
     */
    public function forColumn_returns_required_rule_by_default()
    {
        $column = new Column('test', 'unknown');

        $this->assertEquals(['required'], Rules::fromColumn($column));
    }

    /**
     * @test
     * @dataProvider stringDataTypesProvider
     */
    public function forColumn_returns_string_rule_for_string_data_types($data_type)
    {
        $column = new Column('test', $data_type);

        $this->assertContains('string', Rules::fromColumn($column));
    }

    /**
     * @test
     */
    public function forColumn_returns_max_rule_for_string_attributes()
    {
        $column = new Column('test', 'string', [], [1000]);

        $this->assertContains('max:1000', Rules::fromColumn($column));

        $column = new Column('test', 'char', [], [10]);

        $this->assertContains('max:10', Rules::fromColumn($column));
    }

    /**
     * @test
     * @dataProvider stringDataTypesProvider
     */
    public function forColumn_overrides_string_rule_with_email_rule_for_attributes_named_email_or_email_address($data_type)
    {
        $column = new Column('email', $data_type);

        $this->assertContains('email', Rules::fromColumn($column));
        $this->assertNotContains('string', Rules::fromColumn($column));

        $column = new Column('email_address', $data_type);

        $this->assertContains('email', Rules::fromColumn($column));
        $this->assertNotContains('string', Rules::fromColumn($column));
    }

    /**
     * @test
     * @dataProvider stringDataTypesProvider
     */
    public function forColumn_overrides_string_rule_with_password_rule_for_attributes_named_password($data_type)
    {
        $column = new Column('password', $data_type);

        $this->assertContains('password', Rules::fromColumn($column));
        $this->assertNotContains('string', Rules::fromColumn($column));
    }

    /**
     * @test
     * @dataProvider numericDataTypesProvider
     */
    public function forColumn_returns_numeric_rule_for_numeric_types($data_type)
    {
        $column = new Column('test', $data_type);

        $this->assertContains('numeric', Rules::fromColumn($column));
    }

    /**
     * @test
     * @dataProvider integerDataTypesProvider
     */
    public function forColumn_returns_integer_rule_for_integer_types($data_type)
    {
        $column = new Column('test', $data_type);

        $this->assertContains('integer', Rules::fromColumn($column));
    }

    /**
     * @test
     */
    public function forColumn_returns_gt0_rule_for_unsigned_numeric_types()
    {
        $column = new Column('test', 'integer');

        $this->assertNotContains('gt:0', Rules::fromColumn($column));

        $column = new Column('test', 'unsignedInteger');

        $this->assertContains('gt:0', Rules::fromColumn($column));
    }

    /**
     * @test
     */
    public function forColumn_returns_in_rule_for_enums_and_sets()
    {
        $column = new Column('test', 'enum', [], ['alpha', 'bravo', 'charlie']);
        $this->assertContains('in:alpha,bravo,charlie', Rules::fromColumn($column));

        $column = new Column('test', 'set', [], [2,4,6]);

        $this->assertContains('in:2,4,6', Rules::fromColumn($column));
    }

    /**
     * @test
     * @dataProvider dateDataTypesProvider
     */
    public function forColumn_returns_date_rule_for_date_types($data_type)
    {
        $column = new Column('test', $data_type);

        $this->assertContains('date', Rules::fromColumn($column));
    }

    /**
     * @test
     * @dataProvider stringDataTypesProvider
     */
    public function forColumn_does_not_return_unique_rule_for_the_unique_modifier_without_context($data_type)
    {
        $column = new Column('test', $data_type, ['unique', 'nullable']);

        $this->assertNotContains('unique:', Rules::fromColumn($column));
    }

    /**
     * @test
     */
    public function forColumn_returns_unique_rule_for_the_unique_modifier()
    {
        $column = new Column('test', 'string', ['unique'], [100]);

        $actual = Rules::fromColumn($column, 'connection.table');

        $this->assertContains('unique:connection.table', $actual);
        $this->assertContains('max:100', $actual);
    }

    public function stringDataTypesProvider()
    {
        return [
            ['string'],
            ['char'],
            ['text']
        ];
    }

    public function integerDataTypesProvider()
    {
        return [
            ['integer'],
            ['tinyInteger'],
            ['smallInteger'],
            ['mediumInteger'],
            ['bigInteger'],
            ['unsignedBigInteger'],
            ['unsignedInteger'],
            ['unsignedMediumInteger'],
            ['unsignedSmallInteger'],
            ['unsignedTinyInteger'],
            ['increments'],
            ['tinyIncrements'],
            ['smallIncrements'],
            ['mediumIncrements'],
            ['bigIncrements'],
        ];
    }

    public function numericDataTypesProvider()
    {
        return [
            ['decimal'],
            ['double'],
            ['float'],
            ['unsignedDecimal'],
        ];
    }

    public function dateDataTypesProvider()
    {
        return [
            ['date'],
            ['datetime'],
            ['datetimetz'],
        ];
    }
}
