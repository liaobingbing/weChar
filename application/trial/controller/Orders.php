<?php
/**
 * Created by sub.
 * User: wei
 * Date: 2018/7/10
 * Time: 14:10
 */
// 订单

namespace app\trial\controller;
use think\Controller;
use app\trial\model\Users;
use app\trial\model\Check;
use think\Db;
class Orders extends Controller
{
	// 发起0元购
	public function apply()
	{
		$user_id    = input('post.user_id');
		$checkMol=new Check();
		$checkMol->needLogin($user_id); //检查用户登录状态
		$goods_id   = input('post.goods_id');
		if(!$goods_id){
			return array('code'=>400,'msg'=>'goods_id不能为空','data'=>"");
		}
		$goods_info = db('goods')->where('id',$goods_id)->field('id goods_id,goods_name,goods_no,price,primary_picture,secondary_picture,receive_num,join_num,status,user_limit,mail_price,share_img')->find();
		if($goods_info['status'] == 0){
			// 商品下架
			return array('code'=>401,'msg'=>'该商品已下架','data'=>"");
		}
		$cart_where = [
			'user_id'=>$user_id,
			'goods_id'=>$goods_id,
			'status'=>1//有效范围内
		];
		// 是否已发起
		if($cart_info = db('cart')->where($cart_where)->field('id cart_id,end_time,need_num,num')->find()){
			// 未过期
			if($cart_info['end_time'] > time()){
				return array('code'=>201,'msg'=>'该商品已申请，前往查看','data'=>$cart_info);
			}else{
				// 设置已过期
				db('cart')->where($cart_where)->update(array('status'=>0));
			}
		}
		$userInfo = db('users')->where('id',$user_id)->field('id,chance_num')->find();
        if(intval($userInfo['chance_num']) > 0){
            db('users')->where('id',$user_id)->setDec('chance_num');
			$arr = [
				'user_id'     =>  $user_id,
				'goods_id'    =>  $goods_id,
				'need_num'    =>  $goods_info['join_num'],
				'status'      =>  1,
				'add_time'    =>  time(),
				'end_time'    =>  time()+($goods_info['user_limit']*60*60)
			];
			if($cart_id = db('cart')->insertGetId($arr)){
				$data = $arr;
				$data['cart_id']    = $cart_id;
				$data['goods_info'] = $goods_info;
				return array('code'=>200,'msg'=>'申请成功','data'=>$data);
			}else{
				return array('code'=>400,'msg'=>'申请失败，请稍后再试');
			}
            return array('code'=>200,'msg'=>'领取成功','data'=>$userInfo);
        }else{
            return array('code'=>403,'msg'=>'暂无领取机会','data'=>$userInfo);
        }
		
	}

	// 0元购详情
	public function applyInfo()
	{
		$user_id    = input('post.user_id');
		$cart_id    = input('post.cart_id');
		if(!$cart_id){
			return array('code'=>400,'msg'=>'cart_id不能为空','data'=>"");
		}
		$info = db('cart')
			->where(array('id' => $cart_id))
			->field('id cart_id,goods_id,user_id,status,need_num,num,add_time,end_time')->find();

		// 失效时间
		if(intval($info['end_time']) > time()){
			$info['valid_time'] = intval($info['end_time']) - time();
		}else{
			$info['valid_time'] = 0;
			// return array('code'=>400,'msg'=>'该申请已失效','data'=>$info);
		}
		$info['goods_info'] = db('goods')->where('id',$info['goods_id'])->field('id goods_id,goods_name,price,primary_picture,secondary_picture,receive_num,mail_price,share_img,status')->find();
		$info['goods_info']['primary_picture'] = config('IMG_URL').$info['goods_info']['primary_picture'];
		$info['goods_info']['secondary_picture'] = config('IMG_URL').$info['goods_info']['secondary_picture'];
		$userdao=new Users();
		$info['user_info'] = $userdao->findByuid($info['user_id']);
		// 判断用户是否已助力
		$info['support_status'] = db('support_log')->where(array('cart_id'=>$cart_id,'user_id'=>$user_id))->count()?1:0;
		return array('code'=>200,'data'=>$info);
	}

