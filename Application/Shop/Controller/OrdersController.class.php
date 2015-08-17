<?php
// .-----------------------------------------------------------------------------------
// | WE TRY THE BEST WAY
// |-----------------------------------------------------------------------------------
// | Author: 贝贝 <hebiduhebi@163.com>
// | Copyright (c) 2013-2016 杭州博也网络科技, http://www.itboye.com. All Rights Reserved.
// |-----------------------------------------------------------------------------------

namespace Shop\Controller;
use Admin\Api\AddonsApi;
use Admin\Api\FreightTemplateApi;
use Shop\Api\AddressApi;
use Shop\Api\OrderCommentApi;
use Shop\Api\OrdersApi;
use Shop\Api\OrdersInfoViewApi;
use Shop\Api\OrdersItemApi;
use Shop\Api\OrderStatusApi;
use Shop\Api\OrderStatusHistoryApi;
use Shop\Api\ProductApi;
use Shop\Api\ProductSkuApi;
use Shop\Api\StoreApi;
use Shop\Model\OrdersModel;
use Think\Controller;
use Tool\Api\AreaApi;
use Tool\Api\CityApi;
use Tool\Api\ProvinceApi;

class OrdersController extends ShopController {

    protected function _initialize() {
        parent::_initialize();
    }


    /**
     * 订单详情
     */
    public function view(){
        $id = I('get.id',0);
        $map = array('id' => $id);
        $result = apiCall(OrdersInfoViewApi::GET_INFO, array($map));
        if ($result['status']) {
            $orderid = $result['info']['orderid'];
            $this -> assign("order", $result['info']);
            $result = apiCall(OrdersItemApi::QUERY_NO_PAGING, array(array('orders_id'=>$id)));
            if(!$result['status']){
                ifFailedLogRecord($result, __FILE__.__LINE__);
                $this->error($result['info']);
            }

            $this -> assign("items", $result['info']);
            $backStatus = OrdersModel::ORDER_BACK;
            $result = apiCall(OrderStatusHistoryApi::GET_INFO, array(array('orders_id'=>$orderid,'status_type'=>'ORDER','next_status'=>$backStatus)));
            if(!$result['status']){
                ifFailedLogRecord($result, __FILE__.__LINE__);
                $this->error($result['info']);
            }

            $this -> assign("backStatus", $backStatus);
            $this -> assign("backinfo", $result['info']);
            $this ->theme($this->themeType)-> display();
        } else {
            $this -> error($result['info']);
        }
    }



