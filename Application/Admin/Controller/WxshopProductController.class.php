<?php
// .-----------------------------------------------------------------------------------
// | WE TRY THE BEST WAY 杭州博也网络科技有限公司
// |-----------------------------------------------------------------------------------
// | Author: 贝贝 <hebiduhebi@163.com>
// | Copyright (c) 2013-2016, http://www.itboye.com. All Rights Reserved.
// |-----------------------------------------------------------------------------------

namespace Admin\Controller;

use Admin\Api\DatatreeApi;
use Admin\Api\FreightTemplateApi;
use Admin\Api\WxstoreApi;
use Common\Model\ProductSkuModel;
use Shop\Api\CategoryApi;
use Shop\Api\CategoryPropApi;
use Shop\Api\ProductApi;
use Shop\Api\ProductGroupApi;
use Shop\Api\ProductSkuApi;
use Shop\Api\StoreApi;

class WxshopProductController extends AdminController {

	public function group(){
		$id = I('id',0);
		if(IS_GET){

			$storeid = I('get.storeid',0);
			$map = array('parentid'=>C('DATATREE.WXPRODUCTGROUP'));
			$result = apiCall(DatatreeApi::QUERY_NO_PAGING,array($map));
            if(!$result['status']){
				$this->error($result['info']);
			}


			$this->assign("groups",$result['info']);
			$this->assign("storeid",$storeid);

			$result = apiCall(ProductGroupApi::QUERY_NO_PAGING,array(array('p_id'=>$id)));
			if(!$result['status']){
				$this->error($result['info']);
			}
			$this->assign("addedgroups",$this->getGroups($result['info']));
			$this->assign("id",$id);
			$this->display();
		}else{

		}
	}

	/**
	 *
	 */
	private function getGroups($groups){
		$str = "";
		foreach($groups as $vo){
			$str = $str.$vo['g_id'].',';
		}

		return $str;

	}

	/**
	 * 商品运费设置
	 */
	public function express(){
		if(IS_GET){

			$productid = I('get.productid','');
			$id = I('get.id',0);

			$result = apiCall(ProductApi::GET_INFO, array(array('product_id'=>$productid) ));

			if(!$result['status']){
				$this->error($result['info']);
			}

			if(is_null($result['info'])){
				$this->error("警告：商品信息获取失败！");
			}

			$location = $result['info']['loc_country'].">>".$result['info']['loc_province'].">>".$result['info']['loc_city'].">>".$result['info']['loc_address'];

			$this->assign("storeid",$result['info']['storeid']);
			$this->assign("location",$location);
			$this->assign("delivery_type",$result['info']['delivery_type']);
			$tmp = json_decode($result['info']['express'],JSON_UNESCAPED_UNICODE);
			$this->assign("express",$tmp);
			if(count($tmp) > 0){
				foreach($tmp as $vo){
					$express[$vo['id']] = $vo['price']/100.0;
				}
				$this->assign("express",$express);
			}
			$this->assign("template_id",$result['info']['template_id']);

			$result = apiCall("Tool/Province/queryNoPaging", array(array('countryid'=>1017) ));
			if($result['status']){
				$this->assign("province",$result['info']);
			}else{
				$this->error("警告：省份信息获取失败！");
			}
//			$wxshopapi = new \Common\Api\WxShopApi($this -> appid, $this -> appsecret);

//			$result = $wxshopapi->expressGetAll();
////			dump($result);
//			if($result['status']){
//				$this->assign("expresslist",$result['info']);
//			}else{
//				$this->error("警告：运费信息获取失败！");
//			}

			$this->assign("countrylist",C('COUNTRY_LIST'));
			$this->assign("productid",$productid);
			$this->assign("id",$id);
			$this->display();
		}else{
//			delivery_info
//					delivery_type
//					template_id
//					express	= []
//						id
//						price
			$query = I('post.query','','htmlspecialchars_decode');
			$query = json_decode($query,JSON_UNESCAPED_UNICODE);

//			dump($query);

			$productid = I('post.productid','');
			if(empty($productid)){
				$this->error("商品ID失效！");
			}

			$flag =  $query['islocchange'];
			$entity = array();
			if($flag){
				$entity['loc_country'] = $query['country'];
				$entity['loc_province'] = $query['province'];
				$entity['loc_city'] = $query['city'];
				$entity['loc_address'] = $query['area'];
			}

			$flag = $query['haspostfee'];
			$templateid = intval($query['templateid']);
			if($flag){
				$entity['attrext_ispostfree'] = 0;
				if($templateid > 0){
					$entity['delivery_type'] = 1;
					$entity['template_id'] = $templateid;
					$entity['express'] = '';
				}else{
					$entity['delivery_type'] = 0;
					foreach($query['express'] as &$vo){
						$vo['price'] = $vo['price']*100.0;
						$vo['id'] = intval($vo['id']);
					}
					$entity['express'] = json_encode($query['express'],JSON_UNESCAPED_UNICODE);
					$entity['template_id'] = 0;
				}
			}else{
				$entity['attrext_ispostfree'] = 1;
				$entity['delivery_type'] = -1;
			}

//			dump($entity);

			$result = apiCall("Shop/Product/save", array(array('product_id'=>$productid),$entity));

			if(!$result['status']){
				$this->error($result['info']);
			}

			$this->success("操作成功！");

		}
	}


