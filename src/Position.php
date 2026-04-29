<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image;

use InvalidArgumentException;

/**
 * Alignment positions for crop, cover, resizeCanvas, padding and place
 * operations. Use this enum instead of magic position strings:
 *
 *     $variant->cover(150, 150, position: Position::TopCenter);
 *     $variant->place('logo.png', position: Position::BottomRight);
 *
 * The string backing values match intervention/image's `Alignment`
 * enum, so they pass through to the underlying library unchanged.
 */
enum Position: string
{
    case Center = 'center';
    case TopCenter = 'top-center';
    case BottomCenter = 'bottom-center';
    case LeftTop = 'left-top';
    case TopLeft = 'top-left';
    case RightTop = 'right-top';
    case TopRight = 'top-right';
    case LeftCenter = 'left-center';
    case RightCenter = 'right-center';
    case LeftBottom = 'left-bottom';
    case BottomLeft = 'bottom-left';
    case RightBottom = 'right-bottom';
    case BottomRight = 'bottom-right';

    /**
     * Resolves a position name (e.g. from a config array) to the matching
     * enum case. Lower-cases and trims the input so values like `'CENTER'`
     * or `' top-left '` resolve correctly.
     *
     * @param string $name Position name
     *
     * @throws \InvalidArgumentException When the name does not match any case
     *
     * @return self
     */
    public static function fromName(string $name): self
    {
        $case = self::tryFrom(strtolower(trim($name)));
        if ($case !== null) {
            return $case;
        }

        $valid = implode(', ', array_map(static fn (self $c): string => $c->value, self::cases()));

        throw new InvalidArgumentException(sprintf(
            'Unknown image position "%s". Expected one of: %s.',
            $name,
            $valid,
        ));
    }
}
