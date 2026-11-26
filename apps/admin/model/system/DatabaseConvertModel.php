<?php
/**
 *  数据库文件互转模型：SQLite -> MySQL 脚本、MySQL -> SQLite 数据库文件
 *
 *  采用「初始化 + 分步执行」方式处理：
 *  - 初始化只写入表结构，并统计各表记录数；
 *  - 每次 step 按时间/行数预算处理有限数据，支持大库分批转换，避免超时和内存溢出；
 *  - 数据完成后再创建索引，提升导入/写入性能。
 */
namespace app\admin\model\system;

use core\basic\Model;

class DatabaseConvertModel extends Model
{

    // 单次查询条数
    const FETCH_LIMIT = 200;

    // 单步最多处理记录数
    const STEP_MAX_ROWS = 2000;

    // 单步最长执行时间（秒）
    const STEP_TIME_LIMIT = 2.0;

    // ==================== 初始化 ====================

    /**
     * 初始化 SQLite -> MySQL 转换任务
     *
     * @param string $file 目标 .sql 文件
     * @return array 任务状态
     */
    public function initSqliteToMysql($file)
    {
        if (get_db_type() != 'sqlite') {
            throw new \Exception('当前数据库不是 SQLite，无法执行该转换！');
        }

        $tables = $this->sqliteTables();
        if (! $tables) {
            throw new \Exception('未读取到 SQLite 数据表！');
        }

        $metas = array();
        $creates = array();
        $rowsTotal = 0;
        foreach ($tables as $table) {
            $columns = $this->sqliteColumns($table);
            if (! $columns) {
                continue;
            }
            $indexedColumns = $this->sqliteIndexedColumns($table);
            $creates[] = $this->buildMysqlCreate($table, $columns, $indexedColumns);

            $names = array();
            $quoted = array();
            $blobs = array();
            foreach ($columns as $column) {
                $names[] = $column['name'];
                $quoted[] = $this->qm($column['name']);
                if (isset($column['type']) && preg_match('/\bBLOB\b/i', $column['type'])) {
                    $blobs[] = $column['name'];
                }
            }

            $indexSqls = $this->sqliteIndexes($table);
            $total = $this->sqliteRowCount($table);

            $metas[] = array(
                'name' => $table,
                'columns' => $names,
                'quoted' => $quoted,
                'blobs' => $blobs,
                'index_sql' => $indexSqls ? implode('', $indexSqls) : '',
                'total' => $total,
                'rowid' => $this->sqliteHasRowid($table),
                'data_started' => false,
                'has_last_key' => false,
                'last_key' => null,
                'offset' => 0
            );
            $rowsTotal += $total;
        }

        if (! $metas) {
            throw new \Exception('未读取到可转换的数据表！');
        }

        $fp = @fopen($file, 'wb');
        if (! $fp) {
            throw new \Exception('无法写入目标文件，请检查目录权限！');
        }
        try {
            $this->write($fp, $this->mysqlDumpHeader(count($metas)));
            foreach ($creates as $create) {
                $this->write($fp, $create);
            }
        } catch (\Throwable $e) {
            fclose($fp);
            @unlink($file);
            throw $e;
        }
        fclose($fp);
        @chmod($file, 0644);

        return array(
            'direction' => 'sqlite2mysql',
            'file' => $file,
            'tables' => $metas,
            'tables_total' => count($metas),
            'table_index' => 0,
            'table_done' => 0,
            'rows' => 0,
            'rows_total' => $rowsTotal,
            'started' => time(),
            'finished' => false
        );
    }

    /**
     * 初始化 MySQL -> SQLite 转换任务
     *
     * @param string $file 目标 .db 文件
     * @return array 任务状态
     */
    public function initMysqlToSqlite($file)
    {
        if (get_db_type() != 'mysql') {
            throw new \Exception('当前数据库不是 MySQL，无法执行该转换！');
        }
        if (! extension_loaded('SQLite3')) {
            throw new \Exception('服务器未开启 SQLite3 扩展，无法生成 SQLite 数据库文件！');
        }

        $tables = $this->mysqlTables();
        if (! $tables) {
            throw new \Exception('未读取到 MySQL 数据表！');
        }

        $db = $this->openSqlite($file);
        $metas = array();
        $rowsTotal = 0;
        try {
            $db->exec('BEGIN');
            foreach ($tables as $table) {
                $row = $this->one('SHOW CREATE TABLE ' . $this->qm($table), 1);
                if (! $row || ! isset($row['Create Table'])) {
                    throw new \Exception('读取表结构失败：' . $table);
                }
                $parsed = $this->convertMysqlCreate($table, $row['Create Table']);
                $db->exec($parsed['create']);

                $total = $this->mysqlRowCount($table);
                $metas[] = array(
                    'name' => $table,
                    'columns' => $parsed['columns'],
                    'blobs' => $parsed['blobs'],
                    'index_sql' => $parsed['indexes'],
                    'primary' => $parsed['primary'],
                    'total' => $total,
                    'has_last_key' => false,
                    'last_key' => null,
                    'offset' => 0
                );
                $rowsTotal += $total;
            }
            $db->exec('COMMIT');
        } catch (\Throwable $e) {
            try {
                $db->exec('ROLLBACK');
            } catch (\Throwable $ignore) {
            }
            $db->close();
            @unlink($file);
            throw $e;
        }
        $db->close();
        @chmod($file, 0644);

        return array(
            'direction' => 'mysql2sqlite',
            'file' => $file,
            'tables' => $metas,
            'tables_total' => count($metas),
            'table_index' => 0,
            'table_done' => 0,
            'rows' => 0,
            'rows_total' => $rowsTotal,
            'started' => time(),
            'finished' => false
        );
    }

