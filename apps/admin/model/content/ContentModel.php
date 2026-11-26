<?php
/**
 *  列表文章模型类
 */
namespace app\admin\model\content;

use core\basic\Db;
use core\basic\Model;
use core\view\Paging;

class ContentModel extends Model
{

    protected $scodes = array();

    // 分页限制：用精简 COUNT 替代带 JOIN 的全量统计，降低翻页固定开销
    private function pageLimit($mcode, array $where = array(), $scodes = null, $keyword = null)
    {
        $query = parent::table('ek_content a')
            ->where("a.acode='" . session('acode') . "'")
            ->where($where);
        if ($keyword !== null && $keyword !== '') {
            $query->like('a.title', $keyword);
        }
        if ($scodes === null) {
            // 未限定栏目时，用子查询换取归属栏目集合，避免 COUNT 时逐行 JOIN
            $query->where("a.scode IN (SELECT b.scode FROM ek_content_sort b LEFT JOIN ek_model d ON b.mcode=d.mcode WHERE b.mcode='$mcode' AND (d.type=2 OR d.type IS NULL))");
        } elseif ($scodes) {
            $query->in('a.scode', $scodes);
        } else {
            return Paging::getInstance()->limit(0, true);
        }
        return Paging::getInstance()->limit($query->count(), true);
    }

    // 获取文章列表
    public function getList($mcode, $where = array())
    {
        $field = array(
            'a.id',
            'b.name as sortname',
            'a.scode',
            'a.subscode',
            'a.title',
            'a.subtitle',
            'a.date',
            'a.sorting',
            'a.status',
            'a.istop',
            'a.isrecommend',
            'a.isheadline',
            'a.visits',
            'a.ico',
            'a.pics',
            'a.filename',
            'a.outlink',
            'd.urlname',
            'b.filename as sortfilename'
        );
        $join = array(
            array(
                'ek_content_sort b',
                'a.scode=b.scode',
                'LEFT'
            ),
            array(
                'ek_model d',
                'b.mcode=d.mcode',
                'LEFT'
            )
        );
        $limit = $this->pageLimit($mcode, $where);
        return parent::table('ek_content a')->field($field)
            ->where("b.mcode='$mcode'")
            ->where('d.type=2 OR d.type is null ')
            ->where("a.acode='" . session('acode') . "'")
            ->where($where)
            ->join($join)
            ->order('a.sorting ASC,a.id DESC')
            ->limit($limit)
            ->select();
    }

    // 查找指定分类及子类文章
    public function findContent($mcode, $scode, $keyword, $status = null)
    {
        $fields = array(
            'a.id',
            'b.name as sortname',
            'a.scode',
            'a.subscode',
            'a.title',
            'a.subtitle',
            'a.date',
            'a.sorting',
            'a.status',
            'a.istop',
            'a.isrecommend',
            'a.isheadline',
            'a.visits',
            'a.ico',
            'a.pics',
            'a.filename',
            'a.outlink',
            'd.urlname',
            'b.filename as sortfilename'
        );
        $join = array(
            array(
                'ek_content_sort b',
                'a.scode=b.scode',
                'LEFT'
            ),
            array(
                'ek_model d',
                'b.mcode=d.mcode',
                'LEFT'
            )
        );
        $this->scodes = array(); // 先清空
        $scodes = $this->getSubScodes($scode);
        $where = $status === null ? array() : array('a.status' => (string) $status);
        $limit = $this->pageLimit($mcode, $where, $scodes, $keyword);
        return parent::table('ek_content a')->field($fields)
            ->where("b.mcode='$mcode'")
            ->where('d.type=2 OR d.type is null ')
            ->where("a.acode='" . session('acode') . "'")
            ->where($where)
            ->in('a.scode', $scodes)
            ->like('a.title', $keyword)
            ->join($join)
            ->order('a.sorting ASC,a.id DESC')
            ->limit($limit)
            ->select();
    }

    // 在全部栏目查找文章
    public function findContentAll($mcode, $keyword, $status = null)
    {
        $fields = array(
            'a.id',
            'b.name as sortname',
            'a.scode',
            'a.subscode',
            'a.title',
            'a.subtitle',
            'a.date',
            'a.sorting',
            'a.status',
            'a.istop',
            'a.isrecommend',
            'a.isheadline',
            'a.visits',
            'a.ico',
            'a.pics',
            'a.filename',
            'a.outlink',
            'd.urlname',
            'b.filename as sortfilename'
        );
        $join = array(
            array(
                'ek_content_sort b',
                'a.scode=b.scode',
                'LEFT'
            ),
            array(
                'ek_model d',
                'b.mcode=d.mcode',
                'LEFT'
            )
        );
        $where = $status === null ? array() : array('a.status' => (string) $status);
        $limit = $this->pageLimit($mcode, $where, null, $keyword);
        return parent::table('ek_content a')->field($fields)
            ->where("b.mcode='$mcode'")
            ->where('d.type=2 OR d.type is null ')
            ->where("a.acode='" . session('acode') . "'")
            ->where($where)
            ->like('a.title', $keyword)
            ->join($join)
            ->order('a.sorting ASC,a.id DESC')
            ->limit($limit)
            ->select();
    }

    // 获取子栏目
    public function getSubScodes($scode)
    {
        if (! $scode) {
            return;
        }
        $this->scodes[] = $scode;
        $subs = parent::table('ek_content_sort')->where("pcode='$scode'")->column('scode');
        if ($subs) {
            foreach ($subs as $value) {
                $this->getSubScodes($value);
            }
        }
        return $this->scodes;
    }

