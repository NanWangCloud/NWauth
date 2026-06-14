<?php
/**
 * 作者：任意
 * qq：2908286914
 */

// 保护核心文件，禁止直接访问
if (!defined('IN_SYSTEM')) {
    exit('Access Denied');
}

/**
 * 渲染布局
 * @param string $content HTML 内容
 * @param string $page_title 页面标题
 * @param string $layout_type 'user' 或 'admin'
 */
function render_layout($content, $page_title = '授权管理系统', $layout_type = 'user') {
    if ($layout_type === 'user' && isAdminLoggedIn()) {
        set_flash_message('danger', '管理员账号不可进入用户后台。');
        redirect(get_setting('admin_path', 'admin') . '/admin.php');
    }
    $csrf_token = generateCsrfToken();
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // 渲染最终布局
    include __DIR__ . '/../includes/layout.php';
    exit;
}

/**
 * 获取侧边栏项
 * @param string $layout_type 'user' 或 'admin'
 * @return array
 */
function get_sidebar_menu($layout_type = 'user') {
    $admin_path = get_setting('admin_path', 'admin');
    if ($layout_type === 'admin') {
        return [
            ['title' => '主控台', 'type' => 'section'],
            ['title' => '数据概览', 'icon' => 'fas fa-chart-line', 'url' => BASE_URL . $admin_path . '/admin.php', 'id' => 'admin.php'],
            ['title' => '业务管理', 'type' => 'section'],
            ['title' => '用户管理', 'icon' => 'fas fa-users', 'url' => BASE_URL . $admin_path . '/admin_users.php', 'id' => 'admin_users.php'],
            ['title' => '产品管理', 'icon' => 'fas fa-box', 'url' => BASE_URL . $admin_path . '/admin_product.php', 'id' => 'admin_product.php'],
            ['title' => '软件更新', 'icon' => 'fas fa-cloud-upload-alt', 'url' => BASE_URL . $admin_path . '/admin_updates.php', 'id' => 'admin_updates.php'],
            ['title' => '卡密管理', 'icon' => 'fas fa-credit-card', 'url' => BASE_URL . $admin_path . '/admin_cards.php', 'id' => 'admin_cards.php'],
            ['title' => '优惠券管理', 'icon' => 'fas fa-ticket-alt', 'url' => BASE_URL . $admin_path . '/admin_coupons.php', 'id' => 'admin_coupons.php'],
            ['title' => '财务明细', 'icon' => 'fas fa-file-invoice-dollar', 'url' => BASE_URL . $admin_path . '/admin_finance.php', 'id' => 'admin_finance.php'],
            ['title' => '互动与内容', 'type' => 'section'],
            ['title' => '个人资料', 'icon' => 'fas fa-user-circle', 'url' => BASE_URL . $admin_path . '/admin_profile.php', 'id' => 'admin_profile.php'],
            ['title' => '反馈处理', 'icon' => 'fas fa-comment-dots', 'url' => BASE_URL . $admin_path . '/admin_feedback.php', 'id' => 'admin_feedback.php'],
            ['title' => '公告发布', 'icon' => 'fas fa-bullhorn', 'url' => BASE_URL . $admin_path . '/admin_articles.php', 'id' => 'admin_articles.php'],
            ['title' => '系统配置', 'type' => 'section'],
            ['title' => '系统设置', 'icon' => 'fas fa-cog', 'url' => BASE_URL . $admin_path . '/admin_settings.php', 'id' => 'admin_settings.php'],
            ['title' => '管理日志', 'icon' => 'fas fa-history', 'url' => BASE_URL . $admin_path . '/admin_logs.php', 'id' => 'admin_logs.php'],
            ['title' => '退出登录', 'icon' => 'fas fa-sign-out-alt', 'url' => BASE_URL . 'logout.php', 'id' => 'logout', 'class' => 'text-danger']
        ];
    } else {
        $menu = [
            ['title' => '我的控制台', 'icon' => 'fas fa-home', 'url' => BASE_URL . 'user/dashboard.php', 'id' => 'dashboard.php'],
            ['title' => '个人资料', 'icon' => 'fas fa-user-circle', 'url' => BASE_URL . 'user/profile.php', 'id' => 'profile.php'],
            ['title' => '购买授权', 'icon' => 'fas fa-shopping-cart', 'url' => BASE_URL . 'user/buy.php', 'id' => 'buy.php'],
            ['title' => '软件更新', 'icon' => 'fas fa-history', 'url' => BASE_URL . 'updates.php', 'id' => 'updates.php'],
            ['title' => '卡密充值', 'icon' => 'fas fa-credit-card', 'url' => BASE_URL . 'user/recharge.php', 'id' => 'recharge.php'],
            ['title' => '消费记录', 'icon' => 'fas fa-history', 'url' => BASE_URL . 'user/records.php', 'id' => 'records.php'],
            ['title' => '反馈建议', 'icon' => 'fas fa-comment-dots', 'url' => BASE_URL . 'user/feedback.php', 'id' => 'feedback.php']
        ];
        
        $menu[] = ['title' => '代理中心', 'type' => 'section'];
        $menu[] = ['title' => (isset($_SESSION['is_agent']) && $_SESSION['is_agent']) ? '代理面板' : '成为代理', 'icon' => 'fas fa-user-tie text-warning', 'url' => BASE_URL . 'user/agent.php', 'id' => 'agent.php'];
        
        $menu[] = ['title' => '退出登录', 'icon' => 'fas fa-sign-out-alt', 'url' => BASE_URL . 'logout.php', 'id' => 'logout', 'class' => 'text-danger logout'];
        
        return $menu;
    }
}

/**
 * 作者：任意
 * qq：2908286914
 */
?>