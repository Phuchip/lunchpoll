<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Application specific global variables
class Globals
{
    private static $ClientID = '387697988475-p7rpj8vaoi1r3vj1haagshtp0o8o30cv.apps.googleusercontent.com';
    private static $ClientSecret = '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';
    private static $RedirectUri = 'http://whatisforlunch.com/google';

    public static function getGoogle($redirectUrl = null)
    {
        include_once "vendor/autoload.php";

        $google_client = new Google_Client();

        $google_client->setClientId(self::$ClientID); //Define your ClientID

        $google_client->setClientSecret(self::$ClientSecret); //Define your Client Secret Key

        $google_client->setRedirectUri(self::$RedirectUri); //Define your Redirect Uri

        $google_client->addScope('email');

        $google_client->addScope('profile');

        $google_client->setState($redirectUrl);

        return $google_client;
    }

    public static function getLinkLogin()
    {
        $base_url = rtrim(base_url(),'/');
        $redirectUrl = $base_url.$_SERVER['REQUEST_URI'];
        $google_client = self::getGoogle($redirectUrl);
        return $google_client->createAuthUrl(); 
    }

    public static function saveImagePath($path,$url)
    {
        $context = stream_context_create(
            array(
                "http" => array(
                    'method'=>"GET",
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) 
                                AppleWebKit/537.36 (KHTML, like Gecko) 
                                Chrome/50.0.2661.102 Safari/537.36\r\n" .
                                "accept: text/html,application/xhtml+xml,application/xml;q=0.9,
                                image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3\r\n" .
                                "accept-language: es-ES,es;q=0.9,en;q=0.8,it;q=0.7\r\n" . 
                                "accept-encoding: gzip, deflate, br\r\n"
                )
            )
        );
        file_put_contents($path, file_get_contents($url,false,$context));
    }

    public static function createFileName($userId){
        $fileName = $userId.'-'.date('YmdHis').'.png';
        return $fileName;
    }

    public static function createFilePath($path,$userId=null){
        if ($userId) {
            $fileName = self::createFileName($userId);
        }
        $filePath = $path.$fileName;
        return $filePath;
    }

    public static function createFolder($nameFolder)
    {
        $folder = 'images/'.$nameFolder.'/';
        if (!file_exists($folder)) {
            mkdir($folder, 0777,true);
        }
    }

    public static function saveAvatarUser($userId,$url){
        $folder = self::createFolder('avatar');
        $path = 'images/avatar/'.date('Ym').'/';
        if (!file_exists($path)) {
            mkdir($path, 0777,true);
        }
        $filePath = self::createFilePath($path,$userId);
        self::saveImagePath($filePath,$url);
        return $filePath;
    }

    public static function getNumber($str) {
        preg_match_all('/\d+/', $str, $matches);
        return $matches[0];
    }

    public static function convertSlug($title)
    {
        $replacement = '-';
        $map = array();
        $quotedReplacement = preg_quote($replacement, '/');
        $default = array(
            '/??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???|??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???|??/' => 'a',
            '/??|??|???|???|???|??|???|???|???|???|???|??|??|???|???|???|??|???|???|???|???|???|??/' => 'e',
            '/??|??|???|???|??|??|??|???|???|??|??/' => 'i',
            '/??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???|??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???|??/' => 'o',
            '/??|??|???|???|??|??|???|???|???|???|???|??|??|???|???|??|??|???|???|???|???|???|??|??/' => 'u',
            '/???|??|???|???|???|???|??|???|???|???/' => 'y',
            '/??|??/' => 'd',
            '/??/' => 'c',
            '/??/' => 'n',
            '/??|??/' => 'ae',
            '/??/' => 'oe',
            '/??/' => 'ue',
            '/??/' => 'Ae',
            '/??/' => 'Ue',
            '/??/' => 'Oe',
            '/??/' => 'ss',
            '/[^\s\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]/mu' => ' ',
            '/\\s+/' => $replacement,
            sprintf('/^[%s]+|[%s]+$/', $quotedReplacement, $quotedReplacement) => '',
        );
        //Some URL was encode, decode first
        $title = urldecode($title);
        $map = array_merge($map, $default);
        return strtolower(preg_replace(array_keys($map), array_values($map), $title));
    }

    public static function checkLogin()
    {
        $CI = get_instance();
        if(isset($_COOKIE['user_id'])){
            $user_id = $_COOKIE['user_id'];
            $user_id = $CI->encryption->decrypt($user_id);
            $user_email = $_COOKIE['user_email'];
            $user_email = $CI->encryption->decrypt($user_email);
            // You may need to load the model if it hasn't been pre-loaded
            $CI->load->model('site_model');
            $data = $CI->site_model->checkUser('user',['id'=>$user_id,'email'=>$user_email])->row();
            $user = [
                'id'	=> $data->id,
                'username' => $data->username,
                'avatar'	=> $data->avatar,
                'active'=> 1,
            ];
            $_SESSION['user'] = $user;
        }
    }

    public static function setCookie($name,$value,$exprire=null)
    {
		$exprire =$exprire ? $exprire : time() + (86400 * 30);
		setcookie($name,$value,(int)$exprire,'/');
    }

    public static function unsetCookie($name){
        if(is_array($name)){
            foreach($name as $value){
                setcookie($value, '', time() - 3600, '/');
            }
        }else{
            setcookie($name, '', time() - 3600, '/');
        }
    }

    public static function make_avatar($userId,$character)
    {
        $path = 'images/avatar/'.date('Ym').'/';
        if (!file_exists($path)) {
            mkdir($path, 0777,true);
        }
        $filePath = self::createFilePath($path,$userId);
        $image = imagecreate(250, 250);
        $red = rand(0, 255);
        $green = rand(0, 255);
        $blue = rand(0, 255);
        imagecolorallocate($image, $red, $green, $blue);  
        $textcolor = imagecolorallocate($image, 255,255,255);  
        $font = realpath('assets/font/arial.ttf');
        imagettftext($image, 100, 0, 35, 175, $textcolor, $font, $character);  
        imagepng($image, $filePath);
        imagedestroy($image);
        return $filePath;
    }

    public static function getCharacterName($name)
    {
        $name = ucfirst($name);
        $character = '';
		$arrWords = preg_split("/(\s|\-|\.)/", $name);
        $firstName = substr(current($arrWords),0,1);
        $lastName = substr(end($arrWords),0,1);
        $character = $firstName.$lastName;
        return $character;
    }

}