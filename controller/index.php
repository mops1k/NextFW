<?php
namespace NextFW\Controller;


use NextFW;
use NextFW\Engine as Engine;


class Index extends Engine\Controller
{
    /* @var NextFW\Module\Index */
    public $mod;

    function start()
    {
        $this->test();
    }

    function test()
    {
        $this->tpl->set('method',__METHOD__);
//        print_r($this->mod->test());
//        $this->get("NextFW\\Engine\\Logger")->write("Test log string 2"); # запишет в лог index
//        $this->get("NextFW\\Engine\\Logger",1)->customFile = "testInstance";
//        $this->get("NextFW\\Engine\\Logger",1)->write("Test log string 2"); # запишет в лог testInstance
//        $this->get("NextFW\\Engine\\Logger",0)->write("Test log string 3 to instance 1"); # запишет в лог index
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