    // ==================== 分步执行 ====================

    /**
     * 执行一步转换
     *
     * @param array $state 任务状态，执行后原地更新
     */
    public function step(&$state)
    {
        if (empty($state['tables']) || ! empty($state['finished'])) {
            $state['finished'] = true;
            return;
        }
        if ($state['direction'] == 'sqlite2mysql') {
            $this->stepSqliteToMysql($state);
        } else {
            $this->stepMysqlToSqlite($state);
        }
    }

    /**
     * 获取转换进度信息
     *
     * @param array $state 任务状态
     * @return array
     */
    public function progress($state)
    {
        $totalTables = isset($state['tables_total']) ? (int) $state['tables_total'] : 0;
        $index = isset($state['table_index']) ? (int) $state['table_index'] : 0;
        $tableDone = isset($state['table_done']) ? (int) $state['table_done'] : 0;
        $tableTotal = 0;
        $tableName = '';
        if ($index < $totalTables && isset($state['tables'][$index])) {
            $tableTotal = (int) $state['tables'][$index]['total'];
            $tableName = $state['tables'][$index]['name'];
        }

        $finished = ! empty($state['finished']);
        if ($finished) {
            $percent = 100;
        } elseif ($totalTables > 0) {
            $inTable = $tableTotal > 0 ? min(1, $tableDone / $tableTotal) : 1;
            $percent = (int) floor(($index + $inTable) / $totalTables * 100);
            if ($percent > 99) {
                $percent = 99;
            }
        } else {
            $percent = 0;
        }

        $elapsed = isset($state['started']) ? max(0, time() - (int) $state['started']) : 0;
        $size = 0;
        if (! empty($state['file']) && is_file($state['file'])) {
            $size = (int) @filesize($state['file']);
        }

        return array(
            'direction' => isset($state['direction']) ? $state['direction'] : '',
            'percent' => $percent,
            'finished' => $finished,
            'tables_done' => $index,
            'table_total' => $totalTables,
            'table_name' => $tableName,
            'table_rows' => isset($state['rows']) ? (int) $state['rows'] : 0,
            'rows_total' => isset($state['rows_total']) ? (int) $state['rows_total'] : 0,
            'file' => isset($state['relative_file']) ? $state['relative_file'] : '',
            'size' => $size,
            'elapsed' => $elapsed
        );
    }

    // SQLite -> MySQL 单步处理
    private function stepSqliteToMysql(&$state)
    {
        $fp = @fopen($state['file'], 'ab');
        if (! $fp) {
            throw new \Exception('无法写入目标文件，请检查目录权限！');
        }

        $start = microtime(true);
        $processed = 0;
        $total = (int) $state['tables_total'];

        try {
            while ($state['table_index'] < $total) {
                $index = $state['table_index'];
                $meta = &$state['tables'][$index];

                $rows = $this->fetchSqliteRows($meta);
                $count = count($rows);
                if ($count > 0) {
                    if (empty($meta['data_started'])) {
                        $this->write($fp, '--' . PHP_EOL);
                        $this->write($fp, '-- 转存表中的数据 ' . $this->qm($meta['name']) . PHP_EOL);
                        $this->write($fp, '--' . PHP_EOL . PHP_EOL);
                        $meta['data_started'] = true;
                    }
                    $this->writeMysqlInserts($fp, $meta, $rows);
                    $state['rows'] += $count;
                    $state['table_done'] += $count;
                    $processed += $count;
                }

                // 本表数据已全部处理，补写索引语句
                if ($count < self::FETCH_LIMIT) {
                    if (! empty($meta['index_sql'])) {
                        $this->write($fp, $meta['index_sql']);
                        $meta['index_sql'] = '';
                    }
                    unset($meta);
                    $state['table_index'] ++;
                    $state['table_done'] = 0;
                    if ($state['table_index'] >= $total) {
                        break;
                    }
                    continue;
                }
                unset($meta);

                if ($processed >= self::STEP_MAX_ROWS) {
                    break;
                }
                if (microtime(true) - $start >= self::STEP_TIME_LIMIT) {
                    break;
                }
            }

            if ($state['table_index'] >= $total) {
                $this->write($fp, 'SET FOREIGN_KEY_CHECKS = 1;' . PHP_EOL);
                $state['finished'] = true;
            }
        } catch (\Throwable $e) {
            fclose($fp);
            throw $e;
        }
        fclose($fp);
    }