    /**
     * 获取订单
     * @param post.type 0=全部,1=待付款,2=待发货,3=待收货,4＝待评论
     */
    public function orderlist() {
        $type = I('post.type', 0, 'intval');

        //是否获取物流信息
        $shouldGetExpressInfo = false;
        $map = array();
        if ($type == 1) {
            //待付款
            $map['pay_status'] = OrdersModel::ORDER_TOBE_PAID;
        } elseif($type != 0) {
            //货到付款，在线已支付
            $map['pay_status'] = array('in', array(OrdersModel::ORDER_PAID, OrdersModel::ORDER_CASH_ON_DELIVERY));

        }

        if ($type == 2) {
            //1. 已支付、货到付款
            //2. 待发货
            //
            $map['order_status'] = OrdersModel::ORDER_TOBE_SHIPPED;

        } elseif ($type == 3) {
            //1. 已支付、货到付款
            //2. 已发货
            $map['order_status'] = OrdersModel::ORDER_SHIPPED;
            $shouldGetExpressInfo = true;
        } elseif ($type == 4) {
            //1. 已支付、货到付款
            //2. 已收货
            //3. 待评论
            $map['order_status'] = OrdersModel::ORDER_RECEIPT_OF_GOODS;
            $map['comment_status'] = OrdersModel::ORDER_TOBE_EVALUATE;
            $shouldGetExpressInfo = true;

        }

        $map['wxuser_id'] = $this -> userinfo['id'];
        //TODO: 订单假删除时不查询
        $map['status'] = 1;
        $orders = " createtime desc ";
        $page = array('curpage'=>I('post.p',0),'size'=>3);

        $result = apiCall(OrdersApi::QUERY, array($map, $page, $orders));

        ifFailedLogRecord($result, __FILE__ . __LINE__);

        //1. 订单信息
        $order_list = $result['info']['list'];
        $store_ids = array();
        $order_ids = array();
        $result_list = array();

        $store_key = array();

        foreach ($order_list as $vo) {
            $entity = array(
                'orderid' => $vo['id'],
                'price' => number_format($vo['price']/100.0,2), //订单总价
                'storeid' => $vo['storeid'],
                'order_status'=>$vo['order_status'],
                'comment_status'=>$vo['comment_status'],
                'order_status_desc'=> getTaobaoOrderStatus($vo['order_status']),
                'pay_status'=>$vo['pay_status'],
                '_items' => array(), //商品列表
                '_store' => array(), //店铺信息
            );

            $result_list[$vo['id']] = $entity;
            if(!array_key_exists($vo['storeid'], $store_key)){
                array_push($store_ids, $vo['storeid']);
                $store_key[$vo['storeid']] = $vo['storeid'];
            }
            array_push($order_ids, $vo['id']);
        }


        if (count($store_ids) > 0) {
            $mapStore = array();
            $mapStore['id'] = array('in', $store_ids);
            //2. 获取店铺信息
            $result = apiCall(StoreApi::QUERY_NO_PAGING, array($mapStore));
            ifFailedLogRecord($result, __FILE__ . __LINE__);
            foreach ($result_list as &$vo_obj){
                foreach ($result['info'] as $vo) {
                    if ($vo['id'] == $vo_obj['storeid']) {
                        $vo_obj['_store'] = $vo;
                        break;
                    }
                }
            }

        }
        //3. 获取订单商品信息

        if (count($store_ids) > 0) {
            $mapOrder = array();
            $mapOrder['orders_id'] = array('in', $order_ids);
            $result = apiCall(OrdersItemApi::QUERY_NO_PAGING, array($mapOrder));

            ifFailedLogRecord($result, __FILE__ . __LINE__);

            foreach ($result['info'] as $vo) {
                $entity = array(
                    'name'=>$vo['name'],
                    'p_id'=>$vo['p_id'],
                    'img'=>$vo['img'],
                    'price'=> number_format($vo['price']/100.0,2),
                    'ori_price'=> number_format($vo['ori_price']/100.0,2),
                    'sku_id'=>$vo['sku_id'],
                    'sku_desc'=>$vo['sku_desc'],
                    'count'=>$vo['count'],
                    'orders_id'=>$vo['orders_id'],
                    'createtime'=> date("Y-m-d H:i:s",$vo['createtime']),
                );

                if(isset($result_list[$vo['orders_id']])){
                    array_push($result_list[$vo['orders_id']]['_items'], $entity);
                }

            }

        }

        if(IS_POST){
            //dump($result_list);
            $this->success($result_list);
        }else{
            $this->error("禁止访问！");
        }

    }

