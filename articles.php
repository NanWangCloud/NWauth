<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
// articles.php
require_once 'core/auth.php';

global $pdo;
$articles = $pdo->query("SELECT a.*, u.username as author FROM articles a JOIN users u ON a.author_id = u.id ORDER BY a.created_at DESC")->fetchAll();

$page_title = '公告与文章 - 授权管理系统';

ob_start();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">公告与文章</h4>
        <p class="text-muted small mb-0">获取最新的系统动态和软件更新资讯</p>
    </div>
    <div class="text-primary fs-4">
        <i class="fas fa-bullhorn"></i>
    </div>
</div>

<?php if (empty($articles)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-newspaper text-muted mb-3" style="font-size: 3rem;"></i>
            <h5 class="text-muted">暂无公告文章</h5>
            <p class="text-muted mb-0">当有新公告发布时，您将在这里看到它们。</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($articles as $article): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden article-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h4 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($article['title']); ?></h4>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill small fw-bold">
                                <?php echo htmlspecialchars($article['category']); ?>
                            </span>
                        </div>
                        
                        <div class="d-flex align-items-center text-muted small mb-4">
                            <span class="me-3"><i class="far fa-calendar-alt me-1"></i> <?php echo date('Y-m-d H:i', strtotime($article['created_at'])); ?></span>
                            <span><i class="far fa-user me-1"></i> <?php echo htmlspecialchars($article['author']); ?></span>
                        </div>

                        <div class="article-content text-muted lh-lg">
                            <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.article-card {
    border-left: 4px solid #4361ee !important;
}
.article-content {
    font-size: 0.95rem;
}
</style>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'user');

/**
 * 作者：任意
 * qq：2908286914
 */
?>