<?php
namespace Admin\Controller;
/**
 * 权限控制器
 */
class RuleController extends CommonController {

	//分类的列表显示
	public function index() {
		$model = D('Rule');
		$list = $model->getCateTree();
		$this->assign('list',$list);
		$this->display();
	}

	//实现分类的添加
	public function add() {
		if (IS_GET) {
			//获取格式化之后的分类信息
			$model = D('Rule');
			$cate = $model->getCateTree();
			$this->assign('cate',$cate);
			$this->display();
		}else{
			//数据入库
			$model = D('Rule');
			//创建数据
			$data = $model->create();
			if (!$data) {
				$this->error($model->getError());
			}
			$insertid = $model->add($data);
			if (!$insertid) {
				$this->error('数据写入失败');
			}
			//调用方法实现删除超级管理员文件信息
			$obj = new \Admin\Controller\RoleController();
			$obj->flushAdmin();

			$this->success('写入成功');
		}
	}

	//权限分类的删除
	public function dels() {
		$id = intval(I('get.id'));
		if ($id<=0) {
			$this->error('参数不对！');
		}
		$model = D('Rule');
		$res = $model->dels($id);
		if ($res===false) {
			$this->error('删除失败!');
		}
		$this->success('删除成功');
	}

	//权限编辑
	public function edit() {
		if (IS_GET) {
			$id = intval(I('get.id'));
			$model = D('Rule');
			$info = $model->findOneById($id);
			$this->assign('info',$info);
			//获取所有分类信息
			$cate = $model->getCateTree();
			$this->assign('cate',$cate);
			$this->display();
		}else{
			//实现数据修改
			$model = D('Rule');
			$data = $model->create();
			$res = $model->update($data);
			if ($res===false) {
				$this->error($model->getError());
			}
			$this->success('修改成功');
		}
	}

}