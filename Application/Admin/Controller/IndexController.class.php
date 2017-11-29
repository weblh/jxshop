<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends CommonController {
    public function index(){
        $this->display();
    }
    public function top(){
        $this->display();
    }
    public function menu(){
        //赋值权限信息给模板
        $this->assign('menus',$this->user['menus']);
        $this->display();
    }
    public function main(){
        $this->display();
    }
}