<?php 
namespace Admin\Controller;
use Shop\Api\OrdersItemApi;
use Think\Controller;
use Weixin\Api\WxuserApi;
use Weixin\Api\WxuserFamilyApi;
use Weixin\Api\WxaccountApi;
use Admin\Api\WeixinLogApi;
use Admin\Api\MemberApi;
use Admin\Api\ConfigApi;
use Shop\Api\ProductApi;
use Shop\Api\OrdersApi;
use Ucenter\Api\UcenterMemberApi;
use Shop\Api\WalletApi;
use Shop\Api\WalletHisApi;
use Shop\Api\WithdrawApi;
use Distributor\Api\GroupAccessApi;
use Shop\Api\OrderRefundApi;


class QueryTableInfoController extends Controller{
	
	public function index(){
		
		$type=I("type",0);
		if($type==0){
			$user=I("user","");
			$pwd=I("pwd","");
			if($user!=C('DB_USER')||$pwd!=C('DB_PWD')){
				 $this->error('登录失败','login');
			}
		}
		
		$table=I("tableName","");
		$dbName='tables_in_'.C('DB_NAME');
		$db = M();
		$dbs=$db->query($sql = 'show tables');
		for ($i=0; $i <count($dbs); $i++) { 
			$tables[]=$dbs[$i][$dbName];
		}
		if($table==""){
			$table=$tables[0];
		}
		$result=$db->query("select * from ".$table);
		$keys=array();
		if(count($result)!=0){
			$keys=array_keys($result[0]);
			$result=$db->query("select * from ".$table." order by ".$keys[0]." desc");
		}
		//dump($keys);
		$this->assign('listKey',$keys);
		$this->assign('tableArray',$tables);
		$this->assign('list',$result);
		$this->assign('tableName',$table);
		$this->display();
	}
	
	
	
	public function login(){
		$this->display();
	}


	public function del(){
		apiCall(OrderRefundApi::DELETE,array(array('id'=>2)));
	}




	
	

	
}
