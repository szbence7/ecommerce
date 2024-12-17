<?php
require_once 'auth_check.php';
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Load currency configuration
$currencyConfig = json_decode(file_get_contents('../config/currencies.json'), true);
if (!$currencyConfig) {
    die('Error loading currency configuration');
}

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: /login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['currency'])) {
        $newCurrency = $_POST['currency'];
        // Verify currency exists in config
        if (isset($currencyConfig['currencies'][$newCurrency])) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'currency'");
            if ($stmt->execute([$newCurrency])) {
                echo '<div class="alert alert-success">Currency settings updated successfully!</div>';
            }
        }
    }
    
    if (isset($_POST['rates'])) {
        foreach ($_POST['rates'] as $currency => $rate) {
            // Only update rates for currencies in config
            if (isset($currencyConfig['currencies'][$currency]) && is_numeric($rate) && $rate > 0) {
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

include 'layout/header.php';

// Get active tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'currency';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'layout/sidebar.php'; ?>
        
        <div class="col-md-10" id="content">
            <h2>Settings</h2>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'currency' ? 'active' : '' ?>" href="?tab=currency">
                        Currency Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'style' ? 'active' : '' ?>" href="?tab=style">
                        Style Settings
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <?php if ($activeTab === 'currency'): ?>
                    <!-- Currency Settings Tab -->
                    <div class="tab-pane active">
                        <!-- Currency Settings -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="card-title">Shop Currency</h5>
                                <p class="text-muted">Select the currency to display prices in your shop. All prices are stored in <?= $currencyConfig['base_currency'] ?>.</p>
                                
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="currency" class="form-label">Display Currency</label>
                                        <select name="currency" id="currency" class="form-select">
                                            <?php foreach ($currencyConfig['currencies'] as $code => $currency): ?>
                                                <option value="<?= $code ?>" <?= $currentCurrency === $code ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($currency['name']) ?> (<?= $currency['symbol'] ?>)
                                                </option>
                                            <?php endforeach; ?>
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
                                <p class="text-muted">Set exchange rates relative to <?= $currencyConfig['base_currency'] ?> (1 <?= $currencyConfig['base_currency'] ?> equals...)</p>
                                
                                <form method="POST">
                                    <?php foreach ($currencyConfig['currencies'] as $code => $currency): ?>
                                        <?php if ($code !== $currencyConfig['base_currency']): ?>
                                            <div class="mb-3">
                                                <label class="form-label"><?= $currency['name'] ?> (<?= $currency['symbol'] ?>)</label>
                                                <input type="number" name="rates[<?= $code ?>]" class="form-control" step="0.001" 
                                                       value="<?= number_format(isset($rates[$code]) ? $rates[$code] : $currency['default_rate'], 3) ?>" required>
                                                <small class="text-muted">Current rate: 1 <?= $currencyConfig['base_currency'] ?> = <?= number_format(isset($rates[$code]) ? $rates[$code] : $currency['default_rate'], 3) ?> <?= $code ?></small>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <button type="submit" class="btn btn-primary">Save Exchange Rates</button>
                                </form>

                                <!-- Price Preview -->
                                <div class="mt-4">
                                    <h6>Price Preview</h6>
                                    <p class="text-muted">See how prices will appear with current exchange rates:</p>
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th><?= $currencyConfig['base_currency'] ?> (Base)</th>
                                                <th>Selected Currency</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $samplePrices = [10, 99.99, 1000];
                                            foreach ($samplePrices as $price): 
                                            ?>
                                            <tr>
                                                <td><?php echo formatPrice($price, $currencyConfig['base_currency']); ?></td>
                                                <td><?php echo formatPrice($price); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($activeTab === 'style'): ?>
                    <!-- Style Settings Tab -->
                    <div class="tab-pane active">
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="card-title">Style Settings</h5>
                                <p class="text-muted">Customize the appearance of your shop.</p>
                                <!-- Style settings will be added here -->
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>