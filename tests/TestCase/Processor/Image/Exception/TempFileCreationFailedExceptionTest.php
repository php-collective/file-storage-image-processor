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

namespace PhpCollective\Test\TestCase\Processor\Image\Exception;

use PhpCollective\Infrastructure\Storage\Processor\Image\Exception\TempFileCreationFailedException;
use PhpCollective\Test\TestCase\TestCase;

/**
 * TempFileCreationFailedExceptionTest
 */
class TempFileCreationFailedExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testException(): void
    {
        $exception = TempFileCreationFailedException::withFilename('/tmp/titus.jpg');
        $this->assertEquals(
            'Failed to create `/tmp/titus.jpg`',
            $exception->getMessage(),
        );
    }
}
