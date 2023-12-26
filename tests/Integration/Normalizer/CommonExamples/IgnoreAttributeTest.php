<?php

declare(strict_types=1);

namespace CuyZ\Valinor\Tests\Integration\Normalizer\CommonExamples;

use Attribute;
use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\Normalizer\Format;
use PHPUnit\Framework\TestCase;

final class IgnoreAttributeTest extends TestCase
{
    public function test_ignore_attribute_works_properly(): void
    {
        $result = (new MapperBuilder())
            ->registerTransformer(
                fn (object $value, callable $next) => array_filter(
                    $next(),
                    fn (mixed $value) => ! $value instanceof IgnoredValue,
                ),
            )
            ->registerTransformer(Ignore::class)
            ->normalizer(Format::array())
            ->normalize(new class () {
                public function __construct(
                    public string $userName = 'John Doe',
                    #[Ignore]
                    public string $password = 'p4$$w0rd',
                ) {}
            });

        self::assertSame([
            'userName' => 'John Doe',
        ], $result);
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Ignore
{
    public function normalize(mixed $value): IgnoredValue
    {
        return new IgnoredValue($value);
    }
}

final class IgnoredValue
{
    public function __construct(public mixed $value) {}
}
