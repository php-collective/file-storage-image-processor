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
 * Auto-rotate the image based on EXIF orientation. Best placed first
 * in a pipeline so subsequent operations work on the visually correct
 * orientation rather than the raw sensor orientation.
 */
final class Orient implements Operation
{
    public function name(): string
    {
        return 'orient';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->orient();
    }

    public function toArray(): array
    {
        return [];
    }
}
