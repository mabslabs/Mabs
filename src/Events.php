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

namespace Mabs;

class Events
{
    public const string MABS_BEFORE_LOAD      = 'mabs.before.load';
    public const string MABS_ON_LOCKED        = 'mabs.on.locked';
    public const string MABS_ON_BOOT          = 'mabs.on.boot';
    public const string MABS_HANDLE_EXCEPTION = 'mabs.handle.exception';
    public const string MABS_HANDLE_REQUEST   = 'mabs.handle.request';
    public const string MABS_ON_TERMINATE     = 'mabs.on.terminate';
    public const string MABS_ON_FINISH        = 'mabs.on.finish';

    private function __construct()
    {}
}
