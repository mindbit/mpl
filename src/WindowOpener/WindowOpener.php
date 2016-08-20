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

use Mindbit\Mpl\WindowOpener\BaseWindowOpener;

/**
 * Class for generating JavaScript code that opens a window.
 *
 * The main purpose is to have a common way to open new windows for a
 * specific interface (form). By using the mechanism provided by this
 * class, it's guaranteed that:
 * - the opened window for a specific interface (form) will have the same
 *   properties, regardless of where it's opened from
 * - the same object (with the same id) will not be opened in different
 *   windows (the window id is unique for a specific OM)
 */
abstract class WindowOpener extends BaseWindowOpener
{
    public $callerClass;

    /**
     * param $obj
     *     Reference to the calling object. This parameter is used to avoid
     *     JavaScript naming conflicts when several tabs in a multi-tabbed
     *     form want to open the same OM form.
     *
     *     This parameter is passed directly by the getWindowOpener() method
     *     of the form that the opener is attached to. Usually, the
     *     getWindowOpener() method should be called using $this as parameter,
     *     regardless of where the call is made.
     */
    public function __construct($obj)
    {
        $this->callerClass = (null === $obj ? "null" : get_class($obj));
    }

    /**
     * Return an identified that is unique across all windows of the
     * same type (instances of the same form).
     */
    public function getUidBase()
    {
        return get_class($this);
    }

    abstract public function getUrl();

    /**
     * Pentru clasele de ipwal 1, ar trebui suprascrisa aceasta
     * metoda in loc de getUrl(), pentru a asigura transmiterea
     * parametrului index cu numele corect (in ipwal 1 nu are
     * numele fix __id, ci difera in functie de modul).
     */
    public function getLink()
    {
        return $this->getUrl() . "?__id=";
    }

    public function getLinkNew()
    {
        return $this->getUrl() . "?__new=";
    }

    public function __fnLoad()
    {
        return "__" . $this->getUidBase() . "__" . $this->callerClass . "__load";
    }

    public function javaScriptLoad($extraParams = null, $scriptTags = true)
    {
        $append = "";
        if (null !== $extraParams) {
            $list = explode(",", $extraParams);
            foreach ($list as $token) {
                $token = trim($token);
                $append .= ' + "&' . $token . '=" + ' . $token;
            }
        }
        if ($scriptTags) {
            echo '<script type="text/javascript">';
        }
        $this->__javaScriptLoad(
            $this->__fnLoad(),
            '"' . $this->getLink() . '" + id' . $append,
            '"' . $this->getUidBase() . '" + id',
            "id" . (null === $extraParams ? "" : "," . $extraParams)
        );
        if ($scriptTags) {
            echo '</script>';
        }
    }

    public function __fnNew()
    {
        return "__" . $this->getUidBase() . "__" . $this->callerClass . "__new";
    }

    public function javaScriptNew($scriptTags = true)
    {
        if ($scriptTags) {
            echo '<script type="text/javascript">';
        }
        $this->__javaScriptNew(
            $this->__fnNew(),
            '"' . $this->getLinkNew() . '" + append',
            '"' . $this->getUidBase() . '_uniq_"',
            "append"
        );
        if ($scriptTags) {
            echo '</script>';
        }
    }

    public function jsLinkLoad($id, $extraParams = null)
    {
        return "javascript:" . $this->jsInvokeLoad($id, $extraParams);
    }

    public function jsInvokeLoad($id, $extraParams = null)
    {
        return $this->__fnLoad() . "(" . ((int)$id) .
            (null === $extraParams ? "" : "," . $extraParams) . ")";
    }

    public function jsLinkNew($append = "")
    {
        return "javascript:" . $this->__fnNew() . "('" . $append . "')";
    }
}
