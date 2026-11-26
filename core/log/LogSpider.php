<?php
/**
 *  蜘蛛访问专用文本日志驱动（与系统日志数据库隔离）
 */
namespace core\log;

use core\basic\Config;

class LogSpider
{

    protected static $logSpider;

    // 蜘蛛代码 => 中文名称
    private static $labels = array(
        'Baidu' => '百度',
        'Google' => '谷歌',
        'Sogou' => '搜狗',
        'Bing' => '必应',
        'Yahoo' => '雅虎',
        'Soso' => '搜搜',
        '360So' => '360搜索',
        'Yodao' => '有道',
        'Byte' => '头条',
        'Shenma' => '神马',
        'Yisou' => '一搜',
        'Easou' => '宜搜',
        'Adwords' => '谷歌广告',
        'Yandex' => 'Yandex',
        'other-spider' => '其他蜘蛛',
        'other-bot' => '其他Bot'
    );

    private function __construct()
    {}

    // 用于获取单一实例
    public static function getInstance()
    {
        if (! self::$logSpider) {
            self::$logSpider = new self();
        }
        return self::$logSpider;
    }

    /**
     * 写入蜘蛛访问日志
     *
     * @param string $spider 爬虫名称
     * @param string $url 访问 URL
     * @param string $title 页面标题
     * @return bool|int
     */
    public function write($spider, $url, $title = '')
    {
        if (Config::get('spiderlog') === '0') {
            return false;
        }
        // 随 DATA_DIR 备份且受 data 访问拒绝保护，按天存放：data/spider/20261124.log
        $dir = DOC_PATH . DATA_DIR . '/spider';
        // 仅确保目录存在；文件由 fopen('ab') 原子创建，避免 check_file/create_file 的 w 截断竞态
        if (! check_dir($dir, true)) {
            return false;
        }
        $logfile = $dir . '/' . date('Ymd') . '.log';
        $line = $this->formatLine($spider, $url, $title);
        return $this->appendWithLock($logfile, $line);
    }

    // 格式化单行日志（制表符分隔，便于追踪）：时间、蜘蛛、IP、来源、标题、地址
    private function formatLine($spider, $url, $title)
    {
        $source = is_mobile() ? '移动端' : '电脑端';
        return get_datetime() . "\t" . $this->sanitizeField($spider) . "\t" . get_user_ip() . "\t"
            . $source . "\t" . $this->sanitizeField($title) . "\t" . $this->sanitizeField($url) . PHP_EOL;
    }

    // 去除换行/制表符，避免破坏日志格式
    private function sanitizeField($value)
    {
        return str_replace(array("\r", "\n", "\t"), ' ', trim((string) $value));
    }

    // 带文件锁追加写入；ab 模式在文件不存在时原子创建且不截断
    private function appendWithLock($path, $content)
    {
        $fp = @fopen($path, 'ab');
        if (! $fp) {
            return false;
        }
        // 阻塞式独占锁：并发请求在此排队，保证每条记录整行落盘、不会互相覆盖
        if (! flock($fp, LOCK_EX)) {
            fclose($fp);
            return false;
        }
        // 单次 fwrite 可能只写入部分内容（磁盘满、I/O 错误等），循环写完
        $length = strlen($content);
        $offset = 0;
        while ($offset < $length) {
            $written = @fwrite($fp, substr($content, $offset));
            if ($written === false || $written === 0) {
                break;
            }
            $offset += $written;
        }
        // 释放锁前刷出缓冲，避免下个持锁进程先落盘导致行序错乱
        $result = ($offset === $length) && @fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return $result;
    }

    // 获取蜘蛛代码与名称映射
    public static function labels()
    {
        return self::$labels;
    }

    // 获取蜘蛛中文名称，未知代码原样返回
    public static function label($code)
    {
        return isset(self::$labels[$code]) ? self::$labels[$code] : (string) $code;
    }
}
