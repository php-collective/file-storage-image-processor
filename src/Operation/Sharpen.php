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
 * Sharpen the image. Amount is in the 0–100 range; intervention/image
 * applies an unsharp-mask-style filter at the requested intensity.
 */
final class Sharpen implements Operation
{
    /**
     * @param int $amount Sharpen intensity, 0..100
     */
    public function __construct(public readonly int $amount)
    {
    }

    public function name(): string
    {
        return 'sharpen';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->sharpen($this->amount);
    }

    public function toArray(): array
    {
        return ['amount' => $this->amount];
    }
}
