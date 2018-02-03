$("#menu_zw_blockid-index").click(function (){zw_blockid_load_set();})

$("#zw_blockid-add").click(function(){
	createWindow().setTitle("添加记录").setContent('<form method="get" action="plugin.php?id=zw_blockid&action=add-id" id="add-id" onsubmit="return post_win(this.action, this.id, zw_blockid_load_set)"><p>请输入贴吧名（必须拥有该贴吧的吧主权限）:<input type="text" id="tb_name" name="tb_name" style="width:100%"/></p><p>请输入用户名:<input type="text" name="user_name" style="width:100%"/></p></form>').addButton("确定", function(){ $('#add-id').submit(); }).addCloseButton("取消").append();
	});

$("#zw_blockid-add-batch").click(function(){
	createWindow().setTitle("批量添加").setContent('<form method="get" action="plugin.php?id=zw_blockid&action=add-id-batch" id="add_id_batch" onsubmit="return post_win(this.action, this.id, zw_blockid_load_set)"><p>请输入贴吧名（必须拥有该贴吧的吧主权限）:<input type="text" id="tb_name" name="tb_name" style="width:100%"/></p><p>请输入用户名（一行一个）:<textarea  id="user_name" name="user_name" style="width:100%"/></textarea></p></form>').addButton("确定", function(){ $('#add_id_batch').submit(); }).addCloseButton("取消").append();
	});

$("#zw_blockid-del-all").click(function(){
	createWindow().setTitle("取消封禁").setContent('你确定要取消全部ID的自动封禁吗？').addButton("确定", function(){msg_callback_action('plugin.php?id=zw_blockid&action=del-all',zw_blockid_load_set);}).addCloseButton("取消").append();
});	

function zw_blockid_load_set(){
	showloading();
	$.getJSON("plugin.php?id=zw_blockid&action=get-list", function(result){
		zw_blockid_show_set(result);
	}).fail(function() { createWindow().setTitle('系统错误').setContent('发生未知错误: 无法获取设置').addButton('确定', function(){ location.reload(); }).append(); }).always(function(){ hideloading(); });
}

function zw_blockid_show_set(result){
	var status="";
	$('#zw_blockid-list').html('');
	$('#zw_blockid-log').html('');
	$.each(result.list, function(i, field){
		$("#zw_blockid-list").append("<tr><td>"+(i+1)+"</td><td><a href=\"http://tieba.baidu.com/"+field.tieba+"\" target=\"_blank\">"+field.tieba+"</a></td><td><a href=\"http://www.baidu.com/p/"+field.blockid+"\" target=\"_blank\">"+field.blockid+"</a></td><td><a href=\"javascript:;\" onclick=\"return zw_blockid_del_id("+field.id+")\">删除</a></td></tr>");
	})
    zw_blockid_show_log(result.today);
	$('#zw_blockid-report').attr('checked', result.sendmail == "1");
	$('#zw_blockid-report').click(function(){msg_callback_action("plugin.php?id=zw_blockid&action=send-mail&switch="+(result.sendmail == "1")?0:1,zw_blockid_load_set);});
	;}

function zw_blockid_show_log(date){
	showloading();
	$.getJSON("plugin.php?id=zw_blockid&action=get-log&date="+parseInt(date), function(result){
	$('#zw_blockid-log').html('');
	var zw_blockid_fliptext = '';
	if(result.before_date){ zw_blockid_fliptext = zw_blockid_fliptext + '<a href="javascript:zw_blockid_show_log('+result.before_date+');">« 前一天</a>&nbsp;&nbsp;';}
    if(result.after_date){ zw_blockid_fliptext = zw_blockid_fliptext + '<a href="javascript:zw_blockid_show_log('+result.today+');">今天</a>&nbsp;&nbsp;<a href="javascript:zw_blockid_show_log('+result.after_date+');">后一天 »</a>';}
	$('#zw_blockid-history').html(result.date+" 封禁记录");
	$('#zw_blockid-log-flip').html(zw_blockid_fliptext);
	$('#zw_blockid-log-stat').html('共要封禁 '+parseInt(result.log_count)+' 个ID, 成功封禁 '+parseInt(result.log_success_status)+' 个ID');
    $.each(result.log, function(i, field){
    status=field.status==1?"成功":("失败 （"+"<a href='javascript:;' onclick=\"return msg_callback_action('plugin.php?id=zw_blockid&action=do-blockid&fid="+field.fid+"&user_name="+encodeURIComponent(field.blockid)+"&tieba="+encodeURIComponent(field.tieba)+"&bid="+field.id+"',zw_blockid_load_set) \">手动封禁</a>）");
	$("#zw_blockid-log").append("<tr><td>"+(i+1)+"</td><td><a href=\"http://tieba.baidu.com/"+field.tieba+"\" target=\"_blank\">"+field.tieba+"</a></td><td><a href=\"http://www.baidu.com/p/"+field.blockid+"\" target=\"_blank\">"+field.blockid+"</a></td><td>"+status+"</td></tr>");
	})
	}).fail(function() { createWindow().setTitle('系统错误').setContent('发生未知错误: 无法获取封禁记录').addButton('确定', function(){ location.reload(); }).append(); }).always(function(){ hideloading(); });
}

function zw_blockid_del_id(no){
	createWindow().setTitle('取消封禁').setContent('确认要取消这个ID的循环封禁吗？').addButton('确定', function(){ msg_callback_action("plugin.php?id=zw_blockid&action=del-blockid&no="+no,zw_blockid_load_set); }).addCloseButton('取消').append();
	return false;
}