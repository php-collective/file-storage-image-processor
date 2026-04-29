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
 * Convert the image to grayscale.
 */
final class Grayscale implements Operation
{
    public function name(): string
    {
        return 'grayscale';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->grayscale();
    }

    public function toArray(): array
    {
        return [];
    }
}
