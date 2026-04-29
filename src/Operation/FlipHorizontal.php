<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image\Operation;

use Intervention\Image\Direction;

/**
 * Mirror the image horizontally.
 */
final class FlipHorizontal implements Operation
{
    public function name(): string
    {
        return 'flipHorizontal';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->flip(Direction::HORIZONTAL);
    }

    public function toArray(): array
    {
        return [];
    }
}
