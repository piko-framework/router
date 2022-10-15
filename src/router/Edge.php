<?php

/**
 * This file is part of Piko Router
 *
 * @copyright 2019-2022 Sylvain PHILIP
 * @license LGPL-3.0-or-later; see LICENSE.txt
 * @link https://github.com/piko-framework/router
 */

declare(strict_types=1);

namespace piko\router;

/**
 * This class represents a trie edge.
 *
 * @author Sylvain PHILIP <contact@sphilip.com>
 * @see https://iq.opengenus.org/radix-tree/
 */
class Edge
{
    /**
     * @var Node
     */
    public $targetNode;

    /**
     * @var string
     */
    public $label;

    public function __construct(string $label = '')
    {
        $this->label = $label;
        $this->targetNode = new Node(true);
    }
}
