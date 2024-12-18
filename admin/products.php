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
require_once '../includes/functions.php';

// Termék törlése
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: products.php');
    exit();
}

// Új termék hozzáadása
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $image = $_POST['image'];

        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $category_id, $image]);
        header('Location: products.php');
        exit();
    }
    
    // Termék szerkesztése
    if (isset($_POST['edit'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $image = $_POST['image'];
        $is_on_sale = isset($_POST['is_on_sale']) ? 1 : 0;
        $discount_price = $is_on_sale ? $_POST['discount_price'] : null;

        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image = ?, is_on_sale = ?, discount_price = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $category_id, $image, $is_on_sale, $discount_price, $id]);
        header('Location: products.php');
        exit();
    }
}

// Kategóriák lekérése a legördülő listához
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

// Termékek lekérése
$stmt = $pdo->query("SELECT products.*, categories.name as category_name 
                     FROM products 
                     LEFT JOIN categories ON products.category_id = categories.id");
$products = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'layout/sidebar.php'; ?>
        
        <div class="col-md-10" id="content">
            <h2>Products</h2>

            <!-- Új termék form -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Add New Product</h5>
                    <form method="POST">
                        <input type="hidden" name="add" value="1">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (EUR)</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" name="price" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="text" name="image" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </form>
                </div>
            </div>

            <!-- Termékek listája -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Akció</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <?php if ((int)$product['is_on_sale'] === 1): ?>
                                        <i data-lucide="check" class="text-success" style="width: 18px; height: 18px;"></i>
                                    <?php endif; ?>
                                    <?php 
                                        // Debug output
                                        echo "<!-- Debug: is_on_sale=" . var_export($product['is_on_sale'], true) . " -->";
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['description']); ?></td>
                                <td><?php echo formatPrice($product['price']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><img src="<?php echo htmlspecialchars($product['image']); ?>" height="50"></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">Edit</button>
                                    <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Product</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST">
                                <input type="hidden" name="edit" value="1">
                                <input type="hidden" name="id" id="edit_id">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" id="edit_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" id="edit_description" class="form-control" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price (EUR)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number" name="price" id="edit_price" class="form-control" step="0.01" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_on_sale" id="edit_is_on_sale" class="form-check-input">
                                        <label class="form-check-label" for="edit_is_on_sale">On Sale</label>
                                    </div>
                                </div>
                                <div class="mb-3" id="discount_price_container" style="display: none;">
                                    <label class="form-label">Sale Price (EUR)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number" name="discount_price" id="edit_discount_price" class="form-control" step="0.01">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" id="edit_category_id" class="form-select" required>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Image URL</label>
                                    <input type="text" name="image" id="edit_image" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            function editProduct(product) {
                document.getElementById('edit_id').value = product.id;
                document.getElementById('edit_name').value = product.name;
                document.getElementById('edit_description').value = product.description;
                document.getElementById('edit_price').value = product.price;
                document.getElementById('edit_category_id').value = product.category_id;
                document.getElementById('edit_image').value = product.image;
                document.getElementById('edit_is_on_sale').checked = product.is_on_sale == 1;
                document.getElementById('edit_discount_price').value = product.discount_price || '';
                toggleDiscountPrice();
                
                new bootstrap.Modal(document.getElementById('editModal')).show();
            }

            function toggleDiscountPrice() {
                const isOnSale = document.getElementById('edit_is_on_sale').checked;
                const discountContainer = document.getElementById('discount_price_container');
                const discountInput = document.getElementById('edit_discount_price');
                
                discountContainer.style.display = isOnSale ? 'block' : 'none';
                if (isOnSale) {
                    discountInput.setAttribute('required', 'required');
                } else {
                    discountInput.removeAttribute('required');
                }
            }

            document.getElementById('edit_is_on_sale').addEventListener('change', toggleDiscountPrice);
            </script>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>