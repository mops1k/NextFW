<?php
namespace NextFW\Controller;

use NextFW\Engine as Engine;
use NextFW\Config as Config;

class Captcha {
    function start()
    {
       $captcha = new Engine\Captcha();
       $captcha->createCaptcha(
        Config\Captcha::$width,
        Config\Captcha::$height,
        Config\Captcha::$length,
        Config\Captcha::$fontColor,
        Config\Captcha::$bgColor,
        Config\Captcha::$noise
        );
       /*
if(isset($_POST['submit'])) {
    if( $_SESSION['security_code']==md5(trim($_POST['captcha'])) ) {
        print "<b>You Passed the Test !! </b>";
    } else {
        print "<b>Test Failed !! Try againg !! :";
    }
}
       */
    }
}