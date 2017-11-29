<?php
namespace Home\Controller;

/**
 * 购物车控制器
 * 目前加入商品到购物车还有问题
 * 库存没有更新，买东西库存量未减少
 */
class CartController extends CommonController {
	
	//实现商品添加到购物车
	public function addCart() {
		$goods_id = intval(I('post.goods_id'));
		$goods_count = intval(I('post.goods_count'));
		$attr = I('post.attr');
		//实例化模型对象调用方法实现数据写入
		$model = D('Cart');
		$res = $model->addCart($goods_id,$goods_count,$attr);
		if (!$res) {
			$this->error($model->getError());
		}
		$this->success('写入成功');
	}

	//购物车列表显示信息
	public function index() {
		$model = D('Cart');
		//获取购物车中具体的商品信息
		$data = $model->getList();
		$this->assign('data',$data);
		//实现购物车列表显示功能
		$total = $model->getTotal($data);
		$this->assign('total',$total);
		$this->display();
	}

	//删除购物车商品
	public function dels() {
		$goods_id = intval(I('get.goods_id'));
		$goods_attr_ids = intval(I('get.goods_attr_ids'));
		D('Cart')->dels($goods_id,$goods_attr_ids);
		$this->success('删除成功');
	}

	//更新购物车中商品数量
	public function updateCount() {
		$goods_id = intval(I('post.goods_id'));
		$goods_count = intval(I('post.goods_count'));
		$goods_attr_ids = intval(I('post.goods_attr_ids'));
		D('Cart')->updateCount($goods_id,$goods_attr_ids,$goods_count);
	}
}