	/**
	 * 商品SKU 管理
	 */
	public function sku(){

		if(IS_GET){
			$id = I('get.id',0);

			$result = apiCall("Shop/Product/getInfo", array(array('id'=>$id) ));

			if(!$result['status']){
				$this->error($result['info']);
			}

			if(is_null($result['info'])){
				$this->error("警告：商品信息获取失败！");
			}

			if($result['info']['has_sku'] == 1){
				//多规格
				$skuinfo = $result['info']['sku_info'];

				$this->assign("skuinfo",$this->getSkuValue(json_decode($skuinfo,JSON_UNESCAPED_UNICODE)));

				$skulist = apiCall("Shop/ProductSku/queryNoPaging", array(array('product_id'=>$id)));
				if($skulist['status']){
					if($skulist['info']==null){
						$this->assign('isOk',0);
					}else{
						$this->assign('isOk',1);
					}
					$this->assign("skuvaluelist",json_encode($skulist['info'],JSON_UNESCAPED_UNICODE));
				}
			}
			//dump(json_encode($skulist['info'],JSON_UNESCAPED_UNICODE));
			$this->assign("has_sku",$result['info']['has_sku']);
			$this->assign("storeid",$result['info']['storeid']);
			//dump($result['info']['has_sku']);

			$cate_id = $result['info']['cate_id'];

			$result = apiCall("Shop/Sku/querySkuTable",array($cate_id));

			if($result['status']){
				$this->assign("skulist",$this->color2First($result['info']));
			}

			//dump($this->color2First($result['info']));
			//SKU
			$result = apiCall("Shop/Category/getInfo",array(array('id'=>$cate_id)));
			if(!$result['status']){
				$this->error($result['info']);
			}
			$level = 0;
			$parent = 0;
			$preparent = -1;

			if(is_array($result['info'])){
				$level = $result['info']['level'];
				$parent = $result['info']['parent'];
				$result = apiCall("Shop/Category/getInfo",array(array('id'=>$parent)));
				if(!$result['status']){
					$this->error($result['info']);
				}
				$preparent = $result['info']['parent'];
			}

			$this->assign("cate_id",$level);
			$this->assign("parent",$parent);
			$this->assign("preparent",$preparent);
			$this->assign("cate_id",$cate_id);
			$this->assign("id",$id);
			$this->display();
		}else{

			$id = I('post.id',0);
			$has_sku = I('post.has_sku',1);

			if($has_sku == 0){
				$entity = array(
					'sku_info'=>'',
					'has_sku'=>0,
				);

				$result = apiCall("Admin/Product/saveByID", array($id,$entity));

				if(!$result['status']){
					$this->error($result['info']);
				}

				$this->success("保存成功！");

			}

			$sku_list = I('post.sku_list','');
			$sku_info = I('post.sku_info','');

			$sku_info = json_decode(htmlspecialchars_decode($sku_info),JSON_UNESCAPED_UNICODE);
			$sku_list = json_decode(htmlspecialchars_decode($sku_list),JSON_UNESCAPED_UNICODE);


			$result = apiCall(ProductSkuApi::ADD_SKU_LIST, array($id,$sku_info,$sku_list));

			if(!$result['status']){
				$this->error($result['info']);

			}else{

				$this->success("保存成功！");

			}
		}
	}



	/**
	 *
	 */
	private function getSkuValue($skuvalue){
		$valuelist = "";
		foreach($skuvalue as $value){
			foreach($value['vid'] as $vo){
				$valuelist = $valuelist.$vo.",";
			}
		}
		return $valuelist;
	}

