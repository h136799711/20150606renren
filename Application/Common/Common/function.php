<?php
// .-----------------------------------------------------------------------------------
// | WE TRY THE BEST WAY
// |-----------------------------------------------------------------------------------
// | Author: 贝贝 <hebiduhebi@163.com>
// | Copyright (c) 2013-2015, http://www.gooraye.net. All Rights Reserved.
// |-----------------------------------------------------------------------------------

/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 */
function is_login() {
	$user = session('global_user');
	if (empty($user)) {
		return 0;
	} else {
		return session('global_user_sign') == data_auth_sign($user) ? session('uid') : 0;
	}
}

/**
 * 检测当前用户是否为管理员
 * @return boolean true-管理员，false-非管理员
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function is_administrator($uid = null) {
	$uid = is_null($uid) ? is_login() : $uid;
	return $uid && (intval($uid) === C('USER_ADMINISTRATOR'));
}


/**
 * apiCall
 * @param $url
 * @param array $vars
 * @param string $layer
 * @return mixed
 */
function apiCall($url, $vars=array(), $layer = 'Api') {
	//TODO:考虑使用func_get_args 获取参数数组
    $ret = R($url, $vars, $layer);
    if(!$ret){
        return array('status'=>false,'info'=>'无法调用'.$url);
    }
	return $ret;
}



/**
 * 记录日志，系统运行过程中可能产生的日志
 * Level取值如下：
 * EMERG 严重错误，导致系统崩溃无法使用
 * ALERT 警戒性错误， 必须被立即修改的错误
 * CRIT 临界值错误， 超过临界值的错误
 * WARN 警告性错误， 需要发出警告的错误
 * ERR 一般性错误
 * NOTICE 通知，程序可以运行但是还不够完美的错误
 * INFO 信息，程序输出信息
 * DEBUG 调试，用于调试信息
 * SQL SQL语句，该级别只在调试模式开启时有效
 */
function LogRecord($msg, $location, $level = 'ERR') {
	Think\Log::write($location . $msg, $level);
}

/**
 * 如果操作失败则记录日志
 * @return array 格式：array('status'=>boolean,'info'=>'错误信息')
 * @author hebiduhebi@163.com
 */
function ifFailedLogRecord($result, $location) {
	if ($result['status'] === false) {
		Think\Log::write($location . $result['info'], 'ERR');
	}
}

/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名
 */
function data_auth_sign($data) {
	//数据类型检测
	if (!is_array($data)) {
		$data = (array)$data;
	}
	ksort($data);
	//排序
	$code = http_build_query($data);
	//url编码并生成query字符串
	$sign = sha1($code);
	//生成签名
	return $sign;
}

/**
 * 获取一个日期时间段
 * 如果有查询参数包含startdatetime，enddatetime，则优先使用否则生成
 * @param $type 0|1|2|3｜其它
 * @return array("0"=>开始日期,"1"=>结束日期)
 */
function getDataRange($type) {
	$result = array();
	switch($type) {
		case 0 :
			//今天之内
			$result['0'] = I('startdatetime', (date('Y-m-d 00:00:00', time())), 'urldecode');
			break;
		case 1 :
			//昨天
			$result['0'] = I('startdatetime', (date('Y-m-d 00:00:00', time() - 24 * 3600)), 'urldecode');
			$result['1'] = I('enddatetime', (date('Y-m-d 00:00:00', time())), 'urldecode');
			break;
		case 2 :
			//最近7天
			$result['0'] = I('startdatetime', (date('Y-m-d H:i:s', time() - 24 * 3600 * 7)), 'urldecode');
			break;
		case 3 :
			//最近30天
			$result['0'] = I('startdatetime', (date('Y-m-d H:i:s', time() - 24 * 3600 * 30)), 'urldecode');
			break;
		default :
			$result['0'] = I('startdatetime', (date('Y-m-d 00:00:00', time() - 24 * 3600)), 'urldecode');
			break;
	}
	if (!isset($result['1'])) {
		$result['1'] = I('enddatetime', (date('Y-m-d H:i:s', time() + 10)), 'urldecode');
	}
	return $result;
}

/**
 * 返回 是|否
 * @param $param 一个值|对象等
 * @return 空|false|0 时返回否，否则返回是
 */
function yesorno($param) {
	if (is_null($param) || $param === false || $param == 0 || $param == "0") {
		return L("NO");
	} else {
		return L('YES');
	}
}

/**
 * 返回数据状态的含义
 * @status $status 一个数字 -1,0,1,2,3 其它值都是未知
 * @return 描述字符串
 */
