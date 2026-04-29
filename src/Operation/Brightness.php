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
 * Adjust image brightness. Level ranges from -100 (darker) to
 * 100 (brighter); 0 leaves the image unchanged.
 */
final class Brightness implements Operation
{
    /**
     * @param int $level Brightness shift, -100..100
     */
    public function __construct(public readonly int $level)
    {
    }

    public function name(): string
    {
        return 'brightness';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->brightness($this->level);
    }

    public function toArray(): array
    {
        return ['level' => $this->level];
    }
}
