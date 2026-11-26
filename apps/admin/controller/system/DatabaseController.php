<?php
/**
 *  数据库管理,只支持MySQL
 */
namespace app\admin\controller\system;

use core\basic\Controller;
use app\admin\model\system\DatabaseModel;

class DatabaseController extends Controller
{

    private $model;

    private $dbauth;

    function __construct()
    {
        $this->model = new DatabaseModel();
        $this->dbauth = $this->config('database');
    }

    // 数据库管理
    public function index()
    {
        $this->prepareView('t1');
        $this->display('system/database.html');
    }

    // 数据库互转
    public function convert()
    {
        if (! $_POST) {
            $this->prepareView('t2');
            $this->display('system/database.html');
            return;
        }

        if (! check_level('mod') && session('id') != 1) {
            alert_back('您没有数据库互转权限！');
        }

        $action = isset($_POST['convert_action']) ? $_POST['convert_action'] : '';
        switch ($action) {
            case 'start':
                $this->convertStart();
                break;
            case 'step':
                $this->convertStep();
                break;
            default:
                json(0, '请求参数错误！');
        }
    }

    // 开始转换任务：校验参数、写入表结构并统计记录数
    private function convertStart()
    {
        $type = get_db_type();
        if (! in_array($type, array(
            'mysql',
            'sqlite'
        ), true)) {
            json(0, '当前数据库类型不支持互转！');
        }
        if ($type == 'mysql' && ! extension_loaded('SQLite3')) {
            json(0, '服务器未开启 SQLite3 扩展，无法生成 SQLite 数据库文件！');
        }

        list ($file, $relativeFile) = $this->resolveTargetFile($type);

        set_time_limit(0);
        $converter = new \app\admin\model\system\DatabaseConvertModel();
        try {
            if ($type == 'sqlite') {
                $state = $converter->initSqliteToMysql($file);
            } else {
                $state = $converter->initMysqlToSqlite($file);
            }
        } catch (\Throwable $e) {
            @unlink($file);
            $this->log('数据库互转失败：' . $e->getMessage());
            json(0, '转换失败：' . $e->getMessage());
        }

        $state['relative_file'] = $relativeFile;
        $state['user'] = session('id');

        $job = get_uniqid();
        $stateFile = $this->jobDir() . '/job_' . $job . '.json';
        $content = json_encode($state, JSON_UNESCAPED_UNICODE);
        if ($content === false || @file_put_contents($stateFile, $content) === false) {
            @unlink($file);
            json(0, '无法保存转换任务状态，请检查 runtime 目录权限！');
        }

        $this->cleanupJobs();

        json(1, array(
            'job' => $job,
            'progress' => $converter->progress($state)
        ));
    }