    // MySQL -> SQLite 单步处理
    private function stepMysqlToSqlite(&$state)
    {
        $db = $this->openSqlite($state['file']);
        $start = microtime(true);
        $processed = 0;
        $total = (int) $state['tables_total'];

        try {
            $db->exec('BEGIN');
            while ($state['table_index'] < $total) {
                $index = $state['table_index'];
                $meta = &$state['tables'][$index];

                $rows = $this->fetchMysqlRows($meta);
                $count = count($rows);
                if ($count > 0) {
                    $this->insertSqliteRows($db, $meta['name'], $meta['columns'], $rows, isset($meta['blobs']) ? $meta['blobs'] : array());
                    $state['rows'] += $count;
                    $state['table_done'] += $count;
                    $processed += $count;
                }

                // 本表数据已全部处理，创建索引
                if ($count < self::FETCH_LIMIT) {
                    foreach ($meta['index_sql'] as $sql) {
                        $db->exec($sql);
                    }
                    $meta['index_sql'] = array();
                    unset($meta);
                    $state['table_index'] ++;
                    $state['table_done'] = 0;
                    if ($state['table_index'] >= $total) {
                        break;
                    }
                    continue;
                }
                unset($meta);

                if ($processed >= self::STEP_MAX_ROWS) {
                    break;
                }
                if (microtime(true) - $start >= self::STEP_TIME_LIMIT) {
                    break;
                }
            }

            if ($state['table_index'] >= $total) {
                $state['finished'] = true;
            }
            $db->exec('COMMIT');
        } catch (\Throwable $e) {
            try {
                $db->exec('ROLLBACK');
            } catch (\Throwable $ignore) {
            }
            $db->close();
            throw $e;
        }
        $db->close();
    }

    // ==================== SQLite 读取 ====================

    // 读取一批 SQLite 数据
    private function fetchSqliteRows(&$meta)
    {
        $table = $this->qs($meta['name']);

        // 有 rowid 的表使用 keyset 分页，避免大偏移量带来的性能损耗
        if (! empty($meta['rowid'])) {
            $alias = '__pb_convert_rid';
            if (in_array($alias, $meta['columns'], true)) {
                $alias = '__pb_convert_rid_2';
            }
            $aliasSql = $this->qs($alias);
            if (! empty($meta['has_last_key'])) {
                $rows = $this->all('SELECT rowid AS ' . $aliasSql . ', * FROM ' . $table . ' WHERE rowid > ? ORDER BY rowid LIMIT ' . self::FETCH_LIMIT, 1, array(
                    $meta['last_key']
                ));
            } else {
                $rows = $this->all('SELECT rowid AS ' . $aliasSql . ', * FROM ' . $table . ' ORDER BY rowid LIMIT ' . self::FETCH_LIMIT, 1);
            }
            if ($rows) {
                $last = end($rows);
                if (isset($last[$alias])) {
                    $meta['last_key'] = $last[$alias];
                    $meta['has_last_key'] = true;
                }
                foreach ($rows as &$row) {
                    unset($row[$alias]);
                }
                unset($row);
            }
            return $rows;
        }

        // WITHOUT ROWID 表回退为偏移量分页
        $offset = (int) $meta['offset'];
        $rows = $this->all('SELECT * FROM ' . $table . ' LIMIT ' . self::FETCH_LIMIT . ' OFFSET ' . $offset, 1);
        $meta['offset'] = $offset + count($rows);
        return $rows;
    }

    // 输出一批数据为 MySQL INSERT 语句
    private function writeMysqlInserts($fp, $meta, $rows)
    {
        $prefix = 'INSERT INTO ' . $this->qm($meta['name']) . ' (' . implode(',', $meta['quoted']) . ') VALUES' . PHP_EOL;
        $batch = array();
        $batchLen = 0;
        foreach ($rows as $row) {
            $values = array();
            foreach ($meta['columns'] as $name) {
                $binary = isset($meta['blobs']) && in_array($name, $meta['blobs'], true);
                $values[] = $this->mysqlValue(array_key_exists($name, $row) ? $row[$name] : null, $binary);
            }
            $line = '(' . implode(',', $values) . ')';
            $batch[] = $line;
            $batchLen += strlen($line) + 1;
            if (count($batch) >= 100 || $batchLen >= 1048576) {
                $this->write($fp, $prefix . implode(',' . PHP_EOL, $batch) . ';' . PHP_EOL);
                $batch = array();
                $batchLen = 0;
            }
        }
        if ($batch) {
            $this->write($fp, $prefix . implode(',' . PHP_EOL, $batch) . ';' . PHP_EOL);
        }
        $this->write($fp, PHP_EOL);
    }

