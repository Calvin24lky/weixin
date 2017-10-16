<?php

define("TOKEN","chicklin");

class wechatCallbackapiTest{
    public function valid()
    { $echoStr = $_GET["echostr"];
          if($this->checkSignature())
           {
           echo $echoStr;
           exit;           
           }
    }
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        if( sha1(implode($tmpArr)) == $signature)
        {
            return true;
        }else{
            return false;
        } 
    }
}

$wechatObj = new wechatCallbackapiTest();
$wechatObj->valid();
?>
