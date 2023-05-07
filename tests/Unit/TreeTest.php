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
        $this->expectExceptionMessage('The model class [App\Models\Unknown] could not be found.');

        $tree = new Tree(['models' => []]);
        $tree->modelForContext('Unknown', true);
    }
}
