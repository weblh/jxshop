<?php
namespace Admin\Model;

/**
 * 角色权限控制器
 */
class RoleRuleModel extends CommonModel{
	protected $fields = array('id','role_id','rule_id');
	public function disfetch($role_id,$rules) {
		$this->where("role_id={$role_id}")->delete();
		foreach ($rules as $key => $value) {
			$list[] = array(
				'role_id'=>$role_id,
				'rule_id'=>$value
			);
		}
		$this->addAll($list);
	}

	//根据角色ID获取对应的权限信息
	public function getRules($role_id) {
		return $this->where("role_id={$role_id}")->select();
	}
}