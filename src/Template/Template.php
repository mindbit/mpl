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

class Template
{
    const VERSION = 1;

    protected static $loadPath = null;
    protected static $cacheDir = null;
    protected $strict;

    /**
     * Root block (has no name and contains the index)
     *
     * @var Block
     */
    protected $rootBlock;

    protected function __construct($rootBlock)
    {
        $this->rootBlock = $rootBlock;
    }

    public static function setLoadPath($loadPath)
    {
        if (is_array($loadPath)) {
            $loadPath = implode(PATH_SEPARATOR, $loadPath);
        }

        self::$loadPath = $loadPath;
    }

    public static function getLoadPath()
    {
        return self::$loadPath;
    }

    public static function setCacheDir($cacheDir)
    {
        self::$cacheDir = $cacheDir;
    }

    public static function getCacheDir()
    {
        return self::$cacheDir;
    }

    public static function load($name, $strict = false)
    {
        if (self::$loadPath == null) {
            throw new \Exception('Load path is not set');
        }

        foreach (explode(PATH_SEPARATOR, self::$loadPath) as $tplPath) {
            $tplPath .= DIRECTORY_SEPARATOR . $name;
            if ($tplStat = @stat($tplPath)) {
                break;
            }
        }

        if (!$tplStat) {
            throw new \Exception('Could not find template ' . $name);
        }

        $cacheStat = false;
        if (self::$cacheDir != null) {
            $cachePath = self::$cacheDir . DIRECTORY_SEPARATOR . $name .
                '&version=' . self::VERSION .
                '&strict=' . ($strict ? 'true' : 'false');
            $cacheStat = @stat($cachePath);
        }

        if ($cacheStat && $cacheStat['mtime'] >= $tplStat['mtime']) {
            $tplSerial = file_get_contents($cachePath);
            return unserialize($tplSerial);
        }

        $tplObj = self::parse(file_get_contents($tplPath), $strict);

        if (self::$cacheDir != null) {
            file_put_contents($cachePath, serialize($tplObj));
        }

        return $tplObj;
    }

    public static function parse($str, $strict = false)
    {
        return new Template(Block::parse($str, $strict));
    }

    /**
     * Replace the contents of a given block with another template
     *
     * @param string $name
     * @param Template $template
     */
    public function replaceBlock($name, $template)
    {
        $this->rootBlock->getBlock($name)->replace($template->rootBlock);
    }

    public function getRenderedText()
    {
        return $this->rootBlock->getRenderedText();
    }

    public function setVariable($name, $value)
    {
        $this->rootBlock->setVariable($name, $value);
        return $this;
    }

    public function setVariables($data)
    {
        $this->rootBlock->setVariables($data);
        return $this;
    }

    public function getBlock($name)
    {
        return $name == null ?
            $this->rootBlock :
            $this->rootBlock->getBlock($name);
    }

    public function show()
    {
        echo $this->getRenderedText();
    }
}
