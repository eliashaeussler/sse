<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/sse".
 *
 * Copyright (C) 2023-2024 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\SSE\Stream;

use EliasHaeussler\SSE\Event;
use EliasHaeussler\SSE\Exception;
use Http\Discovery;
use Psr\Http\Message;

use function json_encode;
use function sprintf;
use function uniqid;

/**
 * Psr7EventStream.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class Psr7EventStream implements EventStream
{
    private ?Message\ResponseInterface $response = null;
    private bool $closed = false;

    /**
     * @param non-empty-string $id
     */
    private function __construct(
        private readonly string $id,
        private readonly int $retry,
        private readonly Message\ResponseFactoryInterface $responseFactory,
    ) {}

    /**
     * @param non-empty-string|null $id
     */
    public static function create(?string $id = null, int $retry = 50): self
    {
        $id ??= uniqid();
        $responseFactory = Discovery\Psr17FactoryDiscovery::findResponseFactory();

        return new self($id, $retry, $responseFactory);
    }

    public function open(): void
    {
        if ($this->isActive()) {
            throw new Exception\StreamIsActive();
        }

        $this->response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withHeader('X-Accel-Buffering', 'no')
        ;
    }

    public function close(string $eventName = 'done'): void
    {
        $this->sendMessage($eventName);
        $this->closed = true;
    }

    public function sendEvent(Event\Event $event): void
    {
        $this->sendMessage($event->getName(), json_encode($event, JSON_THROW_ON_ERROR));
    }

    public function sendMessage(string $name = 'message', bool|float|int|string|null $data = null): void
    {
        if ($this->closed) {
            throw new Exception\StreamIsClosed();
        }
        if (!$this->isActive()) {
            throw new Exception\StreamIsInactive();
        }

        // Send event data
        $this->sendStreamData('id', $this->id);
        $this->sendStreamData('event', $name);
        $this->sendStreamData('data', $data);
        $this->sendStreamData('retry', $this->retry);
        $this->sendDelimiter();
    }

    /**
     * @phpstan-assert-if-true !null $this->response
     */
    public function isActive(): bool
    {
        return null !== $this->response;
    }

    /**
     * @return non-empty-string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @throws Exception\StreamIsInactive
     */
    public function getResponse(): Message\ResponseInterface
    {
        if (!$this->isActive()) {
            throw new Exception\StreamIsInactive();
        }

        return $this->response;
    }

    public static function canHandle(Message\ServerRequestInterface $request): bool
    {
        return $request->getHeader('Accept') === [self::CONTENT_TYPE];
    }

    private function sendStreamData(string $name, bool|float|int|string|null $value): void
    {
        $this->addBodyLine(
            sprintf('%s: %s', $name, $value),
        );

        $this->sendDelimiter();
    }

    private function sendDelimiter(): void
    {
        $this->addBodyLine(PHP_EOL);
    }

    private function addBodyLine(string $line): void
    {
        $this->response?->getBody()->write($line);
    }
}
