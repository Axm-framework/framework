<?php

declare(strict_types=1);

namespace Session;

/**
 * Class Session
 *
 * Represents a session management class with features for handling flash messages,
 * session initialization, manipulation, and expiration monitoring.
 *
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @package System
 */
interface SessionHandlerInterface
{
    public function open(string $savePath, string $sessionName): bool;
    public function write(string $sessionId, string $sessionData): bool;
    public function read(string $sessionId): string;
    public function gc(int $maxLifeTime): int;
    public function close(): bool;
    public function destroy(string $sessionId): bool;
}
