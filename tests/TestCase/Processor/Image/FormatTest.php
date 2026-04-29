<?php declare(strict_types = 1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author Mark Scherer
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace PhpCollective\Test\TestCase\Processor\Image;

use InvalidArgumentException;
use PhpCollective\Infrastructure\Storage\Processor\Image\Format;
use PhpCollective\Test\TestCase\TestCase;

/**
 * FormatTest
 */
class FormatTest extends TestCase
{
    /**
     * @return void
     */
    public function testStringValuesMatchCanonicalExtensions(): void
    {
        $this->assertSame('jpeg', Format::Jpeg->value);
        $this->assertSame('webp', Format::Webp->value);
        $this->assertSame('avif', Format::Avif->value);
    }

    /**
     * @return void
     */
    public function testFromNameAcceptsCanonicalNames(): void
    {
        $this->assertSame(Format::Webp, Format::fromName('webp'));
        $this->assertSame(Format::Jpeg, Format::fromName('JPEG'));
        $this->assertSame(Format::Avif, Format::fromName('  avif  '));
    }

    /**
     * @return void
     */
    public function testFromNameAcceptsAliases(): void
    {
        $this->assertSame(Format::Jpeg, Format::fromName('jpg'));
        $this->assertSame(Format::Tiff, Format::fromName('tif'));
        $this->assertSame(Format::Pjpg, Format::fromName('pjpeg'));
        $this->assertSame(Format::Heic, Format::fromName('heif'));
        $this->assertSame(Format::Jp2, Format::fromName('j2k'));
        $this->assertSame(Format::Jp2, Format::fromName('jp2k'));
    }

    /**
     * @return void
     */
    public function testFromNameThrowsOnUnknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown image format "vips"');

        Format::fromName('vips');
    }
}
