<?php
/**
 *  日志模型类
 */
namespace app\admin\model\system;

use core\basic\Model;

class SyslogModel extends Model
{

    // 获取日志列表
    public function getList()
    {
        return parent::table('ek_syslog')->order('id DESC')
            ->page()
            ->select();
    }

    // 删除全部
    public function clearLog()
    {
        return parent::table('ek_syslog')->delete();
    }
}
