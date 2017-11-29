<?php
namespace Admin\Controller;

/**
 * 管理员控制器
 */
class AdminController extends CommonController{
	
	public function add() {
		if (IS_GET) {
			$role = D('Role')->select();
			$this->assign('role',$role);
			$this->display();
		}else{
			$model = D('Admin');
			$data = $model->create();
			if (!$data) {
				$this->error($model->getError());
			}
			$model->add($data);
			$this->success('写入数据成功');
		}
	}

	public function index() {
		$model = D('Admin');
		$data = $model->listData();
		$this->assign('data',$data);
		$this->display();
	}

	public function dels() {
		$admin_id = intval(I('get.admin_id'));
		if ($admin_id<=1) {
			$this->error('参数错误');
		}
		$res = D('Admin')->remove($admin_id);
		if ($res===false) {
			$this->error('删除失败');
		}
		$this->success('删除成功');
	}

	public function edit() {
		$model = D('Admin');
		if (IS_GET) {
			$admin_id = intval(I('get.admin_id'));
			//获取用户名密码以及对应的角色ID
			$info = $model->findOne($admin_id);
			//获取所有角色
			$role = D('Role')->select();

			$this->assign('info',$info);
			$this->assign('role',$role);
			$this->display();
		}else{
			$data = $model->create();
			if (!$data) {
				$this->error($model->getError());
			}
			if ($data['id']<=1) {
				$this->error('参数错误');
			}
			$model->update($data);
			$this->success('修改成功',U('index'));
		}
	}

}