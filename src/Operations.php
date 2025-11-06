<?php

/**
 * Copyright (c) Florian Krämer (https://florian-kraemer.net)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Florian Krämer (https://florian-kraemer.net)
 * @author    Florian Krämer
 * @link      https://github.com/Phauthentic
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace PhpCollective\Infrastructure\Storage\Processor\Image;

use Intervention\Image\Interfaces\ImageInterface;
use InvalidArgumentException;
use PhpCollective\Infrastructure\Storage\Processor\Image\Exception\UnsupportedOperationException;

/**
 * Operations
 *
 * @link https://image.intervention.io/v3
 */
class Operations
{
    public const POSITION_CENTER = 'center';
    public const POSITION_TOP_CENTER = 'top-center';
    public const POSITION_BOTTOM_CENTER = 'bottom-center';
    public const POSITION_LEFT_TOP = 'left-top';
    public const POSITION_RIGHT_TOP = 'right-top';
    public const POSITION_LEFT_CENTER = 'left-center';
    public const POSITION_RIGHT_CENTER = 'right-center';
    public const POSITION_LEFT_BOTTOM = 'left-bottom';
    public const POSITION_RIGHT_BOTTOM = 'right-bottom';

    /**
     * @var \Intervention\Image\Interfaces\ImageInterface
     */
    protected ImageInterface $image;

    /**
     * @param \Intervention\Image\Interfaces\ImageInterface $image Image
     */
    public function __construct(ImageInterface $image)
    {
        $this->image = $image;
    }

    /**
     * @param string $name Name
     * @param array<string, mixed> $arguments Arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        throw UnsupportedOperationException::withName($name);
    }

    /**
     * Crops the image
     *
     * @param array<string, mixed> $arguments Arguments
     * @return void
     */
    public function cover(array $arguments): void
    {
        if (!isset($arguments['height'], $arguments['width'])) {
            throw new InvalidArgumentException('Missing width or height');
        }

        $arguments += ['position' => static::POSITION_CENTER];
        $preventUpscale = $arguments['preventUpscale'] ?? false;
        if ($preventUpscale) {
            $this->image->coverDown(
                (int)$arguments['width'],
                (int)$arguments['height'],
                $arguments['position']
            );

            return;
        }

        $this->image->cover(
            (int)$arguments['width'],
            (int)$arguments['height'],
            $arguments['position']
        );
    }

    /**
     * Crops the image
     *
     * @param array<string, mixed> $arguments Arguments
     * @return void
     */
    public function crop(array $arguments): void
    {
        if (!isset($arguments['height'], $arguments['width'])) {
            throw new InvalidArgumentException('Missing width or height');
        }

        $arguments += ['x' => null, 'y' => null, 'position' => static::POSITION_CENTER];
        $height = $arguments['height'] ? (int)$arguments['height'] : null;
        $width = $arguments['width'] ? (int)$arguments['width'] : null;
        $x = $arguments['x'] ? (int)$arguments['x'] : 0;
        $y = $arguments['y'] ? (int)$arguments['y'] : 0;

        $this->image->crop($width, $height, $x, $y, $arguments['position']);
    }

    /**
     * Flips the image horizontal
     *
     * @return void
     */
    public function flipHorizontal(): void
    {
        $this->flip(['direction' => 'h']);
    }

    /**
     * Flips the image vertical
     *
     * @return void
     */
    public function flipVertical(): void
    {
        $this->flip(['direction' => 'v']);
    }

    /**
     * Flips the image
     *
     * @param array<string, mixed> $arguments Arguments
     * @return void
     */
    public function flip(array $arguments): void
    {
        if (!isset($arguments['direction'])) {
            throw new InvalidArgumentException('Direction missing');
        }

        if ($arguments['direction'] !== 'v' && $arguments['direction'] !== 'h') {
            throw new InvalidArgumentException(
                'Invalid argument, you must provide h or v'
            );
        }

        if ($arguments['direction'] === 'h') {
            $this->image->flip();

            return;
        }

        $this->image->flop();
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return void
     */
    public function scale(array $arguments): void
    {
        if (!isset($arguments['height'], $arguments['width'])) {
            throw new InvalidArgumentException(
                'Missing height or width'
            );
        }

        $preventUpscale = $arguments['preventUpscale'] ?? false;

        if ($preventUpscale) {
            $this->image->scaleDown(
                $arguments['width'],
                $arguments['height']
            );

            return;
        }

        $this->image->scale(
            $arguments['width'],
            $arguments['height'],
        );
    }

    /**
     * Resizes the image
     *
     * @param array<string, mixed> $arguments Arguments
     * @return void
     */
    public function resize(array $arguments): void
    {
        if (!isset($arguments['height'], $arguments['width'])) {
            throw new InvalidArgumentException(
                'Missing height or width'
            );
        }

        // Deprecated: Coming from old API
        $aspectRatio = $arguments['aspectRatio'] ?? null;
        if ($aspectRatio !== null) {
            $this->scale($arguments);

            return;
        }

        $preventUpscale = $arguments['preventUpscale'] ?? false;

        if ($preventUpscale) {
            $this->image->resizeDown(
                $arguments['width'],
                $arguments['height']
            );

            return;
        }

        $this->image->resize(
            $arguments['width'],
            $arguments['height'],
        );
    }

    /**
     * @param array<string, mixed> $arguments Arguments
     * @return void
     */
    public function rotate(array $arguments): void
    {
        if (!isset($arguments['angle'])) {
            throw new InvalidArgumentException(
                'Missing angle'
            );
        }

        $this->image->rotate((int)$arguments['angle']);
    }

    /**
     * @return void
     */
    public function sharpen(array $arguments): void
    {
        if (!isset($arguments['amount'])) {
            throw new InvalidArgumentException(
                'Missing amount'
            );
        }

        $this->image->sharpen((int)$arguments['amount']);
    }

    /**
     * Allows the declaration of a callable that gets the image manager instance
     * and the arguments passed to it.
     *
     * @param array<string, mixed> $arguments Arguments
     * @return void
     */
    public function callback(array $arguments): void
    {
        if (!isset($arguments['callback'])) {
            throw new InvalidArgumentException(
                'Missing callback argument'
            );
        }

        if (!is_callable($arguments['callback'])) {
            throw new InvalidArgumentException(
                'Provided value for callback is not a callable'
            );
        }

        $arguments['callable']($this->image, $arguments);
    }
}
