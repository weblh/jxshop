<?php
namespace Admin\Controller;

/**
 * 商品控制器
 */
class GoodsController extends CommonController {
	
	public function add() {
		if (IS_GET) {
			$cate = D('Category')->getCateTree();
			$this->assign('cate',$cate);

			//获取所有类型信息
			$type = D('Type')->select();
			$this->assign('type',$type);

			$this->display();
			exit();
		}
		$model = D('Goods');
		$data = $model->create();
		if (!$data) {
			$this->error($model->getError());
		}
		//dump($data);exit;

		//促销商品时间格式化
		if ($data['cx_price']>0) {
			$data['start'] = strtotime($data['start']);
			$data['end'] = strtotime($data['end']);
		}else{
			$data['cx_price'] = 0.00;
			$data['start'] = 0;
			$data['end'] = 0;
		}

		$goods_id = $model->add($data);
		if (!$goods_id) {
			$this->error($model->getError());
		}
		$this->success('添加成功');
	}

	//当列表选择时显示出具体的选择框
	public function showAttr() {
		$type_id = intval(I('post.type_id'));
		if ($type_id<=0) {
			echo "没有数据";exit;
		}
		$data = D('Attribute')->where('type_id='.$type_id)->select();
		foreach ($data as $key => $value) {
			if ($value['attr_input_type']==2) {
				//时列表选择，需要处理默认值
				$data[$key]['attr_value'] = explode(',', $value['attr_value']);
			}
		}
		//dump($data);exit;
		$this->assign('data',$data);
		$this->display();

	}

	//商品列表显示
	public function index() {
		$cate = D('Category')->getCateTree();
		$this->assign('cate',$cate);

		$model = D('Goods');
		$data = $model->listData();
		$this->assign('data',$data);
		$this->display();
	}

	//商品的伪删除
	public function dels() {
		$goods_id = intval(I('get.goods_id'));
		if ($goods_id<=0) {
			$this->error('参数错误');
		}
		$model = D('Goods');
		$res = $model->dels($goods_id);
		if ($res===false) {
			$this->error('删除失败');
		}
		$this->success('删除成功');
	}

	//商品修改
	public function edit() {
		if (IS_GET) {
			$goods_id = intval(I('get.goods_id'));
			$model = D('Goods');
			$info = $model->findOneById($goods_id);
			if (!$info) {
				$this->error('参数错误');
			}
			$info['goods_body'] = htmlspecialchars_decode($info['goods_body']);
			$this->assign('info',$info);

			//获取所有分类信息
			$cate = D('Category')->getCateTree();
			$this->assign('cate',$cate);

			//获取扩展分类
			$ext_cate_ids = M('GoodsCate')->where("goods_id={$goods_id}")->select();
			if (!$ext_cate_ids) {
				$ext_cate_ids = array('msg'=>'no data');
			}
			$this->assign('ext_cate_ids',$ext_cate_ids);

			//获取所有商品类型
			$type = D('type')->select();
			$this->assign('type',$type);

			//获取该商品所有属性信息
			$goodsAttrModel = D('GoodsAttr');
			$attr = $goodsAttrModel->alias('a')->join("left join __ATTRIBUTE__ b on a.attr_id=b.id")->field('a.*,b.attr_name,b.type_id,b.attr_type,b.attr_input_type,b.attr_value')->where('goods_id='.$goods_id)->select();
			foreach ($attr as $key => $value) {
				if ($value['attr_input_type']==2) {
					$attr[$key]['attr_value'] = explode(',',$value['attr_value']);
				}
			}
			foreach ($attr as $key => $value) {
				$attr_list[$value['attr_id']][] = $value;
			}
			$this->assign('attr',$attr_list);
			//dump($attr);exit;
			
			//获取商品相册信息
			$goods_img_list = M('GoodsImg')->where('goods_id='.$goods_id)->select();
			$this->assign('goods_img_list',$goods_img_list);
			//dump($goods_img_list);exit;

			$this->display();
		}else{
			$model = D('Goods');
			$data = $model->create();
			if (!$data) {
				$this->error($model->getError());
			}
			//获取旧的文件信息
			$goods_id = intval(I('get.goods_id'));
			$oldGoods = $model->find($goods_id);

			$res = $model->update($data);
			if ($res===false) {
				$this->error($model->getError());
			}
			
			//修改成功后删除旧的图片文件
			unlink($oldGoods['goods_img']);
			unlink($oldGoods['goods_thumb']);

			$this->success('修改成功',U('index'));
		}
	}

