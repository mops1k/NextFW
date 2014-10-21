<?php
namespace NextFW\Controller;

use NextFW;
use NextFW\Engine as Engine;

class Crontab extends Engine\Controller {
    /* @var NextFW\Module\Crontab */
    public $mod;
    
    function start()
    {
        /* Enter code below */

        // initialize crontab as user www-data
        $crontab = new Engine\Crontab('www-data');

        // show exists tasks as array
        print_r($crontab->crontabs);
        // add cron task
//        $crontab->addCron(0, "*", "*", "*", "*", "ls -al");
//        $crontab->writeCrontab();
        // delete cron task
//        $crontab->delEntry(0);
//        $crontab->writeCrontab();

        $this->tpl->setBlock("breadcrumb",'<a href="/crontab">Crontab example</a>');
        $this->tpl->setBlock('content','');
    }
}