    private function toArray($param){
        if(!is_array($param)){
            return array($param);
        }
        return $param;
    }
    /**
     * 订单确认
     * 运费的计算规则，
     * 1. 同一家的商品，购商品中运费价格总和的作为最终运费
     * TODO: 暂不支持运费模板的运费计算
     */
    public function confirm() {
        //标识是否直接从session中取订单确认信息
        $fromsession = I('get.fromsession', 0);
        $p_id_arr = I('post.p_id', array());
        $sku_id_arr = I('post.sku_id', array());
        $price_arr = I('post.price', array());
        $count_arr = I('post.count', array());
        $p_id_arr = $this->toArray($p_id_arr);
        $sku_id_arr = $this->toArray($sku_id_arr);
        $price_arr = $this->toArray($price_arr);
        $count_arr = $this->toArray($count_arr);


        //针对多规格商品的立即购买操作
        $hebidu_skuchecked = I('post.hebidu_skuchecked', '');
        if (!empty($hebidu_skuchecked)) {
            array_push($sku_id_arr, $hebidu_skuchecked);
            $count_arr[0] = I('post.sku_count', 1);

        }

        //dump($fromsession);
        if (intval($fromsession) == 1) {
            if (!session("?confirm_order_info")) {
                $this -> redirect('Shop/Index/index');
            }
            //从session中取
            $list = session("confirm_order_info");
            //dump($list);
            if (is_null($list)) {
                $this -> redirect('Shop/Index/index');
            }

        } else {
            //dump($p_id_arr);
            if (count($p_id_arr) == 0) {
                LogRecord("参数错误", __FILE__ . __LINE__);
                $this -> error("参数错误！");
            }
            //dump($p_id_arr);
            //获取商品信息
            $product_list = $this -> getProductList($p_id_arr);

            //获取商品SKU信息
            unset($map['id']);
            array_push($sku_id_arr, -1);
            $map['sku_id'] = array('in', $sku_id_arr);
            $order = " product_id desc ";
            $result = apiCall(ProductSkuApi::QUERY_NO_PAGING, array($map, $order));
            if (!$result['status']) {
                LogRecord($result['info'], __FILE__ . __LINE__);
                $this -> error($result['info']);
            }
            //商品数量与商品进行关联
            $tmp_count = array();
            for ($i = 0; $i < count($count_arr); $i++) {
                $tmp_count[$p_id_arr[$i]] = $count_arr[$i];
            }

            $product_sku_list = $result['info'];
            $tmp_arr = array();
            $store_ids = array();
            foreach ($product_sku_list as $vo) {
                $tmp_arr[$vo['product_id']] = $vo;
            }

            //商品SKU、运费计算与商品进行关联
            $all_price = 0.0;
            $all_express = 0.0;
            $tmp_store = array();
            //遍历商品列表


            foreach ($product_list as &$vo) {

                //dump($vo['template_id']);
                //
                $entity = array('p_id' => $vo['id'], 'has_sku' => $vo['has_sku'], //标志是否使用SKU信息
                    'img' => $vo['main_img'], 'price' => $vo['price'],'template_id'=>$vo['template_id'], 'ori_price' => $vo['ori_price'], 'name' => $vo['name'], 'sku_id' => '', 'sku_desc' => $tmp_arr[$vo['id']], 'count' => $tmp_count[$vo['id']]//购买商品数量
                );




                if (intval($vo['has_sku']) == 1) {
                    //有规格的情况下
                    $entity['price'] = $tmp_arr[$vo['id']]['price'];
                    $entity['sku_id'] = $tmp_arr[$vo['id']]['sku_id'];
                    $entity['ori_price'] = $tmp_arr[$vo['id']]['ori_price'];
                    if (!empty($tmp_arr[$vo['id']]['icon_url'])) {
                        $entity['img'] = $tmp_arr[$vo['id']]['icon_url'];
                    }
                }

                if (!isset($tmp_store[$vo['storeid']])) {
                    $tmp_store[$vo['storeid']] = array('products' => array(), 'post_price' => 0.0, 'total_price' => 0.0);
                }

                $all_price += ($entity['count'] * $entity['price'] / 100.0);
                //每个店铺的总价
                $tmp_store[$vo['storeid']]['total_price'] += ($entity['count'] * $entity['price'] / 100.0);
                //取得该商品的运费
                $express_price = $this -> getExpressPrice($vo);
                $express_price = $express_price / 100.0;
                //转化为元
                //取得最大的运费

                $entity['post_price'] = $express_price;
                $tmp_store[$vo['storeid']]['post_price'] += $express_price;
                $all_express += $express_price;

                array_push($tmp_store[$vo['storeid']]['products'], $entity);
                array_push($store_ids, $vo['storeid']);
            }

            unset($map['sku_id']);
            $map['id'] = array('in', $store_ids);
            $order = " id asc ";
            $result = apiCall(StoreApi::QUERY_NO_PAGING, array($map, $order));
            if (!$result['status']) {
                LogRecord($result['info'], __FILE__ . __LINE__);
                $this -> error($result['info']);
            }
            $stores = $result['info'];

            foreach ($stores as &$vo) {
                if (isset($tmp_store[$vo['id']])) {
                    $tmp_store[$vo['id']]['store'] = $vo;
                }
            }

            $list = array('list' => $tmp_store, 'all_price' => $all_price, 'all_express' => $all_express, );



        }

        //获取默认收货地址

        $default_address = $this -> getDefaultAddress();
//        dump($default_address);
        $this -> assign("default_address", $default_address);

       // dump($list);
        $citys = apiCall(AddressApi::GET_INFO, array( array('wxuserid' => $this -> userinfo['id'], 'default' => 1)));
        //获取当前用户ID
        $cityID = $citys['info']['city'];
        $freights=0.00;
        if($list['list'][1]['total_price']<(float)$list['list'][1]['store']['free_shipping']){
            if($cityID!=null){
                foreach($list['list'][1]['products'] as $v=>$p){
                    $t=apiCall(FreightTemplateApi::QUERY_WITH_ADDRESS,array(array('id'=>$p['template_id'])));
                    //dump($t);
                    $freight=0.00;
                    if(count($t[0]['freightAddress'])==1){
                        $freight=(float)$t[0]['freightAddress'][0]['money']*(float)$p['count'];
                    }else{
                        $isExists=false;
                        foreach($t[0]['freightAddress'] as $f){
                            $fs=explode(',',$f['addressids']);
                            if(in_array($cityID,$fs)){
                                $freight=(float)$f['money']*(float)$p['count'];
                                $isExists=true;
                                break;
                            }
                        }
                        if(!$isExists){
                            $freight=(float)$t[0]['freightAddress'][0]['money']*(float)$p['count'];
                        }
                    }
                    //$list['list'][1]['products'][$v]['company']
                    $list['list'][1]['products'][$v]['post_price']=$freight;
                    $freights+=$freight;
                }
            }
        }else{
            foreach($list['list'][1]['products'] as $v=>$p){
                $list['list'][1]['products'][$v]['post_price']=0;
            }
            $freights=-1;
        }
            //$list['list'][1]['products'][$v]['post_price']=0;


        $this -> assign("list", $list);
        session("confirm_order_info", $list);
        //dump($list);
        $this->assign('freights',$freights);
        $this ->theme($this->themeType)-> display();

    }

