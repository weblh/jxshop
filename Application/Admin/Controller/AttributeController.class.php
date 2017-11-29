<?php
namespace Admin\Controller;

/**
 * 属性控制器
 */
class AttributeController extends CommonController {
	
	public function add() {
		if (IS_GET) {
			//获取所有类型信息
			$type = D('Type')->select();
			$this->assign('type',$type);
			$this->display();
		}else{
			$data = $this->model()->create();
			if (!$data) {
				$this->error($this->model()->getError());
			}
			$this->model()->add($data);
			$this->success('写入成功');
		}
	}

	//生成模型的方法
	protected function model() {
		if (!$this->_model) {
			$this->_model = D('Attribute');
		}
		return $this->_model;
	}

	public function index() {
		$data = $this->model()->listData();
		$this->assign('data',$data);
		$this->display();
	}

	public function edit() {
		if (IS_GET) {
			$attr_id = intval(I('get.attr_id'));
			$info = $this->model()->findOneById($attr_id);
			$this->assign('info',$info);

			$type = D('Type')->select();
			$this->assign('type',$type);

			$this->display();
		}else{
			$data = $this->model()->create();
			if (!$data) {
				$this->error($model->getError());
			}
			if ($data['id']<=0) {
				$this->error('参数错误');
			}
			$this->model()->save($data);
			$this->success('修改成功',U('index'));
		}
	}

	public function dels() {
		$attr_id = intval(I('get.attr_id'));
		if ($attr_id<=0) {
			$this->error('参数错误');
		}
		$res = $this->model()->delete($attr_id);
		if ($res===false) {
			$this->error('删除失败');
		}
		$this->success('删除成功');
	}
}