<?php
/**
 * Created by PhpStorm.
 * User: hebidu
 * Date: 15/7/2
 * Time: 14:13
 */

namespace Shop\Model;

/**
 * 钱包历史变动记录
 * Class WalletHisModel
 * @package Shop\Model
 */
class WalletHisModel {

    protected $_auto = array(
        array('create_time','time',self::MODEL_INSERT,"function")
    );

}