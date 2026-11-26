<?php
/**
 *  公司信息模型类
 */
namespace app\admin\model\content;

use core\basic\Model;

class CompanyModel extends Model
{

    // 获取公司信息
    public function getList()
    {
        return parent::table('ek_company')->where("acode='" . session('acode') . "'")->find();
    }

    // 检查公司信息
    public function checkCompany()
    {
        return parent::table('ek_company')->where("acode='" . session('acode') . "'")->find();
    }

    // 增加公司信息
    public function addCompany($data)
    {
        return parent::table('ek_company')->insert($data);
    }

    // 修改公司信息
    public function modCompany($data)
    {
        return parent::table('ek_company')->where("acode='" . session('acode') . "'")->update($data);
    }

    public function getImage()
    {
        return parent::table('ek_company')->column('weixin');
    }
}