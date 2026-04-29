<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image\Exception;

use Throwable;

/**
 * Thrown when intervention/image fails to decode the source file
 * during `process()`. The most common causes are: a truncated upload,
 * a file with the wrong extension for its real format, or a format
 * the active driver cannot decode.
 */
class ImageCorruptedException extends ImageProcessingException
{
    /**
     * @param string $path Source file path that failed to decode
     * @param \Throwable $previous Underlying decode exception
     *
     * @return self
     */
    public static function withPath(string $path, Throwable $previous): self
    {
        return new self(
            sprintf('Failed to decode image at `%s`: %s', $path, $previous->getMessage()),
            0,
            $previous,
        );
    }
}
