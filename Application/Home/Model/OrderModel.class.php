<?php
namespace Home\Model;
use Think\Model;
/**
 * 订单模型
 */
class OrderModel extends Model {
	
	//自定义字段
	protected $fields = array('id','user_id','total_price','pay_status','name','address','tel','addtime');

	public function order() {
		//获取购物车中商品信息
		$cartModel = D('Cart');
		$data = $cartModel->getList();
		if (!$data) {
			$this->error = '购物车中没有商品';
			return false;
		}
		//根据每一个商品做一个库存检查
		foreach ($data as $key => $value) {
			//具体检查每一个商品库存
			$status = $cartModel->checkGoodsNumber($value['goods_id'],$value['goods_count'],$value['goods_attr_ids']);
			if (!$status) {
				$this->error = '库存量不够';
				return false;
			}
		}
		//向订单总表中写入数据
		$total = $cartModel->getTotal($data);
		$order = array(
			'user_id'=>session('user_id'),
			'total_price'=>$total['price'],
			'addtime'=>time(),
			'name'=>I('post.name'),
			'address'=>I('post.address'),
			'tel'=>I('post.tel')
		);
		$order_id = $this->add($order);

		//向商品订单详情表中写入具体信息
		foreach ($data as $key => $value) {
			$goods_order[] = array(
				'order_id'=>$order_id,
				'goods_id'=>$value['goods_id'],
				'goods_attr_ids'=>$value['goods_attr_ids'],
				'price'=>$value['goods']['shop_price'],
				'goods_count'=>$value['goods_count']
			);
		}
		M('OrderGoods')->addAll($goods_order);
		//减少商品对应库存量
		foreach ($data as $key => $value) {
			//减少商品表中总库存
			M('Goods')->where('id='.$value['goods_id'])->setDec('goods_number',$value['goods_count']);

			//增加对应销量
			M('Goods')->where('id='.$value['goods_id'])->setInc('sale_number',$value['goods_count']);

			//根据商品的单选属性组合减少对应库存
			if ($value['goods_attr_ids']) {
				$where = 'goods_id='.$value['goods_id'].' and goods_attr_ids='."'".$value['goods_attr_ids']."'";
				M('GoodsNumber')->where($where)->setDec('goods_number',$value['goods_count']);
			}
		}
		//清空购物车中的数据
		$user_id = session('user_id');
		$cartModel->where('user_id='.$user_id)->delete();
		$order['id'] = $order_id;
		return $order;
	}
}