    // 检查文章
    public function checkContent($where)
    {
        return parent::table('ek_content')->field('id')
            ->where($where)
            ->find();
    }

    // 获取文章详情
    public function getContent($id)
    {
        $field = array(
            'a.*',
            'b.name as sortname',
            'c.name as subsortname',
            'd.*'
        );
        $join = array(
            array(
                'ek_content_sort b',
                'a.scode=b.scode',
                'LEFT'
            ),
            array(
                'ek_content_sort c',
                'a.subscode=c.scode',
                'LEFT'
            ),
            array(
                'ek_content_ext d',
                'a.id=d.contentid',
                'LEFT'
            )
        );
        return parent::table('ek_content a')->field($field)
            ->where("a.id=$id")
            ->where("a.acode='" . session('acode') . "'")
            ->join($join)
            ->find();
    }

    // 添加文章
    public function addContent(array $data)
    {
        return parent::table('ek_content')->autoTime()->insertGetId($data);
    }

    // 删除文章
    public function delContent($id)
    {
        return parent::table('ek_content')->where("id=$id")
            ->where("acode='" . session('acode') . "'")
            ->delete();
    }

    // 删除文章
    public function delContentList($ids)
    {
        return parent::table('ek_content')->where("acode='" . session('acode') . "'")->delete($ids);
    }

    // 修改文章
    public function modContent($id, $data)
    {
        return parent::table('ek_content')->autoTime()
            ->in('id', $id)
            ->where("acode='" . session('acode') . "'")
            ->update($data);
    }

    // 复制内容到指定栏目
    public function copyContent($ids, $scode)
    {
        // 查找出要复制的主内容
        $data = parent::table('ek_content')->in('id', $ids)->select(1);
        
        foreach ($data as $key => $value) {
            // 查找扩展内容
            $extdata = parent::table('ek_content_ext')->where('contentid=' . $value['id'])->find(1);
            
            // 去除主键并修改栏目，清空自定义URL名称避免副本与原文链接冲突
            unset($value['id']);
            $value['scode'] = $scode;
            $value['filename'] = '';
            
            // 插入主内容
            $id = parent::table('ek_content')->insertGetId($value);
            
            // 插入扩展内容
            if ($id && $extdata) {
                unset($extdata['extid']);
                $extdata['contentid'] = $id;
                $result = parent::table('ek_content_ext')->insert($extdata);
            } else {
                $result = $id;
            }
        }
        return $result;
    }

    // 查找文章扩展内容
    public function findContentExt($id)
    {
        return parent::table('ek_content_ext')->where("contentid=$id")->find();
    }

    // 添加文章扩展内容
    public function addContentExt(array $data)
    {
        return parent::table('ek_content_ext')->insert($data);
    }

    // 修改文章扩展内容
    public function modContentExt($id, $data)
    {
        return parent::table('ek_content_ext')->where("contentid=$id")->update($data);
    }

    // 删除文章扩展内容
    public function delContentExt($id)
    {
        return parent::table('ek_content_ext')->where("contentid=$id")->delete();
    }

    // 删除文章扩展内容
    public function delContentExtList($ids)
    {
        return parent::table('ek_content_ext')->delete($ids, 'contentid');
    }

    // 检查自定义URL名称
    public function checkFilename($filename, $where = array())
    {
        return parent::table('ek_content')->field('id')
            ->where("filename='$filename'")
            ->where($where)
            ->find();
    }

    // 检查自定义URL名称是否与已有内容ID冲突
    public function checkFilenameConflictId($filename, $excludeId = 0)
    {
        if (! ctype_digit((string) $filename)) {
            return false;
        }
        $where = $excludeId ? "id<>$excludeId" : '';
        return parent::table('ek_content')->field('id')
            ->where("id='$filename'")
            ->where($where)
            ->find();
    }

    public function getImage()
    {
        $list = array();
        $lastId = 0;
        $pageSize = 500;

        do {
            $rows = parent::table('ek_content')->field('id,ico,pics,content,enclosure')
                ->where("id>$lastId")
                ->order('id ASC')
                ->limit($pageSize)
                ->select(1);
            if (!$rows) {
                break;
            }
            $ids = array();
            foreach ($rows as $value) {
                $ids[] = (int) $value['id'];
            }
            $extMap = array();
            $extRows = parent::table('ek_content_ext')->in('contentid', $ids)->select(1);
            foreach ((array) $extRows as $ext) {
                $paths = array();
                foreach ($ext as $field => $fieldValue) {
                    if ($field === 'extid' || $field === 'contentid') {
                        continue;
                    }
                    $paths = array_merge($paths, $this->extractUploadPaths(decode_string((string) $fieldValue)));
                }
                $extMap[(int) $ext['contentid']] = $paths;
            }
            foreach ($rows as $value) {
                $lastId = (int) $value['id'];
                $list[] = array(
                    'ico' => $value['ico'],
                    'pics' => explode(',', (string) $value['pics']),
                    'content_img' => $this->extractUploadPaths(decode_string((string) $value['content'])),
                    'enclosure' => $this->extractUploadPaths((string) $value['enclosure']),
                    'ext' => isset($extMap[$lastId]) ? $extMap[$lastId] : array()
                );
            }
        } while (count($rows) === $pageSize);

        return $list;
    }

    private function extractUploadPaths($text)
    {
        if ($text === '') {
            return array();
        }
        $marker = preg_quote(ltrim(substr(STATIC_DIR, strlen(SITE_DIR)), '/') . '/upload/', '~');
        preg_match_all('~' . $marker . '[^\s\'"<>()?#,;&]+~i', $text, $match);
        return $match[0];
    }
}
