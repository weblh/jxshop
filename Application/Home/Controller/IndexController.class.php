<?php
namespace Home\Controller;

class IndexController extends CommonController {
    public function index(){
    	//控制是否展开分类
    	$this->assign('is_show',1);

    	$goodsModel = D('Admin/Goods');
    	//获取热卖商品信息
    	$hot = $goodsModel->getRecGoods('is_hot');
    	$this->assign('hot',$hot);
    	//获取推荐商品信息
    	$rec = $goodsModel->getRecGoods('is_rec');
    	$this->assign('rec',$rec);
    	//获取新品商品信息
    	$new = $goodsModel->getRecGoods('is_new');
    	$this->assign('new',$new);
        //获取当前正在促销的商品
        $crazy = $goodsModel->getCrazyGoods();
        $this->assign('crazy',$crazy);
        //获取当前正在促销的商品
        $floor = D('Admin/Category')->getFloor();
        $this->assign('floor',$floor);
        //dump($floor);

        $this->display();
    }

    public function test() {
        $cate = D('Admin/Category');
        $data = $cate->select();
        //对获取的信息格式化
        $list = $cate->getTree($data,0,1,false);
        dump($list);
    }
}