    // ==================== MySQL 读取 ====================

    // 读取一批 MySQL 数据
    private function fetchMysqlRows(&$meta)
    {
        $table = $this->qm($meta['name']);
        $primary = isset($meta['primary']) && is_array($meta['primary']) ? $meta['primary'] : array();

        // 单列主键表使用 keyset 分页
        if (count($primary) === 1) {
            $column = $primary[0];
            $pk = $this->qm($column);
            if (! empty($meta['has_last_key'])) {
                $rows = $this->all('SELECT * FROM ' . $table . ' WHERE ' . $pk . ' > ? ORDER BY ' . $pk . ' LIMIT ' . self::FETCH_LIMIT, 1, array(
                    $meta['last_key']
                ));
            } else {
                $rows = $this->all('SELECT * FROM ' . $table . ' ORDER BY ' . $pk . ' LIMIT ' . self::FETCH_LIMIT, 1);
            }
            if ($rows) {
                $last = end($rows);
                if (array_key_exists($column, $last)) {
                    $meta['last_key'] = $last[$column];
                    $meta['has_last_key'] = true;
                }
            }
            return $rows;
        }

        // 无单列主键时回退偏移量分页
        $offset = (int) $meta['offset'];
        $rows = $this->all('SELECT * FROM ' . $table . ' LIMIT ' . self::FETCH_LIMIT . ' OFFSET ' . $offset, 1);
        $meta['offset'] = $offset + count($rows);
        return $rows;
    }

    // ==================== SQLite 元数据 ====================

    // 获取 SQLite 全部业务表
    private function sqliteTables()
    {
        $rows = $this->all("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name", 1);
        $tables = array();
        foreach ($rows as $row) {
            if (isset($row['name']) && $row['name'] !== '') {
                $tables[] = $row['name'];
            }
        }
        return $tables;
    }

    // 获取 SQLite 表字段信息
    private function sqliteColumns($table)
    {
        return $this->all('PRAGMA table_info(' . $this->qs($table) . ')', 1);
    }

    // 获取 SQLite 表的普通索引
    private function sqliteIndexes($table)
    {
        $sqls = array();
        $list = $this->all('PRAGMA index_list(' . $this->qs($table) . ')', 1);
        foreach ($list as $index) {
            $name = isset($index['name']) ? $index['name'] : '';
            if ($name === '' || stripos($name, 'sqlite_autoindex_') === 0) {
                continue;
            }
            $cols = array();
            $info = $this->all('PRAGMA index_info(' . $this->qs($name) . ')', 1);
            foreach ($info as $item) {
                if (isset($item['name']) && $item['name'] !== null && $item['name'] !== '') {
                    $cols[] = $this->qm($item['name']);
                }
            }
            if (! $cols) {
                continue;
            }
            $unique = ! empty($index['unique']);
            $sqls[] = 'ALTER TABLE ' . $this->qm($table) . ' ADD ' . ($unique ? 'UNIQUE ' : '') . 'KEY ' . $this->qm($name) . ' (' . implode(',', $cols) . ');' . PHP_EOL;
        }
        if ($sqls) {
            $sqls[] = PHP_EOL;
        }
        return $sqls;
    }

    // 获取 SQLite 表中参与索引的字段名称
    private function sqliteIndexedColumns($table)
    {
        $columns = array();
        $list = $this->all('PRAGMA index_list(' . $this->qs($table) . ')', 1);
        foreach ($list as $index) {
            $name = isset($index['name']) ? $index['name'] : '';
            if ($name === '') {
                continue;
            }
            $info = $this->all('PRAGMA index_info(' . $this->qs($name) . ')', 1);
            foreach ($info as $item) {
                if (isset($item['name']) && $item['name'] !== null && $item['name'] !== '') {
                    $columns[$item['name']] = true;
                }
            }
        }
        return $columns;
    }

    // SQLite 表是否支持 rowid 分页
    private function sqliteHasRowid($table)
    {
        $row = $this->one("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", 1, array(
            $table
        ));
        $sql = $row && isset($row['sql']) ? $row['sql'] : '';
        if ($sql === '') {
            return false;
        }
        return stripos($sql, 'WITHOUT ROWID') === false;
    }

