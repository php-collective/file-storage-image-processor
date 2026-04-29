<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image\Operation;

use PhpCollective\Infrastructure\Storage\Processor\Image\Position;

/**
 * Cut a rectangular area out of the image. When `x` and `y` are both
 * provided the crop is positioned at those exact pixel coordinates;
 * otherwise the crop is positioned by the alignment instead.
 */
final class Crop implements Operation
{
    /**
     * @param int $width Crop width in pixels
     * @param int $height Crop height in pixels
     * @param int|null $x Horizontal pixel offset; ignored unless `$y` is also set
     * @param int|null $y Vertical pixel offset; ignored unless `$x` is also set
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position $position Alignment when no x/y given
     */
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly ?int $x = null,
        public readonly ?int $y = null,
        public readonly Position $position = Position::Center,
    ) {
    }

    public function name(): string
    {
        return 'crop';
    }

    public function apply(OperationContext $context): void
    {
        if ($this->x !== null && $this->y !== null) {
            $context->image->crop(
                $this->width,
                $this->height,
                $this->x,
                $this->y,
                alignment: $this->position->value,
            );

            return;
        }

        $context->image->crop(
            $this->width,
            $this->height,
            alignment: $this->position->value,
        );
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'x' => $this->x,
            'y' => $this->y,
            'position' => $this->position->value,
        ];
    }
}
