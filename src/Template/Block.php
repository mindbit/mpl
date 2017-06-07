<?php
/*
 * Mindbit PHP Library
 * Copyright (C) 2017 Mindbit SRL
 *
 * This library is free software; you can redistribute it and/or modify
 * it under the terms of version 2.1 of the GNU Lesser General Public
 * License as published by the Free Software Foundation.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Mindbit\Mpl\Template;

class Block
{
    const NODE_TEXT     = 1;
    const NODE_VAR      = 2;
    const NODE_BLOCK    = 3;

    const PREG_TAG      = '/(\r?\n?[\t ]*)<!--\s*(BEGIN|END|COMMENT)\s*(.*?)\s*-->/';
    const PREG_BEGIN    = '/^([\w.]+)(?:\s+\[(hidden)\]|())$/';
    const PREG_END      = '/^[\w.]+$/';
    const PREG_VAR      = '/\{([\w.]+)(?:|:([heurj]))\}/';

    /**
     * Block name, as defined by BEGIN and END tags
     * @var string
     */
    protected $name;

    /**
     * List of template nodes
     *
     * Each element is an array containing the node type at index 0 and
     * the node data at index 1. The node type is one of the NODE_ constants.
     *
     * The node data depends on the node type:
     *  NODE_TEXT       The actual text that will be copied verbatim to the output
     *  NODE_VAR        The variable name
     *  NODE_BLOCK      The child Block object
     *
     * For NODE_VAR nodes, an additional filter is stored at index 2.
     *
     * @var array
     */
    protected $nodes = array();

    /**
     * The parent Block object; null for the root block
     * @var Block
     */
    protected $parent;

    /**
     * Index of descendent nodes at any level
     *
     * Only populated for the root block.
     *
     * @var array
     */
    protected $index = array();

    /**
     * Flag that indicates whether the block will be included in the output
     * @var bool
     */
    protected $hidden = false;

    protected function __construct($name = null)
    {
        $this->name = $name;
    }

    public static function parse($str, $strict = false)
    {
        $root = new Block();
        $current = $root;

        preg_match_all(
            self::PREG_TAG,
            $str,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        $globalOffset = 0;
        foreach ($matches as $m) {
            $matchOffset = $m[0][1];
            $matchLength = strlen($m[0][0]);
            $blockOffset = $matchOffset + strlen($m[1][0]);
            $blockLength = $matchLength - strlen($m[1][0]);
            $blockKeyword = $m[2][0];
            $blockArgs = $m[3][0];

            if ($strict) {
                $matchOffset = $blockOffset;
                $matchLength = $blockLength;
            }

            if ($matchOffset > $globalOffset) {
                $current->nodes = array_merge(
                    $current->nodes,
                    self::parseText(substr($str, $globalOffset, $matchOffset - $globalOffset))
                );
            }
            $globalOffset = $matchOffset + $matchLength;

            if ($blockKeyword == 'BEGIN') {
                if (!preg_match(self::PREG_BEGIN, $blockArgs, $m)) {
                    throw new \Exception('BEGIN syntax error at ' . $blockOffset);
                }
                $block = new Block($m[1]);

                if (isset($root->index[$block->name])) {
                    throw new \Exception('Duplicate ' . $block->name . ' block at ' . $blockOffset);
                }
                $root->index[$block->name] = $block;

                if ($m[2] == 'hidden') {
                    $block->hidden = true;
                }

                $current->nodes[] = array(self::NODE_BLOCK, $block);
                $block->parent = $current;
                $current = $block;
                continue;
            }

            if ($blockKeyword == 'END') {
                if (!preg_match(self::PREG_END, $blockArgs)) {
                    throw new \Exception('END syntax error at ' . $blockOffset);
                }
                if ($current->parent == null) {
                    throw new \Exception('Unexpected block END at ' . $blockOffset);
                }
                if ($current->name != $blockArgs) {
                    throw new \Exception('Block END mismatch (expected ' . $current->name . ', found ' . $blockArgs . ' at ' . $blockOffset);
                }
                $current = $current->parent;
            }
        }

        if ($current->parent != null) {
            throw new \Exception('Unterminated block ' . $current->name);
        }

        if ($globalOffset < strlen($str)) {
            $current->nodes = array_merge(
                $current->nodes,
                self::parseText(substr($str, $globalOffset))
            );
        }

        return $root;
    }

    protected static function parseText($str)
    {
        $nodes = array();
        preg_match_all(
            self::PREG_VAR,
            $str,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        $globalOffset = 0;
        foreach ($matches as $m) {
            $matchOffset = $m[0][1];
            $matchLength = strlen($m[0][0]);
            $varName = $m[1][0];
            $filter = isset($m[2]) ? $m[2][0] : null;

            if ($matchOffset > $globalOffset) {
                $nodes[] = array(
                    self::NODE_TEXT,
                    substr($str, $globalOffset, $matchOffset - $globalOffset)
                );
            }
            $globalOffset = $matchOffset + $matchLength;

            $nodes[] = array(self::NODE_VAR, $varName, $filter);
        }

        if ($globalOffset < strlen($str)) {
            $nodes[] = array(self::NODE_TEXT, substr($str, $globalOffset));
        }

        return $nodes;
    }
}