    // SQLite 表记录数
    private function sqliteRowCount($table)
    {
        $row = $this->one('SELECT COUNT(*) AS c FROM ' . $this->qs($table), 1);
        return $row && isset($row['c']) ? (int) $row['c'] : 0;
    }

    // 根据 SQLite 字段信息构建 MySQL 建表语句
    private function buildMysqlCreate($table, $columns, $indexedColumns = array())
    {
        $row = $this->one("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", 1, array(
            $table
        ));
        $createSql = $row && isset($row['sql']) ? $row['sql'] : '';

        // SQLite 只允许一个自增主键，直接定位该字段
        $autoColumn = '';
        if (preg_match('/(?:["`\[]([^"`\]]+)["`\]]|([A-Za-z_][A-Za-z0-9_]*))\s+INTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT/i', $createSql, $match)) {
            $autoColumn = ! empty($match[1]) ? $match[1] : (isset($match[2]) ? $match[2] : '');
        }

        $defs = array();
        $primary = array();
        foreach ($columns as $column) {
            $name = $column['name'];
            $isAuto = ($autoColumn !== '' && $name === $autoColumn);
            if (! empty($column['pk'])) {
                $primary[(int) $column['pk']] = $name;
            }

            $mysqlType = $this->sqliteTypeToMysql(isset($column['type']) ? $column['type'] : '', $isAuto, isset($indexedColumns[$name]));
            $def = $this->qm($name) . ' ' . $mysqlType;
            if ($isAuto) {
                $def .= ' NOT NULL AUTO_INCREMENT';
            } else {
                if (! empty($column['notnull'])) {
                    $def .= ' NOT NULL';
                }
                $default = isset($column['dflt_value']) ? $this->normalizeSqliteDefault($column['dflt_value']) : null;
                // MySQL 5.7 的 TEXT/BLOB 字段不支持默认值
                if ($default !== null && ! $this->isMysqlTextType($mysqlType)) {
                    $def .= ' DEFAULT ' . $default;
                }
            }
            $defs[] = $def;
        }

        if ($primary) {
            ksort($primary);
            $cols = array();
            foreach ($primary as $name) {
                $cols[] = $this->qm($name);
            }
            $defs[] = 'PRIMARY KEY (' . implode(',', $cols) . ')';
        }

        $sql = '--' . PHP_EOL;
        $sql .= '-- 表的结构 ' . $this->qm($table) . PHP_EOL;
        $sql .= '--' . PHP_EOL . PHP_EOL;
        $sql .= 'DROP TABLE IF EXISTS ' . $this->qm($table) . ';' . PHP_EOL;
        $sql .= 'CREATE TABLE ' . $this->qm($table) . ' (' . PHP_EOL;
        $sql .= '  ' . implode(',' . PHP_EOL . '  ', $defs) . PHP_EOL;
        $sql .= ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;' . PHP_EOL . PHP_EOL;
        return $sql;
    }

    // SQLite 数据类型转 MySQL 类型
    private function sqliteTypeToMysql($type, $isAuto = false, $indexed = false)
    {
        $type = strtoupper(trim((string) $type));
        $base = $type;
        $length = 0;
        if (preg_match('/^([A-Z]+)\s*(?:\(\s*(\d+)\s*(?:,\s*(\d+)\s*)?\))?/', $type, $match)) {
            $base = $match[1];
            $length = isset($match[2]) ? (int) $match[2] : 0;
        }
        if ($isAuto) {
            return 'int(10) unsigned';
        }
        switch ($base) {
            case 'INTEGER':
            case 'INT':
            case 'BIGINT':
            case 'SMALLINT':
            case 'TINYINT':
            case 'MEDIUMINT':
                return 'int(' . ($length ?: 11) . ')';
            case 'TEXT':
            case 'CLOB':
                if ($indexed) {
                    return 'varchar(' . ($length > 0 && $length <= 191 ? $length : 191) . ')';
                }
                if ($length > 0) {
                    return $length <= 2000 ? 'varchar(' . $length . ')' : 'text';
                }
                return 'mediumtext';
            case 'VARCHAR':
            case 'NVARCHAR':
                if ($indexed && ($length === 0 || $length > 191)) {
                    return 'varchar(191)';
                }
                return 'varchar(' . ($length ?: 255) . ')';
            case 'CHAR':
            case 'NCHAR':
                return 'char(' . ($length ?: 1) . ')';
            case 'BLOB':
                return 'longblob';
            case 'REAL':
            case 'DOUBLE':
            case 'FLOAT':
                return 'double';
            case 'NUMERIC':
            case 'DECIMAL':
                return $length ? 'decimal(' . $length . ',2)' : 'decimal(10,2)';
            case 'DATE':
            case 'DATETIME':
            case 'TIMESTAMP':
                return 'datetime';
            case 'TIME':
                return 'time';
            case 'BOOLEAN':
            case 'BOOL':
                return 'tinyint(1)';
            default:
                if ($indexed) {
                    return 'varchar(191)';
                }
                if ($length > 0 && $length <= 2000) {
                    return 'varchar(' . $length . ')';
                }
                return 'mediumtext';
        }
    }

