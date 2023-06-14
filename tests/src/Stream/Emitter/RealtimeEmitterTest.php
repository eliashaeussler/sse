<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/sse".
 *
 * Copyright (C) 2023 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\SSE\Tests\Stream\Emitter;

use EliasHaeussler\SSE as Src;
use PHPUnit\Framework;

use function function_exists;
use function ob_end_clean;
use function ob_flush;
use function ob_get_clean;
use function ob_start;
use function xdebug_get_headers;

/**
 * RealtimeEmitterTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class RealtimeEmitterTest extends Framework\TestCase
{
    private Src\Stream\Emitter\RealtimeEmitter $subject;

    protected function setUp(): void
    {
        $this->subject = new Src\Stream\Emitter\RealtimeEmitter();
    }

    #[Framework\Attributes\Test]
    public function headerWritesHeaderLineToOutput(): void
    {
        if (!function_exists('xdebug_get_headers')) {
            self::markTestSkipped('Test requires xdebug to be enabled.');
        }

        $this->subject->header('foo', 'baz');

        self::assertSame(['foo: baz'], xdebug_get_headers());
    }

    #[Framework\Attributes\Test]
    public function bodyLineWritesLineToOutput(): void
    {
        ob_flush();
        ob_start();

        $this->subject->bodyLine('foo');

        self::assertSame('foo', ob_get_clean());
    }

    #[Framework\Attributes\Test]
    public function flushFlushesOutputBuffer(): void
    {
        $flushed = false;

        ob_flush();
        ob_start(function (string $buffer, int $phase) use (&$flushed) {
            // Register ob_flush() calls
            if (PHP_OUTPUT_HANDLER_FLUSH === ($phase & PHP_OUTPUT_HANDLER_FLUSH)) {
                $flushed = true;
            }

            return '';
        });

        $this->subject->bodyLine('foo');

        $this->subject->flush();

        self::assertTrue($flushed);

        ob_end_clean();
    }
}
