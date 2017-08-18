<?php
/**
*  
*/
#use DB;

class WX{

    private $appId = "wx2ba827c199fd52d9";
    private $appSecret = "d8eee3610974289d2880cc55a623caca";
    private $db ;
    private $access_token ;
    private $apiUrl = [
        'access_token' => 'https://api.weixin.qq.com/cgi-bin/token',
        'create_menu' => "https://api.weixin.qq.com/cgi-bin/menu/create",
        'create_tmp_media'=> "https://api.weixin.qq.com/cgi-bin/media/upload"
    ];

	public function __construct(){
      //  $this->db = new DB("dbname",'127.0.0.1','username','password');
       /* $response = $this->getDbAccessToken();
        if($response)
            $this->access_token =  $response['access_token'];*/
        $this->access_token = "yj6RzCDroGTsuG9KLa80-d8pDXUVsILFrU4ZoT3-h3DGy-3LWdbNUapelFlcuyf5kiog9_pBOqbrBCU1wRhXjVePGj6xfQsm1dPg3qlOULsjR5el9OkS9_32Nn78a67vQIFaAGAASP";
    }
   /**
     * 模拟发送请求
     * @param $url 请求的url地址
     * @param array $param 请求参数
     * @param string $method 请求方法
     * @return mixed 请求返回
     */
    public function request($url, $param = array(), $method = 'get')
    {
        $resource = curl_init();
        $queryString = '';
        if (!empty($param) && is_array($param)) {
            $seg = array();
            foreach ($param as $k => $v) {
                $seg[] = "{$k}={$v}";
            }
            $queryString = join('&', $seg);
        }
        if (strtolower($method) == 'post') {
            curl_setopt($resource, CURLOPT_POSTFIELDS, $queryString);
            curl_setopt($resource, CURLOPT_POST, true);
        }
        if (strtolower($method) == 'get') {
            $url .= '?' . $queryString;
        }
        curl_setopt($resource, CURLOPT_URL, $url);
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);//数据不输出到页面
        curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, false);//不校验ssl
        $data = curl_exec($resource);
        curl_close($resource);
        return $data;
    }
    
    /**
     * 原始原始POST
     * @param $url 请求的url地址
     * @param $raw 原始数据，可以为字符串或数组
     * @return mixed 返回请求值
     */
    public function requestPost($url, $raw)
    {
        $resource = curl_init();
        curl_setopt($resource, CURLOPT_POST, true);
        curl_setopt($resource, CURLOPT_URL, $url);
        curl_setopt($resource, CURLOPT_POSTFIELDS, $raw);
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, false);
        
        $data = curl_exec($resource);
        if (curl_errno($resource)) {//返回最后一次curl操作的错误号
          return 'Errno'.curl_error($curl);
        }
        curl_close($resource);
        return $data;
    }
	public function requestGet($url,$params)
	{
        $result = [];
        try{
            $ch = curl_init();
            
            $params_url = $url."?".http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $params_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);
           
            curl_close($ch);
            

        }catch(Exception $e){

            echo 'curl error !'.$e->getMessage();
        }
        return $result;
        
	}
    public function getAccessToken(){
        $url = $this->apiUrl['access_token'];
        $param = array(
                'grant_type' => 'client_credential',
                'appid' => $this->appId,
                'secret' => $this->appSecret
        );
        $result = $this->requestGet($url, $param);
        $res = json_decode($result, true);
        if (isset($res['errcode'])) {
            return false;
        }
        $this->setDbAccessToken($res);
        return $res;
    }
    //每次去数据库拿最新一条的token记录(写个脚本每1小时跑一次，把取回的token存入数据库)
    public function getDbAccessToken(){
        
       /* $sql =  "select token from access_token order by creatime desc limit 1";//token表 id token expire_in createtime
        $row = $this->db->getRow($sql);
        return $row['token'];*/
        if(file_exists("access_token.txt")){
            $str = file_get_contents("access_token.txt");
            $arr = json_decode($str, true);//第二个参数为true :转成array
            if($arr['expires_in'] - 1200 < time())
                $this->getAccessToken();
            else 
                return $arr;
        }else{
            $this->getAccessToken();
        }
        
        
        
    }
    //将获得的access_token存到数据库
    public function setDbAccessToken($value)
    {
        /*//存入数据库
        $arr = ['token'=>$value['access_token'],'expire_in'=>$value['expires_in']];
        $this->db->insert("token",$arr);*/
        //存入文件
        $value['expires_in'] = $value['expires_in']  + time();
        $str = json_encode($value);
        file_put_contents("access_token.txt",$str);
    }

    //日志
    public function logger($log_content)
    {
        if(isset($_SERVER['HTTP_APPNAME'])){   //SAE
          
            sae_set_display_errors(false);//关闭信息输出  
            sae_debug($log_content);//记录日志  
            sae_set_display_errors(true);//记录日志后再打开信息输出，否则会阻止正常的错误信息的显示  
        
        }else if($_SERVER['REMOTE_ADDR'] != "127.0.0.1"){ //LOCAL
         
            $max_size = 10000;
            $log_filename = "log.xml";
            if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){
               
                unlink($log_filename);
            }
            
            file_put_contents($log_filename, date('H:i:s')." ".$log_content."\r\n", FILE_APPEND);
        
        }
    }

    //创建菜单
    public function createMenu($data){

        $url = $this->apiUrl['create_menu']."?access_token=".$this->access_token;
        $response = $this->requestPost($url, $data);
        $res = json_decode($response);
        if($res->errcode != 0){
            $this->logger("错误：".$response->errcode.";提示消息：".$response->errmsg);
        }
        return $response;

    }
    //新增临时素材
    public function createTmpMaterial($type, $media_path){
        $url = $this->apiUrl['create_tmp_media']."?access_token=".$this->access_token."&type=".$type;
        $data['media'] = curl_file_create($media_path);//上传媒体文件
        $result = $this->requestPost($url, $data);//返回string类型
        return $result;

    }

}
