<?php
namespace NextFW\Controller;

use NextFW;
use NextFW\Engine as Engine;

class Weather extends Engine\Controller {
    /* @var NextFW\Module\Weather */
    public $mod;
    function start() {
        $this->tpl->setBlock("breadcrumb",'<a href="/weather">Погода</a>');
        $weather = $this->mod->getWeather(27612);
        //print_r($weather);
        $this->tpl->setArray('current',$weather['current_weather']);
        $now = new \DateTime();
        $now->modify("+1 day");
        $tomorrowDate = $now->format("Y-m-d");
        $this->tpl->setArray('tomorrow.night',$weather[$tomorrowDate]['night_short']);
        $this->tpl->setArray('tomorrow.day',$weather[$tomorrowDate]['day_short']);
        $weather_html = $this->tpl->subLoad('weather.tpl');
        $this->tpl->setBlock("content",$weather_html);
    }
}