<?php
/**
 * Moon framework
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

namespace Moon\Utility;


class MoonBag implements \ArrayAccess
{

    protected $bag = array();


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
            throw new \InvalidArgumentException($offset.' not exist.');
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
        if ($this->offsetExists($offset)) {
            unset($this->bag[$offset]);
        }
    }

}
 