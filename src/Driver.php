<?php declare(strict_types = 1);

/**
 * Copyright (c) Florian Krämer (https://florian-kraemer.net)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Florian Krämer (https://florian-kraemer.net)
 * @author Florian Krämer
 * @link https://github.com/Phauthentic
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image;

use Intervention\Image\Drivers\Gd\Driver as InterventionGdDriver;
use Intervention\Image\Drivers\Imagick\Driver as InterventionImagickDriver;
use Intervention\Image\Interfaces\DriverInterface;
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
