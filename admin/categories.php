<?php
require_once 'auth_check.php';
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: /login.php');
    exit();
}

include 'layout/header.php';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' && !empty($_POST['name'])) {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$_POST['name']]);
        } elseif ($_POST['action'] == 'edit' && !empty($_POST['name']) && !empty($_POST['id'])) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['id']]);
        } elseif ($_POST['action'] == 'delete' && !empty($_POST['id'])) {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }
    }
}

// Get all categories with product counts
$stmt = $pdo->query("SELECT c.*, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id 
                     GROUP BY c.id 
                     ORDER BY c.id DESC");
$categories = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'layout/sidebar.php'; ?>
        
        <div class="col-md-10" id="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Categories</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    Add New Category
                </button>
            </div>

            <!-- Categories Table -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo $category['product_count']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')">
                                        Edit
                                    </button>
                                    <?php if ($category['product_count'] == 0): ?>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                            Delete
                                        </button>
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_category_id">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" id="edit_category_name" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editCategory(id, name) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'layout/footer.php'; ?>