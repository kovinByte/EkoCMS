<?php
/**
 * 结构化数据（JSON-LD）生成与页面注入
 *
 * 读取后台“全局配置-结构化数据”中的配置项，按当前页面类型生成
 * schema.org 结构化数据，并插入到页面 head 或 body 的底部。
 */
namespace app\common;

use core\basic\Config;
use app\home\model\ParserModel;
use app\home\controller\ParserController;

class JsonLd
{

    private static $site;

    private static $company;

    private static $parser;

    /**
     * 将结构化数据注入页面HTML
     *
     * @param string $html 渲染完成的页面HTML
     * @param string $page 页面类型：index/list/content/about
     * @param mixed $data 页面数据：列表页为栏目对象，内容页为内容对象，单页为数组
     * @return string
     */
    public static function inject($html, $page, $data = null)
    {
        if (trim((string) Config::get('jld_open')) !== '1') {
            return $html;
        }
        // 主题模板已自行输出结构化数据时不重复插入
        if (stripos($html, 'application/ld+json') !== false) {
            return $html;
        }
        $graph = self::graph($page, $data);
        if (! $graph) {
            return $html;
        }
        if (count($graph) === 1) {
            $payload = array_merge(array('@context' => 'https://schema.org'), $graph[0]);
        } else {
            $payload = array('@context' => 'https://schema.org','@graph' => array_values($graph));
        }
        $json = self::encode($payload);
        if ($json === false) {
            return $html;
        }
        $script = '<script type="application/ld+json">' . $json . '</script>';
        // 优先使用配置位置，闭合标签缺失时回退到另一个位置
        $markers = trim((string) Config::get('jld_pos')) === 'head' ? array('</head>', '</body>') : array('</body>', '</head>');
        $pos = false;
        foreach ($markers as $marker) {
            $pos = strripos($html, $marker);
            if ($pos !== false) {
                break;
            }
        }
        if ($pos === false) { // 模板未闭合 head/body 时追加到页面末尾，避免整段丢失
            return $html . "\n" . $script;
        }
        return substr($html, 0, $pos) . $script . "\n" . substr($html, $pos);
    }

