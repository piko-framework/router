<?php

/**
 * This file is part of Piko Router
 *
 * @copyright 2022 Sylvain PHILIP
 * @license LGPL-3.0-or-later; see LICENSE.txt
 * @link https://github.com/piko-framework/router
 */

declare(strict_types=1);

namespace Piko\Router;

use Piko\Event;

/**
 * Event after the uri built
 *
 * @author Sylvain PHILIP <contact@sphilip.com>
 */
class AfterUriBuiltEvent extends Event
{
    /**
     * @var string
     */
    public $uri = '';

    /**
     * @param string $uri
     */
    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }
}
