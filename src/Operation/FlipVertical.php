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
 * Mirror the image vertically.
 */
final class FlipVertical implements Operation
{
    public function name(): string
    {
        return 'flipVertical';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->flip(Direction::VERTICAL);
    }

    public function toArray(): array
    {
        return [];
    }
}
