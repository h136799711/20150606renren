<?php
namespace Admin\Api;
use Admin\Model\FreightAddressModel;
use Common\Api\Api;


/**
 * Created by PhpStorm.
 * User: an
 * Date: 2015/8/13
 * Time: 16:50
 */

class FreightAddressApi extends Api{

    const ADD="Admin/FreightAddress/add";

    const DELETE="Admin/FreightAddress/delete";

    const QUERY_NO_PAGING="Admin/FreightAddress/queryNoPaging";

    protected function _init(){
        $this->model=new FreightAddressModel();
    }
}