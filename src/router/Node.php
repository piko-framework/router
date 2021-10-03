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
 * This class represents a trie node.
 *
 * @author Sylvain PHILIP <contact@sphilip.com>
 * @see https://iq.opengenus.org/radix-tree/
 */
class Node
{
    /**
     * @var Edge[]
     */
    public $edges = [];

    /**
     * @var boolean
     */
    public $isLeaf;

    public function __construct(bool $isLeaf)
    {
        $this->isLeaf = $isLeaf;
    }

    public function getTransition(string $transitionChar): ?Edge
    {
        return $this->edges[$transitionChar] ?? null;
    }

    public function addEdge(string $label, Node $targetNode): void
    {
        $edge = new Edge($label);
        $edge->targetNode = $targetNode;
        $this->edges[$label[0]] = $edge;
    }
}
