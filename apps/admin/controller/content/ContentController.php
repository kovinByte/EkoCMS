<?php
/**
 *  文章控制器
 */

namespace app\admin\controller\content;

use app\admin\model\content\ContentModel;
use core\basic\Controller;

class ContentController extends Controller
{

    private $model;

    private $blank;

    public function __construct()
    {
        $this->model = new ContentModel();
    }

    // 当前主题可用的模板文件列表
    private function assignTpls()
    {
        $htmldir = $this->config('tpl_html_dir') ? '/' . $this->config('tpl_html_dir') : '';
        $theme = model('admin.content.ContentSort')->getTheme();
        $this->assign('tpls', tpl_file_list(ROOT_PATH . current($this->config('tpl_dir')) . '/' . $theme . $htmldir));
    }

    // 文章列表
    public function index()
    {
        // 本页只读取会话，尽早释放会话写锁，避免与其他后台请求（如开关）互相阻塞
        session_write_close();
        if ((!!$id = get('id', 'int')) && $result = $this->model->getContent($id)) {
            $this->assign('more', true);
            $this->assign('content', $result);
        } else {
            $this->assign('list', true);
            if (!$mcode = get('mcode', 'var')) {
                error('传递的模型编码参数有误，请核对后重试！');
            }

            $status = get('status', 'int'); // 状态筛选：1显示 0隐藏，空为全部

            if (isset($_GET['keyword'])) {
                if (!!$scode = get('scode', 'var')) {
                    $result = $this->model->findContent($mcode, $scode, get('keyword', 'vars'), $status);
                } else {
                    $result = $this->model->findContentAll($mcode, get('keyword', 'vars'), $status);
                }
            } elseif (!!$scode = get('scode', 'int')) {
                $result = $this->model->findContent($mcode, $scode, '', $status);
            } else {
                $where = $status === null ? array() : array('a.status' => (string) $status);
                $result = $this->model->getList($mcode, $where);
            }
            $this->assign('contents', $result);

            // 文章分类下拉列表
            $sort_model = model('admin.content.ContentSort');
            $sort_select = $sort_model->getListSelect($mcode);
            $this->assign('search_select', $this->makeSortSelect($sort_select, get('scode')));
            $this->assign('sort_select', $this->makeSortSelect($sort_select, session('addscode')));
            $this->assign('subsort_select', $this->makeSortSelect($sort_select));

            // 模型名称
            $this->assign('model_name', model('admin.content.Model')->getName($mcode));

            // 扩展字段
            $extfield = model('admin.content.ExtField')->getModelField($mcode);
            $this->assign('extfield', $extfield);

            // 批量导入字段列表（主表字段 + 当前模型扩展字段）
            $import_fields = array();
            $import_main_fields = array(
                'title' => '内容标题（必填）',
                'subtitle' => '副标题',
                'author' => '作者',
                'source' => '来源',
                'date' => '发布时间',
                'tags' => '标签（多个用逗号分隔）',
                'keywords' => 'SEO关键字',
                'description' => 'SEO描述',
                'content' => '内容正文',
                'ico' => '缩略图地址',
                'pics' => '多图地址（多个用逗号分隔）',
                'filename' => 'URL名称',
                'outlink' => '跳转外链',
                'enclosure' => '附件地址'
            );
            foreach ($import_main_fields as $name => $description) {
                $item = new \stdClass();
                $item->name = $name;
                $item->description = $description;
                $item->main = 1;
                $import_fields[] = $item;
            }
            if ($extfield) {
                foreach ($extfield as $value) {
                    $item = new \stdClass();
                    $item->name = $value->name;
                    $item->description = htmlspecialchars($value->description, ENT_QUOTES, 'UTF-8');
                    $item->main = 0;
                    $import_fields[] = $item;
                }
            }
            $this->assign('import_fields', $import_fields);

            $this->assign('baidu_zz_token', $this->config('baidu_zz_token'));
            $this->assign('baidu_ks_token', $this->config('baidu_ks_token'));
            $indexnow_key = get_effective_indexnow_key($this->config('indexnow_key'), $this->config('indexnow_key_location'));
            $this->assign('indexnow_key', $indexnow_key);

            // 前端地址连接符判断
            $url_break_char = $this->config('url_break_char') ?: '_';
            $this->assign('url_break_char', $url_break_char);

            // 获取会员分组
            $this->assign('groups', model('admin.member.MemberGroup')->getSelect());

            // AI 助手：列表+新增同页，仅在「新增」Tab 显示浮动按钮
            $this->assign('ai_show_assistant', 'tabbed');
        }

        $this->assignTpls();

        $this->display('content/content.html');
    }

