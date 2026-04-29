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
 * Image output formats supported by the processor — the format an
 * encoder produces, as opposed to a single file extension. Use this
 * enum instead of magic strings on `convert()` and quality maps:
 *
 *     $variant->convert(Format::Webp);
 *     $processor->setQuality([Format::Webp->value => 80, Format::Jpeg->value => 90]);
 *
 * Backed by canonical file extensions (`webp`, `jpeg`, `avif`, …)
 * so existing config-driven setups (`'convert' => ['format' => 'webp']`)
 * resolve correctly.
 */
enum Format: string
{
    case Jpeg = 'jpeg';
    case Png = 'png';
    case Gif = 'gif';
    case Webp = 'webp';
    case Avif = 'avif';
    case Heic = 'heic';
    case Tiff = 'tiff';
    case Bmp = 'bmp';
    case Jp2 = 'jp2';
    case Pjpg = 'pjpg';

    /**
     * Resolves an extension or format name to the matching enum case.
     * Lower-cases and trims the input. Recognises common aliases:
     * `jpg` → `Jpeg`, `tif` → `Tiff`, `pjpeg` → `Pjpg`, `heif` → `Heic`,
     * `j2k` → `Jp2`. Throws when the input does not match any case.
     *
     * @param string $name Extension or format name
     *
     * @throws \InvalidArgumentException When the name does not match any case
     *
     * @return self
     */
    public static function fromName(string $name): self
    {
        $normalized = strtolower(trim($name));

        $aliases = [
            'jpg' => self::Jpeg,
            'tif' => self::Tiff,
            'pjpeg' => self::Pjpg,
            'heif' => self::Heic,
            'j2k' => self::Jp2,
            'jp2k' => self::Jp2,
        ];
        if (isset($aliases[$normalized])) {
            return $aliases[$normalized];
        }

        $case = self::tryFrom($normalized);
        if ($case !== null) {
            return $case;
        }

        $valid = implode(', ', array_map(static fn (self $c): string => $c->value, self::cases()));

        throw new InvalidArgumentException(sprintf(
            'Unknown image format "%s". Expected one of: %s.',
            $name,
            $valid,
        ));
    }
}
