<?php

namespace Tests\Feature\Lexers;

use Blueprint\Lexers\ModelLexer;
use Tests\TestCase;

class ModelLexerTest extends TestCase
{
    /**
     * @var ModelLexer
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ModelLexer();
    }

    /**
     * @test
     */
    public function it_returns_nothing_without_models_token()
    {
        $this->assertEquals([
            'models' => [],
            'cache' => [],
        ], $this->subject->analyze([]));
    }

    /**
     * @test
     */
    public function it_returns_models()
    {
        $tokens = [
            'models' => [
                'ModelOne' => [
                    'id' => 'id',
                    'name' => 'string nullable',
                ],
                'ModelTwo' => [
                    'count' => 'integer',
                    'timestamps' => 'timestamps',
                ],
                'ModelThree' => [
                    'id' => 'increments',
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(3, $actual['models']);

        $model = $actual['models']['ModelOne'];
        $this->assertEquals('ModelOne', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(2, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->modifiers());
        $this->assertEquals('name', $columns['name']->name());
        $this->assertEquals('string', $columns['name']->dataType());
        $this->assertEquals(['nullable'], $columns['name']->modifiers());

        $model = $actual['models']['ModelTwo'];
        $this->assertEquals('ModelTwo', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(2, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->modifiers());
        $this->assertEquals('count', $columns['count']->name());
        $this->assertEquals('integer', $columns['count']->dataType());
        $this->assertEquals([], $columns['count']->modifiers());

        $model = $actual['models']['ModelThree'];
        $this->assertEquals('ModelThree', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(1, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('increments', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->modifiers());
    }

    /**
     * @test
     */
    public function it_defaults_the_id_column()
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'title' => 'string nullable',
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Model'];
        $this->assertEquals('Model', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(2, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->attributes());
        $this->assertEquals([], $columns['id']->modifiers());
        $this->assertEquals('title', $columns['title']->name());
        $this->assertEquals('string', $columns['title']->dataType());
        $this->assertEquals([], $columns['title']->attributes());
        $this->assertEquals(['nullable'], $columns['title']->modifiers());
    }

    /**
     * @test
     */
    public function it_disables_the_id_column()
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'id' => false,
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Model'];

        $this->assertEquals('Model', $model->name());
        $this->assertCount(0, $model->columns());
        $this->assertFalse($model->usesPrimaryKey());
    }

    /**
     * @test
     */
    public function it_disables_timestamps()
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'timestamps' => false,
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Model'];
        $this->assertEquals('Model', $model->name());
        $this->assertFalse($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());
    }

    /**
     * @test
     */
    public function it_defaults_to_string_datatype()
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'title' => 'nullable',
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Model'];
        $this->assertEquals('Model', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(2, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->attributes());
        $this->assertEquals([], $columns['id']->modifiers());
        $this->assertEquals('title', $columns['title']->name());
        $this->assertEquals('string', $columns['title']->dataType());
        $this->assertEquals([], $columns['title']->attributes());
        $this->assertEquals(['nullable'], $columns['title']->modifiers());
    }

    /**
     * @test
     */
    public function it_accepts_lowercase_keywords()
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'sequence' => 'unsignedbiginteger autoincrement',
                    'content' => 'longtext',
                    'saved_at' => 'timestamptz usecurrent',
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Model'];
        $this->assertEquals('Model', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(4, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->attributes());
        $this->assertEquals([], $columns['id']->modifiers());
        $this->assertEquals('sequence', $columns['sequence']->name());
        $this->assertEquals('unsignedBigInteger', $columns['sequence']->dataType());
        $this->assertEquals([], $columns['sequence']->attributes());
        $this->assertEquals(['autoIncrement'], $columns['sequence']->modifiers());
        $this->assertEquals('content', $columns['content']->name());
        $this->assertEquals('longText', $columns['content']->dataType());
        $this->assertEquals([], $columns['content']->attributes());
        $this->assertEquals([], $columns['content']->modifiers());
        $this->assertEquals('saved_at', $columns['saved_at']->name());
        $this->assertEquals('timestampTz', $columns['saved_at']->dataType());
        $this->assertEquals([], $columns['saved_at']->attributes());
        $this->assertEquals(['useCurrent'], $columns['saved_at']->modifiers());
    }

    /**
     * @test
     * @dataProvider dataTypeAttributesDataProvider
     */
    public function it_handles_data_type_attributes($definition, $data_type, $attributes)
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'column' => $definition,
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Model'];
        $this->assertEquals('Model', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(2, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->modifiers());
        $this->assertEquals('column', $columns['column']->name());
        $this->assertEquals($data_type, $columns['column']->dataType());
        $this->assertEquals($attributes, $columns['column']->attributes());
        $this->assertEquals([], $columns['column']->modifiers());
    }

    /**
     * @test
     * @dataProvider modifierAttributesProvider
     */
    public function it_handles_modifier_attributes($definition, $modifier, $attributes)
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'column' => $definition.' nullable',
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Model'];
        $this->assertEquals('Model', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(2, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->modifiers());
        $this->assertEquals('column', $columns['column']->name());
        $this->assertEquals('string', $columns['column']->dataType());
        $this->assertEquals([], $columns['column']->attributes());
        $this->assertEquals([[$modifier => $attributes], 'nullable'], $columns['column']->modifiers());
    }

    /**
     * @test
     */
    public function it_handles_attributes_and_modifiers_with_attributes()
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'column' => 'string:100 unique charset:utf8',
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens)['models']['Model']->columns()['column'];

        $this->assertEquals('column', $actual->name());
        $this->assertEquals('string', $actual->dataType());
        $this->assertEquals(['unique', ['charset' => 'utf8']], $actual->modifiers());
        $this->assertEquals(['100'], $actual->attributes());
    }

    /**
     * @test
     */
    public function it_enables_soft_deletes()
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'softdeletes' => 'softdeletes',
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Model'];
        $this->assertEquals('Model', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertTrue($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(1, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->modifiers());
    }

    /**
     * @test
     */
    public function it_converts_foreign_shorthand_to_id()
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'post_id' => 'foreign',
                    'author_id' => 'foreign:user',
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Model'];
        $this->assertEquals('Model', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(3, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->modifiers());
        $this->assertEquals('post_id', $columns['post_id']->name());
        $this->assertEquals('id', $columns['post_id']->dataType());
        $this->assertEquals(['foreign'], $columns['post_id']->modifiers());
        $this->assertEquals('author_id', $columns['author_id']->name());
        $this->assertEquals('id', $columns['author_id']->dataType());
        $this->assertEquals([['foreign' => 'user']], $columns['author_id']->modifiers());
    }

    /**
     * @test
     */
    public function it_sets_belongs_to_with_foreign_attributes()
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'post_id' => 'id foreign',
                    'author_id' => 'id foreign:users',
                    'uid' => 'id:user foreign:users.id',
                    'cntry_id' => 'foreign:countries',
                    'ccid' => 'foreign:countries.code',
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Model'];
        $this->assertEquals('Model', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(6, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->attributes());
        $this->assertEquals([], $columns['id']->modifiers());

        $this->assertEquals('post_id', $columns['post_id']->name());
        $this->assertEquals('id', $columns['post_id']->dataType());
        $this->assertEquals([], $columns['post_id']->attributes());
        $this->assertEquals(['foreign'], $columns['post_id']->modifiers());

        $this->assertEquals('author_id', $columns['author_id']->name());
        $this->assertEquals('id', $columns['author_id']->dataType());
        $this->assertEquals([], $columns['author_id']->attributes());
        $this->assertEquals([['foreign' => 'users']], $columns['author_id']->modifiers());

        $this->assertEquals('uid', $columns['uid']->name());
        $this->assertEquals('id', $columns['uid']->dataType());
        $this->assertEquals(['user'], $columns['uid']->attributes());
        $this->assertEquals([['foreign' => 'users.id']], $columns['uid']->modifiers());

        $this->assertEquals('cntry_id', $columns['cntry_id']->name());
        $this->assertEquals('id', $columns['cntry_id']->dataType());
        $this->assertEquals([], $columns['cntry_id']->attributes());
        $this->assertEquals([['foreign' => 'countries']], $columns['cntry_id']->modifiers());

        $this->assertEquals('ccid', $columns['ccid']->name());
        $this->assertEquals('id', $columns['ccid']->dataType());
        $this->assertEquals([], $columns['ccid']->attributes());
        $this->assertEquals([['foreign' => 'countries.code']], $columns['ccid']->modifiers());

        $relationships = $model->relationships();
        $this->assertCount(1, $relationships);
        $this->assertEquals([
            'post_id',
            'user:author_id',
            'user:uid',
            'country:cntry_id',
            'country.code:ccid',
        ], $relationships['belongsTo']);
    }

    /**
     * @test
     */
    public function it_returns_traced_models()
    {
        $tokens = [
            'models' => [
                'NewModel' => [
                    'id' => 'id',
                    'name' => 'string nullable',
                ],
            ],
            'cache' => [
                'CachedModelOne' => [
                    'count' => 'integer',
                    'timestamps' => 'timestamps',
                ],
                'CachedModelTwo' => [
                    'id' => 'id',
                    'name' => 'string nullable',
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['NewModel'];
        $this->assertEquals('NewModel', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(2, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->modifiers());
        $this->assertEquals('name', $columns['name']->name());
        $this->assertEquals('string', $columns['name']->dataType());
        $this->assertEquals(['nullable'], $columns['name']->modifiers());

        $this->assertIsArray($actual['cache']);
        $this->assertCount(2, $actual['cache']);

        $model = $actual['cache']['CachedModelOne'];
        $this->assertEquals('CachedModelOne', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(2, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->modifiers());
        $this->assertEquals('count', $columns['count']->name());
        $this->assertEquals('integer', $columns['count']->dataType());
        $this->assertEquals([], $columns['count']->modifiers());

        $model = $actual['cache']['CachedModelTwo'];
        $this->assertEquals('CachedModelTwo', $model->name());
        $this->assertTrue($model->usesTimestamps());
        $this->assertFalse($model->usesSoftDeletes());

        $columns = $model->columns();
        $this->assertCount(2, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->modifiers());
        $this->assertEquals('name', $columns['name']->name());
        $this->assertEquals('string', $columns['name']->dataType());
        $this->assertEquals(['nullable'], $columns['name']->modifiers());
    }

    /**
     * @test
     */
    public function it_stores_relationships()
    {
        $tokens = [
            'models' => [
                'Subscription' => [
                    'different_id' => 'id:user',
                    'title' => 'string',
                    'price' => 'float',
                    'relationships' => [
                        'belongsToMany' => 'Team',
                        'hasmany' => 'Order',
                        'hasOne' => 'Duration, Transaction:tid',
                    ],
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Subscription'];
        $this->assertEquals('Subscription', $model->name());

        $columns = $model->columns();
        $this->assertCount(4, $columns);
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('different_id', $columns);
        $this->assertArrayHasKey('title', $columns);
        $this->assertArrayHasKey('price', $columns);

        $relationships = $model->relationships();
        $this->assertCount(4, $relationships);
        $this->assertEquals(['user:different_id'], $relationships['belongsTo']);
        $this->assertEquals(['Order'], $relationships['hasMany']);
        $this->assertEquals(['Team'], $relationships['belongsToMany']);
        $this->assertEquals(['Duration', 'Transaction:tid'], $relationships['hasOne']);
    }

    /**
     * @test
     */
    public function it_enables_morphable_and_set_its_reference()
    {
        $tokens = [
            'models' => [
                'Model' => [
                    'relationships' => [
                        'morphTo' => 'Morphable',
                    ]
                ],
            ],
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertIsArray($actual['models']);
        $this->assertCount(1, $actual['models']);

        $model = $actual['models']['Model'];
        $this->assertEquals('Model', $model->name());
        $this->assertEquals('Morphable', $model->morphTo());
        $this->assertTrue($model->usesTimestamps());

        $columns = $model->columns();
        $this->assertCount(1, $columns);
        $this->assertEquals('id', $columns['id']->name());
        $this->assertEquals('id', $columns['id']->dataType());
        $this->assertEquals([], $columns['id']->modifiers());

        $relationships = $model->relationships();
        $this->assertCount(1, $relationships);
        $this->assertEquals(['Morphable'], $relationships['morphTo']);
    }

    public function dataTypeAttributesDataProvider()
    {
        return [
            ['unsignedDecimal:10,2', 'unsignedDecimal', [10, 2]],
            ['decimal:8,3', 'decimal', [8, 3]],
            ['double:1,4', 'double', [1, 4]],
            ['float:2,10', 'float', [2, 10]],
            ['char:10', 'char', [10]],
            ['string:1000', 'string', [1000]],
            ['enum:one,two,three,four', 'enum', ['one', 'two', 'three', 'four']],
            ['enum:"Jason McCreary",Shift,O\'Doul', 'enum', ['Jason McCreary', 'Shift', 'O\'Doul']],
            ['set:1,2,3,4', 'set', [1, 2, 3, 4]],
        ];
    }

    public function modifierAttributesProvider()
    {
        return [
            ['default:5', 'default', 5],
            ['default:0.00', 'default', 0.00],
            ['default:0', 'default', 0],
            ['default:string', 'default', 'string'],
            ["default:'empty'", 'default', "'empty'"],
            ['default:""', 'default', '""'],
            ['charset:utf8', 'charset', 'utf8'],
            ['collation:utf8_unicode', 'collation', 'utf8_unicode'],
            ['default:"space between"', 'default', '"space between"'],
        ];
    }
}
