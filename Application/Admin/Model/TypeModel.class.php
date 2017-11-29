<?php
namespace Admin\Model;

/**
 * 角色模型
 */
class TypeModel extends CommonModel{
	
	//定义字段
	protected $fields = array('id','type_name');
	//自动验证
	protected $_validate = array(
		array('type_name','require','类型名称必须填写！'),
		array('type_name','','类型名重复！',1,'unique'),
	);

	public function listData() {
		$pagesize = 3;
		$count = $this->count();
		$page = new \Think\Page($count,$pagesize);
		$show = $page->show();
		//当前页码
		$p = intval(I('get.p'));
		$list = $this->page($p,$pagesize)->select();return array('pageStr'=>$show,'list'=>$list);
	}

	//删除角色
	public function remove($type_id) {
		return $this->delete($type_id);
	}
}