    /**
     * 订单保存
     */
    public function save() {
        //收货地址ID
        $address_id = I('post.address_id', 0);
        $province_name = I('post.province_name', '');
        $city_name = I('post.city_name', '');
        $area_name = I('post.area_name', '');
        $notes = I('post.notes', array());
        $userinfo = $this -> userinfo;
        if (IS_POST && is_array($userinfo)) {
            addWeixinLog($userinfo, '[session]saveispost');
            //购买的商品的列表
            $buyProductList = session("confirm_order_info");
            if(empty($buyProductList)){
                $this->error("您尚未选购商品!");
            }
            addWeixinLog($buyProductList, '购买商品的列表');

            $map = array('id' => $address_id);

            $result = apiCall(AddressApi::GET_INFO, array($map));

            if (!$result['status']) {
                $this -> error($result['info']);
            }

            if (is_null($result['info'])) {
                LogRecord("收货地址信息获取失败", __FILE__ . __LINE__);
                //如果是空的
                $this -> error("收货地址信息获取失败！");
            }
            $address_info = $result['info'];
            //总价，不含运费
            $all_price = $buyProductList['all_price'];
            //运费
            $all_express = $buyProductList['all_express'];

            //订单对象
            $entity = array('wxaccountid' => $this -> wxaccount['id'], 'storeid' => 0, 'wxuser_id' => $userinfo['id'], 'price' => 0, 'mobile' => $address_info['mobile'], 'wxno' => $address_info['wxno'], 'contactname' => $address_info['contactname'], 'note' => '', 'country' => $address_info['country'], 'province' => $province_name, 'city' => $city_name, 'area' => $area_name, 'detailinfo' => $address_info['detailinfo'], 'orderid' => '', 'items' => '', );
            $i = 0;
            $ids = '';
            $orderid = $this -> getOrderID();
//			addWeixinLog($buyProductList['list'],'订单');
            //分店铺保存订单，每个店铺一张订单
            foreach ($buyProductList['list'] as $key => $vo) {
                //店铺ID
                $entity['storeid'] = $key;
                $entity['orderid'] = $orderid . '_' . $key;
                $entity['items'] = $vo;
                //
                $price = 0.0;
                foreach ($vo['products'] as $item) {
//					addWeixinLog($item,'订单［test］');
                    $price += ($item['price'] * intval($item['count']));
                }

                $entity['price'] = $price;
                if ($i < count($notes)) {
                    $entity['note'] = $notes[$i];

                }

                $i++;
                //				dump($entity['items']['products']);
                //				exit();

                //dump($entity);
                $result = apiCall(OrdersApi::ADD_ORDER, array($entity));
                //				addWeixinLog($result,'订单3333');
                if (!$result['status']) {
                    LogRecord($result['info'], __FILE__ . __LINE__);
                    $this -> error($result['info']);
                }
                $ids .= $result['info'] . '-';
            }
            $ids = trim($ids, "-");
            //TODO: 从购物车中移除相对应的商品，根据店铺ID，商品ID，商品SKU
            //目前 就直接删除购物车
            session("shoppingcart", null);
            session("confirm_order_info", null);

            $this -> success("订单保存成功，前往支付！", C('SITE_URL') . "/index.php/Shop/Pay/pay/id/$ids?showwxpaytitle=1");

        } else {
            LogRecord("禁止访问！", __FILE__ . __LINE__);
            $this -> error("禁止访问！");
        }

    }

