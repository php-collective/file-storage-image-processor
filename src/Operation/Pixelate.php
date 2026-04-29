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
 * Pixelate the image with the given block size in pixels. Useful for
 * privacy-redaction effects.
 */
final class Pixelate implements Operation
{
    /**
     * @param int $size Pixel block size in pixels
     */
    public function __construct(public readonly int $size)
    {
    }

    public function name(): string
    {
        return 'pixelate';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->pixelate($this->size);
    }

    public function toArray(): array
    {
        return ['size' => $this->size];
    }
}
