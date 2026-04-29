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
 * Resize the image to a specific height while maintaining aspect ratio.
 */
final class Heighten implements Operation
{
    /**
     * @param int $height Target height in pixels; width follows the aspect ratio
     * @param bool $preventUpscale Don't upscale beyond the source height
     */
    public function __construct(
        public readonly int $height,
        public readonly bool $preventUpscale = false,
    ) {
    }

    public function name(): string
    {
        return 'heighten';
    }

    public function apply(OperationContext $context): void
    {
        if ($this->preventUpscale) {
            $context->image->scaleDown(null, $this->height);

            return;
        }

        $context->image->scale(null, $this->height);
    }

    public function toArray(): array
    {
        return [
            'height' => $this->height,
            'preventUpscale' => $this->preventUpscale,
        ];
    }
}
