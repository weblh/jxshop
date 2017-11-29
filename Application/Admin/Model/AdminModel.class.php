<?php
namespace Admin\Model;

/**
 * 管理员模型
 */
class AdminModel extends CommonModel{
	
	//定义字段
	protected $fields = array('id','username','password');
	//自动验证
	protected $_validate = array(
		array('username','require','用户名必须填写！'),
		array('username','','用户名重复！',1,'unique'),
		array('password','require','密码必须填写！'),
	);
	//自动完成
	protected $_auto = array(
		array('password','md5',3,'function'),
	);

	//使用钩子函数实现中间表数据入库
	protected function _after_insert($data) {
		$admin_role = array(
			'admin_id'=>$data['id'],
			'role_id'=>I('post.role_id')
		);
		M('AdminRole')->add($admin_role);
	}

	public function listData() {
		$pagesize = 3;
		$count = $this->count();
		$page = new \Think\Page($count,$pagesize);
		$show = $page->show();
		//当前页码
		$p = intval(I('get.p'));
		$list = $this->alias('a')->field('a.*,c.role_name')->join('left join __ADMIN_ROLE__ b on a.id=b.admin_id')->join('left join __ROLE__ c on b.role_id=c.id')->page($p,$pagesize)->select();
		return array('pageStr'=>$show,'list'=>$list);
	}

	//删除角色
	public function remove($admin_id) {
		//开启事务
		$this->startTrans();
		//删除对应用户信息
		$userStatus = $this->delete($admin_id);
		if (!$userStatus) {
			$this->rollback();
			return false;
		}
		//删除对应用户角色信息
		$roleStatus = M('AdminRole')->where("admin_id={$admin_id}")->delete();
		if (!$roleStatus) {
			$this->rollback();
			return false;
		}
		$this->commit();
		return true;
	}

	//根据用户ID获取用户基本信息以及对应的角色ID
	public function findOne($admin_id) {
		return $this->alias('a')->field("a.*,b.role_id")->join("left join __ADMIN_ROLE__ b on a.id=b.admin_id")->where("a.id=$admin_id")->find();
	}

	public function update($data) {
		$role_id = intval(I('post.role_id'));
		//修改用户基本信息
		$this->save($data);
		//修改用户对应的角色信息
		M('AdminRole')->where('admin_id='.$data['id'])->save(array('role_id'=>$role_id));
	}

	//用户名密码验证
	public function login($username,$password) {
		$userinfo = $this->where("username='$username'")->find();
		if (!$userinfo) {
			$this->error = '用户名不存在';
			return false;
		}
		if ($userinfo['password']!=md5($password)) {
			$this->error = '密码错误';
			return false;
		}
		//保存用户登录状态
		cookie('admin',$userinfo);
		return true;
	}
}