<?php
namespace Admin\Model;

/**
 * 类型的属性模型
 */
class AttributeModel extends CommonModel{
	//定义字段
	protected $fields = array('id','attr_name','type_id','attr_type','attr_input_type','attr_value');
	//自动验证
	protected $_validate = array(
		array('attr_name','require','属性名必须填写！'),
		array('type_id','require','类型id必须填写！'),
		array('attr_type','1,2','属性类型只能为单选或唯一！',1,'in'),
		array('attr_input_type','1,2','属性录入方法只能为手工或列表！',1,'in'),
	);

	//获取数据
	public function listData() {
		$pagesize = 3;
		$count = $this->count();
		$page = new \Think\Page($count,$pagesize);
		$show = $page->show();
		//当前页码
		$p = intval(I('get.p'));
		$list = $this->page($p,$pagesize)->select();

		$type = D('Type')->select();
		//将类型信息转换为使用主键ID作为索引的数组
		foreach ($type as $key => $value) {
			$typeinfo[$value['id']] = $value;
		}
		//循环数据，根据type_id替换对应类型名
		foreach ($list as $key => $value) {
			$list[$key]['type_id'] = $typeinfo[$value['type_id']]['type_name'];
		}

		return array('pageStr'=>$show,'list'=>$list);
	}
}