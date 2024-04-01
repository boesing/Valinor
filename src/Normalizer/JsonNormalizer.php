<?php

declare(strict_types=1);

namespace CuyZ\Valinor\Normalizer;

use CuyZ\Valinor\Normalizer\Formatter\JsonFormatter;
use CuyZ\Valinor\Normalizer\Transformer\RecursiveTransformer;
use RuntimeException;

use function fclose;
use function fopen;
use function get_debug_type;
use function is_resource;
use function stream_get_contents;

use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_INVALID_UTF8_IGNORE;
use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_NUMERIC_CHECK;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_LINE_TERMINATORS;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * @api
 *
 * @implements Normalizer<string>
 */
final class JsonNormalizer implements Normalizer
{
    private const ACCEPTABLE_JSON_OPTIONS = JSON_HEX_QUOT
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_INVALID_UTF8_IGNORE
    | JSON_INVALID_UTF8_SUBSTITUTE
    | JSON_NUMERIC_CHECK
    | JSON_PRESERVE_ZERO_FRACTION
    | JSON_UNESCAPED_LINE_TERMINATORS
    | JSON_UNESCAPED_SLASHES
    | JSON_UNESCAPED_UNICODE
    | JSON_THROW_ON_ERROR;

    public function __construct(
        private RecursiveTransformer $transformer,
        public readonly int $jsonEncodingOptions = JSON_THROW_ON_ERROR,
    ) {
        assert(
            ($this->jsonEncodingOptions & JSON_THROW_ON_ERROR) === JSON_THROW_ON_ERROR,
            'JSON encoding options always have to contain JSON_THROW_ON_ERROR.',
        );
    }

    public function withOptions(int $options): self
    {
        /**
         * Internal note
         * -------------
         *
         * We could use the `int-mask-of<JsonNormalizer::JSON_*>` annotation
         * to let PHPStan infer the type of the accepted options, but we found
         * some caveats:
         * - SA tools are not able to infer that we end up having only accepted
         *   options. Might be fixed with https://github.com/phpstan/phpstan/issues/9384
         *   for PHPStan but Psalm does have some (not all) issues as well.
         * - Using this annotation provokes *severe* performance issues when
         *   running PHPStan analysis, therefore it is preferable to avoid it.
         */
        $acceptedOptions = (self::ACCEPTABLE_JSON_OPTIONS & $options) | JSON_THROW_ON_ERROR;

        return new self($this->transformer, $acceptedOptions);
    }

    public function normalize(mixed $value): string
    {
        $value = $this->transformer->transform($value);

        /** @var resource $resource */
        $resource = fopen('php://memory', 'w');

        (new JsonFormatter($resource, $this->jsonEncodingOptions))->format($value);

        rewind($resource);

        /** @var string */
        $json = stream_get_contents($resource);

        fclose($resource);

        return $json;
    }

    /**
     * Returns a new normalizer that will write the JSON to the given resource
     * instead of returning a string.
     *
     * A benefit of streaming the data to a PHP resource is that it may be more
     * memory-efficient when using generators — for instance when querying a
     * database:
     *
     * ```php
     * // In this example, we assume that the result of the query below is a
     * // generator, every entry will be yielded one by one, instead of
     * // everything being loaded in memory at once.
     * $users = $database->execute('SELECT * FROM users');
     *
     * $file = fopen('path/to/some_file.json', 'w');
     *
     * $normalizer = (new \CuyZ\Valinor\MapperBuilder())
     *     ->normalizer(\CuyZ\Valinor\Normalizer\Format::json())
     *     ->streamTo($file);
     *
     * // Even if there are thousands of users, memory usage will be kept low
     * // when writing JSON into the file.
     * $normalizer->normalize($users);
     * ```
     *
     * @param resource $resource
     */
    public function streamTo(mixed $resource): StreamNormalizer
    {
        // This check is there to help people that do not use static analyzers.
        // @phpstan-ignore-next-line
        if (! is_resource($resource)) {
            throw new RuntimeException('Expected a valid resource, got ' . get_debug_type($resource));
        }

        return new StreamNormalizer($this->transformer, new JsonFormatter($resource, $this->jsonEncodingOptions));
    }
}
