<?php
/**
 * Mabs framework
 *
 * @author      Mohamed Aymen Ben Slimane <aymen.kernel@gmail.com>
 * @copyright   2015 Mohamed Aymen Ben Slimane
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Mohamed Aymen Ben Slimane
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

declare(strict_types=1);

namespace Mabs\Container;

use Closure;
use ArrayAccess;
use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface, ArrayAccess
{
    private bool $isLocked = false;

    /** @var array<string, mixed> */
    private array $entries = [];

    /**
     * @param array<string, mixed> $services Initial services.
     */
    public function __construct(array $services = [])
    {
        foreach ($services as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Retrieve a service by ID.
     *
     * @param string $id
     * @return mixed
     * @throws InvalidArgumentException if service is not defined.
     */
    public function get(string $id): mixed
    {
        if (!array_key_exists($id, $this->entries)) {
            throw new InvalidArgumentException("Service \"$id\" not found.");
        }

        $value = $this->entries[$id];

        if ($value instanceof Closure) {
            $this->entries[$id] = $value = $value($this);
        }

        return $value;
    }

    /**
     * Check if a service exists.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries);
    }

    /**
     * Store a service in the container.
     *
     * @param string $id
     * @param mixed $value
     * @return void
     * @throws LogicException if the container is locked.
     */
    public function set(string $id, mixed $value): void
    {
        if ($this->isLocked && array_key_exists($id, $this->entries)) {
            throw new LogicException("Cannot modify locked container entry \"$id\".");
        }

        $this->entries[$id] = $value;
    }

    /**
     * Remove a service from the container.
     *
     * @param string $id
     * @return void
     * @throws LogicException if the container is locked.
     */
    public function unset(string $id): void
    {
        if ($this->isLocked) {
            throw new LogicException("Cannot remove from a locked container.");
        }

        unset($this->entries[$id]);
    }

    /**
     * Locks the container, preventing further modifications.
     */
    public function lock(): void
    {
        $this->isLocked = true;
    }

    /**
     * Check if the container is locked.
     */
    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    /**
     * Create a new container with optional services and locking.
     */
    public static function create(array $services = [], bool $lock = false): self
    {
        $container = new self($services);
        if ($lock) {
            $container->lock();
        }
        return $container;
    }

    /**
     * Clone the container with new services.
     *
     * @param array<string, mixed> $services
     * @return self
     */
    public function with(array $services): self
    {
        $clone = clone $this;
        $clone->isLocked = false;
        $clone->entries = [...$this->entries];

        foreach ($services as $key => $value) {
            $clone->set($key, $value);
        }

        return $clone;
    }

    // --- ArrayAccess Implementation ---

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string) $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->unset((string) $offset);
    }
}
