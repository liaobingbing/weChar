<?php
/**
 * Created by sub.
 * User: wei
 * Date: 2018/7/10
 * Time: 14:10
 */
// 商品管理
namespace app\trial\controller;
use think\Controller;
class Api extends Controller
{
	// 首页轮播文字
	public function receiveLog()
	{
		$list = db('order o')->limit(100)->order('o.add_time desc')->field('
	            o.goods_id,
	            o.add_time,
	            o.user_name,
	            g.id,
	            g.goods_name
	        ')
			->join('shop_goods g','g.id=o.goods_id')
			->select();
		return array('code'=>200,'data'=>$list);
	}
	// 商品列表
	public function getGoodsList()
	{
		$page_no  = input('post.page_no',1);
		$page_num = input('post.page_num',10);
		$list = db('goods')
			->where(array('status' => 1))
			->order('sort desc','add_time desc')
			->field('id goods_id,goods_name,goods_no,price,primary_picture,secondary_picture,desc,receive_num,join_num,mail_price,share_img,sort,add_time')
			->page($page_no,$page_num)
			->select();
		foreach ($list as &$value) {
			$value['primary_picture'] = config('IMG_URL').$value['primary_picture'];
			$value['secondary_picture'] = config('IMG_URL').$value['secondary_picture'];
		}
		unset($value);
		return array('code'=>200,'data'=>$list);
	}

	// 商品详情
	public function goodsDetail()
	{
		$goods_id=input('post.goods_id');
		if(!$goods_id){
			return array('code'=>400,'msg'=>'goods_id不能为空','data'=>"");
		}
		$info = db('goods')
			->where(array('id' => $goods_id))
			->field('id goods_id,goods_name,goods_no,price,primary_picture,secondary_picture,desc,receive_num,join_num,mail_price,share_img,status')->find();
		$info['primary_picture'] = config('IMG_URL').$info['primary_picture'];
		$info['secondary_picture'] = config('IMG_URL').$info['secondary_picture'];
		return array('code'=>200,'data'=>$info);
	}
}