    // 执行一步转换任务
    private function convertStep()
    {
        $job = isset($_POST['job']) && is_string($_POST['job']) ? strtolower(trim($_POST['job'])) : '';
        if (! preg_match('/^[a-f0-9]{32}$/', $job)) {
            json(0, '转换任务标识不正确！');
        }

        $stateFile = $this->jobDir() . '/job_' . $job . '.json';
        if (! is_file($stateFile)) {
            json(0, '转换任务不存在或已结束，请重新发起转换！');
        }

        $fp = @fopen($stateFile, 'c+');
        if (! $fp) {
            json(0, '无法读取转换任务状态！');
        }
        if (! flock($fp, LOCK_EX)) {
            fclose($fp);
            json(0, '转换任务处理中，请稍后重试！');
        }

        $state = json_decode(stream_get_contents($fp), true);
        if (! is_array($state) || empty($state['direction'])) {
            flock($fp, LOCK_UN);
            fclose($fp);
            @unlink($stateFile);
            json(0, '转换任务状态异常，请重新发起转换！');
        }
        if (isset($state['user']) && $state['user'] != session('id')) {
            flock($fp, LOCK_UN);
            fclose($fp);
            json(0, '您无权操作该转换任务！');
        }

        set_time_limit(0);
        $converter = new \app\admin\model\system\DatabaseConvertModel();
        try {
            $converter->step($state);
        } catch (\Throwable $e) {
            flock($fp, LOCK_UN);
            fclose($fp);
            @unlink($stateFile);
            if (! empty($state['file'])) {
                @unlink($state['file']);
            }
            $this->log('数据库互转失败：' . $e->getMessage());
            json(0, '转换失败：' . $e->getMessage());
        }

        // 保存最新的任务进度
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($state, JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $progress = $converter->progress($state);
        if (! empty($state['finished'])) {
            @unlink($stateFile);
            if (isset($state['relative_file'])) {
                $this->log('数据库互转成功：' . $state['relative_file']);
            }
        }

        json(1, $progress);
    }

    // 校验并计算转换目标文件路径
    private function resolveTargetFile($type)
    {
        // 保存目录，仅允许站点根目录下的相对路径
        $dirInput = isset($_POST['save_dir']) && is_string($_POST['save_dir']) ? $_POST['save_dir'] : '';
        if (trim($dirInput) === '') {
            $dirInput = 'data/backup/sql';
        }
        $relativeDir = $this->normalizeSaveDir($dirInput);
        if ($relativeDir === false) {
            json(0, '保存目录不合法，请填写站点根目录下的相对路径！');
        }
        $saveDir = ROOT_PATH . ($relativeDir === '' ? '' : '/' . $relativeDir);
        if (! check_dir($saveDir, true) || ! is_dir($saveDir) || ! is_writable($saveDir)) {
            json(0, '保存目录创建失败或不可写！');
        }
        $realRoot = realpath(ROOT_PATH);
        $realDir = realpath($saveDir);
        if ($realRoot === false || $realDir === false || strpos($realDir . DIRECTORY_SEPARATOR, $realRoot . DIRECTORY_SEPARATOR) !== 0) {
            json(0, '保存目录必须位于站点根目录内！');
        }

        // 文件名称
        $ext = $type == 'sqlite' ? 'sql' : 'db';
        $nameInput = isset($_POST['save_name']) && is_string($_POST['save_name']) ? trim($_POST['save_name']) : '';
        if ($nameInput === '') {
            $fileName = 'ekocms_' . ($type == 'sqlite' ? 'mysql' : 'sqlite') . '_' . date('YmdHis') . '.' . $ext;
        } else {
            $fileName = basename(str_replace('\\', '/', $nameInput));
            $fileName = preg_replace('/\.(sql|db|sqlite|sqlite3)$/i', '', $fileName);
            if (! preg_match('/^[\x{4e00}-\x{9fa5}\w\-\.]+$/u', $fileName) || $fileName === '.' || $fileName === '..') {
                json(0, '文件名称不合法！');
            }
            $fileName .= '.' . $ext;
        }

        $file = rtrim($saveDir, '/\\') . '/' . $fileName;
        if (file_exists($file)) {
            json(0, '目标文件已存在，请更换文件名或转换目录！');
        }

        $relativeFile = ($relativeDir === '' ? '' : $relativeDir . '/') . $fileName;
        return array(
            $file,
            $relativeFile
        );
    }

    // 转换任务状态目录
    private function jobDir()
    {
        $dir = RUN_PATH . '/convert';
        if (! check_dir($dir, true)) {
            json(0, '无法创建转换任务目录，请检查 runtime 目录权限！');
        }
        return $dir;
    }

    // 清理超时未完成的转换任务
    private function cleanupJobs()
    {
        $files = glob($this->jobDir() . '/job_*.json');
        if (! $files) {
            return;
        }
        $expire = time() - 7200;
        $realRoot = realpath(ROOT_PATH);
        foreach ($files as $stateFile) {
            if (@filemtime($stateFile) >= $expire) {
                continue;
            }
            $state = json_decode((string) @file_get_contents($stateFile), true);
            if (is_array($state) && empty($state['finished']) && ! empty($state['file']) && $realRoot !== false) {
                $realFile = realpath($state['file']);
                if ($realFile !== false && strpos($realFile, $realRoot . DIRECTORY_SEPARATOR) === 0) {
                    @unlink($realFile);
                }
            }
            @unlink($stateFile);
        }
    }

    // 分配数据库页面数据
    private function prepareView($activeTab)
    {
        switch ($this->dbauth['type']) {
            case 'mysqli':
            case 'pdo_mysql':
                $this->assign('db', 'mysql');
                $this->assign('tables', $this->model->getList());
                break;
            case 'sqlite':
            case 'pdo_sqlite':
                $this->assign('db', 'sqlite');
                break;
            default:
                error('当前配置的数据库类型不支持在线管理！');
        }
        $this->assign('active_tab', $activeTab);
        $this->assign('save_dir', 'data/backup/sql/');
    }

    // 校验转换文件保存目录
    private function normalizeSaveDir($dir)
    {
        $dir = str_replace('\\', '/', trim((string) $dir));
        $dir = preg_replace('/[\x00-\x1F\x7F]/', '', $dir);
        $dir = preg_replace('#/+#', '/', $dir);
        $dir = trim($dir, '/');
        if ($dir === '') {
            return '';
        }
        if (preg_match('/^[A-Za-z]:/', $dir) || strpos($dir, '://') !== false) {
            return false;
        }
        $parts = explode('/', $dir);
        foreach ($parts as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                return false;
            }
            if (! preg_match('/^[\x{4e00}-\x{9fa5}\w\-\.]+$/u', $part)) {
                return false;
            }
        }
        return implode('/', $parts);
    }

