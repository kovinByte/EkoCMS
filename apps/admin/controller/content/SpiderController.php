<?php
/**
 *  蜘蛛统计控制器（基于 data/spider/ 按天日志）
 */
namespace app\admin\controller\content;

use core\basic\Controller;
use app\admin\model\content\SpiderModel;

class SpiderController extends Controller
{

    private $model;

    public function __construct()
    {
        $this->model = new SpiderModel();
    }

    // 蜘蛛访问统计列表
    public function index()
    {
        // 蜘蛛多选筛选项，仅保留合法字符
        $spiders = array();
        if (isset($_GET['spider']) && is_array($_GET['spider'])) {
            foreach ($_GET['spider'] as $spider) {
                $spider = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $spider);
                if ($spider !== '') {
                    $spiders[] = $spider;
                }
            }
        }

        $field = get('field', 'var') ?: 'title';
        if (! in_array($field, array('title', 'url', 'ip'), true)) {
            $field = 'title';
        }

        $order = get('order', 'var') ?: 'time';
        if (! in_array($order, array('time', 'url'), true)) {
            $order = 'time';
        }
        $dir = strtolower(get('dir', 'var') ?: 'desc');
        if (! in_array($dir, array('asc', 'desc'), true)) {
            $dir = 'desc';
        }

        // 关键词按原始值检索，输出时统一转义
        $keyword = isset($_GET['keyword']) && is_string($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $keyword = str_replace(array("\0", "\r", "\n", "\t"), ' ', $keyword);
        if (mb_strlen($keyword) > 100) {
            $keyword = mb_substr($keyword, 0, 100);
        }

        $query = array(
            'spiders' => $spiders,
            'field' => $field,
            'keyword' => $keyword,
            'start' => get('start', 'var') ?: '',
            'end' => get('end', 'var') ?: '',
            'order' => $order,
            'dir' => $dir
        );

        $data = $this->model->getRecords($query);

        // 生成分页并截取当前页数据
        $limit = $this->page($data['total']);
        list ($offset, $size) = array_map('intval', explode(',', $limit));
        $records = array_slice($data['rows'], $offset, $size);

        // 蜘蛛筛选项附加选中状态
        $selected = array_flip($spiders);
        $options = $this->model->getSpiderOptions($data['counts']);
        foreach ($options as $option) {
            $option->checked = isset($selected[$option->code]);
        }

        // 排序链接
        $baseParams = $_GET;
        unset($baseParams['page'], $baseParams['order'], $baseParams['dir']);
        $timeDir = ($order === 'time' && $dir === 'desc') ? 'asc' : 'desc';
        $urlDir = ($order === 'url' && $dir === 'asc') ? 'desc' : 'asc';

        $this->assign('spider_options', $options);
        $this->assign('selected_count', count($spiders));
        $this->assign('records', $records);
        $this->assign('total', $data['total']);
        $this->assign('start', $data['start']);
        $this->assign('end', $data['end']);
        $this->assign('field', $field);
        $this->assign('keyword', htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'));
        $this->assign('order', $order);
        $this->assign('dir', $dir);
        $this->assign('time_url', $this->buildUrl('/admin/Spider/index', array_merge($baseParams, array('order' => 'time', 'dir' => $timeDir))));
        $this->assign('url_url', $this->buildUrl('/admin/Spider/index', array_merge($baseParams, array('order' => 'url', 'dir' => $urlDir))));
        $this->assign('clear_url', url('/admin/Spider/index'));

        $this->display('content/spider.html');
    }

    // 在任意 URL 模式下合并查询参数
    private function buildUrl($path, array $params)
    {
        $url = url($path);
        $query = http_build_query($params);
        if ($query === '') {
            return $url;
        }
        return $url . (strpos($url, '?') === false ? '?' : '&') . $query;
    }
}
