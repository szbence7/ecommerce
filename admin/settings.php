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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['currency'])) {
        $newCurrency = $_POST['currency'];
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'currency'");
        if ($stmt->execute([$newCurrency])) {
            echo '<div class="alert alert-success">Currency settings updated successfully!</div>';
        }
    }
    
    if (isset($_POST['rates'])) {
        foreach ($_POST['rates'] as $currency => $rate) {
            if (is_numeric($rate) && $rate > 0) {
                $stmt = $pdo->prepare("INSERT INTO exchange_rates (currency, rate) VALUES (?, ?) 
                                     ON DUPLICATE KEY UPDATE rate = ?");
                $stmt->execute([$currency, $rate, $rate]);
            }
        }
        echo '<div class="alert alert-success">Exchange rates updated successfully!</div>';
    }
}

// Get current settings
$currentCurrency = getShopCurrency();

// Get exchange rates
$rates = [];
$stmt = $pdo->query("SELECT currency, rate FROM exchange_rates");
while ($row = $stmt->fetch()) {
    $rates[$row['currency']] = $row['rate'];
}

// Example prices for preview
$samplePrices = [10, 99.99, 1000];
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'layout/sidebar.php'; ?>
        
        <div class="col-md-10" id="content">
            <h2>Settings</h2>

            <!-- Currency Settings -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Shop Currency</h5>
                    <p class="text-muted">Select the currency to display prices in your shop. All prices are stored in EUR.</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="currency" class="form-label">Display Currency</label>
                            <select name="currency" id="currency" class="form-select">
                                <option value="EUR" <?php echo $currentCurrency === 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
                                <option value="HUF" <?php echo $currentCurrency === 'HUF' ? 'selected' : ''; ?>>Hungarian Forint (HUF)</option>
                                <option value="USD" <?php echo $currentCurrency === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Currency</button>
                    </form>
                </div>
            </div>

            <!-- Exchange Rates -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Exchange Rates</h5>
                    <p class="text-muted">Set exchange rates relative to EUR (1 EUR equals...)</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">EUR</label>
                            <input type="number" class="form-control" value="1.00" disabled>
                            <small class="text-muted">Base currency - always 1.00</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">HUF</label>
                            <input type="number" name="rates[HUF]" class="form-control" step="0.01" 
                                   value="<?php echo isset($rates['HUF']) ? $rates['HUF'] : '410.00'; ?>" required>
                            <small class="text-muted">Current rate: 1 EUR = <?php echo isset($rates['HUF']) ? $rates['HUF'] : '410.00'; ?> HUF</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">USD</label>
                            <input type="number" name="rates[USD]" class="form-control" step="0.01" 
                                   value="<?php echo isset($rates['USD']) ? $rates['USD'] : '1.08'; ?>" required>
                            <small class="text-muted">Current rate: 1 EUR = <?php echo isset($rates['USD']) ? $rates['USD'] : '1.08'; ?> USD</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Exchange Rates</button>
                    </form>

                    <!-- Price Preview -->
                    <div class="mt-4">
                        <h6>Price Preview</h6>
                        <p class="text-muted">See how prices will appear with current exchange rates:</p>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>EUR (Base)</th>
                                    <th>Selected Currency</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($samplePrices as $price): ?>
                                <tr>
                                    <td><?php echo formatPrice($price, 'EUR'); ?></td>
                                    <td><?php echo formatPrice($price); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>