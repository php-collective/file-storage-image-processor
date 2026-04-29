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
 * Scale the image to fit within the given dimensions while preserving
 * aspect ratio.
 */
final class Scale implements Operation
{
    /**
     * @param int $width Maximum width in pixels
     * @param int $height Maximum height in pixels
     * @param bool $preventUpscale Don't upscale beyond the source dimensions
     */
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly bool $preventUpscale = false,
    ) {
    }

    public function name(): string
    {
        return 'scale';
    }

    public function apply(OperationContext $context): void
    {
        if ($this->preventUpscale) {
            $context->image->scaleDown($this->width, $this->height);

            return;
        }

        $context->image->scale($this->width, $this->height);
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'preventUpscale' => $this->preventUpscale,
        ];
    }
}
