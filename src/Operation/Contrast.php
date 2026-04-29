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
 * Adjust image contrast. Level ranges from -100 (less) to 100 (more);
 * 0 leaves the image unchanged.
 */
final class Contrast implements Operation
{
    /**
     * @param int $level Contrast shift, -100..100
     */
    public function __construct(public readonly int $level)
    {
    }

    public function name(): string
    {
        return 'contrast';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->contrast($this->level);
    }

    public function toArray(): array
    {
        return ['level' => $this->level];
    }
}