    /**
     *  订单评价
     */
    public function evaluation(){
        if(IS_GET){
            $id = I('get.id',0,'intval');
            $result = apiCall(OrdersApi::QUERY_NO_PAGING , array(array( 'id'=>$id )));

            if(!$result['status']){
                $this->error($result['info']);
            }
            $map=array(
                'orders_id'=>$result['info'][0]['id'],
            );
            $item=apiCall(OrdersItemApi::QUERY_NO_PAGING,array($map));

            //dump($item);
            $result['info'][0]['img']=$item['info'][0]['img'];
            $result['info'][0]['name']=$item['info'][0]['name'];
            $result['info'][0]['p_id']=$item['info'][0]['p_id'];
            //dump($result);
            $this->assign("items",$result['info']);
            $this->theme($this->themeType)->display();
        }else{
            $orders_id = I('get.id',0,'intval');
            $pid_arr = I("post.pid",array());
            $score_arr = I("post.score",array());
            $logistics_service_arr = I("post.logistics_service",array());
            $delivery_speed_arr = I("post.delivery_speed",array());
            $service_attitude_arr = I("post.service_attitude",array());
            $text_arr = I("post.text",array());


            $result = apiCall(OrderCommentApi::Add_ARRAY , array($orders_id,$this->userinfo['id'],$pid_arr,$score_arr,$logistics_service_arr,$delivery_speed_arr,$service_attitude_arr,$text_arr));

            if(!$result['status']){
                $this->error($result['info']);
            }

            $this->success("评价成功!",U('Shop/User/order'));
        }
    }

    //==============单订单状态变更操作

    /**
     * TODO: 确认收货
     * 	权限验证
     */
    public function confirmReceive(){

        $id= I('get.id',0);

        $result = apiCall(OrderStatusApi::CONFIRM_RECEIVE, array($id,false,UID));

        if(!$result['status']){
            $this->error($result['info']);
        }

        $this->success("操作成功!");

    }

    /**
     * TODO: 取消订单
     */
    public function cancelOrder(){
        //检测订单状态
        //订单状态只能为,
        $map = array(
            'id'=>I('get.id',0)
        );
        //假删除订单
        $result = apiCall(OrdersApi::PRETEND_DELETE, array($map));
        ifFailedLogRecord($result, __FILE__.__LINE__);

        if(!$result['status']){
            $this->error($result['info']);
        }

        $this->success("取消成功！");

    }



    //===================private

    /**
     * 获取商品集合
     * @param $p_id_arr 商品id数组
     */
    private function getProductList($p_id_arr) {
        $map = array();
        $map['id'] = array('in', $p_id_arr);
        $order = " id desc ";
        $result = apiCall(ProductApi::QUERY_NO_PAGING, array($map, $order));

        if (!$result['status']) {
            LogRecord($result['info'], __FILE__ . __LINE__);
            $this -> error($result['info']);
        }
        return $result['info'];
    }

    private function getDefaultAddress() {
        $result = apiCall(AddressApi::GET_INFO, array( array('wxuserid' => $this -> userinfo['id'], 'default' => 1)));
        if (!$result['status']) {
            LogRecord($result['info'], __FILE__ . __LINE__);
        }
//        dump($result);
        $default_address = $result['info'];
        if(empty($default_address)){
            return $default_address;
        }

        //默认收货地址
        $province_one = apiCall(ProvinceApi::GET_INFO, array( array("provinceID" => $default_address['province'])));
        $city_one = apiCall(CityApi::GET_INFO, array( array("cityID" => $default_address['city'])));
        $area_one = apiCall(AreaApi::GET_INFO, array( array("areaID" => $default_address['area'])));

        if (is_array($province_one['info'])) {
            $default_address['province_name'] = $province_one['info']['province'];
        }
        if (is_array($city_one['info'])) {
            $default_address['city_name'] = $city_one['info']['city'];
        }
        if (is_array($area_one['info'])) {
            $default_address['area_name'] = $area_one['info']['area'];
        }

        return $default_address;
    }

    private function getOrderID() {
        return date('YmdHis', time()) . $this -> randInt();
    }

    private function randInt() {
        srand(GUID());
        return rand(10000000, 99999999);
    }

    /**
     * 获取商品的运费
     */
    private function getExpressPrice($product) {

        if (intval($product['attrext_ispostfree']) == 1) {
            return 0;
        }

        if (intval($product['delivery_type']) == 0) {
            //快递，EMS，
            $express = json_decode($product['express']);

            if (count($express) > 0) {
                return ($express[0] -> price);
            } else {
                return 0;
            }

        } elseif (intval($product['delivery_type']) == 1) {
            //使用运费模板
        }

        return 0;
    }

}