    // 是否为 MySQL 的 TEXT/BLOB 类型
    private function isMysqlTextType($type)
    {
        return (bool) preg_match('/^(tinytext|text|mediumtext|longtext|tinyblob|blob|mediumblob|longblob)\b/i', trim($type));
    }

    // 规范化 SQLite 默认值，无法转换的表达式返回 null 表示忽略
    private function normalizeSqliteDefault($default)
    {
        $default = trim((string) $default);
        if ($default === '') {
            return "''";
        }
        if ($default[0] === '(') {
            return null;
        }
        if (preg_match('/^(-?\d+(\.\d+)?|NULL|CURRENT_(DATE|TIME|TIMESTAMP))$/i', $default)) {
            return $default;
        }
        if ($default[0] === "'" || $default[0] === '"') {
            return $default;
        }
        return "'" . str_replace(array(
            "\\",
            "'"
        ), array(
            "\\\\",
            "\\'"
        ), $default) . "'";
    }

    // 数据库脚本头部
    private function mysqlDumpHeader($tableCount)
    {
        return '-- EkoCMS 数据库互转脚本（SQLite -> MySQL）' . PHP_EOL
            . '-- 生成时间: ' . date('Y-m-d H:i:s') . PHP_EOL
            . '-- 数据表数量: ' . $tableCount . PHP_EOL . PHP_EOL
            . 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL
            . 'SET time_zone = "+08:00";' . PHP_EOL
            . 'SET NAMES utf8mb4;' . PHP_EOL
            . 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL . PHP_EOL;
    }

    // ==================== MySQL 元数据 ====================

    // 获取 MySQL 全部业务表
    private function mysqlTables()
    {
        $rows = $this->all("SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME", 1);
        $tables = array();
        foreach ($rows as $row) {
            if (isset($row['name']) && $row['name'] !== '') {
                $tables[] = $row['name'];
            }
        }
        return $tables;
    }

    // MySQL 表记录数（优先使用统计信息，避免大表初始化时全表扫描）
    private function mysqlRowCount($table)
    {
        $row = $this->one('SELECT TABLE_ROWS AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', 1, array(
            $table
        ));
        if ($row && isset($row['c']) && (int) $row['c'] > 0) {
            return (int) $row['c'];
        }
        // 统计信息为 0（小表或尚未统计）时取精确值
        $row = $this->one('SELECT COUNT(*) AS c FROM ' . $this->qm($table), 1);
        return $row && isset($row['c']) ? (int) $row['c'] : 0;
    }

