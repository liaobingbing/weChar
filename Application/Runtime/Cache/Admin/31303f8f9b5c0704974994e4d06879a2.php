<?php if (!defined('THINK_PATH')) exit();?>


<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<meta name="renderer" content="webkit|ie-comp|ie-stand">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
<meta http-equiv="Cache-Control" content="no-siteapp" />
<link rel="Bookmark" href="/Public/Admin//favicon.ico" >
<link rel="Shortcut Icon" href="/Public/Admin//favicon.ico" />
<!--[if lt IE 9]>
<script type="text/javascript" src="/Public/Admin/lib/html5shiv.js"></script>
<script type="text/javascript" src="/Public/Admin/lib/respond.min.js"></script>
<![endif]-->
<link rel="stylesheet" type="text/css" href="/Public/Admin/static/h-ui/css/H-ui.min.css" />
<link rel="stylesheet" type="text/css" href="/Public/Admin/static/h-ui.admin/css/H-ui.admin.css" />
<link rel="stylesheet" type="text/css" href="/Public/Admin/lib/Hui-iconfont/1.0.8/iconfont.css" />
<link rel="stylesheet" type="text/css" href="/Public/Admin/static/h-ui.admin/skin/default/skin.css" id="skin" />
<link rel="stylesheet" type="text/css" href="/Public/Admin/static/h-ui.admin/css/style.css" />
<!--[if IE 6]>
<script type="text/javascript" src="/Public/Admin/lib/DD_belatedPNG_0.0.8a-min.js" ></script>
<script>DD_belatedPNG.fix('*');</script>
<![endif]-->
<title><?php echo ((isset($title ) && ($title !== ""))?($title ):'默认TITLE'); ?></title>
<meta name="keywords" content="<?php echo ((isset($keywords ) && ($keywords !== ""))?($keywords ):'默认关键字'); ?>">
<meta name="description" content="<?php echo ((isset($description ) && ($description !== ""))?($description ):'默认描述'); ?>">
</head>

<body>




	<article class="page-container">
		<form class="form form-horizontal" id="form-admin-add" enctype="multipart/form-data">
			<div class="row cl">
				<label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>小程序名称：</label>
				<div class="formControls col-xs-8 col-sm-6">
					<input type="text" class="input-text" value="" placeholder="" name="name">
				</div>
			</div>
			<div class="row cl">
				<label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>描述：</label>
				<div class="formControls col-xs-8 col-sm-6">
					<input type="text" class="input-text" value="" placeholder="" name="desc">
				</div>
			</div>
			<div class="row cl">
				<label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>AppID：</label>
				<div class="formControls col-xs-8 col-sm-6">
					<input type="text" class="input-text" value="" placeholder="" name="appid">
				</div>
			</div>
			<div class="row cl">
				<label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>排序：</label>
				<div class="formControls col-xs-8 col-sm-6">
					<input type="text" class="input-text" value="5" placeholder="" name="sort">
				</div>
			</div>

			<div class="row cl">
				<label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>LOGO：</label>
				<div class="formControls col-xs-8 col-sm-6">
					<input type="file" class="input-text" autocomplete="off"  placeholder="" name="logo">
				</div>
			</div>
			<div class="row cl">
				<label class="form-label col-xs-4 col-sm-3">类别：</label>
				<div class="form-label col-xs-4 col-sm-3"> <span class="select-box">
				<select class="select" size="1" name="type">
					<option value="" selected>请选择类别</option>
					<?php if(is_array($type)): $i = 0; $__LIST__ = $type;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$t_vo): $mod = ($i % 2 );++$i;?><option value="<?php echo ($t_vo["id"]); ?>"><?php echo ($t_vo["name"]); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
				</select>
				</span> </div>
			</div>
			<div class="row cl">
				<label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>状态：</label>
				<div class="formControls col-xs-8 col-sm-9 skin-minimal">
					<div class="radio-box">
						<input name="status" type="radio" id="sex-1" checked value="1">
						<label for="sex-1">显示</label>
					</div>
					<div class="radio-box">
						<input type="radio" id="sex-2" name="status" value="0">
						<label for="sex-2">隐藏</label>
					</div>
				</div>
			</div>

			<div class="row cl">
				<div class="col-xs-8 col-sm-6 col-xs-offset-4 col-sm-offset-3">
					<input class="btn btn-primary radius" type="submit" value="&nbsp;&nbsp;提交&nbsp;&nbsp;">
				</div>
			</div>
		</form>
	</article>


<script type="text/javascript" src="/Public/Admin/lib/jquery/1.9.1/jquery.min.js"></script> 
<script type="text/javascript" src="/Public/Admin/lib/layer/2.4/layer.js"></script>
<script type="text/javascript" src="/Public/Admin/static/h-ui/js/H-ui.min.js"></script>
<script type="text/javascript" src="/Public/Admin/static/h-ui.admin/js/H-ui.admin.js"></script> 



<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/Public/Admin/lib/jquery.validation/1.14.0/jquery.validate.js"></script>
<script type="text/javascript" src="/Public/Admin/lib/jquery.validation/1.14.0/validate-methods.js"></script>
<script type="text/javascript" src="/Public/Admin/lib/jquery.validation/1.14.0/messages_zh.js"></script>
<script type="text/javascript">
$(function(){
	$('.skin-minimal input').iCheck({
		checkboxClass: 'icheckbox-blue',
		radioClass: 'iradio-blue',
		increaseArea: '20%'
	});
	
	$("#form-admin-add").validate({
		rules:{
			name:{
				required:true,
			},
            desc:{
                required:true,
                minlength:0,
                maxlength:50
            },
            logo:{
                required:true,
            },
            appid:{
                required:true,
            },
            type:{
                required:true,
            },
            sort:{
                digits:true,
            },


		},
		onkeyup:false,
		focusCleanup:true,
		success:"valid",
		submitHandler:function(form){
			$(form).ajaxSubmit({
				type: 'post',
				url: "<?php echo U('Admin/App/do_add');?>" ,
				success: function(data){
				    console.log(data.code);

				    if ( data.code == 200 ){
						layer.msg('添加成功!',{icon:1,time:1000});
						setTimeout(function () {
                            var index = parent.layer.getFrameIndex(window.name);
                            /*parent.$('.btn-refresh').click();
                            parent.layer.close(index);*/
                            parent.refresh();
                            parent.layer.close(index);
                        },1000)
					}else{
                        layer.msg(data.msg,{icon:3,time:2000});
					}

				},
                error: function(XmlHttpRequest, textStatus, errorThrown){
					layer.msg('error!',{icon:5,time:1000});
				}
			});
		}
	});
});
</script> 
<!--/请在上方写此页面业务相关的脚本-->

</body>
</html>