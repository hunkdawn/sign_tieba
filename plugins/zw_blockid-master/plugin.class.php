<?php
if (!defined('IN_KKFRAME')) exit('Access Denied!');
class plugin_zw_blockid extends Plugin {
    public $name = 'zw_blockid';
    public $description = '本插件可以给网站用户提供循环封禁用户功能';
    public $modules = array(
        array(
            'id' => 'index',
            'type' => 'page',
            'title' => '循环封禁',
            'file' => 'index.inc.php'
        ),
        array(
            'type' => 'cron',
            'cron' => array(
                'id' => 'zw_blockid/daily',
                'order' => 30
            )
        ),
        array(
            'type' => 'cron',
            'cron' => array(
                'id' => 'zw_blockid/blockid',
                'order' => 31
            )
        ),
        array(
            'type' => 'cron',
            'cron' => array(
                'id' => 'zw_blockid/mail',
                'order' => 32
            )
        )
    );
    public $version = '1.3.0';
    function checkCompatibility() {
        if (version_compare(VERSION, '1.14.6.4', '<')) showmessage('签到助手版本过低，请升级');
    }
    function page_footer_js() {
        echo '<script src="plugins/zw_blockid/zw_blockid.js"></script>';
    }
    function install() {
        runquery("
            CREATE TABLE `zw_blockid_list` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `uid` int(10) unsigned NOT NULL,
                `fid` int(10) unsigned NOT NULL,
                `blockid` varchar(20) NOT NULL,
                `tieba` varchar(200) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uid` (`uid`,`fid`,`blockid`,`tieba`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

            CREATE TABLE `zw_blockid_log` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `uid` int(10) unsigned NOT NULL,
                `fid` int(8) NOT NULL,
                `tieba` varchar(200) NOT NULL,
                `blockid` varchar(100) NOT NULL,
                `date` int(11) NOT NULL DEFAULT '20131201',
                `status` tinyint(4) NOT NULL DEFAULT '0',
                `retry` int(1) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
    function uninstall() {
        runquery("
            DROP TABLE `zw_blockid_list`;
            DROP TABLE `zw_blockid_log`;
            DELETE FROM `plugin_var` WHERE `pluginid`='zw_blockid';
        ");
    }
    function on_upgrade($from_version) {
        switch ($from_version) {
            case '0':
                runquery("DELETE FROM  `setting` WHERE  `k` LIKE  'zw_blockid%';");
                return '1.2.0';
            case '1.2.0':
                return '1.2.4';
            case '1.2.4':
                runquery("
                    UPDATE cron SET id='zw_blockid/cron/zw_blockid' WHERE id='zw_blockid';
                    UPDATE cron SET id='zw_blockid/cron/zw_blockid_daily' WHERE id='zw_blockid_daily';
                    UPDATE cron SET id='zw_blockid/cron/zw_blockid_mail' WHERE id='zw_blockid_mail';
                ");
                return '1.2.5';
            case '1.2.5':
                 runquery("
                    UPDATE cron SET id='zw_blockid/cron_blockid' WHERE id='zw_blockid' OR id='zw_blockid/cron/zw_blockid';
			        UPDATE cron SET id='zw_blockid/cron_daily' WHERE id='zw_blockid_daily' OR id='zw_blockid/cron/zw_blockid_daily';
			        UPDATE cron SET id='zw_blockid/cron_mail' WHERE id='zw_blockid_mail' OR id='zw_blockid/cron/zw_blockid_mail';
                ");
                return '1.2.6';
            case '1.2.6':
                runquery("
                    UPDATE cron SET id='zw_blockid/blockid' WHERE id='zw_blockid/cron_blockid';
			        UPDATE cron SET id='zw_blockid/daily' WHERE id='zw_blockid/cron_daily';
			        UPDATE cron SET id='zw_blockid/mail' WHERE id='zw_blockid/cron_mail';
                ");
                return '1.2.8';
            case '1.2.8':
                runquery("
                    CREATE TABLE IF NOT exists `zw_blockid_list_tmp` (
                        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                        `uid` int(10) unsigned NOT NULL,
                        `fid` int(10) unsigned NOT NULL,
                        `blockid` varchar(20) NOT NULL,
                        `tieba` varchar(200) NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `uid` (`uid`,`fid`,`blockid`,`tieba`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    INSERT INTO `zw_blockid_list_tmp`(uid, fid, blockid, tieba) SELECT DISTINCT uid, fid, blockid, tieba FROM `zw_blockid_list`;
                    DELETE FROM `zw_blockid_list`;
                    INSERT INTO `zw_blockid_list`(uid, fid, blockid, tieba) SELECT DISTINCT uid, fid, blockid, tieba FROM `zw_blockid_list_tmp`;
                    DROP TABLE `zw_blockid_list_tmp`;
                    ALTER TABLE `zw_blockid_list` ADD UNIQUE (`uid`,`fid`,`blockid`,`tieba`);
                ");
                return '1.2.9';
            case '1.2.9':
                return '1.3.0';
        }
    }
    function handleAction() {
        global $uid;
        if (!$uid) return;
        $data = array();
        switch ($_GET['action']) {
            case 'add-id':
                $tb_name = trim($_POST['tb_name']);
                $user_name = trim($_POST['user_name']);
                $contents = _get_redirect_data('http://tieba.baidu.com/f?kw=' . urlencode($tb_name) . '&fr=index');
                $fid = 0;
                preg_match('/"forum_id"\s?:\s?(?<fid>\d+)/', $contents, $fids);
                $fid = $fids['fid'];
                if (empty($fid)) {
                    $data['msg'] = "添加失败，无法获取<b>{$tb_name}</b>贴吧<b>FID</b>，请检查贴吧名称并重试";
                    break;
                }
                if ($result = DB::result_first("SELECT * FROM zw_blockid_list WHERE fid={$fid} AND blockid='{$user_name}' AND tieba='{$tb_name}'")) {
                    $data['msg'] = '添加失败，在您或其它账户下已有该用户封禁记录！';
                } else {
                    DB::insert('zw_blockid_list', array(
                        'uid' => $uid,
                        'fid' => $fid,
                        'blockid' => $user_name,
                        'tieba' => $tb_name,
                    ));
                    $re = $this->blockid($uid, $user_name, $tb_name, $fid, 1);
                    $str = '';
                    switch ($re['error_code']) {
                        case 0:
                            $result = '封禁成功，<a href="http://tieba.baidu.com/bawu2/platform/listFilterUser?word=' . urlencode($tb_name) . '&fr=home" target="_blank">点击查看</a>';
                            break;
                        case -1:
                            $data['msg'] = "封禁失败，返回数据错误！";
                            break;
                        default:
                            $result = '封禁失败，已添加到封禁列表！';
                            $str = "<p>错误代码：{$re['error_code']}</p><p>详细信息：{$re['error_msg']}</p>";
                    }
                    $data['msg'] = "<p>执行贴吧：<a href='https://tieba.baidu.com/f?kw=" . urlencode($tb_name) . "&fr=index' target='_blank'>{$tb_name}</a></p><p>封禁用户：<a href='http://tieba.baidu.com/home/main?un=" . urlencode($user_name) . "&fr=home' target='_blank'>{$user_name}</a></p><p>执行结果：{$result}</p>{$str}";
                }
                break;

            case 'add-id-batch':
                $tb_name = trim($_POST['tb_name']);
                $users = explode("\n", trim($_POST['user_name']));
                for ($i = 0; $i < count($users); $i++) {
                    $users_name[$i] = trim($users[$i]);
                }
                $users_name = array_filter($users_name);
                if (!is_array($users_name)) {
                    $data['msg'] = '添加失败：格式错误，多个ID请用换行分隔！';
                    break;
                }
                $contents = _get_redirect_data('http://tieba.baidu.com/f?kw=' . urlencode($tb_name) . '&fr=index');
                $fid = 0;
                preg_match('/"forum_id"\s?:\s?(?<fid>\d+)/', $contents, $fids);
                $fid = $fids['fid'];
                if (empty($fid)) {
                    $data['msg'] = "添加失败，无法获取<b>{$tb_name}</b>贴吧<b>FID</b>，请检查贴吧名称并重试";
                    break;
                }
                $count = 0;
                foreach ($users_name as $user_name) {
                    if (DB::insert('zw_blockid_list', array(
                        'uid' => $uid,
                        'fid' => $fid,
                        'blockid' => $user_name,
                        'tieba' => $tb_name
                    ), true)) ++$count;
                }
                $data['msg'] = "<p>所在贴吧：<a href='https://tieba.baidu.com/f?kw=" . urlencode($tb_name) . "&fr=index' target='_blank'>{$tb_name}</a></p><p>添加封禁： <b>{$count}</b> 个用户";
                break;

            case 'get-list':
                $data['list'] = array();
                $query = DB::query("SELECT * FROM zw_blockid_list WHERE uid={$uid}");
                while ($result = DB::fetch($query)) {
                    $data['list'][] = $result;
                }
                $data['today'] = date('Ymd');
                $sendmail_uid = array_filter(explode(',', $this->getSetting('sendmail_uid')));
                $data['sendmail'] = in_array($uid, $sendmail_uid) ? 1 : 0;
                break;

            case 'get-log':
                $date = intval($_GET['date']);
                $data['log'] = array();
                $data['today'] = date('Ymd');
                $data['date'] = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
                $data['log_success_status'] = 0;
                $query = DB::query("SELECT * FROM zw_blockid_log WHERE uid={$uid} AND date={$date}");
                while ($result = DB::fetch($query)) {
                    if ($result['status'] == 1) $data['log_success_status']++;
                    $data['log'][] = $result;
                }
                $data['log_count'] = count($data['log']);
                $data['before_date'] = DB::result_first("SELECT date FROM zw_blockid_log WHERE uid={$uid} AND date<{$date} ORDER BY date DESC LIMIT 0,1");
                $data['after_date'] = DB::result_first("SELECT date FROM zw_blockid_log WHERE uid={$uid} AND date>{$date} ORDER BY date LIMIT 0,1");
                break;

            case 'del-blockid':
                $no = intval($_GET['no']);
                DB::query("DELETE FROM zw_blockid_list WHERE id={$no} AND uid={$uid}");
                $data['msg'] = "删除成功！";
                break;

            case 'do-blockid':
                $fid = (int)$_GET['fid'];
                $user_name = urldecode(trim($_GET['user_name']));
                $tieba = urldecode(trim($_GET['tieba']));
                $re = $this->blockid($uid, $user_name, $tieba, $fid, 1);
                $id = (int)$_GET['bid'];
                $str = '';
                switch ($re['error_code']) {
                    case 0:
                        $result = '封禁成功，<a href="http://tieba.baidu.com/bawu2/platform/listFilterUser?word=' . urlencode($tieba) . '&fr=home" target="_blank">点击查看</a>';
                        DB::query("UPDATE zw_blockid_log SET status=1 WHERE id={$id} AND uid={$uid}");
                        break;
                    case -1:
                        $data['msg'] = "封禁失败，返回数据错误！";
                        break;
                    default:
                        $result = '封禁失败！';
                        $str = "<p>错误代码：{$re['error_code']}</p><p>详细信息：{$re['error_msg']}</p>";
                }
                $data['msg'] = "<p>执行贴吧：<a href='https://tieba.baidu.com/f?kw=" . urlencode($tieba) . "&fr=index' target='_blank'>{$tieba}</a></p><p>封禁账号：<a href='http://tieba.baidu.com/home/main?un=" . urlencode($user_name) . "&fr=home' target='_blank'>{$user_name}</a></p><p>执行结果：{$result}</p>{$str}";
                break;

            case 'del-all':
                DB::query("DELETE FROM zw_blockid_list WHERE uid='{$uid}'");
                $data['msg'] = "删除成功！";
                break;

            case 'test-blockid':
                $query = DB::query("SELECT * FROM zw_blockid_list WHERE uid='{$uid}'");
                while ($result = DB::fetch($query)) {
                    $blockid_list[] = $result;
                }
                if (empty($blockid_list)) {
                    $data['msg'] = "没有封禁信息，请先添加！";
                    break;
                }
                $rand = mt_rand(0, count($blockid_list) - 1);
                $test_blockid = $blockid_list[$rand];
                $re = $this->blockid($uid, $test_blockid['blockid'], $test_blockid['tieba'], $test_blockid['fid'], 1);
                $str = '';
                switch ($re['error_code']) {
                    case 0:
                        $result = '封禁成功，<a href="http://tieba.baidu.com/bawu2/platform/listFilterUser?word=' . urlencode($test_blockid['tieba']) . '&fr=home" target="_blank">点击查看</a>';
                        break;
                    case -1:
                        $data['msg'] = "封禁失败，返回数据错误！";
                        break;
                    default:
                        $result = '封禁失败，已添加到封禁列表！';
                        $str = "<p>错误代码：{$re['error_code']}</p><p>详细信息：{$re['error_msg']}</p>";
                }
                $data['msg'] = "<p>执行贴吧：<a href='https://tieba.baidu.com/f?kw=" . urlencode($test_blockid['tieba']) . "&fr=index' target='_blank'>{$test_blockid['tieba']}</a></p><p>封禁用户：<a href='http://tieba.baidu.com/home/main?un=" . urlencode($test_blockid['blockid']) . "&fr=home' target='_blank'>{$test_blockid['blockid']}</a></p><p>执行结果：{$result}</p>{$str}";
                break;

            case 'setting':
                if ((int)$_POST['zw_blockid-report'] === 1) {
                    $sendmail_uid = array_filter(explode(',', $this->getSetting('sendmail_uid')));
                    if (!in_array($uid, $sendmail_uid)) $sendmail_uid[] = $uid;
                    $this->saveSetting('sendmail_uid', implode(',', $sendmail_uid));
                    $data['msg'] = "成功开启邮件报告！";
                } else {
                    $sendmail_uid = array_filter(explode(',', $this->getSetting('sendmail_uid')));
                    if (in_array($uid, $sendmail_uid)) {
                        for ($i = 0; $i < count($sendmail_uid); $i++) {
                            if ($sendmail_uid[$i] == $uid) unset($sendmail_uid[$i]);
                        }
                        $this->saveSetting('sendmail_uid', implode(',', $sendmail_uid));
                    }
                    $data['msg'] = "已关闭邮件报告！";
                }
                break;

            default:
                $data['msg'] = "没有指定action！";
                break;
        }
        echo json_encode($data);
    }
    function blockid($uid, $user_name, $tb_name, $fid, $day) {
        $cookie = get_cookie($uid);
        preg_match('/BDUSS=([^ ;]+);/i', $cookie, $matches);
        $bduss = trim($matches[1]);
        if (empty($bduss)) return array(-1, '找不到 BDUSS Cookie');

        $header = array(
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: bdtb for Android 8.6.8.0'
        );
        $formdata = array(
            'BDUSS' => $bduss,
            '_client_id' => 'wappc_1500' . random(9, true) . '_' . random(3, true),
            '_client_type' => '2', //2=Android
            '_client_version' => '8.6.8.0',
            '_phone_imei' => '000000000000000',
            'day' => $day,
            'fid' => $fid,
            'model' => 'MI 6',
            'ntn' => 'banid',
            'reason' => '抱歉，你的发贴操作或发表贴子的内容违反了本吧的吧规，已经被封禁，封禁期间不能在本吧继续发言。',
            'tbs' => get_tbs($uid),
            'timestamp' => TIMESTAMP . '000',
            'un' => $user_name,
            'word' => $tb_name,
            'z' => '66'
        );

        $adddata = '';
        foreach ($formdata as $k => $v) $adddata .= $k . '=' . $v;
        $sign = strtoupper(md5($adddata . 'tiebaclient!!!'));
        $formdata['sign'] = $sign;

        $ch = curl_init('http://c.tieba.baidu.com/c/c/bawu/commitprison');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formdata));
        $re = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (empty($re)) {
            return array(
                'errno' => -1,
                'errmsg' => '提交数据失败！'
            );
        } else {
            return $re;
        }
    }
}
