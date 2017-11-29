<?php
namespace Home\Model;

/**
 * 用户模型
 */
class UserModel extends CommonModel{

	//字段定义
	protected $fields = array('id','username','password','salt','tel','status','email','active_code');

	//注册实现用户的信息入库
	public function regist($username,$password,$tel) {
		//检查用户名是否可用
		$info = $this->where("username='$username'")->find();
		if ($info) {
			$this->error = '用户名重复';
			return false;
		}
		//检查手机号是否重复
		$info = $this->where("tel={$tel}")->find();
		if ($info) {
			$this->error = '手机号重复';
			return false;
		}
		//生成盐
		$salt = rand(100000,999999);
		//生成双重md5之后的密码
		$db_password = md5(md5($password.$salt));
		$data = array(
			'username'=>$username,
			'password'=>$db_password,
			'salt'=>$salt,
			'tel'=>$tel,
			'status'=>1,
		);
		return $this->add($data);
	}

	//登陆
	public function login($username,$password) {
		//调用接口操作数据
		$parms = array(
			'username'=>$username,
			'password'=>$password,
			'c'=>'User',
			'a'=>'login'
		);
		$res = get_data($parms);
		if ($res['status']==0) {
			$this->error = $res['msg'];
			return false;
		}
		$info = $res['data'];
		//保存用户登陆状态
		session('user',$info);
		session('user_id',$info['id']);
		//实现购物车cookie中数据转移到数据库
		D('Cart')->cookie2db();
		return true;
	}

	//实现用户使用邮箱进行注册
	public function registbyemail($username,$password,$email) {
		//检查用户名是否可用
		$info = $this->where("username='{$username}'")->find();
		if ($info) {
			$this->error = '用户名重复';
			return false;
		}
		//检查邮箱是否重复
		$info = $this->where("email='{$email}'")->find();
		if ($info) {
			$this->error = '邮箱重复';
			return false;
		}
		//生成盐
		$salt = rand(100000,999999);
		//生成双重md5之后的密码
		$db_password = md5(md5($password.$salt));
		$data = array(
			'username'=>$username,
			'password'=>$db_password,
			'salt'=>$salt,
			'email'=>$email,
			'status'=>0,
			'active_code'=>uniqid()//生成激活码
		);
		$user_id = $this->add($data);
		$data['user_id'] = $user_id;
		return $data;
	}
}