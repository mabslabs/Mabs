<?php
/**
 * Created in Moon.
 * User: Mohamed Aymen Ben Slimane <aymen.kernel@gmail.com>
 * Date: 12/07/15
 * Time: 01:53 ص
 */

namespace Moon;


class Events
{
    const MOON_BEFORE_LOAD      = 'moon.before.load';
    const MOON_ON_LOCKED        = 'moon.on.locked';
    const MOON_ON_BOOT        = 'moon.on.boot';
    const MOON_HANDLE_EXCEPTION = 'moon.handle.exception';
    const MOON_HANDLE_REQUEST   = 'moon.handle.request';
    const MOON_ON_TERMINATE     = 'moon.on.terminate';
    const MOON_ON_FINISH        = 'moon.on.finish';
}
 