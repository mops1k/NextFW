<?php
namespace NextFW\Controller;


use NextFW;
use NextFW\Engine as Engine;


class Index extends Engine\Controller
{
    /* @var NextFW\Module\Index */
    public $mod;

    function test()
    {
        $this->tpl->set('method',__METHOD__);
        print_r($this->mod->test());
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