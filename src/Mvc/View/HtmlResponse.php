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

namespace Mindbit\Mpl\Mvc\View;

use Mindbit\Mpl\Mvc\Controller\OmRequest;
use Mindbit\Mpl\Template\Template;

abstract class HtmlResponse extends BaseResponse
{
    // constants for coding various doctypes
    // bits 0 to 7 are for minor version, 8 to 15 for major
    const DOCTYPE_STRICT                    = 0x00010000;
    const DOCTYPE_TRANSITIONAL              = 0x00020000;
    const DOCTYPE_FRAMESET                  = 0x00030000;
    const DOCTYPE_XHTML                     = 0x00100000;

    // valid doctypes in W3C Recommendations
    // for further info, see http://www.w3schools.com/tags/tag_DOCTYPE.asp
    const DOCTYPE_HTML_5                    = 0x00000500;
    const DOCTYPE_HTML_4_01_STRICT          = 0x00010401; // DOCTYPE_STRICT | 0x401;
    const DOCTYPE_HTML_4_01_TRANSITIONAL    = 0x00020401; // DOCTYPE_TRANSITIONAL | 0x401;
    const DOCTYPE_HTML_4_01_FRAMESET        = 0x00030401; // DOCTYPE_FRAMESET | 0x401;
    const DOCTYPE_XHTML_1_0_STRICT          = 0x00110100; // DOCTYPE_XHTML | DOCTYPE_STRICT | 0x100;
    const DOCTYPE_XHTML_1_0_TRANSITIONAL    = 0x00120100; // DOCTYPE_XHTML | DOCTYPE_TRANSITIONAL | 0x100;
    const DOCTYPE_XHTML_1_0_FRAMESET        = 0x00130100; // DOCTYPE_XHTML | DOCTYPE_FRAMESET | 0x100;
    const DOCTYPE_XHTML_1_1                 = 0x00100101; // DOCTYPE_XHTML | 0x101;

    const TEMPLATE_HTML_DOC                 = 'mindbit.mpl.htmldoc.html';

    const BLOCK_CHARSET                     = 'mindbit.mpl.charset';
    const BLOCK_TITLE                       = 'mindbit.mpl.title';
    const BLOCK_CSSREF                      = 'mindbit.mpl.cssref';
    const BLOCK_JSREF                       = 'mindbit.mpl.jsref';
    const BLOCK_BODY_INNER                  = 'mindbit.mpl.body.inner';

    const VAR_DOCTYPE                       = 'mindbit.mpl.doctype';
    const VAR_CHARSET                       = 'mindbit.mpl.charset';
    const VAR_TITLE                         = 'mindbit.mpl.title';
    const VAR_CSSREF_HREF                   = 'mindbit.mpl.cssref.href';
    const VAR_CSSREF_XATTR                  = 'mindbit.mpl.cssref.xattr';
    const VAR_JSREF_SRC                     = 'mindbit.mpl.jsref.src';

    const VAR_FORM_METHOD                   = 'mindbit.mpl.form.method';
    const VAR_FORM_ACTION                   = 'mindbit.mpl.form.action';

    const METHOD_POST                       = 'POST';
    const METHOD_GET                        = 'GET';

    // constants-to-uri map for valid doctypes
    protected static $doctypeMap = array(
        self::DOCTYPE_HTML_5                    =>
        null,
        self::DOCTYPE_HTML_4_01_STRICT          =>
        'PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"',
        self::DOCTYPE_HTML_4_01_TRANSITIONAL    =>
        'PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"',
        self::DOCTYPE_HTML_4_01_FRAMESET        =>
        'PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd"',
        self::DOCTYPE_XHTML_1_0_STRICT          =>
        'PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"',
        self::DOCTYPE_XHTML_1_0_TRANSITIONAL    =>
        'PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"',
        self::DOCTYPE_XHTML_1_0_FRAMESET        =>
        'PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd"',
        self::DOCTYPE_XHTML_1_1                 =>
        'PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"'
        );

    const MEDIA_SCREEN  = 'screen';
    const MEDIA_PRINT   = 'print';

    protected $doctype = self::DOCTYPE_HTML_5;

    /**
     * Default value to use as $encoding for htmlentities() and htmlspecialchars()
     *
     * @var string
     */
    protected $encodeCharset = 'UTF-8';

    /**
     * Default value to use as $flags for htmlentities() and htmlspecialchars()
     *
     * This property is used by the entities() method.
     *
     * @var int
     */
    protected $encodeFlags;

    /**
     * @var \Mindbit\Mpl\Template\Template
     */
    protected $template;

    protected $om;

    public function __construct($request)
    {
        parent::__construct($request);

        $this->encodeFlags = ENT_COMPAT | ENT_HTML401;

        // TODO: load different template based on self::DOCTYPE_XHTML flag in $this->doctype
        // Descendent classes can change $this->doctype from their constructor before calling
        // the parent constructor.
        $this->template = Template::load(self::TEMPLATE_HTML_DOC);
        $this->template->setVariable(self::VAR_FORM_METHOD, self::METHOD_POST);
        $this->template->setVariable(self::VAR_FORM_ACTION, $_SERVER['PHP_SELF']);

        if ($this->request instanceof OmRequest) {
            $this->om = $this->request->getOm();
        }
    }

    public function getOm()
    {
        return $this->om;
    }

    public function setOm($om)
    {
        $this->om = $om;
    }

    public function entities($string)
    {
        return htmlentities($string, $this->encodeFlags, $this->encodeCharset);
    }

    public function attr($attr)
    {
        $string = '';
        if (!$attr) {
            return $string;
        }
        foreach ($attr as $name => $value) {
            $string .= ' ' . $name;
            if ($value !== null) {
                $string .= '="' . $this->entities($value) . '"';
            }
        }
        return $string;
    }

    public function send()
    {
        $doctype = self::$doctypeMap[$this->doctype];
        $doctype = 'HTML' . (empty($doctype) ? '' : ' ' . $doctype);
        $this->template->setVariable(self::VAR_DOCTYPE, $doctype);
        $this->template->setVariable(self::VAR_CHARSET, $this->encodeCharset);
        $this->template->show();
    }

    public function addTitle($title)
    {
        $block = $this->template->getBlock(self::BLOCK_TITLE);
        $block->setVariable(self::VAR_TITLE, $title);
        $block->show();
    }

    public function addCssRef($url, $media = null)
    {
        $attr = array();
        if ($media !== null) {
            $attr['media'] = $media;
        }
        $block = $this->template->getBlock(self::BLOCK_CSSREF);
        $block->setVariable(self::VAR_CSSREF_HREF, $url);
        $block->setVariable(self::VAR_CSSREF_XATTR, ltrim($this->attr($attr)));
        $block->show();
    }

    public function addJsRef($url)
    {
        $block = $this->template->getBlock(self::BLOCK_JSREF);
        $block->setVariable(self::VAR_JSREF_SRC, $url);
        $block->show();
    }
}
