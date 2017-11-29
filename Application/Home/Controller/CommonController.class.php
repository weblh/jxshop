<?php
namespace Home\Controller;
use Think\Controller;
class CommonController extends Controller {
    public function __construct() {
    	parent::__construct();

    	//获取分类信息
    	$cate = D('Admin/Category')->getCateTree();
    	$this->assign('cate',$cate);
    }

    //检查用户是否登陆，若未登陆直接跳转到登陆页面
    public function checkLogin() {
    	$user_id = session('user_id');
    	if (!$user_id) {
    		//没有登陆
    		$this->error('请先登陆',U('User/login'));
    	}
    }
}