<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once 'core/auth.php';

global $pdo;
// 获取软件信息
$software = $pdo->query("SELECT * FROM software LIMIT 1")->fetch();

// 获取最新公告
$articles = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 3")->fetchAll();

// 获取最新更新日志
$updates = $pdo->query("SELECT * FROM software_updates ORDER BY created_at DESC LIMIT 3")->fetchAll();

$query_result = null;
if (isset($_GET['query_domain'])) {
    $domain = trim($_GET['query_domain']);
    $stmt = $pdo->prepare("SELECT l.*, s.name as software_name FROM licenses l JOIN software s ON l.software_id = s.id WHERE l.domain = ? AND l.status = 1");
    $stmt->execute([$domain]);
    $query_result = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_setting('site_name'); ?> - 专业的软件授权管理专家</title>
    <?php if ($desc = get_setting('site_description')): ?>
    <meta name="description" content="<?php echo htmlspecialchars($desc); ?>">
    <?php endif; ?>
    <?php if ($keywords = get_setting('site_keywords')): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords); ?>">
    <?php endif; ?>
    <?php if ($favicon = get_setting('site_favicon')): ?>
    <link rel="shortcut icon" href="<?php echo BASE_URL . $favicon; ?>" type="image/x-icon">
    <?php endif; ?>
    <link href="https://cdn.staticfile.org/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.staticfile.org/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-soft: #eff6ff;
            --secondary: #64748b;
            --dark: #0f172a;
            --accent: #3b82f6;
            --glass: rgba(255, 255, 255, 0.8);
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #ffffff; color: var(--dark); overflow-x: hidden; }
        
        /* Navbar */
        .navbar { background: var(--glass); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(0,0,0,0.05); padding: 0.75rem 0; }
        .navbar-brand { font-weight: 800; font-size: 1.25rem; color: var(--dark) !important; letter-spacing: -0.5px; }
        .nav-link { font-weight: 600; color: var(--secondary) !important; margin: 0 0.75rem; transition: color 0.3s; font-size: 0.95rem; }
        .nav-link:hover { color: var(--primary) !important; }

        /* Hero Section */
        .hero { padding: 160px 0 100px; background: radial-gradient(circle at 100% 0%, #f0f7ff 0%, #ffffff 50%); position: relative; }
        .hero-title { font-weight: 800; font-size: 4rem; line-height: 1.1; letter-spacing: -2px; margin-bottom: 1.5rem; background: linear-gradient(135deg, #1e293b 0%, #3b82f6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-desc { font-size: 1.25rem; color: var(--secondary); max-width: 600px; margin-bottom: 2.5rem; }
        .hero-badge { background: var(--primary-soft); color: var(--primary); padding: 0.5rem 1rem; border-radius: 100px; font-weight: 700; font-size: 0.875rem; display: inline-block; margin-bottom: 1.5rem; }

        /* Feature Cards */
        .card-custom { border: 1px solid rgba(0,0,0,0.05); border-radius: 24px; padding: 2.5rem; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); background: #fff; height: 100%; }
        .card-custom:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); border-color: var(--primary-soft); }
        .icon-wrapper { width: 64px; height: 64px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 2rem; }
        
        /* Query Section */
        .query-section { background: var(--dark); color: #fff; padding: 100px 0; border-radius: 48px; margin: 50px 0; position: relative; overflow: hidden; }
        .query-section::after { content: ''; position: absolute; top: -50%; right: -20%; width: 600px; height: 600px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; filter: blur(100px); }
        .query-input { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 1.25rem 2rem; border-radius: 18px; font-size: 1.1rem; width: 100%; }
        .query-input:focus { background: rgba(255,255,255,0.1); border-color: var(--primary); box-shadow: none; color: #fff; }

        /* List Items */
        .list-group-item { border: none; padding: 1.25rem 0; background: transparent; display: flex; align-items: center; transition: all 0.3s; }
        .list-group-item:hover { transform: translateX(10px); }
        .date-badge { min-width: 100px; font-weight: 700; color: var(--secondary); font-size: 0.875rem; }
        .title-link { color: var(--dark); text-decoration: none; font-weight: 600; flex: 1; }
        .title-link:hover { color: var(--primary); }

        .btn-main { padding: 1rem 2.5rem; border-radius: 16px; font-weight: 700; transition: all 0.3s; }
        .btn-primary-custom { background: var(--primary); color: #fff; border: none; }
        .btn-primary-custom:hover { background: var(--dark); transform: scale(1.02); }

        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
            .query-section { border-radius: 0; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <?php if ($logo = get_setting('site_logo')): ?>
                    <img src="<?php echo BASE_URL . $logo; ?>" alt="Logo" style="max-height: 30px;" class="me-2">
                <?php else: ?>
                    <i class="fas fa-bolt-lightning text-primary me-2"></i>
                <?php endif; ?>
                <?php echo get_setting('site_name'); ?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-link"><a href="#features" class="nav-link">功能特性</a></li>
                    <li class="nav-link"><a href="#query" class="nav-link">授权查询</a></li>
                    <li class="nav-link"><a href="#news" class="nav-link">官方动态</a></li>
                    <li class="nav-link"><a href="#updates" class="nav-link">更新日志</a></li>
                </ul>
                <div class="d-flex gap-3">
                    <?php if (isLoggedIn()): ?>
                        <a href="user/dashboard.php" class="btn btn-main btn-primary-custom">管理控制台</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-main btn-light">登录</a>
                        <a href="register.php" class="btn btn-main btn-primary-custom">立即开始</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <span class="hero-badge">Next-Gen Auth System v<?php echo SYSTEM_VERSION; ?></span>
                    <h1 class="hero-title">为您的软件<br>注入安全灵魂</h1>
                    <p class="hero-desc">全自动化授权管理平台，提供毫秒级验证响应、多重加密防护及完善的代理分销体系，助力开发者专注于产品本身。</p>
                    <div class="d-flex gap-3">
                        <a href="register.php" class="btn btn-main btn-primary-custom shadow-lg">免费开启</a>
                        <a href="#query" class="btn btn-main btn-outline-dark">查询授权</a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="https://img.freepik.com/free-vector/security-analyst-concept-illustration_114360-1996.jpg" class="img-fluid" alt="Security Illustration">
                </div>
            </div>
        </div>
    </header>

    <section id="features" class="py-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold h1 mb-3">核心产品特性</h2>
                <p class="text-secondary">我们为您提供业内领先的授权管理解决方案</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="icon-wrapper bg-primary-soft text-primary"><i class="fas fa-shield-halved"></i></div>
                        <h4 class="fw-bold mb-3">多重加密防护</h4>
                        <p class="text-secondary mb-0">采用 AES-256 加密与动态签名算法，确保授权数据在传输与存储中的绝对安全。</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="icon-wrapper bg-success bg-opacity-10 text-success"><i class="fas fa-users-gear"></i></div>
                        <h4 class="fw-bold mb-3">代理分销体系</h4>
                        <p class="text-secondary mb-0">完善的代理等级与返点系统，支持自定义折扣，让您的产品快速铺开市场。</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="icon-wrapper bg-info bg-opacity-10 text-info"><i class="fas fa-chart-line"></i></div>
                        <h4 class="fw-bold mb-3">数据分析仪表盘</h4>
                        <p class="text-secondary mb-0">实时掌握销售动态、用户活跃度及财务报表，用数据驱动产品迭代决策。</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-5">
        <div class="container py-5">
            <div class="row align-items-center mb-5 pb-5">
                <div class="col-lg-6">
                    <img src="https://img.freepik.com/free-vector/security-concept-illustration_114360-1510.jpg" class="img-fluid rounded-5 shadow-lg" alt="Secure">
                </div>
                <div class="col-lg-5 offset-lg-1">
                    <h2 class="fw-bold mb-4">金融级数据安全保障</h2>
                    <p class="text-secondary lead">我们深知授权数据对开发者的重要性。系统采用多层加密架构，从 API 通讯到数据库存储，每一环节都经过严苛的安全审计。</p>
                    <ul class="list-unstyled mt-4">
                        <li class="mb-3"><i class="fas fa-check-circle text-primary me-2"></i> 动态签名算法，拒绝模拟请求</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-primary me-2"></i> 异地登录提醒，实时监控风险</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-primary me-2"></i> 核心代码混淆，防止逆向分析</li>
                    </ul>
                </div>
            </div>

            <div class="row align-items-center flex-row-reverse">
                <div class="col-lg-6">
                    <img src="https://img.freepik.com/free-vector/setup-analytics-concept-illustration_114360-1438.jpg" class="img-fluid rounded-5 shadow-lg" alt="Analytics">
                </div>
                <div class="col-lg-5">
                    <h2 class="fw-bold mb-4">多维度分销管理系统</h2>
                    <p class="text-secondary lead">不仅是授权管理，更是您的分销增长引擎。内置完善的代理体系，支持自定义等级权重与自动化返佣结算。</p>
                    <div class="row g-3 mt-2">
                        <div class="col-6">
                            <div class="p-3 border rounded-4">
                                <h4 class="fw-bold text-primary mb-1">99%</h4>
                                <span class="small text-muted">分销自动化率</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 border rounded-4">
                                <h4 class="fw-bold text-primary mb-1">0.1s</h4>
                                <span class="small text-muted">验证平均延迟</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container py-5">
            <div class="row g-5">
                <div id="news" class="col-lg-6">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h3 class="fw-bold mb-0">官方公告</h3>
                        <a href="articles.php" class="text-primary text-decoration-none fw-bold small">更多公告 <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                    <div class="list-group list-group-flush bg-transparent">
                        <?php if (empty($articles)): ?>
                            <p class="text-muted">暂无公告数据</p>
                        <?php else: ?>
                            <?php foreach ($articles as $art): ?>
                                <div class="list-group-item">
                                    <span class="date-badge"><?php echo date('Y.m.d', strtotime($art['created_at'])); ?></span>
                                    <a href="article.php?id=<?php echo $art['id']; ?>" class="title-link"><?php echo htmlspecialchars($art['title']); ?></a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="updates" class="col-lg-6">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h3 class="fw-bold mb-0">更新日志</h3>
                        <a href="updates.php" class="text-primary text-decoration-none fw-bold small">所有日志 <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                    <div class="list-group list-group-flush bg-transparent">
                        <?php if (empty($updates)): ?>
                            <p class="text-muted">暂无更新记录</p>
                        <?php else: ?>
                            <?php foreach ($updates as $up): ?>
                                <div class="list-group-item">
                                    <span class="date-badge"><?php echo date('Y.m.d', strtotime($up['created_at'])); ?></span>
                                    <a href="updates.php" class="title-link">发布了版本 v<?php echo htmlspecialchars($up['version']); ?></a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-5 border-top">
        <div class="container py-4 text-center">
            <div class="mb-4">
                <a class="navbar-brand mb-3 d-block" href="#">
                    <?php if ($logo = get_setting('site_logo')): ?>
                        <img src="<?php echo BASE_URL . $logo; ?>" alt="Logo" style="max-height: 40px;" class="mb-2">
                    <?php else: ?>
                        <i class="fas fa-bolt-lightning text-primary me-2"></i>
                    <?php endif; ?>
                    <div class="fw-bold"><?php echo get_setting('site_name'); ?></div>
                </a>
                <p class="text-secondary small">致力于打造全球领先的开发者授权分销生态系统</p>
            </div>
            <div class="text-secondary smaller">
                <p class="mb-1">&copy; <?php echo date('Y'); ?> <?php echo get_setting('site_name'); ?>. All rights reserved. </p>
                <?php if ($icp = get_setting('site_icp')): ?>
                    <p class="mb-1"><?php echo htmlspecialchars($icp); ?></p>
                <?php endif; ?>
                <?php if ($cr = get_setting('site_copyright_no')): ?>
                    <p class="mb-0">软著登字第 <?php echo htmlspecialchars($cr); ?> 号</p>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <script src="https://cdn.staticfile.org/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
