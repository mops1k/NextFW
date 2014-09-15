<?php
namespace NextFW\Controller;


use NextFW;
use NextFW\Engine as Engine;
use NextFW\Config as Config;


class Index extends Engine\Controller
{
    function test()
    {
        $this->tpl->set('method',__METHOD__);
    }
}