<?php

declare(strict_types=1);

namespace CuyZ\Valinor\Tests\Unit\Mapper\Tree\Builder;

use AssertionError;
use CuyZ\Valinor\Mapper\Tree\Builder\RootNodeBuilder;
use CuyZ\Valinor\Mapper\Tree\Builder\ArrayNodeBuilder;
use CuyZ\Valinor\Mapper\Tree\Exception\InvalidTraversableKey;
use CuyZ\Valinor\Mapper\Tree\Exception\SourceMustBeIterable;
use CuyZ\Valinor\Mapper\Tree\Shell;
use CuyZ\Valinor\Tests\Fake\Type\FakeType;
use CuyZ\Valinor\Type\Types\ArrayKeyType;
use CuyZ\Valinor\Type\Types\ArrayType;
use CuyZ\Valinor\Type\Types\NativeStringType;
use PHPUnit\Framework\TestCase;

final class ArrayNodeBuilderTest extends TestCase
{
    public function test_build_with_null_value_returns_empty_branch_node(): void
    {
        $node = (new RootNodeBuilder(new ArrayNodeBuilder()))->build(Shell::root(ArrayType::native(), null));

        self::assertSame([], $node->value());
        self::assertEmpty($node->children());
    }

    public function test_invalid_type_fails_assertion(): void
    {
        $this->expectException(AssertionError::class);

        (new RootNodeBuilder(new ArrayNodeBuilder()))->build(Shell::root(new FakeType(), []));
    }

    public function test_build_with_invalid_source_throws_exception(): void
    {
        $this->expectException(SourceMustBeIterable::class);
        $this->expectExceptionCode(1618739163);
        $this->expectExceptionMessage('Source must be iterable in order to be cast to `array`, but is of type `string`.');

        (new RootNodeBuilder(new ArrayNodeBuilder()))->build(Shell::root(ArrayType::native(), 'foo'));
    }

    public function test_invalid_source_key_throws_exception(): void
    {
        $this->expectException(InvalidTraversableKey::class);
        $this->expectExceptionCode(1630946163);
        $this->expectExceptionMessage('Invalid key `foo`, it must be of type `int`.');

        $type = new ArrayType(ArrayKeyType::integer(), NativeStringType::get());
        $value = [
            'foo' => 'key is not ok',
        ];

        (new RootNodeBuilder(new ArrayNodeBuilder()))->build(Shell::root($type, $value));
    }
}
