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

namespace Mabs\Router;


class Route
{
    private $path;
    private $callback;
    private $name;

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        if ($this->name == null) {
            $this->name =  spl_object_hash($this);
        }

        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param mixed $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    public function getRegularExpression()
    {
        $path = trim($this->path, '/');
        $patterns = array('/{(\w+)\?}/', '/{(\w+)}/');
        $replacements = array('(\w*)', '(\w+)');

        return preg_replace($patterns, $replacements, $path);
    }

    public function getNamesParameters(array $matchedValues = array())
    {
        $path = trim($this->path, '/');
        $pattern = '/{(\w+)\??}/';

        preg_match_all($pattern, $path, $matches);
        $params = array();
        if (isset($matches[1]) && is_array($matches[1])) {
            foreach ($matches[1] as $index => $matche) {
                $params[$matche] = isset($matchedValues[$index + 1]) ? $matchedValues[$index + 1] : null;
            }
        }

        return $params;
    }
}
