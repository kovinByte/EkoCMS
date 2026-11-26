<?php
/**
 *  定时发布配置控制器（扩展内容）
 */
namespace app\admin\controller\content;

use core\basic\Controller;
use app\admin\model\system\ConfigModel;

class AutoPublishController extends Controller
{

    private $model;

    public function __construct()
    {
        $this->model = new ConfigModel();
    }

    // 定时发布配置页面
    public function index()
    {
        $this->assign('auto_publish', $this->getConfig(true));
        $this->display('content/autopublish.html');
    }

    // 保存定时发布配置
    public function mod()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error('请通过配置页面提交保存！', url('/admin/AutoPublish/index'));
        }

        $token = trim((string) post('token', 'var'));
        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            $token = bin2hex(random_bytes(16));
        }

        // 生效时间为每天的时:分，如 08:30
        $start = $this->normalizeTimeOfDay(post('start', 'var'));
        $end = $this->normalizeTimeOfDay(post('end', 'var'));
        if ($start === false || $end === false) {
            json(0, '生效时间格式有误，请使用时:分格式（如 08:30）！');
        }

        $interval = $this->intPost('interval', 2, 1, 1440);
        $count = $this->intPost('count', 1, 1, 100);
        $visits_min = $this->intPost('visits_min', 100, 0, 1000000);
        $visits_max = $this->intPost('visits_max', 500, 0, 1000000);
        if ($visits_max < $visits_min) {
            $visits_max = $visits_min;
        }
        $sort_limits = $this->parseSortLimitPost();

        $this->saveValue('auto_publish_token', $token, '定时发布访问token');
        $this->saveValue('auto_publish_start', $start, '定时发布开始时间');
        $this->saveValue('auto_publish_end', $end, '定时发布结束时间');
        $this->saveValue('auto_publish_interval', (string) $interval, '定时发布间隔（分钟）');
        $this->saveValue('auto_publish_count', (string) $count, '定时发布每次篇数');
        $this->saveValue('auto_publish_visits_min', (string) $visits_min, '定时发布随机访问量下限');
        $this->saveValue('auto_publish_visits_max', (string) $visits_max, '定时发布随机访问量上限');
        $this->saveValue('auto_publish_sort_limit', '', '定时发布栏目每日上限（兼容字段）');
        $this->saveSortLimits($sort_limits);
        if (! $this->model->checkConfig("name='auto_publish_last'")) {
            $this->saveValue('auto_publish_last', '0', '定时发布上次执行时间');
        }

        cache_config(true);
        json(1, '保存成功！');
    }

    // 读取定时发布配置
    private function getConfig($create_token = false)
    {
        $token = (string) $this->model->getValue('auto_publish_token');
        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            $token = bin2hex(random_bytes(16));
            if ($create_token) {
                $this->saveValue('auto_publish_token', $token, '定时发布访问token');
            }
        }

        $config = new \stdClass();
        $config->token = $token;
        $config->start = $this->displayTimeOfDay($this->model->getValue('auto_publish_start'));
        $config->end = $this->displayTimeOfDay($this->model->getValue('auto_publish_end'));
        $config->interval = $this->intValue($this->model->getValue('auto_publish_interval'), 2, 1, 1440);
        $config->count = $this->intValue($this->model->getValue('auto_publish_count'), 1, 1, 100);
        $config->visits_min = $this->intValue($this->model->getValue('auto_publish_visits_min'), 100, 0, 1000000);
        $config->visits_max = $this->intValue($this->model->getValue('auto_publish_visits_max'), 500, 0, 1000000);
        if ($config->visits_max < $config->visits_min) {
            $config->visits_max = $config->visits_min;
        }

        $sort_limits = $this->getSortLimits();
        $today_counts = $this->getDayCounts();
        $config->sorts = $this->getSorts($sort_limits, $today_counts);

        // 每日总量为参与栏目每日上限之和
        $day_total = 0;
        foreach ($sort_limits as $limit) {
            if ($limit > 0) {
                $day_total += $limit;
            }
        }
        $config->day_total = $day_total;
        $config->url = $this->buildUrl('/auto-publish', $token);
        $config->stat_url = $this->buildUrl('/auto-publish-stat', $token);
        return $config;
    }

    // 生成带层级、每日上限和今日已发数量的栏目列表（排除单页模型栏目）
    private function getSorts($limits, $counts)
    {
        $sorts = model('admin.content.ContentSort')->getList();
        $result = array();
        $this->flattenSorts($sorts, 0, $limits, $counts, $result);
        return $result;
    }

    private function flattenSorts($sorts, $level, $limits, $counts, &$result)
    {
        if (! $sorts) {
            return;
        }
        foreach ($sorts as $sort) {
            // 单页模型栏目不支持内容发布，不纳入定时发布配置
            if (isset($sort->type) && $sort->type == 1) {
                if (! empty($sort->son)) {
                    $this->flattenSorts($sort->son, $level, $limits, $counts, $result);
                }
                continue;
            }
            $row = new \stdClass();
            $row->scode = $sort->scode;
            $row->name = ($level ? str_repeat('— ', $level) : '') . $sort->name;
            $row->limit = isset($limits[$sort->scode]) ? $limits[$sort->scode] : '';
            $row->today = isset($counts[$sort->scode]) ? (int) $counts[$sort->scode] : 0;
            $result[] = $row;
            if (! empty($sort->son)) {
                $this->flattenSorts($sort->son, $level + 1, $limits, $counts, $result);
            }
        }
    }

    // 读取栏目每日上限（按栏目拆分存储，兼容旧 JSON 字段）
    private function getSortLimits()
    {
        $result = array();
        $rows = $this->model->getValueByPrefix('ap_limit_');
        foreach ($rows as $name => $limit) {
            $scode = substr($name, strlen('ap_limit_'));
            if ($scode === '') {
                continue;
            }
            $result[$scode] = $this->intValue($limit, 0, 0, 10000);
        }
        if (! $result) {
            $result = $this->parseSortLimit($this->model->getValue('auto_publish_sort_limit'));
        }
        return $result;
    }

    // 保存栏目每日上限，按栏目拆分为独立配置项，避免单条配置超长
    private function saveSortLimits($limits)
    {
        $this->model->delValueByPrefix('ap_limit_');
        foreach ($limits as $scode => $limit) {
            if ($limit <= 0) { // 0 或留空表示不参与定时发布
                continue;
            }
            $this->model->addConfig(array(
                'name' => 'ap_limit_' . $scode,
                'value' => (string) $limit,
                'type' => 2,
                'sorting' => 255,
                'description' => '定时发布栏目每日上限'
            ));
        }
    }

    // 读取当日各栏目已发布数量（ap_day_scode = 日期|数量，兼容旧 JSON 字段）
    private function getDayCounts()
    {
        $today = date('Y-m-d');
        $counts = array();
        $rows = $this->model->getValueByPrefix('ap_day_');
        foreach ($rows as $name => $value) {
            $scode = substr($name, strlen('ap_day_'));
            $parts = explode('|', (string) $value);
            if ($scode === '' || count($parts) !== 2 || $parts[0] !== $today) {
                continue;
            }
            $counts[$scode] = max(0, (int) $parts[1]);
        }
        if (! $rows) {
            $counts = $this->parseDayCount($this->model->getValue('auto_publish_day_state'));
        }
        return $counts;
    }

    // 解析栏目每日上限（存储值为 scode => 上限）
    private function parseSortLimit($value)
    {
        $result = array();
        $data = json_decode((string) $value, true);
        if (is_array($data)) {
            foreach ($data as $scode => $limit) {
                if ($scode === '' || ! is_scalar($limit)) {
                    continue;
                }
                $result[(string) $scode] = $this->intValue($limit, 0, 0, 10000);
            }
        }
        return $result;
    }

    // 解析提交的栏目每日上限，留空或 0 表示该栏目不参与定时发布
    private function parseSortLimitPost()
    {
        $posted = post('sort_limits', 'array');
        $result = array();
        if (! is_array($posted)) {
            return $result;
        }
        foreach ($posted as $scode => $limit) {
            $scode = trim((string) $scode);
            if ($scode === '' || ! preg_match('/^[a-zA-Z0-9_\-]+$/', $scode)) {
                continue;
            }
            $limit = trim((string) $limit);
            if ($limit === '' || ! preg_match('/^\d+$/', $limit)) {
                continue;
            }
            $result[$scode] = $this->intValue($limit, 0, 0, 10000);
        }
        return $result;
    }

    // 读取当日各栏目已发布数量
    private function parseDayCount($value)
    {
        $counts = array();
        $data = json_decode((string) $value, true);
        if (is_array($data) && isset($data['date']) && $data['date'] === date('Y-m-d') && isset($data['counts']) && is_array($data['counts'])) {
            foreach ($data['counts'] as $scode => $number) {
                if (! is_scalar($number)) {
                    continue;
                }
                $counts[(string) $scode] = max(0, (int) $number);
            }
        }
        return $counts;
    }

    // 保存单个配置项
    private function saveValue($name, $value, $description)
    {
        if ($this->model->checkConfig("name='" . $name . "'")) {
            return $this->model->modValue($name, $value);
        }
        return $this->model->addConfig(array(
            'name' => $name,
            'value' => (string) $value,
            'type' => 2,
            'sorting' => 255,
            'description' => $description
        ));
    }

    // 生成外部访问地址
    private function buildUrl($path, $token)
    {
        $https = (! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        $protocol = $https ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $dir = defined('SITE_INDEX_DIR') ? rtrim(SITE_INDEX_DIR, '/') : '';
        return $protocol . $host . $dir . $path . '?token=' . $token;
    }

    // 读取整数配置值
    private function intValue($value, $default, $min, $max)
    {
        if ($value === null || $value === '') {
            $value = $default;
        }
        $value = (int) $value;
        if ($value < $min) {
            $value = $min;
        }
        if ($value > $max) {
            $value = $max;
        }
        return $value;
    }

    // 读取提交的整数值
    private function intPost($name, $default, $min, $max)
    {
        return $this->intValue(post($name, 'var'), $default, $min, $max);
    }

    // 归一化每天的时:分（支持 8:30/08:30，兼容旧版完整日期时间）
    private function normalizeTimeOfDay($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        // 兼容旧版保存的完整日期时间，仅取时分
        if (strpos($value, '-') !== false || strpos($value, ' ') !== false) {
            $ts = strtotime($value);
            if ($ts !== false) {
                $value = date('H:i', $ts);
            }
        }
        if (! preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $value, $match)) {
            return false;
        }
        return sprintf('%02d:%02d', (int) $match[1], (int) $match[2]);
    }

    // 页面展示用的时:分，旧数据自动转换，异常值原样返回
    private function displayTimeOfDay($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $normalized = $this->normalizeTimeOfDay($value);
        return $normalized === false ? $value : $normalized;
    }
}