	// 申请列表
	public function applyList()
	{
		$user_id    = input('post.user_id');
		// 测试
		// $user_id  = '2';
		// select * from tablename order by column1 desc,
// 　　CHARINDEX(column2,'AAA,BBB') , column3 desc
		// $userdao=new Users();
		// $data['user_info'] =$userdao->findByuid($user_id);
		$sql = 'SELECT c.id cart_id,`c`.`goods_id`,`c`.`status`,`c`.`need_num`,`c`.`num`,`c`.`add_time`,`c`.`end_time`,`g`.`id`,`g`.`goods_name`,`g`.`price`,`g`.`primary_picture`,`g`.`secondary_picture`,`g`.`receive_num`,`g`.`share_img` FROM shop_cart c INNER JOIN `shop_goods` `g` ON `g`.`id`=`c`.`goods_id` WHERE `user_id` = '.$user_id.' ORDER BY field(c.status,1) desc,c.add_time desc';
		$list = Db::query($sql);
		// $list = db('cart c')->where('user_id',$user_id)->order('c.add_time desc')->field('
	 //            c.id cart_id,
	 //            c.goods_id,
	 //            c.status,
	 //            c.need_num,
	 //            c.num,
	 //            c.add_time,
	 //            c.end_time,
	 //            c.status,
	 //            g.id,
	 //            g.goods_name,
	 //            g.price,
	 //            g.primary_picture,
	 //            g.secondary_picture,
	 //            g.receive_num,
	 //            g.share_img
	 //        ')
		// 	->join('shop_goods g','g.id=c.goods_id')
		// 	->select();
		// var_dump($list);exit();
		// var_dump(db()->getLastSql());exit();
		foreach ($list as &$value) {
			$value['primary_picture'] = config('IMG_URL').$value['primary_picture'];
			$value['secondary_picture'] = config('IMG_URL').$value['secondary_picture'];
			if(intval($value['end_time']) > time()){
				$value['valid_time'] = intval($value['end_time']) -time();
			}else{
				$value['valid_time'] = 0;
			}
		}
		unset($value);
		// $data['list'] = $list;
		return array('code'=>200,'data'=>$list);
	}

	// 助力一下
	public function support()
	{
		$user_id    = input('post.user_id');
		$cart_id    = input('post.cart_id');
		$cart_info  = db('cart')->where('id',$cart_id)->find();
		if(!$cart_info){
			return array('code'=>400,'msg'=>'该申请不存在');
		}
		$goods_info = db('goods')->where('id',$cart_info['goods_id'])->find();
		if($goods_info['status'] == 0){
			db('cart')->where('id',$cart_id)->update(array('status'=>0));
			return array('code'=>400,'msg'=>'该商品已下架');
		}
		if($cart_info['end_time'] < time()){
			db('cart')->where('id',$cart_id)->update(array('status'=>0));
			return array('code'=>400,'msg'=>'该助力已失效');
		}
		if($cart_info['need_num'] > $cart_info['num']){
			// 还需要助力
			if(db('support_log')->where(array('user_id'=>$user_id,'cart_id'=>$cart_id))->count()){
				return array('code'=>200,'msg'=>'已助力');
			}else{
				if(db('support_log')->insertGetId(array('user_id'=>$user_id,'cart_id'=>$cart_id,'add_time'=>time()))){
					db('cart')->where('id',$cart_id)->setInc('num',1);
					// 判断是否助力成功
					if($cart_info['need_num'] > ($cart_info['num']+1)){
						return array('code'=>200,'msg'=>'已助力');
					}else{
						db('cart')->where('id',$cart_id)->update(array('status'=>2));
						return array('code'=>201,'msg'=>'该申请已成功');
					}
				}else{
					return array('code'=>400,'msg'=>'助力失败，请稍后再试');
				}
			}
		}else{
			db('cart')->where('id',$cart_id)->update(array('status'=>2));
			return array('code'=>201,'msg'=>'该申请已成功');
		}
	}

	// 生成订单
	public function addOrder()
	{
		$user_id       = input('post.user_id');
		$goods_id      = input('post.goods_id');
		$cart_id       = input('post.cart_id');
		$pay_price     = input('post.pay_price');//支付金额
		$adr           = input('post.adr');//获取前台传送的用户信息
        $adr           = str_replace("&quot;","\"",$adr);
        $adr           = json_decode($adr,true);
		$checkMol=new Check();
		$checkMol->needLogin($user_id); //检查用户登录状态
		if(!$goods_id){
			return array('code'=>400,'msg'=>'goods_id不能为空','data'=>"");
		}
		if(!$cart_id){
			return array('code'=>400,'msg'=>'cart_id不能为空','data'=>"");
		}
		// if(!$address_id){
		// 	return array('code'=>400,'msg'=>'address_id不能为空','data'=>"");
		// }
		$goods_info = db('goods')->where('id',$goods_id)->field('id goods_id,goods_name,goods_no,price,primary_picture,secondary_picture,receive_num,join_num,status,user_limit,mail_price,share_img')->find();
		if($goods_info['status'] == 0){
			// 商品下架
			return array('code'=>401,'msg'=>'该商品已下架','data'=>"");
		}
		// 是否已领取过
		// if($orderInfo = db('order')->where(array('user_id'=>$user_id,'cart_id'=>$cart_id,'goods_id'=>$goods_id,'pay_status'=>0))->find()){
		// 	$orderInfo['goods_info'] = $goods_info;
		// 	// 已添加
		// 	return array('code'=>200,'msg'=>'提交成功','data'=>$orderInfo);
		// }
		$order_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
		$arr = [
			'order_no'    =>  $order_no,
			'user_id'     =>  $user_id,
			'cart_id'     =>  $cart_id,
			'goods_id'    =>  $goods_id,
			'addr_id'     =>  $adr['address_id'],
			'user_name'   =>  $adr['user_name'],
			'phone'       =>  $adr['phone'],
			'province'    =>  $adr['province'],
			'city'        =>  $adr['city'],
			'county'      =>  $adr['county'],
			'detail'      =>  $adr['detail'],
			'status'      =>  0,//待发货
			'add_time'    =>  time(),
			'pay_status'  =>  1,//待支付
			'pay_price'   =>  $pay_price,
			'pay_time'    =>  time()
		];
		if($order_id = db('order')->insertGetId($arr)){
			//已领取
			db('cart')->where('id',$cart_id)->update(array('status'=>3));
			$data = $arr;
			$data['order_id']    = $order_id;
			$data['goods_info'] = $goods_info;
			return array('code'=>200,'msg'=>'提交成功','data'=>$data);
		}else{
			return array('code'=>400,'msg'=>'提交失败，请稍后再试');
		}
	}

