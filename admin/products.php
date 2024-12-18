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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Products</h2>
                <button class="btn btn-primary" onclick="toggleNewProductForm()">
                    <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
                    Add New Product
                </button>
            </div>

            <!-- Új termék form -->
            <div class="card mb-4" id="new_product_form" style="display: none;">
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

            <!-- Keresőmező -->
<div class="mb-4">
    <div class="input-group">
        <span class="input-group-text">
            <i data-lucide="search"></i>
        </span>
        <input type="text" id="product_search" class="form-control" placeholder="Search products... (min. 3 characters)">
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
                            <tr data-product-id="<?php echo $product['id']; ?>">
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <?php if ((int)$product['is_on_sale'] === 1): ?>
                                        <i data-lucide="check" class="text-success" style="width: 18px; height: 18px;"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['description']); ?></td>
                                <td>
                                    <?php if ($product['is_on_sale'] == 1 && $product['discount_price'] !== null): ?>
                                        <div class="text-decoration-line-through text-muted small"><?php echo formatPrice($product['price']); ?></div>
                                        <div class="text-danger fw-bold"><?php echo formatPrice($product['discount_price']); ?></div>
                                    <?php else: ?>
                                        <?php echo formatPrice($product['price']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><img src="<?php echo htmlspecialchars($product['image']); ?>" height="50"></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">Edit</button>
                                    <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="no-results" style="display: none;">
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    <i data-lucide="search-x" style="width: 24px; height: 24px; display: inline-block; vertical-align: middle;"></i>
                                    Nincs találat
                                </div>
                            </td>
                        </tr>
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
                                <div class="mb-3" id="discount_percentage_container" style="display: none;">
                                    <label class="form-label">Discount Percentage</label>
                                    <div class="d-flex gap-3">
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check" name="discount_percentage" id="discount_5" value="5" autocomplete="off">
                                            <label class="btn btn-outline-primary" for="discount_5">5%</label>
                                            
                                            <input type="radio" class="btn-check" name="discount_percentage" id="discount_10" value="10" autocomplete="off">
                                            <label class="btn btn-outline-primary" for="discount_10">10%</label>
                                            
                                            <input type="radio" class="btn-check" name="discount_percentage" id="discount_25" value="25" autocomplete="off">
                                            <label class="btn btn-outline-primary" for="discount_25">25%</label>
                                            
                                            <input type="radio" class="btn-check" name="discount_percentage" id="discount_50" value="50" autocomplete="off">
                                            <label class="btn btn-outline-primary" for="discount_50">50%</label>
                                        </div>
                                        <div class="input-group" style="width: 120px;">
                                            <input type="number" id="custom_discount_percentage" class="form-control" step="0.01" min="0" max="100" placeholder="">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3" id="discount_price_container" style="display: none;">
                                    <label class="form-label">Discount Price (EUR)</label>
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
                document.getElementById('edit_discount_price').value = product.discount_price;
                document.getElementById('custom_discount_percentage').value = '';
                toggleDiscountPrice();
                
                var modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            }

            function toggleDiscountPrice() {
                const isOnSale = document.getElementById('edit_is_on_sale').checked;
                const discountContainer = document.getElementById('discount_price_container');
                const percentageContainer = document.getElementById('discount_percentage_container');
                const discountInput = document.getElementById('edit_discount_price');
                const customPercentageInput = document.getElementById('custom_discount_percentage');
                
                if (isOnSale) {
                    discountContainer.style.display = 'block';
                    percentageContainer.style.display = 'block';
                } else {
                    discountContainer.style.display = 'none';
                    percentageContainer.style.display = 'none';
                    discountInput.value = '';
                    customPercentageInput.value = '';
                    // Uncheck all radio buttons
                    document.querySelectorAll('input[name="discount_percentage"]').forEach(radio => {
                        radio.checked = false;
                    });
                }
            }

            // Add event listeners for discount percentage radio buttons
            document.querySelectorAll('input[name="discount_percentage"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const originalPrice = parseFloat(document.getElementById('edit_price').value);
                    const discountPercentage = parseFloat(this.value);
                    document.getElementById('custom_discount_percentage').value = '';
                    if (!isNaN(originalPrice) && !isNaN(discountPercentage)) {
                        const discountedPrice = originalPrice * (1 - discountPercentage / 100);
                        document.getElementById('edit_discount_price').value = discountedPrice.toFixed(2);
                    }
                });
            });

            // Add event listener for custom percentage input
            document.getElementById('custom_discount_percentage').addEventListener('input', function() {
                // Uncheck radio buttons when custom percentage is being used
                document.querySelectorAll('input[name="discount_percentage"]').forEach(radio => {
                    radio.checked = false;
                });
            });

            document.getElementById('custom_discount_percentage').addEventListener('blur', function() {
                const originalPrice = parseFloat(document.getElementById('edit_price').value);
                const customPercentage = parseFloat(this.value);
                if (!isNaN(originalPrice) && !isNaN(customPercentage)) {
                    const discountedPrice = originalPrice * (1 - customPercentage / 100);
                    document.getElementById('edit_discount_price').value = discountedPrice.toFixed(2);
                }
            });

            // Add event listener for the "On Sale" checkbox
            document.getElementById('edit_is_on_sale').addEventListener('change', toggleDiscountPrice);

            function toggleNewProductForm() {
                const form = document.getElementById('new_product_form');
                if (form.style.display === 'none') {
                    form.style.display = 'block';
                    // Scroll to the form
                    form.scrollIntoView({ behavior: 'smooth' });
                } else {
                    form.style.display = 'none';
                }
            }

            // Keresés funkcionalitás
let searchTimeout;
const searchInput = document.getElementById('product_search');
const noResults = document.getElementById('no-results');
                
if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
                        
        if (this.value.length >= 3) {
            searchTimeout = setTimeout(() => {
                searchProducts(this.value);
            }, 300);
        } else if (this.value.length === 0) {
            // Ha üres a mező, minden terméket megjelenítünk
            document.querySelectorAll('table tbody tr').forEach(tr => {
                tr.style.display = '';
            });
            noResults.style.display = 'none';
        }
    });
}

function searchProducts(query) {
    fetch(`search_products.php?search=${encodeURIComponent(query)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(products => {
            const rows = document.querySelectorAll('table tbody tr:not(#no-results)');
                            
            // Minden sort elrejtünk először
            rows.forEach(row => {
                row.style.display = 'none';
            });
                            
            // Csak a találatokat jelenítjük meg
            if (Array.isArray(products) && products.length > 0) {
                products.forEach(product => {
                    if (product && product.id) {
                        const productRow = document.querySelector(`tr[data-product-id="${product.id}"]`);
                        if (productRow) {
                            productRow.style.display = '';
                        }
                    }
                });
                noResults.style.display = 'none';
            } else {
                // Ha nincs találat, megjelenítjük az üzenetet
                noResults.style.display = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Hiba esetén minden sort megjelenítünk
            document.querySelectorAll('table tbody tr:not(#no-results)').forEach(tr => {
                tr.style.display = '';
            });
            noResults.style.display = 'none';
        });
}
            </script>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>