	//商品相册图片删除
	public function delImg() {
		$img_id = intval(I('post.img_id'));
		$info = M('GoodsImg')->find($img_id);
		if (!$info) {
			$this->ajaxReturn(array('status'=>0,'msg'=>'参数错误'));
		}
		//删除图片
		unlink($info['goods_img']);
		unlink($info['goods_thumb']);
		M('GoodsImg')->delete($img_id);
		$this->ajaxReturn(array('status'=>1,'msg'=>'删除成功'));
	}

	//回收站商品列表显示
	public function trash() {
		$cate = D('Category')->getCateTree();
		$this->assign('cate',$cate);

		$model = D('Goods');
		$data = $model->listData(0);
		$this->assign('data',$data);
		$this->display();
	}

	//还原回收站商品
	public function recover() {
		$goods_id = intval(I('get.goods_id'));
		$model = D('Goods');
		$res = $model->setStatus($goods_id,1);
		if ($res===false) {
			$this->error('还原失败');
		}
		$this->success('还原成功');
	}

	//彻底删除回收站商品
	public function remove() {
		$goods_id = intval(I('get.goods_id'));
		if ($goods_id<=0) {
			$this->error('参数错误');
		}
		$model = D('Goods');
		$res = $model->remove($goods_id);
		if ($res===false) {
			$this->error('删除失败');
		}
		$this->success('删除成功');
	}

	//库存设置
	public function setNumber() {
		if (IS_GET) {
			$goods_id = intval(I('get.goods_id'));
			$goodsAttrModel = D('GoodsAttr');
			$attr = $goodsAttrModel->getSigleAttr($goods_id);
			if (!$attr) {
				//没有单选属性，只有唯一属性
				$info = D('Goods')->field('goods_number')->find($goods_id);
				$this->assign('info',$info);
				$this->display('nosigle');
				exit;
			}
			$info = M('GoodsNumber')->where('goods_id='.$goods_id)->select();
			//没设置库存的至少显示一次
			if (!$info) {
				$info = array('goods_number'=>0);
			}
			$this->assign('info',$info);
			$this->assign('attr',$attr);
			//dump($info);
			$this->display();
		}else{
			$attr = I('post.attr');
			$goods_number = I('post.goods_number');
			$goods_id = I('post.goods_id');
			//dump($attr);
			
			//没有单选属性的数据提交
			if (!$attr) {
				$goods_number = I('post.goods_number');
				D('Goods')->where('id='.$goods_id)->setField('goods_number',$goods_number);
				$this->success('更新库存成功');
				//exit;
			}

			//格式化数据，方便入库
			foreach ($goods_number as $key => $value) {
				//获取一组不同属性id
				$temp = array();
				foreach ($attr as $k => $v) {
					$temp[] = $v[$key];
				}

				//排序防止出现 3，4  4，3 情况
				sort($temp);
				$goods_attr_ids = implode(',', $temp);

				//实现组合去重,为重复则加入has,重复则删除
				if (in_array($goods_attr_ids, $has)) {
					//重复则去掉$goods_number中对应库存量，并跳出本层循环
					unset($goods_number[$key]);
					continue;
				}
				$has[] = $goods_attr_ids;

				$list[] = array(
					'goods_id'=>$goods_id,
					'goods_attr_ids'=>$goods_attr_ids,
					'goods_number'=>$value,
				);
			}
			//dump($list);exit;
			//先删除已有的库存信息
			M('GoodsNumber')->where('goods_id='.$goods_id)->delete();
			M('GoodsNumber')->addAll($list);
			//计算库存总数
			$goods_count = array_sum($goods_number);
			D('Goods')->where('id='.$goods_id)->setField('goods_number',$goods_count);
			$this->success('更新库存成功');
		}
	}
}