    // 文章增加
    public function add()
    {
        if ($_POST) {

            // 获取数据
            $scode = post('scode');
            $subscode = post('subscode');
            $title = post('title');
            $titlecolor = post('titlecolor');
            $subtitle = post('subtitle');
            $filename = trim(post('filename'), '/');
            $author = post('author');
            $source = post('source');
            $outlink = post('outlink');
            $date = post('date');
            $ico = normalize_upload_saved_path(post('ico'));
            $pics = normalize_upload_saved_paths(post('pics'));

            // 获取多图标题
            $picstitle = post('picstitle');
            if ($picstitle) {
                $picstitle = implode(',', $picstitle);
            }

            $content = normalize_richtext_for_storage(post('content'));
            $tags = str_replace('，', ',', post('tags'));
            $enclosure = normalize_upload_saved_path(post('enclosure'));
            $keywords = post('keywords');
            $description = post('description');
            $status = post('status', 'int');
            $istop = post('istop', 'int', '', '', 0);
            $isrecommend = post('isrecommend', 'int', '', '', 0);
            $isheadline = post('isheadline', 'int', '', '', 0);

            $gid = post('gid', 'int') ?: 0;
            $gtype = post('gtype', 'int') ?: 4;
            $gnote = post('gnote');
            $contenttpl = tpl_path(post('contenttpl'));

            if (!$scode) {
                alert_back('内容分类不能为空！');
            }

            if (!$title) {
                alert_back('文章标题不能为空！');
            }

            if ($filename && !preg_match('/^[a-zA-Z0-9\-_\/]+$/', $filename)) {
                alert_back('内容URL名称只允许字母、数字、横线、下划线组成!');
            }

            if ($filename && $this->model->checkFilenameConflictId($filename)) {
                alert_back('内容URL名称不能与已有内容ID重复!');
            }

            // 自动提起前一百个字符为描述
            if (!$description && isset($_POST['content'])) {
                $description = escape_string(clear_html_blank(substr_both(strip_tags($_POST['content']), 0, 150)));
            }

            // 无缩略图时，自动提取文章第一张图为缩略图
            if (!$ico && preg_match('/<img\s+.*?src=\s?[\'|\"](.*?(\.gif|\.jpg|\.jpeg|\.png|\.webp))[\'|\"].*?[\/]?>/i', decode_string($content), $srcs) && isset($srcs[1])) {
                $ico = $srcs[1];
            }

            // 缩放缩略图
            if ($ico) {
                resize_img(ROOT_PATH . $ico, '', $this->config('ico.max_width'), $this->config('ico.max_height'));
            }

            // 检查自定义URL名称
            if ($filename) {
                while ($this->model->checkFilename($filename)) {
                    $filename = $filename . '-' . mt_rand(1, 20);
                }
            }

            // 记住新增栏目
            session('addscode', $scode);

            // 构建数据
            $data = array(
                'acode' => session('acode'),
                'scode' => $scode,
                'subscode' => $subscode,
                'title' => $title,
                'titlecolor' => $titlecolor,
                'subtitle' => $subtitle,
                'filename' => $filename,
                'author' => $author,
                'source' => $source,
                'outlink' => $outlink,
                'date' => $date,
                'ico' => $ico,
                'pics' => $pics,
                'picstitle' => $picstitle,
                'contenttpl' => $contenttpl,
                'content' => $content,
                'tags' => $tags,
                'enclosure' => $enclosure,
                'keywords' => $keywords,
                'description' => clear_html_blank($description),
                'sorting' => 255,
                'status' => $status,
                'istop' => $istop,
                'isrecommend' => $isrecommend,
                'isheadline' => $isheadline,
                'gid' => $gid,
                'gtype' => $gtype,
                'gnote' => $gnote,
                'visits' => 0,
                'likes' => 0,
                'oppose' => 0,
                'create_user' => session('username'),
                'update_user' => session('username')
            );

            // 执行添加
            $contentBack = admin_back_target(url('/admin/Content/index/mcode/' . get('mcode')));
            if (!!$id = $this->model->addContent($data)) {
                // 扩展内容添加
                foreach ($_POST as $key => $value) {
                    if (preg_match('/^ext_[\w\-]+$/', $key)) {
                        if (!isset($data2['contentid'])) {
                            $data2['contentid'] = $id;
                        }
                        $temp = post($key);
                        if (is_array($temp)) {
                            $data2[$key] = implode(',', $temp);
                        } else {
                            $data2[$key] = str_replace("\r\n", '<br>', normalize_richtext_for_storage($temp));
                        }
                    }
                }
                if (isset($data2)) {
                    if (!$this->model->addContentExt($data2)) {
                        $this->model->delContent($id);
                        $this->log('新增文章失败！');
                        alert_back('新增失败！');
                    }
                }

                $this->log('新增文章成功！');
                $msg = '新增成功！';
                $syncHtmls = array($content);
                if (isset($data2)) {
                    foreach ($data2 as $__k => $__v) {
                        if (strpos($__k, 'ext_') === 0 && is_string($__v)) {
                            $syncHtmls[] = $__v;
                        }
                    }
                }
                if ($notice = iframe_whitelist_sync_notice_many($syncHtmls, '新增成功！')) {
                    $msg = $notice;
                }
                success($msg, $contentBack);
            } else {
                $this->log('新增文章失败！');
                alert_back('新增失败！');
            }
        }
    }

    // 批量导入：上传并流式解析表格
    public function importparse()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error('请使用正确方式提交！');
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        if (! $mcode = get('mcode', 'var')) {
            json(0, '传递的模型编码参数有误！');
        }

        if (! model('admin.content.Model')->getName($mcode)) {
            json(0, '内容模型不存在！');
        }

        if (! isset($_FILES['importfile']) || ! is_array($_FILES['importfile'])) {
            json(0, '请选择要导入的表格文件！');
        }

