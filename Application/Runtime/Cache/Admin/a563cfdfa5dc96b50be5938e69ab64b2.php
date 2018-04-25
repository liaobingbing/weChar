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




    <nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 小程序管理 <span class="c-gray en">&gt;</span> 小程序列表 <a class="btn btn-success radius r" id="refresh" style="line-height:1.6em;margin-top:3px" onclick="refresh()" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
    <div class="page-container">
        <div class="text-c">
            <form action="" method="get" onkeydown="javascript:return no_enter();">
                <button onclick="removeIframe()" class="btn btn-primary radius">关闭选项卡</button>
                <input id="key" type="text" name="key" placeholder="搜歌名" value="<?php echo ($key); ?>" style="width:250px" class="input-text">
                <button  class="btn btn-success" type="submit"><i class="Hui-iconfont">&#xe665;</i> 搜小程序</button>
            </form>
        </div>
        <div class="cl pd-5 bg-1 bk-gray mt-20">
            <span class="l">
                <a class="btn btn-primary radius" onclick="article_add('添加小程序','<?php echo U('Admin/App/app_add');?>',200,100)" href="javascript:;"><i class="Hui-iconfont">&#xe600;</i> 添加小程序</a>
            </span> <span class="r">共有数据：<strong><?php echo ($count); ?></strong> 条</span>
        </div>
        <div class="mt-20">
            <table class="table table-border table-bordered table-bg table-hover table-sort table-responsive">
                <thead>
                <tr class="text-c">
                    <th width="25"><input type="checkbox" name="" value=""></th>
                    <th width="80">ID</th>
                    <th>LOGO</th>
                    <th>小程序名称</th>
                    <th>描述</th>
                    <th>appid</th>
                    <th>排序</th>
                    <th>类型</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr class="text-c">
                    <td><input type="checkbox" value="" name=""></td>
                    <td><?php echo ($vo["id"]); ?></td>
                    <td><img src="<?php echo ($vo["logo"]); ?>" width="50px" height="50px"></td>
                    <td><?php echo ($vo["name"]); ?></td>
                    <td><?php echo ($vo["desc"]); ?></td>
                    <td><?php echo ($vo["appid"]); ?></td>
                    <td><?php echo ($vo["sort"]); ?></td>
                    <td>
                        <?php if(is_array($type)): $i = 0; $__LIST__ = $type;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$t_vo): $mod = ($i % 2 );++$i; if(($vo["type"]) == $t_vo["id"]): echo ($t_vo["name"]); endif; endforeach; endif; else: echo "" ;endif; ?></else>
                    </td>
                    <?php if(($vo["status"]) == "1"): ?><td class="user-status"><span class="label label-success radius">显示</span></td>
                        <?php else: ?>
                        <td class="user-status"><span class="label label-defaunt radius">隐藏</span></td><?php endif; ?>
                    <td class="f-14 td-manage">
                        <?php if(($vo["status"]) == "1"): ?><a style="text-decoration:none" onClick="article_stop(this,<?php echo ($vo["id"]); ?>)" href="javascript:;" title="隐藏"><i class="Hui-iconfont">&#xe6de;</i></a>
                        <?php else: ?>
                            <a style="text-decoration:none" onClick="article_start(this,<?php echo ($vo["id"]); ?>)" href="javascript:;" title="显示"><i class="Hui-iconfont">&#xe603;</i></a><?php endif; ?>

                        <a style="text-decoration:none" class="ml-5" onClick="article_edit('资讯编辑','/Admin/App/app_edit/id/<?php echo ($vo["id"]); ?>')" href="javascript:;" title="编辑"><i class="Hui-iconfont">&#xe6df;</i></a>
                        
                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                </tbody>
            </table>
        </div>
        <div class="page_style"><?php echo ($page); ?></div>
    </div>


<script type="text/javascript" src="/Public/Admin/lib/jquery/1.9.1/jquery.min.js"></script> 
<script type="text/javascript" src="/Public/Admin/lib/layer/2.4/layer.js"></script>
<script type="text/javascript" src="/Public/Admin/static/h-ui/js/H-ui.min.js"></script>
<script type="text/javascript" src="/Public/Admin/static/h-ui.admin/js/H-ui.admin.js"></script> 



    <script type="text/javascript" src="/Public/Admin/lib/My97DatePicker/4.8/WdatePicker.js"></script>
    <script type="text/javascript" src="/Public/Admin/lib/datatables/1.10.0/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="/Public/Admin/lib/laypage/1.2/laypage.js"></script>
    <script type="text/javascript">


        /*资讯-添加*/
        function article_add(title,url,w,h){
            var index = layer.open({
                type: 2,
                title: title,
                content: url
            });
            layer.full(index);
        }
        /*资讯-编辑*/
        function article_edit(title,url,id,w,h){
            var index = layer.open({
                type: 2,
                title: title,
                data:{
                    id : 1
                },
                content: url
            });
            layer.full(index);
        }
        /*资讯-删除*/
        function article_del(obj,id){
            layer.confirm('确认要删除吗？',function(index){
                $.ajax({
                    type: 'POST',
                    url: '',
                    dataType: 'json',
                    success: function(data){
                        $(obj).parents("tr").remove();
                        layer.msg('已删除!',{icon:1,time:1000});
                    },
                    error:function(data) {
                        console.log(data.msg);
                    },
                });
            });
        }


        /*资讯-下架*/
        function article_stop(obj,id){
            layer.confirm('确认要隐藏吗？',function(index){
                $.ajax({
                    type: 'POST',
                    url: "<?php echo U('Admin/App/do_status');?>",
                    dataType: 'json',
                    data:{
                      'id' : id
                    },
                    success: function(data){
                         $(obj).parents("tr").find(".td-manage").prepend('<a style="text-decoration:none" onClick="article_start(this,'+id+')" href="javascript:;" title="发布"><i class="Hui-iconfont">&#xe603;</i></a>');
                $(obj).parents("tr").find(".user-status").html('<span class="label label-defaunt radius">隐藏</span>');
                $(obj).remove();
                layer.msg('已隐藏!',{icon: 5,time:1000});
                    }
                });
               /* $(obj).parents("tr").find(".td-manage").prepend('<a style="text-decoration:none" onClick="article_start(this,id)" href="javascript:;" title="发布"><i class="Hui-iconfont">&#xe603;</i></a>');
                $(obj).parents("tr").find(".user-status").html('<span class="label label-defaunt radius">隐藏</span>');
                $(obj).remove();
                layer.msg('已隐藏!',{icon: 5,time:1000});*/
            });
        }

        /*资讯-发布*/
        function article_start(obj,id){
            layer.confirm('确认要显示吗？',function(index){
                $.ajax({
                    type: 'POST',
                    url: "<?php echo U('Admin/App/do_status');?>",
                    dataType: 'json',
                    data:{
                        'id' : id
                    },
                    success: function(data){
                        $(obj).parents("tr").find(".td-manage").prepend('<a style="text-decoration:none" onClick="article_stop(this,'+id+')" href="javascript:;" title="下架"><i class="Hui-iconfont">&#xe6de;</i></a>');
                        $(obj).parents("tr").find(".user-status").html('<span class="label label-success radius">显示</span>');
                        $(obj).remove();
                        layer.msg('已显示!',{icon: 6,time:1000});
                    }
                });

            });
        }


        // 禁止回车按钮
        function no_enter(){
            if(window.event.keyCode == 13)
            {
                return false;
            }
        }

        // 刷新本页面
        function refresh(){
            location.replace(location.href)
        }



    </script>

</body>
</html>