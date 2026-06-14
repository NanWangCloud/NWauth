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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $discount = (float)$_POST['discount'];
            $min_recharge = (float)$_POST['min_recharge'];
            if ($name === '') {
                $error = '等级名称不能为空';
            } else {
                $stmt = $pdo->prepare("INSERT INTO agent_levels (name, discount, min_recharge) VALUES (?, ?, ?)");
                $stmt->execute([$name, $discount, $min_recharge]);
                logAdminAction('添加代理等级', "名称: $name");
                $success = '等级添加成功';
            }
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name'] ?? '');
            $discount = (float)$_POST['discount'];
            $min_recharge = (float)$_POST['min_recharge'];
            if ($name === '') {
                $error = '等级名称不能为空';
            } else {
                $stmt = $pdo->prepare("UPDATE agent_levels SET name = ?, discount = ?, min_recharge = ? WHERE id = ?");
                $stmt->execute([$name, $discount, $min_recharge, $id]);
                logAdminAction('编辑代理等级', "等级ID: $id, 名称: $name");
                $success = '等级更新成功';
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM agent_levels WHERE id = ?");
            $stmt->execute([$id]);
            $success = '等级删除成功';
        }
    }
}
$levels = $pdo->query("SELECT * FROM agent_levels ORDER BY id ASC")->fetchAll();
$page_title = '代理等级管理';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">代理等级配置</h4>
    <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openAddModal()">
        <i class="fas fa-plus me-1"></i> 添加等级
    </button>
</div>
<?php if ($success): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4"><?php echo $error; ?></div>
<?php endif; ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>等级名称</th>
                        <th>拿货折扣</th>
                        <th>最低充值</th>
                        <th class="text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($levels as $lv): ?>
                        <tr>
                            <td>#<?php echo $lv['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($lv['name']); ?></strong></td>
                            <td><span class="badge bg-info bg-opacity-10 text-info"><?php echo $lv['discount'] * 10; ?> 折</span></td>
                            <td>¥ <?php echo number_format($lv['min_recharge'], 2); ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light border" onclick='openEditModal(<?php echo json_encode($lv); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('确定要删除吗？')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $lv['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-light border text-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="levelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">添加等级</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" id="modalAction" value="add">
                    <input type="hidden" name="id" id="modalId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">等级名称</label>
                        <input type="text" name="name" id="modalName" class="form-control" placeholder="如：金牌代理" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">拿货折扣 (0.01-1.00)</label>
                        <input type="number" name="discount" id="modalDiscount" step="0.01" min="0.01" max="1" class="form-control" placeholder="0.80" required>
                        <div class="form-text small">0.80 代表 8 折</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">升级所需最低充值 (元)</label>
                        <input type="number" name="min_recharge" id="modalMinRecharge" step="0.01" class="form-control" placeholder="0.00" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary px-4">确认保存</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = '添加等级';
    document.getElementById('modalAction').value = 'add';
    document.getElementById('modalName').value = '';
    document.getElementById('modalDiscount').value = '1.00';
    document.getElementById('modalMinRecharge').value = '0.00';
    new bootstrap.Modal(document.getElementById('levelModal')).show();
}
function openEditModal(data) {
    document.getElementById('modalTitle').innerText = '编辑等级';
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalId').value = data.id;
    document.getElementById('modalName').value = data.name;
    document.getElementById('modalDiscount').value = data.discount;
    document.getElementById('modalMinRecharge').value = data.min_recharge;
    new bootstrap.Modal(document.getElementById('levelModal')).show();
}
</script>
<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'admin');
/**
 * 作者：任意
 * qq：2908286914
 */
?>