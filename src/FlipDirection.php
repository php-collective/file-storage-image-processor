<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image;

use Intervention\Image\Direction;
use InvalidArgumentException;

/**
 * Direction for the `flip` operation. Use this enum instead of magic
 * `'h'` / `'v'` strings:
 *
 *     $variant->flip(FlipDirection::Horizontal);
 *
 * Backed by the same single-character codes the package has used since
 * v1 so existing variant configs (`['direction' => 'h']`) continue to
 * resolve correctly.
 */
enum FlipDirection: string
{
    case Horizontal = 'h';
    case Vertical = 'v';

    /**
     * Resolves a direction code (e.g. from a config array) to the matching
     * enum case. Lower-cases and trims the input, and accepts both the
     * single-character codes (`h`, `v`) and the long forms
     * (`horizontal`, `vertical`).
     *
     * @param string $name Direction code or name
     *
     * @throws \InvalidArgumentException When the input does not match any case
     *
     * @return self
     */
    public static function fromName(string $name): self
    {
        $normalized = strtolower(trim($name));

        return match ($normalized) {
            'h', 'horizontal' => self::Horizontal,
            'v', 'vertical' => self::Vertical,
            default => throw new InvalidArgumentException(sprintf(
                'Unknown flip direction "%s". Expected one of: h, v, horizontal, vertical.',
                $name,
            )),
        };
    }

    /**
     * Returns the matching intervention/image `Direction` enum case so
     * callers don't have to import intervention's enum directly.
     *
     * @return \Intervention\Image\Direction
     */
    public function intervention(): Direction
    {
        return match ($this) {
            self::Horizontal => Direction::HORIZONTAL,
            self::Vertical => Direction::VERTICAL,
        };
    }
}
