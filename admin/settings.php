<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/language.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: /ecommerce/login.php');
    exit();
}

// Load currency configuration
$currencyConfigPath = __DIR__ . '/../config/currencies.json';
if (!file_exists($currencyConfigPath)) {
    die('Currency configuration file not found');
}

$currencyConfig = json_decode(file_get_contents($currencyConfigPath), true);
if (!$currencyConfig) {
    die('Error loading currency configuration');
}

// Get current settings
$currentCurrency = getShopCurrency();
$currentLanguage = getCurrentLanguage();

// Load exchange rates
$rates = [];
if (file_exists('../config/rates.json')) {
    $rates = json_decode(file_get_contents('../config/rates.json'), true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['currency'])) {
        $newCurrency = $_POST['currency'];
        // Verify currency exists in config
        if (isset($currencyConfig['currencies'][$newCurrency])) {
            // Update in database
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'currency'");
            $stmt->execute([$newCurrency]);
            
            // Update in session
            $_SESSION['currency'] = $newCurrency;
            
            echo '<div class="alert alert-success">' . __t('admin.settings.success') . '</div>';
        }
    }
    
    if (isset($_POST['default_language'])) {
        $newLanguage = $_POST['default_language'];
        
        // First, remove default flag from all languages
        $stmt = $pdo->prepare("UPDATE languages SET is_default = 0");
        $stmt->execute();
        
        // Set the new default language
        $stmt = $pdo->prepare("UPDATE languages SET is_default = 1 WHERE code = ?");
        if ($stmt->execute([$newLanguage])) {
            // Clear any existing language from session to force reload from DB
            unset($_SESSION['language']);
            
            // Get the language again (will get the new default)
            $currentLanguage = getCurrentLanguage();
            
            echo '<div class="alert alert-success">' . __t('admin.settings.success') . '</div>';
        }
    }

    if (isset($_POST['rates'])) {
        $rates = $_POST['rates'];
        file_put_contents('../config/rates.json', json_encode($rates, JSON_PRETTY_PRINT));
        echo '<div class="alert alert-success">' . __t('admin.settings.success') . '</div>';
    }
}

include 'layout/header.php';

// Get active tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Get available languages
$languages = getAvailableLanguages();
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'layout/sidebar.php'; ?>
        
        <div class="col-md-10" id="content">
            <h2><?= __t('admin.settings') ?></h2>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?>" href="?tab=general">
                        <?= __t('admin.settings.general') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'currency' ? 'active' : '' ?>" href="?tab=currency">
                        <?= __t('admin.settings.currency') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'style' ? 'active' : '' ?>" href="?tab=style">
                        <?= __t('admin.settings.style') ?>
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <?php if ($activeTab === 'general'): ?>
                <!-- General Settings Tab -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><?= __t('admin.settings.general') ?></h5>
                        <p class="text-muted"><?= __t('admin.settings.language') ?></p>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="default_language" class="form-label"><?= __t('admin.settings.language.default') ?></label>
                                <select class="form-select" id="default_language" name="default_language">
                                    <?php foreach ($languages as $lang): ?>
                                        <option value="<?= htmlspecialchars($lang['code']) ?>" 
                                                <?= $lang['is_default'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($lang['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= __t('admin.settings.save') ?></button>
                        </form>
                    </div>
                </div>

            <?php elseif ($activeTab === 'currency'): ?>
                <!-- Currency Settings Tab -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><?= __t('admin.settings.currency') ?></h5>
                        <p class="text-muted"><?= __t('admin.settings.currency.select') ?></p>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="currency" class="form-label"><?= __t('admin.settings.currency.display') ?></label>
                                <select name="currency" id="currency" class="form-select">
                                    <?php foreach ($currencyConfig['currencies'] as $code => $currency): ?>
                                        <option value="<?= $code ?>" <?= $currentCurrency === $code ? 'selected' : '' ?>>
                                            <?= $currency['name'] ?> (<?= $currency['symbol'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= __t('admin.settings.save') ?></button>
                        </form>
                    </div>
                </div>

                <!-- Exchange Rates -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><?= __t('admin.settings.exchange_rates') ?></h5>
                        <p class="text-muted"><?= __t('admin.settings.exchange_rates.relative') ?></p>
                        
                        <form method="POST">
                            <?php foreach ($currencyConfig['currencies'] as $code => $currency): ?>
                                <?php if ($code !== $currencyConfig['base_currency']): ?>
                                    <div class="mb-3">
                                        <label class="form-label"><?= $currency['name'] ?> (<?= $currency['symbol'] ?>)</label>
                                        <input type="number" name="rates[<?= $code ?>]" class="form-control" step="0.001" 
                                               value="<?= number_format(isset($rates[$code]) ? $rates[$code] : $currency['default_rate'], 3) ?>" required>
                                        <small class="text-muted"><?= __t('admin.settings.exchange_rates.current') ?>: 1 <?= $currencyConfig['base_currency'] ?> = <?= number_format(isset($rates[$code]) ? $rates[$code] : $currency['default_rate'], 3) ?> <?= $code ?></small>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <button type="submit" class="btn btn-primary"><?= __t('admin.settings.save') ?></button>
                        </form>

                        <!-- Price Preview -->
                        <div class="mt-4">
                            <h6><?= __t('admin.settings.price_preview') ?></h6>
                            <p class="text-muted"><?= __t('admin.settings.price_preview.description') ?></p>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th><?= $currencyConfig['base_currency'] ?> (<?= __t('admin.settings.base_currency') ?>)</th>
                                        <th><?= __t('admin.settings.selected_currency') ?></th>
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

            <?php elseif ($activeTab === 'style'): ?>
                <!-- Style Settings Tab -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><?= __t('admin.settings.style') ?></h5>
                        <p class="text-muted"><?= __t('admin.settings.style.description') ?></p>
                        <!-- Style settings will be added here -->
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>