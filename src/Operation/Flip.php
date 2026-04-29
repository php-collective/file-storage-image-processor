<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image\Operation;

use PhpCollective\Infrastructure\Storage\Processor\Image\FlipDirection;

/**
 * Mirror the image along the given direction.
 */
final class Flip implements Operation
{
    /**
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\FlipDirection $direction Direction to mirror along
     */
    public function __construct(public readonly FlipDirection $direction)
    {
    }

    public function name(): string
    {
        return 'flip';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->flip($this->direction->intervention());
    }

    public function toArray(): array
    {
        return ['direction' => $this->direction->value];
    }
}
