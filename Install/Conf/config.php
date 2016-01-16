<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 安装程序配置文件
 */

define('INSTALL_APP_PATH', realpath('./') . '/');

return array(

    //自动载入,不然没法加载插件类
    'AUTOLOAD_NAMESPACE'=>array('Addons' => ADDON_PATH),
    //开启数据库session
    'SESSION_TYPE'=>'',
    'SESSION_AUTO_START'=>true,
	'URL_HTML_SUFFIX'=>'shtml'	,
    'ORIGINAL_TABLE_PREFIX' => 'boye_', //默认表前缀

    /* 模板相关配置 */
   'TMPL_PARSE_STRING'  =>array(
//       '__CDN__' => __ROOT__.'/Public/cdn', // 更改默认的/Public 替换规则
       '__CDN__' => 'http://192.168.0.100/github/itboye_cdn/cdn', // 更改默认的/Public 替换规则
		'__JS__'     => __ROOT__.'/Public/'.MODULE_NAME.'/js', // 增加新的JS类库路径替换规则
     	'__CSS__'     => __ROOT__.'/Public/'.MODULE_NAME.'/css', // 增加新的JS类库路径替换规则
     	'__IMG__'     => __ROOT__.'/Public/'.MODULE_NAME.'/imgs', // 增加新的JS类库路径替换规则	
     
	),	

);