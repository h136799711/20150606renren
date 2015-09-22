<?php
// .-----------------------------------------------------------------------------------
// | WE TRY THE BEST WAY 杭州博也网络科技有限公司
// |-----------------------------------------------------------------------------------
// | Author: 贝贝 <hebiduhebi@163.com>
// | Copyright (c) 2013-2016, http://www.itboye.com. All Rights Reserved.
// |-----------------------------------------------------------------------------------
namespace Shop\Controller;

use Admin\Api\DatatreeApi;
use Shop\Api\BannersApi;
use Shop\Api\ProductApi;
use Shop\Model\ProductModel;
use Weixin\Api\WxuserApi;
use Ucenter\Api\MemberApi;
use Ucenter\Api\UcenterMemberApi;
use Distributor\Api\DistributorInfoApi;


class IndexController extends ShopController{

	public function distributor(){

		if(IS_GET){
			//phpinfo();
			$referrer=I('referrer');
			$map=array(
				'id'=>$referrer
			);
			$result=apiCall(WxuserApi::QUERY_NO_PAGING ,array($map));
			$this->assign('referrer',$result[info][0]);
			$this->theme($this->themeType)->display();
		}else if(IS_AJAX){

			$map=array(
				'id'=>$this->userinfo['id'],
			);
			$userInfo= apiCall(WxuserApi::QUERY_NO_PAGING,array($map));
			$userInfo['info']['groupid']=13;
			//dump($userInfo['info']);
			$result=apiCall(WxuserApi::SAVE_BY_ID,array($this->userinfo['id'],$userInfo['info']));

			$entity=array(
				'name'=>I("name"),
				'phone'=>I("phone"),
				'address'=>I("address"),
				'create_time'=>time(),
				'uid'=>$userInfo['info'][0]['uid'],
				'wxaccount_id'=>1
			);
			$result=apiCall(DistributorInfoApi::ADD,array($entity));
			session('[destroy]'); // 删除session
			$this->success('操作成功',U('Shop/Index/index'));
		}
	}


	protected function _initialize(){
		parent::_initialize();
		$showStartPage = true;
		$last_entry_time = cookie("last_entry_time");
		if(empty($last_entry_time)){
			//一小时过期
			cookie("last_entry_time",time(),3600);
			$last_entry_time = time();
		}elseif(time() - $last_entry_time < 20*60){
			$showStartPage = false;
		}else{
			//一小时过期
			cookie("last_entry_time",time(),3600);
		}

		$this->assign("showstartpage",$showStartPage);

	}



	/**
	 * 首页
	 */
	/*
	public function index(){
		//dump($this->);
		
		$map= array(
			'uid'=>$this->wxaccount['uid'],
			'storeid'=>-1,
			'position'=>getDatatree("SHOP_INDEX_BANNERS")
		);
		
		$page = array(
			'curpage'=>0,
			'size'=>8
		);
		$order = "createtime desc";
		$params = false;
		
		$result = apiCall(BannersApi::QUERY,array($map,$page,$order,$params));
//		dump($result);
		if(!$result['status']){
			$this->error($result['info']);
		}
		
		$this->assign("banners",$result['info']['list']);
		
		$map= array('parentid'=>C("DATATREE.STORE_TYPE"));
		$result = apiCall(DatatreeApi::QUERY,array($map,$page,$order,$params));
		
		if(!$result['status']){
			$this->error($result['info']);
		}

		$this->assign("store_types",$result['info']['list']);
		
		// 获取推荐商品
		$result = $this->getProducts();
		if($result['status'] && is_array($result['info'])){
			$this->assign("recommend_products",$result['info']['list']);
		}
		
		
		// 获取最新商品
		$result = $this->getNewProducts();
		if($result['status'] && is_array($result['info'])){
			$this->assign("new_products",$result['info']['list']);
		}


		// 获取热门商品
		$result = $this->getHotProducts();
		if($result['status'] && is_array($result['info'])){
			$this->assign("hot_products",$result['info']['list']);
		}
		
		$ads  = $this->getAds();
		
		$this->assign("ads",$ads['info']['list']);
		
		//获取推荐店铺
		$result = $this->getRecommendStore();
		
		$this->assign("rec_stores",$result['info']['list']);
		
		//获取首页4格活动
		$result = $this->getFourGrid();
        $this->assign("meta_title",$this->getStoreName());

		$this->assign('g_id_new',getDatatree("WXPRODUCTGROUP_NEW"));
		$this->assign('g_id_hot',getDatatree("WXPRODUCTGROUP_HOT"));
		$this->assign('g_id_recommend',getDatatree("WXPRODUCTGROUP_RECOMMEND"));

		$this->assign("fourgrid",$result['info']['list']);
        $this->assign("isDistributor",$this->isDistributor());
		$this->theme($this->themeType)->display();
	}
*/






