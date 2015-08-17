<?php
// .-----------------------------------------------------------------------------------
// | WE TRY THE BEST WAY 杭州博也网络科技有限公司
// |-----------------------------------------------------------------------------------
// | Author: 贝贝 <hebiduhebi@163.com>
// | Copyright (c) 2013-2016, http://www.itboye.com. All Rights Reserved.
// |-----------------------------------------------------------------------------------
namespace Admin\Controller;

use Shop\Api\CategoryApi;

class CategoryController extends AdminController{

	public function index(){
		$parent = I('parent',0);
		$preparent = I('preparent',-1);
		$level =  I('level',0);
		$map = array(
			'parent'=>$parent
		);
		$name = I('name','');
		$params = array(
			'parent'=>$parent
		);

		if(!empty($name)){
			$map['name'] = array('like',"%$name%");
			$params['name'] = $name;
		}

		$result = apiCall(CategoryApi::GET_INFO, array(array('id'=>$parent)));
		if(!$result['status']){
			$this->error($result['info']);
		}
		$parent_vo = $result['info'];

		$result = apiCall(CategoryApi::GET_INFO, array(array('id'=>$preparent)));
		$prepreparent = "";
		if($result['status']){
			$prepreparent = $result['info']['parent'];
		}


		$page = array('curpage' => I('get.p', 0), 'size' => C('LIST_ROWS'));

		$order = " id asc ";
		//
		$result = apiCall(CategoryApi::QUERY,array($map,$page,$order,$params));

		//
		if($result['status']){
			$this->assign('level',$level);
			$this->assign('parent_vo',$parent_vo);
			$this->assign('prepreparent',$prepreparent);
			$this->assign('preparent',$preparent);
			$this->assign('parent',$parent);
			$this->assign('name',$name);
			$this->assign('show',$result['info']['show']);
			$this->assign('list',$result['info']['list']);
			$this->display();
		}else{
			LogRecord('INFO:'.$result['info'],'[FILE] '.__FILE__.' [LINE] '.__LINE__);
			$this->error(L('UNKNOWN_ERR'));
		}
	}


	/**
	 * 一级类目添加
	 */
	public function add(){
		if(IS_GET){
			$parent = I('parent',0);
			$preparent = I('preparent',-1);
			$level = I('level',0);

			$this->assign("parent",$parent);
			$this->assign("preparent",$preparent);
			$this->assign("level",$level);
			$this->display();
		}else{
			$parent = I('post.parent',0);
			$level = I('post.level',0);
			$entity = array(
				'name'=>I('post.name'),
				'parent'=>$parent,
				'cate_id'=>'custom',
				'level'=>$level,
			);

			$result = apiCall(CategoryApi::ADD, array($entity));
			if($result['status']){
				$this->success("添加成功！",U('Admin/Category/index'));
			}else{
				$this->error($result['info']);
			}
		}
	}

	/**
	 * 编辑
	 */
	public function edit(){
		if(IS_GET){

			$parent = I('parent',-1);
			$preparent = I('preparent',0);

			$id = I('get.id',0);
			$map = array('id'=>$id);
			$result = apiCall(CategoryApi::GET_INFO, array($map));
			if($result['status']){
				$this->assign("parent",$parent);
				$this->assign("preparent",$preparent);
				$this->assign("cate",$result['info']);
				$this->display();
			}else{
				$this->error($result['info']);
			}

		}else{
			$id = I('post.id',0);
			$entity = array(
				'name'=>I('post.name'),
			);

			$result = apiCall(CategoryApi::SAVE_BY_ID, array($id,$entity));
			if($result['status']){
				$this->success("编辑成功！");
			}else{
				$this->error($result['info']);
			}
		}
	}

	public function delete(){
		$id = I('get.id',0);
		$map = array('parent'=>$id);
		$result = apiCall(CategoryApi::QUERY,array($map));
		if($result['status']){
			if(count($result['info']['list']) > 0){
				$this->error("存在子类目，无法删除此类目！");
			}

			$result = apiCall(CategoryApi::DELETE,array(array('id'=>$id)));
			if($result['status']){
				$this->success("删除成功！");
			}else{
				$this->error($result['info']);
			}

		}else{

		}
	}


}

