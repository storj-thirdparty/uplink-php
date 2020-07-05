<?php

namespace Storj\Uplink\Exception;

use Storj\Uplink\Exception;

/**
 * Issues with sockets and file descriptors
 * which do not originate in the Uplink library
 */
class IOException extends Exception\UplinkException
{
}
