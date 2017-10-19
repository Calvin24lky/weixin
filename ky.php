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
        if($this->checkSignature())
        {
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
        if($tmpStr == $signature)
        {
            return true;
        }
        else
        {
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
    	switch ($msgType) 
        {
    		case 'event':
    			echo $this->receiveEvent($xmlObj);
    			break;

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
    	//$content = trim($obj->Content);//获取文本消息的内容
        $content = str_replace(" ","",$obj->Content);
        $content = str_replace("+","",$content);
        $keyword = mb_substr($content,0,2,'utf-8');//截取回复消息的前两个中文
    	switch ($keyword) 
        {
    		case '功能':
    			$replyStr = "查询功能介绍(回复关键字)：\n1、志愿时\n2、天气+城市名\n3、快递+单号\n4、\n5";
    			return $this->replyText($obj,$replyStr);
    			break;

    		case '志愿':
    			$replyStr = "<a href='http://www.chicklin.site/Vtime/test.html'>点击查询志愿时</a>";
    			return $this->replyText($obj,$replyStr);
    			break;

            case '天气':
                $cityname = mb_substr($content,2,6,'utf-8');//获得城市名
                $weather_json = $this->getWeather($cityname);
                if (json_decode($weather_json)->{'status'} == 0) 
                {
                    $replyStr = $this->replyWeather($weather_json);
                    return $this->replyText($obj,$replyStr);
                }
                else
                {
                    $replyStr = "输入数据有误，请重新输入";
                    return $this->replyText($obj,$replyStr);
                }
                break;

            case '快递':
                $code = mb_substr($content,2,NULL,'utf-8');
                $express_json = $this->getExpress($code);
                if (json_decode($express_json)->{'status'} == 0) 
                {
                    $replyStr = $this->replyExpress($express_json);
                    return $this->replyText($obj,$replyStr);
                }
                else
                {
                    $replyStr = "输入数据有误，请重新输入";
                    return $this->replyText($obj,$replyStr);
                }
                break;

            case '比赛':
                $appkey = "ce20cdb1a57b90202c86d253a6fa469d";
                $url = "http://op.juhe.cn/onebox/basketball/nba";
                $params = array(
                      "key" => $appkey,//应用APPKEY(应用详细页查询)
                      "dtype" => "json",//返回数据的格式,xml或json，默认json
                );
                $paramstring = http_build_query($params);
                $nba_json = $this->getNBA($url,$paramstring);
                $result = json_decode($nba_json,true);
                if($result)
                {
                    if($result['error_code']=='0')
                    {
                        //查询成功
                        $replyStr = $this->replyNBA($nba_json);
                        return $this->replyText($obj,$replyStr);
                    }
                    else
                    {
                        $replyStr = "查询出错，请重新输入";
                        return $this->replyText($obj,$replyStr);
                    }
                }
                else
                {
                    $replyStr = "查询出错，请重新输入";
                    return $this->replyText($obj,$replyStr);
                }
                break;
    		
    		default:
    			$replyStr = "回复'功能'查看我的功能吧~";
    			return $this->replyText($obj,$replyStr);
    			break;
    	}
    	
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
    //接受图片消息
    public function receiveImage($obj)
    {
    	$picUrl = $obj->PicUrl;//获取图片的URL
    	$mediaId = $obj->MediaId;//获取图片消息媒体id
    	$picArr = array('picUrl'=>$picUrl,'mediaId'=>$mediaId);
    	return $this->replyImage($obj,$picArr);
    }
    //回复图片消息
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
    //接受事件消息
    public function receiveEvent($obj)
    {
		switch ($obj->Event) 
        {
			case 'subscribe':
				$replyContent = "欢迎关注！回复“功能”看看我能干什么吧！";
				return $this->replyText($obj,$replyContent);
				break;
			
			default:
				# code...
				break;
		}
    }
    //获取天气json
    public function getWeather($cityname)
    {
        $host = "http://jisutianqi.market.alicloudapi.com";
        $path = "/weather/query";
        $method = "GET";
        $appcode = "5f8abfa54bed4f128d1fb245a13a6460";//7830cf6348c444d1a1455e70fb5f434e
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "city={$cityname}&citycode=citycode&cityid=cityid&ip=ip&location=location";
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        //var_dump(curl_exec($curl));
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
    //回复天气消息
    public function replyWeather($weather_json)
    {
        $arr = json_decode($weather_json);
        $result = $arr->{"result"};
        $replyText = "城市: ".$result->{'city'}."\n更新时间: ".$result->{'updatetime'}."\n----------\n"."1、天气速览\n天气: ".$result->{'weather'}."\n温度: ".$result->{'temp'}."°C (最高温: ".$result->{'temphigh'}."°C 最低温: ".$result->{'templow'}."°C)\n湿度: ".$result->{'humidity'}."%\n风力: ".$result->{'winddirect'}.$result->{'windpower'}."\n空气: ".$result->{'aqi'}->{'quality'}."\n".$result->{'index'}[2]->{'iname'}.": ".$result->{'index'}[2]->{'ivalue'}."\n----------\n"."2、空气质量\nPM2.5: ".$result->{'aqi'}->{'pm2_5'}."\nPM10: ".$result->{'aqi'}->{'pm10'}."\nSO2: ".$result->{'aqi'}->{'so2'}."\nNO2: ".$result->{'aqi'}->{'no2'}."\nCO: ".$result->{'aqi'}->{'co'}."\n指数: ".$result->{'aqi'}->{'aqiinfo'}->{'level'}."\n影响: ".$result->{'aqi'}->{'aqiinfo'}->{'affect'}."\n----------\n"."3、未来6小时天气: \n".$result->{'hourly'}[0]->{'time'}.' '.$result->{'hourly'}[0]->{'weather'}.' '.$result->{'hourly'}[0]->{'temp'}."°C\n".$result->{'hourly'}[1]->{'time'}.' '.$result->{'hourly'}[1]->{'weather'}.' '.$result->{'hourly'}[1]->{'temp'}."°C\n".$result->{'hourly'}[2]->{'time'}.' '.$result->{'hourly'}[2]->{'weather'}.' '.$result->{'hourly'}[2]->{'temp'}."°C\n".$result->{'hourly'}[3]->{'time'}.' '.$result->{'hourly'}[3]->{'weather'}.' '.$result->{'hourly'}[3]->{'temp'}."°C\n".$result->{'hourly'}[4]->{'time'}.' '.$result->{'hourly'}[4]->{'weather'}.' '.$result->{'hourly'}[4]->{'temp'}."°C\n".$result->{'hourly'}[5]->{'time'}.' '.$result->{'hourly'}[5]->{'weather'}.' '.$result->{'hourly'}[5]->{'temp'}."°C"."\n----------\n"."3、未来3天天气: \n".$result->{'daily'}[1]->{'date'}.' '.$result->{'daily'}[1]->{'week'}.' '.$result->{'daily'}[1]->{'day'}->{'weather'}.' '.$result->{'daily'}[1]->{'night'}->{'templow'}.'-'.$result->{'daily'}[1]->{'day'}->{'temphigh'}."°C\n".$result->{'daily'}[2]->{'date'}.' '.$result->{'daily'}[2]->{'week'}.' '.$result->{'daily'}[2]->{'day'}->{'weather'}.' '.$result->{'daily'}[2]->{'night'}->{'templow'}.'-'.$result->{'daily'}[2]->{'day'}->{'temphigh'}."°C\n".$result->{'daily'}[3]->{'date'}.' '.$result->{'daily'}[3]->{'week'}.' '.$result->{'daily'}[3]->{'day'}->{'weather'}.' '.$result->{'daily'}[3]->{'night'}->{'templow'}.'-'.$result->{'daily'}[3]->{'day'}->{'temphigh'}."°C\n";
        return $replyText;
    }
    //获取快递json
    public function getExpress($code)
    {
        $host = "http://jisukdcx.market.alicloudapi.com";
        $path = "/express/query";
        $method = "GET";
        $appcode = "7830cf6348c444d1a1455e70fb5f434e";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "number={$code}&type=auto";
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
    //回复快递消息
    public function replyExpress($express_json)
    {
        $result = json_decode($express_json);
        $arr = $result->{"result"}->{'list'};
        $replyText = '';
        for ($i=0; $i < count($arr); $i++) 
        { 
            $replyText .= $arr[$i]->{'time'}." ".$arr[$i]->{'status'}."\n";
        }
        return $replyText;
    }

    function getNBA($url,$params=false,$ispost=0)
    {
        $httpInfo = array();
        $ch = curl_init();
     
        curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
        curl_setopt( $ch, CURLOPT_USERAGENT , 'JuheData' );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 60 );
        curl_setopt( $ch, CURLOPT_TIMEOUT , 60);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //若为post请求
        if( $ispost )
        {
            curl_setopt( $ch , CURLOPT_POST , true );
            curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
            curl_setopt( $ch , CURLOPT_URL , $url );
        }
        else
        {
            if($params){
                curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
            }else{
                curl_setopt( $ch , CURLOPT_URL , $url);
            }
        }
        $response = curl_exec( $ch );
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
        $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
        curl_close( $ch );
        return $response;
    }

    public function replyNBA($nba_json)
    {
        $result = json_decode($nba_json,true);
        $str = "";
        $arr = $result['result']['list'];
        for ($i=0; $i < count($arr); $i++) 
        { 
            $tr_len = count($arr[$i]['tr']);
            for ($j=0; $j < $tr_len; $j++) 
            { 
                $status = $arr[$i]['tr'][$j]['status'];
                $str .= $arr[$i]['tr'][$j]['time'].' '.$arr[$i]['tr'][$j]['player1'].'-'.$arr[$i]['tr'][$j]['player2'].' '.$arr[$i]['tr'][$j]['score'];
                switch ($status) 
                {
                    case '0':
                        $str .= " 未开始\n";
                        break;
                    case '1':
                        $str .= " 进行中\n";
                        break;
                    case '2':
                        $str .= " 已结束\n";
                        break;
                    default:
                        $str .= " 数据出错\n";
                        break;
                }
            }
        }
        return $str;
    }

}

?>

