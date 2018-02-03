<style type="text/css">
    .small_gray {
        color: #757575;
        font-size: 12px;
    }
</style>
<h2>循环封禁</h2>
<p class="small_gray">当前插件版本：1.3.0 | 更新日期：17-07-24 | Designed By <a href="http://jerrys.me" target="_blank">@JerryLocke</a> | Optimized by <a href="http://gakuen.me" target="_blank">Gakuen</a> | 交流群：<a target="_blank" href="http://shang.qq.com/wpa/qunwpa?idkey=ba20b2535872bd9ede8fc11e5b5badf42a4b992b0069ba1621e182ef5defc4dd">187751253</a></p>
<p class="small_gray">本插件可以每天对指定贴吧的指定ID进行封禁操作，前提是您有吧主权限。</p>

<br/>
<h2>设置</h2>
<form method="post" action="plugin.php?id=zw_blockid&action=setting" id="zw_blockid-setting" onsubmit="return post_win(this.action, this.id, zw_blockid_load_set)">
    <p><label><input type="checkbox" id="zw_blockid-report" name="zw_blockid-report" value="1" /> 当天有封禁失败的记录时发邮件告知我</label></p>
    <input type="submit" value="保存设置"></form>
</form>


<br/><hr/>
<h2>封禁测试</h2>
<p>从列表随机选取一条，进行一次封禁测试，检查你的设置有没有问题</p>
<p><a href="javascript:msg_win_action('plugin.php?id=zw_blockid&action=test-blockid');" class="btn">测试封禁</a></p>

<br/><hr/>
<h2>封禁列表</h2>
<p>以下ID系统将会每天自动封禁：</p>
<table>
	<thead>
		<tr>
			<td style="width: 5%">#</td>
			<td>所在贴吧</td>
			<td>封禁ID</td>
			<td style="width: 20%">操作</td>
		</tr>
	</thead>
	<tbody id="zw_blockid-list"></tbody>
</table>
<p>
	<a class="btn" href="javascript:;" id="zw_blockid-add">添加封禁</a>
	<a class="btn" href="javascript:;" id="zw_blockid-add-batch">批量添加</a>
	<a class="btn" href="javascript:;" id="zw_blockid-del-all">全部删除</a>
</p>

<br/><hr/>
<h2 id='zw_blockid-history'>封禁记录</h2>
<span id="zw_blockid-log-flip" class="float-right"><a href="javascript:zw_blockid_show_log();">« 前一天</a></span>
<p id="zw_blockid-log-stat">共要封禁 0 个ID, 成功封禁 0 个ID</p>
<table>
	<thead>
		<tr>
			<td style="width: 5%">#</td>
			<td>所在贴吧</td>
			<td>封禁ID</td>
			<td style="width: 20%">封禁情况</td>
		</tr>
	</thead>
	<tbody id="zw_blockid-log"></tbody>
</table>