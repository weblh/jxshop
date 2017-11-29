<?php
namespace Home\Controller;

class GoodsController extends CommonController {
    public function index(){
    	//获取商品信息
    	$goods_id = intval(I('goods_id'));
    	$goodsModel = D('Admin/Goods');
    	if ($goods_id<=0) {
    		$this->redirect('Index/index');
    	}
    	$goods = $goodsModel->where("is_sale=1 and id={$goods_id}")->find();
    	if (!$goods) {
    		//如果商品不存在或下架不允许查
    		$this->redirect('Index/index');
    	}
        //若当前商品处于促销阶段价格显示为促销价格
        if ($goods['cx_price']>0&&$goods['start']<time()&&$goods['end']>time()) {
            $goods['shop_price'] = $goods['cx_price'];
        }
    	$goods['goods_body'] = htmlspecialchars_decode($goods['goods_body']);
    	$this->assign('goods',$goods);
    	//获取商品相册信息
    	$pic = M('GoodsImg')->where('goods_id='.$goods_id)->select();
    	$this->assign('pic',$pic);

    	//获取当前商品对应属性信息
    	$attr = M('GoodsAttr')->alias('a')->field('a.*,b.attr_name,b.attr_type')->join('left join __ATTRIBUTE__ b on a.attr_id=b.id')->where('a.goods_id='.$goods_id)->select();
    	//格式化数据
    	foreach ($attr as $key => $value) {
    		if ($value['attr_type']==1) {
    			$unique[] = $value;
    		}else{
    			$sigle[$value['attr_id']][] = $value;
    		}
    	}
    	$this->assign('unique',$unique);
    	$this->assign('sigle',$sigle);

        //获取评论数据
        $commentModel = D('comment');
        $comment = $commentModel->getList($goods_id);
        $this->assign('comment',$comment);

        //获取当前商品对应的印象数据
        $buyer = M('Impression')->where('goods_id='.$goods_id)->order('count desc')->limit(8)->select();
        $this->assign('buyer',$buyer);

    	//dump($sigle);

    	$this->display();
    }

    //增加评论对应的有用值
    public function good() {
        $comment_id = I('post.comment_id');
        $model = D('Comment');
        $info = $model->where('id='.$comment_id)->find();
        if (!$info) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'error'));
        }
        $model->where('id='.$comment_id)->setField('good_number',$info['good_number']+1);
        $this->ajaxReturn(array('status'=>1,'msg'=>'ok','good_number'=>$info['good_number']+1));
    }

    //实现评论数据入库
    public function comment() {
        //登陆判断
        $this->checkLogin();

        $model = D('comment');
        $data = $model->create();
        //dump($data);exit;
        if (!$data) {
            $this->error('参数不对');
        }
        $model->add($data);
        $this->success('写入成功');
    }
}