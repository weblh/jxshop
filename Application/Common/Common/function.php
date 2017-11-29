<?php

//发送邮件
function sendemail($to,$subject,$body) {
	require '../PHPMailer/class.phpmailer.php';
	$mail             = new PHPMailer();
	/*服务器相关信息*/
	$mail->IsSMTP();                        
	$mail->SMTPAuth   = true;               
	$mail->Host       = 'smtp.163.com';   	   
	$mail->Username   = 'xyd_lh';  		
	$mail->Password   = 'shouquanma123';
	/*内容信息*/
	$mail->IsHTML(true);
	$mail->CharSet    ="UTF-8";			
	$mail->From       = 'xyd_lh@163.com';	 		
	$mail->FromName   ="京西商城";	
	$mail->Subject    = $subject; 
	$mail->MsgHTML($body);
   

	$mail->AddAddress($to);  
	$mail->Send();
}

//生成商品列表中的链接地址
function myU($name,$value) {
	//获取当前浏览器上已经拥有的属性值参数信息
	$attr = I('get.attr');
	if ($name=='sort') {
		//将目前的排序字段保存到$sort变量中
		$sort = $value;
		$price = I('get.price');//获取浏览器上已有的条件信息
	}elseif ($name=='price'){
		//将目前价格信息保存到变量中
		$price = $value;
		$sort = I('get.sort');
	}elseif ($name=='attr'){
		//根据属性值生成链接地址
		//可以实现使用多个属性值作为条件
		if (!$attr) {
			$attr = $value;
		}else{
			//说明目前已经拥有了属性值对应的条件
			//将已经拥有的属性值参数信息转换为数组
			$attr = explode(',', $attr);
			$attr[] = $value;
			//对目前已经拥有的属性值信息进行去重操作
			$attr = array_unique($attr);
			$attr = implode(',', $attr);
		}
	}

	return U('Category/index').'?id='.I('get.id').'&sort='.$sort.'&price='.$price.'&attr='.$attr;
}


//发送短信验证码       
function sendTemplateSMS($to,$datas,$tempId)
{
	include_once("../sms/CCPRestSmsSDK.php");

	//主帐号,对应开官网发者主账号下的 ACCOUNT SID
	$accountSid= '8a216da85f5c89b1015f9b0c39e31d04';

	//主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
	$accountToken= '6df60492aa394a73b15640cd2dff8bce';

	//应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
	//在开发调试的时候，可以使用官网自动为您分配的测试Demo的APP ID
	$appId='8aaf07085f5c54cf015f9e69fabd1991';

	//请求地址
	//沙盒环境（用于应用开发调试）：sandboxapp.cloopen.com
	//生产环境（用户应用上线使用）：app.cloopen.com
	$serverIP='sandboxapp.cloopen.com';


	//请求端口，生产环境和沙盒环境一致
	$serverPort='8883';

	//REST版本号，在官网文档REST介绍中获得。
	$softVersion='2013-12-26';

	
     $rest = new REST($serverIP,$serverPort,$softVersion);
     $rest->setAccount($accountSid,$accountToken);
     $rest->setAppId($appId);
    
     // 发送模板短信
     $result = $rest->sendTemplateSMS($to,$datas,$tempId);
     if($result == NULL ) {
         return false;
     }
     if($result->statusCode!=0) {
         return false;
     }
     
     return true;
}

//发送请求的函数
//$url 具体请求的url地址
//$data 具体请求所携带的参数
//$method 具体的请求方式（post/get）默认get
function http_curl($url,$data=array(),$method='get') {
	if (!function_exists('curl_init')) {
		//表示目前curl扩展没开
		echo "curl扩展没有开启";exit;
	}
	//打开会话
	$ch = curl_init();
	//增加密钥
	//base64_encode函数可以将加密之后的字符串转换一下，防止影响具体的参数
	$data['formid'] = base64_encode(authcode('pc','ENCODE'));
	//设置参数信息
	if ($method=='post') {
		//设置为post请求
		curl_setopt($ch, CURLOPT_POST, true);
		//设置请求的参数
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}else{
		//对于get方式的参数信息可以直接将参数增加到url地址中
		$url .= '&'.http_build_query($data);
	}
	//设置具体请求的具体url地址
	curl_setopt($ch, CURLOPT_URL, $url);
	//设置得到结果不进行输出
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//执行具体的请求
	$res = curl_exec($ch);
	//关闭会话
	curl_close($ch);
	//将得到的结果转换为数组格式然后返回
	return json_decode($res,true);
}

//调用接口进行数据操作
//$data 具体请求所携带的参数
//$method 具体的请求方式（get/post）默认值get方式
function get_data($data=array(),$method='get') {
	//有时未指定控制器和方法名，则直接使用项目中同名的控制器和方法名
	if (!$data['c']) {
		//没有指定具体的接口控制器名称
		$data['c'] = CONTROLLER_NAME;
	}
	if (!$data['c']) {
		//没有指定具体的方法名称
		$data['a'] = ACTION_NAME;
	}
	
	//需要考虑到有时调用的接口并不是自己的api接口
	if ($data['url']) {
		$url = $data['url'];
	}else{
		$url = 'http://api.com/index.php?m=Home&c='.$data['c'].'&a='.$data['a'];
	}
	unset($data['c']);
	unset($data['a']);
	unset($data['url']);
	//请求某个接口
	$res = http_curl($url,$data,$method);
	return $res;
}

//加密解密函数
//$string 具体的加密解密字符串
//$operation 加密还是解密
//$key 代表的就是具体的加密解密密钥
//$expiry 代表具体密文的有效时间，时间一过就不能正常反解
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) 
{  
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙  
    $ckey_length = 4;  
      
    // 密匙  
    $key = md5($key ? $key : 'abc');  
      
    // 密匙a会参与加解密  
    $keya = md5(substr($key, 0, 16));  
    // 密匙b会用来做数据完整性验证  
    $keyb = md5(substr($key, 16, 16));  
    // 密匙c用于变化生成的密文  
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';  
    // 参与运算的密匙  
    $cryptkey = $keya.md5($keya.$keyc);  
    $key_length = strlen($cryptkey);  
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性  
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确  
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;  
    $string_length = strlen($string);  
    $result = '';  
    $box = range(0, 255);  
    $rndkey = array();  
    // 产生密匙簿  
    for($i = 0; $i <= 255; $i++) 
    {  
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);  
    }  
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度  
    for($j = $i = 0; $i < 256; $i++) 
    {  
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;  
        $tmp = $box[$i];  
        $box[$i] = $box[$j];  
        $box[$j] = $tmp;  
    }  
    // 核心加解密部分  
    for($a = $j = $i = 0; $i < $string_length; $i++) 
    {  
        $a = ($a + 1) % 256;  
        $j = ($j + $box[$a]) % 256;  
        $tmp = $box[$a];  
        $box[$a] = $box[$j];  
        $box[$j] = $tmp;  
        // 从密匙簿得出密匙进行异或，再转成字符  
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));  
    }  
    if($operation == 'DECODE') 
    {  
        // substr($result, 0, 10) == 0 验证数据有效性  
        // substr($result, 0, 10) - time() > 0 验证数据有效性  
        // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性  
        // 验证数据有效性，请看未加密明文的格式  
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) 
        {  
            return substr($result, 26);  
        } else {  
            return '';  
        }  
    } else {  
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因  
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码  
        return $keyc.str_replace('=', '', base64_encode($result));  
    }  
}