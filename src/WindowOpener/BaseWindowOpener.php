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

namespace Mindbit\Mpl\WindowOpener;

class BaseWindowOpener
{
    public function getWidth()
    {
        return 600;
    }

    public function getHeight()
    {
        return 400;
    }

    public function getResizable()
    {
        return true;
    }

    public function hasLocation()
    {
        return true;
    }

    public function getScrollbars()
    {
        return true;
    }

    public function jsOpenFn()
    {
        return "ow";
    }

    public function __javaScriptLoad($fn, $link, $obj, $arg)
    {
        ?>
        public function <?= $fn?>(<?= $arg?>) {
            var leftw = (window.screen.availWidth - <?= $this->getWidth()?>) / 2;
            var topw = (window.screen.availHeight - <?= $this->getHeight()?>) / 2;
            var win = window.open(
                <?= $link?>,
                <?= $obj?>,
                "left=" + leftw + "," +
                "top=" + topw + "," +
                "resizable=<?= $this->getResizable() ? "yes" : "no"?>," +
                "location=<?= $this->hasLocation() ? "1" : "0"?>," +
                "width=<?= $this->getWidth()?>," +
                "height=<?= $this->getHeight()?>," +
                "scrollbars=<?= $this->getScrollbars() ? "yes" : "no"?>"
           );
        }
        <?php
    }

    public function __javaScriptNew($fn, $link, $obj, $arg)
    {
        ?>
        public function <?= $fn?>(<?= $arg?>) {
            var leftw = (window.screen.availWidth - <?= $this->getWidth()?>) / 2;
            var topw = (window.screen.availHeight - <?= $this->getHeight()?>) / 2;
            var dat = new Date();
            var uniq = dat.getHours() * 3600000 +
                dat.getMinutes() * 6000 +
                dat.getSeconds() * 1000 +
                dat.getMilliseconds();

            var win = window.open(
                <?= $link?>,
                <?= $obj?> + uniq,
                "left=" + leftw + "," +
                "top=" + topw + "," +
                "resizable=<?= $this->getResizable() ? "yes" : "no"?>," +
                "location=<?= $this->hasLocation() ? "yes" : "no"?>," +
                "width=<?= $this->getWidth()?>," +
                "height=<?= $this->getHeight()?>," +
                "scrollbars=<?= $this->getScrollbars() ? "yes" : "no"?>"
           );
        }
        <?php
    }

    public function javaScriptOpen($link, $objName)
    {
        $this->__javaScriptLoad(
            $this->jsOpenFn(),
            '"' . $link . '"',
            '"' . $objName . '"',
            ""
        );
    }
}
