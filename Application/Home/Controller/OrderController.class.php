<?php
namespace Home\Controller;

/**
 * 购物车控制器
 * 目前加入商品到购物车还有问题
 * 库存没有更新，买东西库存量未减少
 */
class OrderController extends CommonController {

	//显示结算页面
	public function check() {
		//判断用户是否登陆
		$this->checkLogin();
		$model = D('Cart');
		//获取购物车中具体商品信息
		$data = $model->getList();
		$this->assign('data',$data);
		//计算当前购物车总金额
		$total = $model->getTotal($data);
		$this->assign('total',$total);
		$this->display();
	}

	//实现用户的下单操作
	public function order() {
		$model = D('Order');
		$res = $model->order();
		if (!$res) {
			$this->error($model->getError());
		}
		echo "ok";
	}

	//实现用户继续支付
	public function pay() {
		$order_id = intval(I('get.order_id'));
		$model = D('Order');
		$res = $model->where('id='.$order_id)->find();
		if(!$res){
			$this->error('参数错误');
		}
		if ($res['pay_status']==1) {
			$this->error('已经支付了该账单');
		}
	}
}