    // 将 MySQL 建表语句转换为 SQLite 建表语句及索引语句
    private function convertMysqlCreate($table, $create)
    {
        $create = rtrim(trim($create), ';');
        $open = strpos($create, '(');
        $close = strrpos($create, ')');
        if ($open === false || $close === false || $close <= $open) {
            throw new \Exception('无法解析表结构：' . $table);
        }
        $body = substr($create, $open + 1, $close - $open - 1);
        $items = $this->splitDefinitions($body);

        $defs = array();
        $indexes = array();
        $columns = array();
        $columnTypes = array();
        $blobs = array();
        $primary = array();
        $autoName = '';
        $inlinePrimary = false;

        foreach ($items as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }

            // 主键
            if (preg_match('/^PRIMARY\s+KEY\s*\((.*?)\)(?:\s+USING\s+\w+)?$/is', $item, $match)) {
                $primary = $this->indexColumns($match[1]);
                continue;
            }

            // 普通索引、唯一索引
            if (preg_match('/^(UNIQUE\s+)?(?:KEY|INDEX)\s+`([^`]+)`\s*\((.*?)\)(?:\s+USING\s+\w+)?$/is', $item, $match)) {
                $cols = $this->indexColumns($match[3]);
                if ($cols) {
                    $indexes[] = $this->buildSqliteIndex($table, $match[2], $cols, ! empty($match[1]));
                }
                continue;
            }

            // SQLite 不支持全文索引
            if (preg_match('/^FULLTEXT\s+(?:KEY|INDEX)/i', $item)) {
                continue;
            }

            // 外键、检查约束直接忽略
            if (preg_match('/^(CONSTRAINT|CHECK|FOREIGN)\b/i', $item)) {
                continue;
            }

            if (! preg_match('/^`([^`]+)`\s+(.+)$/s', $item, $match)) {
                continue;
            }
            $name = $match[1];
            $rest = $match[2];

            // 清理 MySQL 专属属性
            $rest = preg_replace("/\s+COMMENT\s+'(?:\\\\.|[^'\\\\])*'/is", '', $rest);
            $rest = preg_replace('/\s+CHARACTER\s+SET\s+\w+/i', '', $rest);
            $rest = preg_replace('/\s+COLLATE\s+\w+/i', '', $rest);
            $rest = preg_replace('/\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP(?:\(\d+\))?/i', '', $rest);
            $auto = preg_match('/\bAUTO_INCREMENT\b/i', $rest) ? true : false;
            $rest = preg_replace('/\bAUTO_INCREMENT\b/i', '', $rest);
            $rest = preg_replace('/\bUNSIGNED\b/i', '', $rest);
            $rest = preg_replace('/\bZEROFILL\b/i', '', $rest);
            $rest = preg_replace("/\bDEFAULT\s+b'([01]+)'/i", 'DEFAULT $1', $rest);

            $type = '';
            $args = '';
            if (preg_match('/^([A-Za-z]+)\s*(\([^)]*\))?/', $rest, $matchType)) {
                $type = strtolower($matchType[1]);
                $args = isset($matchType[2]) ? trim($matchType[2]) : '';
                $rest = trim(substr($rest, strlen($matchType[0])));
            }

            $columns[] = $name;
            $columnTypes[$name] = $type;
            if (in_array($type, array(
                'blob',
                'tinyblob',
                'mediumblob',
                'longblob',
                'binary',
                'varbinary'
            ), true)) {
                $blobs[] = $name;
            }
            if ($auto) {
                $defs[] = $this->qs($name) . ' INTEGER PRIMARY KEY AUTOINCREMENT';
                $inlinePrimary = true;
                $autoName = $name;
            } else {
                $def = $this->qs($name) . ' ' . $this->mysqlTypeToSqlite($type, $args);
                $rest = trim(preg_replace('/\s+/', ' ', $rest));
                if ($rest !== '') {
                    $def .= ' ' . $rest;
                }
                $defs[] = $def;
            }
        }

        if (! $inlinePrimary && $primary) {
            $cols = array();
            foreach ($primary as $name) {
                $cols[] = $this->qs($name);
            }
            $defs[] = 'PRIMARY KEY (' . implode(',', $cols) . ')';
        }

        $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->qs($table) . ' (' . PHP_EOL;
        $sql .= '  ' . implode(',' . PHP_EOL . '  ', $defs) . PHP_EOL;
        $sql .= ');';

        // 分页主键：仅支持单列且非二进制类型
        $primaryCols = $inlinePrimary && $autoName !== '' ? array(
            $autoName
        ) : $primary;
        if (count($primaryCols) === 1) {
            $pkType = isset($columnTypes[$primaryCols[0]]) ? $columnTypes[$primaryCols[0]] : '';
            if (in_array($pkType, array(
                'blob',
                'tinyblob',
                'mediumblob',
                'longblob',
                'binary',
                'varbinary'
            ), true)) {
                $primaryCols = array();
            }
        }

