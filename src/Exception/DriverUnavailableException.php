<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image\Exception;

/**
 * Thrown when `Driver::Auto` is requested but neither the Imagick nor
 * the GD PHP extension is available on the host. Install one of them
 * or pass an explicit `Driver::Gd` / `Driver::Imagick` (which then
 * fails with the underlying intervention/image error if the chosen
 * extension is missing).
 */
class DriverUnavailableException extends ImageProcessingException
{
    /**
     * @return self
     */
    public static function autoDetectFailed(): self
    {
        return new self(
            'Neither the Imagick nor the GD PHP extension is available; '
            . 'install one or pass a configured ImageManager to the constructor instead.',
        );
    }
}