    // 数据库修改
    public function mod()
    {
        $dbUrl = url('/admin/Database/index');
        if (! $_POST) {
            error('请使用正确方式提交！', $dbUrl);
        }
        
        $back = admin_stay_target($dbUrl);
        $submit = post('submit', 'letter', true);
        
        switch ($submit) {
            case 'yh':
                $tables = self::getTableList();
                if (! $tables)
                    alert_back('请选择数据表！');
                if ($this->model->optimize(implode(',', $tables))) {
                    // $this->log('优化数据库表成功！');
                    success('优化成功！', $back);
                } else {
                    // $this->log('优化数据库表失败！');
                    error('优化失败！', $back);
                }
                break;
            case 'xf':
                $tables = self::getTableList();
                if (! $tables)
                    alert_back('请选择数据表！');
                if ($this->model->repair(implode(',', $tables))) {
                    // $this->log('修复数据库表成功！');
                    success('修复成功！', $back);
                } else {
                    // $this->log('修复数据库表失败！');
                    error('修复失败！', $back);
                }
                break;
            case 'bf':
                $tables = self::getTableList();
                if (! $tables)
                    alert_back('请选择数据表！');
                if ($this->backupTable($tables)) {
                    $this->log('备份数据库表成功！');
                    success('备份表成功！', $back);
                } else {
                    $this->log('备份数据库表失败！');
                    error('备份失败！', $back);
                }
                break;
            case 'bfdb':
                if ($this->backupDB()) {
                    $this->log('备份数据库成功！');
                    success('备份数据库成功！', $back);
                } else {
                    $this->log('备份数据库失败！');
                    error('备份失败！', $back);
                }
                break;
            case 'bfsqlite':
                if ($this->backupSqliteDB()) {
                    $this->log('备份数据库成功！');
                    success('备份数据库成功！', $back);
                } else {
                    $this->log('备份数据库失败！');
                    error('备份失败！', $back);
                }
                break;
        }
    }

    // 备份数据表
    public function backupTable($tables)
    {
        $backdir = date('YmdHis');
        foreach ($tables as $table) {
            $sql = '';
            $sql .= $this->header(); // 备份文件头部说明
            $sql .= $this->tableSql($table); // 表结构信息
            $fields = $this->model->getFields($table); // 表字段
            $field_num = $this->model->getFieldNum($table); // 字段数量
            $all_data = $this->model->getAll($table); // 读取全部数据
            $sql .= $this->dataSql($table, $fields, $field_num, $all_data); // 生成语句
            $filename = $backdir . "/" . get_uniqid() . "_" . $backdir . "_" . $table . '.sql'; // 写入文件
            $result = $this->writeFile($filename, $sql);
        }
        return $result;
    }

    // 备份整个数据库
    public function backupDB()
    {
        $sql = '';
        $sql .= $this->header(); // 备份文件头部说明
        $sql .= $this->dbSql(); // 数据库创建语句
        
        $tables = $this->model->getTables(); // 获取所有表
        foreach ($tables as $table) { // 表结构及数据
            $sql .= $this->tableSql($table); // 表结构信息
            $fields = $this->model->getFields($table); // 表字段
            $field_num = $this->model->getFieldNum($table); // 字段数量
            $all_data = $this->model->getAll($table); // 读取全部数据
            if ($all_data) {
                $sql .= $this->dataSql($table, $fields, $field_num, $all_data); // 生成数据语句
            }
            $sql .= '-- --------------------------------------------------------' . PHP_EOL . PHP_EOL;
        }
        // 写入文件
        $filename = get_uniqid() . '_' . date('YmdHis') . '_' . $this->dbauth['dbname'] . '.sql';
        return $this->writeFile($filename, $sql);
    }

