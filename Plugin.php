<div class="j-setting-contain">
    <link href="<?php echo Helper::options()->rootUrl ?>/usr/plugins/TypechoMobile/assets/css/joe.setting.min.css" rel="stylesheet" type="text/css" />
    <div>
        <div class="j-aside">
            <div class="logo">Typecho 客户端配套插件</div>
            <ul class="j-setting-tab">
                <li data-current="j-setting-notice">插件公告</li>
                <li data-current="j-setting-basic">基础设置</li>
                <!-- <li data-current="j-setting-index">首页设置</li> -->
                <!-- <li data-current="j-setting-hot">热门设置</li> -->
                <li data-current="j-setting-login">登录设置</li>
            </ul>
            <?php require_once('Backups.php'); ?>
        </div>
    </div>
    <span id="j-version" style="display: none;">1.0.3</span>
    <div class="j-setting-notice"></div>
    <script src="<?php echo Helper::options()->rootUrl ?>/usr/plugins/TypechoMobile/assets/js/joe.setting.min.js"></script>
<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * typecho mobile  专用插件
 * 文本解析暂时只支持 Markdown
 * @package TypechoMobile
 * @author YingYue
 * @version 1.0.0
 * @link https://apkdv.com
 *
 */

require_once 'widget/Widget_Contents_Modify.php';

