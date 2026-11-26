<?php
/**
 *  
 */
namespace app\api\model;

use app\common\VisitsCounter;
use core\basic\Model;

class DoModel extends Model
{

    // 新增访问（本地增量聚合，批量回写数据库）
    public function addVisits($id)
    {
        VisitsCounter::incr($id);
    }

    // 新增喜欢
    public function addLikes($id)
    {
        $data = array(
            'likes' => '+=1'
        );
        parent::table('ek_content')->where("id='$id'")->update($data);
    }

    // 新增喜欢
    public function addOppose($id)
    {
        $data = array(
            'oppose' => '+=1'
        );
        parent::table('ek_content')->where("id='$id'")->update($data);
    }
}