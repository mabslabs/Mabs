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

 namespace Mabs\Dispatcher;

 use Closure;
 use Psr\Container\ContainerInterface;
 
 final class EventDispatcher implements EventDispatcherInterface
 {
     /** @var array<string, array<array{eventName: string, callback: callable, priority: int}>> */
     private array $listeners = [];
 
     public function __construct(
         private readonly ContainerInterface $container
     ) {}
 
     /**
      * Dispatch an event to all registered listeners
      *
      * @param string $eventName The event to dispatch
      * @param mixed $data Optional data to pass to the event listeners
      * @return $this
      */
     public function dispatch(string $eventName, mixed $data = null): self
     {
         foreach ($this->getListenersByEvent($eventName) as $event) {
             $event['callback']($this->container, $data);
         }
 
         return $this;
     }
 
     /**
      * Remove all listeners for a given event
      *
      * @param string $eventName The event name to clear
      * @return $this
      */
     final public function detach(string $eventName): self
     {
         unset($this->listeners[$eventName]);
 
         return $this;
     }
 
     /**
      * Register an event listener
      *
      * @param string $eventName The event to listen for
      * @param callable|array|string $callback The callback to execute
      * @param int $priority The priority (higher numbers execute first)
      * @return $this
      */
     final public function register(
         string $eventName,
         callable|array|string $callback,
         int $priority = 0
     ): self {
         $eventName = trim($eventName);
         $this->listeners[$eventName] ??= [];
 
         $this->listeners[$eventName][] = [
             'eventName' => $eventName,
             'callback' => $this->normalizeCallback($callback),
             'priority' => $priority
         ];
 
         if (count($this->listeners[$eventName]) > 1) {
             usort(
                 $this->listeners[$eventName],
                 fn(array $a, array $b): int => $b['priority'] <=> $a['priority']
             );
         }
 
         return $this;
     }
 
     /**
      * Get all registered listeners
      *
      * @return array<string, array<array{eventName: string, callback: callable, priority: int}>>
      */
     public function getListeners(): array
     {
         return $this->listeners;
     }
 
     /**
      * Get listeners for a specific event
      *
      * @param string $eventName The event name
      * @return array<array{eventName: string, callback: callable, priority: int}>
      */
     public function getListenersByEvent(string $eventName): array
     {
         return $this->listeners[$eventName] ?? [];
     }
 
     /**
      * Normalize different callback formats to a callable
      */
     private function normalizeCallback(callable|array|string $callback): callable
     {
         return match (true) {
             is_callable($callback) => $callback,
             is_string($callback) && str_contains($callback, '::') => explode('::', $callback),
             is_string($callback) => fn(ContainerInterface $c) => $c->get($callback),
             default => throw new \InvalidArgumentException('Invalid callback type')
         };
     }
 }