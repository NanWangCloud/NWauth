<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once '../core/auth.php';
requireAdmin();

$error = '';
$success = '';

global $pdo;
$software_id = 1;

// 获取当前软件信息
$stmt = $pdo->prepare("SELECT * FROM software WHERE id = ?");
$stmt->execute([$software_id]);
$sw = $stmt->fetch();

// 处理发布更新
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $new_version = trim($_POST['version'] ?? '');
        $update_url = trim($_POST['update_url'] ?? '');
        $update_log = trim($_POST['update_log'] ?? '');

        if ($new_version === '') {
            $error = '版本号不能为空';
        } elseif (!version_compare($new_version, $sw['version'], '>')) {
            $error = '新版本号 (' . $new_version . ') 必须大于当前版本号 (' . $sw['version'] . ')';
        } else {
            $pdo->beginTransaction();
            try {
                // 1. 更新主表
                $stmt = $pdo->prepare("UPDATE software SET version=?, update_url=?, update_log=? WHERE id=?");
                $stmt->execute([$new_version, $update_url, $update_log, $software_id]);

                // 2. 插入历史记录表
                $stmt = $pdo->prepare("INSERT INTO software_updates (software_id, version, update_url, update_log) VALUES (?, ?, ?, ?)");
                $stmt->execute([$software_id, $new_version, $update_url, $update_log]);

                logAdminAction('发布软件更新', "版本: $new_version");
                $pdo->commit();
                $success = '新版本已发布成功！';
                
                // 重新获取数据
                $stmt = $pdo->prepare("SELECT * FROM software WHERE id = ?");
                $stmt->execute([$software_id]);
                $sw = $stmt->fetch();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = '发布失败: ' . $e->getMessage();
            }
        }
    }
}

// 获取历史更新记录
$stmt = $pdo->prepare("SELECT * FROM software_updates WHERE software_id = ? ORDER BY created_at DESC");
$stmt->execute([$software_id]);
$updates = $stmt->fetchAll();

$page_title = '软件更新管理';

ob_start();
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-upload me-2"></i>发布新版本</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success border-0 small mb-4">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 small mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">当前版本</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($sw['version']); ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">新版本号</label>
                        <input type="text" name="version" class="form-control" placeholder="例如: <?php echo explode('.', $sw['version'])[0] . '.' . (explode('.', $sw['version'])[1] ?? '0') . '.' . ((explode('.', $sw['version'])[2] ?? 0) + 1); ?>" required>
                        <div class="form-text smaller">必须大于当前版本号。</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">更新包直链</label>
                        <input type="url" name="update_url" class="form-control" value="<?php echo htmlspecialchars($sw['update_url']); ?>" placeholder="https://...">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold">更新日志</label>
                        <textarea name="update_log" class="form-control" rows="5" placeholder="请输入本次更新的具体内容..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-pill shadow-sm">
                        <i class="fas fa-paper-plane me-2"></i> 立即发布更新
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-history me-2"></i>历史更新记录</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead>
                            <tr>
                                <th>版本号</th>
                                <th>更新时间</th>
                                <th>更新日志</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($updates)): ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">暂无更新历史</td></tr>
                            <?php else: ?>
                                <?php foreach ($updates as $up): ?>
                                    <tr>
                                        <td><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">v<?php echo htmlspecialchars($up['version']); ?></span></td>
                                        <td class="small text-muted"><?php echo $up['created_at']; ?></td>
                                        <td>
                                            <div class="small text-dark text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($up['update_log']); ?>">
                                                <?php echo htmlspecialchars($up['update_log']); ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'admin');
?>