        $file = $_FILES['importfile'];
        if ($file['error'] != UPLOAD_ERR_OK) {
            json(0, '文件上传失败，错误码：' . $file['error']);
        }
        if ($file['size'] <= 0) {
            json(0, '上传的文件内容为空！');
        }
        if ($file['size'] > 20 * 1024 * 1024) {
            json(0, '文件体积过大，请控制在 20MB 以内！');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (! in_array($ext, array('xlsx', 'csv'))) {
            json(0, '仅支持 .xlsx 或 .csv 格式的表格文件！');
        }

        if (! is_uploaded_file($file['tmp_name'])) {
            json(0, '上传文件校验失败！');
        }

        $dir = RUN_PATH . '/import';
        if (! check_dir($dir, true)) {
            json(0, '导入临时目录创建失败，请检查 runtime 目录写入权限！');
        }
        $this->cleanImportFiles($dir);

        $token = md5(uniqid((string) mt_rand(), true));
        $save = $dir . '/' . $mcode . '_' . $token . '.' . $ext;
        if (! move_uploaded_file($file['tmp_name'], $save)) {
            json(0, '文件保存失败，请检查目录写入权限！');
        }

        // 流式转换为逐行 JSON 中间文件，避免整张表驻留内存
        $ndjson = $dir . '/' . $mcode . '_' . $token . '.ndjson';
        $meta_file = $dir . '/' . $mcode . '_' . $token . '.json';
        $result = $this->convertImportFile($save, $ext, $ndjson);
        @unlink($save);

        if ($result['error'] !== '') {
            @unlink($ndjson);
            json(0, $result['error']);
        }
        if ($result['total'] <= 0) {
            @unlink($ndjson);
            json(0, '未能读取到表格数据，请检查文件内容是否为有效的表格！');
        }

        // 按最大列数生成列信息，标题取首行
        $columns = array();
        $header = $result['first'];
        $max_column = max($result['columns'], count($header));
        for ($i = 0; $i < $max_column; $i ++) {
            $columns[] = array(
                'index' => $i,
                'letter' => $this->excelColumnLetter($i),
                'title' => isset($header[$i]) ? (string) $header[$i] : ''
            );
        }

        // 仅保存解析元信息，供分片导入读取总行数
        $meta = array(
            'mcode' => $mcode,
            'ext' => $ext,
            'total' => $result['total'],
            'created' => time()
        );
        @file_put_contents($meta_file, json_encode($meta, JSON_UNESCAPED_UNICODE));

        json(1, array(
            'token' => $token,
            'columns' => $columns,
            'rows' => $result['total'],
            'first' => $result['first'],
            'sample' => $result['sample']
        ));
    }

    // 批量导入：按分片执行导入
    public function importrun()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error('请使用正确方式提交！');
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        if (! $mcode = get('mcode', 'var')) {
            json(0, '传递的模型编码参数有误！');
        }

        if (! model('admin.content.Model')->getName($mcode)) {
            json(0, '内容模型不存在！');
        }

        $scode = post('scode');
        if (! $scode) {
            json(0, '请选择要导入的内容栏目！');
        }
        if (! model('admin.content.ContentSort')->checkSort("scode='" . escape_string($scode) . "' AND mcode='" . escape_string($mcode) . "' AND acode='" . session('acode') . "'")) {
            json(0, '所选栏目不存在或不属于当前模型！');
        }

