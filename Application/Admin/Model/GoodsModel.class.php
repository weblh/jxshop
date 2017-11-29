<?php
namespace Admin\Model;

/**
 * 商品模型
 */
class GoodsModel extends CommonModel {
	
	protected $field = array('id','goods_name','goods_sn','cate_id','market_price','shop_price','goods_img','goods_thumb','goods_body','is_hot','is_rec','is_new','addtime','isdel','is_sale','type_id','goods_number','cx_price','start','end','plcount','sale_number');
	protected $_validate = array(
		array('goods_name','require','商品名称必须填写',1),
		array('cate_id','checkCategory','分类必须填写',1,'callback'),
		array('market_price','currency','市场价格格式不对',1),
		array('shop_price','currency','本店价格格式不对',1),
	);

	//对分类进行验证
	public function checkCategory($cate_id) {
		$cate_id = intval($cate_id);
		if ($cate_id>0) {
			return true;
		}
		return false;
	}

	public function _before_insert(&$data) {
		//dump($data);exit;
		//添加时间
		$data['addtime'] = time();

		//处理货号
		if (!$data['goods_sn']) {
			//没有填写货号则自动生成
			$data['goods_sn'] = 'JX'.uniqid();
		}else{
			$info = $this->where('goods_sn='.$data['goods_sn'])->find();
			if ($info) {
				$this->error = '货号重复';
				return false;
			}
		}

		//图片上传
		$res = $this->uploadImg();
		if ($res) {
			$data['goods_img'] = $res['goods_img'];
			$data['goods_thumb'] = $res['goods_thumb'];
		}
		
	}

	public function _after_insert($data) {
		$goods_id = $data['id'];
		$ext_cate_id = I('post.ext_cate_id');

		D('GoodsCate')->insertExtCate($ext_cate_id,$goods_id);

		//属性入库
		$attr = I('post.attr');
		D('GoodsAttr')->insertAttr($attr,$goods_id);

		//商品相册图片上传以及入库
		unset($_FILES['goods_img']);
		$upload = new \Think\Upload();
		$info = $upload->upload();
		$imgs = array();
		foreach ($info as $key => $value) {
			//图片路径
			$imgPath = 'Uploads/'.$value['savepath'].$value['savename'];
			//dump($imgPath);exit;
			//制作缩略图
			$img = new \Think\Image();
			$img->open($imgPath);
			$thumbPath = 'Uploads/'.$value['savepath'].'thumb_'.$value['savename'];
			$img->thumb(100,100)->save($thumbPath);
			$imgs[] = array(
				'goods_id'=>$goods_id,
				'goods_img'=>$imgPath,
				'goods_thumb'=>$thumbPath
			);
		}	
		if ($imgs) {
			M('GoodsImg')->addAll($imgs);
		}

	}

	//获取商品信息
	public function listData($isdel=1) {
		$pagesize = 3;
		$where = 'isdel='.$isdel;


		//继续拼接where子句
		//接受提交的分类ID
		$cate_id = intval(I('get.cate_id'));
		if ($cate_id) {
			//获取所有目标分类下的子分类id
			$cateModel = D('Category');
			$tree = $cateModel->getChildren($cate_id);
			//把目标分类也加入到数组中
			$tree[] = $cate_id;
			$children = implode(',', $tree);

			//目标分类及其子分类可能是某些商品的扩展分类，这些商品也要查出来
			$ext_goods_ids = M('GoodsCate')->group('goods_id')->where("cate_id in ({$children})")->select();
			//只取id
			if ($ext_goods_ids) {
				foreach ($ext_goods_ids as $key => $value) {
					$goods_ids[] = $value['goods_id'];
				}
				$goods_ids = implode(',', $goods_ids);
			}
			//有无扩展分类的两种商品的情况
			if (!$goods_ids) {
				//无
				$where .= " and cate_id in ({$children})";
			}else{
				//有
				$where .= " and (cate_id in ({$children}) or id in ({$goods_ids}))";
			}
		}

		//接受提交的推荐状态
		$intro_type = I('get.intro_type');
		if ($intro_type) {
			//限制只能用此三个推荐作为条件
			if ($intro_type=='is_new'||$intro_type=='is_rec'||$intro_type=='is_hot') {
				$where .= " and {$intro_type} = 1";
			}
		}

		//接受上下架
		$is_sale = intval(I('get.is_sale'));
		if ($is_sale==1) {
			//上架
			$where .= " and is_sale = 1";
		}elseif ($is_sale==2) {
			//下架
			$where .= " and is_sale = 0";
		}

		//接受关键词
		$keyword = I('get.keyword');
		if ($keyword) {
			$where .= " and goods_name like '%{$keyword}%'";
		}


		//数据分页
		$count = $this->where($where)->count();
		$page = new \Think\Page($count,$pagesize);
		$show = $page->show();
		$p = intval(I('get.p'));

		$data = $this->where($where)->page($p,$pagesize)->select();
		return array('pageStr'=>$show,'data'=>$data);
	}

