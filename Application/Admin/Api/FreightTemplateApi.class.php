<?php
namespace Admin\Api;

use Admin\Model\FreightTemplateModel;

/**
 * Created by PhpStorm.
 * User: an
 * Date: 2015/8/13
 * Time: 15:59
 */
use \Common\Api\Api;
use Admin\Api\FreightAddressApi;

class FreightTemplateApi extends Api{



    public function _init() {
        $this->model = new FreightTemplateModel();
    }

    const QUERY_NO_PAGING="Admin/FreightTemplate/queryNoPaging";

    const QUERY="Admin/FreightTemplate/query";

    const ADD="Admin/FreightTemplate/add";

    const QUERY_WITH_ADDRESS="Admin/FreightTemplate/queryWithAddress";

    const ADD_WITH_ADDRESS="Admin/FreightTemplate/addWithAddress";

    const DELETE_WITH_ADDRESS="Admin/FreightTemplate/deleteWithAddress";

    const EDIT_WITH_ADDRESS="Admin/FreightTemplate/editWithAddress";


    const QUERY_WITH_COMPANY="Admin/FreightTemplate/queryWithCompany";
    /**
     * 查询
     */
    public function queryWithAddress($map){
      //  dump($this->model);
        return $this->model->where($map)->relation(true)->Select();
    }

    /**
     *
     * @param $map
     * @return mixed
     */
    public function queryWithCompany($map = null, $page = array('curpage'=>0,'size'=>10), $order = false, $params = false, $fields = false){
        //__WORK__ ON __ARTIST__.id = __WORK__.artist_id
        $map1=array(
          'a.name'=>$map['name']
        );
        $list=$this->model->alias('a')->join('left join common_datatree b on a.company=b.id')->where($map1)->field("a.id,a.name,b.name as company")->select();

        if ($list === false) {
            $error = $this -> model -> getDbError();
            return $this -> apiReturnErr($error);
        }

        $count = $this -> model -> where($map) -> count();
        // 查询满足要求的总记录数
        $Page = new \Think\Page($count, $page['size']);

        //分页跳转的时候保证查询条件
        if ($params !== false) {
            foreach ($params as $key => $val) {
                $Page -> parameter[$key] = urlencode($val);
            }
        }

        // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page -> show();

        return $this -> apiReturnSuc(array("show" => $show, "list" => $list));
    }

    /**
     * 添加运费模板，
     * 事务添加
     */
    public function addWithAddress($entity){

        //dump($entity);
        $trans = M();
        $trans->startTrans();
        //$error = "";

        $entity1=array(
            'name'=>$entity['name'],
            'type'=>$entity['type'],
            'company'=>$entity['company']
        );
        $result=$this->add($entity1);


        if($result['status']){
            $tid=$result['info'];
            //dump($tid);
            foreach($entity['cityInfos'] as $city){
                $city['template_id']=$tid;
                $result1=apiCall(FreightAddressApi::ADD,array($city));
                if(!$result1['status']){
                    //dump($result1);
                    $trans->rollback();
                    return $this->apiReturnErr("模板添加失败");
                }
            }
            $trans->commit();
            return $this->apiReturnSuc("模板添加成功");

        }else{

            $trans->rollback();
            return $this->apiReturnErr("模板添加失败");
        }
    }


    /**
     * 修改运费模板，
     * 事务添加
     */
    public function editWithAddress($entity){

        //dump($entity);
        $trans = M();
        $trans->startTrans();
        //$error = "";

        $entity1=array(
            'name'=>$entity['name'],
            'type'=>$entity['type'],
            'company'=>$entity['company']
        );
        $result=$this->saveByID($entity['id'],$entity1);
        $map=array(
            'template_id'=>$entity['id'],
        );
        $r=apiCall(FreightAddressApi::DELETE,array($map));
        if(!$r['status']){
            $trans->rollback();
            return $this->apiReturnErr("模板添加失败");
        }
        if($result['status']){
            $tid=$result['info'];
            //dump($tid);
            foreach($entity['cityInfos'] as $city){
                $city['template_id']=$entity['id'];
                $result1=apiCall(FreightAddressApi::ADD,array($city));
                if(!$result1['status']){
                    //dump($result1);
                    $trans->rollback();
                    return $this->apiReturnErr("模板添加失败");
                }
            }
            $trans->commit();
            return $this->apiReturnSuc("模板添加成功");

        }else{

            $trans->rollback();
            return $this->apiReturnErr("模板添加失败");
        }
    }

    /**
     * 删除运费模板
     */
    public function deleteWithAddress($map){
        $trans = M();
        $trans->startTrans();
        $result=$this->delete($map);
        $flag=true;
        if($result['status']){
            $map1=array(
              'template_id'=>$map['id']
            );
            $result1=apiCall(FreightAddressApi::DELETE,array($map1));
            if($result1['status']){
                $trans->commit();
            }else{
                $flag=false;
                $trans->rollback();
            }
        }else{
            $flag=false;
            $trans->rollback();
        }
        return $flag;
    }

}