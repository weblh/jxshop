<?php
namespace Home\Controller;

class UserController extends CommonController {

	public function registbyemail() {
		if (IS_GET) {
			$this->display();
		}else{
			$username = I('post.username');
			$password = I('post.password');
			$email = I('post.email');
			//实例化模型对象，调用方法入库
			$model = D('User');
			$res = $model->registbyemail($username,$password,$email);
			if (!$res) {
				$this->ajaxReturn(array('status'=>0,'msg'=>$model->getError()));
			}
			//发送邮件
			//拼接具体得邮件内容中得连接地址
			$link = 'http://jxshop.com'.U('active').'?user_id='.$res['user_id'].'&active_code='.$res['active_code'];
			sendemail($email,'商城用户激活邮件',$link);
			$this->ajaxReturn(array('status'=>1,'msg'=>'ok'));
		}
	}

	//邮件激活
	public function active() {
		$user_id = I('get.user_id');
		$active_code = I('get.active_code');
		$model = D('User');
		$user_info = $model->where('id='.$user_id)->find();

		if (!$user_info) {
			$this->error('参数错误');
		}
		if ($user_info['status']==1) {
			$this->error('已经激活');
		}
		if ($active_code!=$user_info['active_code']) {
			$this->error('激活码错误');
		}
		//修改用户状态
		$model->where('id='.$user_id)->setField('status',1);
		$this->success('激活成功',U('login'));
	}

	public function regist() {
		if (IS_GET) {
			$this->display();
		}else{
			$username = I('post.username');
			$password = I('post.password');
			$checkcode = I('post.checkcode');
			$tel = I('post.tel');//用户手机号
			$telcode = I('post.telcode');//手机验证码
			//检查验证码
			$obj = new \Think\Verify();
			if (!$obj->check($checkcode,2)) {
				$this->ajaxReturn(array('status'=>0,'msg'=>'验证码错误'));
			}
			//检查目前提交的手机验证码跟发送的是否匹配
			if (!$telcode) {
				$this->ajaxReturn(array('status'=>0,'msg'=>'没有输入手机验证码'));
			}
			//获取当前session中具体的信息
			$data = session('telcode');
			if (!$data) {
				$this->ajaxReturn(array('status'=>0,'msg'=>'没有发送手机验证码'));
			}
			//判断验证码是否过期
			if ($data['time']+$data['limit']<time()) {
				$this->ajaxReturn(array('status'=>0,'msg'=>'手机验证码过期'));
			}
			if ($data['code']!=$telcode) {
				$this->ajaxReturn(array('status'=>0,'msg'=>'手机验证码错误'));
			}

			//实例化模型对象，调用方法入库
			$model = D('User');
			$res = $model->regist($username,$password,$tel);
			if (!$res) {
				$this->ajaxReturn(array('status'=>0,'msg'=>$model->getError()));
			}
			$this->ajaxReturn(array('status'=>1,'msg'=>'ok'));
		}
	}

	//验证码
	public function code() {
		$config = array('length'=>4);
		$obj = new \Think\Verify($config);
		$obj->entry(2);
	}

	public function login() {
		if (IS_GET) {
			$this->display();
		}else{
			$username = I('post.username');
			$password = I('post.password');
			$checkcode = I('post.checkcode');
			//检查验证码
			$obj = new \Think\Verify();
			if (!$obj->check($checkcode,2)) {
				$this->ajaxReturn(array('status'=>0,'msg'=>'验证码错误'));
			}
			//实例化模型对象，调用方法入库
			$model = D('User');
			$res = $model->login($username,$password);
			if (!$res) {
				$this->ajaxReturn(array('status'=>0,'msg'=>$model->getError()));
			}
			$this->ajaxReturn(array('status'=>1,'msg'=>'ok'));
		}
	}

	public function logout() {
		session('user',null);
		session('user_id',null);
		$this->redirect('/');
	}

	public function test() {
		//phpinfo();
		// $res = sendTemplateSMS("15639168180",array('1234','60'),"1");
		// dump($res);
		//dump(session());
		//sendemail('xydlh@qq.com','测试','测试邮件发送');
		// $res = http_curl('http://api.com/?c=user&a=login',array('username'=>'小伙','password'=>'123'));
		// dump($res);
		$str = authcode('pc','ENCODE');
		dump($str);
		dump(authcode($str,'DECODE'));
	}

	//根据指定手机号发送验证码
	public function sendcode() {
		$tel = I('post.tel');
		if (!$tel) {
			$this->ajaxReturn(array('status'=>0,'msg'=>'手机号错误'));
		}
		$code = rand(1000,9999);
		$res = sendTemplateSMS($tel,array($code,'60'),'1');
		if (!$res) {
			$this->ajaxReturn(array('status'=>0,'msg'=>'发送验证码失败'));
		}
		//验证码发送成功，保存验证码到session中
		$telcode = array(
			'code'=>$code,
			'time'=>time(),
			'limit'=>'3600'
		);
		session('telcode',$telcode);
		$this->ajaxReturn(array('status'=>1,'msg'=>'发送成功'));
	}
}