	public function dels($goods_id) {
		return $this->where("id={$goods_id}")->setField('isdel',0);
	}

	public function update($data) {
		$goods_id = $data['id'];

		//促销商品时间格式化
		if ($data['cx_price']>0) {
			$data['start'] = strtotime($data['start']);
			$data['end'] = strtotime($data['end']);
		}else{
			$data['cx_price'] = 0.00;
			$data['start'] = 0;
			$data['end'] = 0;
		}

		//解决商品的货号问题
		$goods_sn = $data['goods_sn'];
		if (!$goods_sn) {
			//没有提交货号
			$data['goods_sn'] = 'JX'.uniqid();
		}else{
			//提交货号，检查是否重复（去掉自己）
			$res = $this->where("goods_sn = '{$goods_sn}' and id != {$goods_id}")->find();
			if ($res) {
				$this->error = '货号错误';
				return false;
			}
		}

		//解决扩展分类问题
		$extCateModel = D('GoodsCate');
		$extCateModel->where("goods_id={$goods_id}")->delete();
		$ext_cate_id = I('post.ext_cate_id');
		$extCateModel->insertExtCate($ext_cate_id,$goods_id);

		//解决图片问题
		//图片上传
		$res = $this->uploadImg();
		if ($res) {
			$data['goods_img'] = $res['goods_img'];
			$data['goods_thumb'] = $res['goods_thumb'];
		}

		//属性修改
		$goodsAttrModel = D('GoodsAttr');
		$goodsAttrModel->where('goods_id='.$goods_id)->delete();
		$attr = I('post.attr');
		$goodsAttrModel->insertAttr($attr,$goods_id);

		//商品相册图片上传以及入库
		unset($_FILES['goods_img']);
		$upload = new \Think\Upload();
		$info = $upload->upload();
		$imgs = array();
		foreach ($info as $key => $value) {
			//图片路径
			$imgPath = 'Uploads/'.$value['savepath'].$value['savename'];
			//dump($imgPath);exit;
			//制作缩略图
			$img = new \Think\Image();
			$img->open($imgPath);
			$thumbPath = 'Uploads/'.$value['savepath'].'thumb_'.$value['savename'];
			$img->thumb(100,100)->save($thumbPath);
			$imgs[] = array(
				'goods_id'=>$goods_id,
				'goods_img'=>$imgPath,
				'goods_thumb'=>$thumbPath
			);
		}	
		if ($imgs) {
			M('GoodsImg')->addAll($imgs);
		}

		return $this->save($data);
	}

	//图片上传
	public function uploadImg() {
		//判断是否有图片上传
		if (!isset($_FILES['goods_img'])||$_FILES['goods_img']['error']!=0) {
			return false;
		}
		//图片上传
		$upload = new \Think\Upload();
		$info = $upload->uploadOne($_FILES['goods_img']);
		if (!$info) {
			$this->error = $upload->getError();
		}

		$goods_img = 'Uploads/'.$info['savepath'].$info['savename'];

		// 根据上传的图片制作缩略图
		$img = new \Think\Image();
		$img->open($goods_img);
		$goods_thumb = 'Uploads/'.$info['savepath'].'thumb_'.$info['savename'];
		$img->thumb(450,450)->save($goods_thumb);
		$data['goods_img'] = $goods_img;
		$data['goods_thumb'] = $goods_thumb;
		return $data;
	}

	public function setStatus($goods_id,$isdel=0) {
		return $this->where("id={$goods_id}")->setField('isdel',$isdel);
	}

	//彻底删除回收站商品
	public function remove($goods_id) {
		$goods_info = $this->findOneById($goods_id);
		if (!$goods_info) {
			return false;
		}
		unlink($goods_info['goods_img']);
		unlink($goods_info['goods_thumb']);
		D('GoodsCate')->where("goods_id = {$goods_id}")->delete();
		$this->where("id = {$goods_id}")->delete();
		return true;
	}

