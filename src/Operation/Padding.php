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
 * Add padding around the image by extending the canvas relative to
 * the current dimensions. `amount` adds N pixels to each side; pass
 * explicit `width` / `height` for asymmetric padding (each value is
 * the *total* added across the matching axis).
 */
final class Padding implements Operation
{
    /**
     * @param int|null $amount Pixels added on each side (used when neither width nor height is given)
     * @param int|null $width Total horizontal padding (overrides $amount)
     * @param int|null $height Total vertical padding (overrides $amount)
     * @param string|null $background Background colour for the new area
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position $position Alignment of the original image
     */
    public function __construct(
        public readonly ?int $amount = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?string $background = null,
        public readonly Position $position = Position::Center,
    ) {
    }

    public function name(): string
    {
        return 'padding';
    }

    public function apply(OperationContext $context): void
    {
        $width = $this->width ?? ($this->amount !== null ? $this->amount * 2 : 0);
        $height = $this->height ?? ($this->amount !== null ? $this->amount * 2 : 0);

        $context->image->resizeCanvasRelative(
            $width,
            $height,
            $this->background,
            $this->position->value,
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'width' => $this->width,
            'height' => $this->height,
            'background' => $this->background,
            'position' => $this->position->value,
        ];
    }
}
