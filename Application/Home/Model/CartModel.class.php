<?php
namespace Home\Model;
use Think\Model;
/**
 * 购物车模型
 */
class CartModel extends Model {
	
	//自定义字段
	protected $fields = array('id','user_id','goods_id','goods_count','goods_attr_ids');

	//具体商品信息加入购物车
	public function addCart($goods_id,$goods_count,$attr) {
		//将属性信息排序（考虑到后期库存量的的检查）
		sort($attr);
		//将属性信息转换未字符串
		$goods_attr_ids = $attr ? implode(',', $attr) : '';
		//实现库存量检查
		$res = $this->checkGoodsNumber($goods_id,$goods_count,$goods_attr_ids);
		if (!$res) {
			$this->error = '库存不足';
			return false;
		}
		//获取用户id标识
		$user_id = session('user_id');
		if ($user_id) {
			//说明用户已经登陆，继续操作对应数据表
			//根据要写入的数据判断数据库中是否存在，如果存在直接跟新对应数量，否则直接写入即可
			$map = array(
				'user_id'=>$user_id,
				'goods_id'=>$goods_id,
				'goods_attr_ids'=>$goods_attr_ids
			);
			$info = $this->where($map)->find();
			if ($info) {
				//数据已经存在
				$this->where($map)->setField('goods_count',$goods_count+$info['goods_count']);
			}else{
				//数据不存在，直接写入
				$map['goods_count'] = $goods_count;
				$this->add($map);
			}
		}else{
			//用户未登陆，操作cookie数据
			//反序列化
			$cart = unserialize(cookie('cart'));
			$key = $goods_id.'-'.$goods_attr_ids;
			if (array_key_exists($key, $cart)) {
				//说明目前添加的商品已经存在
				$cart[$key]+=$goods_count;
			}else{
				//说明目前添加的商品信息不存在
				$cart[$key] = $goods_count;
			}
			//处理完之后需要将最新的数据再次写入cookie
			cookie('cart',serialize($cart));
		}
		return true;
	}

	//库存检查
	public function checkGoodsNumber($goods_id,$goods_count,$goods_attr_ids) {
		//检查总的库存量
		$goods = D('Admin/Goods')->where("id={$goods_id}")->find();
		if ($goods['goods_number']<$goods_count) {
			//库存不足
			return false;
		}
		//根据单选属性检查对应的属性组合库存量
		if ($goods_attr_ids) {
			$where = "goods_id=$goods_id and goods_attr_ids='{$goods_attr_ids}'";
			$number = M('GoodsNumber')->where($where)->find();
			if (!$number || $number['goods_number']<$goods_count) {
				//库存不足
				return false;
			}
		}
		return true;
	}

	//购物车cookie中的数据转移到数据库中
	public function cookie2db() {
		//获取cookie中购物车的数据
		$cart = unserialize(cookie('cart'));
		//获取当前用户id标识
		$user_id = session('user_id');
		if (!$user_id) {
			return false;
		}
		foreach ($cart as $key => $value) {
			//将下标对应的商品id和属性值组合拆分出来
			$tmp = explode('-', $key);
			//拼接条件查询当前商品信息是否存在
			$map = array(
				'user_id'=>$user_id,
				'goods_id'=>$tmp[0],
				'goods_attr_ids'=>$tmp[1]
			);
			$info = $this->where($map)->find();
			if ($info) {
				//商品信息存在，直接跟新对应数量
				$this->where($map)->setField('goods_count',$value+$info['goods_count']);
			}else{
				//说明目前商品信息不存在，直接将数据写入即可
				$map['goods_count'] = $value;
				$this->add($map);
			}
		}
		//将目前cookie中的数据清空
		cookie('cart',null);
	}

	//获取购物车信息
	public function getList() {
		//获取当前购物车中对应的信息
		$user_id = session('user_id');
		if ($user_id) {
			//表示拥护已经登陆
			$data = $this->where('user_id='.$user_id)->select();
		}else{
			//用户未登录，直接从cookie中获取数据
			$cart = unserialize(cookie('cart'));
			//将没有登陆的购物车数据转换为数据库中格式
			foreach ($cart as $key => $value) {
				$tmp = explode('-', $key);
				$data[] = array(
					'goods_id'=>$tmp[0],
					'goods_attr_ids'=>$tmp[1],
					'goods_count'=>$value
				);
			}
		}
		//根据购物车中商品id获取具体的商品信息
		$goodsModel = D('Admin/Goods');
		foreach ($data as $key => $value) {
			//获取具体的商品信息
			$goods = $goodsModel->where('id='.$value['goods_id'])->find();
			//根据商品是否处于促销状态设置价格
			if ($goods['cx_price']>0&&$goods['start']<time()&&$goods['end']>time()) {
				//处于促销状态，将shop_price设置为促销价格
				$goods['shop_price'] = $goods['cx_price'];
			}
			$data[$key]['goods'] = $goods;
			//根据商品对应的属性值组合获取对应的属性名称跟库存
			if ($value['goods_attr_ids']) {
				$attr = M('GoodsAttr')->alias('a')->join('left join __ATTRIBUTE__ b on a.attr_id=b.id')->field('a.attr_values,b.attr_name')->where("a.id in ({$value['goods_attr_ids']})")->select();
				$data[$key]['attr'] = $attr;
			}
		}
		return $data;
	}

	//购物车中总金额计算
	public function getTotal($data) {
		//初始商品个数及总金额都为0
		$count = $price = 0;
		foreach ($data as $key => $value) {
			$count += $value['goods_count'];
			$price += $value['goods_count']*$value['goods']['shop_price'];
		}
		return array('count'=>$count,'price'=>$price);
	}

	//购物车中删除商品功能
	public function dels($goods_id,$goods_attr_ids) {
		$goods_attr_ids = $goods_attr_ids ? $goods_attr_ids : '';
		$user_id = session('user_id');
		if ($user_id) {
			$where = "user_id={$user_id} and goods_id={$goods_id} and goods_attr_ids='{$goods_attr_ids}'";
			$this->where($where)->delete();
		}else{
			$cart = unserialize(cookie('cart'));
			//手动拼接当前商品对应的key信息
			$key = $goods_id.'-'.$goods_attr_ids;
			unset($cart[$key]);
			cookie('cart',serialize($cart));
		}
	}

	//更新购物车商品数量
	public function updateCount($goods_id,$goods_attr_ids,$goods_count) {
		//增加判断当目前$goods_count值小于等于0时不进行更新
		if ($goods_count<=0) {
			return false;
		}
		$goods_attr_ids = $goods_attr_ids ? $goods_attr_ids : '';
		$user_id = session('user_id');
		if ($user_id) {
			$where = "user_id={$user_id} and goods_id={$goods_id} and goods_attr_ids='{$goods_attr_ids}'";
			$res = $this->where($where)->setField('goods_count',$goods_count);
		}else{
			$cart = unserialize(cookie('cart'));
			//手动拼接cookie中key组合
			$key = $goods_id.'-'.$goods_attr_ids;
			$cart[$key] = $goods_count;
			//处理完之后需要将最新的数据再次写入cookie
			cookie('cart',serialize($cart));
		}
		return true;
	}

}