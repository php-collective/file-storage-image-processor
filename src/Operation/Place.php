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
 * Insert another image (typically a watermark or overlay) on top of
 * the current one. `image` accepts whatever
 * intervention/image's insert() does — usually a file path.
 */
final class Place implements Operation
{
    /**
     * @param string $image Path to the overlay image
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\Position $position Anchor for the overlay
     * @param int $x Horizontal pixel offset from the anchor
     * @param int $y Vertical pixel offset from the anchor
     * @param float $opacity Overlay opacity, 0.0..1.0
     */
    public function __construct(
        public readonly string $image,
        public readonly Position $position = Position::BottomCenter,
        public readonly int $x = 0,
        public readonly int $y = 0,
        public readonly float $opacity = 1.0,
    ) {
    }

    public function name(): string
    {
        return 'place';
    }

    public function apply(OperationContext $context): void
    {
        $context->image->insert(
            $this->image,
            $this->x,
            $this->y,
            $this->position->value,
            $this->opacity,
        );
    }

    public function toArray(): array
    {
        return [
            'image' => $this->image,
            'position' => $this->position->value,
            'x' => $this->x,
            'y' => $this->y,
            'opacity' => $this->opacity,
        ];
    }
}
