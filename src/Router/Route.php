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

namespace Mabs\Router;

final class Route
{
    private string $path;
    private mixed $handler;
    private ?string $name = null;

    public function __construct(string $path, mixed $handler)
    {
        $this->path = $path;
        $this->handler = $handler;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function name(): string
    {
        return $this->name ??= spl_object_hash($this);
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function handler(): mixed
    {
        return $this->handler;
    }

    public function withHandler(mixed $handler): self
    {
        $this->handler = $handler;
        return $this;
    }

    public function toRegex(): string
    {
        return preg_replace(
            ['/\{(\w+)\?\}/', '/\{(\w+)\}/'],
            ['(\w*)', '(\w+)'],
            $this->path
        ) ?? $this->path;
    }

    public function extractParameters(array $matches): array
    {
        preg_match_all('/\{(\w+)\??\}/', $this->path, $paramNames);

        return array_combine(
            $paramNames[1],
            array_slice($matches, 1, count($paramNames[1])) + array_fill(0, count($paramNames[1]), null)
        ) ?: [];
    }
}
