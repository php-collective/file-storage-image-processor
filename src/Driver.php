<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image;

use Intervention\Image\Drivers\Gd\Driver as InterventionGdDriver;
use Intervention\Image\Drivers\Imagick\Driver as InterventionImagickDriver;
use Intervention\Image\Interfaces\DriverInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Identifies which intervention/image driver `ImageProcessor::create()`
 * should resolve. Use this enum instead of magic strings:
 *
 *     ImageProcessor::create(Driver::Imagick, $storage, $pathBuilder);
 *     ImageProcessor::create(Driver::Auto, $storage, $pathBuilder);
 */
enum Driver: string
{
    /**
     * Imagick when `ext-imagick` is loaded, otherwise GD. Raises a
     * `RuntimeException` when neither extension is available.
     */
    case Auto = 'auto';

    /**
     * The PHP-built-in GD extension. Smaller install footprint, no ICC
     * profile or color-space support.
     */
    case Gd = 'gd';

    /**
     * The Imagick extension. Wider format support, ICC color profiles,
     * higher quality output.
     */
    case Imagick = 'imagick';

    /**
     * Resolves a driver name (e.g. from a config array) to the matching
     * enum case. Lower-cases and trims the input so values like `'GD'`
     * or `' imagick '` resolve correctly. Throws when the input does
     * not match any known case.
     *
     * @param string $name Driver name (`'gd'`, `'imagick'`, `'auto'`)
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
            'Unknown image driver "%s". Expected one of: %s.',
            $name,
            $valid,
        ));
    }

    /**
     * Resolves this enum case to a concrete intervention/image driver
     * instance. Hidden behind the enum so callers never need to import
     * intervention's driver classes directly.
     *
     * @return \Intervention\Image\Interfaces\DriverInterface
     */
    public function build(): DriverInterface
    {
        return match ($this) {
            self::Gd => new InterventionGdDriver(),
            self::Imagick => new InterventionImagickDriver(),
            self::Auto => self::autoDetect(),
        };
    }

    /**
     * @throws \RuntimeException
     *
     * @return \Intervention\Image\Interfaces\DriverInterface
     */
    private static function autoDetect(): DriverInterface
    {
        if (extension_loaded('imagick')) {
            return new InterventionImagickDriver();
        }
        if (extension_loaded('gd')) {
            return new InterventionGdDriver();
        }

        throw new RuntimeException(
            'Neither the Imagick nor the GD PHP extension is available; '
            . 'install one or pass a configured ImageManager to the constructor instead.',
        );
    }
}
