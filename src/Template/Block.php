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
    const PREG_PADDING  = '/(\r?\n?)[\t ]*$/';
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

    /**
     * Values of block variables that are used for expansion
     *
     * @var array
     */
    protected $variables = array();

    /**
     * List of rendered instances of this block
     *
     * Each rendered instance is the text that results from rendering the current block and all
     * visible descendent blocks at any level.
     *
     * @var array
     */
    protected $renderedTexts = array();

    protected $padding = '';

    protected function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * Parse a string that contains Template markup into Block objects
     *
     * @param string $str
     * @param bool $strict
     * @throws \Exception
     * @return \Mindbit\Mpl\Template\Block
     */
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
            $padding = $m[1][0];
            $blockOffset = $matchOffset + strlen($padding);
            $blockLength = $matchLength - strlen($padding);
            $blockKeyword = $m[2][0];
            $blockArgs = $m[3][0];

            if ($strict) {
                $matchOffset = $blockOffset;
                $matchLength = $blockLength;
                $padding = '';
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
                $current->padding = $padding;
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

    public function setVariable($name, $value)
    {
        $this->variables[$name] = $value;
        return $this;
    }

    public function setVariables($data)
    {
        $this->variables = $data + $this->variables;
        return $this;
    }

    /**
     * @param string $name
     * @return \Mindbit\Mpl\Template\Block
     */
    public function getBlock($name)
    {
        return isset($this->index[$name]) ? $this->index[$name] : null;
    }

    /**
     * Create a rendered instance of this block and save it on the rendered
     * instances stack.
     *
     * Calling this method consumes all rendered instances of all sub-blocks. If any
     * of the sub-blocks has never been rendered before (or all its rendered
     * instances have been already consumed), the sub-block will be rendered
     * implicitly. This does not apply to hidden sub-blocks, which will be ignored
     * completely.
     *
     * @throws \Exception
     * @return \Mindbit\Mpl\Template\Block
     */
    public function show()
    {
        for ($block = $this; $block != null; $block = $block->parent) {
            $block->hidden = false;
        }

        $text = '';
        foreach ($this->nodes as $node) {
            if ($node[0] == self::NODE_TEXT) {
                $text .= $node[1];
                continue;
            }

            if ($node[0] == self::NODE_VAR) {
                $text .= $this->expandVariable($node[1], $node[2]);
                continue;
            }

            if ($node[0] != self::NODE_BLOCK) {
                throw new \Exception('Invalid node type ' . $node[0]);
            }

            /* @var $block Block */
            $block = $node[1];

            if ($block->hidden) {
                continue;
            }

            if (empty($block->renderedTexts)) {
                $block->show();
            }

            $text .= implode('', $block->renderedTexts);
            $block->renderedTexts = array();
        }

        $this->renderedTexts[] = $text;
        return $this;
    }

    public function hide()
    {
        $this->hidden = true;
        $this->renderedTexts = array();
    }

    /**
     * Get a rendered instance of this block.
     *
     * If this block has been rendered multiple times (by calling the show() method),
     * this method returns the first rendered instance and pops it off the stack of
     * rendered instances.
     *
     * If this block has never been rendered (or has been rendered but all instances
     * have been consumed by previously calling this method), the show() method will
     * be called implicitly in order to produce a rendered instance.
     *
     * @return string
     */
    public function getRenderedText()
    {
        if (empty($this->renderedTexts)) {
            $this->show();
        }

        return array_shift($this->renderedTexts);
    }

    /**
     * Replace the contents of this block with the contents of a different block
     *
     * This is typically used by the Template class to replace a sub-block of the
     * current template with the contents of an external template.
     *
     * @param mixed $block
     */
    public function replace($block, $trimPadding = true)
    {
        if (is_string($block)) {
            $node = array(self::NODE_TEXT, $block);
            $block = new Block();
            $block->nodes[] = $node;
        }

        for ($root = $this; $root->parent != null; $root = $root->parent) {
        }

        foreach ($block->index as $name => $ref) {
            if (isset($root->index[$name])) {
                throw new \Exception('Duplicate block ' . $name);
            }
            $root->index[$name] = $ref;
        }

        $this->nodes = $block->nodes;
        foreach ($this->nodes as $key => $node) {
            if ($node[0] == self::NODE_BLOCK) {
                $this->nodes[$key][1]->parent = $this;
            }
        }

        $this->renderedTexts = array();

        // Prepend trailing space from original node. Note that in strict mode,
        // 'trailingCrLf' is always an empty string (see the code in the parse()
        // method), so no adjustments will be made.

        if (!strlen($this->padding) || empty($this->nodes)) {
            return $this;
        }

        $padding = $this->padding;
        if ($trimPadding) {
            $padding = preg_replace(self::PREG_PADDING, '$1', $padding);
        }

        if ($this->nodes[0][0] == self::NODE_TEXT) {
            $this->nodes[0][1] = $padding . $this->nodes[0][1];
        } elseif ($this->nodes[0][0] == self::NODE_VAR) {
            array_unshift($this->nodes, array(self::NODE_TEXT, $padding));
        }

        // Remove trailing space, as it would have been "swallowed" by the end
        // marker of this block.

        $node =& $this->nodes[count($this->nodes) - 1];
        if ($node[0] == self::NODE_TEXT) {
            $node[1] = preg_replace(self::PREG_PADDING, '', $node[1]);
        }
    }

    public function getPadding()
    {
        return $this->padding;
    }

    protected function expandVariable($name, $filter)
    {
        $value = '';

        for ($block = $this; $block != null; $block = $block->parent) {
            if (isset($block->variables[$name])) {
                $value = $block->variables[$name];
                break;
            }
        }

        // TODO implement filter parameter customization through class properties
        // TODO implement 'j' filter
        switch ($filter) {
            case 'h':
                return htmlspecialchars($value);
            case 'e':
                return htmlentities($value);
            case 'u':
                return urlencode($value);
            case 'r':
                return rawurlencode($value);
            default:
                return $value;
        }
    }
}
