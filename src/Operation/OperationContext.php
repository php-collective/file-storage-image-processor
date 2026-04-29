<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Infrastructure\Storage\Processor\Image\Operation;

use Intervention\Image\Interfaces\ImageInterface;

/**
 * Mutable per-variant scratchpad threaded through the operations
 * pipeline. Carries the image being modified plus any side-channel
 * state — currently just the requested output format from a
 * `Convert` operation.
 */
class OperationContext
{
    /**
     * The image being modified in-place. Operations call methods on it
     * (resize, crop, …) directly; intervention/image returns the image
     * itself from each call so chaining works, but the operations here
     * don't need to capture the return value.
     *
     * @var \Intervention\Image\Interfaces\ImageInterface
     */
    public ImageInterface $image;

    /**
     * Output format requested via the `Convert` operation. When non-null
     * the processor encodes the variant using this extension instead of
     * the source file's extension and adjusts the variant's stored
     * path accordingly.
     *
     * @var string|null
     */
    public ?string $outputFormat = null;

    /**
     * @param \Intervention\Image\Interfaces\ImageInterface $image Image
     */
    public function __construct(ImageInterface $image)
    {
        $this->image = $image;
    }
}
