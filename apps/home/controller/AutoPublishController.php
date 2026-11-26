<?php
/**
 *  定时发布 web 定时访问入口，访问地址：/auto-publish?token=xxx
 */
namespace app\home\controller;

use app\admin\model\system\ConfigModel;
use core\basic\Controller;
use core\basic\Model;

class AutoPublishController extends Controller
{

    public function index()
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '256M');

        header('Content-Type: application/json; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            json(0, '请使用 GET 方式访问定时发布地址！');
        }

        $config = new ConfigModel();

        // token 校验
        $this->verifyToken($config);

        // 生效时间段校验（每天 HH:mm 至 HH:mm，留空表示不限制，结束早于开始表示跨天）
        $now = time();
        $start = $this->timeOfDay($config->getValue('auto_publish_start'));
        $end = $this->timeOfDay($config->getValue('auto_publish_end'));
        if ($start === false) {
            json(0, '定时发布开始时间配置有误，请重新保存配置！');
        }
        if ($end === false) {
            json(0, '定时发布结束时间配置有误，请重新保存配置！');
        }
        if ($start !== '' || $end !== '') {
            $now_hm = (int) date('Hi', $now);
            $start_hm = $start !== '' ? (int) str_replace(':', '', $start) : null;
            $end_hm = $end !== '' ? (int) str_replace(':', '', $end) : null;
            if ($start_hm !== null && $end_hm !== null) {
                if ($start_hm == $end_hm) {
                    $in_range = true; // 起止相同视为全天
                } elseif ($start_hm < $end_hm) {
                    $in_range = ($now_hm >= $start_hm && $now_hm <= $end_hm);
                } else {
                    $in_range = ($now_hm >= $start_hm || $now_hm <= $end_hm); // 跨天
                }
            } elseif ($start_hm !== null) {
                $in_range = ($now_hm >= $start_hm);
            } else {
                $in_range = ($now_hm <= $end_hm);
            }
            if (! $in_range) {
                json(1, '当前不在每日生效时间段（' . ($start !== '' ? $start : '00:00') . ' - ' . ($end !== '' ? $end : '23:59') . '），本次跳过。');
            }
        }

        // 读取配置
        $interval = $this->intConfig($config, 'auto_publish_interval', 2, 1, 1440);
        $count = $this->intConfig($config, 'auto_publish_count', 1, 1, 100);
        $visits_min = $this->intConfig($config, 'auto_publish_visits_min', 100, 0, 1000000);
        $visits_max = $this->intConfig($config, 'auto_publish_visits_max', 500, 0, 1000000);
        if ($visits_max < $visits_min) {
            $visits_max = $visits_min;
        }
        // 仅配置了每日上限（大于 0）的栏目参与定时发布，每日总量为各栏目上限之和
        $sort_limit = array();
        foreach ($this->getSortLimits($config) as $scode => $limit) {
            if ($limit > 0) {
                $sort_limit[(string) $scode] = $limit;
            }
        }
        if (! $sort_limit) {
            json(1, '未配置参与定时发布的栏目（栏目每日上限需大于 0），本次跳过。');
        }
        $today = date('Y-m-d');
        $today_counts = $this->getDayCounts($config, $today);

        // 并发锁，避免同一次任务被重复执行
        if (! check_dir(RUN_PATH, true)) {
            json(0, '运行目录创建失败，请检查 runtime 目录写入权限！');
        }
        $lock_file = RUN_PATH . '/auto_publish.lock';
        $lock = @fopen($lock_file, 'c+b');
        if (! $lock) {
            json(0, '定时发布锁文件创建失败，请检查 runtime 目录写入权限！');
        }
        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            json(1, '定时发布任务正在执行中，本次跳过。');
        }

        // 发布间隔校验（读取数据库，避免配置缓存导致间隔失效）
        $last = (int) $config->getValue('auto_publish_last');
        if ($last > 0 && ($now - $last) < $interval * 60) {
            flock($lock, LOCK_UN);
            fclose($lock);
            json(1, '未到发布间隔（' . $interval . ' 分钟），本次跳过。');
        }

        try {
            $model = new Model();
            // 待发布内容涉及的栏目
            $pending_scodes = $model->table('ek_content')->field('scode')
                ->where(array('status' => '0'))
                ->group('scode')
                ->column('scode');
            if (! $pending_scodes) {
                flock($lock, LOCK_UN);
                fclose($lock);
                json(1, '没有待发布的隐藏内容，本次跳过。');
            }

            // 仅保留已配置每日上限且仍有剩余配额的栏目
            $allowed = array();
            $remaining = array();
            foreach ($pending_scodes as $scode) {
                $scode = (string) $scode;
                $limit = isset($sort_limit[$scode]) ? $sort_limit[$scode] : 0;
                if ($limit <= 0) { // 未参与定时发布
                    continue;
                }
                $used = isset($today_counts[$scode]) ? (int) $today_counts[$scode] : 0;
                if ($used < $limit) {
                    $allowed[] = $scode;
                    $remaining[$scode] = $limit - $used;
                }
            }
            if (! $allowed) {
                flock($lock, LOCK_UN);
                fclose($lock);
                json(1, '今日参与定时发布的栏目均已达上限，本次跳过。');
            }

            $rows = $model->table('ek_content')->field('id,title,scode')
                ->where(array('status' => '0', 'scode' => $allowed))
                ->order('id asc')
                ->limit($count)
                ->select();
        } catch (\Exception $e) {
            flock($lock, LOCK_UN);
            fclose($lock);
            json(0, '读取待发布内容失败：' . $e->getMessage());
        }

        if (! $rows) {
            flock($lock, LOCK_UN);
            fclose($lock);
            json(1, '没有待发布的隐藏内容，本次跳过。');
        }

        $published = array();
        $published_scodes = array();
        $failed = 0;
        $update_time = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $scode = (string) $row->scode;
            // 单次执行内同样不能超出栏目剩余配额
            if (! isset($remaining[$scode]) || $remaining[$scode] <= 0) {
                continue;
            }
            $visits = mt_rand($visits_min, $visits_max);
            try {
                $result = $model->table('ek_content')
                    ->where(array('id' => $row->id))
                    ->update(array(
                    'status' => '1',
                    'visits' => (string) $visits,
                    'update_time' => $update_time
                ));
            } catch (\Exception $e) {
                $result = false;
            }
            if ($result) {
                $remaining[$scode] --;
                $today_counts[$scode] = isset($today_counts[$scode]) ? (int) $today_counts[$scode] + 1 : 1;
                $published_scodes[$scode] = $today_counts[$scode];
                $published[] = array(
                    'id' => $row->id,
                    'title' => $row->title,
                    'visits' => $visits
                );
            } else {
                $failed ++;
            }
        }

        // 记录本次发布时间及当日各栏目发布计数
        if ($published) {
            foreach ($published_scodes as $scode => $count) {
                $this->saveConfig($config, 'ap_day_' . $scode, $today . '|' . (int) $count, '定时发布当日栏目计数');
            }
            $this->saveConfig($config, 'auto_publish_last', (string) $now);
        }

        flock($lock, LOCK_UN);
        fclose($lock);

        json(1, array(
            'published' => count($published),
            'failed' => $failed,
            'items' => $published,
            'msg' => '本次成功发布 ' . count($published) . ' 篇' . ($failed ? '，失败 ' . $failed . ' 篇' : '') . '。'
        ));
    }

    // 配额查询接口：暴露每日总量与隐藏内容数量
    public function stat()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            json(0, '请使用 GET 方式访问配额接口！');
        }

        $config = new ConfigModel();
        $this->verifyToken($config);

        // 每日总量为所有参与栏目每日上限之和
        $daily_total = 0;
        foreach ($this->getSortLimits($config) as $limit) {
            if ($limit > 0) {
                $daily_total += $limit;
            }
        }

        try {
            $model = new Model();
            $hidden_count = (int) $model->table('ek_content')
                ->where(array('status' => '0'))
                ->count();
        } catch (\Exception $e) {
            json(0, '统计数据读取失败：' . $e->getMessage());
        }

        json(1, array(
            'daily_total' => $daily_total,
            'hidden_count' => $hidden_count
        ));
    }

    // 校验定时访问 token
    private function verifyToken($config)
    {
        $token = trim((string) get('token', 'var'));
        $saved_token = trim((string) $config->getValue('auto_publish_token'));
        if (! preg_match('/^[a-f0-9]{32}$/', $saved_token)) {
            json(0, '定时发布尚未配置，请先在后台保存配置！');
        }
        if ($token === '' || ! hash_equals($saved_token, $token)) {
            json(0, 'token 校验失败，请核对定时访问地址！');
        }
        return $saved_token;
    }

    // 读取整数配置，未配置时使用默认值
    private function intConfig($config, $name, $default, $min, $max)
    {
        $value = $config->getValue($name);
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

    // 读取每天的时:分，空值返回空字符串，格式错误返回 false（兼容旧版完整日期时间）
    private function timeOfDay($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
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

    // 读取栏目每日上限（按栏目拆分存储，兼容旧 JSON 字段）
    private function getSortLimits($config)
    {
        $result = array();
        $rows = $config->getValueByPrefix('ap_limit_');
        foreach ($rows as $name => $limit) {
            $scode = substr($name, strlen('ap_limit_'));
            if ($scode === '') {
                continue;
            }
            $result[$scode] = (int) $limit;
        }
        if (! $result) {
            $result = $this->parseSortLimit($config->getValue('auto_publish_sort_limit'));
        }
        return $result;
    }

    // 读取当日各栏目已发布数量（ap_day_scode = 日期|数量，兼容旧 JSON 字段）
    private function getDayCounts($config, $today)
    {
        $counts = array();
        $rows = $config->getValueByPrefix('ap_day_');
        foreach ($rows as $name => $value) {
            $scode = substr($name, strlen('ap_day_'));
            $parts = explode('|', (string) $value);
            if ($scode === '' || count($parts) !== 2 || $parts[0] !== $today) {
                continue;
            }
            $counts[$scode] = max(0, (int) $parts[1]);
        }
        if (! $rows) {
            $day_state = $this->parseDayState($config->getValue('auto_publish_day_state'), $today);
            $counts = $day_state['counts'];
        }
        return $counts;
    }

    // 解析栏目每日上限配置，返回 scode => 上限
    private function parseSortLimit($value)
    {
        $result = array();
        $data = json_decode((string) $value, true);
        if (is_array($data)) {
            foreach ($data as $scode => $limit) {
                if ($scode === '' || ! is_scalar($limit)) {
                    continue;
                }
                $limit = (int) $limit;
                if ($limit < 0) {
                    $limit = 0;
                }
                if ($limit > 10000) {
                    $limit = 10000;
                }
                $result[(string) $scode] = $limit;
            }
        }
        return $result;
    }

    // 解析当日发布计数，跨天自动归零
    private function parseDayState($value, $today)
    {
        $counts = array();
        $data = json_decode((string) $value, true);
        if (is_array($data) && isset($data['date']) && $data['date'] === $today && isset($data['counts']) && is_array($data['counts'])) {
            foreach ($data['counts'] as $scode => $number) {
                if (! is_scalar($number)) {
                    continue;
                }
                $counts[(string) $scode] = max(0, (int) $number);
            }
        }
        return array(
            'date' => $today,
            'counts' => $counts
        );
    }

    // 写入配置值
    private function saveConfig($config, $name, $value, $description = '定时发布')
    {
        if ($config->checkConfig("name='" . $name . "'")) {
            $config->modValue($name, $value);
        } else {
            $config->addConfig(array(
                'name' => $name,
                'value' => $value,
                'type' => 2,
                'sorting' => 255,
                'description' => $description
            ));
        }
    }
}
