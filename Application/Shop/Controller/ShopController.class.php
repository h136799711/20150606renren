<?php
// .-----------------------------------------------------------------------------------
// | WE TRY THE BEST WAY
// |-----------------------------------------------------------------------------------
// | Author: 贝贝 <hebiduhebi@163.com>
// | Copyright (c) 2013-2016 杭州博也网络科技, http://www.itboye.com. All Rights Reserved.
// |-----------------------------------------------------------------------------------

namespace Shop\Controller;
use Admin\Api\ConfigApi;
use Common\Api\WeixinApi;
use Think\Controller;
use Weixin\Api\WxaccountApi;
use Weixin\Api\WxuserApi;

class ShopController extends  Controller {
	
	protected $userinfo;
	protected $wxaccount;
	protected $wxapi;
	protected $openid;
    protected $themeType;
	
	protected function _initialize() {
		header("X-AUTHOR:ITBOYE.COM");
		// 获取配置
		$this -> getConfig();

		if (!defined('APP_VERSION')) {
			//定义版本
			if (defined("APP_DEBUG") && APP_DEBUG) {
				define("APP_VERSION", time());
			} else {
				define("APP_VERSION", C('APP_VERSION'));
			}
		}
//		C('SHOW_PAGE_TRACE', false);//设置不显示trace
		$this -> refreshWxaccount();
		$debug = true;
		//$debug = false;

		if($debug){
			$this->getDebugUser();
		}else{
			$url = getCurrentURL();
			$this->getWxuser($url);
		}

		if(empty($this->userinfo) || $this->userinfo['subscribed'] == 0){
			$this->display("Error:please_subscribe");
			exit();
		}

        $this->assign("userinfo",$this->userinfo);
        $this->assign("wxaccount",$this->wxaccount);

        //TODO: 商城模板切换
        $this->themeType = "style2";
	}
	
	//获取测试用户信息，用于PC端测试使用
	private function getDebugUser(){
		$this->userinfo = array(
			'id'=>10,
            'uid'=>236,
			'openid'=>'oJz-Ks7mwh0CadiADLlAWfjE7vvw',
			'nickname'=>'老胖子何必都',
			'avatar'=>'http://wx.qlogo.cn/mmopen/An6TFzHNImPecEhl1R3UWd26LlC1mvVgyhdh2KGCOb0yjQ4JNQnOicG2ysaKojzusSO9R3RE55Exq0lYKpVr3RRArU0u7kgjR/0',
			'score'=>0,
			'wxaccount_id'=>5,
			'exp'=>100,
            'groupid'=>11,
            'subscribed'=>1,
		);

		$this->openid = "oJz-Ks7mwh0CadiADLlAWfjE7vvw";
	}
	
	

	public function getWxuser($url) {

		$this -> userinfo = null;
		if (session("?global_user")) {
			$this -> userinfo = session("global_user");
			$this -> openid = $this->userinfo['openid'];
		}
		
		if (!is_array($this -> userinfo)) {

			$code = I('get.code', '');
			$state = I('get.state', '');
			if (empty($code) && empty($state)) {

				$redirect = $this -> wxapi -> getOAuth2BaseURL($url, 'HomeIndexOpenid');

				redirect($redirect);
			}

			if ($state == 'HomeIndexOpenid') {
				$accessToken = $this -> wxapi -> getOAuth2AccessToken($code);

				$this -> openid = $accessToken['openid'];
				$result = $this -> wxapi -> getBaseUserInfo($accessToken['openid']);

				if ($result['status']) {
					$this -> refreshWxuser($result['info']);
				} else {
                    $this->error($result['info']);
                }
			}
		}
	}

	/**
	 * 刷新粉丝信息
	 */
	private function refreshWxuser($userinfo) {

		$wxuser = array();
		$uid = $this -> wxaccount['uid'];
//		$wxuser['wxaccount_id'] = intval($this -> wxaccount['id']);
		$wxuser['nickname'] = $userinfo['nickname'];
		$wxuser['province'] = $userinfo['province'];
		$wxuser['country'] = $userinfo['country'];
		$wxuser['city'] = $userinfo['city'];
		$wxuser['sex'] = $userinfo['sex'];
		$wxuser['avatar'] = $userinfo['headimgurl'];
		$wxuser['subscribe_time'] = $userinfo['subscribe_time'];
		
		if (!empty($this -> openid) && is_array($this -> wxaccount)) {
			
			$map = array('openid' => $this -> openid, 'wxaccount_id' => $this -> wxaccount['id']);

			$result = apiCall(WxuserApi::SAVE, array($map, $wxuser));

			if (!$result['status']) {
				LogRecord($result['info'], "商城控制器基类_刷新wxuser信息" . __LINE__);
			}else{
				$result = apiCall(WxuserApi::GET_INFO , array($map));
				if($result['status']){
					//
                   // dump($result);
					$this -> userinfo = $result['info'];
					session("global_user", $result['info']);
				}else{
                    $this->error("个人用户信息获取失败！");
                }
			}

		}else{
            $this->error("系统参数错误！");
        }

	}

	/**
	 * 刷新
	 */
	private function refreshWxaccount() {
		$id = I('get.storeid', '');
		if (!empty($token)) {
			session("storeid", $id);
		} elseif (session("?storeid")) {
            $id = session("storeid");
		}else{
            $id = I('post.storeid', '');
		}
        
		if(empty($id)){
            $id = C('STORE_ID');
		}

		$result = apiCall(WxaccountApi::GET_INFO, array( array('id' => $id)));
		if ($result['status'] && is_array($result['info'])) {
			$this -> wxaccount = $result['info'];
			$this -> wxapi = new WeixinApi($this -> wxaccount['appid'], $this -> wxaccount['appsecret']);
		} else {
			exit("公众号信息获取失败，请重试！");
		}
	}

	/**
	 * 从数据库中取得配置信息
	 */
	protected function getConfig() {
		$config = S('config_' . session_id() . '_' . session("uid"));

		if ($config === false) {
			$map = array();
			$fields = 'type,name,value';
			$result = apiCall(ConfigApi::QUERY_NO_PAGING, array($map, false, $fields));
			if ($result['status']) {
				$config = array();
				if (is_array($result['info'])) {
					foreach ($result['info'] as $value) {
						$config[$value['name']] = $this -> parse($value['type'], $value['value']);
					}
				}
				//缓存配置300秒
				S("config_" . session_id() . '_' . session("uid"), $config, 300);
			} else {
				LogRecord('INFO:' . $result['info'], '[FILE] ' . __FILE__ . ' [LINE] ' . __LINE__);
				$this -> error($result['info']);
			}
		}
		C($config);
	}

    /**
     * 根据配置类型解析配置
     * @param  integer $type 配置类型
     * @param  string $value 配置值
     * @return array|string
     */
	private static function parse($type, $value) {
		switch ($type) {
			case 3 :
				//解析数组
				$array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
				if (strpos($value, ':')) {
					$value = array();
					foreach ($array as $val) {
						list($k, $v) = explode(':', $val);
						$value[$k] = $v;
					}
				} else {
					$value = $array;
				}
				break;
		}
		return $value;
	}

}
