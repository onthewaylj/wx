<?php

header('Content-type:text');
define("TOKEN", "wxceshi");

$currentDir = dirname(__FILE__);
include_once $currentDir . '/libs/WX.php';

$wechatObj = new wechatCallbackapiTest();
/*第一次验证成功后即可屏蔽
if (!isset($_GET['echostr'])) {
	$wechatObj->responseMsg();
}else{
    $wechatObj->valid();
}*/
//$wechatObj->actionCreatemenu();//新建菜单
//$wechatObj->actionCreateTmpMaterials(0, $currentDir."/media/1.jpg");//创建临时素材 每次调用都产生一个新的media_id，获得后可以把方法关了
$wechatObj->responseMsg();



class wechatCallbackapiTest 
{
    
   /* 
   public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);//从小到达排序
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }*/
    //上传临时素材
    public function actionCreateTmpMaterials($type=0, $path){
        switch ($type) {
            case '1':
                $param_type = "thumb";
                break;
            case '2':
                $param_type = "voice";
                break;
            case '3':
                $param_type = "video";
                break;
            case '0':
                $param_type = "image";
                break;
        }
       $wx = new WX(); 
        $response = $wx->createTmpMaterial($param_type, $path);//返回字符串类型
        $response = json_decode($response);
        if(isset($response->errcode)){
            //$this->logger()
            return false;
        }
        echo $response->media_id;

    }
    public function responseMsg()
    {
        //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; //php7.0已经移除
        $postStr = file_get_contents("php://input"); 
        if (!empty($postStr)){
            //$this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text"://
                    $result = $this->receiveText($postObj);
                    break;
                case "image":
                    $result = $this->receiveImage($postObj);
                    break;
            }
           // $this->logger("T ".$result);
            echo $result;
        }else {
            echo "success";//"";
            exit;
        }
    }
    
    private function receiveEvent($object)
    {
        $content = "";
        switch ($object->Event)
        {
            case "subscribe":
                $content = "欢迎关注";
                break;
            case "unsubscribe":
                $content = "取消关注";
                break;
        }
        $result = $this->transmitText($object, $content);
        return $result;
    }
    
    //接收文本消息
    private function receiveText($object)
    {
        $keyword = trim($object->Content);//用户输入的信息

        if($keyword == '图文'){
            //图片连接有长度限制，太长无法返回正确结果
            $content = array(
                array('Title'=>"标题1",'Description'=>"描述1",'PicUrl'=>"http://841145365.applinzi.com/media/1.jpg",'Url'=>"https://zhidao.baidu.com/daily/view?id=61901"),
                array('Title'=>"标题2",'Description'=>"描述2",'PicUrl'=>"http://841145365.applinzi.com/media/2.jpg",'Url'=>"http://www.baidu.com")
            );

        }else if(in_array($keyword,['?','？']) ){//中英文问号
            $content = "[1] 消息提示\n[2] 新提示\n[3]测试测试";//返回给用户的信息
        
        }else if($keyword == '音乐'){
            //media_id没有也正常，但文档是必填
            $content = array("Title"=>"演员","Description"=>"描述描述描述",'MusicUrl'=>"http://841145365.applinzi.com/media/001.mp3","HQMusicUrl"=>"http://841145365.applinzi.com/media/001.mp3","media_id"=>"qRWc6rjdk2BInMkddZrIhvmt5hNO2UBBsyKE04SnOCOlpT_Kp--B9gVP2RUyEeW1");
        }else{
            $content = "您输入的是".$keyword;
        }
        
         
        if(is_array($content)){
            if (isset($content[0]['PicUrl'])){//图文

                $result = $this->transmitNews($object, $content);

            }else if (isset($content['MusicUrl'])){

                $result = $this->transmitMusic($object, $content);
            }
        }else {
            $result = $this->transmitText($object, $content);
        }

        return $result;
    }
    private function receiveImage($object){//用户发张图--》回张一样的图
        $media_id = trim($object->MediaId);
        return $this->transmitImage($object, $media_id);
    }
    //图片 
    private function transmitImage($object, $media_id){
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[image]]></MsgType>
                    <Image>
                    <MediaId><![CDATA[%s]]></MediaId>
                    </Image>
                    </xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $media_id);
        //无论用户发啥图，回张固定的图 $media_id：素材上传接口获得的media_id
        //$result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), "qRWc6rjdk2BInMkddZrIhvmt5hNO2UBBsyKE04SnOCOlpT_Kp--B9gVP2RUyEeW1");
        return $result;

    }
    //文本
    private function transmitText($object, $content)
    {
        $textTpl = "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[%s]]></Content>
                </xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }
    //回复图文消息
    private function transmitNews($object, $arr_item)
    {
        if(!is_array($arr_item))
            return;

       
        $item_str = "";
        /*// {$item['Title']}要加大括号
        foreach ($arr_item as $item){
         $item_str .= "<item>
                        <Title><![CDATA[{$item['Title']}]]></Title>
                        <Description><![CDATA[{$item['Description']}]]></Description>
                        <PicUrl><![CDATA[{$item['PicUrl']}]]></PicUrl>
                        <Url><![CDATA[{$item['Url']}]]></Url>
                    </item>";
        
        }*/
        $itemTpl = "<item>
                        <Title><![CDATA[%s]]></Title>
                        <Description><![CDATA[%s]]></Description>
                        <PicUrl><![CDATA[%s]]></PicUrl>
                        <Url><![CDATA[%s]]></Url>
                    </item>
                ";
        foreach ($arr_item as $item)
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
            
      

        $newsTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[news]]></MsgType>
                    
                    <ArticleCount>%s</ArticleCount>
                    <Articles>
                    $item_str
                    </Articles>
                    </xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($arr_item));
        return $result;
    }

    private function transmitMusic($object, $musicArray)
    {
        $itemTpl = "<Music>
                        <Title><![CDATA[%s]]></Title>
                        <Description><![CDATA[%s]]></Description>
                        <MusicUrl><![CDATA[%s]]></MusicUrl>
                        <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                       <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
                    </Music>";
 
        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl'], $musicArray['media_id']);

        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[music]]></MsgType>
                    $item_str
                    </xml>";

        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }
    /**
     * 创建自定义菜单
     * 
     * @param $token
     */
    public function actionCreatemenu()
    {

       
        $menu = '{
                     "button":[
                     {  
                          "type":"click",
                          "name":"今日歌曲",
                          "key":"V1001_TODAY_MUSIC"
                      },
                      {
                           "name":"菜单",
                           "sub_button":[
                           {    
                               "type":"view",
                               "name":"搜索",
                               "url":"http://www.soso.com/"
                            },
                           
                            {
                               "type":"click",
                               "name":"赞一下我们",
                               "key":"V1001_GOOD"
                            }]
                       },
                       {
                          "type": "view",
                          "name": "产品中心",
                          "url": "http://www.baidu.com"
                       }]
                }';
            $wx = new WX();
            echo  $wx->createmenu($menu);


    }
    
}



