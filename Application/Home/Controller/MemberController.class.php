<?php
namespace Home\Controller;

/**
 * 购物车控制器
 * 目前加入商品到购物车还有问题
 * 库存没有更新，买东西库存量未减少
 */
class MemberController extends CommonController {
	public function __construct() {
		parent::__construct();
		//登陆验证
		$this->checkLogin();
	}

	//显示我的订单
	public function order() {
		$user_id = session('user_id');
		$data = D('Order')->where('user_id='.$user_id)->select();
		$this->assign('data',$data);
		$this->display();
	}

	//查看具体的订单快递信息
	public function express() {
		$order_id = I('get.order_id');
		$info = M('Order')->where('id='.$order_id)->find();
		if (!$info||$info['order_status']!=2) {
			$this->error('参数错误');
		}
		//根据快递公司代号以及运单号查询快递信息
		//组装请求的URL地址
		$url = 'http://v.juhe.cn/exp/index?key=965e5c1130ba3bd448d3bf6b011b3c97&com='.$info['com'].'&no='.$info['no'];
		$res = file_get_contents($url);
		$res = json_decode($res,true);
		//dump($res);exit;
		$this->assign('data',$res);
		$this->display();
	}

}