class TypechoMobile_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        self::sqlInstall();
        $base = '/TypechoMobile/v1';
        $setting = $base.'/setting';

        Helper::addRoute('get_home',$setting.'/home','TypechoMobile_Action','get_home');
        Helper::addRoute('get_hot',$setting.'/hot','TypechoMobile_Action','get_hot');
        Helper::addRoute('get_category',$setting.'/category','TypechoMobile_Action','get_category');
        Helper::addRoute('get_profile',$setting.'/profile','TypechoMobile_Action','get_profile');
        
        $category = $base.'/category';
        //获取所有分类
        Helper::addRoute('get_category_index',$category.'/index','TypechoMobile_Action','get_category_index');

        $tag = $base.'/tags';
        //获取所有tag
        Helper::addRoute('get_tag_index',$tag.'/index','TypechoMobile_Action','get_tag_index');

        $comment = $base.'/comment';
        //文章的评论列表
        Helper::addRoute('comment_index',$comment.'/index','TypechoMobile_Action','comment_index');
        //发布评论
        Helper::addRoute('comment_add',$comment.'/add','TypechoMobile_Action','comment_add');
        //删除评论
        Helper::addRoute('comment_delete',$comment.'/delete','TypechoMobile_Action','comment_delete');
        // 获取所有评论
        Helper::addRoute('comment_all',$comment.'/all','TypechoMobile_Action','comment_all');


        $post = $base.'/posts';
        //最新文章
        Helper::addRoute('get_last_posts',$post.'/last','TypechoMobile_Action','get_last_posts');
        //获取某个分类下的文章
        Helper::addRoute('get_category_posts',$post.'/category','TypechoMobile_Action','get_category_posts');
        //获取某个TAG下的文章
        Helper::addRoute('get_tag_posts',$post.'/tag','TypechoMobile_Action','get_tag_posts');
        //搜索
        Helper::addRoute('get_search_posts',$post.'/search','TypechoMobile_Action','get_search_posts');
        //热门搜索
        Helper::addRoute('get_search_hot',$post.'/search/hot','TypechoMobile_Action','get_search_hot');
        //文章详情
        Helper::addRoute('get_post_detail',$post.'/detail','TypechoMobile_Action','get_post_detail');
        //页面详情
        Helper::addRoute('get_post_page',$post.'/page','TypechoMobile_Action','get_post_page');
        //热门 浏览数[views] 点赞数[likes] 评论数[commnets]
        Helper::addRoute('get_hot_posts',$post.'/hot','TypechoMobile_Action','get_hot_posts');
        //我的文章 浏览数[views] 点赞数[likes] 评论数[commnets] 收藏[favorite]
        Helper::addRoute('get_my_posts',$post.'/my','TypechoMobile_Action','get_my_posts');
 


        $user = $base.'/user';
        //用户登陆
        Helper::addRoute('user_login',$user.'/login','TypechoMobile_Action','user_login');

        //用户点赞
        Helper::addRoute('user_like',$user.'/like','TypechoMobile_Action','user_like');
        //用户收藏
        Helper::addRoute('user_favorite',$user.'/favorite','TypechoMobile_Action','user_favorite');

        $circle = $base.'/circle';
        Helper::addRoute('circle_hot',$circle.'/hot','TypechoMobile_Action','circle_hot');
        Helper::addRoute('circle_hot_list',$circle.'/hot_list','TypechoMobile_Action','circle_hot_list');
        Helper::addRoute('circle_follow_user',$circle.'/follow_user','TypechoMobile_Action','circle_follow_user');
        Helper::addRoute('circle_cancel_follow_user',$circle.'/cancelf_user','TypechoMobile_Action','circle_cancel_follow_user');

    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        Helper::removeRoute('get_home');
        Helper::removeRoute('get_hot');
        Helper::removeRoute('get_category');
        Helper::removeRoute('get_profile');
        Helper::removeRoute('get_login');

        Helper::removeRoute('get_category_index');
        Helper::removeRoute('get_tag_index');

        Helper::removeRoute('comment_index');
        Helper::removeRoute('comment_add');
        Helper::removeRoute('comment_delete');
        Helper::removeRoute('comment_all');

        Helper::removeRoute('get_last_posts');
        Helper::removeRoute('get_category_posts');
        Helper::removeRoute('get_search_posts');
        Helper::removeRoute('get_search_hot');
        Helper::removeRoute('get_tag_posts');
        Helper::removeRoute('get_post_detail');
        Helper::removeRoute('get_post_page');
        Helper::removeRoute('get_hot_posts');
        Helper::removeRoute('get_my_posts');



        Helper::removeRoute('user_login');
        Helper::removeRoute('user_login3');
        Helper::removeRoute('user_logintest');
        Helper::removeRoute('user_index');
        Helper::removeRoute('user_like');
        Helper::removeRoute('user_favorite');

        Helper::removeRoute('circle_hot');
        Helper::removeRoute('circle_hot_list');
        Helper::removeRoute('circle_follow_user');
        Helper::removeRoute('circle_cancel_follow_user');


    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /**
         * 基础设置
         */
        // $JHide_cat = new Typecho_Widget_Helper_Form_Element_Text(
        //     'JHide_cat',
        //     NULL,
        //     Null,
        //     '隐藏分类',
        //     '隐藏相应分类下的文章,分类ID,英文逗号分隔<br>例如：3,4,7'
        // );
        // $JHide_cat->setAttribute('class', 'j-setting-content j-setting-basic');
        // $form->addInput($JHide_cat);

        $JSwitch_excerpt = new Typecho_Widget_Helper_Form_Element_Radio(
                'JSwitch_excerpt',
            array(
                    '0' => '不显示',
                    '1' => '显示'
            ),
            '1','文章摘要','文章列表中是否显示摘要? *目前不生效（因为目前只有管理员功能，后期或许会有针对用户的单独的博客客户端）'
        );
        $JSwitch_excerpt->setAttribute('class', 'j-setting-content j-setting-basic');
        $form->addInput($JSwitch_excerpt);

        $JSwitch_comment = new Typecho_Widget_Helper_Form_Element_Radio(
            'JSwitch_comment',
            array(
                '0' => '关闭',
                '1' => '开启'
            ),
            '0','评论','是否开启评论功能? *注意：关闭后即使是管理员也无法通过 APP 进行评论'
        );
        $JSwitch_comment->setAttribute('class', 'j-setting-content j-setting-basic');
        $form->addInput($JSwitch_comment);

        $JSwitch_comment_verify = new Typecho_Widget_Helper_Form_Element_Radio(
            'JSwitch_comment_verify',
            array(
                '0' => '关闭',
                '1' => '开启'
            ),
            '0','评论审核','评论是否需要审核？ *注意：只对普通用户生效，管理员评论任然不需要审核'
        );
        $JSwitch_comment_verify->setAttribute('class', 'j-setting-content j-setting-basic');
        $form->addInput($JSwitch_comment_verify);

        // $JDefault_thumbnail = new Typecho_Widget_Helper_Form_Element_Text(
        //     'JDefault_thumbnail',
        //     NULL,
        //     Null,
        //     '默认微缩图',
        //     '默认微缩图 *同样现在没啥用'
        // );
        // $JDefault_thumbnail->setAttribute('class', 'j-setting-content j-setting-basic');
        // $form->addInput($JDefault_thumbnail);

        // $JSwitch_stick = new Typecho_Widget_Helper_Form_Element_Radio(
        //     'JSwitch_stick',
        //     array(
        //         '0' => '关闭',
        //         '1' => '开启'
        //     ),
        //     '0','置顶功能','是否开启置顶功能'
        // );
        // $JSwitch_stick->setAttribute('class', 'j-setting-content j-setting-basic');
        // $form->addInput($JSwitch_stick);

        // $JSticky_posts = new Typecho_Widget_Helper_Form_Element_Text(
        //     'JSticky_posts',
        //     NULL,
        //     Null,
        //     '置顶文章id',
        //     '置顶文章id，英文逗号分隔,如：1,2,3'
        // );
        // $JSticky_posts->setAttribute('class', 'j-setting-content j-setting-basic');
        // $form->addInput($JSticky_posts);

        /**
         * 首页设置
         */

        $JHome_top_nav = new Typecho_Widget_Helper_Form_Element_Text(
            'JHome_top_nav',
            NULL,
            "1,2,3",
            '顶部导航',
            '分类ID,英文逗号分隔<br>例如：8,12,23 *后期功能'
        );
        $JHome_top_nav->setAttribute('class', 'j-setting-content j-setting-index');
        $form->addInput($JHome_top_nav);

        $JTop_slide = new Typecho_Widget_Helper_Form_Element_Text(
            'JTop_slide',
            NULL,
            Null,
            '幻灯片',
            '设置首页幻灯片显示的文章,文章ID,英文逗号分隔<br>例如：8,12,23 *后期功能(可能移除)'
        );
        $JTop_slide->setAttribute('class', 'j-setting-content j-setting-index');
        $form->addInput($JTop_slide);

        $JHome_icon_nav = new Typecho_Widget_Helper_Form_Element_Textarea(
            'JHome_icon_nav',
            NULL,
            "https://xcx.jiangqie.com/wp-content/uploads/2020/05/32-1.png||使用必读||/pages/article/article?post_id=76",
            '导航',
            '设置首页导航页显示，一行一个，格式：图标||标题||链接<br>
                         https://xcx.jiangqie.com/wp-content/uploads/2020/05/32-1.png||代码下载||/pages/article/article?post_id=261<br>post_id是要显示文章的id'
        );
        $JHome_icon_nav->setAttribute('class', 'j-setting-content j-setting-index');
        $form->addInput($JHome_icon_nav);

      



        $JHome_hot = new Typecho_Widget_Helper_Form_Element_Text(
            'JHome_hot',
            NULL,
            "71",
            '首页热门',
            '设置设置首页热门文章,文章ID,英文逗号分隔<br>例如：8,12,23'
        );
        $JHome_hot->setAttribute('class', 'j-setting-content j-setting-index');
        $form->addInput($JHome_hot);

        $JHome_list_mode = new Typecho_Widget_Helper_Form_Element_Select(
            'JHome_list_mode',
            array(
                '3'         => '混合模式',
                '1'         => '小图模式',
                '2'         => '大图模式',
            ),
            '3',
            '列表模式',
            '首页文章列表显示方式'
        );
        $JHome_list_mode->setAttribute('class', 'j-setting-content j-setting-index');
        $form->addInput($JHome_list_mode);


        /**
         * 热榜设置
         */
        $JHot_background= new Typecho_Widget_Helper_Form_Element_Text(
            'JHot_background',
            NULL,
            "https://xcx.jiangqie.com/wp-content/uploads/2020/08/333.png",
            '热门背景图',
            '热门背景图'
        );
        $JHot_background->setAttribute('class', 'j-setting-content j-setting-hot');
        $form->addInput($JHot_background);

        $JHot_title = new Typecho_Widget_Helper_Form_Element_Text(
            'JHot_title',
            NULL,
            "热榜",
            '热门标题',
            '热门标题'
        );
        $JHot_title->setAttribute('class', 'j-setting-content j-setting-hot');
        $form->addInput($JHot_title);

        $JHot_description = new Typecho_Widget_Helper_Form_Element_Text(
            'JHot_description',
            NULL,
            "热门描述",
            '热门描述',
            '热门描述'
        );
        $JHot_description->setAttribute('class', 'j-setting-content j-setting-hot');
        $form->addInput($JHot_description);
        /**
         * login
         */
        $JLogin_bg = new Typecho_Widget_Helper_Form_Element_Text(
            'JLogin_bg',
            NULL,
            "https://xcx.jiangqie.com/wp-content/uploads/2020/02/21212.png",
            'APP启动图',
            'APP启动图'
        );
        $JLogin_bg->setAttribute('class', 'j-setting-content j-setting-login');
        $form->addInput($JLogin_bg);
        /**
         * profile
         */
        // $JProfile_background= new Typecho_Widget_Helper_Form_Element_Text(
        //     'JProfile_background',
        //     NULL,
        //     "https://xcx.jiangqie.com/wp-content/uploads/2020/02/21212.png",
        //     '顶部背景图',
        //     '顶部背景图'
        // );
        // $JProfile_background->setAttribute('class', 'j-setting-content j-setting-profile');
        // $form->addInput($JProfile_background);

    }



    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    public static function sqlInstall(){
        // create circle follow table
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        
        if (!array_key_exists('mobile_token', $db->fetchRow($db->select()->from('table.users')))) {
            $mobile_token = self::generate_token();
            $db->query('ALTER TABLE `'.$db->getPrefix().'users` ADD `mobile_token` varchar(50) DEFAULT "'.$mobile_token.'";');
        }

        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
    }

    private static function generate_token()
    {
        return md5(uniqid(rand()));
    }
}