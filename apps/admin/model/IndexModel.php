<?php
/**
 *  首页模型类
 */
namespace app\admin\model;

use core\basic\Model;

class IndexModel extends Model
{

    // 检查用户账号密码
    public function login($where)
    {
        // 执行登录
        $result = parent::table('ek_user')->field('id,ucode,username,realname')
            ->where($where)
            ->where('status=1')
            ->find();
        
        if ($result) { // 登录成功
            $this->ensureAutoPublishMenu($result->username); // 旧库升级：确保定时发布菜单存在
            // $this->ensureSeoMenu($result->username); // 旧库升级：确保SEO配置菜单存在
            $this->updateLogin($where); // 执行更新登录记录
            $menus = $this->getUserMenu($result->ucode); // 用户菜单
            $result->menus = get_tree($menus, 0, 'mcode', 'pcode'); // 用户菜单树
            $result->rcodes = $this->getUserRcode($result->ucode); // 用户角色
            $result->levels = $this->getUserLevel($result->ucode); // 用户权限
            
            $acodes = $this->getUserAcode($result->ucode); // 获取用户区域
            $areas = $this->getAreas();
            $first_acode = $areas[0]->acode;
            if (in_array($first_acode, $acodes)) {
                array_unshift($acodes, $first_acode);
                $acodes = array_unique($acodes);
            }
            $result->acodes = $acodes;
            $result->area_map = get_mapping($areas, 'name', 'acode'); // 区域代码名称映射表
            $result->area_tree = $this->getUserAreaTree($areas, 0, 'acode', 'pcode', 'son', $result->acodes); // 当前用户的区域树
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 确保「扩展内容 - 定时发布」菜单及对应权限存在（幂等，用于旧版本数据库自动升级）
     */
    private function ensureAutoPublishMenu($username)
    {
        try {
            if (parent::table('ek_menu')->where("url='/admin/AutoPublish/index'")->find()) {
                return;
            }

            // 父级菜单「扩展内容」必须存在
            if (! parent::table('ek_menu')->where("mcode='M157'")->find()) {
                return;
            }

            // 生成不冲突的菜单编码，优先使用M162
            $mcode = 'M162';
            if (parent::table('ek_menu')->where("mcode='$mcode'")->find()) {
                $mcodes = parent::table('ek_menu')->column('mcode');
                $max = 1000;
                foreach ($mcodes as $code) {
                    if (preg_match('/^M(\d+)$/', $code, $match)) {
                        $max = max($max, intval($match[1]));
                    }
                }
                $mcode = 'M' . ($max + 1);
            }

            parent::table('ek_menu')->autoTime()->insert(array(
                'mcode' => $mcode,
                'pcode' => 'M157',
                'name' => '定时发布',
                'url' => '/admin/AutoPublish/index',
                'sorting' => 507,
                'status' => '1',
                'shortcut' => '0',
                'ico' => 'fa-clock-o',
                'create_user' => $username,
                'update_user' => $username
            ));

            // 菜单操作权限（修改）
            if (! parent::table('ek_menu_action')->where("mcode='$mcode' AND action='mod'")->find()) {
                parent::table('ek_menu_action')->insert(array(
                    'mcode' => $mcode,
                    'action' => 'mod'
                ));
            }

            // 原可访问内容列表的角色自动获得定时发布浏览权限
            $rcodes = parent::table('ek_role_level')->where("level='/admin/Content/index'")->column('rcode');
            foreach ($rcodes as $rcode) {
                if (! parent::table('ek_role_level')->where("rcode='$rcode' AND level='/admin/AutoPublish/index'")->find()) {
                    parent::table('ek_role_level')->insert(array(
                        'rcode' => $rcode,
                        'level' => '/admin/AutoPublish/index'
                    ));
                }
            }

            // 原可新增内容的角色自动获得定时发布保存权限
            $rcodes = parent::table('ek_role_level')->where("level='/admin/Content/add'")->column('rcode');
            foreach ($rcodes as $rcode) {
                if (! parent::table('ek_role_level')->where("rcode='$rcode' AND level='/admin/AutoPublish/mod'")->find()) {
                    parent::table('ek_role_level')->insert(array(
                        'rcode' => $rcode,
                        'level' => '/admin/AutoPublish/mod'
                    ));
                }
            }
        } catch (\Throwable $e) {
            // 升级失败不影响正常登录
        }
    }

    /**
     * 确保SEO配置菜单及对应权限存在（幂等，用于旧版本数据库自动升级）
     */
    // private function ensureSeoMenu($username)
    // {
    //     try {
    //         if (parent::table('ek_menu')->where("url='/admin/Config/seo'")->find()) {
    //             return;
    //         }

    //         // 父级菜单「全局配置」必须存在
    //         if (! parent::table('ek_menu')->where("mcode='M156'")->find()) {
    //             return;
    //         }

    //         // 生成不冲突的菜单编码，优先使用M159
    //         $mcode = 'M159';
    //         if (parent::table('ek_menu')->where("mcode='$mcode'")->find()) {
    //             $mcodes = parent::table('ek_menu')->column('mcode');
    //             $max = 1000;
    //             foreach ($mcodes as $code) {
    //                 if (preg_match('/^M(\d+)$/', $code, $match)) {
    //                     $max = max($max, intval($match[1]));
    //                 }
    //             }
    //             $mcode = 'M' . ($max + 1);
    //         }

    //         parent::table('ek_menu')->autoTime()->insert(array(
    //             'mcode' => $mcode,
    //             'pcode' => 'M156',
    //             'name' => 'SEO配置',
    //             'url' => '/admin/Config/seo',
    //             'sorting' => 202,
    //             'status' => '1',
    //             'shortcut' => '0',
    //             'ico' => 'fa-search',
    //             'create_user' => $username,
    //             'update_user' => $username
    //         ));

    //         // 已拥有「配置参数」权限的角色自动获得SEO配置权限
    //         $rcodes = parent::table('ek_role_level')->where("level='/admin/Config/index'")->column('rcode');
    //         foreach ($rcodes as $rcode) {
    //             if (! parent::table('ek_role_level')->where("rcode='$rcode' AND level='/admin/Config/seo'")->find()) {
    //                 parent::table('ek_role_level')->insert(array(
    //                     'rcode' => $rcode,
    //                     'level' => '/admin/Config/seo'
    //                 ));
    //             }
    //         }
    //     } catch (\Throwable $e) {
    //         // 升级失败不影响正常登录
    //     }
    // }

    // 用户登录成功后更新登录信息
    private function updateLogin($where)
    {
        $data = array(
            'last_login_ip' => ip2long(get_user_ip()),
            'login_count' => '+=1'
        );
        return parent::table('ek_user')->where($where)
            ->autoTime()
            ->update($data);
    }

    // 获取用户菜单列表
    public function getUserMenu($ucode)
    {
        if ($ucode == '10001') { // 管理员获所有区域
            $field = array(
                'id',
                'mcode',
                'pcode',
                'name',
                'url',
                'shortcut',
                'ico',
                'sorting',
                'status'
            );
            $order = array(
                'sorting',
                'pcode',
                'id'
            );
            $result = parent::table('ek_menu')->distinct()
                ->field($field)
                ->order($order)
                ->select();
        } else {
            $table = array(
                'ek_user',
                'ek_user_role',
                'ek_role',
                'ek_role_level',
                'ek_menu'
            );
            $field = array(
                'ek_menu.id',
                'ek_menu.mcode',
                'ek_menu.pcode',
                'ek_menu.name',
                'ek_menu.url',
                'ek_menu.shortcut',
                'ek_menu.ico',
                'ek_menu.sorting',
                'ek_menu.status'
            );
            $where = array(
                "ek_user.ucode='$ucode'",
                "ek_user.ucode=ek_user_role.ucode",
                "ek_role.rcode=ek_user_role.rcode",
                "ek_role.rcode=ek_role_level.rcode",
                "ek_menu.url=ek_role_level.level"
            );
            $order = array(
                'ek_menu.sorting',
                'ek_menu.pcode',
                'ek_menu.id'
            );
            $result = parent::table($table)->distinct()
                ->field($field)
                ->where($where)
                ->order($order)
                ->select();
        }
        return $result;
    }

    // 获取用户的角色代码
    public function getUserRcode($ucode)
    {
        return parent::table('ek_user_role')->where("ucode='$ucode'")->column('rcode');
    }

    // 获取用户权限列表
    public function getUserLevel($ucode)
    {
        $table = array(
            'ek_user',
            'ek_user_role',
            'ek_role',
            'ek_role_level'
        );
        $where = array(
            "ek_user.ucode='$ucode'",
            "ek_user.ucode=ek_user_role.ucode",
            "ek_role.rcode=ek_user_role.rcode",
            "ek_role.rcode=ek_role_level.rcode"
        );
        return parent::table($table)->distinct()
            ->where($where)
            ->column('ek_role_level.level');
    }

    // 获取用户可管理的区域代码
    public function getUserAcode($ucode)
    {
        if ($ucode == '10001') { // 管理员获所有区域
            $result = parent::table('ek_area')->distinct()->column('acode');
        } else {
            $table = array(
                'ek_user',
                'ek_user_role',
                'ek_role',
                'ek_role_area',
                'ek_area'
            );
            $where = array(
                "ek_user.ucode = '$ucode'",
                "ek_user.ucode=ek_user_role.ucode",
                "ek_role.rcode=ek_user_role.rcode",
                "ek_role.rcode=ek_role_area.rcode",
                "ek_area.acode=ek_role_area.acode"
            );
            $result = parent::table($table)->distinct()
                ->where($where)
                ->column('ek_area.acode');
        }
        return $result;
    }

    // 获取区域列表
    public function getAreas()
    {
        $result = parent::table('ek_area')->field('pcode,acode,name,is_default')
            ->order('is_default DESC,pcode,acode')
            ->select();
        return $result;
    }

    // 生成授权的区域树
    public function getUserAreaTree($data, $tid, $idField, $pidField, $sonName = 'son', $valid = array())
    {
        $tree = array();
        foreach ($data as $key => $value) {
            if ($value->$pidField == "$tid") { // 父亲找到儿子
                $value->$sonName = $this->getUserAreaTree($data, $value->$idField, $idField, $pidField, $sonName, $valid);
                if ($value->$sonName) {
                    $tree[] = $value;
                } elseif (in_array($value->$idField, $valid)) {
                    $tree[] = $value;
                }
            }
        }
        return $tree;
    }

    // 检查当前用户密码
    public function checkUserPwd($password)
    {
        return parent::table('ek_user')->field('id')
            ->where("id=" . session('id') . " AND password='$password'")
            ->find();
    }

    // 修改当前用户信息
    public function modUserInfo($data)
    {
        return parent::table('ek_user')->where("id=" . session('id'))->update($data);
    }

    // 获取用户信息
    public function getUserInfo($ucode)
    {
        $result = parent::table('ek_user')->field('id,ucode,username,realname,last_login_ip,update_time,login_count')
            ->where("ucode='$ucode'")
            ->find();
        return $result;
    }
}