    // 插入数据库备份基础信息
    private function header()
    {
        $sql = '-- Online Database Management SQL Dump' . PHP_EOL;
        $sql .= '-- 数据库名: ' . $this->dbauth['dbname'] . PHP_EOL;
        $sql .= '-- 生成日期: ' . date('Y-m-d H:i:s') . PHP_EOL;
        $sql .= '-- PHP 版本: ' . phpversion() . PHP_EOL . PHP_EOL;
        
        $sql .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL;
        $sql .= 'SET time_zone = "+08:00";' . PHP_EOL;
        $sql .= 'SET NAMES utf8;' . PHP_EOL . PHP_EOL;
        
        $sql .= '-- --------------------------------------------------------' . PHP_EOL . PHP_EOL;
        return $sql;
    }

    // 数据库创建语句
    private function dbSql()
    {
        $sql = '';
        $sql .= "--" . PHP_EOL;
        $sql .= "-- 数据库名 `" . $this->dbauth['dbname'] . '`' . PHP_EOL;
        $sql .= "--" . PHP_EOL . PHP_EOL;
        
        // 如果数据库不存在则创建
        $sql .= "CREATE DATABASE IF NOT EXISTS `" . $this->dbauth['dbname'] . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;' . PHP_EOL;
        // 选择数据库
        $sql .= "USE `" . $this->dbauth['dbname'] . "`;" . PHP_EOL . PHP_EOL;
        $sql .= '-- --------------------------------------------------------' . PHP_EOL . PHP_EOL;
        return $sql;
    }

    // 表结构语句
    private function tableSql($table)
    {
        $sql = '';
        $sql .= "--" . PHP_EOL;
        $sql .= "-- 表的结构 `" . $table . '`' . PHP_EOL;
        $sql .= "--" . PHP_EOL . PHP_EOL;
        
        $sql .= $this->model->tableStru($table); // 表创建语句
        return $sql;
    }

    // 数据语句
    private function dataSql($table, $fields, $fieldNnum, $data)
    {
        if (! $data)
            return;
        $sql = '';
        $sql .= "--" . PHP_EOL;
        $sql .= "-- 转存表中的数据 `" . $table . "`" . PHP_EOL;
        $sql .= "--" . PHP_EOL;
        $sql .= PHP_EOL;
        
        // 循环每个字段下面的内容
        
        $sql .= "INSERT INTO `" . $table . "` (" . implode(',', $fields) . ") VALUES" . PHP_EOL;
        $brackets = "(";
        foreach ($data as $value) {
            $sql .= $brackets;
            $comma = "";
            for ($i = 0; $i < $fieldNnum; $i ++) {
                $sql .= ($comma . "'" . addslashes(decode_string($value[$i])) . "'");
                $comma = ",";
            }
            $sql .= ")";
            $brackets = "," . PHP_EOL . "(";
        }
        $sql .= ';' . PHP_EOL . PHP_EOL;
        return $sql;
    }

    // 写入文件
    private function writeFile($filename, $content)
    {
        $sqlfile = DOC_PATH . DATA_DIR . '/backup/sql/' . $filename;
        check_file($sqlfile, true);
        if (file_put_contents($sqlfile, $content)) {
            return true;
        }
    }

    // 获取并检查表名称
    private function getTableList()
    {
        $list = post('list');
        foreach ($list as $key => $value) {
            if (! preg_match('/^[\w]+$/', $value)) {
                unset($list[$key]);
            }
        }
        return $list;
    }

    // 备份SQLite数据库文件
    public function backupSqliteDB($filename = '', $backup_dir_suffix='/backup/sql/')
    {
        // 仅在 SQLite 类型时执行
        $type = isset($this->dbauth['type']) ? $this->dbauth['type'] : '';
        if ($type !== 'sqlite' && $type !== 'pdo_sqlite') {
            return false;
        }

        $source = DOC_PATH . $this->dbauth['dbname'];
        if (! file_exists($source)) {
            return false;
        }

        // 备份目录，不存在时自动创建（递归创建多级目录）
        $backup_dir = DOC_PATH . DATA_DIR . $backup_dir_suffix;
        if (! check_dir($backup_dir, true)) {
            return false;
        }

        // 默认文件名规则与原逻辑保持一致
        if (! $filename) {
            $filename = get_uniqid() . '_' . date('YmdHis') . '.db';
        }

        return copy($source, $backup_dir . $filename);
    }

}