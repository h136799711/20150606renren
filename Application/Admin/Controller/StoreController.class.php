<?php
// .-----------------------------------------------------------------------------------
// | WE TRY THE BEST WAY 杭州博也网络科技有限公司
// |-----------------------------------------------------------------------------------
// | Author: 贝贝 <hebiduhebi@163.com>
// | Copyright (c) 2013-2016, http://www.itboye.com. All Rights Reserved.
// |-----------------------------------------------------------------------------------


namespace Admin\Controller;

use Admin\Api\ConfigApi;
use Admin\Api\WxstoreApi;
use Shop\Api\CategoryApi;
use Shop\Api\ProductApi;
use Shop\Api\StoreApi;

class StoreController extends AdminController{
	
	protected function _initialize(){
		parent::_initialize();
	}
	
    /**
	 * 商城配置
	 */
	public function config(){
		if(IS_GET){
			$map = array('name'=>"WXPAY_OPENID");
			$result = apiCall(ConfigApi::GET_INFO, array($map));
			if($result['status']){
				$this->assign("wxpayopenid",	$result['info']['value']);
				$this->display();
			}
		}elseif(IS_POST){
			
			$openids = I('post.openids','');
			
			$config = array("WXPAY_OPENID"=>$openids);
			$result = apiCall(ConfigApi::SET , array($config));
			if($result['status']){
				C('WXPAY_OPENID',$openids);
				
				$this->success(L('RESULT_SUCCESS'),U('Store/config'));
			}else{
				if(is_null($result['info'])){
					$this->error("无更新！");
				}else{
					$this->error($result['info']);
				}
			}
			
		}
	}
	
	/**
	 * 店铺管理
	 */
	public function index(){


		//分页时带参数get参数
		$name = I('name','');

		$map = array();
		if(!empty($name)){
			$map['name'] = array('like',"%".$name."%");
		}
		
		$page = array('curpage' => I('get.p', 0), 'size' => C('LIST_ROWS'));
		$order = " create_time desc ";
		$map['uid'] = UID;
		//
		$result = apiCall(StoreApi::QUERY, array($map, $page, $order));
		//
		if ($result['status']) {
			$this -> assign('name', $name);
			$this -> assign('show', $result['info']['show']);
			$this -> assign('list', $result['info']['list']);
			$this -> display();
		} else {
			LogRecord('INFO:' . $result['info'], '[FILE] ' . __FILE__ . ' [LINE] ' . __LINE__);
			$this -> error(L('UNKNOWN_ERR'));
		}
	}
	
	
	public function add(){
		if(IS_GET){
			$this->display();
		}elseif(IS_POST){
			$name = I('post.name','店铺名称');//
			$desc = I('post.desc','');
			$type = I('post.type','');
			$logo = I('post.logo','');
			$free_shipping=I('post.free_shipping','');
			$banner = I('post.banner','');
			$wxno = I('post.wxno','');
			$cate_id = I('post.store_type','');
			$wxnum = I('post.weixin_number','');
			$weixin_name = I('post.weixin_number_name','');
			$weixin = array();
			$wxnum = explode(",",$wxnum);
			$weixin_name = explode(",",$weixin_name);
			$lat = I('post.lat',30.314933);
			$lng = I('post.lng',120.337985);

			for($i=0;$i<count($wxnum);$i++){
				if(!empty($weixin_name[$i])){
					array_push($weixin,array('openid'=>$wxnum[$i],'name'=>$weixin_name[$i]));
				}
			}

			$service_phone = I('post.service_phone','');
			
			$entity = array(
				'latitude'=>$lat,
				'longitude'=>$lng,
				'wxno'=>$wxno,
				'uid'=>UID,
				'name'=>$name,
				'desc'=>$desc,
				'logo'=>$logo,
				'free_shipping'=>$free_shipping,
				'banner'=>$banner,
				'isopen'=>0,
				'cate_id'=>$cate_id,
				'notes'=>I('post.notes',''),
				'weixin_number'=>json_encode($weixin,JSON_UNESCAPED_UNICODE),
				'service_phone'=>$service_phone,
			);
//			dump($entity);
//			exit();

			$result = apiCall(StoreApi::ADD,array($entity));
//			dump($result);
			if($result['status']){
				$this->success("操作成功！",U('Admin/Store/index'));
			}else{
				$this->error($result['info']);
			}
		}
	}
	
	
	
