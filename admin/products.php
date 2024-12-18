<?php
require_once 'auth_check.php';
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/components/alert.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: /login.php');
    exit();
}

include 'layout/header.php';

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
        $short_description = $_POST['short_description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $image = $_POST['image'];

        $stmt = $pdo->prepare("INSERT INTO products (name, short_description, price, category_id, image) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $short_description, $price, $category_id, $image])) {
            $product_id = $pdo->lastInsertId();
            
            // Insert translations
            $stmt = $pdo->prepare("INSERT INTO product_translations (product_id, language_code, name, short_description) VALUES (?, ?, ?, ?)");
            // Insert Hungarian translation
            $stmt->execute([$product_id, 'hu', $name, $short_description]);
            // Insert English translation
            $stmt->execute([$product_id, 'en', $name, $short_description]);
            
            header('Location: products.php');
            exit();
        }
    }
    
    // Termék szerkesztése
    if (isset($_POST['edit'])) {
        try {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $short_description = $_POST['short_description'];
            $price = $_POST['price'];
            $category_id = $_POST['category_id'];
            $image = $_POST['image'];
            $is_on_sale = isset($_POST['is_on_sale']) ? 1 : 0;
            $discount_price = $is_on_sale ? $_POST['discount_price'] : null;
            $discount_end_time = !empty($_POST['discount_end_time']) ? $_POST['discount_end_time'] : null;

            // Debug
            error_log("Updating product with data:");
            error_log("ID: " . $id);
            error_log("Name: " . $name);
            error_log("Price: " . $price);
            error_log("Is on sale: " . $is_on_sale);
            error_log("Discount price: " . $discount_price);
            error_log("Discount end time: " . $discount_end_time);

            // Update products table
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?,
                    short_description = ?,
                    price = ?,
                    category_id = ?,
                    image = ?,
                    is_on_sale = ?,
                    discount_price = ?,
                    discount_end_time = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $short_description,
                $price,
                $category_id,
                $image,
                $is_on_sale,
                $discount_price,
                $discount_end_time,
                $id
            ]);

            // Update translations
            $stmt = $pdo->prepare("
                UPDATE product_translations 
                SET name = ?,
                    short_description = ?
                WHERE product_id = ?
            ");
            $stmt->execute([$name, $short_description, $id]);
            
            set_alert("Product updated successfully!", "success");
            header('Location: products.php');
            exit();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            set_alert("Error updating product: " . $e->getMessage(), "error");
            header('Location: products.php');
            exit();
        } catch (Exception $e) {
            error_log("General error: " . $e->getMessage());
            set_alert("An unexpected error occurred: " . $e->getMessage(), "error");
            header('Location: products.php');
            exit();
        }
    }
}

// Kategóriák lekérése a legördülő listához
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

// Termékek lekérése
$stmt = $pdo->query("SELECT p.*, c.name as category_name, pt.short_description 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id
                     LEFT JOIN product_translations pt ON p.id = pt.product_id 
                     AND pt.language_code = 'hu'");
$products = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'layout/sidebar.php'; ?>
        
        <div class="col-md-10" id="content">
            <?php display_alert(); ?>
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
                            <label class="form-label">Short Description</label>
                            <input type="text" name="short_description" class="form-control" maxlength="255" required>
                            <small class="text-muted">A brief description of the product (max 255 characters)</small>
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
                            <th>Short Description</th>
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
                                    <?php if ($product['is_on_sale']): ?>
                                        <?php if (!empty($product['discount_end_time'])): ?>
                                            <i class="text-warning" data-lucide="timer" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top" 
                                               title="Expires: <?php echo date('Y-m-d H:i', strtotime($product['discount_end_time'])); ?>">
                                            </i>
                                        <?php else: ?>
                                            <i class="text-success" data-lucide="check-circle"></i>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <i class="text-danger" data-lucide="x-circle"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['short_description']); ?></td>
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
                            <form method="POST" id="editProductForm">
                                <input type="hidden" name="edit" value="1">
                                <input type="hidden" name="id" id="edit_id">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" id="edit_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Short Description</label>
                                    <input type="text" name="short_description" id="edit_short_description" class="form-control" maxlength="255" required>
                                    <small class="text-muted">A brief description of the product (max 255 characters)</small>
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
                                <div class="discount-fields" style="display: none;">
                                    <div class="mb-3">
                                        <label for="discount_price" class="form-label">Discount Price</label>
                                        <input type="number" step="0.01" class="form-control" id="edit_discount_price" name="discount_price" required>
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
                                    <div class="mb-3" id="discount_end_time_container" style="display: none;">
                                        <label class="form-label">Discount End Time</label>
                                        <input type="datetime-local" name="discount_end_time" id="edit_discount_end_time" class="form-control">
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
                document.getElementById('edit_short_description').value = product.short_description || '';
                document.getElementById('edit_price').value = product.price;
                document.getElementById('edit_category_id').value = product.category_id;
                document.getElementById('edit_image').value = product.image;
                document.getElementById('edit_is_on_sale').checked = product.is_on_sale == 1;
                document.getElementById('edit_discount_price').value = product.discount_price;
                document.getElementById('edit_discount_end_time').value = product.discount_end_time ? product.discount_end_time.slice(0, 16) : '';
                document.getElementById('custom_discount_percentage').value = '';
                toggleDiscountPrice();
                
                var modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            }

            function toggleDiscountPrice() {
                const isOnSale = document.getElementById('edit_is_on_sale').checked;
                const discountContainer = document.querySelector('.discount-fields');
                const discountPriceInput = document.getElementById('edit_discount_price');
                const percentageContainer = document.getElementById('discount_percentage_container');
                const endTimeContainer = document.getElementById('discount_end_time_container');
                
                if (isOnSale) {
                    discountContainer.style.display = 'block';
                    percentageContainer.style.display = 'block';
                    endTimeContainer.style.display = 'block';
                    discountPriceInput.required = true;
                } else {
                    discountContainer.style.display = 'none';
                    percentageContainer.style.display = 'none';
                    endTimeContainer.style.display = 'none';
                    discountPriceInput.required = false;
                    discountPriceInput.value = ''; // Clear the value when unchecked
                }
            }

            function validateForm() {
                const isOnSale = document.getElementById('edit_is_on_sale').checked;
                const discountPrice = document.getElementById('edit_discount_price').value;
                
                if (isOnSale && !discountPrice) {
                    alert('Please set a discount price when the product is on sale!');
                    return false;
                }
                return true;
            }

            document.getElementById('editProductForm').addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return false;
                }
            });

            document.getElementById('edit_is_on_sale').addEventListener('change', toggleDiscountPrice);

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
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>
</body>
</html>