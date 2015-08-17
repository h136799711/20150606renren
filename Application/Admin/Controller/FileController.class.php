<?php
// .-----------------------------------------------------------------------------------
// | WE TRY THE BEST WAY
// |-----------------------------------------------------------------------------------
// | Author: 贝贝 <hebiduhebi@163.com>
// | Copyright (c) 2013-2016 杭州博也网络科技, http://www.itboye.com. All Rights Reserved.
// |-----------------------------------------------------------------------------------

namespace Admin\Controller;

use Admin\Api\UserPictureApi;
use Admin\Api\WxshopPictureApi;

class FileController extends AdminController{
	
	protected function _initialize(){

        //TODO: 导致session，修改不启作用，沿用上次，导致一级菜单未能存入session，使得当前激活菜单不正确
        //FIXME:考虑，将图片上传放到另外一个类中
        //解决uploadify上传session问题

        session('[pause]');
        $session_id = I('get.session_id','');
        if (!empty($session_id)) {
            session_id($session_id);
            session('[start]');
        }

		parent::_initialize();
	}
	
	public function test(){
		$this->display();
	}
	
	public function uploadPicture(){
		if(IS_POST){
			
	        /* 返回标准数据 */
	        $return  = array('status' => 1, 'info' => '上传成功', 'data' => '');
	
	        /* 调用文件上传组件上传文件 */
	        $Picture = D('Picture');
	        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
	        $info = $Picture->upload(
	            $_FILES,
	            C('PICTURE_UPLOAD'),
	            C('PICTURE_UPLOAD_DRIVER'),
	            C("UPLOAD_{$pic_driver}_CONFIG")
	        ); //TODO:上传到远程服务器
	
	        /* 记录图片信息 */
	        if($info){
	            $return['status'] = 1;
	            $return = array_merge($info['download'], $return);
	        } else {
	            $return['status'] = 0;
	            $return['info']   = $Picture->getError();
	        }
	
	        /* 返回JSON数据 */
	        $this->ajaxReturn($return);
		}
		
	}


    public function picturelist(){
        if(IS_AJAX){
            $q = I('post.q','');
            $time = I('post.time','');
            $cur = I('post.p',0);
            $size = I('post.size',10);
            $map = array('uid'=>UID);
            $page = array('curpage'=>$cur,'size'=>$size);
            $order = 'create_time desc';
            $params = array(
                'p'=>$cur,
                'size'=>$size,
            );

            if(!empty($q)){
                $params['q'] = $q;
                $map['ori_name'] = array("like",'%'.$q.'%');
            }

            if(!empty($time)){
                $time = strtotime($time);
//                dump($time);
                $map['create_time'] = array(array('lt',$time+24*3600),array('gt',$time-1),'and');
            }

            $fields = 'id,create_time,status,path,url,md5,imgurl,ori_name,savename,size';
            $result = apiCall(UserPictureApi::QUERY,array($map,$page,$order,$params,$fields));

            if($result['status']){
                $this->success($result['info']);
            }else{
                $this->error($result['info']);
            }
        }
    }


    /**
     * 上传图片接口
     */
    public function uploadWxshopPicture(){
        if(IS_POST){

            if(!isset($_FILES['wxshop'])){
                $this->error("文件对象必须为wxshop");
            }

            $result['info'] = "";
            //2.再上传到自己的服务器，
            //TODO:也可以上传到QINIU上
            /* 返回标准数据 */
            $return  = array('status' => 1, 'info' => '上传成功', 'data' => '');

            /* 调用文件上传组件上传文件 */
            $Picture = D('UserPicture');
            $extInfo = array('uid' => UID,'imgurl' => $result['info']);
            $info = $Picture->upload(
                $_FILES,
                C('WXSHOP_PICTURE_UPLOAD')
                ,$extInfo
            );

            /* 记录图片信息 */
            if($info){
                $return['status'] = 1;
                $return = array_merge($info['wxshop'], $return);
            } else {
                $return['status'] = 0;
                $return['info']   = $Picture->getError();
            }

            /* 返回JSON数据 */
            $this->ajaxReturn($return);
        }

    }



    /**
     * 图片删除
     */
    public function del(){
        $imgIds=I("imgIds",-1);


        if($imgIds!=-1){
            $map=array(
                'id'=>array(
                    'in',$imgIds
                )
            );
            $result=apiCall(UserPictureApi::QUERY_NO_PAGING,array($map));
            $result=apiCall(UserPictureApi::DELETE,array($map));
            /*if($result['status']){
                $this->success('删除成功');
            }*/
            foreach($result['info'] as $v){
                $result =unlink('.'.$v['path']);
                if ($result) {
                   // echo '蚊子赶走了';
                    $this->success('删除成功'.'.'.$v['path']);
                } else {
                    $this->success('删除失败');
                   // echo '无法赶走';
                }
               // unlink();
            }

        }
    }
	
}