	/**
	 * 我的小店
	 */
	/*public function index(){
		$isStore=I('isStore',0);
		$this->assign('isStore',$isStore);
		$map= array(
			'uid'=>$this->wxaccount['uid'],
			'storeid'=>-1,
			'position'=>getDatatree("SHOP_INDEX_BANNERS")
		);

		$page = array(
			'curpage'=>0,
			'size'=>8
		);
		$order = "createtime desc";
		$params = false;

		$result = apiCall(BannersApi::QUERY,array($map,$page,$order,$params));
//		dump($result);
		if(!$result['status']){
			$this->error($result['info']);
		}

		$this->assign("banners",$result['info']['list']);

		$map= array('parentid'=>C("DATATREE.STORE_TYPE"));
		$result = apiCall(DatatreeApi::QUERY,array($map,$page,$order,$params));

		if(!$result['status']){
			$this->error($result['info']);
		}

		$this->assign("store_types",$result['info']['list']);

		// 获取推荐商品
		$result = $this->getProducts();


		if($result['status'] && is_array($result['info'])){
			$this->assign("recommend_products",$result['info']['list']);

			if($result['info']['list']==NULL){
				$this->assign("recommend_products",-1);
			}else{
				if(count($result['info']['list'])%2!=0){
					if(count($result['info']['list'])==1){
						$this->assign("recommend_products",-1);
					}else{
						array_pop($result['info']['list']);
						$this->assign("recommend_products",$result['info']['list']);
					}

				}else{
					$this->assign("recommend_products",$result['info']['list']);
				}

			}
		}


		// 获取最新商品
		$result = $this->getNewProducts();
		if($result['status'] && is_array($result['info'])){
			$this->assign("new_products",$result['info']['list']);
			if($result['info']['list']==NULL){
				$this->assign("new_products",-1);
			}else{
				if(count($result['info']['list'])%2!=0){
					if(count($result['info']['list'])==1){
						$this->assign("new_products",-1);
					}else{
						array_pop($result['info']['list']);
						$this->assign("new_products",$result['info']['list']);
					}

				}else{
					$this->assign("new_products",$result['info']['list']);
				}

			}

		}

		// 获取热门商品
		$result = $this->getHotProducts();
		if($result['status'] && is_array($result['info'])){
			$this->assign("hot_products",$result['info']['list']);
			if($result['info']['list']==NULL){
				$this->assign("hot_products",-1);
			}else{
				if(count($result['info']['list'])%2!=0){
					if(count($result['info']['list'])==1){
						$this->assign("hot_products",-1);
					}else{
						array_pop($result['info']['list']);
						$this->assign("hot_products",$result['info']['list']);
					}

				}else{
					$this->assign("hot_products",$result['info']['list']);
				}

			}
		}

		$ads  = $this->getAds();
		$adsVideo=$this->getAdsVideo();

		$this->assign("ads",$ads['info']['list']);
		$this->assign("adsVideo",$adsVideo['info']['list']);

		//获取推荐店铺
		$result = $this->getRecommendStore();

		$this->assign("rec_stores",$result['info']['list']);

		//获取首页4格活动
		$result = $this->getFourGrid();
		$this->assign("meta_title",$this->getStoreName());

		$this->assign("fourgrid",$result['info']['list']);
		$this->assign("isDistributor",$this->isDistributor());
		//获取当前商品总数
		$map=array(
			'onshelf'=>1
		);
		$count=apiCall(ProductApi::COUNT,array($map));


		$this->assign('g_id_new',getDatatree("WXPRODUCTGROUP_NEW"));
		$this->assign('g_id_hot',getDatatree("WXPRODUCTGROUP_HOT"));
		$this->assign('g_id_recommend',getDatatree("WXPRODUCTGROUP_RECOMMEND"));

		$this->assign('count',$count['info']);

		$this->theme($this->themeType)->display();
		//获取当前商品总数
	}*/


	/**
	 * 我的小店：style2
	 */
	public function index(){
		$isStore=I('isStore',0);
		$this->assign('isStore',$isStore);
		$map= array(
			'uid'=>$this->wxaccount['uid'],
			'storeid'=>-1,
			'position'=>getDatatree("SHOP_INDEX_BANNERS")
		);

		$page = array(
			'curpage'=>0,
			'size'=>8
		);
		$order = "createtime desc";
		$params = false;

		$result = apiCall(BannersApi::QUERY,array($map,$page,$order,$params));
//		dump($result);
		if(!$result['status']){
			$this->error($result['info']);
		}

		$this->assign("banners",$result['info']['list']);

		$map= array('parentid'=>C("DATATREE.STORE_TYPE"));
		$result = apiCall(DatatreeApi::QUERY,array($map,$page,$order,$params));

		if(!$result['status']){
			$this->error($result['info']);
		}

		$this->assign("store_types",$result['info']['list']);

		// 获取推荐商品
		$result = $this->getProducts();


		if($result['status'] && is_array($result['info'])){
			$this->assign("recommend_products",$result['info']['list']);
			if($result['info']['list']==NULL){
				$this->assign("recommend_products",-1);
			}else{
				if(count($result['info']['list'])>3){

					$this->assign("recommend_products",array_slice($result['info']['list'],0,3));
				}else{
					$this->assign("recommend_products",-1);
				}
			}
		}


		// 获取最新商品
		$result = $this->getNewProducts();
		if($result['status'] && is_array($result['info'])){
			$this->assign("new_products",$result['info']['list']);
			if($result['info']['list']==NULL){
				$this->assign("new_products",-1);
			}else{
				if(count($result['info']['list'])>3){

					$this->assign("new_products",array_slice($result['info']['list'],0,3));
				}else{
					$this->assign("new_products",-1);
				}
			}

		}

		// 获取热门商品
		$result = $this->getHotProducts();

		$this->assign("hot_products",$result['info']['list']);
		$ads  = $this->getAds();
		$adsVideo=$this->getAdsVideo();

		$this->assign("ads",$ads['info']['list']);
		$this->assign("adsVideo",$adsVideo['info']['list']);

		//获取推荐店铺
		$result = $this->getRecommendStore();

		$this->assign("rec_stores",$result['info']['list']);

		//获取首页4格活动
		$result = $this->getFourGrid();
		$this->assign("meta_title",$this->getStoreName());

		$this->assign("fourgrid",$result['info']['list']);
		$this->assign("isDistributor",$this->isDistributor());
		//获取当前商品总数
		$map=array(
			'onshelf'=>1
		);
		$count=apiCall(ProductApi::COUNT,array($map));


		$this->assign('g_id_new',getDatatree("WXPRODUCTGROUP_NEW"));
		$this->assign('g_id_hot',getDatatree("WXPRODUCTGROUP_HOT"));
		$this->assign('g_id_recommend',getDatatree("WXPRODUCTGROUP_RECOMMEND"));

		$this->assign('count',$count['info']);

		$this->theme($this->themeType)->display();
		//获取当前商品总数
	}









