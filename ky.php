<?php
/**
  * wechat php test
  */

//填入微信配置时的token
define("TOKEN", "chicklin");
$wechatObj = new wechatCallbackapiTest();
if(isset($_GET['echostr'])){
    $wechatObj->valid();
}else{
    $wechatObj->responseMsg();
}


class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature()
    {
        //一、实现
        //1、接受微信服务器GET请求过来的4个参数
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];    
                
        //$token = TOKEN;
        //二、加密/校验
        //1、将token、timestamp、nonce三个参数进行字典序排序
        $tmpArr = array(TOKEN, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);

        //2、将三个参数字符拼接成一个字符串进行sha1加密
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        
        //3、开发者获得加密后的字符串与signature对比
        if($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }

    public function responseMsg()
    {
        //预定义变量，获取原生POST数据，即获取微信服务器post过来的xml数据
        $postData = $GLOBALS["HTTP_RAW_POST_DATA"];

          //extract post data
        if (!empty($postData)){
                
                //把XML字符串载入对象中，通过对象属性访问xml的内容
                $xmlObj = simplexml_load_string($postData, 'SimpleXMLElement', LIBXML_NOCDATA);
                //访问属性
                $FromUserName = $xmlObj->FromUserName;
                $ToUserName = $xmlObj->ToUserName;
                $keyword = trim($xmlObj->Content);
                $MsgType = $xmlObj->MsgType;
                switch ($MsgType) {
                    case 'text':
                        //$keyword = $xmlObj->Content;
                        $this->receiveText($xmlObj);
                        break;
                    case 'image':
                        $picUrl = $xmlObj->PicUrl;
                        $mediaId = $xmlObj->MediaId;

                        $replyImageMsg = "<xml>
                                            <ToUserName><![CDATA[%s]]></ToUserName>
                                            <FromUserName><![CDATA[%s]]></FromUserName>
                                            <CreateTime>%s</CreateTime>
                                            <MsgType><![CDATA[image]]></MsgType>
                                            <Image>
                                                <MediaId><![CDATA[%s]]></MediaId>
                                            </Image>
                                        </xml>"
                        echo sprintf($replyImageMsg,$FromUserName,$ToUserName,time(),$mediaId);
                        break;
                    default:
                        # code...
                        break;
                }
                             
                // if(!empty($keyword))
                // {
                //       $msgType = "text";
                //     $contentStr = "<a href='http://39.108.229.63/Vtime/test.html'>点击查询志愿时</a>";
                //     $resultStr = sprintf($replyMsg, $FromUserName, $ToUserName, $time, $msgType, $contentStr);
                //     echo $resultStr;
                // }else{
                //     echo "Input something...";
                // }

        }else {
            echo "";
            exit;
        }
    }
    public function receiveText($obj){
        //$content = $obj->Content;
        $this->replyText($obj);
    }
    public function replyText($obj){
        //
        $replyTextMsg = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[text]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            <FuncFlag>0</FuncFlag>
                        </xml>";
        $msgType = "text";
        $contentStr = "<a href='http://www.chicklin.site/Vtime/test.html'>点击查询志愿时</a>";
        echo sprintf($replyTextMsg, $obj->FromUserName, $obj->ToUserName, time(), $contentStr);
    }
        
}

?>

