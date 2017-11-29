<?php
namespace Home\Model;
use Think\Model;
/**
 * 评论模型
 */
class CommentModel extends Model {
	protected $fields = array('id','user_id','goods_id','addtime','content','star','good_number');

	public function _before_insert(&$data) {
		$data['addtime'] = time();
		$data['user_id'] = session('user_id');
	}

	public function _after_insert($data) {
		//选择的印象入库
		$old = I('post.old');
		foreach ($old as $key => $value) {
			M('Impression')->where('id='.$value)->setInc('count');
		}

		$name = I('post.name');
		//将数据转换为数组格式
		$name = explode(',', $name);
		//对目前接受到的印象进行去重操作
		$name = array_unique($name);
		foreach ($name as $key => $value) {
			if (!$value) {
				continue;
			}
			$where = array('goods_id'=>$data['goods_id'],'name'=>$value);
			$model = M('Impression');
			$res = $model->where($where)->find();
			if ($res) {
				//说明目前的印象已经存在
				$model->where($where)->setInc('count');
			}else{
				$where['count'] = 1;
				$model->add($where);
			}
		}

		//实现商品表中评论总数增加
		M('Goods')->where('id='.$data['goods_id'])->setInc('plcount');
	}

	public function getList($goods_id) {
		//获取当前页
		$p = intval(I('get.p'));
		$pagesize = 2;
		//计算评论数据总数
		$count = $this->where('goods_id='.$goods_id)->count();
		//实例化分页类 获取分页信息
		$page = new \Think\Page($count,$pagesize);
		$page->setConfig('is_anchor',true);
		$show = $page->show();
		$list = $this->alias('a')->field('a.*,b.username')->join('left join __USER__ b on a.user_id=b.id')->where('a.goods_id='.$goods_id)->page($p,$pagesize)->select();
		return array('list'=>$list,'page'=>$show);
	}
}