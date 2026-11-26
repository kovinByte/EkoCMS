<?php

namespace core\basic;
class Kernel {
    private static $pathSegments = array();

    public static function run() {
        self::outputHtmlCache();
        $pathInfo = self::parsePathInfo();
        $pathInfo = self::bindAppModule($pathInfo);
        $pathInfo = self::parseUrlRoute($pathInfo);
        $route = self::parseRoute($pathInfo);
        $controllerPath = self::resolveController($route);
        self::loadModuleEnv();
        self::dispatchController($controllerPath);
    }

    private static function parsePathInfo() {
        if (isset($_SERVER['PATH_INFO']) && ! mb_check_encoding($_SERVER['PATH_INFO'], 'UTF-8')) {
            $_SERVER['PATH_INFO'] = mb_convert_encoding($_SERVER['PATH_INFO'], 'utf-8', 'GBK');
        }
        if (isset($_SERVER['REQUEST_URI']) && ! mb_check_encoding($_SERVER['REQUEST_URI'], 'UTF-8')) {
            $_SERVER['REQUEST_URI'] = mb_convert_encoding($_SERVER['REQUEST_URI'], 'utf-8', 'GBK');
        }
        if (isset($_SERVER['ORIG_PATH_INFO']) && ! mb_check_encoding($_SERVER['ORIG_PATH_INFO'], 'UTF-8')) {
            $_SERVER['ORIG_PATH_INFO'] = mb_convert_encoding($_SERVER['ORIG_PATH_INFO'], 'utf-8', 'GBK');
        }
        $pathInfo = '';
        if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO']) {
            $pathInfo = $_SERVER['PATH_INFO'];
        } elseif (isset($_SERVER["REDIRECT_URL"]) && $_SERVER["REDIRECT_URL"]) {
            $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
            $pathInfo = str_replace('/' . basename($scriptName), '', $_SERVER['REDIRECT_URL']);
            $pathInfo = str_replace(SITE_DIR, '', $pathInfo);
            $_SERVER['PATH_INFO'] = $pathInfo;
        }
        if (! $pathInfo) {
            if (isset($_GET['p']) && $_GET['p']) {
                $pathInfo = $_GET['p'];
            } elseif (isset($_GET['s']) && $_GET['s']) {
                $pathInfo = $_GET['s'];
            }
        }
        if ($pathInfo) {
            $pathPattern = '{^\/?([\x{4e00}-\x{9fa5}\w\-\/\.' . Config::get('url_allow_char') . ']+?)?$}u';
            if (preg_match($pathPattern, $pathInfo)) {
                $pathInfo = preg_replace($pathPattern, '$1', $pathInfo);
            } else {
                $invalidPath = true;
            }
        }
        if (isset($invalidPath) && $invalidPath) {
            http_response_code(404);
            $defendFile = ROOT_PATH . '/defend.html';
            if (file_exists($defendFile)) {
                require $defendFile;
                exit();
            } else {
                error('您访问路径含有非法字符，防注入系统提醒您请勿尝试非法操作！');
            }
        }
        define('P', $pathInfo);
        return $pathInfo;
    }

    private static function bindAppModule($pathInfo) {
        $bindModule = '';
        if (! ! $appDomainBind = Config::get('app_domain_bind')) {
            $host = get_http_host();
            if (isset($appDomainBind[$host])) {
                $bindModule = $appDomainBind[$host];
            }
        }
        if (defined('URL_BIND')) {
            if ($bindModule && URL_BIND != $bindModule) {
                error('系统配置的模块域名绑定与入口文件绑定冲突，请核对！');
            } else {
                $bindModule = URL_BIND;
            }
        }
        return $bindModule ? trim_slash($bindModule) . '/' . $pathInfo : $pathInfo;
    }

    private static function parseUrlRoute($pathInfo) {
        if (! ! $urlRoute = Config::get('url_route')) {
            if (! $pathInfo && isset($urlRoute['/'])) {
                return $urlRoute['/'];
            }
            foreach ($urlRoute as $routeKey => $routeValue) {
                $routeKey = trim_slash($routeKey);
                $routePattern = "{" . $routeKey . "}i";
                if (preg_match($routePattern, $pathInfo)) {
                    $routeValue = trim_slash($routeValue);
                    $pathInfo = preg_replace($routePattern, $routeValue, $pathInfo);
                    break;
                }
            }
        }
        return $pathInfo;
    }

    private static function parseRoute($pathInfo) {
        $publicApp = Config::get('public_app', true);
        if ($pathInfo) {
            $pathInfo = trim_slash($pathInfo);
            $segments = explode('/', $pathInfo);
            self::$pathSegments = $segments;
            $segmentCount = count($segments);
            if ($segmentCount >= 3) {
                $route['m'] = $segments[0];
                $route['c'] = $segments[1];
                $route['f'] = $segments[2];
            } elseif ($segmentCount == 2) {
                $route['m'] = $segments[0];
                $route['c'] = $segments[1];
            } elseif ($segmentCount == 1) {
                $route['m'] = $segments[0];
            }
        }
        if (! isset($route['m'])) {
            $route['m'] = $publicApp[0];
        }
        if (! isset($route['c'])) {
            $route['c'] = 'Index';
        }
        if (! isset($route['f'])) {
            $route['f'] = 'index';
        }
        if (! in_array(strtolower($route['m']), $publicApp)) {
            error('您访问的模块' . $route['m'] . '未开放,请核对后重试！');
        }
        return $route;
    }

    private static function resolveController($route) {
        define('M', strtolower($route['m']));
        define('APP_MODEL_PATH', APP_PATH . '/' . M . '/model');
        define('APP_CONTROLLER_PATH', APP_PATH . '/' . M . '/controller');
        if (($tplDir = Config::get('tpl_dir')) && is_array($tplDir) && array_key_exists(M, $tplDir)) {
            if (strpos($tplDir[M], ROOT_PATH) === false) {
                define('APP_VIEW_PATH', ROOT_PATH . $tplDir[M]);
            } else {
                define('APP_VIEW_PATH', $tplDir[M]);
            }
        } else {
            define('APP_VIEW_PATH', APP_PATH . '/' . M . '/view');
        }
        if (strpos($route['c'], '.') > 0) {
            $controllerPath = str_replace('.', '/', $route['c']);
            $controller = ucfirst(basename($controllerPath));
            $controllerPath = dirname($controllerPath) . '/' . $controller;
        } else {
            $controller = ucfirst($route['c']);
            $controllerPath = $controller;
        }
        $controllerFile = APP_CONTROLLER_PATH . '/' . $controllerPath . 'Controller.php';
        $reservedControllers = array('List', 'Content', 'About');
        $secondRvar = Config::get('second_rvar', true);
        if (! is_array($secondRvar)) {
            $secondRvar = array();
        }
        $pathOffset = 0;
        if (M == 'home' && (! file_exists($controllerFile) || in_array($controller, $reservedControllers))) {
            $controller = 'Index';
            $controllerPath = 'Index';
            define('F', $route['c']);
            $pathOffset = - 1;
        } elseif (M == 'home' && in_array($controller, $secondRvar)) {
            define('F', 'index');
            define('RVAR', $route['f']);
        } else {
            define('F', $route['f']);
        }
        define('C', $controller);
        if (isset($_SERVER["REQUEST_URI"])) {
            define('URL', $_SERVER["REQUEST_URI"]);
        } else {
            $origPathInfo = isset($_SERVER["ORIG_PATH_INFO"]) ? $_SERVER["ORIG_PATH_INFO"] : '';
            $queryString = isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : '';
            define('URL', $origPathInfo . '?' . $queryString);
        }
        $segmentCount = count(self::$pathSegments);
        for ($i = 3 + $pathOffset;
        $i < $segmentCount;
        $i = $i + 2) {
            if (isset(self::$pathSegments[$i + 1])) {
                $_GET[self::$pathSegments[$i]] = self::$pathSegments[$i + 1];
            } else {
                $_GET[self::$pathSegments[$i]] = null;
            }
        }
        return $controllerPath;
    }

    private static function loadModuleEnv() {
        Config::get('debug') ? Check::checkAppFile() : '';
        if (M == 'api') {
            if (! ! $sid = request('sid')) {
                session_id($sid);
                session_start();
            }
            header("Access-Control-Allow-Origin: *");
        } else {
            Check::checkBs();
            Check::checkOs();
        }
        if (file_exists(APP_PATH . '/common/function.php')) {
            require APP_PATH . '/common/function.php';
        }
        $moduleConfigFile = APP_PATH . '/' . M . '/config/config.php';
        if (file_exists($moduleConfigFile)) {
            Config::assign($moduleConfigFile);
        }
        $moduleFunctionFile = APP_PATH . '/' . M . '/function/function.php';
        if (file_exists($moduleFunctionFile)) {
            require $moduleFunctionFile;
        }
        if (file_exists(APP_PATH . '/common/' . ucfirst(M) . 'Controller.php')) {
            $commonControllerClass = '\\app\\common\\' . ucfirst(M) . 'Controller';
            $commonController = new $commonControllerClass();
        }
    }

    private static function dispatchController($controllerPath) {
        $controllerFile = $controllerPath . 'Controller.php';
        $controllerFileFull = APP_CONTROLLER_PATH . '/' . $controllerFile;
        $controllerClass = '\\app\\' . M . '\\controller\\' . str_replace('/', '\\', $controllerPath) . 'Controller';
        $action = F;
        if (! file_exists($controllerFileFull)) {
            http_response_code(404);
            $errorPage = ROOT_PATH . '/404.html';
            if (file_exists($errorPage)) {
                require $errorPage;
                exit();
            } else {
                error('对不起，您访问的页面类文件不存在，请核对后再试！');
            }
        }
        if (! class_exists($controllerClass)) {
            error('类文件中不存在访问的类名！' . $controllerClass);
        }
        $controller = new $controllerClass();
        if (method_exists($controllerClass, $action)) {
            if (strtolower($controllerClass) != strtolower($action)) {
                $result = $controller->$action();
            } else {
                $result = $controller;
            }
        } else {
            if (method_exists($controllerClass, '_empty')) {
                $result = $controller->_empty();
            } else {
                error('不存在您调用的类或方法' . $action . '，可能正在开发中，请耐心等待！');
            }
        }
        if ($result !== null) {
            print_r($result);
            exit();
        }
    }

    private static function outputHtmlCache() {
        if (! Config::get('tpl_html_cache') || (defined('URL_BIND') && URL_BIND == 'api') || get('nocache', 'int') == 1) {
            return;
        }
        // 已登录会员不读取公共HTML缓存，避免命中包含其他会员权限内容的缓存文件
        if (isset($_COOKIE[session_name()]) && session('eko_uid')) {
            return;
        }
        $areaCacheFile = RUN_PATH . '/config/' . md5('area') . '.php';
        if (! Config::assign($areaCacheFile)) {
            return;
        }
        $languages = Config::get('lgs');
        if (! is_array($languages) || ! $languages) {
            return;
        }
        if (count($languages) > 1) {
            $host = get_http_host();
            foreach ($languages as $language) {
                if (isset($language['domain'], $language['acode']) && $language['domain'] == $host) {
                    cookie('lg', $language['acode']);
                    break;
                }
            }
        }
        $blackLgs = array('eko', 'system');
        if (! isset($_COOKIE['lg']) || in_array($_COOKIE['lg'], $blackLgs)) {
            $defaultLg = current($languages);
            if (isset($defaultLg['acode'])) {
                cookie('lg', $defaultLg['acode']);
            }
        }
        $configCacheFile = RUN_PATH . '/config/' . md5('config') . '.php';
        if (! Config::assign($configCacheFile)) {
            return;
        }
        if (Config::get('open_wap') && (is_mobile() || Config::get('wap_domain') == get_http_host())) {
            $themeSuffix = 'wap';
        } else {
            $themeSuffix = '';
        }
        $requestUri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
        $cacheFile = RUN_PATH . '/cache/' . md5(get_http_url() . $requestUri . cookie('lg') . $themeSuffix) . '.html';
        if (file_exists($cacheFile) && time() - filemtime($cacheFile) < Config::get('tpl_html_cache_time')) {
            ob_start();
            include $cacheFile;
            $cacheContent = ob_get_contents();
            ob_end_clean();
            $acceptEncoding = isset($_SERVER["HTTP_ACCEPT_ENCODING"]) ? $_SERVER["HTTP_ACCEPT_ENCODING"] : '';
            if (Config::get('gzip') && ! headers_sent() && extension_loaded("zlib") && strstr($acceptEncoding, "gzip")) {
                $cacheContent = gzencode($cacheContent, 6);
                header("Content-Encoding: gzip");
                header("Vary: Accept-Encoding");
                header("Content-Length: " . strlen($cacheContent));
            }
            echo $cacheContent;
            exit();
        }
    }
}
