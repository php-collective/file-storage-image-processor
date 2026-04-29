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
 * Resize the image to exact width and height. Aspect ratio is **not**
 * preserved — use {@see Scale} when you want a constrained fit.
 */
final class Resize implements Operation
{
    /**
     * @param int $width Target width in pixels
     * @param int $height Target height in pixels
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
        return 'resize';
    }

    public function apply(OperationContext $context): void
    {
        if ($this->preventUpscale) {
            $context->image->resizeDown($this->width, $this->height);

            return;
        }

        $context->image->resize($this->width, $this->height);
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
