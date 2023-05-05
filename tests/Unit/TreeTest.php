<?php

namespace Tests\Unit;

use Blueprint\Tree;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(\Blueprint\Tree::class)]
final class TreeTest extends TestCase
{
    #[Test]
    public function it_throws_when_a_referenced_model_cannot_be_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The [App\Models\Unknown] model class could not be found or autoloaded. Please ensure that the model class name is correctly spelled, adheres to the appropriate namespace, and that the file containing the class is properly located within the "app/Models" directory or another relevant directory as configured.');

        $tree = new Tree(['models' => []]);
        $tree->modelForContext('Unknown', true);
    }
}
