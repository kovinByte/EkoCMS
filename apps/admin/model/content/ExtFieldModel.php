<?php
/**
 *  扩展字段模型类
 */
namespace app\admin\model\content;

use core\basic\Model;

class ExtFieldModel extends Model
{

    // 获取扩展字段列表
    public function getList()
    {
        return parent::table('ek_extfield')->order('mcode asc,sorting asc,id asc')
            ->page()
            ->select();
    }

    // 查找扩展字段
    public function findExtField($field, $keyword)
    {
        return parent::table('ek_extfield')->like($field, $keyword)
            ->order('mcode asc,sorting asc,id asc')
            ->page()
            ->select();
    }

    // 检查扩展字段
    public function checkExtField($name)
    {
        return parent::table('ek_extfield')->where("name='$name'")->find();
    }

    // 获取模型字段
    public function getModelField($mcode)
    {
        return parent::table('ek_extfield')->where("mcode='$mcode'")
            ->order('sorting asc,id asc')
            ->select();
    }

    // 扩展字段名 => 类型（8=编辑器）
    public function getTypeMap()
    {
        return parent::table('ek_extfield')->column('type', 'name');
    }

    // 获取扩展字段详情
    public function getExtField($id)
    {
        return parent::table('ek_extfield')->where("id=$id")->find();
    }

    // 获取扩展字段名称
    public function getExtFieldName($id)
    {
        return parent::table('ek_extfield')->where("id=$id")->value('name');
    }

    // 添加扩展字段
    public function addExtField(array $data)
    {
        return parent::table('ek_extfield')->insert($data);
    }

    // 删除扩展字段
    public function delExtField($id)
    {
        return parent::table('ek_extfield')->where("id=$id")->delete();
    }

    // 修改扩展字段
    public function modExtField($id, $data)
    {
        return parent::table('ek_extfield')->where("id=$id")->update($data);
    }

    // 判断字段是否存在
    public function isExistField($field)
    {
        $fields = parent::tableFields('ek_content_ext');
        if (in_array($field, $fields)) {
            return true;
        } else {
            return false;
        }
    }

    //查询content_ext的索引
    public function checkExtIndex(): array
    {
        return parent::table('ek_content_ext')->checkIndexSql();
    }
}