function getStatus($status) {
	$desc = '未知状态';
	switch($status) {
		case -1 :
			$desc = "已删除";
			break;
		case 0 :
			$desc = "禁用";
			break;
		case 1 :
			$desc = "正常";
			break;
		case 2 :
			$desc = "待审核";
			break;
		case 3 :
			$desc = "通过";
			break;
		case 4 :
			$desc = "不通过";
			break;
		default :
			break;
	}
	return $desc;
}

/**
 * 获得皮肤的字符串表示
 */
function getSkin($skin) {

    $desc = '';

    switch($skin) {
        case 0 :
            $desc = "simplex";
            break;
        case 1 :
            $desc = "flatly";
            break;
        case 2 :
            $desc = "darkly";
            break;
        case 3 :
            $desc = "cosmo";
            break;
        case 4 :
            $desc = "paper";
            break;
        case 5 :
            $desc = "slate";
            break;
        case 6 :
            $desc = "superhero";
            break;
        case 7 :
            $desc = "united";
            break;
        case 8 :
            $desc = "yeti";
            break;
        case 9 :
            $desc = "spruce";
            break;
        case 10 :
            $desc = "readable";
            break;
        case 11 :
            $desc = "cyborg";
            break;
        case 12 :
            $desc = "cerulean";
            break;
        default :
            $desc = "simplex";
            break;
    }
    return $desc;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pk
 * @param string $pid parent标记字段
 * @param string $child
 * @param int $root
 * @return array
 * @internal param string $level level标记字段
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0) {
	// 创建Tree
	$tree = array();
	if (is_array($list)) {
		// 创建基于主键的数组引用
		$refer = array();
		foreach ($list as $key => $data) {
			$refer[$data[$pk]] = &$list[$key];
		}
		foreach ($list as $key => $data) {
			// 判断是否存在parent
			$parentId = $data[$pid];
			if ($root == $parentId) {
				$tree[] = &$list[$key];
			} else {
				if (isset($refer[$parentId])) {
					$parent = &$refer[$parentId];
					$parent[$child][] = &$list[$key];
				}
			}
		}
	}
	return $tree;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array $tree  原来的树
 * @param  string $child 孩子节点的键
 * @param  string $order 排序显示的键，一般是主键 升序排列
 * @param  array  $list  过渡用的中间数组，
 * @return array        返回排过序的列表数组
 * @author yangweijie <yangweijiester@gmail.com>
 */
function tree_to_list($tree, $child = '_child', $order = 'id', &$list = array()) {
	if (is_array($tree)) {
		foreach ($tree as $key => $value) {
			$reffer = $value;
			if (isset($reffer[$child])) {
				unset($reffer[$child]);
				tree_to_list($value[$child], $child, $order, $list);
			}
			$list[] = $reffer;
		}
		$list = list_sort_by($list, $order, $sortby = 'asc');
	}
	return $list;
}

/**
 * 获取图片表的图片链接
 */
function getPictureURL($localpath, $remoteurl) {
	if (strpos($remoteurl, "http") === 0) {
		return $remoteurl;
	}
	return __ROOT__ . $localpath;
}

function GUID() {
	if (function_exists('com_create_guid') === true) {
		return trim(com_create_guid(), '{}');
	}

	return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

function addWeixinLog($data, $operator = '') {
    if(is_null($data)){
        $data = "";
    }
	$log['ctime'] = time();
	$log['loginfo'] = is_array($data) ? serialize($data) : $data;
	$log['operator'] = $operator;
	$weixinlog = new \Common\Model\WeixinLogModel();
	$weixinlog -> add($log);
}

/**
 * 获取订单状态的文字描述
 */
function getOrderStatus($status) {

	switch($status) {
		case \Shop\Model\OrdersModel::ORDER_COMPLETED :
			return "已完成";
		case \Shop\Model\OrdersModel::ORDER_RETURNED :
			return "已退货";
		case \Shop\Model\OrdersModel::ORDER_SHIPPED :
			return "已发货";
		case \Shop\Model\OrdersModel::ORDER_TOBE_CONFIRMED :
			return "待确认";
		case \Shop\Model\OrdersModel::ORDER_TOBE_SHIPPED :
			return "待发货";
		case \Shop\Model\OrdersModel::ORDER_CANCEL :
			return "订单已关闭";
		case \Shop\Model\OrdersModel::ORDER_RECEIPT_OF_GOODS :
			return "已收货";
		case \Shop\Model\OrdersModel::ORDER_BACK :
			return "卖家退回";
		default :
			return "未知";
	}
}

/**
 * 获取支付状态的文字描述
 */
function getPayStatus($status) {
	switch($status) {
		case \Shop\Model\OrdersModel::ORDER_PAID :
			return "已支付";
		case \Shop\Model\OrdersModel::ORDER_TOBE_PAID :
			return "待支付";
		case \Shop\Model\OrdersModel::ORDER_REFUND :
			return "已退款";
		case \Shop\Model\OrdersModel::ORDER_CASH_ON_DELIVERY :
			return "货到付款";
			
		default :
			return "未知";
	}
}

/**
 * 获取数据字典的ID
 * TODO: 考虑从数据库中获取
 */
function getDatatree($code) {
	return C("DATATREE." . $code);
}

/**
 * 使用fsockopen请求地址
 * @param $url 请求地址 ，完整的地址，
 * @param $post_data 请求参数，数组形式
 * @param $cookie
 * @param $repeat TODO: 重复次数
 * @return int
 */
function fsockopenRequest($url,$post_data = array(),$method="POST", $cookie = array(), $repeat = 1) {
	if($method == "POST"){
		
	}else{
		$method = "GET";
	}
	//通过POST或者GET传递一些参数给要触发的脚本
	$url_array = parse_url($url);
	//获取URL信息
	$port = isset($url_array['port']) ? $url_array['port'] : 80;
	//5秒超时
	$fp = @fsockopen($url_array['host'], $port, $errno, $errstr, 5);
	if (!$fp) {
		//连接失败
		return 0;
	}
	//非阻塞设置
	stream_set_blocking($fp, FALSE);
	$getPath = $url_array['path'] . "?" . $url_array['query'];
	
	$header = $method . " " . $getPath;
	$header .= " HTTP/1.1\r\n";
	$header .= "Host: " . $url_array['host'] . "\r\n";
	//HTTP 1.1 Host域不能省略
	/*以下头信息域可以省略 */

	$header .= "Referer:http://" . $url_array['host'] . " \r\n";
//	$header .= "User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X; en-us) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53 \r\n";
//	$header .= "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8 \r\n";
//	$header .= "Accept-Language:zh-CN,zh;q=0.8,en;q=0.6 \r\n";
//	$header .= "Accept-Encoding:gzip, deflate, sdch \r\n";

	$header .= "Connection:Close\r\n";
//			$header .= "Keep-Alive: 3\r\n";
//			$header .= "Connection: keep-alive\r\n";
			
	//需要重复2次
	if (!empty($cookie)) {
		$_cookie = strval(NULL);
		foreach ($cookie as $k => $v) {
			$_cookie .= $k . "=" . $v . "; ";
		}
		$cookie_str = "Cookie: " . base64_encode($_cookie) . " \r\n";
		//传递Cookie
		$header .= $cookie_str;
	}
	if (!empty($post_data)) {
		$_post = strval(NULL);
		$i == 0;
		foreach ($post_data as $k => $v) {
			if ($i == 0) {
				$_post .= $k . "=" . $v;
			} else {
				$_post .= '&'.$k . "=" . urlencode($v);
			}
			$i++;
		}

		//			$post_str = "Content-Type: multipart/form-data; charset=UTF-8	\r\n";
		$post_str = "Content-Type: application/x-www-form-urlencoded; charset=UTF-8	\r\n";
		//			$_post = "username=demo&password=hahaha";
		$post_str .= "Content-Length: " . strlen($_post) . "\r\n";
		$post_str .= "\r\n";
		//POST数据的长度
		$post_str .= $_post;
		//传递POST数据
		$header .= $post_str;
	}else{
		$header .= "\r\n";
	}
//	dump($header);
	fwrite($fp, $header);
	//TODO: 从返回结果来判断是否成功
	//		$result = "";
	//		while(!feof($fp)){//测试文件指针是否到了文件结束的位置
	//		   $result.= fgets($fp,128);
	//		}

	//		$result = split("\r\n", $result);
	//		for($i=count($result)-1;$i>=0;$i--){
	//			dump($result);
	//		}

	fclose($fp);
	return 1;
}
/**
 * 获取当前完整url
 */
function getCurrentURL(){
	$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	return $url;
}



/**
 * 记录行为日志，并执行该行为的规则
 * @param string $action 行为标识
 * @param string $model 触发行为的模型名
 * @param int $record_id 触发行为的记录id
 * @param int $user_id 执行行为的用户id
 * @return boolean
 */
function action_log($action = null, $model = null, $record_id = null, $user_id = null){

    //参数检查
    if(empty($action) || empty($model) || empty($record_id)){
        return '参数不能为空';
    }
    if(empty($user_id)){
        $user_id = is_login();
    }
	
	
    //查询行为,判断是否执行
    $action_info = apiCall("Admin/Action/getInfo", array(array("name"=>$action)));
	
	
//	dump($action_info);
    if($action_info['status'] && is_array($action_info['info'])  && $action_info['info']['status'] != 1){
    		
        return '该行为被禁用或删除';
    }
	$action_info = $action_info['info'];
    //插入行为日志
    $data['action_id']      =   $action_info['id'];
    $data['user_id']        =   $user_id;
    $data['action_ip']      =   ip2long(get_client_ip());
    $data['model']          =   $model;
    $data['record_id']      =   $record_id;
    $data['create_time']    =   NOW_TIME;
	
    //解析日志规则,生成日志备注
    if(!empty($action_info['log'])){
        if(preg_match_all('/\[(\S+?)\]/', $action_info['log'], $match)){//匹配[]，获取[]里的字符串
            $log['user']    =   $user_id;
            $log['record']  =   $record_id;
            $log['model']   =   $model;
            $log['time']    =   NOW_TIME;
            $log['data']    =   array('user'=>$user_id,'model'=>$model,'record'=>$record_id,'time'=>NOW_TIME);
            foreach ($match[1] as $value){
                $param = explode('|', $value);//分割字符串通过|
                if(isset($param[1])){
                    $replace[] = call_user_func($param[1],$log[$param[0]]);//调用函数
                }else{
                    $replace[] = $log[$param[0]];
                }
            }
			
            $data['remark'] =   str_replace($match[0], $replace, $action_info['log']);
        }else{
            $data['remark'] =   $action_info['log'];
        }
    }else{
        //未定义日志规则，记录操作url
        $data['remark']     =   '操作url：'.$_SERVER['REQUEST_URI'];
    }
	
	$result = apiCall("Admin/ActionLog/add", array($data));
	
	if(!$result['status']){
		LogRecord("记录操作日志失败!", $result['info']);
	}
//  M('ActionLog')->add($data);
		
    if(!empty($action_info['rule'])){
        //解析行为
        $rules = parse_action($action, $user_id);

        //执行行为
        $res = execute_action($rules, $action_info['id'], $user_id);
    }
}

/**
 * 解析行为规则
 * 示例：table:member|field:score|condition:uid={$self} AND status>-1|rule:9-2+3+score*1/1|cycle:24|max:1;
 * 规则定义  table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
 * 规则字段解释：table->要操作的数据表，不需要加表前缀；
 *              field->要操作的字段；
 *              condition->操作的条件，目前支持字符串，默认变量{$self}为执行行为的用户
 *              rule->对字段进行的具体操作，目前支持四则混合运算，如：1+score*2/2-3
 *              cycle->执行周期，单位（小时），表示$cycle小时内最多执行$max次
 *              max->单个周期内的最大执行次数（$cycle和$max必须同时定义，否则无效）
 * 单个行为后可加 ； 连接其他规则
 * @param string $action 行为id或者name
 * @param int $self 替换规则里的变量为执行用户的id
 * @return boolean|array: false解析出错 ， 成功返回规则数组
 */
function parse_action($action = null, $self){
    if(empty($action)){
        return false;
    }
	
    //参数支持id或者name
    if(is_numeric($action)){
        $map = array('id'=>$action);
    }else{
        $map = array('name'=>$action);
    }

    //查询行为信息
  	$result = apiCall("Admin/Action/getInfo", array($map));
	if(!$result['status']){
		return false;
	}
	
	$info = $result['info'];
    if(is_null($info) || $info['status'] != 1){
        return false;
    }
	
    //解析规则:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
    $rules = $info['rule'];
    $rules = str_replace('{$self}', $self, $rules);
    $rules = explode(';', $rules);
    $return = array();
    foreach ($rules as $key=>&$rule){
        $rule = explode('|', $rule);
        foreach ($rule as $k=>$fields){
            $field = empty($fields) ? array() : explode(':', $fields);
            if(!empty($field)){
                $return[$key][$field[0]] = $field[1];
            }
        }
        //cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
        if(!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])){
            unset($return[$key]['cycle'],$return[$key]['max']);
        }
    }
	
    return $return;
}