        $token = post('token');
        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            json(0, '导入文件标识无效，请重新上传表格！');
        }

        $dir = RUN_PATH . '/import';
        $ndjson = $dir . '/' . $mcode . '_' . $token . '.ndjson';
        $meta_file = $dir . '/' . $mcode . '_' . $token . '.json';
        $state_file = $dir . '/' . $mcode . '_' . $token . '.state.json';
        if (! is_file($ndjson)) {
            json(0, '导入文件已失效，请重新上传表格！');
        }

        // 允许导入的字段
        $main_fields = array('title', 'subtitle', 'author', 'source', 'date', 'tags', 'keywords', 'description', 'content', 'ico', 'pics', 'filename', 'outlink', 'enclosure');
        $ext_fields = array();
        foreach (model('admin.content.ExtField')->getModelField($mcode) as $field) {
            $ext_fields[$field->name] = $field;
        }

        $map = post('map');
        if (! is_array($map)) {
            $map = array();
        }
        $mapping = array();
        foreach ($map as $field => $col) {
            if (! in_array($field, $main_fields) && ! isset($ext_fields[$field])) {
                continue;
            }
            if ($col === '' || $col === null || ! is_numeric($col)) {
                continue;
            }
            $mapping[$field] = (int) $col;
        }
        if (! isset($mapping['title'])) {
            json(0, '请为“标题(title)”字段指定对应的表格列！');
        }

        $firstrow = isset($_POST['firstrow']) ? (post('firstrow', 'int') ? 1 : 0) : 1;
        $status = post('status', 'int') ? 1 : 0;

        $meta = is_file($meta_file) ? json_decode((string) @file_get_contents($meta_file), true) : array();
        $total = (isset($meta['total']) && $meta['total'] > 0) ? (int) $meta['total'] : $this->countImportRows($ndjson);

        // 状态文件加锁，避免并发分片重复导入
        $lock = @fopen($state_file, 'c+b');
        if (! $lock) {
            json(0, '导入状态文件创建失败，请检查 runtime 目录写入权限！');
        }
        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            json(0, '当前表格正在导入中，请稍后再试！');
        }

        $state = array(
            'offset' => 0,
            'index' => 0,
            'success' => 0,
            'fail' => 0,
            'skip' => 0
        );
        $raw = stream_get_contents($lock);
        if ($raw) {
            $saved = json_decode($raw, true);
            if (is_array($saved)) {
                $state = array_merge($state, $saved);
            }
        }

        $success = 0;
        $fail = 0;
        $skip = 0;
        $errors = array();
        $finished = false;
        $fatal = '';

        $offset = (int) $state['offset'];
        $row_no = (int) $state['index'];
        $new_offset = $offset;
        $lines = array();
        $fh = null;
        $in_transaction = false;

        try {
            $fh = fopen($ndjson, 'rb');
            if (! $fh) {
                throw new \Exception('导入文件无法读取，请重新上传表格！');
            }
            fseek($fh, $offset);

            // 一次读取一个分片到内存，避免整表驻留
            $read = 0;
            while ($read < 1000 && count($lines) < 500 && ! feof($fh)) {
                $line = fgets($fh);
                if ($line === false) {
                    break;
                }
                $read ++;
                $new_offset = ftell($fh);
                $row_no ++;
                if ($row_no == 1 && $firstrow) { // 首行标题不导入
                    continue;
                }
                $row = json_decode(trim($line), true);
                if (! is_array($row) || ! $row) {
                    $skip ++;
                    continue;
                }
                if ($this->importValue($row, $mapping, 'title') === '') {
                    $skip ++;
                    continue;
                }
                $lines[] = array($row_no, $row);
            }
            $finished = ($new_offset >= filesize($ndjson));

            // 批量预查重名 URL 名称，避免逐行查库
            $filenames = array();
            if ($lines) {
                $candidates = array();
                foreach ($lines as $key => $item) {
                    $filenames[$key] = '';
                    if (isset($mapping['filename'])) {
                        $name = $this->cleanImportFilename($this->importValue($item[1], $mapping, 'filename'));
                        $filenames[$key] = $name;
                        if ($name !== '') {
                            $candidates[$name] = 1;
                        }
                    }
                }
                $used = array();
                if ($candidates) {
                    $exists = $this->model->table('ek_content')->field('filename')->in('filename', array_keys($candidates))->select();
                    foreach ($exists as $item) {
                        if (isset($item->filename)) {
                            $used[$item->filename] = 1;
                        }
                    }
                }
                foreach ($filenames as $key => $name) {
                    if ($name === '') {
                        continue;
                    }
                    $base = $name;
                    $times = 0;
                    while (isset($used[$name])) {
                        if (++ $times > 50) {
                            $name = $base . '-' . ($key + 1);
                            break;
                        }
                        $name = $base . '-' . mt_rand(1, 20);
                    }
                    $used[$name] = 1;
                    $filenames[$key] = $name;
                }
            }

            // 每个分片一个事务，批量提交
            if ($lines) {
                $this->model->begin();
                $in_transaction = true;
                foreach ($lines as $key => $item) {
                    try {
                        $this->importContentRow($item[1], $mapping, $ext_fields, $scode, $status, $filenames[$key]);
                        $success ++;
                    } catch (\Throwable $e) {
                        $fail ++;
                        if (count($errors) < 20) {
                            $errors[] = '第' . $item[0] . '行：' . $e->getMessage();
                        }
                    }
                }
                $this->model->commit();
                $in_transaction = false;
            }

            // 分片成功后落盘进度
            $state['offset'] = $new_offset;
            $state['index'] = $row_no;
            $state['success'] += $success;
            $state['fail'] += $fail;
            $state['skip'] += $skip;
            ftruncate($lock, 0);
            rewind($lock);
            fwrite($lock, json_encode($state, JSON_UNESCAPED_UNICODE));
            fflush($lock);
        } catch (\Throwable $e) {
            if ($in_transaction) {
                try {
                    $this->model->rollBack();
                } catch (\Throwable $ignore) {}
            }
            $fatal = $e->getMessage();
        } finally {
            if (is_resource($fh)) {
                fclose($fh);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        if ($fatal !== '') {
            json(0, '导入中断：' . $fatal);
        }

        if ($finished) {
            @unlink($ndjson);
            @unlink($meta_file);
            @unlink($state_file);
            $this->log('批量导入内容完成：成功' . $state['success'] . '条，失败' . $state['fail'] . '条，跳过' . $state['skip'] . '条！');
        }

        json(1, array(
            'success' => $success,
            'fail' => $fail,
            'skip' => $skip,
            'total_success' => $state['success'],
            'total_fail' => $state['fail'],
            'total_skip' => $state['skip'],
            'processed' => min($state['index'], $total),
            'total' => $total,
            'finished' => $finished,
            'msg' => $finished ? '导入完成：成功 ' . $state['success'] . ' 条，失败 ' . $state['fail'] . ' 条' . ($state['skip'] ? '，跳过空标题 ' . $state['skip'] . ' 条' : '') . '。' : '正在导入，请稍候……',
            'errors' => $errors
        ));
    }


    // 导入单行内容
    private function importContentRow($row, $mapping, $ext_fields, $scode, $status, $filename = null)
    {
        $title = escape_string($this->importValue($row, $mapping, 'title'));

        $subtitle = escape_string($this->importValue($row, $mapping, 'subtitle'));
        $author = escape_string($this->importValue($row, $mapping, 'author'));
        $source = escape_string($this->importValue($row, $mapping, 'source'));
        $outlink = escape_string($this->importValue($row, $mapping, 'outlink'));
        $keywords = escape_string($this->importValue($row, $mapping, 'keywords'));
        $tags = str_replace('，', ',', escape_string($this->importValue($row, $mapping, 'tags')));

        $raw_content = $this->importValue($row, $mapping, 'content');
        $content = normalize_richtext_for_storage(str_replace(array("\r\n", "\r", "\n"), '<br>', $raw_content));

        // 未填写描述时自动提取正文前150个字符
        $description = $this->importValue($row, $mapping, 'description');
        if ($description === '' && $raw_content !== '') {
            $description = escape_string(clear_html_blank(substr_both(strip_tags($raw_content), 0, 150)));
        } else {
            $description = clear_html_blank(escape_string($description));
        }

        // 缩略图，未填写时自动提取正文第一张图片
        $ico = $this->importValue($row, $mapping, 'ico');
        $ico = $ico ? normalize_upload_saved_path(escape_string($ico)) : '';
        if (! $ico && $raw_content && preg_match('/<img\s+.*?src=\s?[\'|\"](.*?(\.gif|\.jpg|\.jpeg|\.png|\.webp))[\'|\"].*?[\/]?>/i', $raw_content, $srcs) && isset($srcs[1])) {
            $ico = $srcs[1];
        }
        if ($ico && is_file(ROOT_PATH . $ico)) {
            resize_img(ROOT_PATH . $ico, '', $this->config('ico.max_width'), $this->config('ico.max_height'));
        }

        $pics = str_replace('，', ',', $this->importValue($row, $mapping, 'pics'));
        $pics = normalize_upload_saved_paths(escape_string($pics));
        $enclosure = normalize_upload_saved_path(escape_string($this->importValue($row, $mapping, 'enclosure')));
        $date = $this->normalizeImportDate($this->importValue($row, $mapping, 'date'));

        // 未传入时为单行模式，逐行校验重名；分片模式已在批次内统一处理
        if ($filename === null) {
            $filename = $this->cleanImportFilename($this->importValue($row, $mapping, 'filename'));
            if ($filename) {
                while ($this->model->checkFilename($filename)) {
                    $filename = $filename . '-' . mt_rand(1, 20);
                }
            }
        }

        $data = array(
            'acode' => session('acode'),
            'scode' => $scode,
            'subscode' => '',
            'title' => $title,
            'titlecolor' => '',
            'subtitle' => $subtitle,
            'filename' => $filename,
            'author' => $author,
            'source' => $source,
            'outlink' => $outlink,
            'date' => $date,
            'ico' => $ico,
            'pics' => $pics,
            'picstitle' => '',
            'content' => $content,
            'tags' => $tags,
            'enclosure' => $enclosure,
            'keywords' => $keywords,
            'description' => $description,
            'sorting' => 255,
            'status' => $status,
            'istop' => 0,
            'isrecommend' => 0,
            'isheadline' => 0,
            'gid' => 0,
            'gtype' => 4,
            'gnote' => '',
            'visits' => 0,
            'likes' => 0,
            'oppose' => 0,
            'create_user' => session('username'),
            'update_user' => session('username')
        );

        if (! $id = $this->model->addContent($data)) {
            throw new \Exception('内容写入失败');
        }

        $data2 = array();
        foreach ($ext_fields as $name => $field) {
            if (! isset($mapping[$name])) {
                continue;
            }
            $value = $this->importValue($row, $mapping, $name);
            if ($field->type == 4) {
                $value = escape_string(str_replace(array('，', '、', ';', '；'), ',', $value));
            } elseif ($field->type == 7) {
                $value = $this->normalizeImportDate($value);
            } elseif ($field->type == 5 || $field->type == 10) {
                $value = normalize_upload_saved_paths(escape_string(str_replace('，', ',', $value)));
            } else {
                $value = str_replace("\r\n", '<br>', normalize_richtext_for_storage($value));
            }
            $data2[$name] = $value;
        }

        if ($data2) {
            $data2['contentid'] = $id;
            if (! $this->model->addContentExt($data2)) {
                $this->model->delContent($id);
                throw new \Exception('扩展内容写入失败');
            }
        }
    }

    // 流式解析表格并转换为逐行 JSON 中间文件
    private function convertImportFile($path, $ext, $ndjsonPath)
    {
        $result = array(
            'total' => 0,
            'columns' => 0,
            'first' => array(),
            'sample' => array(),
            'error' => ''
        );

        $out = @fopen($ndjsonPath, 'wb');
        if (! $out) {
            $result['error'] = '导入临时文件创建失败，请检查 runtime 目录写入权限！';
            return $result;
        }

        $reader = null;
        $json_flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $json_flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        try {
            if ($ext == 'xlsx') {
                $reader = (new \core\extend\excel\XlsxStreamReader($path))->rows();
            } else {
                $reader = $this->csvRows($path);
            }

            foreach ($reader as $row) {
                $line = $this->normalizeImportRow($row);
                if (! $line) {
                    continue;
                }
                $result['total'] ++;
                $result['columns'] = max($result['columns'], count($line));
                if ($result['total'] == 1) {
                    $result['first'] = $this->clipImportRow($line);
                } elseif ($result['total'] == 2) {
                    $result['sample'] = $this->clipImportRow($line);
                }
                fwrite($out, json_encode($line, $json_flags) . "\n");
            }
        } catch (\Throwable $e) {
            $reader = null; // 释放生成器持有的临时文件
            fclose($out);
            @unlink($ndjsonPath);
            $result['error'] = '表格解析失败：' . $e->getMessage();
            return $result;
        }

        $reader = null;
        fclose($out);
        if (! $result['sample']) {
            $result['sample'] = $result['first'];
        }
        return $result;
    }

    // CSV 流式读取，兼容 UTF-8 BOM 与 GB18030 编码
    private function csvRows($path)
    {
        $fp = @fopen($path, 'rb');
        if (! $fp) {
            return;
        }

        try {
            $sample = fread($fp, 8192);
            if ($sample === false) {
                $sample = '';
            }
            $bom = (strncmp($sample, "\xEF\xBB\xBF", 3) === 0);
            $utf8 = mb_check_encoding($sample, 'UTF-8');
            if (! $utf8) {
                // 采样末尾可能截断多字节字符，最多回退 3 字节再判断
                for ($cut = 1; $cut <= 3 && strlen($sample) > $cut; $cut ++) {
                    if (mb_check_encoding(substr($sample, 0, - $cut), 'UTF-8')) {
                        $utf8 = true;
                        break;
                    }
                }
            }
            rewind($fp);
            if ($bom) {
                fseek($fp, 3);
            }

            while (($row = fgetcsv($fp, 0, ',')) !== false) {
                if (! $utf8) {
                    foreach ($row as $key => $value) {
                        if (is_string($value) && $value !== '') {
                            $converted = @mb_convert_encoding($value, 'UTF-8', 'GB18030');
                            if ($converted !== false) {
                                $row[$key] = $converted;
                            }
                        }
                    }
                }
                yield $row;
            }
        } finally {
            fclose($fp);
        }
    }

    // 规范化导入行：去除首尾空白并丢弃全空行
    private function normalizeImportRow($row)
    {
        if (! is_array($row)) {
            return array();
        }
        $line = array();
        $has = false;
        foreach ($row as $value) {
            if (is_array($value) || is_object($value) || $value === null) {
                $value = '';
            } elseif (is_string($value)) {
                $value = trim($value);
            }
            if ($value !== '') {
                $has = true;
            }
            $line[] = $value;
        }
        return $has ? $line : array();
    }

    // 截取用于前端预览的行
    private function clipImportRow($row)
    {
        $line = array();
        foreach ($row as $key => $value) {
            $line[$key] = $this->clipImportSample($value);
        }
        return $line;
    }

    // 统计中间文件行数（元信息缺失时兜底）
    private function countImportRows($path)
    {
        $fp = @fopen($path, 'rb');
        if (! $fp) {
            return 0;
        }
        $total = 0;
        while (fgets($fp) !== false) {
            $total ++;
        }
        fclose($fp);
        return $total;
    }

    // 规范化导入的 URL 名称
    private function cleanImportFilename($filename)
    {
        $filename = trim((string) $filename, '/');
        return preg_replace('/[^a-zA-Z0-9\-_\/]/', '', escape_string($filename));
    }

    // 读取指定字段在表格行中的值
    private function importValue($row, $mapping, $field)
    {
        if (! isset($mapping[$field]) || ! isset($row[$mapping[$field]])) {
            return '';
        }
        $value = $row[$mapping[$field]];
        if (is_array($value) || is_object($value)) {
            return '';
        }
        return trim((string) $value);
    }

    // 规范化导入的日期
    private function normalizeImportDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return date('Y-m-d H:i:s');
        }
        if (preg_match('/^\d{8}$/', $value)) {
            return substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2) . ' 00:00:00';
        }
        // Excel日期序列号
        if (is_numeric($value) && $value > 20000 && $value < 80000) {
            return gmdate('Y-m-d H:i:s', (int) (($value - 25569) * 86400));
        }
        $value = str_replace(array('年', '月'), '-', $value);
        $value = str_replace(array('日', 'T'), ' ', $value);
        $value = str_replace(array('/', '.'), '-', $value);
        $time = strtotime($value);
        if ($time === false || $time <= 0) {
            return date('Y-m-d H:i:s');
        }
        return date('Y-m-d H:i:s', $time);
    }

    // 清理过期的导入临时文件
    private function cleanImportFiles($dir)
    {
        $files = glob($dir . '/*');
        if (! $files) {
            return;
        }
        $time = time() - 7200;
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $time) {
                @unlink($file);
            }
        }
    }

    // 生成Excel列字母
    private function excelColumnLetter($index)
    {
        $letter = '';
        $index ++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int) (($index - $mod) / 26);
        }
        return $letter;
    }

    // 截取导入示例值
    private function clipImportSample($value)
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }
        return substr_both(trim((string) $value), 0, 50);
    }

    // 生成分类选择
    private function makeSortSelect($tree, $selectid = null)
    {
        $list_html = '';
        foreach ($tree as $value) {
            // 默认选择项
            if ($selectid == $value->scode) {
                $select = "selected='selected'";
            } else {
                $select = '';
            }
            $list_html .= "<option value='{$value->scode}' $select>{$this->blank}{$value->name}";
            // 子菜单处理
            if ($value->son) {
                $this->blank .= '　　';
                $list_html .= $this->makeSortSelect($value->son, $selectid);
            }
        }
        // 循环完后回归位置
        $this->blank = substr($this->blank, 0, -6);
        return $list_html;
    }

    // 文章删除
    public function del()
    {
        $back = admin_stay_target(url('/admin/Content/index'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error('请使用正确方式提交！', $back);
        }
        if (!!$list = post('list')) {
            if ($this->model->delContentList($list)) {
                $this->model->delContentExtList($list);
                $this->log('批量删除文章成功！');
                success('批量删除成功！', $back);
            } else {
                $this->log('批量删除文章失败！');
                error('批量删除失败！', $back);
            }
        }

        if (!$id = get('id', 'int')) {
            error('请选择要删除的内容！', $back);
        }

        if ($this->model->delContent($id)) {
            $this->model->delContentExt($id);
            $this->log('删除文章' . $id . '成功！');
            success('删除成功！', $back);
        } else {
            $this->log('删除文章' . $id . '失败！');
            error('删除失败！', $back);
        }
    }

    // 文章修改
    public function mod()
    {
        if (!!$submit = post('submit')) {
            switch ($submit) {
                case 'sorting': // 修改列表排序
                    $listall = post('listall');
                    if ($listall) {
                        $sorting = post('sorting');
                        foreach ($listall as $key => $value) {
                            if ($sorting[$key] === '' || !is_numeric($sorting[$key]))
                                $sorting[$key] = 255;
                            $this->model->modContent($value, "sorting=" . $sorting[$key]);
                        }
                        $this->log('修改内容排序成功！');
                        json(1, array('sorting' => array_values($sorting)));
                    } else {
                        alert_back('排序失败，无任何内容！');
                    }
                    break;
                case 'copy':
                    $list = post('list');
                    $scode = post('scode');
                    if (!$list) {
                        alert_back('请选择要复制的内容！');
                    }
                    if (!$scode) {
                        alert_back('请选择目标栏目！');
                    }
                    $back = admin_stay_target(url('/admin/Content/index'));
                    if ($this->model->copyContent($list, $scode)) {
                        $this->log('复制内容成功！');
                        success('复制内容成功！', $back);
                    } else {
                        error('复制内容失败！', $back);
                    }
                    break;
                case 'move':
                    $list = post('list');
                    $scode = post('scode');
                    if (!$list) {
                        alert_back('请选择要移动的内容！');
                    }
                    if (!$scode) {
                        alert_back('请选择目标栏目！');
                    }
                    $back = admin_stay_target(url('/admin/Content/index'));
                    if ($this->model->modContent($list, "scode='" . $scode . "'")) {
                        $this->log('移动内容成功！');
                        success('移动内容成功！', $back);
                    } else {
                        error('移动内容失败！', $back);
                    }
                    break;
                case 'baiduzz':
                    $list = post('list');
                    $urls = post('urls');
                    if (!$list) {
                        alert_back('请选择要推送的内容！');
                    }
                    // 依次推送
                    $domain = get_http_url();
                    if (!$token = $this->config('baidu_zz_token')) {
                        alert_back('请先到系统配置中填写百度普通收录推送token值！');
                    }
                    $api = "http://data.zz.baidu.com/urls?site=$domain&token=$token";
                    foreach ($list as $key => $value) {
                        $url = $domain . $urls[$value];
                        $this->log('百度普通收录推送：' . $url);
                        $post_urls[] = $url;
                    }
                    $result = post_baidu($api, $post_urls);
                    if (isset($result->error)) {
                        alert_back('百度普通收录推送发生错误：' . $result->message);
                    } elseif (isset($result->success)) {
                        alert_back('成功推送' . $result->success . '条，今天剩余可推送' . $result->remain . '条数!', 1);
                    } else {
                        alert_back('发生未知错误！');
                    }
                    break;
                case 'baiduks':
                    $list = post('list');
                    $urls = post('urls');
                    if (!$list) {
                        alert_back('请选择要推送的内容！');
                    }
                    // 依次推送
                    $domain = get_http_url();
                    if (!$token = $this->config('baidu_ks_token')) {
                        alert_back('请先到系统配置中填写百度快速收录推送token值！');
                    }
                    $api = "http://data.zz.baidu.com/urls?site=$domain&token=$token&type=daily";
                    foreach ($list as $key => $value) {
                        $url = $domain . $urls[$value];
                        $this->log('百度快速收录推送：' . $url);
                        $post_urls[] = $url;
                    }
                    $result = post_baidu($api, $post_urls);
                    if (isset($result->error)) {
                        alert_back('百度快速收录推送发生错误：' . $result->message);
                    } elseif (isset($result->success_daily)) {
                        alert_back('成功推送' . $result->success_daily . '条，今天剩余可推送' . $result->remain_daily . '条数!', 1);
                    } else {
                        alert_back('发生未知错误！');
                    }
                    break;
                case 'indexnow':
                    $list = post('list');
                    $urls = post('urls');
                    if (!$list || !is_array($list) || !is_array($urls)) {
                        alert_back('请选择要推送的内容！');
                    }
                    if (!$key = get_effective_indexnow_key($this->config('indexnow_key'), $this->config('indexnow_key_location'))) {
                        alert_back('请先到系统配置中填写IndexNow推送密钥！');
                    }

                    $domain = get_http_url();
                    $expected_host = get_http_host(true);
                    $post_urls = array();
                    foreach ($list as $value) {
                        if (!isset($urls[$value])) { // 链接类型内容不输出推送地址
                            continue;
                        }
                        $post_urls[] = build_indexnow_url($domain, $urls[$value]);
                    }
                    // 以服务端当前 host 为准，丢弃客户端隐藏域篡改的异域地址
                    $post_urls = array_slice(filter_indexnow_urls($post_urls, $expected_host), 0, 10000); // 接口单次上限
                    if (!$post_urls) {
                        alert_back('所选内容中没有可推送的地址，链接类型内容不允许推送！');
                    }

                    $key_location = resolve_indexnow_key_location($key, $this->config('indexnow_key_location'), $post_urls);
                    if (! indexnow_key_location_file_ready($key, $key_location)) {
                        alert_back('密钥文件不存在或内容与推送密钥不一致，请先到系统配置保存IndexNow密钥，并检查网站根目录（或自定义密钥文件地址）写入权限！');
                    }
                    // 分批推送：单次请求体更小，单批失败也能上报已成功的部分，避免整批等待超时
                    $chunk_size = 100;
                    $ok_count = 0;
                    $fail_count = 0;
                    $fail_msg = '';
                    foreach (array_chunk($post_urls, $chunk_size) as $chunk) {
                        // ek_syslog.event 仅 varchar(200)，不能一次 implode 全部地址；按条记录
                        foreach ($chunk as $url) {
                            $this->log('IndexNow推送：' . $url);
                        }
                        $rs = post_indexnow($key, $key_location, $chunk, $expected_host);
                        if (! $rs['errno'] && ($rs['code'] == 200 || $rs['code'] == 202)) {
                            $ok_count += count($chunk);
                        } else {
                            $fail_count += count($chunk);
                            if ($fail_msg === '') {
                                $fail_msg = get_indexnow_msg($rs, count($chunk));
                            }
                        }
                    }
                    $total = count($post_urls);
                    if ($fail_count) {
                        $msg = ($ok_count ? '成功推送' . $ok_count . '条，' : '') . '失败' . $fail_count . '条。' . $fail_msg;
                        $this->log('IndexNow推送失败：共' . $total . '条，成功' . $ok_count . '条，失败' . $fail_count . '条，' . $fail_msg);
                        alert_back($msg, 0);
                    }
                    $msg = '成功推送' . $total . '条地址到IndexNow！';
                    $this->log('IndexNow推送成功：共' . $total . '条，' . $msg);
                    alert_back($msg, 1);
                    break;
            }
        }

        if (!$id = get('id', 'int')) {
            error('传递的参数值错误！', url('/admin/Content/index'));
        }

        // 单独修改状态
        $posted = admin_posted_field(array('status', 'istop', 'isrecommend'), function () use ($id) {
            return admin_stay_target(admin_model_list_fallback($id));
        });
        if ($posted) {
            $listUrl = admin_stay_target(admin_model_list_fallback($id));
            if (! $this->model->getContent($id)) {
                error('编辑的内容已经不存在！', $listUrl);
            }
            // 先取出会话数据，随后释放会话写锁，避免连续开关请求串行排队
            $operator = session('username');
            session_write_close();
            if (admin_saved($this->model->modContent($id, $posted[0] . "='" . $posted[1] . "',update_user='" . $operator . "'"), true)) {
                success('修改成功！', $listUrl);
            } else {
                error('修改失败！', $listUrl);
            }
        }

        // 修改操作
        if ($_POST) {

            // 获取数据
            $scode = post('scode');
            $subscode = post('subscode');
            $title = post('title');
            $titlecolor = post('titlecolor');
            $subtitle = post('subtitle');
            $filename = trim(post('filename'), '/');
            $author = post('author');
            $source = post('source');
            $outlink = post('outlink');
            $date = post('date');
            $ico = normalize_upload_saved_path(post('ico'));
            $pics = normalize_upload_saved_paths(post('pics'));

            // 获取多图标题
            $picstitle = post('picstitle');
            if ($picstitle) {
                $picstitle = implode(',', $picstitle);
            }

            $content = normalize_richtext_for_storage(post('content'));
            $tags = str_replace('，', ',', post('tags'));
            $enclosure = normalize_upload_saved_path(post('enclosure'));
            $keywords = post('keywords');
            $description = post('description');
            $status = post('status', 'int');
            $istop = post('istop', 'int', '', '', 0);
            $isrecommend = post('isrecommend', 'int', '', '', 0);
            $isheadline = post('isheadline', 'int', '', '', 0);

            $gid = post('gid', 'int') ?: 0;
            $gtype = post('gtype', 'int') ?: 4;
            $gnote = post('gnote');
            $contenttpl = tpl_path(post('contenttpl'));

            if (!$scode) {
                alert_back('内容分类不能为空！');
            }

            if (!$title) {
                alert_back('文章标题不能为空！');
            }

            if ($filename && !preg_match('/^[a-zA-Z0-9\-_\/]+$/', $filename)) {
                alert_back('内容URL名称只允许字母、数字、横线、下划线组成!');
            }

            if ($filename && $this->model->checkFilenameConflictId($filename, $id)) {
                alert_back('内容URL名称不能与已有内容ID重复!');
            }

            // 自动提起前一百个字符为描述
            if (!$description && isset($_POST['content'])) {
                $description = escape_string(clear_html_blank(substr_both(strip_tags($_POST['content']), 0, 150)));
            }

            // 无缩略图时，自动提取文章第一张图为缩略图
            if (!$ico && preg_match('/<img\s+.*?src=\s?[\'|\"](.*?(\.gif|\.jpg|\.jpeg|\.png|\.webp))[\'|\"].*?[\/]?>/i', decode_string($content), $srcs) && isset($srcs[1])) {
                $ico = $srcs[1];
            }

            // 缩放缩略图
            if ($ico) {
                resize_img(ROOT_PATH . $ico, '', $this->config('ico.max_width'), $this->config('ico.max_height'));
            }

            if ($filename) {
                while ($this->model->checkFilename($filename, "id<>$id")) {
                    $filename = $filename . '-' . mt_rand(1, 20);
                }
            }

            // 构建数据
            $data = array(
                'scode' => $scode,
                'subscode' => $subscode,
                'title' => $title,
                'titlecolor' => $titlecolor,
                'subtitle' => $subtitle,
                'filename' => $filename,
                'author' => $author,
                'source' => $source,
                'outlink' => $outlink,
                'date' => $date,
                'ico' => $ico,
                'pics' => $pics,
                'picstitle' => $picstitle,
                'contenttpl' => $contenttpl,
                'content' => $content,
                'tags' => $tags,
                'enclosure' => $enclosure,
                'keywords' => $keywords,
                'description' => clear_html_blank($description),
                'status' => $status,
                'istop' => $istop,
                'isrecommend' => $isrecommend,
                'isheadline' => $isheadline,
                'gid' => $gid,
                'gtype' => $gtype,
                'gnote' => $gnote,
                'update_user' => session('username')
            );

            // 执行添加
            $contentBack = admin_back_target(admin_model_list_fallback($id));
            if (! $this->model->getContent($id)) {
                error('编辑的内容已经不存在！', $contentBack);
            }
            if ($this->model->modContent($id, $data)) {
                // 扩展内容修改
                foreach ($_POST as $key => $value) {
                    if (preg_match('/^ext_[\w\-]+$/', $key)) {
                        $temp = post($key);
                        if (is_array($temp)) {
                            $data2[$key] = implode(',', $temp);
                        } else {
                            $data2[$key] = str_replace("\r\n", '<br>', normalize_richtext_for_storage($temp));
                        }
                    }
                }
                if (isset($data2)) {
                    if ($this->model->findContentExt($id)) {
                        $this->model->modContentExt($id, $data2);
                    } else {
                        $data2['contentid'] = $id;
                        $this->model->addContentExt($data2);
                    }
                }

                $this->log('修改文章' . $id . '成功！');
                $msg = '修改成功！';
                $syncHtmls = array($content);
                if (isset($data2)) {
                    foreach ($data2 as $__k => $__v) {
                        if (strpos($__k, 'ext_') === 0 && is_string($__v)) {
                            $syncHtmls[] = $__v;
                        }
                    }
                }
                if ($notice = iframe_whitelist_sync_notice_many($syncHtmls, '修改成功！')) {
                    $msg = $notice;
                }
                success($msg, $contentBack);
            } else {
                success('修改成功！', $contentBack);
            }
        } else {
            // 调取修改内容
            $this->assign('mod', true);
            if (!$result = $this->model->getContent($id)) {
                error('编辑的内容已经不存在！', admin_back_target(admin_model_list_fallback($id)));
            }
            $this->assign('content', $result);

            if (!$mcode = get('mcode', 'var')) {
                error('传递的模型编码参数有误，请核对后重试！');
            }

            // 文章分类
            $sort_model = model('admin.content.ContentSort');
            $sort_select = $sort_model->getListSelect($mcode);
            $this->assign('sort_select', $this->makeSortSelect($sort_select, $result->scode));
            $this->assign('subsort_select', $this->makeSortSelect($sort_select, $result->subscode));

            // 模型名称
            $this->assign('model_name', model('admin.content.Model')->getName($mcode));

            // 扩展字段
            $this->assign('extfield', model('admin.content.ExtField')->getModelField($mcode));

            // 获取会员分组
            $this->assign('groups', model('admin.member.MemberGroup')->getSelect());

            $this->assign('ai_show_assistant', '1');

            $this->assignTpls();

            $this->display('content/content.html');
        }
    }
}