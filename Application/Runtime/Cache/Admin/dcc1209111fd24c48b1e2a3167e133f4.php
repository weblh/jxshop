<?php if (!defined('THINK_PATH')) exit();?><!-- $Id: category_info.htm 16752 2009-10-20 09:59:38Z wangleisvn $ -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>ECSHOP 管理中心 - 分类添加 </title>
<meta name="robots" content="noindex, nofollow">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="/Public/Admin/Styles/general.css" rel="stylesheet" type="text/css" />
<link href="/Public/Admin/Styles/main.css" rel="stylesheet" type="text/css" />
</head>
<body>
<h1>
    <span class="action-span"><a href="#">商品分类</a></span>
    <span class="action-span1"><a href="#">ECSHOP 管理中心</a></span>
    <span id="search_id" class="action-span1"> - 添加分类 </span>
    <div style="clear:both"></div>
</h1>
<div class="main-div">
    
<style type="text/css">
.main-div table {background: #BBDDE5;}
</style>
    <form action="" method="post" name="theForm" enctype="multipart/form-data">
       <div class="list-div">
            <table width="100%" cellpadding="3" cellspacing="1">
            <tbody>
                <tr>
                    <th colspan="4">订单信息</th>
                </tr>
                <tr>
                    <td align="right" width="18%">订单号:</td>
                    <td align="left" width="34%"><?php echo ($info["id"]); ?></td>
                    <td align="right" width="15%">订单金额:</td>
                    <td align="left"><?php echo ($info["total_price"]); ?></td>
                </tr>
                <tr>
                    <td align="right" width="18%">下单时间:</td>
                    <td align="left" width="34%"><?php echo (date('Y-m-d H:i:s',$info["addtime"])); ?></td>
                    <td align="right" width="15%">订单状态:</td>
                    <td align="left"><?php if(($info["pay_status"]) == "1"): ?>已支付<?php else: ?>未支付<?php endif; ?> <?php if(($info["order_status"]) == "2"): ?>已发货<?php else: ?>未发货<?php endif; ?></td>
                </tr>
                <tr>
                    <td align="right" width="18%">收货人:</td>
                    <td align="left" width="34%"><?php echo ($info["name"]); ?></td>
                    <td align="right" width="15%">收货地址:</td>
                    <td align="left"><?php echo ($info["address"]); ?></td>
                </tr>
                <tr>
                    <td align="right" width="18%">联系电话:</td>
                    <td align="left" width="34%"><?php echo ($info["tel"]); ?></td>
                    <td align="right" width="15%">下单人:</td>
                    <td align="left"><?php echo ($info["username"]); ?></td>
                </tr>
            </tbody>
            </table>
        </div>
        <div class="list-div">
            <table width="100%" cellpadding="3" cellspacing="1">
                <tr>
                    <th colspan="4">发货信息</th>
                </tr>
                <tr>
                    <td class="label">快递代号:</td>
                    <td>
                        <input type='text' name='com' value='<?php echo ($info["com"]); ?>'  /> 
                    </td>
                </tr>
                <tr>
                    <td class="label">快递单号:</td>
                    <td>
                        <input type='text' name='no'  value='<?php echo ($info["no"]); ?>'  /> 
                    </td>
                </tr>
            </table>
        </div>
        <div class="list-div">
            <table width="100%" cellpadding="3" cellspacing="1">
            <tbody>
                <tr>
                    <th>地图信息</th>
                </tr>
                <tr>
                    <td>
                        <div id="container" style="width:100%; height:500px"></div>
                    </td>
                    
                </tr>
            </tbody>
            </table>
        </div>
        <input type="hidden" name="id" value="<?php echo ($info["id"]); ?>">
        <div class="button-div">
            <input type="submit" value=" 确定 " />
            <input type="reset" value=" 重置 " />
        </div>
    </form>

</div>

<div id="footer">
共执行 3 个查询，用时 0.162348 秒，Gzip 已禁用，内存占用 2.266 MB<br />
版权所有 &copy; 2005-2012 上海商派网络科技有限公司，并保留所有权利。</div>

</body>
</html>
<script type="text/javascript" src="/Public/Admin/Js/jquery-1.8.3.min.js"></script>

<script charset="utf-8" src="http://map.qq.com/api/js?v=2.exp"></script>
<script>
function init() {
    var myLatlng = new qq.maps.LatLng(39.916527, 116.397128);
    var myOptions = {
        zoom: 8,//地图缩放比例
        //position:result.detail.location
    }
    var map = new qq.maps.Map(document.getElementById("container"), myOptions);
    map.panTo(myLatlng);
    var callbacks={
        //文档中results是错误的
        complete:function(result){
            map.setCenter(result.detail.location);
            var marker = new qq.maps.Marker({
                map:map,
                position: result.detail.location
            });
        },
    }
    //实例化地址解析类
    geocoder = new qq.maps.Geocoder(callbacks);
    //将具体的地名转换为经纬度
    geocoder.getLocation("<?php echo ($info["address"]); ?>");
}
init();
</script>