        return array(
            'create' => $sql,
            'indexes' => $indexes,
            'columns' => $columns,
            'blobs' => $blobs,
            'primary' => $primaryCols
        );
    }

    // 拆分建表语句中的字段与索引定义（忽略括号与引号内的逗号）
    private function splitDefinitions($body)
    {
        $items = array();
        $buffer = '';
        $depth = 0;
        $inQuote = false;
        $quote = '';
        $length = strlen($body);
        for ($i = 0; $i < $length; $i ++) {
            $char = $body[$i];
            if ($inQuote) {
                $buffer .= $char;
                if ($char === '\\' && $i + 1 < $length) {
                    $buffer .= $body[++ $i];
                } elseif ($char === $quote) {
                    $inQuote = false;
                }
                continue;
            }
            if ($char === "'" || $char === '"' || $char === '`') {
                $inQuote = true;
                $quote = $char;
                $buffer .= $char;
                continue;
            }
            if ($char === '(') {
                $depth ++;
                $buffer .= $char;
                continue;
            }
            if ($char === ')') {
                $depth --;
                $buffer .= $char;
                continue;
            }
            if ($char === ',' && $depth === 0) {
                $items[] = trim($buffer);
                $buffer = '';
                continue;
            }
            $buffer .= $char;
        }
        if (trim($buffer) !== '') {
            $items[] = trim($buffer);
        }
        return $items;
    }

    // 从索引定义中提取字段名称
    private function indexColumns($text)
    {
        $cols = array();
        if (preg_match_all('/`([^`]+)`/', $text, $match)) {
            foreach ($match[1] as $name) {
                $cols[] = $name;
            }
        }
        if (! $cols) {
            foreach (explode(',', $text) as $part) {
                $part = trim(preg_replace('/\s*\(\d+\).*$/', '', trim($part)));
                $part = trim($part, '`" ');
                if ($part !== '') {
                    $cols[] = $part;
                }
            }
        }
        return $cols;
    }

    // 构建 SQLite 索引语句
    private function buildSqliteIndex($table, $name, $columns, $unique)
    {
        // SQLite 索引名在库内全局唯一，必要时加上表名前缀避免冲突
        $indexName = (strpos($name, $table . '_') === 0) ? $name : $table . '_' . $name;
        $cols = array();
        foreach ($columns as $column) {
            $cols[] = $this->qs($column);
        }
        return 'CREATE ' . ($unique ? 'UNIQUE ' : '') . 'INDEX IF NOT EXISTS ' . $this->qs($indexName) . ' ON ' . $this->qs($table) . ' (' . implode(',', $cols) . ');';
    }

    // MySQL 数据类型转 SQLite 类型
    private function mysqlTypeToSqlite($type, $args)
    {
        switch ($type) {
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
            case 'bit':
            case 'bool':
            case 'boolean':
                return 'INTEGER';
            case 'decimal':
            case 'numeric':
                return 'DECIMAL' . $args;
            case 'float':
            case 'double':
            case 'real':
                return 'REAL';
            case 'varchar':
                return 'VARCHAR' . $args;
            case 'char':
                return 'CHAR' . $args;
            case 'tinytext':
            case 'text':
            case 'mediumtext':
            case 'longtext':
            case 'json':
            case 'date':
            case 'datetime':
            case 'timestamp':
            case 'time':
            case 'year':
            case 'enum':
            case 'set':
                return 'TEXT';
            case 'blob':
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'binary':
            case 'varbinary':
                return 'BLOB';
            default:
                return 'TEXT';
        }
    }

    // ==================== SQLite 写入 ====================

    // 打开 SQLite 目标文件并设置写入性能参数
    private function openSqlite($file)
    {
        $db = new \SQLite3($file);
        $db->enableExceptions(true);
        $db->busyTimeout(15000);
        $db->exec('PRAGMA journal_mode = MEMORY');
        $db->exec('PRAGMA synchronous = OFF');
        $db->exec('PRAGMA temp_store = MEMORY');
        $db->exec('PRAGMA cache_size = -20000');
        return $db;
    }

    // 写入 SQLite 数据
    private function insertSqliteRows($db, $table, $columns, $rows, $blobs = array())
    {
        $quoted = array();
        foreach ($columns as $column) {
            $quoted[] = $this->qs($column);
        }
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = 'INSERT INTO ' . $this->qs($table) . ' (' . implode(',', $quoted) . ') VALUES (' . $placeholders . ')';
        $stmt = $db->prepare($sql);
        if (! $stmt) {
            throw new \Exception('创建写入语句失败：' . $table);
        }

        foreach ($rows as $row) {
            $position = 1;
            foreach ($columns as $column) {
                $value = array_key_exists($column, $row) ? $row[$column] : null;
                if ($value === null) {
                    $stmt->bindValue($position, null, SQLITE3_NULL);
                } elseif (is_int($value)) {
                    $stmt->bindValue($position, $value, SQLITE3_INTEGER);
                } elseif (is_float($value)) {
                    $stmt->bindValue($position, $value, SQLITE3_FLOAT);
                } elseif (in_array($column, $blobs, true) || (is_string($value) && strpos($value, "\0") !== false)) {
                    // 二进制数据需按 BLOB 绑定，否则文本绑定会截断 NUL 字节
                    $stmt->bindValue($position, (string) $value, SQLITE3_BLOB);
                } else {
                    $stmt->bindValue($position, (string) $value, SQLITE3_TEXT);
                }
                $position ++;
            }
            if (! $stmt->execute()) {
                throw new \Exception('写入数据失败：' . $table);
            }
            $stmt->reset();
        }
        $stmt->close();
    }

    // ==================== 通用 ====================

    // MySQL 值转 SQL 字面量
    private function mysqlValue($value, $binary = false)
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($binary && is_string($value)) {
            // 二进制字段使用十六进制字面量，避免字符集转换与转义问题
            return "X'" . bin2hex($value) . "'";
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return "'" . str_replace(array(
            "\\",
            "\0",
            "\n",
            "\r",
            "'",
            '"',
            "\x1a"
        ), array(
            "\\\\",
            "\\0",
            "\\n",
            "\\r",
            "\\'",
            '\\"',
            "\\Z"
        ), (string) $value) . "'";
    }

    // 写入文件内容
    private function write($fp, $content)
    {
        if (fwrite($fp, $content) === false) {
            throw new \Exception('写入转换文件失败！');
        }
    }

    // MySQL 标识符
    private function qm($name)
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    // SQLite 标识符
    private function qs($name)
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
