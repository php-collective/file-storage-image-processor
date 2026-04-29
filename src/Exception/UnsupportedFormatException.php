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
 * Thrown when the processor cannot determine an output format for a
 * variant — for example when the source file has no extension, or
 * when an extension cannot be encoded by the active driver.
 */
class UnsupportedFormatException extends ImageProcessingException
{
    /**
     * @return self
     */
    public static function missingExtension(): self
    {
        return new self('Cannot encode image without a file extension');
    }

    /**
     * @param string $extension Extension string that has no encoder
     *
     * @return self
     */
    public static function withExtension(string $extension): self
    {
        return new self(sprintf('No encoder available for extension `%s`', $extension));
    }
}
