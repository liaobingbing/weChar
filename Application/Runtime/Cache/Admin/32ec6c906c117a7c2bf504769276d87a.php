<?php if (!defined('THINK_PATH')) exit();?>﻿<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<meta name="renderer" content="webkit|ie-comp|ie-stand">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
<meta http-equiv="Cache-Control" content="no-siteapp" />
<!--[if lt IE 9]>
<script type="text/javascript" src="/Public/Admin/lib/html5shiv.js"></script>
<script type="text/javascript" src="/Public/Admin/lib/respond.min.js"></script>
<![endif]-->
<link href="/Public/Admin/static/h-ui/css/H-ui.min.css" rel="stylesheet" type="text/css" />
<link href="/Public/Admin/static/h-ui.admin/css/H-ui.login.css" rel="stylesheet" type="text/css" />
<link href="/Public/Admin/static/h-ui.admin/css/style.css" rel="stylesheet" type="text/css" />
<link href="/Public/Admin/lib/Hui-iconfont/1.0.8/iconfont.css" rel="stylesheet" type="text/css" />
<!--[if IE 6]>
<script type="text/javascript" src="/Public/Admin/lib/DD_belatedPNG_0.0.8a-min.js" ></script>
<script>DD_belatedPNG.fix('*');</script>
<![endif]-->
<title><?php echo ((isset($title) && ($title !== ""))?($title):'网页TITLE'); ?></title>
<meta name="keywords" content="">
<meta name="description" content="">
</head>
<body>
<input type="hidden" id="TenantId" name="TenantId" value="" />
<div class="header"></div>
<div class="loginWraper">
  <div id="loginform" class="loginBox">
    <form class="form form-horizontal" action="<?php echo U('Login/do_login');?>" method="post">
      <div class="row cl">
        <label class="form-label col-xs-3"><i class="Hui-iconfont">&#xe60d;</i></label>
        <div class="formControls col-xs-8">
          <input name="username" type="text" placeholder="账户" class="input-text size-L">
        </div>
      </div>
      <div class="row cl">
        <label class="form-label col-xs-3"><i class="Hui-iconfont">&#xe60e;</i></label>
        <div class="formControls col-xs-8">
          <input name="password" type="password" placeholder="密码" class="input-text size-L">
        </div>
      </div>
      <div class="row cl">
        <div class="formControls col-xs-8 col-xs-offset-3">
          <input name="code" class="input-text size-L" type="text" placeholder="验证码" style="width:180px;">
          <img id="code" src="<?php echo U('Admin/Login/verify');?>"  title="点击刷新" onclick="refresh_code()"/>
      </div>
      <div class="row cl">

      <div class="row cl">
        <div class="formControls col-xs-5 col-xs-offset-4" style="margin-top: 10px;align-content: center">
          <button type="submit" class="btn btn-success radius size-L">&nbsp;登&nbsp;&nbsp;&nbsp;&nbsp;录&nbsp;</button>
          <button type="reset" class="btn btn-default radius size-L">&nbsp;取&nbsp;&nbsp;&nbsp;&nbsp;消&nbsp;</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="footer">Copyright 你的公司名称 by </div>
<script type="text/javascript" src="/Public/Admin/lib/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="/Public/Admin/static/h-ui/js/H-ui.min.js"></script>
<script type="text/javascript" src="/Public/Admin/lib/layer/2.4/layer.js"></script>
<script>

  $(function(){

      //表单验证
      $("form").submit(function (event) {
          event.preventDefault(); // 禁止表单提交
          $.ajax({
              type: "POST",
              url: "<?php echo U('Admin/Login/do_login');?>",
              dataType: "JSON",
              data: $("form").serialize(),
              success: function (data) {
                  if(data.code == 400){
                      layer.msg(data.msg,{icon: 5,time:1000});
                      refresh_code();
                  }else if(data.code == 200){
                      layer.msg(data.msg,{icon: 1,time:1000});
                      window.location="<?php echo U('Admin/Index/index');?>";
                  }
              }
          });
      });

  });

  //验证码刷新
  function refresh_code(){
      var a = Math.random();
      $('#code').attr('src',"<?php echo U('Admin/Login/verify');?>");
  }



</script>

</body>
</html>