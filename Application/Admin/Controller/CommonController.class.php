<?php

namespace Admin\Controller;
use Think\Controller;

/**
 * 公共控制器
 */
class CommonController extends Controller {
	//标识是否进行权限认证
	public $is_check_rule = true;
	//保存用户信息
	public $user = array();
	public function __construct() {
		parent::__construct();

		//验证是否已登录
		$admin = cookie('admin');
		if (!$admin) {
			$this->error('没有登录',U('Login/login'));
		}

		//读取当前用户对应的文件信息
		$this->user = S('user_'.$admin['id']);
		//根据获取的文件信息判断是否需要数据库获取
		if (!$this->user) {

			//将用户信息保存到属性中
			$this->user = $admin;

			//将用户角色信息保存到属性中
			$role_info = M('AdminRole')->where('admin_id='.$admin['id'])->find();
			$this->user['role_id'] = $role_info['role_id'];

			//获取用户权限信息
			$ruleModel = D('Rule');
			if ($role_info['role_id']==1) {
				//超级管理员
				$this->is_check_rule = false;
				$rule_list = $ruleModel->select();
			}else{
				//普通管理员
				$rules = D('RoleRule')->getRules($role_info['role_id']);
				foreach ($rules as $key => $value) {
					$rules_ids[] = $value['rule_id'];
				}
				$rules_ids = implode(',', $rules_ids);
				$rule_list = $ruleModel->where("id in ({$rules_ids})")->select();
			}
			//格式化用户权限信息,并保存到user属性中  m/c/a
			foreach ($rule_list as $key => $value) {
				//用户的访问权限
				$this->user['rules'][] = strtolower($value['module_name'].'/'.$value['controller_name'].'/'.$value['action_name']);
				//要显示的导航菜单
				if ($value['is_show']==1) {
					$this->user['menus'][] = $value;
				}
			}
			//读取数据库完成后将信息写入到文件中
			S('user_',$admin['id'],$this->user);
		}

		if ($this->user['role_id']==1) {
			//超级管理员不验证
			$this->is_check_rule = false;
		}

		//增加用户默认访问权限
		if ($this->is_check_rule) {
			$this->user['rules'][] = 'admin/index/index';
			$this->user['rules'][] = 'admin/index/top';
			$this->user['rules'][] = 'admin/index/menu';
			$this->user['rules'][] = 'admin/index/main';
		}

		//判断是有有权限访问当前方法
		if ($this->is_check_rule) {
			//普通管理员
			$action = strtolower(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME);
			if (!in_array($action, $this->user['rules'])) {
				if (IS_AJAX) {
					$this->ajaxReturn(array('status'=>0,'msg'=>'没有权限'));
				}else{
					$this->error('没有权限',U('index/index'));
				}
			}
		}
	}
}