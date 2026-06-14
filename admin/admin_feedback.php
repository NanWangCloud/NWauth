<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once '../core/auth.php';
requireAdmin();

$success = '';
$error = '';

global $pdo;

// 处理回复
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $id = (int)$_POST['feedback_id'];
        $reply = trim($_POST['reply'] ?? '');
        
        if ($reply !== '') {
            $stmt = $pdo->prepare("UPDATE feedback SET reply_content = ?, status = 1 WHERE id = ?");
            $stmt->execute([$reply, $id]);
            logAdminAction('回复反馈', "反馈ID: $id");
            $success = '回复成功！';
        }
    }
}

// 获取反馈列表
$feedbacks = $pdo->query("
    SELECT f.*, u.username 
    FROM feedback f 
    JOIN users u ON f.user_id = u.id 
    ORDER BY f.status ASC, f.created_at DESC
")->fetchAll();

$page_title = '反馈处理';

ob_start();
?>

<?php if ($success): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4">
        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php if (empty($feedbacks)): ?>
        <div class="col-12 text-center py-5">
            <i class="fas fa-comment-slash text-muted mb-3" style="font-size: 3rem;"></i>
            <h5 class="text-muted">暂无待处理反馈</h5>
        </div>
    <?php else: ?>
        <?php foreach ($feedbacks as $fb): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge <?php echo $fb['status'] == 1 ? 'bg-success' : 'bg-warning text-dark'; ?> rounded-pill px-3 py-1 fw-bold me-2">
                                <?php echo $fb['status'] == 1 ? '已处理' : '待处理'; ?>
                            </span>
                            <span class="text-muted small">
                                <i class="far fa-user me-1"></i> <?php echo htmlspecialchars($fb['username']); ?> 
                                <i class="far fa-clock ms-3 me-1"></i> <?php echo $fb['created_at']; ?>
                            </span>
                        </div>
                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($fb['title']); ?></h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="p-3 bg-light rounded-3 mb-4 border-start border-4 border-primary">
                            <p class="mb-0 text-dark lh-base"><?php echo nl2br(htmlspecialchars($fb['content'])); ?></p>
                        </div>

                        <?php if ($fb['status'] == 1): ?>
                            <div class="p-3 bg-success bg-opacity-10 rounded-3 border-start border-4 border-success">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-reply text-success me-2"></i>
                                    <span class="fw-bold text-success small">您的回复:</span>
                                </div>
                                <p class="mb-0 text-dark small"><?php echo nl2br(htmlspecialchars($fb['reply_content'])); ?></p>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="feedback_id" value="<?php echo $fb['id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">回复内容</label>
                                    <textarea name="reply" class="form-control" rows="3" placeholder="在此输入您的回复..." required></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                                        <i class="fas fa-paper-plane me-2"></i> 发送回复
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'admin');

/**
 * 作者：任意
 * qq：2908286914
 */
?>