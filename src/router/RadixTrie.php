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
 * This class is an utility to insert and search routes into a radix trie structure.
 *
 * @author Sylvain PHILIP <contact@sphilip.com>
 * @see https://iq.opengenus.org/radix-tree/
 */
class RadixTrie
{
    /**
     * Root node of the radix trie
     *
     * @var Node
     */
    public $root;

    /**
     * Registered $handlers
     *
     * @var string[]
     */
    public $handlers = [];

    public function __construct()
    {
        $this->root = new Node(true);
    }

    private function getFirstMismatchLetter(string $word, string $edgeWord): int
    {
        $length = min(strlen($word), strlen($edgeWord));

        for ($i = 1; $i < $length; $i++) {
            if ($word[$i] != $edgeWord[$i]) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Register a route/handler into the radix trie
     *
     * @param string $route
     * @param mixed $handler
     */
    public function insert(string $route, $handler): void
    {
        $this->handlers[$route] = $handler;
        $node = $this->root;
        $index = 0;
        $routeLen = strlen($route);

        while ($index < $routeLen) {
            $transitionChar = $route[$index];
            $edge = $node->getTransition($transitionChar);
            $lastPart = substr($route, $index); // Last part of the route

            // There is no associated edge with the first character of the current string.
            // So simply add the rest of the string and finish
            if ($edge === null) {
                $node->edges[$transitionChar] = new Edge($lastPart);
                break;
            }

            $split = $this->getFirstMismatchLetter($lastPart, $edge->label);

            if ($split == -1) {

                if (strlen($lastPart) == strlen($edge->label)) {
                    // The edge and leftover string are the same length
                    // so finish and update the target node as a leaf node
                    $edge->targetNode->isLeaf = true;
                    break;
                } elseif (strlen($lastPart) < strlen($edge->label)) {
                    // The leftover word is a prefix to the edge string, so split
                    // $suffix = currentEdge.label.substring(currStr.length());
                    $suffix = substr($edge->label, strlen($lastPart));
                    $edge->label = $lastPart;
                    $newTarget = new Node(true);
                    $afterNewTarget = $edge->targetNode;
                    $edge->targetNode = $newTarget;
                    $edge->targetNode->addEdge($suffix, $afterNewTarget);
                    break;
                } else {
                    // strlen($currStr) > strlen($edge->label)
                    // There is leftover string after a perfect match
                    $split = strlen($edge->label);
                }

            } else {
                // The leftover string and edge string differed, so split at point
                $suffix = substr($edge->label, $split);
                $edge->label = substr($edge->label, 0, $split);
                $prevTarget = $edge->targetNode;
                $edge->targetNode = new Node(false);
                $edge->targetNode->addEdge($suffix, $prevTarget);
            }

            // Traverse the tree
            $node = $edge->targetNode;
            $index += $split;
        }
    }

    /**
     * @param string $route
     * @throws \RuntimeException
     * @return Match
     */
    public function search(string $route): Match
    {
        $match = new Match();
        $current = $this->root;
        $index = 0;
        $routeLen = strlen($route);
        $searchPath = '';

        while ($index < $routeLen) {
            $transitionChar = $route[$index];
            $edge = $current->getTransition($transitionChar);

            if ($edge == null) {
                // No match found
                return $match;
            }

            $currSubstring = substr($route, $index);
            $searchPath .= $edge->label;
            $label = $edge->label; // Because label could be altered by param, we work on a copy

            // Check if the next edge is a param
            if (isset($edge->targetNode->edges[':'])) {
                $edge = $edge->targetNode->edges[':'];
                $label .= $edge->label;
                $searchPath .= $edge->label;
            }

            // Params substitution
            while (($pos = strpos($label, ':')) !== false) {

                $param = substr($label, $pos + 1);
                $value = substr($currSubstring, $pos);

                if (!$value) {
                    break;
                }

                if ($param === '') {
                    // This mean there is several params in next edges. We cannot determine which param to use.
                    // Ex: /user/:id /user/:alias => /user/: id|alias ?
                    $parts = [];

                    foreach ($edge->targetNode->edges as $e) {
                        $parts[] = $label . $e->label;
                    }

                    throw new \RuntimeException('Cannot determine param for the route parts: ' . implode(', ', $parts));
                }

                if (($pos = strpos($param, '/')) !== false) {
                    $param = substr($param, 0, $pos);
                }

                if (($pos = strpos($value, '/')) !== false) {
                    $value = substr($value, 0, $pos);
                }

                $match->params[$param] = $value;
                $label = str_replace(':' . $param, $value, $label);
            }

            $startWith = substr($currSubstring, 0, strlen($label));

            if ($startWith != $label) {
                // No match found
                return $match;
            }

            $index += strlen($label);
            $current = $edge->targetNode;
        }

        if ($current->isLeaf) {
            $match->found = true;
            $match->handler = $this->handlers[$searchPath] ?? '';

            if (is_string($match->handler)) {
                foreach ($match->params as $key => $value) {
                    $match->handler = str_replace(':' . $key, $value, $match->handler);
                }
            }
        }

        return $match;
    }
}
