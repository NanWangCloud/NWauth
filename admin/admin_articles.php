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

// 处理发布/编辑
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = trim($_POST['category'] ?? '官方公告');

        if (!validate_input($title, 'string', 2, 100)) {
            $error = '标题长度需在 2-100 字之间！';
        } elseif (!validate_input($content, 'string', 5, 10000)) {
            $error = '内容过短或过长 (5-10,000)！';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE articles SET title=?, content=?, category=? WHERE id=?");
                $stmt->execute([$title, $content, $category, $id]);
                $success = '文章更新成功！';
            } else {
                $stmt = $pdo->prepare("INSERT INTO articles (title, content, author_id, category) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $_SESSION['admin_id'], $category]);
                $success = '文章发布成功！';
            }
        }
    }
}

// 处理删除
if (isset($_GET['delete'])) {
    if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([(int)$_GET['delete']]);
        $success = '文章已删除。';
    }
}

// 获取文章列表
$articles = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC")->fetchAll();

// 获取编辑项
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_item = $stmt->fetch();
}

$page_title = '文章管理';

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
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0"><?php echo $edit_item ? '编辑文章' : '发布新公告'; ?></h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_item['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">文章标题</label>
                        <input type="text" name="title" class="form-control" value="<?php echo $edit_item ? htmlspecialchars($edit_item['title']) : ''; ?>" placeholder="输入引人注目的标题" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">分类</label>
                        <select name="category" class="form-select">
                            <option value="官方公告" <?php echo ($edit_item && $edit_item['category'] == '官方公告') ? 'selected' : ''; ?>>官方公告</option>
                            <option value="技术文档" <?php echo ($edit_item && $edit_item['category'] == '技术文档') ? 'selected' : ''; ?>>技术文档</option>
                            <option value="更新日志" <?php echo ($edit_item && $edit_item['category'] == '更新日志') ? 'selected' : ''; ?>>更新日志</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold">正文内容</label>
                        <textarea name="content" class="form-control" rows="10" placeholder="在此输入公告详细内容..." required><?php echo $edit_item ? htmlspecialchars($edit_item['content']) : ''; ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary fw-bold py-2 shadow-sm">
                            <i class="fas fa-paper-plane me-2"></i> <?php echo $edit_item ? '保存更改' : '立即发布'; ?>
                        </button>
                        <?php if ($edit_item): ?>
                            <a href="admin_articles.php" class="btn btn-light fw-bold text-muted">取消编辑</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0">已发布文章</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead>
                            <tr>
                                <th>标题</th>
                                <th>分类</th>
                                <th>发布日期</th>
                                <th class="text-end">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td data-label="标题">
                                        <div class="fw-bold text-dark text-truncate ms-auto ms-md-0" style="max-width: 250px;">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </div>
                                    </td>
                                    <td data-label="分类">
                                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-1 fw-bold small">
                                            <?php echo htmlspecialchars($article['category']); ?>
                                        </span>
                                    </td>
                                    <td data-label="发布日期" class="text-muted small">
                                        <?php echo date('Y-m-d', strtotime($article['created_at'])); ?>
                                    </td>
                                    <td data-label="操作" class="text-end">
                                        <div class="btn-group">
                                            <a href="admin_articles.php?edit=<?php echo $article['id']; ?>" class="btn btn-sm btn-light border">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="admin_articles.php?delete=<?php echo $article['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('确定要删除这篇文章吗？')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
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

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'admin');

/**
 * 作者：任意
 * qq：2908286914
 */
?>