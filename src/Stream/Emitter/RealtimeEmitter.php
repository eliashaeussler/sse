<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/sse".
 *
 * Copyright (C) 2023-2026 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\SSE\Stream\Emitter;

use function flush;
use function header;
use function headers_sent;
use function ob_flush;
use function ob_get_level;

/**
 * RealtimeEmitter.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class RealtimeEmitter implements Emitter
{
    public function header(string $name, bool|float|int|string|null $value): void
    {
        header($name.': '.$value);
    }

    /**
     * @codeCoverageIgnore
     */
    public function wereHeadersSent(): bool
    {
        return headers_sent();
    }

    public function bodyLine(string $line): void
    {
        echo $line;
    }

    public function flush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
