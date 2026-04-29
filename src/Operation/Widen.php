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
 * Resize the image to a specific width while maintaining aspect ratio.
 */
final class Widen implements Operation
{
    /**
     * @param int $width Target width in pixels; height follows the aspect ratio
     * @param bool $preventUpscale Don't upscale beyond the source width
     */
    public function __construct(
        public readonly int $width,
        public readonly bool $preventUpscale = false,
    ) {
    }

    public function name(): string
    {
        return 'widen';
    }

    public function apply(OperationContext $context): void
    {
        if ($this->preventUpscale) {
            $context->image->scaleDown($this->width);

            return;
        }

        $context->image->scale($this->width);
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'preventUpscale' => $this->preventUpscale,
        ];
    }
}