	/**
	 * 商品详情页/新增
	 * @param $get.productid  商品ID
	 * @param $get.storeid 店铺ID
	 */
	public function detail(){
		if(IS_GET){
			$productid = I('get.productid','');
			$id = I('get.id',0);
			$storeid = I('get.storeid',0);
			if(empty($productid)){
				$this->error("缺少商品ID");
			}
			if(empty($storeid)){
				$this->error("缺少店铺ID");
			}
			$map['product_id'] = $productid;
			$result = apiCall(ProductApi::GET_INFO,array($map));
			if($result['status']){
				$detail = $result['info']['detail'];

			}else{
				$this->error("商品信息获取失败！");
			}


			$this->assign("detail",json_decode(htmlspecialchars_decode($detail),JSON_UNESCAPED_UNICODE));
			$this->assign("productid",$productid);
			$this->assign("storeid",$storeid);
			$this->assign("id",$id);
			$this->display();

		}else{
			$detail = I("post.detail",'');
			$productid = I("post.productid",'');

			$product_detail = array();

			if(!empty($detail)){
				$detail_decode = json_decode(htmlspecialchars_decode($detail),JSON_UNESCAPED_UNICODE);
			}else{
				$this->error("详情为空");
			}

			$map['product_id'] = $productid;
			$result = apiCall(ProductApi::SAVE,array($map,array('detail'=>$detail)));
			if($result['status']){
				$this->success("修改成功！");
			}else{
				$this->error($result['info']);
			}
		}
	}

	/**
	 * 首页/商品管理页面
	 */
	public function index() {
		$onshelf = I('onshelf', 0);
        $name = I('post.name', '');

		$storeid = I('storeid', 0, "intval");
		if (empty($storeid)) {
			$this -> error("缺少店铺ID参数！");
		}

        //检测storeid 是否合法
        $result = apiCall(StoreApi::GET_INFO,array(array('id'=>$storeid,'uid'=>UID)));

        if(!$result['status']){
            $this -> error($result['info']);
        }

        if(is_null($result['info'])){
            $this -> error("店铺ID不合法!");
        }

        $params = array('onshelf' => $onshelf,'storeid'=>$storeid);

        $map = array();
        if (!empty($name)) {
            $map['name'] = array('like', '%'.$name.'%');
            $params['name'] = $name;
        }
        $map['onshelf'] = $onshelf;
        $map['storeid'] = $storeid;
        $page = array('curpage' => I('get.p', 0), 'size' => C('LIST_ROWS'));
        $order = " createtime desc ";

        $result = apiCall(ProductApi::QUERY, array($map, $page, $order, $params));

		//
		if ($result['status']) {

			$this -> assign('name', $name);
			$this -> assign('onshelf', $onshelf);
			$this -> assign('storeid', $storeid);
			$this -> assign('show', $result['info']['show']);
			$this -> assign('list', $result['info']['list']);

			$store = apiCall(StoreApi::GET_INFO, array(array('id'=>$storeid)));
			if(!$store['status']){
				$this->error($store['info']);
			}
			$this->assign("store",$store['info']);
			$this -> display();
		} else {
			LogRecord('INFO:' . $result['info'], '[FILE] ' . __FILE__ . ' [LINE] ' . __LINE__);
			$this -> error(L('UNKNOWN_ERR'));
		}
	}


    /**
     * 商品上下架
     * @internal param 删除成功后跳转 $success_url
     */
	public function shelf() {
		$status = I('get.on',0,'intval');
		$map = array('id' => I('get.id', -1));

		$entity['onshelf'] = $status;
		$result = apiCall(ProductApi::SAVE, array($map,$entity));

		if ($result['status'] === false) {
			LogRecord('[INFO]' . $result['info'], '[FILE] ' . __FILE__ . ' [LINE] ' . __LINE__);
			$this -> error($result['info']);
		} else {
			$this -> success(L('RESULT_SUCCESS'));
		}

	}

    /**
     * 单个删除
     * @param 删除成功后跳转|bool $success_url 删除成功后跳转
     */
	public function delete($success_url = false) {

		if ($success_url === false) {
			$success_url = U('Admin/WxshopProduct/index',array('storeid'=>$storeid));
		}

		//TODO: 检测商品的其它数据是否存在
		$map = array('id' => I('id', -1));

		$result = apiCall(ProductApi::DELETE, array($map));

		if ($result['status'] === false) {
			LogRecord('[INFO]' . $result['info'], '[FILE] ' . __FILE__ . ' [LINE] ' . __LINE__);
			$this -> error($result['info']);
		} else {
			$this -> success(L('RESULT_SUCCESS'));
		}

	}

