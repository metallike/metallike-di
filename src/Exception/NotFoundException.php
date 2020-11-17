<?php
/*
 * This file is part of the Metallike Framework package.
 *
 * (c) Florian Brandl <fb@metallike.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Metallike\Component\DependencyInjection\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}