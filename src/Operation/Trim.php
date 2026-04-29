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
 * Strip uniform-coloured border areas from the image. Tolerance
 * controls how close to the corner colour the trim extends; 0 means
 * only exact matches are trimmed.
 */
final class Trim implements Operation
{
    /**
     * @param int $tolerance Colour tolerance, 0..100; 0 = exact match
     */
    public function __construct(public readonly int $tolerance = 0)
    {
    }

    public function name(): string
    {
        return 'trim';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->trim($this->tolerance);
    }

    public function toArray(): array
    {
        return ['tolerance' => $this->tolerance];
    }
}
