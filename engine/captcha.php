<?php
namespace NextFW\Engine;

class Captcha {
    /**
     * Create security String from this variable
     * @access      private
    **/
    private $code_array;
    /**
     * Set font color
     * @access      private
    **/
    private $font_color;
    /**
     *  Set Captcha image background color
     *  @access     private
    **/
    private $bg_color;
    /**
     * Initalize the code_array variable
    **/
    function __construct() {
        $this->code_array = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    }
     /**
     * This function is used to create captcha
     * @access public
     * @param  int  $width      : Capach image width
     * @param  int  $height     : Captcha image height
     * @param  int  $length     : Captcha string length
     * @param  hex  $fontColor  : Captcha image font color code
     * @param  hex  $bgColor    : Captcha image background color code
     * @param  bool $pattern    : Captcha image pattern
     * @return image            : return captcha image
     **/
    public function createCaptcha($width=150,$height=50,$lenght=5,$fontcolor=0,$bgcolor=4,$pattern=false) {
         /**
          * Set type to each variable to avoid any vulnarables
          *
          */
         $flag = settype($width,"int");
         $width = ($flag===false)? 140 : $width;

         $flag = settype($height,'integer');
         $height = ($flag===false)? 50 : $height;

         $flag = settype($pattern,'boolean');
         $pattern = ($flag===false)? false : $pattern;
         /*
          * To create security code which is placed over the captcha image
          */
         $security_code = $this->createSecurityCode($lenght);
         /*
          * Store the generated security code to the session for the comaprion which the end user enter the code.
          * It also removing white spaces in the security code.
          */
         $_SESSION["security_code"] = md5(str_replace(" ","",$security_code));
         /*
          *  create the image in specified width and height
          */
         $image = ImageCreate($width, $height);
         /*
          * set font color
          */
         $this->font_color = $this->setColor($image,$fontcolor);
         /*
          * set background color
          */
         $this->bg_color = $this->setColor($image,$bgcolor);
        /*
         * Create background
         */
         ImageFill($image, 0, 0, $this->bg_color );

         /*
          * Generate noice
          */
         if ($pattern) {
            $this->generateNoice($image,100,$width,$height,10);
         }
         /*
          *  Write security code on the image
          */
         $width = ( $width - (strlen($security_code) * 7) ) / 2;
         ImageString($image, 4, (int) $width, $height/4, $security_code, $this->font_color);
         //ImageRectangle($image,0,0,$width-1,$height-1,$grey);
         /*
          * Rendering to image
          */
         header("Content-Type: image/jpeg");
         ImageJpeg($image);
         ImageDestroy($image);
    }

    /*
     * @description     : Generate the sucurity code from the code_array,security code lenght is 5.
     *                    It may contains alphabets(both upper and lower cases) and numbers from 0-9
     * @acces           : private
     * @param int $num  : length
     * @return  string  : security code
     */
    private function createSecurityCode($num) {
        $s=null;
        for($i=0;$i<$num;$i++) {
            $l = rand(0,61);
            $s .=$this->code_array[$l];
            $s .= " ";
        }
        return $s;
    }
    private function setColor($im,$color) {
        $hex = str_replace("#", "", $color);
        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        $col = ImageColorAllocate($im, $r, $g, $b);
        return $col;
    }
    private function settingsCaptcha() {

    }
    /*
     * Generate Noice
     */
    private function generateNoice($im,$count,$w,$h,$t) {
        for($i=0;$i<100;$i++) {
                $x = rand(0,$w);
                $y = rand(0,$h);
                $col = ImageColorAllocate($im, rand(5,250), rand(1,200), rand(5,255));
                imageline($im,$x,$y,$x+rand(1,$t),$y+rand(1,$t),$col);
             }
    }

}