<?php
/**
 * Created by sub.
 * User: wei
 * Date: 2018/7/10
 * Time: 14:10
 */
// 地址
namespace app\trial\controller;
use think\Controller;
use app\trial\model\Check;
class Address extends Controller
{
	// 获取地址列表
	public function getAddressList()
	{
		$user_id=input('post.user_id');
		// $checkMol=new Check();
		// $checkMol->needLogin($user_id); //检查用户登录状态
		$list = db('address')
			->where(array('user_id' => $user_id))
			->field('id address_id,user_name,phone,province,city,county,detail')
			->order('add_time desc')
			->select();
		return array('code'=>200,'data'=>$list);
	}

	// 地址详情
	public function detail()
	{
		$address_id=input('post.address_id');
		if(!$address_id){
			return array('code'=>400,'msg'=>'address_id不能为空','data'=>"");
		}
		$info = db('address')
			->where(array('id' => $address_id))
			->field('id address_id,user_name,phone,province,city,county,detail')->find();
		return array('code'=>200,'data'=>$info);
	}

	/**
	 * 更新收获地址
	 * @param address_id 地址id
	 * @param user_id 用户id
	 * @param province 省
	 * @param city 市
	 * @param county 区
	 * @param detail 地址详情
	 * @param user_name 名字
	 * @param phone 手机号码
	 */
	public function update() {
		$user_id    = intval(input('post.user_id'));
		$address_id = intval(input('post.address_id'));
		if(!$address_id){
			return array('code'=>400,'msg'=>'address_id不能为空','data'=>"");
		}
		$add_arr = [
			'user_name' =>input('post.user_name'),
			'phone'     =>input('post.phone'),
			'province'  =>input('post.province'),
			'city'      =>input('post.city'),
			'county'    =>input('post.county'),
			'detail'    =>input('post.detail')
		];
		if(db('address')->where($add_arr)->count()){
			return array('code'=>200,'msg'=>'数据无变动','data'=>"");
		}
		if (db('address')->where(array('id' => $address_id))->update($add_arr)) {
			$add_arr['address_id'] = $address_id;
			return array('code'=>200,'msg'=>'修改成功','data'=>$add_arr);
		} else {
			return array('code'=>400,'msg'=>'修改失败','data'=>$add_arr);
		}
	}

	/**
	 * 新增收获地址
	 * @param user_id 用户id
	 * @param province 省
	 * @param city 市
	 * @param county 区
	 * @param detail 地址详情
	 * @param user_name 名字
	 * @param phone 手机号码
	 * @param is_default 是否默认地址
	 */
	public function add() {
		$user_id    = intval(input('post.user_id'));
		$add_arr = [
			'user_id'   =>$user_id,
			'user_name' =>input('post.user_name'),
			'phone'     =>input('post.phone'),
			'province'  =>input('post.province'),
			'city'      =>input('post.city'),
			'county'   =>input('post.county'),
			'detail'    =>input('post.detail'),
			'add_time'  =>time()
		];
		if($address_id = db('address')->insertGetId($add_arr)){
			return array('code'=>200,'msg'=>'添加成功','data'=>$add_arr);
		}else{
			return array('code'=>400,'msg'=>'添加失败','data'=>$add_arr);
		}
	}

	// 删除地址
	public function delete()
	{
		$user_id=input('post.user_id');
		$address_id=input('post.address_id');
		$checkMol=new Check();
		$checkMol->needLogin($user_id); //检查用户登录状态
		if(!$address_id){
			return array('code'=>400,'msg'=>'address_id不能为空','data'=>"");
		}
		if(db('address')->where('id',$address_id)->delete()){
        	return array('code'=>200,'msg'=>'删除成功');
		}else{
			return array('code'=>400,'msg'=>'删除失败');
		}
	}
}