<?php
namespace Shop\Api;
use Common\Api\Api;
use Shop\Model\OrderRefundModel;

/**
 * Created by PhpStorm.
 * User: an
 * Date: 2015/8/20
 * Time: 17:38
 */
class OrderRefundApi extends  Api{
    const ADD="Shop/OrderRefund/add";

    const QUERY="Shop/OrderRefund/query";
	
	const QUERY_NO_PAGING="Shop/OrderRefund/queryNoPaging";

    const SAVE_BY_ID="Shop/OrderRefund/saveById";

    const SAVE="Shop/OrderRefund/save";

    const DELETE="Shop/OrderRefund/delete";

    protected  function _init(){
        $this->model=new OrderRefundModel();
    }
}
