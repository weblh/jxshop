<?php
namespace Admin\Model;

/**
 * 商品属性模型
 */
class GoodsAttrModel extends CommonModel {
	protected $fields = array('id','goods_id','attr_id','attr_values');
	public function insertAttr($attr,$goods_id) {
		foreach ($attr as $key => $value) {
			foreach ($value as $v) {
				$attr_list[] = array(
					'goods_id'=>$goods_id,
					'attr_id'=>$key,
					'attr_values'=>$v
				);
			}
		}
		M('GoodsAttr')->addAll($attr_list);
	}

	//根据商品id获取单选属性相关信息
	public function getSigleAttr($goods_id) {
		$data = $this->field('a.*,attr_name,attr_type,attr_input_type,attr_value')->alias('a')->join('left join __ATTRIBUTE__ b on a.attr_id=b.id')->where("a.goods_id={$goods_id} and b.attr_type=2")->select();
		foreach ($data as $key => $value) {
			$list[$value['attr_id']][] = $value;
		}
		//dump($list);exit;
		return $list;
	}
}