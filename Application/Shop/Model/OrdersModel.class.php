<?php
// .-----------------------------------------------------------------------------------
// | WE TRY THE BEST WAY
// |-----------------------------------------------------------------------------------
// | Author: 贝贝 <hebiduhebi@163.com>
// | Copyright (c) 2013-2016 杭州博也网络科技, http://www.itboye.com. All Rights Reserved.
// |-----------------------------------------------------------------------------------

namespace Shop\Model;

use Think\Model;

 
class OrdersModel extends Model{
	
	/**
	 * 订单退回
	 */
	const ORDER_BACK = 12;
	/**
	 * 待确认
	 */
	const ORDER_TOBE_CONFIRMED = 2;
	/**
	 * 待发货
	 */
	const ORDER_TOBE_SHIPPED = 3;
	/**
	 * 已发货
	 */
	const ORDER_SHIPPED = 4;
	/**
	 * 已收货
	 */
	const ORDER_RECEIPT_OF_GOODS = 5;
	/**
	 * 已退货
	 */
	const ORDER_RETURNED = 6;
	/**
	 * 已完成
	 */
	const ORDER_COMPLETED = 7;
	/**
	 * 取消或交易关闭
	 */
	const ORDER_CANCEL = 8;

	const ORDER_TOREFUND=9;
	
	
	//订单支付状态
	/**
	 * 待支付
	 */
	const ORDER_TOBE_PAID = 0;
	/**
	 * 货到付款
	 */
	const ORDER_CASH_ON_DELIVERY = 5;
	/**
	 * 已支付
	 */
	const ORDER_PAID = 1;
	/**
	 * 已退款
	 */
	const ORDER_REFUND = 2;
	
	//订单评论状态
	
	
	/**
	 * 待评论
	 */
	const ORDER_TOBE_EVALUATE = 0;
	/**
	 * 已评论
	 */
	const ORDER_HUMAN_EVALUATED = 1;
	/**
	 * 超时、系统自动评论
	 */
	const ORDER_SYSTEM_EVALUATED = 2;
	

	protected $_auto = array(
		array('status',1,self::MODEL_INSERT),
		array('pay_status',self::ORDER_TOBE_PAID,self::MODEL_INSERT),
		array('order_status',self::ORDER_TOBE_CONFIRMED,self::MODEL_INSERT),
		array('evalute_status',self::ORDER_TOBE_EVALUATE,self::MODEL_INSERT),
		array('createtime',NOW_TIME,self::MODEL_INSERT),
		array('updatetime','time',self::MODEL_BOTH,"function"),
	);
}
