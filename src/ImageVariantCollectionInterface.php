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

use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * @extends \IteratorAggregate<string, \PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariant>
 */
interface ImageVariantCollectionInterface extends JsonSerializable, IteratorAggregate, Countable
{
    /**
     * Gets a manipulation from the collection
     *
     * @param string $name
     *
     * @return \PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariant
     */
    public function get(string $name): ImageVariant;

    /**
     * @param \PhpCollective\Infrastructure\Storage\Processor\Image\ImageVariant $variant Variant
     *
     * @return void
     */
    public function add(ImageVariant $variant): void;

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool;
}
