<?php

/**
 *  二维码生成
 */

// 绘制二维码图片
function draw_qcode($string)
{
    require_once dirname(__FILE__) . '/extend/qrcode/phpqrcode.php'; // 引入类文件
    QRcode::png($string, false, 'M', 6, 1); // 生成二维码图片
}

require_once dirname(__FILE__) . '/init.php'; // 载入框架基础函数（限频、客户端IP等）

// 参数校验与体量限制
$string = isset($_GET['string']) ? $_GET['string'] : '';
if (! is_string($string) || $string === '') {
    die('地址必须传入string参数！');
}
if (strlen($string) > 1024) {
    http_response_code(400);
    die('二维码内容过长！');
}
$string = str_replace(array("\0", "\r", "\n", "\t"), '', $string);

// 同一IP限频，避免被用于资源消耗
if (! rate_limit_allow('qrcode', get_user_ip(), 60, 60)) {
    http_response_code(429);
    die('请求过于频繁，请稍后再试！');
}

draw_qcode($string);