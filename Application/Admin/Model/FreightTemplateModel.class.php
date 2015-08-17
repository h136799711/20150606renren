<?php
namespace Admin\Model;

use Think\Model\RelationModel;
/**
 * Created by PhpStorm.
 * User: an
 * Date: 2015/8/13
 * Time: 15:20
 */

 class FreightTemplateModel extends RelationModel{
     protected $_link = array(
         'FreightAddress' => array(
             'mapping_type'  => self::HAS_MANY,
             'class_name'    => 'FreightAddress',
             'foreign_key'   => 'template_id',
             'mapping_name'  => 'freightAddress',
            // 'mapping_order' => 'id desc',
         )
        );
 }