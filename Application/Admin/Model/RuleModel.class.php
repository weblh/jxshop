<?php
namespace Admin\Model;

/**
 * 权限模型
 */
class RuleModel extends CommonModel{
	
	//自定义字段
	protected $fields = array('id','rule_name','module_name','controller_name','action_name','parent_id','is_show');
	//自动验证
	protected $_validate = array(
		array('rule_name','require','权限名称必须填写'),
		array('module_name','require','模型名称必须填写'),
		array('controller_name','require','控制器名称必须填写'),
		array('action_name','require','方法名称必须填写'),
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
		//根据参数决定是否需要重置
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
}