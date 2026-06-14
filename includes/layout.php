<?php
/**
 * 作者：任意
 * qq：2908286914
 */

// 保护核心文件，禁止直接访问
if (!defined('IN_SYSTEM')) {
    exit('Access Denied');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title ? $page_title . ' - ' . get_setting('site_name') : get_setting('site_name'); ?></title>
    <?php if ($desc = get_setting('site_description')): ?>
    <meta name="description" content="<?php echo htmlspecialchars($desc); ?>">
    <?php endif; ?>
    <?php if ($keywords = get_setting('site_keywords')): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords); ?>">
    <?php endif; ?>
    <?php if ($favicon = get_setting('site_favicon')): ?>
    <link rel="shortcut icon" href="<?php echo BASE_URL . $favicon; ?>" type="image/x-icon">
    <?php endif; ?>
    <!-- 高速 CDN 镜像 -->
    <link href="https://cdn.staticfile.org/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.staticfile.org/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Unified CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php if (isset($_SESSION['impersonated_by_admin'])): ?>
        <div class="bg-danger text-white py-2 px-4 text-center sticky-top shadow-sm" style="z-index: 2000;">
            <i class="fas fa-user-secret me-2"></i> 您当前正以用户 <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 的身份进行操作
            <a href="<?php echo BASE_URL . get_setting('admin_path', 'admin'); ?>/admin_users.php?switch_back=1" class="btn btn-sm btn-light ms-3 rounded-pill fw-bold">返回管理员后台</a>
        </div>
    <?php endif; ?>
    <div class="wrapper">
        <aside class="sidebar <?php echo ($layout_type === 'admin') ? 'sidebar-dark' : ''; ?>" id="sidebar">
            <div class="sidebar-header">
                <?php if ($logo = get_setting('site_logo')): ?>
                    <img src="<?php echo BASE_URL . $logo; ?>" alt="Logo" class="img-fluid mb-2" style="max-height: 40px;">
                <?php else: ?>
                    <h2><i class="fas fa-shield-halved me-2"></i><?php echo get_setting('site_name'); ?></h2>
                <?php endif; ?>
            </div>
            <ul class="nav-menu">
                <?php foreach (get_sidebar_menu($layout_type) as $menu_item): ?>
                    <?php if (isset($menu_item['type']) && $menu_item['type'] === 'section'): ?>
                        <li class="nav-section"><?php echo $menu_item['title']; ?></li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="<?php echo $menu_item['url']; ?>" 
                               class="nav-link <?php echo ($current_page === $menu_item['id']) ? 'active' : ''; ?> <?php echo $menu_item['class'] ?? ''; ?>">
                                <i class="<?php echo $menu_item['icon']; ?>"></i> 
                                <span><?php echo $menu_item['title']; ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </aside>

        <main class="main-content">
            <header class="admin-navbar">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link d-lg-none me-2 p-0 text-dark" id="sidebarToggle">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                    <h4 class="mb-0"><?php echo $page_title; ?></h4>
                </div>
                <?php if ($layout_type === 'admin'): ?>
                    <div class="user-info d-flex align-items-center d-none d-sm-flex">
                        <span class="me-3 text-muted small">欢迎，<strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
                        <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold">超级管理员</div>
                    </div>
                <?php endif; ?>
            </header>
            
            <div class="container-fluid p-0">
                <?php display_flash_message(); ?>
                <?php echo $content; ?>
            </div>

            <footer class="mt-5 pt-4 border-top text-center text-muted small">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo get_setting('site_name'); ?>. All rights reserved.</p>
                <?php if ($icp = get_setting('site_icp')): ?>
                    <p class="mb-0 mt-1"><?php echo htmlspecialchars($icp); ?></p>
                <?php endif; ?>
                <?php if ($cr = get_setting('site_copyright_no')): ?>
                    <p class="mb-0 mt-1">软著登字第 <?php echo htmlspecialchars($cr); ?> 号</p>
                <?php endif; ?>
                <p class="mt-1">Version <?php echo SYSTEM_VERSION; ?></p>
            </footer>
        </main>
    </div>

    <!-- 高速 CDN 镜像 JS -->
    <script src="https://cdn.staticfile.org/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.staticfile.org/jquery/3.6.0/jquery.min.js"></script>
    <script>
        const CSRF_TOKEN = '<?php echo $csrf_token; ?>';
        $(function() {
            // 移动端侧边栏切换
            $('#sidebarToggle, #sidebarOverlay').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#sidebarOverlay').fadeToggle(300);
            });

            // 点击内容区域关闭侧边栏
            $('.main-content').on('click', function(e) {
                if ($(window).width() < 992 && $('#sidebar').hasClass('active') && !$(e.target).closest('#sidebarToggle').length) {
                    $('#sidebar').removeClass('active');
                    $('#sidebarOverlay').fadeOut(300);
                }
            });

            if (typeof jQuery !== 'undefined') {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php
/**
 * 作者：任意
 * qq：2908286914
 */
?>