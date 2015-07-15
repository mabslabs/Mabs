<?php
/**
 * Created in Moon.
 * User: Mohamed Aymen Ben Slimane <aymen.kernel@gmail.com>
 * Date: 10/07/15
 * Time: 06:56 م
 */

namespace Moon\Container;


class Container implements \ArrayAccess
{

    private $isLocked = false;

    protected $bag = [];

    public function __construct($values = array())
    {
        foreach ($values as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * @param mixed $offset
     * An offset to check for.
     *
     * @return boolean true on success or false on failure.
     */
    public function offsetExists($offset)
    {
        return isset($this->bag[$offset]);
    }

    /**
     * @param mixed $offset
     * The offset to retrieve.
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if (!isset($this->bag[$offset])) {
            throw new \InvalidArgumentException($offset.' not registred in container.');
        }
        $value = $this->bag[$offset];
        if ($value instanceof \Closure) {
            $this->bag[$offset] = $value($this);
        }

        return $this->bag[$offset];
    }

    /**
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * @param mixed $value
     * The value to set.
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if ($this->isLocked && isset($this->bag[$offset])) {
            throw new \LogicException('Cannot edit locked container '.$offset);
        }
        $this->bag[$offset] = $value;
    }

    /**
     * Offset to unset
     * @param mixed $offset
     * The offset to unset.
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        if ($this->isLocked) {
            throw new \LogicException('Cannot edit locked container');
        }
        if ($this->offsetExists($offset)) {
            unset($this->bag[$offset]);
        }
    }

    public function lock()
    {
        $this->isLocked = true;
    }
}
