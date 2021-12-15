<?php
header('Access-Control-Allow-Origin: *');
ini_set("display_errors", 0);
error_reporting(E_ALL ^ E_NOTICE);
error_reporting(E_ALL ^ E_WARNING);
require_once 'Utils.php';

require_once 'widget/Widget_Contents_Modify.php';

define('UPLOAD_DIR', '/usr/uploads');

class TypechoMobile_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;
    private $res;
    const LACK_PARAMETER = 'Not found';
    //分页 每页数量
    const POSTS_PER_PAGE = 10;
    /**
     * @var string
     */
    private $plugin_dir;

    /**
     * @var mixed|null
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->db = Typecho_Db::get();
        $this->res = new Typecho_Response();
        $this->plugin_dir = Helper::options()->pluginDir('TypechoMobile') . '/TypechoMobile';

        if (method_exists($this, $this->request->type)) {
            call_user_func(array(
                $this,
                $this->request->type
            ));
        } else {
            $this->defaults();
        }
    }

    /**
     * 获取配置
     * @param $key
     * @return false|mixed|null
     * @throws Typecho_Plugin_Exception
     */
    public static function option_value($key)
    {
        $options = Helper::options()->plugin('TypechoMobile');
        if (isset($options->$key) && !empty($options->$key)) {
            return trim($options->$key);
        }

        return false;
    }

    //组合返回值
    public function make_response($code, $msg, $data = null)
    {
        $response = [
            'code' => $code,
            'msg' => $msg,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }
        $this->response->throwJson($response);
        return $response;
    }

    //组合返回值 成功
    public function make_success($data = null)
    {
        return $this->make_response(0, '操作成功！', $data);
    }

    //组合返回值 失败
    public function make_error($msg ='', $code = 1)
    {
        return $this->make_response($code, $msg);
    }

    public function make_error_notlogin($msg = '', $code = -1)
    {
        return $this->make_response($code, $msg);
    }

    /**
     * 解析分类描述中的图片，格式<imgurl>
     * @param $defaultSlugUrl
     * @param $desc
     * @return mixed|string
     */
    function parseDesc2img($desc, $defaultSlugUrl = null)
    {
        $preg = '/^<(.*)>([\s\S]*)/';
        preg_match($preg, $desc, $res);
        if (isset($res[1])) {
            return $res[1];
        }
        if (!$defaultSlugUrl) {
            $defaultSlugUrl = "https://img.icons8.com/dusk/2x/categorize.png";
        }
        return $defaultSlugUrl;
    }

    function parseDesc2text($desc)
    {
        $preg = '/^<(.*)>([\s\S]*)/';
        preg_match_all($preg, $desc, $res);
        if (isset($res[2][0])) {
            return $res[2][0];
        }
        return $desc;
    }

    /**
     * @param $content
     * @param int $length
     * @param string $trim
     * @return string
     */
    function tp_trim_words($content, $length = 100, $trim = '...')
    {
        return Typecho_Common::subStr(strip_tags($content), 0, $length, $trim);
    }

    /**
     * 获取目录
     * @param $args
     * @return array
     */
    public function get_categories($args)
    {
        $cat_ids = !empty($args['include']) ? $args['include'] : null;
        $exclude = !empty($args['exclude']) ? $args['exclude'] : null;
        if (isset($cat_ids) && !empty($cat_ids[0])) {
            $select = $this->db->select()->from('table.metas')->where('type = ?', 'category')->where('mid IN ?', $cat_ids);
        } elseif (isset($exclude) and !empty($exclude[0])) {
            $select = $this->db->select()->from('table.metas')->where('type = ?', 'category');

            foreach ($exclude as $ext) {
                $select = $select->where('mid <> ?', $ext);
            }
        } else {
            $select = $this->db->select()->from('table.metas')->where('type = ?', 'category');
        }
        return $this->db->fetchAll($select);
    }


    /**
     * 获取目录
     * @param $args
     * @return array
     */
    public function get_all_tags()
    {
        return $this->db->fetchAll($this->db
        ->select()->from('table.metas')
        ->where('type = ?', 'tag'));
    }


    public function get_the_category($id)
    {
        return $this->db->fetchAll($this->db
            ->select()->from('table.metas')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $id)
            ->where('table.metas.type = ?', 'category'), array($this->widget('Widget_Abstract_Metas'), 'filter'));
    }

    public function get_the_tags($id, $limit = 2)
    {
        return $this->db->fetchAll($this->db
            ->select()->from('table.metas')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $id)
            ->where('table.metas.type = ?', 'tag')->limit($limit), array($this->widget('Widget_Abstract_Metas'), 'filter'));
    }

    public function get_post_type($post_id)
    {
        return $this->db->fetchRow($this->db->select('type')->from('table.contents')->where('cid = ?', $post_id))['type'];
    }

    /**
     * $post_id文章的ID（如果在循环中，你可以用 get_the_ID()来设置）,
     * $key自定义字段的名称（键值）,
     * $single是否以字符串形式返回，false会返回数组形式。
     * @param $post_id
     * @param $key
     * @param $single
     */
    public function get_post_meta($post_id, $key, $single)
    {
        if ($key == 'views') {
            return $this->db->fetchRow($this->db->select('views')->from('table.contents')->where('cid = ?', $post_id))['views'];
        } elseif ($key == 'favorites') {
            return $this->db->fetchRow($this->db->select('favorites')->from('table.contents')->where('cid = ?', $post_id))['favorites'];
        } elseif ($key == 'likes') {
            return $this->db->fetchRow($this->db->select('likes')->from('table.contents')->where('cid = ?', $post_id))['likes'];

        }
    }

    public function update_post_meta($post_id, $key, $value)
    {
        if ($key == 'views') {
            return $this->db->query($this->db->update('table.contents')->where('cid = ?', $post_id)->rows([
                'views' => $value
            ]));
        } elseif ($key == 'likes') {
            return $this->db->query($this->db->update('table.contents')->where('cid = ?', $post_id)->rows([
                'likes' => $value
            ]));
        }
    }

    private function username_exists($open_id)
    {
        $user = $this->db->fetchRow($this->db->select('uid')->from('table.users')->where('name = ?', $open_id));
        return $user['uid'] ? true : false;
    }

    private function get_user_by($type, $open_id)
    {
        if ($type == 'login') {
            return $this->db->fetchObject($this->db->select()->from('table.users')->where('name = ?', $open_id));
        }
    }

    private function get_user_meta($user_id, $key)
    {
        return $this->db->fetchRow($this->db->select($key)->from('table.users')->where('uid = ?', $user_id))[$key];
    }

    private function tp_update_user($uid, $arr)
    {
        $this->db->query($this->db->update('table.users')->where('table.users.uid = ?', $uid)->rows($arr));
        return $uid;
    }

    public function get_post_excerpt($content, $length = 50, $trim = '...')
    {
        Typecho_Common::subStr(strip_tags($content), 0, $length, $trim);
    }

    /**
     * 传入的是 post class 而不是 数据库原始数据
     * @param $posts
     * @return array
     * @throws Typecho_Plugin_Exception
     */
    public function filter_post_for_list($posts)
    {
        $data = [];
        foreach ($posts as $post) {
            $item = [
                'id' => $post['cid'],
                'time' => $post['created'],
                'title' => $post['title'],
                'comment_count' => $post['commentsNum'],
                'views' => $post['views'],
                'uid' => $post['authorId'],
            ];

            if (self::option_value('JSwitch_excerpt') == '1') {
                if (is_object($post) and $post->excerpt) {
                    $item["excerpt"] = html_entity_decode(self::tp_trim_words($post['text'], 50, '...'));
                } else {
                    $content = $post['text'];
                    $item["excerpt"] = html_entity_decode(self::tp_trim_words($content, 50, '...'));
                }
            }
            $item['thumbnail'] = self::GetRandomThumbnail($post['text']);
            $data[] = $item;
        }
        return $data;
    }

    public function filter_tag_for_list($tags)
    {
        $data = [];
        foreach ($tags as $tag) {
            //计算字符串长度 兼容中英文

           $data[] = [
                'id' => $tag['mid'],
                'name' => $tag['name'],
                'permalink' => $tag['permalink']
            ];

            //列表中最多放2个标签 或 标签长度和大于6
            //if (sizeof($data) > 1 || $all_tag_len == 6) {
            //    break;
            //}
        }
        return $data;
    }

    /**
     * args 传入之前必须处理成 array
     * @param $select
     * @param $args
     */
    public function queryPost($select, $args)
    {
        $select = $select->from('table.contents')->where('type = ? and status = ?', 'post', 'publish');
        if (isset($args['category__not_in']) and !empty($args['category__not_in'][0])) {
            $select = $select->join('table.relationships', 'table.contents.cid = table.relationships.cid', Typecho_Db::LEFT_JOIN);
            foreach ($args['category__not_in'] as $ext) {
                $select = $select->where('mid <> ?', $ext);
            }
        }
        if (isset($args['cat']) and !empty($args['cat'][0])) { //category
            $select = $select->join('table.relationships', 'table.contents.cid = table.relationships.cid', Typecho_Db::LEFT_JOIN)->where('mid = ?', $args['cat']);
        }
        if (isset($args['tag_id']) and !empty($args['tag_id'][0])) { //tag
            $select = $select->join('table.relationships', 'table.contents.cid = table.relationships.cid', Typecho_Db::LEFT_JOIN)->where('mid = ?', $args['tag_id']);
        }
        if (isset($args['s']) and !empty($args['s'][0])) { //search
            $select = $select->where('table.contents.title LIKE ? OR table.contents.text LIKE ?', '%' . $args['s'] . '%', '%' . $args['s'] . '%');
        }
        if (isset($args['post__not_in']) and !empty($args['post__not_in'][0])) {
            foreach ($args['post__not_in'] as $ext) {
                $select = $select->where('cid <> ?', $ext);
            }
//            $select = $select->where('cid NOT IN ?',$args['post__not_in']);
        }
        if (isset($args['post__in']) and !empty($args['post__in'][0])) {
            $select = $select->where('cid IN ?', $args['post__in']);
        }

        if (isset($args['offset'])) {
            $select = $select->offset($args['offset']);
        }
        if (isset($args['posts_per_page'])) {
            $select = $select->limit($args['posts_per_page']);
        }
        if (isset($args['orderby']) and !empty($args['orderby'][0])) {
            if (array_key_exists('order', $args) && $args['order'] == 'DESC') {
                $select = $select->order($args['orderby'], Typecho_Db::SORT_DESC);
            } else {
                $select = $select->order($args['orderby']);
            }
        }


        return $select;
    }

    /**
     * 获取置顶的文章
     */
    private function _getStickPosts($args)
    {
        $sticky_posts = self::option_value('JSticky_posts');
        if (!$sticky_posts) {
            return [];
        }
        $sticky_posts = explode(',', $sticky_posts);
//        $posts = [];
//        foreach ($sticky_posts as $sticky_post) {
//            $this->widget('Widget_Archive@'.$sticky_post, 'pageSize=1&type=post', 'cid='.$sticky_post)->to($post);
//            $posts[] = $post;
//        }
//        return $posts;
        return $this->db->fetchAll($this->db->select()->from('table.contents')->where('cid IN ?', $sticky_posts));
    }

    /**
     * 处理文章
     * @param $args
     * @param bool $qstick
     * @return array
     * @throws Typecho_Plugin_Exception
     */
    public function get_posts($args, $qstick = false)
    {
        if ($qstick && self::option_value('JSwitch_stick') == '1') { // 置顶
            //第一页获取置顶帖子
            if ($args['offset'] == 0) {
                $posts_stick = $this->_getStickPosts($args);
            } else {
                $posts_stick = [];
            }
            $posts_stick = $this->filter_post_for_list($posts_stick);
            foreach ($posts_stick as &$post) {
                $post['stick'] = 1;
            }
            $args['post__not_in'] = self::option_value('JSticky_posts');
            $args['post__not_in'] = explode(',', $args['post__not_in']);
            $select = $this->db->select();
            $select = $this->queryPost($select, $args);

            $common_posts = $this->db->fetchAll($select);

//            $common_posts = [];
//            foreach ($common_posts_ids as $comon_posts_id){
//                $this->widget('Widget_Archive@'.$comon_posts_id['cid'], 'pageSize=1&type=post', 'cid='.$comon_posts_id['cid'])->to($post);
//                $common_posts[]  = $post;
//            }
            $common_posts = $this->filter_post_for_list($common_posts);
            $posts = array_merge($posts_stick, $common_posts);
        } else {
            $select = $this->db->select();
            $select = $this->queryPost($select, $args);
            $common_posts = $this->db->fetchAll($select);

//            $common_posts = [];
//            foreach ($common_posts_ids as $comon_posts_id){
//                $this->widget('Widget_Archive@'.$comon_posts_id['cid'], 'pageSize=1&type=post', 'cid='.$comon_posts_id['cid'])->to($post);
//                $common_posts[]  = $post;
//            }
            $posts = $this->filter_post_for_list($common_posts);
        }

        foreach ($posts as &$post) {
            //查询tag
            $tags = $this->get_the_tags($post['id']);
            if (!$tags) {
                $post['tags'] = [];
            } else {
                $post['tags'] = $this->filter_tag_for_list($tags);
            }

            //美化时间
            $post['time'] = Utils::time_beautify($post['time']);

            $post['views'] = self::get_post_meta($post['id'], 'views', true);
        }

        return $posts;
    }

    /* 随机图片 */
    public static function GetRandomThumbnail($content, $thumb = "")
    {
        if ($thumb) {
            return $thumb;
        }
        $options = Typecho_Widget::widget('Widget_Options')->plugin('TypechoMobile');
        $default_thumb = $options->JDefault_thumbnail;
        $random = 'https://cdn.jsdelivr.net/npm/typecho_joe_theme@4.3.5/assets/img/random/' . rand(1, 25) . '.webp';
        if ($options->defaultBackImg) {
            $moszu = explode("\r\n", $options->defaultBackImg);
            $random = $moszu[array_rand($moszu, 1)] . "?jrandom=" . mt_rand(0, 1000000);
        }
//        $pattern = '/\<img.*?src\=\"(.*?)\"[^>]*>/i';
        // 采用更严格的图片匹配模式
        $pattern = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpeg|\.png|\.jpg]))[\'|\"].*?[\/]?>/";
        $patternMD = '/\!\[.*?\]\((http(s)?:\/\/.*?(jpg|jpeg|gif|png|webp))/i';
        $patternMDfoot = '/\[.*?\]:\s*(http(s)?:\/\/.*?(jpg|jpeg|gif|png|webp))/i';
        $t = preg_match_all($pattern, $content, $thumbUrl);
        $img = $random;
        if ($t) {
            $img = $thumbUrl[1][0];
        } elseif (preg_match_all($patternMD, $content, $thumbUrl)) {
            $img = $thumbUrl[1][0];
        } elseif (preg_match_all($patternMDfoot, $content, $thumbUrl)) {
            $img = $thumbUrl[1][0];
        } elseif (!empty($default_thumb)) {
            $img = $default_thumb;
        }
        return $img;
    }

    /**
     * @param $opt
     * @return array
     */
    public static function getMutilineOptions($opt)
    {
        $opt_list = explode("\r\n", $opt);
        $result = [];
        foreach ($opt_list as $key => $val) {
            $val_list = explode('||', trim($val));
            $result[] = $val_list;
        }
        return $result;
    }

    // 这里开始功能函数

    /**
     * 获取配置 首页
     */
    public function get_home()
    {
        //LOGO
        $data['logo'] = self::option_value('JLogo');

        //小程序名称
        $data['title'] = self::option_value('JTitle');

        //顶部分类
        $cat_ids = trim(self::option_value('JHome_top_nav'));
        $cat_ids = explode(',', $cat_ids);
        $args = ['hide_empty' => 0];
        if (!empty($cat_ids)) {
            $args['include'] = $cat_ids;
        }

        $result = self::get_categories($args);

        $categories = [];
        foreach ($result as $item) {
            $categories[] = [
                'id' => $item['mid'],
                'name' => $item['name'],
            ];
        }

        $data['top_nav'] = $categories;

        //幻灯片
        $slide_ids = self::option_value('JTop_slide');
        $slides = [];
        if (!empty($slide_ids)) {
            $slide_ids = explode(',', $slide_ids);

            $result = $this->db->fetchAll($this->db->select('cid', 'text')->from('table.contents')->where('cid IN ?', $slide_ids));
            foreach ($result as $item) {
                $slides[] = [
                    'id' => $item['cid'],
                    'thumbnail' => self::GetRandomThumbnail($item['text'])
                ];
            }
        }
        $data['slide'] = $slides;

        //图标导航
        $icon_nav_org = self::option_value('JHome_icon_nav');
        $nav_list = explode("\r\n", trim($icon_nav_org));

        $icon_nav = [];
        if (is_array($nav_list) and !empty($nav_list)) {
            foreach ($nav_list as $item_list) {
                $items = explode('||', trim($item_list));
                if (isset($items) and !empty($items[0])) {
                    $item = [];
                    $item['icon'] = $items[0];
                    $item['title'] = $items[1];
                    $item['link'] = $items[2];
                    $icon_nav[] = $item;
                }
            }
        }
        $data['icon_nav'] = $icon_nav;

        //活动区域
        $JHome_active_left = self::option_value('JHome_active_left');
        $active_left_arr = explode('||', $JHome_active_left);
        $JHome_active_right_top = self::option_value('JHome_active_right_top');
        $active_right_top_arr = explode('||', $JHome_active_right_top);
        $JHome_active_right_down = self::option_value('JHome_active_right_down');
        $active_right_down_arr = explode('||', $JHome_active_right_down);

        if (!empty($JHome_active_left) && !empty($active_left_arr[0]) && !empty($active_right_top_arr[0]) && !empty($active_right_down_arr[0])) {
            $data['actives'] = [
                'left' => [
                    'image' => $active_left_arr[0],
                    'title' => $active_left_arr[1],
                    'link' => $active_left_arr[2]
                ],
                'right_top' => [
                    'image' => $active_right_top_arr[0],
                    'title' => $active_right_top_arr[1],
                    'link' => $active_right_top_arr[2]
                ],
                'right_down' => [
                    'image' => $active_right_down_arr[0],
                    'title' => $active_right_down_arr[1],
                    'link' => $active_right_down_arr[2]
                ],
            ];
        } else {
            $data['actives'] = false;
        }

        //热门文章
        $hot_ids = self::option_value('JHome_hot');
        $hots = [];
        $hot_ids = explode(',', $hot_ids);

        if (!empty($hot_ids)) {
            $result = $this->db->fetchAll($this->db->select('cid', 'title', 'text')->from('table.contents')->where('cid IN ?', $hot_ids));
            foreach ($result as $item) {
                $hots[] = [
                    'id' => $item['cid'],
                    'title' => $item['title'],
                    'thumbnail' => self::GetRandomThumbnail($item['text'])
                ];
            }
        }
        $data['hot'] = $hots;

        //列表模式
        $data['list_mode'] = self::option_value('JHome_list_mode');
        if (!$data['list_mode']) {
            $data['list_mode'] = 3;
        }

        return $this->make_success($data);
    }

    /**
     * 获取配置 热门
     */
    public function get_hot()
    {
        $data = [
            'background' => self::option_value('JHot_background'),
            'title' => self::option_value('JHot_title'),
            'description' => self::option_value('JHot_description'),
        ];

        return $this->make_success($data);
    }

    /**
     * 获取配置 分类
     * @throws Typecho_Plugin_Exception
     */
    public function get_category()
    {
        $data = [
            'background' => self::option_value('JCategory_background'),
            'title' => self::option_value('JCategory_title'),
            'description' => self::option_value('JCategory_description'),
        ];

        return $this->make_success($data);
    }

    /**
     * 获取配置 用户中心
     * @throws Typecho_Plugin_Exception
     */
    public function get_profile()
    {
        $user_id = $this->check_login();
        if (!$user_id) {
            return $this->make_error('还没有登陆', -1);
        }
        $table_comments = $this->db->getPrefix() . 'comments';
        $table_contents = $this->db->getPrefix() . 'contents';
        $comments = $this->db->fetchAll($this->db->query("SELECT coid,`status` FROM `$table_comments`"));
        $contents = $this->db->fetchAll($this->db->query("SELECT cid,`status`,type,password FROM `$table_contents`"));
        $comments_approved_count = 0;
        $comments_count_waiting = 0;
        $comments_count_spam = 0;


        // -- 
        $contents_post_publish = 0;
        $contents_post_password = 0;
        $contents_post_post_draft = 0;
        $contents_post_hidden = 0;
        $contents_post_private = 0;
        // ---- 
        $contents_page_count = 0;
        
        foreach ($comments as $comment) {
            // 通过
            if ($comment['status'] == 'approved') {
                $comments_approved_count++;
            }else if ($comment['status'] == 'waiting') {
                $comments_count_waiting++;
            }else if ($comment['status'] == 'spam') {
                $comments_count_spam++;
            }
        }
        
        foreach ($contents as $content) {
            // post 
            if($content['type'] == 'post') {
                if(!empty($content['password'])) {
                    $contents_post_password++;
                }else if ($content['status'] == 'publish') {
                    // 发布
                    $contents_post_publish++;
                }else if ($content['status'] == 'hidden') {
                    // 隐藏
                    $contents_post_hidden++;
                }else if ($content['status'] == 'private') {
                    // 私有
                    $contents_post_private++;
                }
            }else if ($content['type'] == 'page') {
                // page
                $contents_page_count++;
            }else if ($content['type'] == 'post_draft') {
                // 草稿
                $contents_post_post_draft++;
            }
        }
        $comment_count = [
            'comments_approved_count' => $comments_approved_count,
            'comments_count_waiting' => $comments_count_waiting,
            'comments_count_spam' => $comments_count_spam
        ];
        $contents_post = [
            'contents_post_publish' => $contents_post_publish,
            'contents_post_password' => $contents_post_password,
            'contents_post_post_draft' => $contents_post_post_draft,
            'contents_post_hidden' => $contents_post_hidden,
            'contents_post_private' => $contents_post_private,
            'contents_page_count' => $contents_post_private,
        ];
        return $this->make_success([
            'comments' => $comment_count,
            'contents' => $contents_post,
        ]);
    }

    /**
     * 获取所有分类
     * @throws Typecho_Plugin_Exception
     */
    public function get_category_index()
    {
        $hide_cat = self::option_value('JHide_cat');
        $hide_cat = explode(',', $hide_cat);
        $args = [];
        if (!empty($hide_cat)) {
            $args['exclude'] = $hide_cat;
        }
        $result = $this->get_categories($args);

        $categories = [];
        foreach ($result as $cat) {
            $categories[] = [
                'id' => $cat['mid'],
                'name' => $cat['name'],
                'description' => $this->parseDesc2text($cat['description']),
                'cover' => $this->parseDesc2img($cat['description'], null)
            ];
        }

        return $this->make_success($categories);
    }