    /**
     * JSON 编码，兼容脏数据与非UTF-8内容
     *
     * @param array $payload
     * @return string|false
     */
    private static function encode(array $payload)
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE; // PHP7.2+：非法UTF-8字节替换为U+FFFD而不是整体失败
        }
        $json = json_encode($payload, $flags);
        if ($json === false && defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
            $json = json_encode($payload, $flags | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        return $json;
    }

    /**
     * 组装当前页面的结构化数据节点集合
     */
    private static function graph($page, $data)
    {
        $cfg = self::settings();
        $graph = array();
        $url = self::currentUrl();

        if ($page === 'index') {
            if (! $cfg['home']) {
                return array();
            }
            $kg = self::knowledge($cfg);
            if ($cfg['search']) {
                $kg['potentialAction'] = self::searchAction();
            }
            $graph[] = $kg;
            return $graph;
        }

        if ($page === 'list' && is_object($data)) {
            $breadcrumb = $cfg['breadcrumb'] ? self::breadcrumb($data->scode) : null;
            if ($breadcrumb) {
                $graph[] = $breadcrumb;
            }
            $kg = self::knowledge($cfg);
            if ($kg) {
                $graph[] = $kg;
            }
            $graph[] = self::listPage($cfg, $data, $url);
            return $graph;
        }

        if ($page === 'content' && is_object($data)) {
            $sort = self::model()->getSort($data->scode);
            if ($sort) {
                $breadcrumb = $cfg['breadcrumb'] ? self::breadcrumb($sort->scode, $data->title, $url) : null;
                if ($breadcrumb) {
                    $graph[] = $breadcrumb;
                }
                $kg = self::knowledge($cfg);
                if ($kg) {
                    $graph[] = $kg;
                }
            }
            $map = self::mapping($cfg, $sort ? $sort->mcode : '', $data->scode);
            $schemaType = $map[1] ?: 'Article';
            $graph[] = self::detailNode($cfg, $data, $sort, $schemaType, $url);
            $webPage = array(
                '@type' => 'WebPage',
                '@id' => $url,
                'url' => $url,
                'name' => $data->title,
                'inLanguage' => str_replace('_', '-', get_lg()),
                'mainEntity' => array('@id' => $url . '#' . $schemaType)
            );
            if ($cfg['breadcrumb']) {
                $webPage['breadcrumb'] = array('@id' => $url . '#breadcrumb');
            }
            $graph[] = $webPage;
            return $graph;
        }

        if ($page === 'about' && is_array($data) && isset($data['sort']) && isset($data['data'])) {
            $sort = $data['sort'];
            $row = $data['data'];
            $breadcrumb = $cfg['breadcrumb'] ? self::breadcrumb($sort->scode) : null;
            if ($breadcrumb) {
                $graph[] = $breadcrumb;
            }
            $kg = self::knowledge($cfg);
            if ($kg) {
                $graph[] = $kg;
            }
            $graph[] = self::pageNode($cfg, $row, $sort, $url);
            return $graph;
        }

        return $graph;
    }

    /**
     * 知识图谱节点（组织机构/个人）
     * 未选择数据类型时按组织机构输出，名称回退到站点名称，避免首页无任何结构化数据
     */
    private static function knowledge($cfg)
    {
        if ((int) $cfg['dtype'] === 2 && $cfg['person_name'] !== '') {
            return self::personNode($cfg);
        }
        return self::organizationNode($cfg);
    }

    private static function organizationNode($cfg)
    {
        $site = self::site();
        $company = self::company();
        $node = array('@type' => $cfg['org_type'] ?: 'Organization');
        $node['name'] = $cfg['org_name'] !== '' ? $cfg['org_name'] : self::siteTitle();
        if ($cfg['org_legal'] !== '') {
            $node['legalName'] = $cfg['org_legal'];
        }
        $node['url'] = $cfg['org_url'] !== '' ? $cfg['org_url'] : self::base() . '/';
        $node['@id'] = $node['url'];

        $logo = $cfg['logo'] !== '' ? $cfg['logo'] : ($site ? $site->logo : '');
        if ($logo !== '') {
            $node['logo'] = self::absolute($logo);
        }
        if ($cfg['socials']) {
            $node['sameAs'] = $cfg['socials'];
        }

        $phone = $cfg['contact_phone'] !== '' ? $cfg['contact_phone'] : ($company ? ($company->mobile ?: $company->phone) : '');
        if ($phone !== '' || $cfg['contact_url'] !== '' || $cfg['contact_type'] !== '') {
            $contactPoint = array('@type' => 'ContactPoint');
            if ($phone !== '') {
                $contactPoint['telephone'] = $phone;
            }
            if ($cfg['contact_url'] !== '') {
                $contactPoint['contactUrl'] = self::absolute($cfg['contact_url']);
            }
            if ($cfg['contact_type'] !== '') {
                $contactPoint['contactType'] = $cfg['contact_type'];
            }
            $contactPoint['url'] = $node['url'];
            $node['contactPoint'] = $contactPoint;
        }

        $address = self::postalAddress($cfg);
        if ($address) {
            $node['address'] = $address;
        }
        $geo = self::geo($cfg);
        if ($geo) {
            $node['geo'] = $geo;
        }
        if ($node['@type'] === 'LocalBusiness') {
            $hours = self::openingHours($cfg);
            if ($hours) {
                $node['openingHoursSpecification'] = $hours;
            }
            if ($cfg['price'] !== '') {
                $node['priceRange'] = $cfg['price'];
            }
        }
        return $node;
    }

    private static function personNode($cfg)
    {
        $node = array('@type' => 'Person');
        $node['name'] = $cfg['person_name'] !== '' ? $cfg['person_name'] : self::siteTitle();
        if ($cfg['person_job'] !== '') {
            $node['jobTitle'] = $cfg['person_job'];
        }
        if ($cfg['person_image'] !== '') {
            $node['image'] = self::absolute($cfg['person_image']);
        }
        $node['url'] = $cfg['contact_url'] !== '' ? self::absolute($cfg['contact_url']) : self::base() . '/';
        if ($cfg['socials']) {
            $node['sameAs'] = $cfg['socials'];
        }
        return $node;
    }

    /**
     * 列表页节点
     */
    private static function listPage($cfg, $sort, $url)
    {
        $map = self::mapping($cfg, $sort->mcode, $sort->scode);
        $listType = $map[0] ?: 'CollectionPage';
        $schemaType = $map[1] ?: 'Article';

        $name = $sort->title !== '' ? $sort->title : $sort->name;
        $node = array(
            '@type' => $listType === 'DetailedItemList' ? 'CollectionPage' : $listType,
            'name' => $name,
            'url' => $url,
            '@id' => $url
        );
        if ($sort->description !== '') {
            $node['description'] = $sort->description;
        }
        $items = self::listItems($cfg, $sort, $listType, $schemaType);
        if ($items) {
            $itemList = array(
                '@type' => 'ItemList',
                'name' => $name,
                'numberOfItems' => count($items),
                'itemListElement' => $items
            );
            if ($sort->description !== '') {
                $itemList['description'] = $sort->description;
            }
            $node['mainEntity'] = $itemList;
        }
        return $node;
    }

    /**
     * 列表页条目
     */
    private static function listItems($cfg, $sort, $listType, $schemaType)
    {
        $list = self::model()->getList($sort->scode, 20, 'a.date DESC,a.id DESC');
        if (! $list) {
            return array();
        }
        $items = array();
        $detail = $listType === 'DetailedItemList' || $listType === 'CollectionPage';
        foreach ($list as $index => $row) {
            $link = self::contentLink($row);
            $item = array(
                '@type' => 'ListItem',
                'position' => $index + 1
            );
            if ($detail) {
                $subItem = self::itemSubNode($cfg, $row, $schemaType, $link);
                $item['item'] = $subItem;
            } else {
                $item['name'] = $row->title;
                $item['item'] = $link;
            }
            $items[] = $item;
        }
        return $items;
    }

    private static function itemSubNode($cfg, $row, $schemaType, $link)
    {
        if ($schemaType === 'Product') {
            $subItem = array(
                '@type' => 'Product',
                'name' => $row->title,
                'url' => $link,
                'sku' => (string) $row->id
            );
            if ($cfg['brand'] !== '') {
                $subItem['brand'] = array('@type' => 'Brand','name' => $cfg['brand']);
            }
            if ($cfg['org_name'] !== '') {
                $subItem['manufacturer'] = array('@type' => 'Organization','name' => $cfg['org_name']);
            }
        } else {
            $subItem = array(
                '@type' => $schemaType,
                'name' => $row->title,
                'url' => $link
            );
        }
        if ($row->description !== '') {
            $subItem['description'] = $row->description;
        }
        $image = self::firstImage($row->ico, $row->pics, $cfg['image']);
        if ($image !== '') {
            $subItem['image'] = $image;
        }
        if ($row->date) {
            $subItem['datePublished'] = self::isoDate($row->date);
        }
        return $subItem;
    }

    /**
     * 内容详情节点
     */
    private static function detailNode($cfg, $row, $sort, $schemaType, $url)
    {
        $node = array(
            '@id' => $url . '#' . $schemaType,
            '@type' => $schemaType
        );

        if ($schemaType === 'Product') {
            $node['name'] = $row->title;
            $node['sku'] = (string) $row->id;
            if ($cfg['brand'] !== '') {
                $node['brand'] = array('@type' => 'Brand','name' => $cfg['brand']);
            }
            if ($cfg['org_name'] !== '') {
                $node['manufacturer'] = array('@type' => 'Organization','name' => $cfg['org_name']);
                $node['seller'] = array('@type' => 'Organization','name' => $cfg['org_name']);
            }
        } elseif (self::isArticle($schemaType)) {
            $node['headline'] = $row->title;
        } else {
            $node['name'] = $row->title;
        }

        $description = $row->description !== '' ? $row->description : llms_plain_text($row->content, 200);
        if ($description !== '') {
            $node['description'] = $description;
        }
        $image = self::firstImage($row->ico, $row->pics, $cfg['image']);
        if ($image !== '') {
            $node['image'] = $image;
        }
        if ($row->date) {
            $node['datePublished'] = self::isoDate($row->date);
        }
        if (! empty($row->update_time)) {
            $node['dateModified'] = self::isoDate($row->update_time);
        }

        if ($cfg['author']) {
            $authorName = $cfg['author_name'] !== '' ? $cfg['author_name'] : (string) $row->author;
            if ($authorName !== '') {
                $author = array('@type' => 'Person','name' => $authorName);
                if ($cfg['author_url'] !== '') {
                    $author['url'] = self::absolute($cfg['author_url']);
                }
                $node['author'] = $author;
            }
        }

        if ($sort) {
            if (self::isArticle($schemaType)) {
                $node['articleSection'] = $sort->name;
            } elseif ($schemaType !== 'Product') {
                $node['category'] = $sort->name;
            }
            if (! empty($sort->parentname)) {
                $node['genre'] = $sort->parentname;
            }
        }

        if ($cfg['comment']) {
            $reviews = self::reviews($row->id);
            if ($reviews) {
                $node['review'] = $reviews;
            }
        }
        return $node;
    }

    /**
     * 单页（关于/联系等）节点
     */
    private static function pageNode($cfg, $row, $sort, $url)
    {
        $map = self::mapping($cfg, $sort->mcode, $sort->scode);
        $schemaType = $map[1] ?: 'WebPage';

        $isAbout = $cfg['about'] !== '' && $cfg['about'] !== '0' && $cfg['about'] === $sort->scode;
        $isContact = $cfg['contact'] !== '' && $cfg['contact'] !== '0' && $cfg['contact'] === $sort->scode;
        if ($isAbout) {
            $schemaType = 'AboutPage';
        } elseif ($isContact) {
            $schemaType = 'ContactPage';
        }

        $node = array(
            '@type' => $schemaType,
            'name' => $row->title,
            'url' => $url
        );
        $description = $row->description !== '' ? $row->description : llms_plain_text($row->content, 200);
        if ($description !== '') {
            $node['description'] = $description;
        }
        $image = self::firstImage($row->ico, $row->pics, $cfg['image']);
        if ($image !== '') {
            $node['image'] = $image;
        }
        if (! empty($row->date)) {
            $node['datePublished'] = self::isoDate($row->date);
        }
        if (! empty($row->update_time)) {
            $node['dateModified'] = self::isoDate($row->update_time);
        }

        if ($isAbout || $isContact) {
            $kg = self::knowledge($cfg);
            if ($kg) {
                unset($kg['sameAs']);
                $node['mainEntity'] = $kg;
            }
            $geo = self::geo($cfg);
            if ($geo) {
                $node['geo'] = $geo;
            }
            $address = self::postalAddress($cfg);
            if ($address) {
                $node['address'] = $address;
            }
        }

        if ($isContact) {
            $company = self::company();
            $phone = $cfg['contact_phone'] !== '' ? $cfg['contact_phone'] : ($company ? ($company->mobile ?: $company->phone) : '');
            if ($phone !== '') {
                $node['telephone'] = $phone;
            }
            if ($cfg['contact_url'] !== '') {
                $node['url'] = self::absolute($cfg['contact_url']);
            }
            if ($cfg['contact_type'] !== '') {
                $node['contactType'] = $cfg['contact_type'];
            }
            $contactPoint = array('@type' => 'ContactPoint');
            if ($phone !== '') {
                $contactPoint['telephone'] = $phone;
            }
            if ($cfg['contact_type'] !== '') {
                $contactPoint['contactType'] = $cfg['contact_type'];
            }
            if (count($contactPoint) > 1) {
                $node['contactPoint'] = $contactPoint;
            }
        }

        if ($cfg['author']) {
            $authorName = $cfg['author_name'] !== '' ? $cfg['author_name'] : (string) $row->author;
            if ($authorName !== '') {
                $author = array('@type' => 'Person','name' => $authorName);
                if ($cfg['author_url'] !== '') {
                    $author['url'] = self::absolute($cfg['author_url']);
                }
                $node['author'] = $author;
            }
        }

        if (! empty($sort->parentname)) {
            $node['genre'] = $sort->parentname;
        }

        $node['mainEntityOfPage'] = array('@type' => 'WebPage','@id' => $url);
        return $node;
    }

    /**
     * 面包屑节点
     */
    private static function breadcrumb($scode, $lastName = '', $lastUrl = '')
    {
        $sorts = self::model()->getPosition($scode);
        if (! $sorts) {
            return null;
        }
        $items = array(
            array('@type' => 'ListItem','position' => 1,'name' => '首页','item' => self::base() . '/')
        );
        foreach ($sorts as $sort) {
            $link = self::sortLink($sort);
            if ($link === '') {
                continue;
            }
            $items[] = array(
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $sort['name'],
                'item' => $link
            );
        }
        if ($lastName !== '' && $lastUrl !== '') {
            $items[] = array(
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $lastName,
                'item' => $lastUrl
            );
        }
        return array(
            '@id' => self::currentUrl() . '#breadcrumb',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        );
    }

    /**
     * 评论转 Review 节点
     */
    private static function reviews($contentid)
    {
        $comments = self::model()->getComment($contentid, 0, 5, 'id DESC');
        if (! $comments) {
            return array();
        }
        $reviews = array();
        foreach ($comments as $comment) {
            $body = trim(strip_tags((string) $comment->comment));
            if ($body === '') {
                continue;
            }
            $name = trim((string) ($comment->nickname !== '' ? $comment->nickname : $comment->username));
            $review = array(
                '@type' => 'Review',
                'reviewBody' => $body,
                'datePublished' => self::isoDate($comment->create_time),
                'author' => array('@type' => 'Person','name' => $name !== '' ? $name : '匿名用户')
            );
            $reviews[] = $review;
            if (count($reviews) >= 5) {
                break;
            }
        }
        return $reviews;
    }

    private static function searchAction()
    {
        $search = self::absolute(\core\basic\Url::home('search', false));
        $search .= strpos($search, '?') === false ? '?keyword=' : '&keyword=';
        return array(
            '@type' => 'SearchAction',
            'target' => $search . '{search_term_string}',
            'query-input' => 'required name=search_term_string'
        );
    }

    /**
     * 读取并整理全部配置
     */
    private static function settings()
    {
        static $cfg = null;
        if ($cfg !== null) {
            return $cfg;
        }
        $all = Config::get();
        if (! is_array($all)) {
            $all = array();
        }
        $get = function ($key, $default = '') use ($all) {
            return isset($all[$key]) && is_scalar($all[$key]) ? (string) $all[$key] : $default;
        };

        $cfg = array(
            'home' => $get('jld_home') === '1',
            'search' => $get('jld_search') === '1',
            'author' => $get('jld_author') === '1',
            'breadcrumb' => $get('jld_breadcrumb') === '1',
            'comment' => $get('jld_comment') === '1',
            'about' => $get('jld_about', '0'),
            'contact' => $get('jld_contact', '0'),
            'author_name' => $get('jld_author_name'),
            'author_url' => $get('jld_author_url'),
            'dtype' => $get('jld_dtype', '0'),
            'org_type' => $get('jld_org_type'),
            'org_name' => $get('jld_org_name'),
            'org_legal' => $get('jld_org_legal'),
            'org_url' => $get('jld_org_url'),
            'person_name' => $get('jld_person_name'),
            'person_job' => $get('jld_person_job'),
            'person_image' => $get('jld_person_image'),
            'contact_type' => $get('jld_contact_type'),
            'contact_phone' => $get('jld_contact_phone'),
            'contact_url' => $get('jld_contact_url'),
            'logo' => $get('jld_logo'),
            'open_days' => $get('jld_open_days'),
            'open_start' => $get('jld_open_start'),
            'open_end' => $get('jld_open_end'),
            'price' => $get('jld_price'),
            'lat' => $get('jld_geo_lat'),
            'lng' => $get('jld_geo_lng'),
            'street' => $get('jld_street'),
            'city' => $get('jld_city'),
            'region' => $get('jld_region'),
            'zip' => $get('jld_zip'),
            'country' => $get('jld_country'),
            'brand' => $get('jld_brand'),
            'image' => $get('jld_image'),
            'mods' => array(),
            'cats' => array(),
            'socials' => array()
        );

        $socials = array();
        foreach ($all as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }
            if (preg_match('/^jld_mod_([\w\-]+)$/', $key, $match)) {
                $cfg['mods'][$match[1]] = trim((string) $value);
            } elseif (preg_match('/^jld_cat_([\w\-]+)$/', $key, $match)) {
                $cfg['cats'][$match[1]] = trim((string) $value);
            } elseif (preg_match('/^jld_soc_(\d+)$/', $key, $match)) {
                $socials[(int) $match[1]] = trim((string) $value);
            }
        }
        ksort($socials);
        $cfg['socials'] = array_values(array_filter($socials, 'strlen'));
        return $cfg;
    }

    /**
     * 读取模型/分类的结构化映射，分类优先于模型
     *
     * @return array 0=>列表类型 1=>结构化类型
     */
    private static function mapping($cfg, $mcode, $scode)
    {
        $map = array('', '');
        if ($mcode !== '' && isset($cfg['mods'][$mcode])) {
            $map = self::parseMapping($cfg['mods'][$mcode]);
        }
        if ($scode !== '' && isset($cfg['cats'][$scode])) {
            $cat = self::parseMapping($cfg['cats'][$scode]);
            if ($cat[0] !== '') {
                $map[0] = $cat[0];
            }
            if ($cat[1] !== '') {
                $map[1] = $cat[1];
            }
        }
        return $map;
    }

    private static function parseMapping($value)
    {
        $parts = explode('|', $value);
        $list = isset($parts[0]) ? trim($parts[0]) : '';
        $schema = isset($parts[1]) ? trim($parts[1]) : '';
        $listTypes = array_keys(jsonld_list_types());
        $schemaTypes = array_keys(jsonld_schema_types());
        return array(
            in_array($list, $listTypes, true) ? $list : '',
            in_array($schema, $schemaTypes, true) ? $schema : ''
        );
    }

    private static function isArticle($schemaType)
    {
        return $schemaType === 'Article' || $schemaType === 'BlogPosting' || $schemaType === 'NewsArticle' || substr($schemaType, - 7) === 'Article';
    }

    private static function postalAddress($cfg)
    {
        if ($cfg['street'] === '' && $cfg['city'] === '' && $cfg['region'] === '' && $cfg['zip'] === '' && $cfg['country'] === '') {
            return null;
        }
        $address = array('@type' => 'PostalAddress');
        $map = array(
            'streetAddress' => 'street',
            'addressLocality' => 'city',
            'addressRegion' => 'region',
            'postalCode' => 'zip',
            'addressCountry' => 'country'
        );
        foreach ($map as $field => $key) {
            if ($cfg[$key] !== '') {
                $address[$field] = $cfg[$key];
            }
        }
        return $address;
    }

    private static function geo($cfg)
    {
        if ($cfg['lat'] === '' || $cfg['lng'] === '') {
            return null;
        }
        return array(
            '@type' => 'GeoCoordinates',
            'latitude' => $cfg['lat'],
            'longitude' => $cfg['lng']
        );
    }

    private static function openingHours($cfg)
    {
        $days = array_filter(array_map('trim', explode(',', $cfg['open_days'])));
        if (! $days) {
            return null;
        }
        $spec = array('@type' => 'OpeningHoursSpecification');
        $spec['dayOfWeek'] = array();
        foreach ($days as $day) {
            if (array_key_exists($day, jsonld_week_days())) {
                $spec['dayOfWeek'][] = 'https://schema.org/' . $day;
            }
        }
        if (! $spec['dayOfWeek']) {
            return null;
        }
        if ($cfg['open_start'] !== '') {
            $spec['opens'] = $cfg['open_start'];
        }
        if ($cfg['open_end'] !== '') {
            $spec['closes'] = $cfg['open_end'];
        }
        return $spec;
    }

    private static function firstImage($ico, $pics, $default)
    {
        if ($ico !== '') {
            return self::absolute($ico);
        }
        if ($pics !== '') {
            $list = explode(',', $pics);
            $first = trim($list[0]);
            if ($first !== '') {
                return self::absolute($first);
            }
        }
        if ($default !== '') {
            return self::absolute($default);
        }
        return '';
    }

    private static function sortLink($sort)
    {
        $pagetype = isset($sort['type']) && (string) $sort['type'] === '1' ? 'about' : 'list';
        $link = self::parser()->parserLink($sort['type'], $sort['urlname'], $pagetype, $sort['scode'], $sort['filename']);
        if (! $link || strpos($link, 'javascript') !== false) {
            return '';
        }
        return self::absolute($link);
    }

    private static function contentLink($row)
    {
        $link = self::parser()->parserLink($row->type, $row->urlname, 'content', $row->scode, $row->sortfilename, $row->id, $row->filename);
        return self::absolute($link);
    }

    private static function absolute($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if (strpos($path, '//') === 0) {
            return (is_https() ? 'https:' : 'http:') . $path;
        }
        return get_http_url() . '/' . ltrim($path, '/');
    }

    private static function base()
    {
        return rtrim(get_http_url() . SITE_INDEX_DIR, '/');
    }

    private static function currentUrl()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        return get_http_url() . $uri;
    }

    private static function siteTitle()
    {
        $site = self::site();
        if ($site && $site->title !== '') {
            return $site->title;
        }
        return get_http_host();
    }

    private static function isoDate($date)
    {
        $time = strtotime($date);
        return $time ? date('c', $time) : (string) $date;
    }

    private static function site()
    {
        if (self::$site === null) {
            self::$site = self::model()->getSite();
        }
        return self::$site;
    }

    private static function company()
    {
        if (self::$company === null) {
            self::$company = self::model()->getCompany();
        }
        return self::$company;
    }

    private static function model()
    {
        return new ParserModel();
    }

    private static function parser()
    {
        if (self::$parser === null) {
            self::$parser = new ParserController();
        }
        return self::$parser;
    }
}
