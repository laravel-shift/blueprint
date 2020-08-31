<?php

namespace Tests\Feature\Translators;

use Tests\TestCase;
use Blueprint\Models\Column;
use Blueprint\Translators\Rules;

/**
 * @see Rules
 */
class RulesTest extends TestCase
{
    /**
     * @test
     */
    public function forColumn_returns_required_rule_by_default()
    {
        $column = new Column('test', 'unknown');

        $this->assertEquals(['required'], Rules::fromColumn('context', $column));
    }

    /**
     * @test
     * @dataProvider stringDataTypesProvider
     */
    public function forColumn_returns_string_rule_for_string_data_types($data_type)
    {
        $column = new Column('test', $data_type);

        $this->assertContains('string', Rules::fromColumn('context', $column));
    }

    /**
     * @test
     */
    public function forColumn_returns_max_rule_for_string_attributes()
    {
        $column = new Column('test', 'string', [], [1000]);

        $this->assertContains('max:1000', Rules::fromColumn('context', $column));

        $column = new Column('test', 'char', [], [10]);

        $this->assertContains('max:10', Rules::fromColumn('context', $column));
    }

    /**
     * @test
     * @dataProvider stringDataTypesProvider
     */
    public function forColumn_uses_email_rule_for_columns_named_email_or_email_address($data_type)
    {
        $column = new Column('email', $data_type);

        $this->assertContains('email', Rules::fromColumn('context', $column));
        $this->assertNotContains('string', Rules::fromColumn('context', $column));

        $column = new Column('email_address', $data_type);

        $this->assertContains('email', Rules::fromColumn('context', $column));
        $this->assertNotContains('string', Rules::fromColumn('context', $column));
    }

    /**
     * @test
     * @dataProvider stringDataTypesProvider
     */
    public function forColumn_uses_password_rule_for_columns_named_password($data_type)
    {
        $column = new Column('password', $data_type);

        $this->assertContains('password', Rules::fromColumn('context', $column));
        $this->assertNotContains('string', Rules::fromColumn('context', $column));
    }

    /**
     * @test
     * @dataProvider numericDataTypesProvider
     */
    public function forColumn_returns_numeric_rule_for_numeric_types($data_type)
    {
        $column = new Column('test', $data_type);

        $this->assertContains('numeric', Rules::fromColumn('context', $column));
    }

    /**
     * @test
     * @dataProvider integerDataTypesProvider
     */
    public function forColumn_returns_integer_rule_for_integer_types($data_type)
    {
        $column = new Column('test', $data_type);
        $this->assertContains('integer', Rules::fromColumn('context', $column));
    }

    /**
     * @test
     */
    public function forColumn_return_exists_rule_for_id_column()
    {
        $column = new Column('user_id', 'id');

        $this->assertContains('exists:users,id', Rules::fromColumn('context', $column));
    }

    /**
     * @test
     */
    public function forColumn_return_exists_rule_id_column_with_attribute()
    {
        $column = new Column('author_id', 'id', [], ['user']);

        $this->assertContains('exists:users,id', Rules::fromColumn('context', $column));
    }

    /**
     * @test
     */
    public function forColumn_return_exists_rule_for_the_unique_modifier()
    {
        $column = new Column('column', 'string', ['unique']);

        $this->assertContains('unique:connection.table,column', Rules::fromColumn('connection.table', $column));
    }

    /**
     * @test
     * @dataProvider relationshipColumnProvider
     */
    public function forColumn_returns_exists_rule_for_foreign_keys($name, $table)
    {
        $column = new Column($name, 'id');

        $actual = Rules::fromColumn('context', $column);

        $this->assertContains('integer', $actual);
        $this->assertContains("exists:{$table},id", $actual);
    }

    /**
     * @test
     */
    public function forColumn_returns_gt0_rule_for_unsigned_numeric_types()
    {
        $column = new Column('test', 'integer');

        $this->assertNotContains('gt:0', Rules::fromColumn('context', $column));

        $column = new Column('test', 'unsignedInteger');

        $this->assertContains('gt:0', Rules::fromColumn('context', $column));
    }

    /**
     * @test
     */
    public function forColumn_returns_in_rule_for_enums_and_sets()
    {
        $column = new Column('test', 'enum', [], ['alpha', 'bravo', 'charlie']);
        $this->assertContains('in:alpha,bravo,charlie', Rules::fromColumn('context', $column));

        $column = new Column('test', 'set', [], [2, 4, 6]);

        $this->assertContains('in:2,4,6', Rules::fromColumn('context', $column));
    }

