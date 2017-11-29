<?php
namespace Admin\Controller;

/**
 * 购物车控制器
 * 目前加入商品到购物车还有问题
 * 库存没有更新，买东西库存量未减少
 */
class OrderController extends CommonController {
	//显示具体的订单列表
	public function index() {
		//获取订单信息
		$data = M('Order')->select();
		$this->assign('data',$data);
		$this->display();
	}

	//具体实现订单的发货功能
	public function send(){
		if (IS_GET) {
			$order_id = I('get.order_id');
			//根据订单号获取具体的订单数据
			$info = M('Order')->alias('a')->field('a.*,b.username')->join('left join __USER__ b on a.user_id=b.id')->where('a.id='.$order_id)->find();
			$this->assign('info',$info);
			$this->display();
		}else{
			//进行发货操作
			$order_id = I('post.id');
			$info = M('Order')->where('id='.$order_id)->find();
			if (!$info||$info['pay_status']!=1) {
				$this->error('参数错误');
			}
			$data = array(
				'com'=>I('post.com'),
				'no'=>I('post.no'),
				'order_status'=>2//设置订单状态为已经发货
			);
			M('Order')->where('id='.$order_id)->save($data);
			$this->success('发货成功');
		}
	}

}