	private function getStoreName(){
		//默认当前公众号名
		$name = $this->wxaccount['wxname'];

		// 如果是分销商，显示分销商名称
		if($this->isDistributor()){
			$name = $this->userinfo['nickname'].'的店铺';
		}

		return $name;
	}

	/**
	 * 判断是否为分销商
	 * @return bool
	 */
	private function isDistributor(){
		//TODO: 当前仅判断大于1 时都为分销商
		if($this->userinfo['groupid'] > 1){
			return true;
		}

		return false;
	}


	/**
	 * 获取首页4格活动
	 *
	 */
	private function getFourGrid(){
		$page = array('curpage'=>0,'size'=>4);
		$map = array('position'=>getDatatree("INDEX_4_ACTIVTIY"));
		$order = " id desc";
		$result = apiCall(BannersApi::QUERY, array($map,$page,$order));

		if(!$result['status']){
			$this->error($result['info']);
		}

		return $result;
	}

	/**
	 * 广告
	 */
	private function getAds(){

		$page = array('curpage'=>0,'size'=>5);
		$map = array('position'=>getDatatree("SHOP_INDEX_ADVERT"));
		$order="starttime desc";
		$result = apiCall(BannersApi::QUERY, array($map,$page,$order));
		if(!$result['status']){
			$this->error($result['info']);
		}
		return $result;
	}

	/**
	 * 视频广告
	 */
	private function getAdsVideo(){

		$page = array('curpage'=>0,'size'=>1);
		$map = array('position'=>38);
		$order="starttime desc";
		$result = apiCall(BannersApi::QUERY, array($map,$page,$order));
		if(!$result['status']){
			$this->error($result['info']);
		}
		return $result;
	}



	/**
	 * 推荐店铺
	 */
	private function getRecommendStore(){

		$page = array('curpage'=>0,'size'=>4);
		$map = array('position'=>getDatatree("SHOP_INDEX_RECOMMEND_STORE"));
		$result = apiCall(DatatreeApi::QUERY, array($map));
		if(!$result['status']){
			$this->error($result['info']);
		}

		return $result;
	}

	/**
	 *
	 */
	public function getProducts(){

		$page = array('curpage'=>0,'size'=>10);
		$order = "updatetime desc";
		$map = array('onshelf'=>ProductModel::STATUS_ONSHELF);
		$group_id = getDatatree("WXPRODUCTGROUP_RECOMMEND");

		$result = apiCall(ProductApi::QUERY_BY_GROUP, array($group_id,$map));
		if(!$result['status']){
			LogRecord($result['info'], __FILE__.__LINE__);
		}

		return $result;
	}


	public function getNewProducts(){
		$page = array('curpage'=>0,'size'=>10);
		$order = "updatetime desc";
		$map = array('onshelf'=>ProductModel::STATUS_ONSHELF);
		$group_id = getDatatree("WXPRODUCTGROUP_NEW");

		$result = apiCall(ProductApi::QUERY_BY_GROUP, array($group_id,$map));
		if(!$result['status']){
			LogRecord($result['info'], __FILE__.__LINE__);
		}

		return $result;
	}


	public function getHotProducts(){
		$page = array('curpage'=>0,'size'=>10);
		$order = "updatetime desc";
		$map = array('onshelf'=>ProductModel::STATUS_ONSHELF);
		$group_id = getDatatree("WXPRODUCTGROUP_HOT");

		$result = apiCall(ProductApi::QUERY_BY_GROUP, array($group_id,$map));
		if(!$result['status']){
			LogRecord($result['info'], __FILE__.__LINE__);
		}

		return $result;
	}

}

