<?php

/**
 * This file is part of Piko Router
 *
 * @copyright 2019-2021 Sylvain PHILIP
 * @license LGPL-3.0-or-later; see LICENSE.txt
 * @link https://github.com/piko-framework/router
 */

declare(strict_types=1);

namespace piko\router;

/**
 * This class represents a search match.
 *
 * @author Sylvain PHILIP <contact@sphilip.com>
 */
class Matcher
{
    /**
     * @var boolean
     */
    public $found = false;

    /**
     * @var mixed
     */
    public $handler;

    /**
     * @var string[]
     */
    public $params = [];
}