	//根据参数获取热卖、推荐、新品商品信息
	public function getRecGoods($type) {
		return $this->where("is_sale=1 and {$type}=1")->order('id desc')->limit(5)->select();
	}

	//获取当前正在促销的商品
	public function getCrazyGoods() {
		$time = time();
		$where = "is_sale=1 and cx_price>0 and start<{$time} and end>{$time}";
		//dump($where);exit;
		return $this->where($where)->limit(5)->select();
	}

	//获取某一个分类下的商品信息
	public function getList() {
		//当前分类id
		$cate_id = I('get.id');
		$children = D('Admin/Category')->getChildren($cate_id);
		//将当前分类追加到children中
		$children[] = $cate_id;
		$children = implode(',', $children);
		//组装查询条件
		$where = "is_sale=1 and cate_id in ({$children})";


		//计算当前分类下对应的价格筛选条件
		//获取当前分类下所有商品对应的最大价格以及最小价格
		$goods_info = $this->field('max(shop_price) max_price,min(shop_price) min_price,count(id) goods_count,group_concat(id) goods_ids')->where($where)->find();//group_concat()是将所有数据拼接为一个字符串，并以逗号隔开
		//根据当前商品的个数判断是否需要显示出价格区间
		if ($goods_info['goods_count']>1) {
			$cha = $goods_info['max_price']-$goods_info['min_price'];
			//通过判断计算出具体显示的价格区间个数
			if ($cha<100) {
				$sec = 1;//具体显示的价格区间个数
			}elseif ($cha<500) {
				$sec = 2;
			}elseif ($cha<1000) {
				$sec = 3;
			}elseif ($cha<5000) {
				$sec = 4;
			}elseif ($cha<10000) {
				$sec = 5;
			}else{
				$sec = 6;
			}
			$price = array();//保存具体的每一个几个区间对应的值
			$first = ceil($goods_info['min_price']);//具体开始的价格
			$zl = ceil($cha/$sec);//每个价格区间增加的具体数量
			//循环运算每一个价格区间对应的开始价格跟结束价格
			for ($i=0; $i < $sec; $i++) { 
				//组装每个价格区间对应的开始跟结束值
				$price[] = $first.'-'.($first+$zl);
				$first += $zl;
			}
		}

		//接受价格条件进行查询
		if (I('get.price')) {
			//有具体的条件传递
			$tmp = explode('-', I('get.price'));
			$where .= ' and shop_price>'.$tmp[0].' and shop_price<'.$tmp[1];
		}

		//获取商品的属性信息,distinct是mysql中的去重操作
		if ($goods_info['goods_ids']) {
			$attr = M('GoodsAttr')->alias('a')->field('distinct a.attr_id,a.attr_values,b.attr_name')->join('left join __ATTRIBUTE__ b on a.attr_id=b.id')->where('a.goods_id in ('.$goods_info['goods_ids'].')')->select();
			//将目前已有的数据转换为三位数组格式
			foreach ($attr as $key => $value) {
				$attrwhere[$value['attr_id']][] = $value;
			}
		}
		//接受属性值条件获取商品信息
		if (I('get.attr')) {
			//需要使用属性值进行商品筛选
			//将目前所接受的属性值的条件转换为数组格式。转换的目的是为了使用tp的条件进行查询
			$attrParms = explode(',', I('get.attr'));
			//获取属性对应的商品id
			$goods = M('GoodsAttr')->field('group_concat(goods_id) as goods_ids')->where(array('attr_values'=>array('in',$attrParms)))->find();
			if($goods['goods_ids']){
				$where .= " and id in ({$goods['goods_ids']})";
			}
		}


		//分页
		$p = I('get.p');
		$pagesize = 8;
		$count = $this->where($where)->count();
		$page = new \Think\Page($count,$pagesize);
		$show = $page->show();

		//接收排序字段
		$sort = I('get.sort') ? I('get.sort'):'sale_number';
		//根据页码获取数据
		$list = $this->where($where)->page($p,$pagesize)->order("{$sort} desc")->select();


		//price 返回具体的价格筛选条件
		return array('list'=>$list,'page'=>$show,'price'=>$price,'attrwhere'=>$attrwhere);
	}

}