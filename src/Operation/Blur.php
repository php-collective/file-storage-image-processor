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
 * Apply a gaussian-style blur. Level is in the 0..100 range; 5 is the
 * intervention default and a sensible mild blur.
 */
final class Blur implements Operation
{
    /**
     * @param int $level Blur amount, 0..100; 5 is the intervention default
     */
    public function __construct(public readonly int $level = 5)
    {
    }

    public function name(): string
    {
        return 'blur';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->blur($this->level);
    }

    public function toArray(): array
    {
        return ['level' => $this->level];
    }
}
