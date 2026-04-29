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
 * Tint the image by shifting the red, green and blue channels. Each
 * value is in the -100..100 range. 0 across the board leaves the
 * image unchanged.
 */
final class Colorize implements Operation
{
    /**
     * @param int $red Red channel shift, -100..100
     * @param int $green Green channel shift, -100..100
     * @param int $blue Blue channel shift, -100..100
     */
    public function __construct(
        public readonly int $red = 0,
        public readonly int $green = 0,
        public readonly int $blue = 0,
    ) {
    }

    public function name(): string
    {
        return 'colorize';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->colorize($this->red, $this->green, $this->blue);
    }

    public function toArray(): array
    {
        return [
            'red' => $this->red,
            'green' => $this->green,
            'blue' => $this->blue,
        ];
    }
}
