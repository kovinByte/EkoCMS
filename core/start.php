<?php
/**
 *  内核启动文件，请使用入口文件对本文件进行引用即可
 */

// 引入初始化文件
require dirname(__FILE__) . '/init.php';

// 入口检测
defined('IS_INDEX') ?: die('不允许直接访问框架内核启动文件！');

// 启动内核
core\basic\Kernel::run(); 

 




