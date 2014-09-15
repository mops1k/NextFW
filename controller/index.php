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
    function testArray()
    {
        $array = [
            [ "a" => "a" ],
            [ "b" => "b" ],
        ];
        foreach($array as $key => $val) {
            $this->tpl->setArray('array', $val);
            print_r($this->tpl->_array);
        }
    }
}