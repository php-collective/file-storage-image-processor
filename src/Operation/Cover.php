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
 * Zoom-crop to exact dimensions while preserving aspect ratio. The
 * resulting image is exactly width × height; the source image is
 * scaled to cover the area and the overflow is cropped at the given
 * alignment.
 */
final class Cover implements Operation
{
    /**
     * @param int $width Target width in pixels
     * @param int $height Target height in pixels
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position $position Where to crop the overflow from
     * @param bool $preventUpscale Don't upscale beyond the source dimensions
     */
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly Position $position = Position::Center,
        public readonly bool $preventUpscale = false,
    ) {
    }

    public function name(): string
    {
        return 'cover';
    }

    public function apply(OperationContext $context): void
    {
        if ($this->preventUpscale) {
            $context->image->coverDown($this->width, $this->height, $this->position->value);

            return;
        }

        $context->image->cover($this->width, $this->height, $this->position->value);
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'position' => $this->position->value,
            'preventUpscale' => $this->preventUpscale,
        ];
    }
}
