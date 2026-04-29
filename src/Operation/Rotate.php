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
 * Rotate the image by the given angle in degrees.
 */
final class Rotate implements Operation
{
    /**
     * @param int $angle Rotation angle in degrees, clockwise
     */
    public function __construct(public readonly int $angle)
    {
    }

    public function name(): string
    {
        return 'rotate';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->rotate($this->angle);
    }

    public function toArray(): array
    {
        return ['angle' => $this->angle];
    }
}
