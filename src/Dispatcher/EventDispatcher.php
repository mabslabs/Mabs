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

namespace Mabs\Dispatcher;


class EventDispatcher
{

    private $listeners = array();

    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }
    /**
     * @param string $eventName
     * @param mixed $data
     * @return EventDispatcher
     */
    public function dispatch($eventName, $data = null)
    {
        $listeners = $this->getListenersByEvent($eventName);
        if (empty($listeners)) {
            return $this;
        }
        foreach ($listeners as $event) {
            call_user_func_array($event['callback'], array($this->container, $data));
        }

        return $this;
    }

    /**
     *
     * @param string $eventName
     * @return EventDispatcher
     */
    final public function detach($eventName)
    {
        if (isset($this->listeners[$eventName])) {
            unset($this->listeners[$eventName]);
        }

        return $this;
    }

    /**
     *
     * @param string $eventName
     * @param mixed $callback
     * @param int $priority
     * @return EventDispatcher
     */
    final public function register($eventName, $callback, $priority)
    {
        $eventName = trim($eventName);

        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = array();
        }

        $event = array(
            'eventName' => $eventName,
            'callback' => $callback,
            'priority' => (int)$priority
        );

        array_push($this->listeners[$eventName], $event);

        if (count($this->listeners[$eventName]) > 1) {
            usort($this->listeners[$eventName], function ($a, $b)
                {
                    if ($a['priority'] == $b['priority']) {
                        return 0;
                    }

                    return ($a['priority'] < $b['priority']) ? -1 : 1;
                }
            );
        }

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * @param $eventName
     * @return array
     */
    public function getListenersByEvent($eventName)
    {
        if (isset($this->listeners[$eventName])) {
            return $this->listeners[$eventName];
        }

        return array();
    }
}
