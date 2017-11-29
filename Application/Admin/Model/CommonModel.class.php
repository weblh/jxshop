<?php
namespace Admin\Model;
use Think\Model;

/**
 * 公共模型类
 */
class CommonModel extends Model {
	
	//根据id获取指定的数据
	public function findOneById($id) {
		return $this->where('id='.$id)->find();
	}
}