	/**
	 * 商品预创建－选择类目
	 */
	public function precreate() {

		if (IS_POST) {
			//保存
		} else {

			$map = array('parent'=>0);
			$result = apiCall(CategoryApi::QUERY_NO_PAGING, array($map));

			$storeid = I('get.storeid', 0);

			if ($storeid == 0) {
				$this -> error("缺少商铺ID参数");
			}

			$this -> assign("storeid", $storeid);
			if ($result['status']) {
				$this -> assign("rootcate", $result['info']);
			}
			$this -> display();
		}
	}

	/**
	 * 添加商品
	 *
	 */
	public function create() {

		if (IS_POST) {

			$base_attr = $this -> getBaseAttr();
			$storeid = I('storeid', 0);

			if ($storeid == 0) {
				$this -> error("缺少商铺ID参数");
			}

			$attrext = array('isPostFree' => 1, 'isHasReceipt' => I('post.ishasreceipt', 0), 'isUnderGuaranty' => I('post.isunderguaranty', 0), 'isSupportReplace' => I('post.issupportreplace', 0), 'location' => array('country' => '中国', 'province' => '四川省', 'city' => '内江市', 'address' => '威远县'));
			$sku_list = $this -> getSKUList();

			$product = array('product_base' => $base_attr, 'attrext' => $attrext, 'sku_list' => $sku_list);
			//dump($product);
			$product_id = GUID();


			$result = $this -> addToProduct($storeid, $product_id, $product);

			if ($result['status']) {
				$this -> success("操作成功!", U('Admin/WxshopProduct/index', array('storeid' => $storeid)));
			} else {
				$this -> error($result['info']);
			}

		} else {
			$catename = I('catename', '');
			$storeid = I('storeid', 0);
			$cates = I("get.cates", '');
			$cates = explode("_", $cates);
			if (count($cates) <= 1) {
				$this -> error("商品类目错误！");
			}

			$this -> assign("cate_id", $cates[count($cates)-1]);
			$this -> assign("storeid", $storeid);
			$this -> assign("catename", $catename);
			$this -> assign("cates", I('cates', ''));
			$result=apiCall(FreightTemplateApi::QUERY_NO_PAGING);
			//dump($result);
			$this->assign('templates',$result['info']);
			$this -> display();
		}
	}

	/**
	 * 商品信息编辑
	 */
	public function edit(){
		if(IS_GET){

			$id = I('get.id',0);
			$result = apiCall(ProductApi::GET_INFO, array(array('id'=>$id)));

			if($result['status']){
				$imgs = explode(",",$result['info']['img']);
				array_pop($imgs);
				$this->assign("imgs",$imgs);
				//dump($result['info']);
				$this->assign("vo",$result['info']);
			}

			$result=apiCall(FreightTemplateApi::QUERY_NO_PAGING);
			//dump($result);
			$this->assign('templates',$result['info']);
			$this->display();
		}else{
			$id = I('post.id',0);
			$buylimit = I('buylimit',0);
			if(empty($buylimit)){
				$buylimit = 0;
			}
			$price = I('price',0,'floatval');
			$price = $price * 100.0;
			$ori_price = I('ori_price',0,'floatval');
			$ori_price = $ori_price * 100.0;
			$profit=I('profit',0,'floatval');
			$profit=$profit*100.0;
			$entity = array(
				'main_img'=>I('main_img',''),
				'img'=> I('post.img', ''),
				'name'=>I('product_name',''),
				'price'=>$price,
				'ori_price'=>$ori_price,
				'profit'=>$profit,
				'quantity'=>I('quantity',0,'intval'),
				'template_id'=>I('template_id',-1),
				'buy_limit'=>$buylimit,
				'attrext_ishasreceipt'=>I('ishasreceipt',0),
				'attrext_isunderguaranty'=>I('isunderguaranty',0),
				'attrext_issupportreplace'=>I('issupportreplace',0),
			);
			$result = apiCall(ProductApi::SAVE_BY_ID,array($id,$entity));
			if(!$result['status']){
				$this->error($result['info']);
			}

			$this->success(L('RESULT_SUCCESS'));

		}
	}

	/**
	 * 指定分类的所有属性
	 */
	public function cateAllProp() {

		if (IS_AJAX) {
			$cate_id = I('cate_id', 0);
			$map = array('cate_id'=>$cate_id);
			$result = apiCall(CategoryPropApi::QUERY_PROP_TABLE, array($map));

			if ($result['status']) {
				$this -> success($result['info']);
			} else {
				$this -> error($result['info']);
			}
		}
	}