	public function edit(){
		if(IS_GET){
			$id = I('get.id',0);
			$map = array('id'=>$id);
			$result = apiCall(StoreApi::GET_INFO,array($map));
			if($result['status']){
				$weixin = json_decode($result['info']['weixin_number']);
				$text = "";

				foreach($weixin as $vo){
					$text = $text.$vo->openid.",";
				}
				
				$this->assign("weixin",$weixin);
				$this->assign("openids",$text);
				
				$this->assign("store",$result['info']);
				$this->display();
			}else{
				$this->error($result['info']);
			}
		}elseif(IS_POST){
			$id = I('post.id',0);
			$lat = I('post.lat',30.314933);
			$lng = I('post.lng',120.337985);
			$name = I('post.name','店铺名称');//
			$desc = I('post.desc','');
			$wxno = I('post.wxno','');
			$type = I('post.type','');
			$logo = I('post.logo','');
			$free_shipping = I('post.free_shipping','');
			$banner = I('post.banner','');
			$cate_id = I('post.store_type','');
			$wxnum = I('post.weixin_number','');
			$weixin_name = I('post.weixin_number_name','');
			$weixin = array();
			$wxnum = explode(",",$wxnum);
			$weixin_name = explode(",",$weixin_name);
			
			for($i=0;$i<count($wxnum);$i++){
				if(!empty($weixin_name[$i])){
					array_push($weixin,array('openid'=>$wxnum[$i],'name'=>$weixin_name[$i]));
				}
			}
			
			$service_phone = I('post.service_phone','');
			
			$entity = array(
				'wxno'=>$wxno,
				'name'=>$name,
				'desc'=>$desc,
				'logo'=>$logo,
				'free_shipping'=>$free_shipping,
				'latitude'=>$lat,
				'longitude'=>$lng,
				'banner'=>$banner,
				'cate_id'=>$cate_id,
				'notes'=>I('post.notes',''),
                'weixin_number'=>json_encode($weixin,JSON_UNESCAPED_UNICODE),
				'service_phone'=>$service_phone,
			);
			
			$result = apiCall(StoreApi::SAVE_BY_ID,array($id,$entity));

			if($result['status']){
				$this->success("操作成功！",U('Admin/Store/index'));
			}else{
				$this->error($result['info']);
			}
		}
	}
	
	public function open(){
		$isopen = 1-I('get.isopen',0);
		
		$id = I('id', -1);
				
		$entity = array(
			'isopen'=>$isopen
		);
		$result = apiCall(StoreApi::SAVE_BY_ID, array($id,$entity));

		if ($result['status'] === false) {
			LogRecord('[INFO]' . $result['info'], '[FILE] ' . __FILE__ . ' [LINE] ' . __LINE__);
			$this -> error($result['info']);
		} else {
			$this -> success(L('RESULT_SUCCESS'), U('Admin/' . CONTROLLER_NAME . '/index'));
		}
		
		
	}
	
	public function delete(){
		$map = array('id' => I('id', -1));
		
		$result = apiCall(ProductApi::QUERY_NO_PAGING,array(array('storeid'=>$map['id'])));
		
		if(!$result['status']){
			$this->error($result['info']);
		}
		if(is_array($result['info']) && count($result['info']) > 0){
			$this->error("该商店尚有相关联数据，无法删除！");			
		}
		
		
		
		$result = apiCall(StoreApi::DELETE, array($map));

		if ($result['status'] === false) {
			LogRecord('[INFO]' . $result['info'], '[FILE] ' . __FILE__ . ' [LINE] ' . __LINE__);
			$this -> error($result['info']);
		} else {
			$this -> success(L('RESULT_SUCCESS'), U('Admin/' . CONTROLLER_NAME . '/index'));
		}
	}
	
	//==============================其它功能接口


	
	
	
	/**
	 * ajax获取类目信息
	 */
	public function cate(){
		$cate_id = I('cateid',-1);
		if($cate_id == -1){
			$this->success(array());
		}

		
		$map = array('parent'=>$cate_id);
		
		$result = apiCall(CategoryApi::QUERY_NO_PAGING, array($map));
		
		if($result['status']){
			$this->success($result['info']);
		}else{
			$this->error($result['info']);
		}
	}
	
}