/**
 * 执行行为
 * @param array|bool $rules 解析后的规则数组
 * @param int $action_id 行为id
 * @param array $user_id 执行的用户id
 * @return bool false 失败 ， true 成功
 *
 */
function execute_action($rules = false, $action_id = null, $user_id = null){
	
    if(!$rules || empty($action_id) || empty($user_id)){
        return false;
    }

    $return = true;
    foreach ($rules as $rule){
		
        //检查执行周期
        $map = array('action_id'=>$action_id, 'user_id'=>$user_id);
        $map['create_time'] = array('gt', NOW_TIME - intval($rule['cycle']) * 3600);
		
		//统计执行次数
        $exec_count = D('ActionLog')->where($map)->count();
        if($exec_count > $rule['max']){
            continue;
        }
		
        //执行数据库操作
        $Model = D(ucfirst($rule['table']));
        $field = $rule['field'];
        $res = $Model->where($rule['condition'])->setField($field, array('exp', $rule['rule']));
		
//		dump($Model);
        if(!$res){
            $return = false;
        }
    }
    return $return;
}

/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 * @author huajie <banhuajie@163.com>
 */
function time_format($time = NULL,$format='Y-m-d H:i'){
    $time = $time === NULL ? NOW_TIME : intval($time);
    return date($format, $time);
}

