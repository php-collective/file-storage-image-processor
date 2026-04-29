<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image\Operation;

/**
 * Re-encode the variant in a different format than the source. Sets
 * the output format on the context; the processor reads it back and
 * adjusts both the encoder extension and the variant's stored path.
 */
final class Convert implements Operation
{
    /**
     * @param string $format Target file extension, e.g. 'webp'
     */
    public function __construct(public readonly string $format)
    {
    }

    public function name(): string
    {
        return 'convert';
    }

    public function apply(OperationContext $context): void
    {
        $context->outputFormat = strtolower($this->format);
    }

    public function toArray(): array
    {
        return ['format' => $this->format];
    }
}
