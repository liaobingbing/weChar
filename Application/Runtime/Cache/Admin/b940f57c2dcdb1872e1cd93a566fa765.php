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
				<label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>歌名：</label>
				<div class="formControls col-xs-8 col-sm-6">
					<input type="text" class="input-text" value="" placeholder="" name="name">
				</div>
			</div>
			<div class="row cl">
				<label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>干扰字：</label>
				<div class="formControls col-xs-8 col-sm-6">
					<input type="text" class="input-text" autocomplete="off" value="" placeholder="请输入大于21,小于30个字" name="option">
				</div>
			</div>
			<div class="row cl">
				<label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>前奏：</label>
				<div class="formControls col-xs-8 col-sm-6">
					<input type="file" class="input-text" autocomplete="off"  placeholder="" name="prelude">
				</div>
			</div>
			<div class="row cl">
				<label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>高潮：</label>
				<div class="formControls col-xs-8 col-sm-6">
					<input type="file" class="input-text" autocomplete="off"  placeholder="" name="climax">
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
				//required:true,
			},
            option:{
                //required:true,
                //minlength:21,
                //maxlength:30
            },
            prelude:{
                required:true,
            },
            climax:{
                //required:true,
            }

		},
		onkeyup:false,
		focusCleanup:true,
		success:"valid",
		submitHandler:function(form){
			$(form).ajaxSubmit({
				type: 'post',
				url: "<?php echo U('Admin/Song/song_do_add');?>" ,
				success: function(data){
				    console.log(data);
					/*layer.msg('添加成功!',{icon:1,time:1000});
					var index = parent.layer.getFrameIndex(window.name);
					parent.$('.btn-refresh').click();
					parent.layer.close(index);*/
				},
                error: function(XmlHttpRequest, textStatus, errorThrown){
					layer.msg('error!',{icon:1,time:1000});
				}
			});
		}
	});
});
</script> 
<!--/请在上方写此页面业务相关的脚本-->

</body>
</html>