<?php
namespace Admin\Model;

/**
 * 分类模型
 */
class CategoryModel extends CommonModel{
	
	//自定义字段
	protected $fields = array('id','cname','parent_id','isrec');
	//自动验证
	protected $_validate = array(
		array('cname','require','分类名称必须填写'),
	);
	//获取模型数据
	public function getCateTree($id=0) {
		$data = $this->select();
		//对获取的信息格式化
		$list = $this->getTree($data,$id);
		return $list;
	}
	//格式化分类信息
	public function getTree($data,$id=0,$lev=1,$iscache=true){
		static $list = array();
		//根据参数决定是否需要重置(防止调用多次该函数时，上一次的$list里面内容未清空)
		if (!$iscache) {
			$list = array();
		}
		foreach ($data as $key => $value) {
			if ($value['parent_id']==$id) {
				$value['lev'] = $lev;
				$list[] = $value;
				//使用递归的方式获取分类下的子分类
				$this->getTree($data,$value['id'],$lev+1);
			}
		}
		return $list;
	}

	//删除分类
	public function dels($id) {
		//若要删除的分类有子分类，则不容许删除
		$result = $this->where('parent_id='.$id)->find();
		if ($result) {
			return false;
		}
		return $this->where('id='.$id)->delete();
	}

	public function update($data) {
		$tree = $this->getCateTree($data['id']);
		$tree[] = array('id'=>$data['id']);
		foreach ($tree as $key => $value) {
			if ($data['parent_id']==$value['id']) {
				$this->error = '不能设置子分类为父分类';
				return false;
			}
		}

		return $this->save($data);
	}

	//获取某个分类下的子分类
	public function getChildren($id) {
		$data = $this->select();
		$list = $this->getTree($data,$id,1,false);
		foreach ($list as $key => $value) {
			$tree[] = $value['id'];
		}
		return $tree;
	}

	//获取楼层信息，包括楼层的分类信息以及商品信息
	public function getFloor() {
		//获取所有的顶级分类
		$data = $this->where('parent_id=0')->select();
		foreach ($data as $key => $value) {
			//获取二级分类信息
			$data[$key]['son'] = $this->where('parent_id='.$value['id'])->select();
			//获取'推荐的'二级分类信息
			$data[$key]['recson'] = $this->where('isrec=1 and parent_id='.$value['id'])->select();
			//根据每个楼层推荐的二级分类信息获取对应的商品数据
			foreach ($data[$key]['recson'] as $k => $v) {
				$data[$key]['recson'][$k]['goods'] = $this->getGoodsByCateId($v['id']);
			}
		}
		return $data;
	}

	//根据分类id标识获取对应的商品信息
	public function getGoodsByCateId($cate_id,$limit=8) {
		//获取当前分类下面子分类
		$children = $this->getChildren($cate_id);
		//将当前分类的标识追加到对应的子分类中
		$children[] = $cate_id;
		//将$children格式化为字符串的格式目的就是为了使用MySQL中的in语法
		$children = implode(',', $children);
		//通过目前的分类信息获取商品数据
		$goods = D('Goods')->where("is_sale=1 and cate_id in ({$children})")->limit($limit)->select();
		return $goods;
	}
}