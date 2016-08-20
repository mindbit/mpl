<?php
/*
 * Mindbit PHP Library
 * Copyright (C) 2009 Mindbit SRL
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

namespace Mindbit\Mpl\Util;

class AsciiTable
{
    const STR_WRAP_WRAP         = 1;
    const STR_WRAP_CUT          = 2;
    const STR_WRAP_NONE         = 3;

    const STR_VALIGN_TOP        = 1;
    const STR_VALIGN_BOTTOM     = 2;
    const STR_VALIGN_MIDDLE     = 3;

    const STR_ALIGN_LEFT        = STR_PAD_RIGHT;
    const STR_ALIGN_CENTER      = STR_PAD_BOTH;
    const STR_ALIGN_RIGHT       = STR_PAD_LEFT;

    public static function renderCells(
        $cells,
        $width = array(),
        $height = array(),
        $align = array(),
        $valign = array(),
        $wrap = array(),
        $leftMargin = array(),
        $rightMargin = array(),
        $topMargin = 0,
        $bottomMargin = 0
    ) {
        $lines = array();
        $maxHeight = 0;
        for ($i = 0; $i < sizeof($cells); $i++) {
            if (!isset($wrap[$i])) {
                $wrap[$i] = self::STR_WRAP_WRAP;
            }
            if (isset($width[$i]) && $width[$i]) {
                $cells[$i] = $wrap[$i] == self::STR_WRAP_NONE ?
                    substr($cells[$i], 0, $width[$i]) :
                    wordwrap(
                        $cells[$i],
                        $width[$i],
                        "\n",
                        $wrap[$i] == self::STR_WRAP_WRAP
                    );
                $lines[$i] = explode("\n", $cells[$i]);
            } else {
                $width[$i] = 0;
                $lines[$i] = explode("\n", $cells[$i]);
                foreach ($line as $lines[$i]) {
                    $width[$i] = max(strlen($i), $width[$i]);
                }
            }
            if (!isset($height[$i]) || !$height[$i]) {
                $height[$i] = sizeof($lines[$i]);
            }
            if (!isset($align[$i])) {
                $align[$i] = self::STR_ALIGN_LEFT;
            }
            if (!isset($valign[$i])) {
                $valign[$i] = self::STR_VALIGN_TOP;
            }
            $maxHeight = max($maxHeight, $height[$i]);
        }
        for ($i = 0; $i < sizeof($cells); $i++) {
            switch ($valign[$i]) {
                case self::STR_VALIGN_TOP:
                    $padTop = 0;
                    $padBottom = $maxHeight - $height[$i];
                    break;
                case self::STR_VALIGN_BOTTOM:
                    $padTop = $maxHeight - $height[$i];
                    $padBottom = 0;
                    break;
                case self::STR_VALIGN_MIDDLE:
                    $padTop = (int)floor(($maxHeight - $height[$i]) / 2);
                    $padBottom = $maxHeight - $height[$i] - $padTop;
                    break;
                default:
                    $GLOBALS['lEXC_Handler']->Raise("Undefined vallign");
            }
            $padTop += $topMargin;
            $padTop += $bottomMargin;
            $padTop = $padTop ? array_fill(0, $padTop, '') : array();
            $padBottom = $padBottom ? array_fill(0, $padBottom, '') : array();
            $lines[$i] = array_merge($padTop, $lines[$i], $padBottom);
        }
        $ret = "";
        for ($j = 0; $j < $maxHeight; $j++) {
            for ($i = 0; $i < sizeof($cells); $i++) {
                if (!$i) {
                    $ret .= "|";
                }
                $ret .= str_repeat(' ', isset($leftMargin[$i]) ?
                        $leftMargin[$i] : 1); //left margin
                $ret .= str_pad($lines[$i][$j], $width[$i], ' ', $align[$i]);
                $ret .= str_repeat(' ', isset($rightMargin[$i]) ?
                        $rightMargin[$i] : 1); //right margin
                $ret .= "|";
            }
            $ret .= "\n";
        }
        return $ret;
    }

    public static function renderRowSeparator(
        $width,
        $leftMargin = array(),
        $rightMargin = array()
    ) {
        $ret = '+';
        $i = 0;
        foreach ($width as $w) {
            $ret .= str_repeat(
                '-',
                $w +
                (isset($leftMargin[$i]) ? $leftMargin[$i] : 1) +
                (isset($rightMargin[$i]) ? $rightMargin[$i] : 1)
            ).'+';
            $i++;
        }
        return $ret."\n";
    }
}