	// 订单详情
	public function orderInfo()
	{
		$user_id    = input('post.user_id');
		$order_no   = input('post.order_no');
		if(!$order_no){
			return array('code'=>400,'msg'=>'order_no不能为空','data'=>"");
		}
		$info = db('order')->where(array('order_no' => $order_no))->find();
		$info['goods_info'] = db('goods')->where('id',$info['goods_id'])->field('id goods_id,goods_name,price,primary_picture,secondary_picture,mail_price,share_img,status')->find();
		$userdao=new Users();
		$info['goods_info']['primary_picture'] = config('IMG_URL').$info['goods_info']['primary_picture'];
		$info['goods_info']['secondary_picture'] = config('IMG_URL').$info['goods_info']['secondary_picture'];
		$info['user_info'] = $userdao->findByuid($user_id);
		// $info['address_info'] = db('address')->where('id',$info['addr_id'])->field('id addr_id,user_name,phone,province,city,county,detail')->find();
		return array('code'=>200,'data'=>$info);
	}

	// 订单列表
	public function orderList()
	{
		$user_id    = intval(input('post.user_id'));
		$page_no    = input('post.page_no',1);
		$page_num    = input('post.page_num',10);
		// $userdao=new Users();
		// $data['user_info'] =$userdao->findByuid($user_id);
		$list = db('order o')->where('o.user_id',$user_id)->page($page_no, $page_num)->order('o.add_time desc')->field('
	            o.id order_id,
	            o.order_no,
	            o.goods_id,
	            o.addr_id,
	            o.status,
	            o.add_time,
	            o.send_time,
	            o.courier_no,
	            g.id,
	            g.goods_name,
	            g.price,
	            g.primary_picture,
	            g.secondary_picture,
	            g.share_img,
	            g.status
	        ')
			->join('shop_goods g','g.id=o.goods_id')
			->select();
		foreach ($list as &$value) {
			$value['primary_picture'] = config('IMG_URL').$value['primary_picture'];
			$value['secondary_picture'] = config('IMG_URL').$value['secondary_picture'];
		}
		unset($value);
		// $data['list'] = $list;
		return array('code'=>200,'data'=>$list);
	}

	// 修改订单支付状态
	public function updatePayStatus()
	{
		$order_no    = input('post.order_no');
		$pay_status  = intval(input('post.pay_status'));
		$pay_price   = input('post.pay_price');
		if(db('order')->where('order_no',$order_no)->count()){
			$arr = [
				'pay_status'=> 1,
				'pay_price' => $pay_price,
				'pay_time'  => time()
			];
			db('order')->where('order_no',$order_no)->update($arr);
			return array('code'=>200,'msg'=>'支付成功');

		}
		return array('code'=>400,'msg'=>'该订单不存在');
	}

	// 修改订单地址
	public function updateOrderAdrId()
	{
		$order_no     = input('post.order_no');
		$addr_id      = input('post.adr_id');
		if(db('order')->where('order_no',$order_no)->count()){
			$address_info = db('address')->where('id',$addr_id)->find();
			$arr = [
				'addr_id'   =>  $addr_id,
				'user_name' =>  $address_info['user_name'],
				'phone'     =>  $address_info['phone'],
				'province'  =>  $address_info['province'],
				'city'     	=>  $address_info['city'],
				'county'    =>  $address_info['county'],
				'detail'    =>  $address_info['detail']
			];
			db('order')->where('order_no',$order_no)->update($arr);
			return array('code'=>200,'msg'=>'修改成功');
		}
		return array('code'=>400,'msg'=>'该订单不存在');
	}

	// 更多免费领取头像列表
	public function getUserList()
	{
		$page_no       = input('post.page_no',1);
		$page_num      = input('post.page_num',10);
		$goods_id      = input('post.goods_id');
		$list = db('cart c')->where('goods_id',$goods_id)->page($page_no, $page_num)->group('user_id')->field('
	            c.id cart_id,
	            c.goods_id,
	            c.user_id,
	            u.nickname,
	            u.avatar_url
	        ')
			->join('shop_users u','u.id=c.user_id')
			->select();
		$count = db('cart')->where('goods_id',$goods_id)->group('user_id')->count();
		return array('code'=>200,'data'=>$list,'count'=>$count);
	}
}