/**
     * 获取所有分类
     * @throws Typecho_Plugin_Exception
     */
    public function get_tag_index()
    {

        $result = $this->get_all_tags();

        $tags = [];
        foreach ($result as $cat) {
            $tags[] = [
                'id' => $cat['mid'],
                'name' => $cat['name'],
                'description' => $cat['description'],
                'parent' => $cat['parent'],
                'count' => $cat['count'],
            ];
        }

        return $this->make_success($tags);
    }

    /**
     * 按【时间倒序】获取文章列表
     * @throws Typecho_Plugin_Exception
     */
    public function get_last_posts()
    {
        $offset = $this->request->get('offset', 0);

        $args = [
            'posts_per_page' => self::POSTS_PER_PAGE,
            'offset' => $offset,
            'orderby' => 'created',
            'order' => 'DESC'
        ];

        $hide_cat = self::option_value('JHide_cat');
        if (!empty($hide_cat)) {
            $args['category__not_in'] = explode(',', $hide_cat);
        }

        $posts = $this->get_posts($args, true);
        return $this->make_success($posts);
    }

    /**
     * 获取某一分类下的文章
     */
    public function get_category_posts()
    {
        $offset = $this->request->get('offset', 0);
        $cat_id = $this->request->get('cat_id', 0);

        $args = [
            'posts_per_page' => self::POSTS_PER_PAGE,
            'offset' => $offset,
            'orderby' => 'created',
            'cat' => $cat_id
        ];

        $posts = $this->get_posts($args);
        return $this->make_success($posts);
    }

    /**
     * 获取某一TAG下的文章
     * @throws Typecho_Plugin_Exception
     */
    public function get_tag_posts()
    {
        $offset = $this->request->get('offset', 0);
        $tag_id = $this->request->get('tag_id', 0);

        $args = [
            'posts_per_page' => self::POSTS_PER_PAGE,
            'offset' => $offset,
            'orderby' => 'created',
            'tag_id' => $tag_id
        ];

        $posts = $this->get_posts($args);
        return $this->make_success($posts);
    }

    /**
     * 搜索文章
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     */
    public function get_search_posts()
    {
        $offset = $this->request->get('offset', 0);
        $search = $this->request->get('search', '');

        if (empty($search)) {
            return $this->make_error('缺少参数');
        }

        $table_post_search = $this->db->getPrefix() . 'one_post_search';//"SELECT times FROM `$table_post_search` WHERE search=%s", $search
        $times = $this->db->fetchObject($this->db->select('times')->from('table.one_post_search')->where('search = ?', $search))->times;
        if (empty($times)) {
            $this->db->query($this->db->insert($table_post_search)->rows([
                'search' => $search,
                'times' => 1
            ]));
        } else {
            $this->db->query($this->db->update($table_post_search)->rows(['times' => $times + 1, 'search' => $search]));
        }

        $args = [
            'posts_per_page' => self::POSTS_PER_PAGE,
            'offset' => $offset,
            'orderby' => 'created',
            's' => $search,
            'post_type' => ['post']
        ];

        $hide_cat = self::option_value('JHide_cat');
        if (!empty($hide_cat)) {
            $args['category__not_in'] = explode(',', $hide_cat);
        }

        $posts = $this->get_posts($args);
        return $this->make_success($posts);
    }

    /**
     * 热门搜索
     * @throws Typecho_Db_Exception
     */
    public function get_search_hot()
    {
        $table_post_search = $this->db->getPrefix() . 'one_post_search';
        $result = $this->db->fetchAll($this->db->query("SELECT search FROM `$table_post_search` ORDER BY times DESC LIMIT 0, 10"));
        $searchs = array_column($result, 'search');

        return $this->make_success($searchs);
    }

    /**
     * 获取文章详情
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Db_Exception
     */
    public function get_post_detail()
    {
        $post_id = $this->request->get('post_id');
        if (!$post_id) {
            return $this->make_error('缺少参数');
        }

        $this->widget('Widget_Archive@tmp_' . $post_id, 'pageSize=1&type=post', 'cid=' . $post_id)->to($postObj);
//        $postObj = $this->db->fetchObject($this->db->select()->from('table.contents')->where('cid = ?',$post_id));
        $post = [
            'id' => $postObj->cid,
            'time' => $postObj->created,
            'title' => $postObj->title,
            'content' => $postObj->content, //preg_replace("/<!--markdown-->/sm", '', $postObj->text),
            'comment_count' => $postObj->commentsNum,
            'thumbnail' => self::GetRandomThumbnail($postObj->text)
        ];
        $post['excerpt'] = html_entity_decode(self::tp_trim_words($postObj->text, 100, '...'));

//        if ($postObj->excerpt) {
//            $post['excerpt'] = html_entity_decode(self::tp_trim_words($postObj->excerpt, 100, '...'));
//        } else {
//        }

        //查询tag
        $tags = self::get_the_tags($post_id);
        if (!$tags) {
            $post['tags'] = [];
        } else {
            $post['tags'] = self::filter_tag_for_list($tags);
        }

        //查询分类
        $cats = self::get_the_category($post_id);
        $post['cats'] = [];
        foreach ($cats as $cat) {
            $post['cats'][] = [
                'id' => $cat['mid'],
                'name' => $cat['name'],
            ];
        }

        //美化时间
        $post['time'] = Utils::time_beautify($post['time']);

        //处理文章浏览数
        $post_views = self::get_post_meta($post_id, "views", true);
        $post['views'] = $post_views + 1;

        self::update_post_meta($post_id, 'views', $post['views']);

        //点赞数
        // $post['likes'] = (int) get_post_meta($post_id, "likes", true);

        //点赞列表
        $table_post_like = $this->db->getPrefix() . 'one_post_like';
        $users = $this->db->fetchAll($this->db->query("SELECT user_id FROM `$table_post_like` WHERE post_id=$post_id ORDER BY id DESC"));
        $post['like_list'] = [];
        if (!empty($users)) {
            foreach ($users as $user) {
                $mail = empty($user['user_mail']) ? '' : $user['user_mail'];
                $post['like_list'][] = Utils::ParseAvatar($mail);
            }
        }

        //收藏数
        $post['favorites'] = self::get_post_meta($post_id, "favorites", true);

        //能否评论
        $post['switch_comment'] = self::option_value('JSwitch_comment') === '1' ? 1 : 0;

        //用户数据
        $user = [];
//        $real_user = Typecho_Widget::widget('Widget_User');
        $user_id = self::check_login();
        if ($user_id) {
            $table_post_like = $this->db->getPrefix() . 'one_post_like';
            $post_like_id = $this->db->fetchRow($this->db->query("SELECT id FROM `$table_post_like` WHERE user_id=" . $user_id . " AND post_id=" . $post_id))['id'];
            $user['islike'] = $post_like_id ? 1 : 0;

            $table_post_favorite = $this->db->getPrefix() . 'one_post_favorite';
            $post_favorite_id = $this->db->fetchRow($this->db->query("SELECT id FROM `$table_post_favorite` WHERE user_id=" . $user_id . " AND post_id=" . $post_id))['id'];
            $user['isfavorite'] = $post_favorite_id ? 1 : 0;

            //添加文章浏览记录
            $table_post_view = $this->db->getPrefix() . 'one_post_view';
            $post_view_id = $this->db->fetchRow($this->db->query("SELECT id FROM `$table_post_view` WHERE user_id=" . $user_id . " AND post_id=" . $post_id))['id'];
            if (!$post_view_id) {
                $this->db->query($this->db->insert($table_post_view)->rows([
                    'user_id' => $user_id,
                    'post_id' => $post_id,
                ]));
            }
        }
        $post['user'] = $user;
        //author
        $author = [
            'uid' => $postObj->author->uid,
            'username' => $postObj->author->screenName,
            'intro' => $postObj->author->userSign==""?'该用户太懒了~':$postObj->author->userSign,
            'avatar' => empty($postObj->author->avatarUrl) ? $postObj->author->userAvatar : $postObj->author->avatarUrl,
            'is_follow' => UserFollow::statusFollow($user_id, $postObj->author->uid)
        ];
        $post['author'] = $author;

        return $this->make_success($post);
    }

    /**
     * 获取页面详情
     * @throws Typecho_Exception
     */
    public function get_post_page()
    {
        $page_id = $this->request->get('page_id');
        if (!$page_id) {
            return $this->make_error('缺少参数');
        }

//        $table_post = $this->db->getPrefix() . 'posts';
        $this->widget('Widget_Archive@tmpage_' . $page_id, 'pageSize=1&type=page', 'cid=' . $page_id)->to($post);
//        $post = $this->db->fetchObject($this->db->select()->from('table.contents')->where('cid = ?',$page_id));
        $page['title'] = $post->title;
        $page['content'] = $post->content;

        return $this->make_success($page);
    }

    /**
     * 热门 浏览数[views] 点赞数[likes] 评论数[commnets]
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Exception
     */
    public function get_hot_posts()
    {
        $offset = $this->request->get('offset', 0);

        $args = [
            'posts_per_page' => self::POSTS_PER_PAGE,
            'offset' => $offset,
            'order' => 'DESC',
        ];

        $hide_cat = self::option_value('JHide_cat');
        if (!empty($hide_cat)) {
            $args['category__not_in'] = explode(',', $hide_cat);
        }

        $sort = $this->request->get('sort', 'views');
        if ($sort == 'views' || $sort == 'likes' || $sort == 'favorites') {
            $args['meta_key'] = $sort;
            $args['orderby'] = $sort;
        } else {
            $args['orderby'] = 'commentsNum';
        }

        $select = $this->db->select();
        $select = $this->queryPost($select, $args);
        $common_posts = $this->db->fetchAll($select);

//        $common_posts = [];
//        foreach ($common_posts_ids as $comon_posts_id){
//            $this->widget('Widget_Archive@'.$comon_posts_id['cid'], 'pageSize=1&type=post', 'cid='.$comon_posts_id['cid'])->to($post);
//            $common_posts[]  = $post;
//        }
        $posts = $this->filter_post_for_list($common_posts);

        foreach ($posts as &$post) {
            //查询tag
            $tags = self::get_the_tags($post['id']);
            if (!$tags) {
                $post['tags'] = [];
            } else {
                $post['tags'] = self::filter_tag_for_list($tags);
            }

            if ($sort == 'views' || $sort == 'likes' || $sort == 'favorites') {
                $post[$sort] = self::get_post_meta($post['id'], $sort, true);
            }

            //美化时间
            $post['time'] = Utils::time_beautify($post['time']);
        }
        return $this->make_success($posts);
    }

    /**
     * 我的文章 浏览数[views] 点赞数[likes] 评论数[commnets] 收藏[favorite]
     * @throws Typecho_Plugin_Exception
     */
    public function get_my_posts()
    {
        $user_id = $this->check_login();
        if (!$user_id) {
            return $this->make_error('还没有登陆', -1);
        }

        $track = $this->request->get('track', 'views');
        if (empty($track)) {
            return $this->make_error('缺少参数');
        }

        $offset = $this->request->get('offset', 0);

        if ($track == 'views') {
            $table_name = $this->db->getPrefix() . 'one_post_view';
            $field = 'post_id';
            $orderby = 'id';
        } else if ($track == 'likes') {
            $table_name = $this->db->getPrefix() . 'one_post_like';
            $field = 'post_id';
            $orderby = 'id';
        } else if ($track == 'comments') {
            $table_name = $this->db->getPrefix() . 'comments';
            $field = 'cid';
            $orderby = 'coid';
        } else if ($track == 'favorites') {
            $table_name = $this->db->getPrefix() . 'one_post_favorite';
            $field = 'post_id';
            $orderby = 'id';
        }

        $per_page_count = self::POSTS_PER_PAGE;
        if ($track == 'comments') {
            $post_ids = $this->db->fetchAll($this->db->query("SELECT distinct $field,$orderby FROM `$table_name` WHERE authorId=$user_id ORDER BY $orderby DESC LIMIT $offset, $per_page_count"));
        } else {
            $post_ids = $this->db->fetchAll($this->db->query("SELECT distinct $field,$orderby FROM `$table_name` WHERE user_id=$user_id ORDER BY $orderby DESC LIMIT $offset, $per_page_count"));
        }
        if (empty($post_ids)) {
            return $this->make_success([]);
        }

        $args = [
            'post__in' => array_column($post_ids, $field),
            'orderby' => 'created',
            'posts_per_page' => $per_page_count,
            'ignore_sticky_posts' => 1,
        ];

        $posts = $this->get_posts($args);
        return $this->make_success($posts);
    }


    


    /**
     * typecho 登录接口
     * @return array
     * @throws Typecho_Exception
     */
    public function user_login()
    {
        $username = $this->request->get('username','');
        $password = $this->request->get('password', '');
        $user = Typecho_Widget::widget('Widget_User');
        if (!$user->login($username, $password, true)) { //使用特定的账号登陆
            return $this->make_error('登录失败');
        }
        $mobile_token = $this->generate_token();
        $this->tp_update_user($user->uid, [
            'mobile_token' => $mobile_token
        ]);
        $userdata = array(
            "nickname" => $user->screenName,
            "token" => $mobile_token,
            "mail" => $user->mail
        );

        return $this->make_success($userdata);
    }

    public function geturl($url, $data)
    {
        $query = '';
        if (!empty($data)) {
            $query = '?' . http_build_query($data);
        }
        $headerArray = array("Content-type:application/json;", "Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . $query);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
        return $output;
    }


    public function posturl($url, $data)
    {
        $data = json_encode($data);
        $headerArray = array("Content-type:application/json;charset='utf-8'", "Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output, true);
    }

 


    /**
     * 用户 点赞 文章
     * @throws Typecho_Db_Exception
     */
    public function user_like()
    {
        $post_id = $this->request->get('post_id', 0);
        if (empty($post_id)) {
            return $this->make_error('缺少参数');
        }

        $user_id = $this->check_login();
        if (!$user_id) {
            return $this->make_error('还没有登陆', -1);
        }

        $table_post_like = $this->db->getPrefix() . 'one_post_like';
        $post_like_id = $this->db->fetchRow($this->db->query("SELECT id FROM `$table_post_like` WHERE user_id=" . $user_id . " AND post_id=" . $post_id))["id"];

        $post_likes = self::get_post_meta($post_id, "likes", true);

        if ($post_like_id) {
            $this->db->query("DELETE FROM `$table_post_like` WHERE id=$post_like_id");

            self::update_post_meta($post_id, 'likes', ($post_likes - 1));
        } else {
            $this->db->query($this->db->insert($table_post_like)->rows([
                'user_id' => $user_id,
                'post_id' => $post_id,
            ]));

            self::update_post_meta($post_id, 'likes', ($post_likes + 1));
        }

        return $this->make_success();
    }

    /**
     * 用户 收藏 文章
     */
    public function user_favorite()
    {
        $post_id = $this->request->get('post_id', 0);
        if (empty($post_id)) {
            return $this->make_error('缺少参数');
        }

        $user_id = $this->check_login();
        if (!$user_id) {
            return $this->make_error('还没有登陆', -1);
        }

        $table_post_favorite = $this->db->getPrefix() . 'one_post_favorite';
        $post_favorite_id = $this->db->fetchRow($this->db->query("SELECT id FROM `$table_post_favorite` WHERE user_id=" . $user_id . " AND post_id=" . $post_id))["id"];

        $post_favorites = self::get_post_meta($post_id, "favorites", true);

        if ($post_favorite_id) {
            $this->db->query("DELETE FROM `$table_post_favorite` WHERE id=$post_favorite_id");

            self::update_post_meta($post_id, 'favorites', ($post_favorites - 1));
        } else {
            $this->db->query($this->db->insert($table_post_favorite)->rows([
                'user_id' => $user_id,
                'post_id' => $post_id,
            ]));
            self::update_post_meta($post_id, 'favorites', ($post_favorites + 1));
        }

        return $this->make_success();
    }

    /**
     * 文章的评论列表
     */
    public function comment_index()
    {
        $post_id = $this->request->get('post_id', 0);
        if (empty($post_id)) {
            return $this->make_error('缺少参数');
        }

        $offset = $this->request->get('offset', 0);

        $token = $this->request->get('token', '');
        $user = [];
        if ($token != 'false') {
            $user = $this->db->fetchRow($this->db->select('uid', 'ext_mail')->from('table.users')->where('mobile_token = ?', $token));
            if (empty($user['uid'])) {
                return $this->make_success([
//                    "comments" => [],
//                    "user_mail" => ''
                ]);
            }
        } else {
            $user['uid'] = null;
        }

        $comments = $this->get_comments($post_id, $user['uid'], 0, $offset);

        foreach ($comments as &$comment) {
            $comment['replys'] = $this->get_comments($post_id, $user['uid'], $comment['id']);
        }
        return $this->make_success($comments);
//        return $this->make_success([
//            "comments" => $comments,
//            "user_mail" => empty($user['ext_mail'])?'':$user['ext_mail']
//        ]);
    }
 /**
     * 文章的评论列表
     */
    public function comment_all()
    {
        $user_id = $this->check_login();
        if (!$user_id) {
            return $this->make_error('还没有登陆', -1);
        }
        $page_index = $this->request->get('page_index', 1);
        $page_size = $this->request->get('page_size', 20);
        if ($page_index < 1) {
            $page_index = 1;
        }

        $offset = ($page_index-1) * $page_size;
        $limit = "LIMIT $page_size OFFSET $offset";
        $table_comments = $this->db->getPrefix().'comments';
        $result = $this->db->fetchAll($this->db->query("SELECT * FROM `$table_comments` ORDER BY created DESC $limit"));

        $comments = [];
        foreach ($result as $comment) {
            $comments[] = [
                'id' => $comment['coid'],
                'user' => [
                    'id' => $comment['authorId'],
                    'name' => $comment['author'],
                    'mail' => $comment['mail'],
                    'url' => $comment['url'],
                ],
                'content' => utils::ParseReply($comment['text']),
                'status' => $comment['status'],
                'time' => Utils::time_beautify($comment['created']),
            ];
        }
    
       return $this->make_success([
           "comments" => $comments,
           "current_user_is_admin" => $user['group'] == 'administrator' ? true : false,
       ]);
    }
    /**
     * 发布评论
     * @throws Typecho_Plugin_Exception
     */
    public function comment_add()
    {

        if (self::option_value('JSwitch_comment') !== '1') {
            return $this->make_error('评论功能未开启');
        }

        $post_id = $this->request->get('post_id', 0);
        $parent_id = $this->request->get('parent_id', 0);
        $content = $this->request->get('content', '');
        if (empty($post_id) || empty($content)) {
            return $this->make_error('参数错误');
        }
        $post = $this->db->fetchObject($this->db->select('authorId')->from('table.contents')->where('cid = ?', $post_id));
        if (empty((array)$post_id)) {
            return $this->make_error("评论的文章不存在");
        }

        // 用户信息
        $token = $this->request->get('token', '');
        $nickname = $this->request->get('nickname', '');
        $c_mail = $this->request->get('mail', '');
        $c_url = $this->request->get('url', '');

        if ($c_mail) {
            if (!Utils::Verify_Email($c_mail)) {
                return $this->make_error("请输入正确的邮箱地址");
            }
        }
        
        // 没有登陆也是能够评论的
        // 只需要按照设置 审核就可以了。
        $isAdmin = false;
        $uid = 0;
        if (!empty($token)) {
            $user = $this->db->fetchRow($this->db->select()->from('table.users')->where('mobile_token = ?', $token));
            if ($user['uid'] && $user['group'] == 'administrator') {
                $isAdmin = true;
                $uid = $user['uid'];
            }
        }
//            'waiting'      =>  _t('待审核'),
//            'approved'   =>  _t('显示'),
//            'spam'      =>  _t('垃圾')
        $status = self::option_value('JSwitch_comment_audit') == '1';
        $comment_approved = self::option_value('JSwitch_comment_verify') === '1' && !$isAdmin ? 'waiting' : 'approved';

        $createTime = time();
        $comment_id = $this->db->query($this->db->insert('table.comments')->rows([
            'cid' => $post_id,
            'text' => $content,
            'created' => $createTime,
            'parent' => $parent_id,
            'status' => $comment_approved,
            'ownerId' => 1, // 不知道为什么是 1 官方的所有 ownerId 都是 1
            'type' => 'comment',
            'author' => $nickname,
            'authorId' => $uid,
            'agent' => 'typechoMobile v1.0',
            'ip' => '8.8.8.8',
            'url' => $c_url,
            'mail' => $c_mail

        ]));
        if ($post_id > 0) {
            $row = $this->db->fetchRow($this->db->select('commentsNum')->from('table.contents')->where('cid = ?', $post_id));
            $this->db->query($this->db->update('table.contents')->rows(array('commentsNum' => (int)$row['commentsNum'] + 1))->where('cid = ?', $post_id));
        }
        $userInfo = [
            'name' => $nickname,
            'mail' => $c_mail,
            'url' => $c_url
        ];

        $comments = [
            'id' => $comment_id,
            'user' => $userInfo,
            'content' => utils::ParseReply($content),
            'status' => $comment_approved,
            'time' => $createTime,
        ];
        
        return $this->make_success($comments);
    }

    /**
     * 删除评论
     * @throws Typecho_Db_Exception
     */
    public function comment_delete()
    {
        $user_id = $this->check_login();
        if (!$user_id) {
            return $this->make_error('还没有登陆', -1);
        }

        $comment_id = $this->request->get('comment_id', 0);
        $post_id = $this->request->get('post_id', 0);

        if (empty($comment_id) or empty($post_id)) {
            return $this->make_error('缺少参数');
        }

        $table_comments = $this->db->getPrefix() . 'comments';
        $res = $this->db->query("DELETE FROM $table_comments WHERE coid=$comment_id OR parent=$comment_id");
        if ($post_id > 0) {
            $row = $this->db->fetchRow($this->db->select('commentsNum')->from('table.contents')->where('cid = ?', $post_id));
            $this->db->query($this->db->update('table.contents')->rows(array('commentsNum' => (int)$row['commentsNum'] - 1))->where('cid = ?', $post_id));
        }
        return $this->make_success($res);
    }

    /**
     * 评论内容
     */
    private function get_comments($post_id, $my_user_id, $parent, $offset = null)
    {
        $per_page_count = self::POSTS_PER_PAGE;
        $table_comments = $this->db->getPrefix() . 'comments';

        $fields = 'coid, author, created, text, status, ownerId, authorId, mail';
        $where = "cid=$post_id AND parent=$parent";
        if ($my_user_id) {
            $where = $where . " AND (status='approved' OR authorId=$my_user_id)";
        } else {
            $where = $where . " AND status='approved'";
        }
        $limit = '';
        if ($offset !== null) {
            $limit = "LIMIT $offset, $per_page_count";
        }

        $result = $this->db->fetchAll($this->db->query("SELECT $fields FROM `$table_comments` WHERE $where ORDER BY created DESC $limit"));

        $comments = [];
        foreach ($result as $comment) {
//            $name = $this->get_user_meta($comment['ownerId'], 'screenName', true);
//            if (!$name) {
//                $name = $this->get_user_meta($comment['ownerId'], 'name', true);
//            }

//            $avatar = $this->get_user_meta($comment['ownerId'], 'avatarUrl', true);

            $comments[] = [
                'id' => $comment['coid'],
                'user' => [
                    'id' => $comment['authorId'],
                    'name' => $comment['author'],
                    'avatar' => utils::ParseAvatar($comment['mail']),
                    'is_me' => ($comment['authorId'] == $my_user_id) ? 1 : 0,
                ],
                'content' => utils::ParseReply($comment['text']),
                'approved' => $comment['status'] == 'approved',
                'time' => Utils::time_beautify($comment['created']),
            ];
        }

        return $comments;
    }

    public function action()
    {
        // TODO: Implement action() method.
    }

    private function defaults()
    {
    }

    private function check_login()
    {
//        $real_user = Typecho_Widget::widget('Widget_User');
//        return $real_user->hasLogin()?$real_user->uid:false;
        $token = $this->request->get('token', '');
        if (empty($token)) {
            return false;
        }
        if ($token == 'false') return '';
        $user_id = $this->db->fetchRow($this->db->select('uid')->from('table.users')->where('mobile_token = ?', $token))['uid'];

        return $user_id ? $user_id : false;
    }

    /**
     * 获取上传目录
     * @return array
     */
    private function tp_uploads_dir()
    {
        $uploads = [];
        $options = Helper::options();
        $date = new Typecho_Date();
        $up_path = defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : UPLOAD_DIR;

        $path = Typecho_Common::url($up_path,
            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);
        $uploads['path'] = $path . '/' . $date->year . '/' . $date->month;
        $uploads['baseurl'] = $options->index . $up_path;
        $uploads['basedir'] = $path;
        $uploads['url'] = $options->index . $up_path . '/' . $date->year . '/' . $date->month;
        return $uploads;
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $appid
     * @param $session
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功 0，失败返回对应的错误码
     */
    private function decryptData($appid, $session, $encryptedData, $iv, &$data)
    {

        $ErrorCode = array(
            'OK' => 0,
            'IllegalAesKey' => -41001,
            'IllegalIv' => -41002,
            'IllegalBuffer' => -41003,
            'DecodeBase64Error' => -41004
        );

        if (strlen($session) != 24) {
            return array('code' => $ErrorCode['IllegalAesKey'], 'message' => 'session_key 长度不合法', 'session_key' => $session);
        }
        $aesKey = base64_decode($session);
        if (strlen($iv) != 24) {
            return array('code' => $ErrorCode['IllegalIv'], 'message' => 'iv 长度不合法', 'iv' => $iv);
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $data_decode = json_decode($result);
        if ($data_decode == NULL) {
            return array('code' => $ErrorCode['IllegalBuffer'], 'message' => '解密失败，非法缓存');
        }
        if ($data_decode->watermark->appid != $appid) {
            return array('code' => $ErrorCode['IllegalBuffer'], 'message' => '解密失败，AppID 不正确');
        }
        $data = $result;
        return $ErrorCode['OK'];
    }



   
    private function http_post_data($url, $data_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        //echo $return_content."<br>";
        ob_end_clean();

        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //  return array($return_code, $return_content);
        return $return_content;
    }

    function random_code($length = 8, $chars = null)
    {
        if (empty($chars)) {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        }
        $count = strlen($chars) - 1;
        $code = '';
        while (strlen($code) < $length) {
            $code .= substr($chars, rand(0, $count), 1);
        }
        return $code;
    }

    private static function generate_token()
    {
        return md5(uniqid(rand()));
    }

    /**
     * 圈子
     *
     */
    public function circle_hot()
    {
        $circles = CircleFollow::getHotCircles();
        return $this->make_success($circles);
    }

    public function circle_hot_list()
    {
        // test
        $offset = $this->request->get('offset', 0);

        $args = [
            'posts_per_page' => self::POSTS_PER_PAGE,
            'offset' => $offset,
            'orderby' => 'views',
            'order' => 'DESC'
        ];

        $hide_cat = self::option_value('JHide_cat');
        if (!empty($hide_cat)) {
            $args['category__not_in'] = explode(',', $hide_cat);
        }


        $select = $this->db->select();
        $select = $this->queryPost($select, $args);
        $common_posts = $this->db->fetchAll($select);

        $data = [
            'per_page' => self::POSTS_PER_PAGE,
            'current_page' => ($offset / self::POSTS_PER_PAGE) + 1,
            'data' => []
        ];

        foreach ($common_posts as $post) {
            $postObj = Typecho_Widget::widget('Widget_Contents_Modify@tmp_' . $post['cid']);
            $postObj->push($post);

            $tmp = [
                'id' => $postObj->cid,
                'uid' => $postObj->authorId,
                'circle_id' => $postObj->categoryId,
                'title' => $postObj->title,
                'content' => $postObj->excerpt(),
                'media' => [$this::GetRandomThumbnail($postObj->text)],
                'read_count' => $postObj->views,
                'create_time' => $postObj->created,
                'comment_count' => $postObj->commentsNum,
                'fabulous_count' => $postObj->views, // 点赞
                'collection_count' => 0, // 收藏 数 todo
                'is_collection' => false, // todo
                'type' => 1, // 1张图片
                'userInfo' => [
                    'username' => $postObj->author->screenName,
                    'avatar' => empty($postObj->author->avatarUrl) ? $postObj->author->userAvatar : $postObj->author->avatarUrl,
                ],
                'topicInfo' => [
                    'cate_id' => $postObj->categoryId,
                    'topic_name' => $postObj->categoryArray['name']
                ]
            ];
            array_push($data['data'], $tmp);
        }
        return $this->make_success($data);
    }

    public function circle_follow_user()
    {
        $fid = $this->request->get('fid', '');
        if(!$fid){
            return $this->make_error('参数错误');
        }
        $user_id = self::check_login();
        if(!$user_id) return $this->make_error_notlogin('没有登录');
        if(UserFollow::addFollow($user_id,intval($fid))){
            return $this->make_success();
        }
        return $this->make_error('error');
    }

    public function circle_cancel_follow_user()
    {
        $fid = $this->request->get('fid', '');
        if(!$fid){
            return $this->make_error('参数错误');
        }

        $user_id = self::check_login();
        if(!$user_id) return $this->make_error_notlogin('没有登录');
        if(UserFollow::cancleFollow($user_id,intval($fid))){
            return $this->make_success();
        }
        return $this->make_error('error');

    }
}
