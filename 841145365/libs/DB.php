<?php
/**
*   功能：数据库操作类DB，包含对数据库中表的添加、删除、更新、查询的功能
*/
class DB
{
	static $link=null;  //mysql服务器连接的资源
	private $host="";   //mysql服务器主机名
	private $user="";   //mysql的用户名
	private $password="";  //mysql的密码
	private $db="";     //访问的数据库名


	//构造方法
	function __construct($db='',$host="127.0.0.1",$user="root",$password=''){
		 
		 $this->host=$host;
		 $this->user=$user;
		 $this->password=$password;
		 $this->db=$db;
		 if(self::$link == null)
		 {
			self::$link = mysql_connect($this->host, $this->user,$this->password);
			mysql_select_db($this->db,self::$link);
			$this->query('SET NAMES utf8');
		 }  
	}


	/**
	* 功能：选择操作的数据库
	*
	*/
	function selectDb($db){
		return mysql_select_db($db) or die('选择数据库失败'.mysql_error());
	}


	/**
	*  功能：执行查询语句，返回查询结果集中的第一行数据的关联数组（默认）
	*  参数：$sqlStr  mysql语句的字符串   $result_type 数组的类型,默认MYSQL+ASSOC  
	*  返回值： 返回查询结果集中的第一行数据的关联数组（默认）
	*  查询一条
	*/
	function getRow($sqlStr,$result_type=MYSQL_ASSOC)
	{
		$rs=@mysql_query($sqlStr,self::$link) or $this->showError("查询语句有错误！$sqlStr");
		if($rs)
		{
			$row=@mysql_fetch_array($rs,$result_type);
			return $row;
		}

		return false;
	}


	/**
	*   功能：获得执行mysql查询语句的关联数组
	*   参数：$sql 完整的mysql语句  $result_type  返回的查询结果数组的类型，默认为MYSQL_ASSOC 
	*   返回值：表中数据的关联数组(默认)
	*   查询所有的
	*/
	function getAll($sql,$result_type=MYSQL_ASSOC)
	{
		$rs=@mysql_query($sql,self::$link) or $this->showError("查询语句有错误！$sql");
		if($rs===false)
		{
			return false;
		}
		else if($rs===true){
			return true;
		}
		else{
			$arr=array();
			while($row=@mysql_fetch_array($rs,$result_type))
			{
				$arr[]=$row;
			}
			return $arr;
		}
	}


	/**
	*  功能：获得执行mysql查询语句后的表中数据的对象数组
	*  参数：$sql  完整的mysql语句
	*  返回值：表中数据的对象数组
	* 
	*/
	function getObjects($sql){
		 $rs=mysql_query($sql,self::$link) or $this->showError("查询语句有错误！");
		 if($rs===false){
		 	return false;
		 }
		 $arr=array();
		 while($row=@mysql_fetch_object($rs)){
			
			$arr[]=$row;
		 
		 }
		 return $arr;
	}


    /**
    * 功能：执行一个完整的sql语句
    * 参数：$sql , 一个完整的mysql语句字符串
    * 返回值：执行sql语句的结果 resource类型
    * 
    */
	function query($sql)
	{
		$bl = mysql_query($sql,self::$link) or $this->showError();
		return $bl;
	}


	/**
	* 功能：输出错误信息
	* 参数：$error  描述错误的字符串 
	*  
	* 
	*/
	function showError($error="")
	{
		echo $error;
		echo '<br>';
		echo mysql_error();
		echo '<br>';
	}
	 

	/**
	*  功能：在表中插入一行数据
	*  参数：$table  表名   $dataArray  表中的列名及值的一维数组, 例如：array('name'=>'zhangsan','age'=>20....)   
	*  返回值：成功 返回true    失败 返回false  
	* 
	*/
	function insert($table,$dataArray){
		$fields="";
		$values="";
		if(!is_array($dataArray) || count($dataArray)<=0) {
            
            $this->showError('没有要插入的数据');
            return false;
        }

		$fields=implode(',',array_keys($dataArray));
		$values=implode("','",array_values($dataArray));
		$values="'".$values."'";
		$sql = "insert into $table($fields) values($values)";
	    return mysql_query($sql) or $this->showError("插入语句有错误！$sql");
	}


	    /**
	    *  功能：更新表中列的值
	    *  参数：
		   	$table  表名   
			$dataArray  要更新的列名及值的一维数组,例如：array('name'=>'zhangsan','age'=>20)   
			$condition: 更新的条件
	    * 
	    * 
	    */
	    public function update($table,$dataArray,$condition="1=1") {
	        if( !is_array($dataArray) || count($dataArray)<=0) {
	            $this->showError('没有要更新的数据');
	            return false;
	        }
	        $value = "";
			$field = "";
	        foreach($dataArray as $key=>$val) {
	            $value .="$key = '$val',";
	        }
	        $value = substr( $value,0,-1);
	        $sql = "update $table set $value where $condition";

			return mysql_query($sql) or $this->showError("更新语句有错误！$sql");
	    }


    /**
    * 功能：删除表中的数据
    * 参数：$table  表名  $condition 删除的条件
    * 
    * 
    */
    public function delete($table,$condition="") {
	    
	    if(empty($condition)) {

			$sql="delete from $table";
		
		}else if(is_string($condition)){
			
			$sql="delete from $table where $condition";
		}
			return mysql_query($sql) or $this->showError("删除语句有错误！$sql");
	}


	/**
	* 功能：关闭服务器连接
	* 
	*
	*/
	function closeDb()
	{
		mysql_close(self::$link);
	}

}


