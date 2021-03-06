<?php

class response{
    const
    HTTP_100='Continue',
    HTTP_101='Switching Protocols',
    HTTP_200='OK',
    HTTP_201='Created',
    HTTP_202='Accepted',
    HTTP_203='Non-Authorative Information',
    HTTP_204='No Content',
    HTTP_205='Reset Content',
    HTTP_206='Partial Content',
    HTTP_300='Multiple Choices',
    HTTP_301='Moved Permanently',
    HTTP_302='Found',
    HTTP_303='See Other',
    HTTP_304='Not Modified',
    HTTP_305='Use Proxy',
    HTTP_306='Temporary Redirect',
    HTTP_400='Bad Request',
    HTTP_401='Unauthorized',
    HTTP_402='Payment Required',
    HTTP_403='Forbidden',
    HTTP_404='Not Found',
    HTTP_405='Method Not Allowed',
    HTTP_406='Not Acceptable',
    HTTP_407='Proxy Authentication Required',
    HTTP_408='Request Timeout',
    HTTP_409='Conflict',
    HTTP_410='Gone',
    HTTP_411='Length Required',
    HTTP_412='Precondition Failed',
    HTTP_413='Request Entity Too Large',
    HTTP_414='Request-URI Too Long',
    HTTP_415='Unsupported Media Type',
    HTTP_416='Requested Range Not Satisfiable',
    HTTP_417='Expectation Failed',
    HTTP_500='Internal Server Error',
    HTTP_501='Not Implemented',
    HTTP_502='Bad Gateway',
    HTTP_503='Service Unavailable',
    HTTP_504='Gateway Timeout',
    HTTP_505='HTTP Version Not Supported';
    protected static $_lastModified = null;
    protected static $_headersSent = false;
    public static function magic($magic, $context = null){
        static $map = array(
    'html/head'=>'head.php',
    'html/header'=>'header.php',
    'html/footer'=>'footer.php'
        );
        // setup default responses
        $args = func_get_args();
        array_shift($args);
        array_unshift($args, false);
        array_unshift($args, $magic);
        $path = dirname(__FILE__).'/_html/';
        magic::set('title', magic::get('title', 'Untitled page')); // LOL
        if (is_int($magic)){
            $file = $magic.'.php';
            if ($magic > 300 && $magic < 400){
                //$location = $context;
                $file = '30x.php';
            }
            $file = $path.$file;
            if (!is_file($file))
                $file = $path.'500.php';
            magic::set($magic, $file);
            return call_user_func_array(array('magic', 'call'), $args);
        }
        if (isset($map[$magic])){
            magic::set($magic, $path.$map[$magic]);
            return call_user_func_array(array('magic', 'call'), $args);
        }
    }
    protected static $_mime = 'text/html';
    protected static $_charset = 'utf-8';
    public static function xml(){
        self::setMime('text/xml');
    }
    public static function setMime($mime){
        self::$_mime = $mime;
        self::_sendContentType();
    }
    public static function setCharset($charset){
        self::$_charset = $charset;
        self::_sendContentType();
    }
    private static function _sendContentType(){
        if (!request::isCli()){
            header("Content-Type: ".self::$_mime."; charset=".self::$_charset);
        }
    }
    public static function setStatus($code){
        if (!request::isCli()){
            $message = constant('self::HTTP_'.$code);
            header($_SERVER['SERVER_PROTOCOL'].' '.$code.' '.$message);
            magic::set('http/code', $code);
            magic::set('http/message', $message);
        }
    }
    public static function http($code, $location = null){
        self::setStatus($code);
        if ($location !== null){
            header('Location: '.$location, true, $code);
        }
        if (!magic::get('css')){
            self::css('body{background:#fff;color:#000;font-family:sans-serif;font-size: 12px;}');
            self::css('a{color:#00c}');
            self::css('a:visited{color:#551a8b}');
            self::css('a:hover{color:#f00}');
        }
        magic::call($code, array('response', 'magic'), $location);
        exit;
    }
    private static function _preventRedirectLoop($location){
        if (request::getMethod() == 'GET' &&
                (isset($_SERVER['HTTP_REFERER']) && $location == $_SERVER['HTTP_REFERER']) &&
                $location == $_SERVER['REQUEST_URI']){
            self::http(500); // dirty enough
        }
    }
    public static function redirect($location){
        request::getMethod() == 'GET'?
                        self::movedPermanently($location):
                        self::seeOther($location);
    }
    public static function css($value){
        magic::append('css', $value);
    }
    public static function back(){
        if (isset($_SERVER['HTTP_REFERER'])){
            self::redirect($_SERVER['HTTP_REFERER']);
        }
        self::http(500);
    }
    public static function sendHeaders(){
        if (self::$_headersSent){
            //throw new Exception('headers already sent');
            return;
        }
        if (self::$_lastModified !== null){
            header('Last-Modified: '.gmdate("D, d M Y H:i:s T", self::$_lastModified));
            //header('X-Last-Modified: '.gmdate("D, d M Y H:i:s T", self::$_lastModified));
            $ifModifiedSince = request::getHttpHeader('If-Modified-Since', false);
            if ($ifModifiedSince){
                if ($ifModifiedSince == gmdate('D, d M Y H:i:s T', self::$_lastModified)){
                    self::notModified();
                }
            }
        }
        self::$_headersSent = true;
    }
    public static function movedPermanently($location){
        self::_preventRedirectLoop($location);
        self::http(301, $location);
    }
    public static function seeOther($location){
        self::_preventRedirectLoop($location);
        self::http(303, $location);
    }
    public static function notModified(){
        self::http(304);
    }
    public static function forbidden(){
        self::http(403);
    }
    public static function notFound(){
        self::http(404);
    }
    public static function unauthorized(){
        self::http(401);
    }
    public static function getLastModified(){
        return self::$_lastModified;
    }
    public static function modifiedSince($timestamp){
        self::$_lastModified = max(self::$_lastModified, $timestamp);
    }
    public static function noCache($modify = true){
        if ($modify){
            self::modifiedSince(time());
        }else{
            header('Last-Modified: '.gmdate("D, d M Y H:i:s T"));
        }
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache"); // HTTP/1.0
        session_cache_limiter("nocache");
    }
}