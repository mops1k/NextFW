<?php
namespace NextFW\Controller;

use NextFW\Engine as Engine;

class Parser extends Engine\Controller
{
    /* @var NextFW\Module\Parser */
    public $mod;

    use Engine\TSingleton;
    function start()
    {
        $this->tpl->setBlock("breadcrumb",'<a href="/parser">Parser</a>');

        $parser_html = $this->tpl->subLoad('parser.tpl');
        $this->tpl->setBlock("content",$parser_html);
    }
}