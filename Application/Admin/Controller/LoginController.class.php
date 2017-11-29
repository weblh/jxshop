<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 登陆控制器
 */
class LoginController extends Controller {
	
	public function login() {
		if (IS_GET) {
			$this->display();
		}else{
			$captcha = I('post.captcha');
			$verify = new \Think\Verify();
			$res = $verify->check($captcha,1);
			if (!$res) {
				$this->error('验证码不正确');
			}
			//接受用户密码调用模型方法进行对比
			$username = I('post.username');
			$password = I('post.password');
			$model = D('Admin');
			$res = $model->login($username,$password);
			if (!$res) {
				$this->error($model->getError());
			}
			$this->success('登录成功',U('Index/index'));
		}
	}

	//生成验证码
	public function verify() {
		$config = array('length'=>4);
		$verify = new \Think\Verify($config);
		$verify->entry(1);
	}
}