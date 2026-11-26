<?php
/**
 *  应用配置控制器
 */
namespace app\admin\controller\system;

use core\basic\Controller;
use app\admin\model\system\ConfigModel;
use core\basic\Config;
use core\basic\Url;

class ConfigController extends Controller
{

    private $model;

    public function __construct()
    {
        $this->model = new ConfigModel();
    }

    // 应用配置列表
    public function index()
    {

        if (! ! $action = get('action')) {
            switch ($action) {
                case 'sendemail':
                    $rs = sendmail($this->config(), get('to'), '【' . CMSNAME . '】测试邮件', '欢迎您使用' . CMSNAME . '网站开发管理系统！');
                    if ($rs === true) {
                        alert_back('测试邮件发送成功！');
                    } else {
                        error('发送失败：' . $rs);
                    }
                    break;
            }
        }
        
        // 修改参数配置
        if ($_POST) {
            unset($_POST['upload']); // 去除上传组件

            // IndexNow：推送密钥必填；仅允许相对路径或本站完整 URL（跨域 keyLocation 会被引擎拒绝且本系统无法代管）
            $indexnow_success_msg = '修改成功！';
            if (post('submit') == 'indexnow') {
                $indexnow_tab = url('/admin/Config/seo' . get_tab('t12'), false);
                $old_indexnow_key = (string) $this->config('indexnow_key');
                $indexnow_key = post('indexnow_key');
                $indexnow_location = trim((string) post('indexnow_key_location'));
                if ($indexnow_location !== '' && is_indexnow_remote_key_location($indexnow_location)) {
                    error('密钥文件地址不允许使用跨域远程地址（IndexNow要求与推送页面同域）！请填写相对路径或本站完整URL，或留空使用网站根目录密钥文件。', $indexnow_tab, 8);
                }
                if (! check_indexnow_key($indexnow_key)) {
                    $indexnow_key = read_indexnow_key_from_local_location($indexnow_location);
                    if ($indexnow_key) {
                        $_POST['indexnow_key'] = $indexnow_key;
                    }
                }
                if (! check_indexnow_key(post('indexnow_key'))) {
                    error('请填写有效的IndexNow推送密钥（8-128位字母、数字或连字符）！也可仅填写站点内可读取的密钥文件地址（相对路径或本站完整URL），系统将自动读取文件内容作为密钥。', $indexnow_tab, 8);
                }
                // 密钥相对已保存值发生变化时清空自定义地址，避免 key 与旧文件内容不一致；仅填 location 回填出 key 时不触发
                $indexnow_key = post('indexnow_key');
                $indexnow_location = trim((string) post('indexnow_key_location'));
                if (check_indexnow_key($old_indexnow_key) && $indexnow_key !== $old_indexnow_key && $indexnow_location !== '') {
                    $_POST['indexnow_key_location'] = '';
                    $indexnow_success_msg = '修改成功！推送密钥已变更，已清空密钥文件地址，改用网站根目录密钥文件！';
                }
                // basename 校验须在落库前（且在密钥回填/变更清空之后），避免非法地址写入 ek_config
                $indexnow_location = trim((string) post('indexnow_key_location'));
                if ($indexnow_location !== '' && ! indexnow_location_basename_ok(post('indexnow_key'), $indexnow_location)) {
                    error('密钥文件名必须为' . post('indexnow_key') . '.txt（例如 /static/' . post('indexnow_key') . '.txt）！', $indexnow_tab, 8);
                }
            }

            // llms.txt：复选框未勾选时浏览器不提交该项，需显式补0，否则无法关闭已开启的范围
            if (post('submit') == 'llms') {
                foreach (array('llms_inc_about', 'llms_inc_list', 'llms_inc_content') as $llms_scope_key) {
                    if (! isset($_POST[$llms_scope_key])) {
                        $_POST[$llms_scope_key] = '0';
                    }
                }
                // Optional段栏目为复选框数组，归一化为逗号串后按普通配置项入库
                $_POST['llms_optional_scodes'] = $this->mergeLlmsOptionalScodes(isset($_POST['llms_optional_sorts']) ? $_POST['llms_optional_sorts'] : array());
                unset($_POST['llms_optional_sorts']);
            }

            // 结构化数据：数组项归一化为普通配置项，并做枚举/长度校验
            if (post('submit') == 'jsonld') {
                $this->prepareJsonLdConfig();
            }

            // AI 配置须在写入循环前完成全部校验，失败时任何 ai_* 均不入库
            if (post('submit') == 'ai' || $this->hasAiFields()) {
                $ai_error = $this->checkAiConfigInput();
                if ($ai_error !== '') {
                    error($ai_error, url('/admin/Config/index' . get_tab('t11'), false), 5);
                }
            }

            foreach ($_POST as $key => $value) {
                if (! preg_match('/^[\w\-]+$/', $key)) {
                    continue;
                }
                $config = array(
                    'debug',
                    'pagenum',
                    'session_in_sitepath'
                );
                if (in_array($key, $config)) {
                    $value = post($key);
                    $this->modConfig($key, $value);
                } else {
                    $this->modDbConfig($key);
                }
            }

            $this->log('修改参数配置成功！');
            path_delete(RUN_PATH . '/config'); // 清理缓存的配置文件
            if (post('submit') == 'jsonld') {
                path_delete(RUN_PATH . '/cache'); // 结构化数据随HTML缓存输出，需清理静态缓存
            }
            
            // IndexNow：根目录写密钥文件；密钥未变且仍填了本站地址时同步写入该文件
            if (post('submit') == 'indexnow' && ! ! $indexnow_key = post('indexnow_key')) {
                $indexnow_tab = url('/admin/Config/seo' . get_tab('t12'), false);
                if (! check_indexnow_key($indexnow_key)) {
                    error('IndexNow密钥格式不正确，须为8-128位的字母、数字或连字符，密钥未保存！', $indexnow_tab, 5);
                }
                $indexnow_location = trim((string) post('indexnow_key_location'));
                $root_ok = save_indexnow_key_file($indexnow_key);
                if ($indexnow_location !== '') {
                    if (! write_indexnow_key_to_local_location($indexnow_key, $indexnow_location)) {
                        error('配置已保存，但无法写入密钥文件地址对应的本地文件，请检查路径及写入权限，或手工将文件内容更新为' . $indexnow_key . '！', $indexnow_tab, 10);
                    }
                    if (! $root_ok) {
                        error('配置已保存，密钥文件地址已同步，但根目录密钥文件创建失败，请检查网站根目录写入权限！', $indexnow_tab, 10);
                    }
                } elseif (! $root_ok) {
                    error('配置已保存，但密钥文件创建失败，请检查网站根目录写入权限，或手工上传内容为' . $indexnow_key . '的文件' . $indexnow_key . '.txt到网站根目录！', $indexnow_tab, 10);
                }
            }

            switch (post('submit')) {
                case 'email':
                    success('修改成功！', url('/admin/Config/index' . get_tab('t2'), false));
                    break;
                case 'baidu':
                    success('修改成功！', url('/admin/Config/seo' . get_tab('t3'), false));
                    break;
                case 'api':
                    success('修改成功！', url('/admin/Config/index' . get_tab('t4'), false));
                    break;
                case 'watermark':
                    success('修改成功！', url('/admin/Config/index' . get_tab('t5'), false));
                    break;
                case 'security':
                    success('修改成功！', url('/admin/Config/index' . get_tab('t6'), false));
                    break;
                case 'urlrule':
                    success('修改成功！', url('/admin/Config/seo' . get_tab('t7'), false));
                    break;
                case 'pagetitle':
                    success('修改成功！', url('/admin/Config/seo' . get_tab('t8'), false));
                    break;
                case 'member':
                    success('修改成功！', url('/admin/Config/index' . get_tab('t9'), false));
                    break;
                case 'remoteattach':
                    success('修改成功！', url('/admin/Config/index' . get_tab('t10'), false));
                    break;
                case 'ai':
                    success('修改成功！', url('/admin/Config/index' . get_tab('t11'), false));
                    break;
                case 'indexnow':
                    success($indexnow_success_msg, url('/admin/Config/seo' . get_tab('t12'), false));
                    break;
                case 'jsonld':
                    success('修改成功！', url('/admin/Config/seo' . get_tab('t14'), false));
                    break;
                case 'llms':
                    success('修改成功！', url('/admin/Config/seo' . get_tab('t13'), false));
                    break;
                default:
                    success('修改成功！', url('/admin/Config/index', false));
            }
        }
        $configs = $this->model->getList();
        $configs['debug']['value'] = $this->config('debug');
        $configs['session_in_sitepath']['value'] = $this->config('session_in_sitepath');
        $configs['pagenum']['value'] = $this->config('pagenum');
        $configs['url_type']['value'] = $this->config('url_type');
        if (! isset($configs['tpl_html_cache']['value'])) {
            $configs['tpl_html_cache']['value'] = $this->config('tpl_html_cache');
        }
        if (! isset($configs['tpl_html_cache_time']['value'])) {
            $configs['tpl_html_cache_time']['value'] = $this->config('tpl_html_cache_time');
        }

        // 配置项在首次保存时才写入数据库，此处补空值避免模板取值报错
        if (! isset($configs['indexnow_key']['value'])) {
            $configs['indexnow_key']['value'] = '';
        }
        if (! isset($configs['indexnow_key_location']['value'])) {
            $configs['indexnow_key_location']['value'] = '';
        }

        // AI API Key 仅向前端展示脱敏值，不输出密文，避免泄露
        $aiKeyOpensslWarn = function_exists('openssl_decrypt') ? '0':'1';
        if (isset($configs['ai_api_key']['value']) && $configs['ai_api_key']['value']) {
            $plain = aes_decrypt($configs['ai_api_key']['value']);
            $configs['ai_api_key']['value'] = $plain ? mask_secret($plain) : '';
        }

        // llms.txt配置项首次保存前不在库中，此处补默认值供模板取值
        $llms_defaults = array(
            'llms_open' => '0',
            'llms_inc_about' => '1',
            'llms_inc_list' => '1',
            'llms_inc_content' => '1',
            'llms_sort_num' => '20',
            'llms_desc_source' => 'description',
            'llms_desc_len' => '100',
            'llms_with_sitemap' => '1',
            'llms_intro' => '',
            'llms_optional_scodes' => ''
        );
        foreach ($llms_defaults as $llms_key => $llms_value) {
            if (! isset($configs[$llms_key]['value'])) {
                $configs[$llms_key]['value'] = $llms_value;
            }
        }

        $llms_selected = array_filter(explode(',', llms_normalize_scodes($configs['llms_optional_scodes']['value'])));
        $this->assign('llms_sort_checkbox', $this->makeLlmsSortCheckbox(model('admin.content.ContentSort')->getSelect(), $llms_selected));
        // 地址含未经校验的 HTTP_HOST，落在提示气泡的属性里，输出前先转义
        $this->assign('llms_url', htmlspecialchars(get_http_url() . Url::home('llms.txt', false), ENT_QUOTES));

        $this->assign('ai_key_openssl_warn', $aiKeyOpensslWarn);
        $ai_provider_hints = array();
        foreach (ai_provider_defs() as $ai_key => $ai_def) {
            $ai_provider_hints[$ai_key] = array(
                'name' => $ai_def['name'],
                'base' => $ai_def['base'],
                'model' => $ai_def['model'],
                'required' => $ai_def['required']
            );
        }
        $this->assign('ai_provider_json', json_encode($ai_provider_hints, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
        $this->assign('is_sqlite', get_db_type() === 'sqlite');
        $this->assign('configs', $configs);
        
        $this->assign('groups', model('admin.member.MemberGroup')->getSelect());
        
        $this->display('system/config.html');
    }

    // SEO配置页（百度接口、IndexNow推送、llms.txt、URL规则、标题样式）
    public function seo()
    {
        $configs = $this->model->getList();

        // 配置项在首次保存时才写入数据库，此处补空值避免模板取值报错
        $seo_defaults = array(
            'indexnow_key',
            'indexnow_key_location',
            'baidu_zz_token',
            'baidu_ks_token',
            'url_rule_type',
            'url_rule_content_path',
            'url_index_404',
            'index_title',
            'about_title',
            'list_title',
            'content_title',
            'search_title',
            'register_title',
            'login_title',
            'ucenter_title',
            'umodify_title',
            'other_title'
        );
        foreach ($seo_defaults as $seo_key) {
            if (! isset($configs[$seo_key]['value'])) {
                $configs[$seo_key]['value'] = '';
            }
        }

        // llms.txt配置项首次保存前不在库中，此处补默认值供模板取值
        $llms_defaults = array(
            'llms_open' => '0',
            'llms_inc_about' => '1',
            'llms_inc_list' => '1',
            'llms_inc_content' => '1',
            'llms_sort_num' => '20',
            'llms_desc_source' => 'description',
            'llms_desc_len' => '100',
            'llms_with_sitemap' => '1',
            'llms_intro' => '',
            'llms_optional_scodes' => ''
        );
        foreach ($llms_defaults as $llms_key => $llms_value) {
            if (! isset($configs[$llms_key]['value'])) {
                $configs[$llms_key]['value'] = $llms_value;
            }
        }

        $llms_selected = array_filter(explode(',', llms_normalize_scodes($configs['llms_optional_scodes']['value'])));
        $this->assign('llms_sort_checkbox', $this->makeLlmsSortCheckbox(model('admin.content.ContentSort')->getSelect(), $llms_selected));
        // 地址含未经校验的 HTTP_HOST，落在提示气泡的属性里，输出前先转义
        $this->assign('llms_url', htmlspecialchars(get_http_url() . Url::home('llms.txt', false), ENT_QUOTES));

        // 结构化数据(JSON-LD)配置项首次保存前不在库中，此处补默认值供模板取值
        $jsonld_defaults = array(
            'jld_open' => '0',
            'jld_pos' => 'body',
            'jld_home' => '1',
            'jld_search' => '0',
            'jld_author' => '0',
            'jld_breadcrumb' => '1',
            'jld_comment' => '0',
            'jld_about' => '0',
            'jld_contact' => '0',
            'jld_author_name' => '',
            'jld_author_url' => '',
            'jld_dtype' => '0',
            'jld_org_type' => '',
            'jld_org_name' => '',
            'jld_org_legal' => '',
            'jld_org_url' => '',
            'jld_person_name' => '',
            'jld_person_job' => '',
            'jld_person_image' => '',
            'jld_contact_type' => '',
            'jld_contact_phone' => '',
            'jld_contact_url' => '',
            'jld_logo' => '',
            'jld_open_days' => '',
            'jld_open_start' => '',
            'jld_open_end' => '',
            'jld_price' => '',
            'jld_geo_lat' => '',
            'jld_geo_lng' => '',
            'jld_street' => '',
            'jld_city' => '',
            'jld_region' => '',
            'jld_zip' => '',
            'jld_country' => '',
            'jld_brand' => '',
            'jld_image' => ''
        );
        foreach ($jsonld_defaults as $jsonld_key => $jsonld_value) {
            if (! isset($configs[$jsonld_key]['value'])) {
                $configs[$jsonld_key]['value'] = $jsonld_value;
            }
        }

        $sort_tree = model('admin.content.ContentSort')->getList();
        $flat_sorts = $this->jsonLdFlatSorts($sort_tree);
        $single_sorts = array();
        $category_sorts = array();
        foreach ($flat_sorts as $flat_scode => $flat_sort) {
            if ((string) $flat_sort['type'] === '1') {
                $single_sorts[$flat_scode] = $flat_sort['name'];
            } else {
                $category_sorts[$flat_scode] = $flat_sort['name'];
            }
        }

        $about_options = '<option value="0">请选择</option>' . $this->makeJsonLdOptions($single_sorts, $configs['jld_about']['value']);
        $contact_options = '<option value="0">请选择</option>' . $this->makeJsonLdOptions($single_sorts, $configs['jld_contact']['value']);
        $this->assign('jld_about_options', $about_options);
        $this->assign('jld_contact_options', $contact_options);

        $this->assign('jld_list_options', $this->makeJsonLdOptions(jsonld_list_types(), ''));
        $this->assign('jld_schema_options', $this->makeJsonLdOptions(jsonld_schema_types(), ''));
        $this->assign('jld_org_type_options', $this->makeJsonLdOptions(jsonld_org_types(), $configs['jld_org_type']['value']));
        $this->assign('jld_contact_type_options', $this->makeJsonLdOptions(jsonld_contact_types(), $configs['jld_contact_type']['value']));

        $this->assign('jld_module_rows', $this->makeJsonLdModuleRows(model('admin.content.Model')->getSelectAll(), $configs));
        $this->assign('jld_category_rows', $this->makeJsonLdCategoryRows($configs, $category_sorts));
        $this->assign('jld_category_row_tpl', $this->makeJsonLdCategoryRow($category_sorts, '', '', '', true));
        $this->assign('jld_social_rows', $this->makeJsonLdSocialRows($configs));

        $open_days = explode(',', $configs['jld_open_days']['value']);
        $open_days_html = '';
        foreach (jsonld_week_days() as $day_value => $day_label) {
            $checked = in_array($day_value, $open_days, true) ? ' checked' : '';
            $open_days_html .= '<label class="jld-day"><input type="checkbox" name="jld_open_days[]" value="' . $day_value . '"' . $checked . '> ' . $day_label . '</label>';
        }
        $this->assign('jld_open_days_checkbox', $open_days_html);

        // 知识图谱默认取值：组织名称/网址缺省使用站点信息
        $site = model('admin.content.Site')->getList();
        $this->assign('jld_site_title', $site ? $site->title : '');
        $this->assign('jld_site_url', get_http_url() . SITE_INDEX_DIR . '/');

        $this->assign('configs', $configs);
        $this->display('system/config_seo.html');
    }

    /**
     * 合并Optional段栏目选择
     *
     * 该配置项跨语言共用，提交表单只含当前区域可见栏目，
     * 因此需保留其它区域的既有选择，避免切换区域保存后互相覆盖。
     */
    private function mergeLlmsOptionalScodes($checked)
    {
        $visible = array();
        $this->collectSortScodes(model('admin.content.ContentSort')->getSelect(), $visible);

        $kept = array();
        foreach (explode(',', llms_normalize_scodes($this->config('llms_optional_scodes'))) as $scode) {
            if ($scode !== '' && ! in_array($scode, $visible, true)) {
                $kept[] = $scode;
            }
        }

        $merged = llms_normalize_scodes(array_merge($kept, is_array($checked) ? $checked : array()));
        // ek_config.value 仅 varchar(200)；超限时若静默截断会先丢掉排在末尾的当前区域勾选
        if (strlen($merged) > 200) {
            error('Optional段栏目数量超出配置项容量限制，请减少勾选后再保存！', url('/admin/Config/seo' . get_tab('t13'), false), 5);
        }
        return $merged;
    }

    // 递归收集栏目树中的全部栏目编码
    private function collectSortScodes($tree, array &$scodes)
    {
        if (! is_array($tree)) {
            return;
        }
        foreach ($tree as $value) {
            $scodes[] = $value->scode;
            if ($value->son) {
                $this->collectSortScodes($value->son, $scodes);
            }
        }
    }

    /**
     * 结构化数据：表单数组项归一化、枚举校验与入库前校验
     */
    private function prepareJsonLdConfig()
    {
        $tab = url('/admin/Config/seo' . get_tab('t14'), false);

        foreach (array('jld_home', 'jld_search', 'jld_author', 'jld_breadcrumb', 'jld_comment') as $key) {
            if (! isset($_POST[$key])) {
                $_POST[$key] = '0';
            }
        }

        $_POST['jld_pos'] = (isset($_POST['jld_pos']) && $_POST['jld_pos'] === 'head') ? 'head' : 'body';
        $dtype = isset($_POST['jld_dtype']) ? (string) $_POST['jld_dtype'] : '0';
        $_POST['jld_dtype'] = in_array($dtype, array('0', '1', '2'), true) ? $dtype : '0';

        if (! isset($_POST['jld_org_type']) || ! array_key_exists((string) $_POST['jld_org_type'], jsonld_org_types())) {
            $_POST['jld_org_type'] = '';
        }
        if (! isset($_POST['jld_contact_type']) || ! array_key_exists((string) $_POST['jld_contact_type'], jsonld_contact_types())) {
            $_POST['jld_contact_type'] = '';
        }
        foreach (array('jld_about','jld_contact') as $key) {
            $value = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '0';
            $_POST[$key] = preg_match('/^\d+$/', $value) ? $value : '0';
        }

        // 营业日：复选框数组转逗号串
        $valid_days = array_keys(jsonld_week_days());
        $days = isset($_POST['jld_open_days']) && is_array($_POST['jld_open_days']) ? array_map('strval', $_POST['jld_open_days']) : array();
        $days = array_values(array_intersect($valid_days, $days));
        $_POST['jld_open_days'] = implode(',', $days);

        // 社交资料：校验后按序号拆分为独立配置项
        $socials = array();
        if (isset($_POST['jld_social']) && is_array($_POST['jld_social'])) {
            foreach ($_POST['jld_social'] as $url) {
                $url = trim(strip_tags((string) $url));
                if ($url === '') {
                    continue;
                }
                if (! preg_match('#^https?://#i', $url) || mb_strlen($url, 'UTF-8') > 200) {
                    error('社交资料必须以 http:// 或 https:// 开头，且长度不超过200个字符！', $tab, 5);
                }
                $socials[] = $url;
            }
        }
        unset($_POST['jld_social']);
        $socials = array_slice(array_values(array_unique($socials)), 0, 20);
        $this->model->delValueByPrefix('jld_soc_');
        foreach ($socials as $index => $url) {
            $_POST['jld_soc_' . ($index + 1)] = $url;
        }

        // 模型结构化映射：mcode => 列表类型|结构化类型
        $mod_list = isset($_POST['jld_mod_list']) && is_array($_POST['jld_mod_list']) ? $_POST['jld_mod_list'] : array();
        $mod_schema = isset($_POST['jld_mod_schema']) && is_array($_POST['jld_mod_schema']) ? $_POST['jld_mod_schema'] : array();
        unset($_POST['jld_mod_list'], $_POST['jld_mod_schema']);
        $valid_mcodes = array();
        foreach (model('admin.content.Model')->getSelectAll() as $item) {
            $valid_mcodes[$item->mcode] = true;
        }
        $this->model->delValueByPrefix('jld_mod_');
        foreach ($valid_mcodes as $mcode => $unused) {
            $list = isset($mod_list[$mcode]) ? trim((string) $mod_list[$mcode]) : '';
            $schema = isset($mod_schema[$mcode]) ? trim((string) $mod_schema[$mcode]) : '';
            if (! array_key_exists($list, jsonld_list_types())) {
                $list = '';
            }
            if (! array_key_exists($schema, jsonld_schema_types())) {
                $schema = '';
            }
            if ($list === '' && $schema === '') {
                continue;
            }
            $_POST['jld_mod_' . $mcode] = $list . '|' . $schema;
        }

        // 特定分类结构化映射：仅覆盖当前区域可见栏目，保留其它区域配置
        $visible = array();
        $this->collectSortScodes(model('admin.content.ContentSort')->getSelect(), $visible);
        $merged = array();
        $all = Config::get();
        if (is_array($all)) {
            foreach ($all as $key => $value) {
                if (preg_match('/^jld_cat_([\w\-]+)$/', (string) $key, $match) && ! in_array($match[1], $visible, true)) {
                    $merged[$match[1]] = (string) $value;
                }
            }
        }
        $cat_scodes = isset($_POST['jld_cat_scode']) && is_array($_POST['jld_cat_scode']) ? array_values($_POST['jld_cat_scode']) : array();
        $cat_lists = isset($_POST['jld_cat_list']) && is_array($_POST['jld_cat_list']) ? array_values($_POST['jld_cat_list']) : array();
        $cat_schemas = isset($_POST['jld_cat_schema']) && is_array($_POST['jld_cat_schema']) ? array_values($_POST['jld_cat_schema']) : array();
        unset($_POST['jld_cat_scode'], $_POST['jld_cat_list'], $_POST['jld_cat_schema']);
        foreach ($cat_scodes as $index => $scode) {
            $scode = trim((string) $scode);
            if (! in_array($scode, $visible, true)) {
                continue;
            }
            unset($merged[$scode]);
            $list = isset($cat_lists[$index]) ? trim((string) $cat_lists[$index]) : '';
            $schema = isset($cat_schemas[$index]) ? trim((string) $cat_schemas[$index]) : '';
            if (! array_key_exists($list, jsonld_list_types())) {
                $list = '';
            }
            if (! array_key_exists($schema, jsonld_schema_types())) {
                $schema = '';
            }
            if ($list === '' && $schema === '') {
                continue;
            }
            $merged[$scode] = $list . '|' . $schema;
        }
        $this->model->delValueByPrefix('jld_cat_');
        foreach ($merged as $scode => $value) {
            if (preg_match('/^[\w\-]+$/', $scode)) {
                $_POST['jld_cat_' . $scode] = $value;
            }
        }

        // 配置值容量限制（ek_config.value 为 varchar(200)）
        foreach ($_POST as $key => $value) {
            if (strpos((string) $key, 'jld_') !== 0 || ! is_scalar($value)) {
                continue;
            }
            if (mb_strlen((string) $value, 'UTF-8') > 200) {
                error('「' . $key . '」内容超过200个字符，配置未保存，请缩短后再提交！', $tab, 5);
            }
        }
    }

    // 递归展开栏目树，返回 scode => array(name, type)
    private function jsonLdFlatSorts($tree, $prefix = '')
    {
        $flat = array();
        if (! is_array($tree)) {
            return $flat;
        }
        foreach ($tree as $value) {
            $flat[$value->scode] = array(
                'name' => $prefix . $value->name,
                'type' => $value->type
            );
            if ($value->son) {
                $flat = $flat + $this->jsonLdFlatSorts($value->son, $prefix . $value->name . ' / ');
            }
        }
        return $flat;
    }

    private function makeJsonLdOptions(array $options, $selected)
    {
        $html = '';
        foreach ($options as $value => $label) {
            $value = (string) $value;
            $select = ((string) $selected === $value) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($value, ENT_QUOTES) . '"' . $select . '>' . $label . '</option>';
        }
        return $html;
    }

    // 模型数据结构行
    private function makeJsonLdModuleRows($models, array $configs)
    {
        $html = '';
        if (! is_array($models)) {
            return $html;
        }
        foreach ($models as $model) {
            $mcode = $model->mcode;
            $stored = isset($configs['jld_mod_' . $mcode]['value']) ? (string) $configs['jld_mod_' . $mcode]['value'] : '';
            $parts = explode('|', $stored);
            $list = isset($parts[0]) ? $parts[0] : '';
            $schema = isset($parts[1]) ? $parts[1] : '';
            $html .= '<div class="jld-module-row">';
            $html .= '<div class="jld-module-name">' . htmlspecialchars($model->name, ENT_QUOTES) . '标题</div>';
            $html .= '<div class="jld-module-fields">';
            $html .= '<div class="jld-subfield"><label>列表类型</label><select name="jld_mod_list[' . htmlspecialchars($mcode, ENT_QUOTES) . ']"><option value="">请选择</option>' . $this->makeJsonLdOptions(jsonld_list_types(), $list) . '</select></div>';
            $html .= '<div class="jld-subfield"><label>结构化数据类型</label><select name="jld_mod_schema[' . htmlspecialchars($mcode, ENT_QUOTES) . ']"><option value="">请选择</option>' . $this->makeJsonLdOptions(jsonld_schema_types(), $schema) . '</select></div>';
            $html .= '</div></div>';
        }
        return $html;
    }

    // 特定分类数据结构行（已保存项）
    private function makeJsonLdCategoryRows(array $configs, array $sorts)
    {
        $html = '';
        foreach ($configs as $key => $item) {
            if (! preg_match('/^jld_cat_([\w\-]+)$/', (string) $key, $match)) {
                continue;
            }
            $scode = $match[1];
            if (! isset($sorts[$scode])) {
                continue;
            }
            $value = isset($item['value']) ? (string) $item['value'] : '';
            $parts = explode('|', $value);
            $html .= $this->makeJsonLdCategoryRow($sorts, $scode, isset($parts[0]) ? $parts[0] : '', isset($parts[1]) ? $parts[1] : '');
        }
        return $html;
    }

    // 单个分类结构化行
    private function makeJsonLdCategoryRow(array $sorts, $selected_scode = '', $list = '', $schema = '')
    {
        $html = '<div class="jld-cat-row">';
        $html .= '<div class="jld-subfield jld-cat-sort"><label>分类</label><select name="jld_cat_scode[]"><option value="">请选择</option>' . $this->makeJsonLdOptions($sorts, $selected_scode) . '</select></div>';
        $html .= '<div class="jld-subfield"><label>列表类型</label><select name="jld_cat_list[]"><option value="">请选择</option>' . $this->makeJsonLdOptions(jsonld_list_types(), $list) . '</select></div>';
        $html .= '<div class="jld-subfield"><label>结构化数据类型</label><select name="jld_cat_schema[]"><option value="">请选择</option>' . $this->makeJsonLdOptions(jsonld_schema_types(), $schema) . '</select></div>';
        $html .= '<button type="button" class="jld-row-del" title="删除">×</button>';
        $html .= '</div>';
        return $html;
    }

    // 社交资料行
    private function makeJsonLdSocialRows(array $configs)
    {
        $html = '';
        foreach ($configs as $key => $item) {
            if (! preg_match('/^jld_soc_(\d+)$/', (string) $key)) {
                continue;
            }
            $value = isset($item['value']) ? (string) $item['value'] : '';
            $html .= '<div class="jld-social-row"><input type="text" name="jld_social[]" value="' . htmlspecialchars($value, ENT_QUOTES) . '" placeholder="https://..."><button type="button" class="jld-row-del" title="删除">×</button></div>';
        }
        return $html;
    }

    // llms.txt Optional段栏目复选框，标题带上父级路径以区分同名栏目
    private function makeLlmsSortCheckbox($tree, array $selected, $prefix = '')
    {
        $html = '';
        if (! is_array($tree)) {
            return $html;
        }
        foreach ($tree as $value) {
            // 栏目名出库时仍是转义态，与栏目下拉列表一样直接插值，再转义会显示成 &amp;
            $name = $prefix . $value->name;
            $checked = in_array($value->scode, $selected, true) ? ' checked="checked"' : '';
            $html .= '<input type="checkbox" name="llms_optional_sorts[]" value="' . $value->scode . '" title="' . $name . '"' . $checked . '>';
            if ($value->son) {
                $html .= $this->makeLlmsSortCheckbox($value->son, $selected, $name . ' / ');
            }
        }
        return $html;
    }

    private function hasAiFields()
    {
        foreach (array_keys($_POST) as $key) {
            if (strpos((string) $key, 'ai_') === 0) {
                return true;
            }
        }
        return false;
    }

    // AI 配置保存前校验：格式、必填与沿用旧 Key 的主机端口；不访问上游
    private function checkAiConfigInput()
    {
        $saved_provider = (string) $this->config('ai_provider');
        $saved_base = (string) $this->config('ai_api_base');
        $provider = isset($_POST['ai_provider']) ? (string) post('ai_provider') : $saved_provider;
        $base = isset($_POST['ai_api_base']) ? (string) post('ai_api_base') : $saved_base;
        $model = isset($_POST['ai_model']) ? (string) post('ai_model') : (string) $this->config('ai_model');
        if ($provider === '') {
            $provider = 'deepseek';
        }
        if (strlen($base) > 200 || strlen($model) > 200) {
            return 'AI 配置未保存：API URL 或模型名称过长（入库后超过200字符），请缩短后再提交！';
        }
        $eff = ai_effective_config($provider, ai_input_unescape($base), ai_input_unescape($model));
        if (! $eff['ok']) {
            return 'AI 配置未保存：' . $eff['error'] . '！';
        }

        $key = isset($_POST['ai_api_key']) ? (string) post('ai_api_key') : '';
        if ($key !== '' && ! is_masked_secret($key)) {
            if (! aes_encrypt('probe')) {
                return '服务端不支持 AES 加密，请联系主机商开启 openssl 扩展！';
            }
            return '';
        }
        if ((string) $this->config('ai_api_key') === '') {
            return '';
        }
        $defs = ai_provider_defs();
        if (! isset($defs[$saved_provider])) {
            $saved_provider = 'deepseek';
        }
        $saved_endpoint = ai_provider_endpoint($saved_provider, ai_input_unescape($saved_base));
        if (! ai_endpoint_same_host($saved_endpoint, $eff['endpoint'])) {
            return 'AI 配置未保存：API URL 的主机或端口与已保存配置不同，请重新输入完整 API Key 后再提交！';
        }
        return '';
    }

    // 修改配置文件
    private function modConfig($key, $value)
    {
        $value = str_replace(' ', '', $value); // 去除空格
        $value = str_replace('，', ',', $value); // 转换可能输入的中文逗号
        if (! preg_match('/^[\w\s\,\-]+$/', $value)) {
            return;
        }
        
        $config = file_get_contents(CONF_PATH . '/config.php');
        if (preg_match("'$key'", $config)) {
            if (preg_match('/^[0-9]+$/', $value)) {
                $config = preg_replace('/(\'' . $key . '\'([\s]+)?=>([\s]+)?)[\w\'\"\s,]+,/', '${1}' . $value . ',', $config);
            } else {
                $config = preg_replace('/(\'' . $key . '\'([\s]+)?=>([\s]+)?)[\w\'\"\s,]+,/', '${1}\'' . $value . '\',', $config);
            }
        } else {
            $config = preg_replace('/(return array\()/', "$1\r\n\r\n\t'$key' => '$value',", $config); // 自动新增配置
        }
        return file_put_contents(CONF_PATH . '/config.php', $config);
    }

    // 修改数据库配置
    private function modDbConfig($key)
    {
        $value = post($key);

        // 动态缓存开关仅允许 0/1
        if ($key == 'tpl_html_cache') {
            $value = ((string) $value === '1') ? '1' : '0';
        }

        // 远程抓图仅 0 为禁用，其余值归一为启用
        if ($key == 'ueditor_catch_remote') {
            $value = ((string) $value === '0') ? '0' : '1';
        }

        // 缓存有效期须为正整数字符串（避免 (int) 截断大 TTL）
        if ($key == 'tpl_html_cache_time') {
            if ($value === null || $value === '' || $value === false) {
                $value = '900000000000';
            } elseif (! preg_match('/^[1-9]\d*$/', (string) $value)) {
                return;
            } else {
                $value = (string) $value;
            }
        }
        
        // 如果开启伪静态时自动拷贝文件
        if ($key == 'url_rule_type' && $value == 2) {
            deploy_pseudo_static_rewrite(get_server_soft());
            // 同步部署上传目录 MIME/nosniff 规则（审计 #25）
            if (function_exists('upload_ensure_htaccess')) {
                upload_ensure_htaccess(true);
            }
        }
        
        // 模板目录修改
        if (($key == 'tpl_html_dir') && $value) {
            
            // 不允许特殊字符
            if (! preg_match('/^\w+$/', $value)) {
                return;
            }
            
            $value = basename($value);
            $htmldir = $this->config('tpl_html_dir');
            $tpl_path = ROOT_PATH . current($this->config('tpl_dir')) . '/' . model('admin.content.ContentSort')->getTheme();
            
            if (! $htmldir || ! file_exists($tpl_path . '/' . $htmldir)) {
                if (! check_dir($tpl_path . '/' . $value, true)) {
                    return;
                } // 原来没有目录时只创建目录，创建失败时直接不修改
            } else {
                if ($value != $htmldir) {
                    if (file_exists($tpl_path . '/' . $value)) {
                        if (dir_copy($tpl_path . '/' . $htmldir, $tpl_path . '/' . $value)) {
                            path_delete($tpl_path . '/' . $htmldir, true); // 删除原来的
                        } else {
                            return; // 修改失败
                        }
                    } else {
                        if (! rename($tpl_path . '/' . $htmldir, $tpl_path . '/' . $value)) {
                            return; // 修改失败
                        }
                    }
                }
            }
        }
        
        if ($key == 'home_upload_ext') {
            $filtered = filter_upload_ext_allow(explode(',', $value));
            if (! $filtered) {
                return;
            }
            $value = implode(',', $filtered);
        }

        // AI API Key：未填写或为脱敏占位（含****）则跳过本次更新；否则加密后入库
        if ($key == 'ai_api_key') {
            if ($value === '' || $value === null || is_masked_secret($value)) {
                return;
            }
            if (! $value = aes_encrypt($value)) {
                error('服务端不支持 AES 加密，请联系主机商开启 openssl 扩展！', url('/admin/Config/index' . get_tab('t11'), false));
            }
        }
        
        // 数据分割处理
        $hander = array(
            'content_keyword_replace',
            'ip_deny',
            'ip_allow',
            'content_iframe_whitelist'
        );
        if (in_array($key, $hander) && $value) {
            $value = str_replace("\r\n", ",", $value); // 替换回车
            $value = str_replace("，", ",", $value); // 替换中文逗号分割符
        }

        // iframe 白名单：仅保留主机名（去协议、路径、端口、末尾点），小写去重
        if ($key == 'content_iframe_whitelist' && $value) {
            $hosts = array();
            foreach (explode(',', $value) as $item) {
                $host = filter_iframe_normalize_host($item);
                if ($host !== '' && ! in_array($host, $hosts, true)) {
                    $hosts[] = $host;
                }
            }
            $value = implode(',', $hosts);
        }
        
        // IndexNow密钥格式非法时不入库，避免脏值导致推送始终被拒
        if ($key == 'indexnow_key' && $value !== '' && $value !== null && ! check_indexnow_key($value)) {
            return;
        }

        if ($key == 'image_backend') {
            $value = strtolower(trim((string) $value));
            if (! in_array($value, array('auto', 'gd_only', 'prefer_imagick'), true)) {
                $value = 'auto';
            }
        }

        // llms.txt数量类配置越界时回落默认值，避免脏值导致输出规模失控
        if ($key == 'llms_sort_num') {
            $value = (int) $value;
            $value = ($value < 1 || $value > 500) ? '20' : (string) $value;
        }
        if ($key == 'llms_desc_len') {
            $value = (int) $value;
            $value = ($value < 1 || $value > 500) ? '100' : (string) $value;
        }
        if ($key == 'llms_desc_source' && ! in_array($value, array('description', 'none'), true)) {
            $value = 'description';
        }
        // ek_config.value 为 varchar(200)，超长会被数据库静默截断，此处主动收敛。
        // 此处的值仍是 post() 转义后的形态，只能按转义态截断，解码入库会破坏后台回显的转义保护
        if ($key == 'llms_intro') {
            $value = llms_truncate_intro($value, 190);
        }
        // Optional 段超长已在 mergeLlmsOptionalScodes 拦截，此处不再静默截断

        if ($this->model->checkConfig("name='$key'")) {
            $this->model->modValue($key, $value);
        } elseif ($key != 'submit' && $key != 'formcheck') {
            // 自动新增配置项
            $description = '';
            if ($key == 'content_iframe_whitelist') {
                $description = 'iframe域名级白名单';
            } elseif ($key == 'ueditor_catch_remote') {
                $description = '远程图片抓取';
            } elseif ($key == 'image_backend') {
                $description = '图像处理后端：auto/gd_only/prefer_imagick';
            } elseif ($key == 'tpl_html_cache') {
                $description = '动态缓存开关';
            } elseif ($key == 'tpl_html_cache_time') {
                $description = '缓存有效期(秒)';
            } elseif ($key == 'indexnow_key') {
                $description = 'IndexNow推送密钥';
            } elseif ($key == 'indexnow_key_location') {
                $description = 'IndexNow密钥文件地址';
            } elseif (strpos($key, 'llms_') === 0) {
                $llms_descriptions = array(
                    'llms_open' => 'llms.txt总开关',
                    'llms_inc_about' => 'llms.txt输出单页栏目',
                    'llms_inc_list' => 'llms.txt输出列表栏目',
                    'llms_inc_content' => 'llms.txt输出栏目内容',
                    'llms_sort_num' => 'llms.txt每栏目条数上限',
                    'llms_desc_source' => 'llms.txt说明文字来源',
                    'llms_desc_len' => 'llms.txt说明截断长度',
                    'llms_with_sitemap' => 'llms.txt附带站点地图',
                    'llms_intro' => 'llms.txt自定义补充说明',
                    'llms_optional_scodes' => 'llms.txtOptional段栏目'
                );
                $description = isset($llms_descriptions[$key]) ? $llms_descriptions[$key] : '';
            }
            $type = 2;
            if ($key == 'tpl_html_cache') {
                $type = 1;
            }
            $data = array(
                'name' => $key,
                'value' => $value,
                'type' => $type,
                'sorting' => 255,
                'description' => $description
            );
            return $this->model->addConfig($data);
        }
    }
}
