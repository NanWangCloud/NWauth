<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once 'core/auth.php';

global $pdo;

// 获取所有软件的历史更新记录
$stmt = $pdo->query("
    SELECT u.*, s.name as software_name 
    FROM software_updates u 
    JOIN software s ON u.software_id = s.id 
    ORDER BY u.created_at DESC 
    LIMIT 50
");
$updates = $stmt->fetchAll();

$page_title = '软件更新日志';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?> - <?php echo get_setting('site_name'); ?></title>
    <!-- 高速 CDN 镜像 -->
    <link href="https://cdn.staticfile.org/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.staticfile.org/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-gradient: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #1e293b; }
        .timeline { position: relative; padding: 2rem 0; }
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            width: 2px;
            height: 100%;
            background: #e2e8f0;
            transform: translateX(-50%);
        }
        .timeline-item { margin-bottom: 3rem; position: relative; width: 100%; }
        .timeline-dot {
            width: 20px;
            height: 20px;
            background: var(--primary);
            border: 4px solid #fff;
            border-radius: 50%;
            position: absolute;
            left: 50%;
            top: 0;
            transform: translateX(-50%);
            z-index: 1;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .timeline-content {
            width: 45%;
            padding: 1.5rem;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            position: relative;
        }
        .timeline-item:nth-child(even) .timeline-content { margin-left: auto; }
        .timeline-item:nth-child(odd) .timeline-content { text-align: right; }
        .update-date { font-size: 0.85rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem; display: block; }
        .update-log-box { background: #fbfcfe; padding: 1rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 1rem; text-align: left; }
        
        @media (max-width: 768px) {
            .timeline::before { left: 20px; }
            .timeline-dot { left: 20px; transform: none; }
            .timeline-content { width: calc(100% - 50px); margin-left: 50px !important; text-align: left !important; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-shield-halved me-2"></i><?php echo get_setting('site_name'); ?>
            </a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-link text-decoration-none text-dark me-2">首页</a>
                <a href="login.php" class="btn btn-primary btn-sm rounded-pill px-4">登录</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold">版本更新历程</h2>
            <p class="text-muted">追踪我们的每一次进步与优化</p>
        </div>

        <div class="timeline">
            <?php if (empty($updates)): ?>
                <div class="text-center py-5 text-muted">暂无更新记录</div>
            <?php else: ?>
                <?php foreach ($updates as $up): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <span class="update-date"><?php echo date('Y-m-d H:i', strtotime($up['created_at'])); ?></span>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($up['software_name']); ?></h5>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 mb-2">v<?php echo htmlspecialchars($up['version']); ?></span>
                            <div class="update-log-box">
                                <div class="small text-muted mb-1 fw-bold">更新内容：</div>
                                <div class="small text-dark" style="white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($up['update_log'])); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo get_setting('site_name'); ?>. 保留所有权利.</p>
        </div>
    </footer>
</body>
</html>
