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

namespace EliasHaeussler\SSE\Tests\Stream;

use EliasHaeussler\SSE as Src;
use Nyholm\Psr7;
use PHPUnit\Framework;

use function implode;

/**
 * Psr7EventStreamTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Stream\Psr7EventStream::class)]
final class Psr7EventStreamTest extends Framework\TestCase
{
    private Src\Stream\Psr7EventStream $subject;

    protected function setUp(): void
    {
        $this->subject = Src\Stream\Psr7EventStream::create('foo', 100);
    }

    #[Framework\Attributes\Test]
    public function openThrowsExceptionIfStreamIsAlreadyActive(): void
    {
        $this->subject->open();

        $this->expectExceptionObject(new Src\Exception\StreamIsActive());

        $this->subject->open();
    }

    #[Framework\Attributes\Test]
    public function openCreatesResponseWithHeaders(): void
    {
        $this->subject->open();

        $expected = [
            'Content-Type' => [Src\Stream\EventStream::CONTENT_TYPE],
            'Cache-Control' => ['no-cache'],
            'Connection' => ['keep-alive'],
            'X-Accel-Buffering' => ['no'],
        ];

        self::assertSame($expected, $this->subject->getResponse()->getHeaders());
    }

    #[Framework\Attributes\Test]
    public function closeThrowsExceptionIfStreamIsClosed(): void
    {
        $this->subject->open();
        $this->subject->close();

        $this->expectExceptionObject(new Src\Exception\StreamIsClosed());

        $this->subject->close();
    }

    #[Framework\Attributes\Test]
    public function closeThrowsExceptionIfStreamIsInactive(): void
    {
        $this->expectExceptionObject(new Src\Exception\StreamIsInactive());

        $this->subject->close();
    }

    #[Framework\Attributes\Test]
    public function sendEventThrowsExceptionIfStreamIsClosed(): void
    {
        $this->subject->open();
        $this->subject->close();

        $this->expectExceptionObject(new Src\Exception\StreamIsClosed());

        $this->subject->sendEvent(new Src\Tests\Fixtures\FakeEvent());
    }

    #[Framework\Attributes\Test]
    public function sendEventThrowsExceptionIfStreamIsInactive(): void
    {
        $this->expectExceptionObject(new Src\Exception\StreamIsInactive());

        $this->subject->sendEvent(new Src\Tests\Fixtures\FakeEvent());
    }

    #[Framework\Attributes\Test]
    public function sendEventWritesEventDataToResponseBody(): void
    {
        $this->subject->open();
        $this->subject->sendEvent(new Src\Tests\Fixtures\FakeEvent(['foo' => 'baz']));

        $expected = [
            'id: foo',
            'event: fake',
            'data: {"foo":"baz"}',
            'retry: 100',
            PHP_EOL,
        ];

        self::assertSame(
            implode(PHP_EOL, $expected),
            (string) $this->subject->getResponse()->getBody(),
        );
    }

    #[Framework\Attributes\Test]
    public function sendMessageThrowsExceptionIfStreamIsClosed(): void
    {
        $this->subject->open();
        $this->subject->close();

        $this->expectExceptionObject(new Src\Exception\StreamIsClosed());

        $this->subject->sendMessage();
    }

    #[Framework\Attributes\Test]
    public function sendMessageThrowsExceptionIfStreamIsInactive(): void
    {
        $this->expectExceptionObject(new Src\Exception\StreamIsInactive());

        $this->subject->sendMessage();
    }

    #[Framework\Attributes\Test]
    public function sendMessageEmitsStreamData(): void
    {
        $this->subject->open();
        $this->subject->sendMessage('foo', 'baz');

        $expected = [
            'id: foo',
            'event: foo',
            'data: baz',
            'retry: 100',
            PHP_EOL,
        ];

        self::assertSame(
            implode(PHP_EOL, $expected),
            (string) $this->subject->getResponse()->getBody(),
        );
    }

    #[Framework\Attributes\Test]
    public function isActiveReturnsTrueIfStreamIsActive(): void
    {
        self::assertFalse($this->subject->isActive());

        $this->subject->open();

        self::assertTrue($this->subject->isActive());
    }

    #[Framework\Attributes\Test]
    public function getIdReturnsId(): void
    {
        self::assertSame('foo', $this->subject->getId());
    }

    #[Framework\Attributes\Test]
    public function getResponseThrowsExceptionIfStreamIsNotActive(): void
    {
        $this->expectExceptionObject(new Src\Exception\StreamIsInactive());

        $this->subject->getResponse();
    }

    #[Framework\Attributes\Test]
    public function canHandleReturnsTrueIfRequestAcceptsRequiredContentType(): void
    {
        $request = new Psr7\ServerRequest('GET', 'https://www.example.com');
        $supportedRequest = $request->withHeader('Accept', 'text/event-stream');
        $unsupportedRequest = $request->withHeader('Accept', 'text/html');

        self::assertTrue(Src\Stream\Psr7EventStream::canHandle($supportedRequest));
        self::assertFalse(Src\Stream\Psr7EventStream::canHandle($unsupportedRequest));
    }
}
