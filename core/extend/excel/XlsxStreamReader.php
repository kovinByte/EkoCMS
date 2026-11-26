<?php
/**
 *   轻量级 xlsx 流式读取器
 *
 * 仅依赖 ZipArchive + XMLReader，按行读取第一个工作表，避免将整张表载入内存。
 * 支持 sharedStrings、inlineStr、富文本、稀疏单元格、缺失 r 属性等常见情况。
 */

namespace core\extend\excel;

use ZipArchive;
use XMLReader;

class XlsxStreamReader
{

    // xlsx 文件路径
    private $file;

    // 读取过程中产生的临时文件
    private $tempFiles = array();

    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * 逐行读取第一个工作表的有效行
     *
     * @throws \Exception
     * @return \Generator
     */
    public function rows()
    {
        if (! class_exists('ZipArchive')) {
            throw new \Exception('服务器未开启 ZipArchive 扩展，无法解析 xlsx 文件！');
        }
        if (! class_exists('XMLReader')) {
            throw new \Exception('服务器未开启 XMLReader 扩展，无法解析 xlsx 文件！');
        }

        $zip = new ZipArchive();
        if ($zip->open($this->file) !== true) {
            throw new \Exception('xlsx 文件无法打开，请确认文件未损坏！');
        }

        try {
            $sheetEntry = $this->resolveSheetEntry($zip);
            if (! $sheetEntry) {
                throw new \Exception('xlsx 文件中未找到可读取的工作表！');
            }
            $shared = $this->loadSharedStrings($zip);
            $sheetTmp = $this->extractEntry($zip, $sheetEntry);
        } finally {
            $zip->close();
        }

        if (! $sheetTmp) {
            throw new \Exception('xlsx 工作表提取失败！');
        }

        try {
            yield from $this->readSheet($sheetTmp, $shared);
        } finally {
            $this->cleanTempFiles();
        }
    }

    // 解析出第一个工作表在压缩包中的路径
    private function resolveSheetEntry($zip)
    {
        $rid = '';
        $workbook = $zip->getFromName('xl/workbook.xml');
        if ($workbook && preg_match('/<(?:\w+:)?sheet\b[^>]*\br:id="([^"]+)"/i', $workbook, $m)) {
            $rid = $m[1];
        }

        $target = '';
        if ($rid) {
            $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($rels && preg_match_all('/<Relationship\b[^>]*>/i', $rels, $items)) {
                foreach ($items[0] as $item) {
                    if (preg_match('/\bId="' . preg_quote($rid, '/') . '"/i', $item) && preg_match('/\bTarget="([^"]+)"/i', $item, $t)) {
                        $target = $t[1];
                        break;
                    }
                }
            }
        }

        if ($target) {
            $target = ltrim(str_replace('\\', '/', $target), '/');
            if (strpos($target, 'xl/') !== 0) {
                $target = 'xl/' . $target;
            }
            if ($zip->locateName($target) !== false) {
                return $target;
            }
        }

        return $zip->locateName('xl/worksheets/sheet1.xml') !== false ? 'xl/worksheets/sheet1.xml' : '';
    }

    // 载入共享字符串表
    private function loadSharedStrings($zip)
    {
        $strings = array();
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return $strings;
        }
        $tmp = $this->extractEntry($zip, 'xl/sharedStrings.xml');
        if (! $tmp) {
            return $strings;
        }

        $reader = new XMLReader();
        if (! @$reader->open($tmp)) {
            @unlink($tmp);
            return $strings;
        }
        try {
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                    $strings[] = $this->xmlText($reader->readOuterXml());
                }
            }
        } finally {
            $reader->close();
            @unlink($tmp);
        }
        return $strings;
    }

    // 流式解析工作表行
    private function readSheet($path, array $shared)
    {
        $reader = new XMLReader();
        if (! @$reader->open($path)) {
            return;
        }
        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }
                $row = $this->parseRowXml($reader->readOuterXml(), $shared);
                if ($row) {
                    yield $row;
                }
            }
        } finally {
            $reader->close();
        }
    }

    // 解析单行 XML 为索引数组
    private function parseRowXml($xml, array $shared)
    {
        if (! preg_match_all('/<(?:\w+:)?c\b([^>]*?)(?:\/>|>(.*?)<\/(?:\w+:)?c>)/s', $xml, $matches, PREG_SET_ORDER)) {
            return array();
        }

        $cells = array();
        $auto = 0;
        foreach ($matches as $match) {
            $attr = $match[1];
            $inner = isset($match[2]) ? $match[2] : '';

            // 列序号，优先使用 r="AB12" 中的字母，缺失时按顺序递增
            $col = $auto;
            if (preg_match('/\br="([A-Za-z]+)\d*"/', $attr, $rm)) {
                $col = $this->columnIndex($rm[1]);
            }
            $auto = $col + 1;

            $type = '';
            if (preg_match('/\bt="([^"]+)"/', $attr, $tm)) {
                $type = $tm[1];
            }

            $value = '';
            if ($type === 'inlineStr') {
                $value = $this->xmlText($inner);
            } elseif (preg_match('/<(?:\w+:)?v\b[^>]*>(.*?)<\/(?:\w+:)?v>/s', $inner, $vm)) {
                $raw = html_entity_decode($vm[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
                if ($type === 's') { // 共享字符串
                    $index = (int) trim($raw);
                    $value = isset($shared[$index]) ? $shared[$index] : '';
                } elseif ($type === 'b') { // 布尔
                    $value = trim($raw) === '1' ? '1' : '0';
                } else { // 普通数值、公式结果、错误值
                    $value = $raw;
                }
            }

            $cells[$col] = $value;
        }

        if (! $cells) {
            return array();
        }

        $max = max(array_keys($cells));
        $row = array();
        for ($i = 0; $i <= $max; $i ++) {
            $row[] = isset($cells[$i]) ? $cells[$i] : '';
        }
        return $row;
    }

    // 拼接 XML 片段中的文本节点（兼容富文本）
    private function xmlText($xml)
    {
        if (preg_match_all('/<(?:\w+:)?t\b[^>]*>(.*?)<\/(?:\w+:)?t>/s', $xml, $matches)) {
            $text = implode('', $matches[1]);
        } else {
            $text = strip_tags($xml);
        }
        return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    // 列字母转索引，A=>0, Z=>25, AA=>26
    private function columnIndex($letters)
    {
        $index = 0;
        $letters = strtoupper($letters);
        for ($i = 0, $len = strlen($letters); $i < $len; $i ++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    // 将压缩包中的条目以流方式复制到临时文件
    private function extractEntry($zip, $entry)
    {
        $stream = $zip->getStream($entry);
        if (! $stream) {
            return '';
        }
        $tmp = tempnam(sys_get_temp_dir(), 'pbxlsx');
        if (! $tmp) {
            fclose($stream);
            return '';
        }
        $out = fopen($tmp, 'wb');
        if (! $out) {
            fclose($stream);
            @unlink($tmp);
            return '';
        }
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);

        $this->tempFiles[] = $tmp;
        return $tmp;
    }

    // 清理临时文件
    private function cleanTempFiles()
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = array();
    }
}
