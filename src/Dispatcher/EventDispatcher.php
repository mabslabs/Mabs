<?php
/**
 * Created in Moon.
 * User: Mohamed Aymen Ben Slimane <aymen.kernel@gmail.com>
 * Date: 12/07/15
 * Time: 01:20 ص
 */

namespace Moon\Dispatcher;


class EventDispatcher
{

    private $listeners = [];

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
        if (!$listeners) {
            return $this;
        }
        foreach ($listeners as $event) {
            call_user_func_array($event['callback'], [$this->container, $data]);
        }

        return $this;
    }

    /**
     *
     * @param string $eventName
     * @return EventDispatcher
     */
    public final function detach($eventName)
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
    public final function register($eventName, $callback, $priority)
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
     * @return bool | array
     */
    public function getListenersByEvent($eventName)
    {
        if (isset($this->listeners[$eventName])) {
            return $this->listeners[$eventName];
        }

        return false;
    }
}
