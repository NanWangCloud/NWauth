<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
// user/feedback.php
require_once '../core/auth.php';
requireLogin();

if (!hasActiveLicense()) {
    set_flash_message('danger', '只有拥有活动授权的用户才能提交和查看反馈建议。');
    redirect('user/dashboard.php');
}

$error = '';
$success = '';

global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (!validate_input($title, 'string', 2, 100)) {
            $error = '标题长度需在 2-100 字之间！';
        } elseif (!validate_input($content, 'string', 5, 1000)) {
            $error = '内容长度需在 5-1000 字之间！';
        } else {
            $stmt = $pdo->prepare("INSERT INTO feedback (user_id, title, content) VALUES (?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $title, $content])) {
                set_flash_message('success', '反馈已提交，我们会尽快回复您！');
                redirect('user/feedback.php');
            } else {
                $error = '提交失败，请重试。';
            }
        }
    }
}

$my_feedback = $pdo->prepare("SELECT * FROM feedback WHERE user_id = ? ORDER BY created_at DESC");
$my_feedback->execute([$_SESSION['user_id']]);
$feedbacks = $my_feedback->fetchAll();

$page_title = '用户反馈 - 授权管理系统';

ob_start();
?>

<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fas fa-comment-dots me-2"></i> 提交反馈
                </h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-4">如果您在使用过程中遇到任何问题，或有任何好的建议，请告诉我们。</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-4">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">反馈标题</label>
                        <input type="text" name="title" class="form-control" placeholder="简述您的问题或需求" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">详细描述</label>
                        <textarea name="content" class="form-control" placeholder="请详细描述您的反馈内容，以便我们更好地为您服务..." rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold w-100">
                        <i class="fas fa-paper-plane me-2"></i> 提交反馈
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="d-flex align-items-center justify-content-between mb-3 px-1">
            <h5 class="fw-bold mb-0">反馈历史</h5>
            <span class="badge bg-light text-muted border"><?php echo count($feedbacks); ?> 条记录</span>
        </div>

        <?php if (empty($feedbacks)): ?>
            <div class="card border-0 shadow-sm bg-light text-center py-5">
                <div class="card-body">
                    <i class="fas fa-history text-muted mb-3" style="font-size: 2.5rem;"></i>
                    <h6 class="text-muted">暂无反馈记录</h6>
                </div>
            </div>
        <?php else: ?>
            <div class="feedback-list">
                <?php foreach ($feedbacks as $fb): ?>
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($fb['title']); ?></h6>
                                <span class="badge <?php echo $fb['status'] == 1 ? 'bg-success' : 'bg-warning text-dark'; ?> rounded-pill px-2 py-1 small">
                                    <?php echo $fb['status'] == 1 ? '已回复' : '待处理'; ?>
                                </span>
                            </div>
                            <div class="text-muted small mb-3">
                                <i class="far fa-clock me-1"></i> <?php echo date('Y-m-d H:i', strtotime($fb['created_at'])); ?>
                            </div>
                            <p class="text-muted small mb-0 lh-base">
                                <?php echo nl2br(htmlspecialchars($fb['content'])); ?>
                            </p>

                            <?php if ($fb['status'] == 1 && $fb['reply_content']): ?>
                                <div class="mt-3 p-3 bg-light rounded-3 border-start border-primary border-4">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="fas fa-reply text-primary me-2"></i>
                                        <span class="fw-bold small text-primary">管理员回复:</span>
                                    </div>
                                    <p class="small mb-0 text-dark">
                                        <?php echo nl2br(htmlspecialchars($fb['reply_content'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'user');

/**
 * 作者：任意
 * qq：2908286914
 */
?>