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

namespace EliasHaeussler\SSE\Tests\Exception;

use EliasHaeussler\SSE as Src;
use PHPUnit\Framework;

/**
 * StreamWasNotOpenedTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Exception\StreamIsInactive::class)]
final class StreamIsInactiveTest extends Framework\TestCase
{
    #[Framework\Attributes\Test]
    public function constructorReturnsException(): void
    {
        $actual = new Src\Exception\StreamIsInactive();

        self::assertSame('No active stream detected. Open a new event stream first.', $actual->getMessage());
        self::assertSame(1686816243, $actual->getCode());
    }
}
