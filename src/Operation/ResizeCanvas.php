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
 * Resize the working canvas without resampling the source image. New
 * pixel area introduced by growing the canvas is filled with the
 * given background colour.
 */
final class ResizeCanvas implements Operation
{
    /**
     * @param int $width New canvas width in pixels
     * @param int $height New canvas height in pixels
     * @param string|null $background Background colour for new pixel area
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position $position Where the original sits within the new canvas
     */
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly ?string $background = null,
        public readonly Position $position = Position::Center,
    ) {
    }

    public function name(): string
    {
        return 'resizeCanvas';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->resizeCanvas(
            $this->width,
            $this->height,
            $this->background,
            $this->position->value,
        );
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'background' => $this->background,
            'position' => $this->position->value,
        ];
    }
}