    /**
     * @test
     * @dataProvider dateDataTypesProvider
     */
    public function forColumn_returns_date_rule_for_date_types($data_type)
    {
        $column = new Column('test', $data_type);

        $this->assertContains('date', Rules::fromColumn('context', $column));
    }

    /**
     * @test
     */
    public function forColumn_return_json_rule_for_the_json_type()
    {
        $column = new Column('column', 'json');

        $this->assertContains('json', Rules::fromColumn('context', $column));
    }

    /**
    * @test
    */
    public function forColumn_does_not_return_between_rule_for_decimal_without_precion_and_scale()
    {
        $column = new Column('column', "decimal");

        $this->assertNotContains("between", Rules::fromColumn('context', $column));
    }

    /**
    * @test
    */
    public function forColumn_does_not_return_between_rule_for_unsigned_decimal_without_precion_and_scale()
    {
        $unsignedBeforeDecimalColumn = new Column('column', "unsigned decimal");

        $this->assertNotContains("between", Rules::fromColumn('context', $unsignedBeforeDecimalColumn));

        $unsignedAfterDecimalColumn = new Column('column', "decimal unsigned");

        $this->assertNotContains("between", Rules::fromColumn('context', $unsignedAfterDecimalColumn));
    }

    /**
    * @test
     * @dataProvider noBetweenRuleDataProvider
    */
    public function forColumn_does_not_return_between_rule_for_double_without_precion_and_scale($column)
    {
        $this->assertNotContains("between", Rules::fromColumn('context', $column));
    }

    /**
     * @test
     * @dataProvider noBetweenRuleDataProvider
     */
    public function forColumn_does_not_return_between_rule($column)
    {
        $this->assertNotContains("between", Rules::fromColumn('context', $column));
    }

    /**
    * @test
    * @dataProvider betweenRuleDataProvider
    */
    public function forColumn_returns_between_rule($column, $interval)
    {
        $fromColumn = Rules::fromColumn('context', $column);
        $this->assertContains("between:$interval", $fromColumn);
    }

    public function stringDataTypesProvider()
    {
        return [
            ['string'],
            ['char'],
            ['text'],
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

    public function relationshipColumnProvider()
    {
        return [
            ['test_id', 'tests'],
            ['user_id', 'users'],
            ['sheep_id', 'sheep'],
        ];
    }

    public function noBetweenRuleDataProvider()
    {
        return [
            [new Column('column', 'float')],
            [new Column('column', 'double')],
            [new Column('column', 'decimal')],
            [new Column('column', 'unsignedDecimal')],
            [new Column('column', 'float', ['unsigned'])],
            [new Column('column', 'double', ['unsigned'])],
            [new Column('column', 'decimal', ['unsigned'])],
        ];
    }

    public function betweenRuleDataProvider()
    {
        return [
            [new Column('column', 'double', [], [8, 0]), '-99999999,99999999'],
            [new Column('column', 'double', [], [10, 1]), '-999999999.9,999999999.9'],
            [new Column('column', 'double', [], [10, 2]), '-99999999.99,99999999.99'],
            [new Column('column', 'decimal', [], [10, 3]), '-9999999.999,9999999.999'],
            [new Column('column', 'decimal', [], [10, 4]), '-999999.9999,999999.9999'],
            [new Column('column', 'decimal', [], [10, 5]), '-99999.99999,99999.99999'],
            [new Column('column', 'float', [], [10, 6]), '-9999.999999,9999.999999'],
            [new Column('column', 'float', [], [10, 7]), '-999.9999999,999.9999999'],
            [new Column('column', 'float', [], [10, 8]), '-99.99999999,99.99999999'],
            [new Column('column', 'double', [], [4, 4]), '-0.9999,0.9999'],
            [new Column('column', 'unsignedDecimal', [], [10, 0]), '0,9999999999'],
            [new Column('column', 'unsignedDecimal', [], [8, 1]), '0,9999999.9'],
            [new Column('column', 'unsignedDecimal', [], [6, 2]), '0,9999.99'],
            [new Column('column', 'decimal', ['unsigned'], [10, 3]), '0,9999999.999'],
            [new Column('column', 'double', ['unsigned'], [10, 4]), '0,999999.9999'],
            [new Column('column', 'float', ['unsigned'], [10, 5]), '0,99999.99999'],
        ];
    }
}