	//==========================私有方法
	/**
	 * 将产品信息保存到数据库
	 */
	private function addToProduct($storeid, $productid, $product) {

		$has_sku = I('post.has_sku', 0, 'intval');

		$entity = array(
                    'uid'=>UID,
					'storeid' => $storeid,
					'wxaccountid' => getWxAccountID(),
					'product_id' => $productid,
					'name' => $product['product_base']['name'],
					'main_img' => $product['product_base']['main_img'],
					'img' => I('post.img', ''),
					'template_id'=>$product['product_base']['template_id'],
					'buy_limit' => $product['product_base']['buy_limit'],
					'cate_id' => $product['product_base']['category_id'][0],
					'delivery_type' => -1, // 包邮
					'template_id' => $product['product_base']['template_id'],
					'express_id' => 0,
					'express_price' => 0,
					'attrext_ispostfree' => $product['attrext']['isPostFree'],
					'attrext_ishasreceipt' => $product['attrext']['isHasReceipt'],
					'attrext_isunderguaranty' => $product['attrext']['isUnderGuaranty'],
					'attrext_issupportreplace' => $product['attrext']['isSupportReplace'],
					'loc_country' => $product['attrext']['location']['country'],
					'loc_province' => $product['attrext']['location']['province'],
					'loc_city' => $product['attrext']['location']['city'],
					'loc_address' => $product['attrext']['location']['address'],
					'has_sku' => intval($has_sku),
					'detail' => '',
					'onself' => '0',
					'status' => 1,
					'properties'=> I('post.property', ''),
					'profit'=>(float)I('post.profit',0)*100,
					'sku_info'=>'',
				);
		if ($has_sku == 0) {
			$entity['ori_price'] = $product['sku_list'][0]['ori_price'];
			$entity['price'] = $product['sku_list'][0]['price'];
			$entity['quantity'] = $product['sku_list'][0]['quantity'];
			$entity['product_code'] = $product['sku_list'][0]['product_code'];
		}

		$result = apiCall(ProductApi::ADD, array($entity));

		return $result;
	}

	private function getBaseAttr() {

		$cates = I("post.cates", '');
		$cates = explode("_", $cates);
		if (count($cates) <= 1) {
			$this -> error("商品类目错误！");
		}

		//属性
		$property = I('post.property', '');
		$property = explode(";", $property);
		$properties = array();
		//		dump($property);
		foreach ($property as $vo) {
			$prop = explode(",", $vo);
			if (count($prop) == 2) {
				$properties[] = array('id' => $prop[0], 'vid' => $prop[1]);
			}
		}

		//SKU
		$sku_info = array();

		$category = $cates[count($cates) - 1];
		$main_img = I('post.main_img', '');
		if (I('post.isbuylimit', '0') == 1) {
			$buylimit = I('post.buylimit', 0);
		} else {
			$buylimit = 0;
		}
		$imglist = array();
		$img = explode(",", I('post.img', ''));
		//		dump($img);
		foreach ($img as $vo) {
			if ($vo) {
				$imglist[] = $vo;
			}
		}

		//
		return array(
			'name' => I('post.product_name', ''),
			'category_id' => array($category),
			'img' => $imglist,
			'template_id'=>I('template_id',-1),
			 'main_img' => $main_img,
			'datail' => array(),
			'property' => $properties,
			'sku_info' => $sku_info,
			'buy_limit' => $buylimit);
	}

	private function getSKUList() {

		$has_sku = I('post.has_sku', '0');

		if ($has_sku == "0") {
			$ori_price = I('post.ori_price', 0, 'intval');
			$price = I('post.price', 0, 'intval');
			//统一规格
			$sku = array('sku_id' => '', //商品添加时默认为统一规格
			'icon_url' => I('post.main_img', ''),
			 'ori_price' => $ori_price * 100, 'price' => $price * 100,
			 'quantity' => I('post.quantity', 0, 'intval'),
			 'product_code' => I('post.product_code', "")
			  );
		} else {
			//商品添加页面不增加多规格功能
			//post.ori_price[]

		}
		return array($sku);
	}


    /**
	 * 将颜色SKU 放在最前面
	 */
	private function color2First($skulist){
		$colorIndex = 0;
		for($i=0;$i<count($skulist);$i++){
			if($skulist[$i]->name == "颜色"){
				$colorIndex = $i;
				break;
			}
		}

		if($colorIndex > 0){
			$temp = $skulist[0];
			$skulist[0] = $skulist[$colorIndex];
			$skulist[$colorIndex] = $temp;
		}
		return $skulist;

	}
}
