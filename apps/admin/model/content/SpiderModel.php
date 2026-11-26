<?php
/**
 *  蜘蛛统计模型类（读取 data/spider/ 下的按天文本日志）
 */
namespace app\admin\model\content;

use core\basic\Model;
use core\log\LogSpider;

class SpiderModel extends Model
{

    // 单次最多解析的记录数，避免超大日志占用过多内存
    const MAX_RECORDS = 50000;

    // 单个日志文件最多读取的字节数（超出时读取文件末尾部分）
    const MAX_FILE_BYTES = 8388608;

    // 允许查询的最大天数跨度
    const MAX_RANGE_DAYS = 31;

    // 获取可筛选的蜘蛛选项（包含记录数）
    public function getSpiderOptions(array $counts)
    {
        $labels = LogSpider::labels();
        // 将日志中出现但未预置的蜘蛛代码追加到选项末尾
        foreach ($counts as $code => $num) {
            if (! isset($labels[$code])) {
                $labels[$code] = LogSpider::label($code);
            }
        }
        $options = array();
        foreach ($labels as $code => $name) {
            $options[] = (object) array(
                'code' => htmlspecialchars($code, ENT_QUOTES, 'UTF-8'),
                'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                'count' => isset($counts[$code]) ? (int) $counts[$code] : 0
            );
        }
        return $options;
    }

    // 读取蜘蛛访问记录
    public function getRecords(array $query)
    {
        $spiders = isset($query['spiders']) && is_array($query['spiders']) ? $query['spiders'] : array();
        $spiders = array_values(array_unique(array_filter(array_map('strval', $spiders))));
        $field = isset($query['field']) ? $query['field'] : 'title';
        if (! in_array($field, array('title', 'url', 'ip'), true)) {
            $field = 'title';
        }
        $keyword = isset($query['keyword']) ? trim((string) $query['keyword']) : '';

        list($start, $end) = $this->normalizeRange(isset($query['start']) ? $query['start'] : '', isset($query['end']) ? $query['end'] : '');

        $base = DOC_PATH . DATA_DIR . '/spider';
        $rows = array();
        $counts = array();
        $total = 0;

        $day = $end;
        $days = 0;
        while (strtotime($day) !== false && $day >= $start && $days < self::MAX_RANGE_DAYS) {
            $file = $base . '/' . date('Ymd', strtotime($day)) . '.log';
            if (is_file($file)) {
                $this->readDayFile($file, $base, $field, $keyword, $spiders, $rows, $counts);
                if (count($rows) >= self::MAX_RECORDS) {
                    break;
                }
            }
            $day = date('Y-m-d', strtotime($day . ' -1 day'));
            $days++;
        }

        $total = count($rows);
        $rows = $this->sortRecords($rows, isset($query['order']) ? $query['order'] : 'time', isset($query['dir']) ? $query['dir'] : 'desc');

        return array(
            'rows' => $rows,
            'total' => $total,
            'counts' => $counts,
            'start' => $start,
            'end' => $end
        );
    }

    // 读取并解析一天的日志
    private function readDayFile($file, $base, $field, $keyword, array $spiders, array &$rows, array &$counts)
    {
        $baseReal = realpath($base);
        $real = realpath($file);
        if ($baseReal === false || $real === false || strpos($real, $baseReal . DIRECTORY_SEPARATOR) !== 0) {
            return;
        }

        $fp = @fopen($real, 'rb');
        if (! $fp) {
            return;
        }

        // 超大文件仅读取末尾部分，优先保留较新的记录
        $size = filesize($real);
        if ($size > self::MAX_FILE_BYTES) {
            fseek($fp, - self::MAX_FILE_BYTES, SEEK_END);
            fgets($fp); // 丢弃可能不完整的一行
        }

        while (($line = fgets($fp)) !== false) {
            if (count($rows) >= self::MAX_RECORDS) {
                break;
            }
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }
            // 正常记录固定 5 个制表符；数量不符说明是截断或拼接的损坏行
            if (substr_count($line, "\t") !== 5) {
                continue;
            }
            $cols = explode("\t", $line, 6);
            list($time, $code, $ip, $source, $title, $url) = $cols;

            // 关键词筛选（先于蜘蛛筛选计数，便于展示可选数量）
            if ($keyword !== '') {
                $haystack = $field === 'url' ? $url : ($field === 'ip' ? $ip : $title);
                if (stripos($haystack, $keyword) === false) {
                    continue;
                }
            }

            if (! isset($counts[$code])) {
                $counts[$code] = 0;
            }
            $counts[$code]++;

            if ($spiders && ! in_array($code, $spiders, true)) {
                continue;
            }

            // 当天记录仅显示时分秒，历史记录显示完整日期时间
            $timeDisplay = (substr($time, 0, 10) === date('Y-m-d')) ? substr($time, 11) : $time;

            $rows[] = (object) array(
                'time' => htmlspecialchars($time, ENT_QUOTES, 'UTF-8'),
                'time_display' => htmlspecialchars($timeDisplay, ENT_QUOTES, 'UTF-8'),
                'spider' => htmlspecialchars($code, ENT_QUOTES, 'UTF-8'),
                'spider_name' => htmlspecialchars(LogSpider::label($code), ENT_QUOTES, 'UTF-8'),
                'ip' => htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'),
                'source' => htmlspecialchars($source, ENT_QUOTES, 'UTF-8'),
                'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'url' => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                'url_safe' => preg_match('/^(https?:\/\/|\/)/i', $url) === 1
            );
        }
        fclose($fp);
    }

    // 记录排序
    private function sortRecords(array $rows, $order, $dir)
    {
        $dir = strtolower($dir) === 'asc' ? 1 : -1;
        if ($order === 'url') {
            usort($rows, function ($a, $b) use ($dir) {
                return $dir * strcmp($a->url, $b->url);
            });
        } else {
            usort($rows, function ($a, $b) use ($dir) {
                $result = strcmp($a->time, $b->time);
                if ($result === 0) {
                    $result = strcmp($a->url, $b->url);
                }
                return $dir * $result;
            });
        }
        return $rows;
    }

    // 规范日期范围；未传日期时默认最近7天，单边传值时以另一边界为准
    private function normalizeRange($start, $end)
    {
        $end = $this->normalizeDate($end);
        $start = $this->normalizeDate($start);
        $startGiven = $start !== false;
        $endGiven = $end !== false;
        if (! $end) {
            $end = date('Y-m-d');
        }
        if (! $start) {
            $start = ($startGiven || $endGiven) ? $end : date('Y-m-d', strtotime($end . ' -6 day'));
        }
        if (strtotime($start) > strtotime($end)) {
            $tmp = $start;
            $start = $end;
            $end = $tmp;
        }
        $minStart = date('Y-m-d', strtotime($end . ' -' . (self::MAX_RANGE_DAYS - 1) . ' day'));
        if (strtotime($start) < strtotime($minStart)) {
            $start = $minStart;
        }
        return array($start, $end);
    }

    // 规范日期格式，支持 YYYY-MM-DD 与 YYYYMMDD
    private function normalizeDate($date)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return false;
        }
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $date, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];
        } elseif (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $date, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];
        } else {
            return false;
        }
        if (! checkdate($month, $day, $year)) {
            return false;
        }
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}
