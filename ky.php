<?php
	/**
	  * wechat php test
	  */

	//define your token
	define("TOKEN", "chicklin");
	$wechatObj = new wechatCallbackapiTest();
	if(isset($_GET['echostr']))
	{
	    $wechatObj->valid();
	}
	else
	{
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
        
    //检验微信加密签名Signature
    private function checkSignature()
    {
        $signature = $_GET["signature"];//微信加密签名
        $timestamp = $_GET["timestamp"];//时间戳
        $nonce = $_GET["nonce"];//随机数
        
        //加密校验
        //将token、timestamp、nonce三个参数进行字典序排序        
        $tmpArr = array(TOKEN, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);

        //将三个参数字符串拼接成一个字符串进行sha1加密
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        
        //开发者获得加密后的字符串与signature对比
        if($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }

    //响应消息
    public function responseMsg()
    {
    	//接收xml数据包
    	$postData = $GLOBALS["HTTP_RAW_POST_DATA"];//全局变量

    	//处理xml数据包，获得xml对象，访问对象的属性
    	$xmlObj = simplexml_load_string($postData,"SimpleXMLElement",LIBXML_NOCDATA);

    	//获取属性
    	$toUserName = $xmlObj->ToUserName;//获取开发者微信号
    	$fromUserName = $xmlObj->FromUserName;//获取用户的openid
    	$msgType = $xmlObj->MsgType;//消息的类型

    	//根据消息类型进行业务处理
    	switch ($msgType) {
    		case 'text':
    			//接受文本消息
    			echo $this->receiveText($xmlObj);
    			break;

    		case 'image':

    			//接受图片消息
    			echo $this->receiveImage($xmlObj);
    			break;
    		
    		default:
    			# code...
    			break;
    	}
    }

    //接受文本消息
    public function receiveText($obj)
    {
    	$content = $obj->Content;//获取文本消息的内容
    	$replyStr = "<a href='http://39.108.229.63/Vtime/test.html'>点击查询志愿时</a>";
    	return $this->replyText($obj,$replyStr);
    }

    //回复文本消息
    public function replyText($obj,$contentStr)
    {
	    $replyTextMsg = "<xml>
			                <ToUserName><![CDATA[%s]]></ToUserName>
			                <FromUserName><![CDATA[%s]]></FromUserName>
			                <CreateTime>%s</CreateTime>
			                <MsgType><![CDATA[text]]></MsgType>
			                <Content><![CDATA[%s]]></Content>
		                </xml>";
		return sprintf($replyTextMsg, $obj->FromUserName, $obj->ToUserName, time(), $contentStr);
    }

    public function receiveImage($obj)
    {
    	$picUrl = $obj->PicUrl;//获取图片的URL
    	$mediaId = $obj->MediaId;//获取图片消息媒体id
    	$picArr = array('picUrl'=>$picUrl,'mediaId'=>$mediaId);
    	return $this->replyImage($obj,$picArr);
    }

    public function replyImage($obj,$array)
    {
    	//回复图片消息
    	$replyImageMsg = "<xml>
			                <ToUserName><![CDATA[%s]]></ToUserName>
			                <FromUserName><![CDATA[%s]]></FromUserName>
			                <CreateTime>%s</CreateTime>
			                <MsgType><![CDATA[image]]></MsgType>
			                <Image>
			                	<MediaId><![CDATA[%s]]></MediaId>
			                </Image>
		                </xml>";
		return sprintf($replyImageMsg,$obj->FromUserName,$obj->ToUserName,time(),$array['mediaId']);
    }
}

?>

