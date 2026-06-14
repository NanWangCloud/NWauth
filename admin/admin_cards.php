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
$generated_cards = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $type = $_POST['type'] ?? 'balance';
        $value = (float)($_POST['value'] ?? 0);
        $software_id = 1; // 锁定单软件 ID
        $count = (int)($_POST['count'] ?? 1);

        if ($value <= 0 || $value > 10000) {
            $error = '面值非法 (0-10,000)！';
        } elseif ($count <= 0 || $count > 100) {
            $error = '单次生成数量必须在 1-100 之间！';
        } else {
            global $pdo;
            $pdo->beginTransaction();
            try {
                for ($i = 0; $i < $count; $i++) {
                    $card_key = generateKey('CDK');
                    $stmt = $pdo->prepare("INSERT INTO cards (card_key, type, value, software_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$card_key, $type, $value, ($type === 'software_time' ? $software_id : null)]);
                    logAdminAction('批量生成卡密', "类型: $type, 面值: $value, 数量: $count");
                    $generated_cards[] = $card_key;
                }
                $pdo->commit();
                $success = "成功生成 " . count($generated_cards) . " 张卡密！";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = '生成失败: ' . $e->getMessage();
            }
        }
    }
}

// 获取软件列表
global $pdo;
$software_list = $pdo->query("SELECT id, name FROM software")->fetchAll();

$page_title = '卡密生成';

ob_start();
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0">批量生成卡密</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 small mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success border-0 small mb-4">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">卡密类型</label>
                        <select name="type" id="card_type" class="form-select" onchange="toggleSoftwareSelect()">
                            <option value="balance">充值余额</option>
                            <option value="software_time">软件授权时间</option>
                        </select>
                    </div>

                    <div class="mb-3" id="software_group" style="display:none;">
                        <label class="form-label small fw-bold">目标软件</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($software_list[0]['name'] ?? '默认应用'); ?>" readonly>
                        <div class="form-text smaller">独立站模式：卡密将自动绑定至主软件。</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">面值 (金额/天数)</label>
                            <input type="number" name="value" step="0.01" class="form-control" placeholder="100" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">生成数量</label>
                            <input type="number" name="count" class="form-control" value="1" min="1" max="100" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                        <i class="fas fa-magic me-2"></i> 立即批量生成
                    </button>
                </form>

                <?php if (!empty($generated_cards)): ?>
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0">生成结果:</h6>
                            <button class="btn btn-sm btn-outline-secondary border-0" onclick="copyResults()">
                                <i class="far fa-copy"></i> 全部复制
                            </button>
                        </div>
                        <textarea id="results_box" class="form-control bg-light border-0 small" rows="8" readonly><?php echo implode("\n", $generated_cards); ?></textarea>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0">最近生成的卡密</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead>
                            <tr>
                                <th>卡密 Key</th>
                                <th>类型</th>
                                <th>面值</th>
                                <th>状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_cards = $pdo->query("
                                SELECT c.*, s.name as software_name 
                                FROM cards c 
                                LEFT JOIN software s ON c.software_id = s.id 
                                ORDER BY c.id DESC LIMIT 10
                            ")->fetchAll();
                            foreach ($recent_cards as $c):
                            ?>
                                <tr>
                                    <td><code class="text-dark fw-bold"><?php echo $c['card_key']; ?></code></td>
                                    <td>
                                        <span class="small text-muted">
                                            <?php echo $c['type'] == 'balance' ? '余额' : '授权 (' . htmlspecialchars($c['software_name']) . ')'; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo $c['value']; ?><?php echo $c['type'] == 'balance' ? '元' : '天'; ?></strong></td>
                                    <td>
                                        <?php if ($c['is_used']): ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2 py-1">已使用</span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">未使用</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSoftwareSelect() {
    var type = document.getElementById('card_type').value;
    var group = document.getElementById('software_group');
    group.style.display = (type === 'software_time') ? 'block' : 'none';
}

function copyResults() {
    var copyText = document.getElementById("results_box");
    copyText.select();
    document.execCommand("copy");
    alert("已复制到剪贴板");
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