function subtext($text, $length)
{
	if(mb_strlen($text, 'utf8') > $length)
		return mb_substr($text, 0, $length, 'utf8').'...';
	return $text;
}


/**
 * 根据用户ID获取用户昵称
 * @param  integer $uid 用户ID
 * @return string       用户昵称
 */
function get_nickname($uid = 0){
    static $list;
    if(!($uid && is_numeric($uid))){ //获取当前登录用户名
        return session('global_user.username');
    }

    /* 获取缓存数据 */
    if(empty($list)){
        $list = S('sys_user_nickname_list');
    }

    /* 查找用户信息 */
    $key = "u{$uid}";
    if(isset($list[$key])){ //已缓存，直接使用
        $name = $list[$key];
    } else { //调用接口获取用户信息
    		$result = apiCall("Admin/Member/getInfo",array("uid"=>$uid));
		
//      $info = M('Member')->field('nickname')->find($uid);

        if($result['status'] !== false && $result['info']['nickname'] ){
            $nickname = $result['info']['nickname'];
            $name = $list[$key] = $nickname;
			
            /* 缓存用户 */
            $count = count($list);
            $max   = 1000;
            while ($count-- > $max) {
                array_shift($list);
            }
            S('sys_user_nickname_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
}


/**
 * 获取行为数据
 * @param string $id 行为id
 * @param string $field 需要获取的字段
 * @return bool
 */
function get_action($id = null, $field = null){
	
    if(empty($id) && !is_numeric($id)){
        return false;
    }
	
    $list = S('action_list');
	
    if(empty($list[$id])){
        $map = array('status'=>array('gt', -1), 'id'=>$id);
        $result = apiCall("Admin/Action/getInfo",array($map));
        if($result['status']){
            $list[$id] = $result['info'];
        }
    }
	S('action_list',$list);
    $ret = empty($field) ? $list[$id] : $list[$id][$field];
	
    return $ret;
}

/**
 * 获取插件类的类名
 * @var string $name 插件名
 * @return string
 */
function get_addon_class($name){
    $class = "Addons\\{$name}\\{$name}Addon";
    return $class;
}
/**
 * 获取插件类的配置文件数组
 * @param string $name 插件名
 * @return array
 */
function get_addon_config($name){
    $class = get_addon_class($name);
    if(class_exists($class)) {
        $addon = new $class();
        return $addon->getConfig();
    }else {
        return array();
    }
}

/**
 * 插件显示内容里生成访问插件的url
 * @param string $url url
 * @param array $param 参数
 * @return string
 */
function addons_url($url, $param = array()){
    $url        = parse_url($url);
    $case       = C('URL_CASE_INSENSITIVE');
    $addons     = $case ? parse_name($url['scheme']) : $url['scheme'];
    $controller = $case ? parse_name($url['host']) : $url['host'];
    $action     = trim($case ? strtolower($url['path']) : $url['path'], '/');
    /* 解析URL带的参数 */
    if(isset($url['query'])){
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }
    /* 基础参数 */
    $params = array(
        '_addons'     => $addons,
        '_controller' => $controller,
        '_action'     => $action,
    );
    $params = array_merge($params, $param); //添加额外参数
    return U('Addons/execute', $params);
}
/**
 * 基于数组创建目录和文件
 *
 */
function create_dir_or_files($files){
    foreach ($files as $key => $value) {
        if(substr($value, -1) == '/'){
            mkdir($value);
        }else{
            @file_put_contents($value, '');
        }
    }
}

/**
 * 从session中取WxAccountID
 */
function getWxAccountID(){
    if(session("?wxaccountid")){
        return session("wxaccountid");
    }
    return -1;
}