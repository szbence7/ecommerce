<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/language.php';
require_once '../includes/components/alert.php';

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
            set_alert(__t('admin.settings.success', 'admin'), 'success');
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
            
            set_alert(__t('admin.settings.success', 'admin'), 'success');
        }
    }

    if (isset($_POST['rates'])) {
        $rates = $_POST['rates'];
        file_put_contents('../config/rates.json', json_encode($rates, JSON_PRETTY_PRINT));
        set_alert(__t('admin.settings.success', 'admin'), 'success');
    }
}

include 'layout/header.php';

// Get active tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Get available languages
$languages = getAvailableLanguages();

// Get translations for dictionary tab
if ($activeTab === 'dictionary') {
    $stmt = $pdo->prepare("SELECT * FROM translations ORDER BY context, translation_key, language_code");
    $stmt->execute();
    $translations = $stmt->fetchAll();
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'layout/sidebar.php'; ?>
        
        <div class="col-md-10" id="content">
            <?php display_alert(); ?>
            
            <h2><?= __t('admin.settings', 'admin') ?></h2>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?>" href="?tab=general">
                        <?= __t('admin.settings.general', 'admin') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'currency' ? 'active' : '' ?>" href="?tab=currency">
                        <?= __t('admin.settings.currency', 'admin') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'style' ? 'active' : '' ?>" href="?tab=style">
                        <?= __t('admin.settings.style', 'admin') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'dictionary' ? 'active' : '' ?>" href="?tab=dictionary">
                        <?= __t('admin.settings.dictionary', 'admin') ?>
                    </a>
                </li>
            </ul>

            <?php if ($activeTab === 'general'): ?>
                <!-- General Settings Tab -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><?= __t('admin.settings.language', 'admin') ?></h5>
                        <form method="POST" class="mt-4">
                            <div class="mb-3">
                                <label for="default_language" class="form-label"><?= __t('admin.settings.default_language', 'admin') ?></label>
                                <select name="default_language" id="default_language" class="form-select">
                                    <?php foreach ($languages as $lang): ?>
                                        <option value="<?= $lang['code'] ?>" <?= $lang['code'] === $currentLanguage ? 'selected' : '' ?>>
                                            <?= $lang['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= __t('admin.settings.save', 'admin') ?></button>
                        </form>
                    </div>
                </div>

            <?php elseif ($activeTab === 'currency'): ?>
                <!-- Currency Settings Tab -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><?= __t('admin.settings.currency', 'admin') ?></h5>
                        <form method="POST" class="mt-4">
                            <div class="mb-3">
                                <label for="currency" class="form-label"><?= __t('admin.settings.shop_currency', 'admin') ?></label>
                                <select name="currency" id="currency" class="form-select">
                                    <?php foreach ($currencyConfig['currencies'] as $code => $currency): ?>
                                        <option value="<?= $code ?>" <?= $code === $currentCurrency ? 'selected' : '' ?>>
                                            <?= $currency['name'] ?> (<?= $code ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= __t('admin.settings.save', 'admin') ?></button>
                        </form>

                        <h5 class="card-title mt-4"><?= __t('admin.settings.exchange_rates', 'admin') ?></h5>
                        <form method="POST" class="mt-4">
                            <?php foreach ($currencyConfig['currencies'] as $code => $currency): ?>
                                <?php if ($code !== $currentCurrency): ?>
                                    <div class="mb-3">
                                        <label for="rate_<?= $code ?>" class="form-label">
                                            1 <?= $currentCurrency ?> = 
                                            <input type="number" 
                                                   step="0.0001" 
                                                   name="rates[<?= $code ?>]" 
                                                   id="rate_<?= $code ?>" 
                                                   value="<?= isset($rates[$code]) ? $rates[$code] : '1' ?>" 
                                                   class="form-control d-inline-block" 
                                                   style="width: 120px;">
                                            <?= $code ?>
                                        </label>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <button type="submit" class="btn btn-primary"><?= __t('admin.settings.save', 'admin') ?></button>
                        </form>
                    </div>
                </div>

            <?php elseif ($activeTab === 'style'): ?>
                <!-- Style Settings Tab -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><?= __t('admin.settings.style', 'admin') ?></h5>
                        <!-- Add style settings here -->
                    </div>
                </div>

            <?php elseif ($activeTab === 'dictionary'): ?>
                <!-- Dictionary Tab -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><?= __t('admin.settings.dictionary.title', 'admin') ?></h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 30%;"><?= __t('admin.settings.dictionary.key', 'admin') ?></th>
                                        <th><?= __t('admin.settings.dictionary.value', 'admin') ?></th>
                                        <th style="width: 100px;"><?= __t('admin.settings.dictionary.context', 'admin') ?></th>
                                        <th style="width: 100px;"><?= __t('admin.settings.dictionary.language', 'admin') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($translations as $translation): ?>
                                        <tr>
                                            <td class="text-break"><?= htmlspecialchars($translation['translation_key']) ?></td>
                                            <td style="min-width: 300px;">
                                                <div class="input-group">
                                                    <input type="text" 
                                                           class="form-control translation-value" 
                                                           value="<?= htmlspecialchars($translation['translation_value']) ?>"
                                                           data-original="<?= htmlspecialchars($translation['translation_value']) ?>"
                                                           data-key="<?= htmlspecialchars($translation['translation_key']) ?>"
                                                           data-language="<?= htmlspecialchars($translation['language_code']) ?>"
                                                           data-context="<?= htmlspecialchars($translation['context']) ?>">
                                                    <button type="button" class="btn btn-success save-translation" title="Save">
                                                        <i data-lucide="check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger cancel-edit" title="Cancel">
                                                        <i data-lucide="x"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($translation['context']) ?></td>
                                            <td><?= htmlspecialchars($translation['language_code']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Add JavaScript for translation editing -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize Lucide icons
                    lucide.createIcons();

                    // Save translation
                    document.querySelectorAll('.save-translation').forEach(button => {
                        button.addEventListener('click', function() {
                            const row = this.closest('tr');
                            const input = row.querySelector('.translation-value');
                            const newValue = input.value;
                            const key = input.dataset.key;
                            const language = input.dataset.language;
                            const context = input.dataset.context;

                            // Send AJAX request
                            fetch('/ecommerce/admin/ajax/update_translation.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `key=${encodeURIComponent(key)}&value=${encodeURIComponent(newValue)}&language=${encodeURIComponent(language)}&context=${encodeURIComponent(context)}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update the original value
                                    input.dataset.original = newValue;
                                    // Show success message
                                    alert('Translation updated successfully');
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Error updating translation');
                            });
                        });
                    });

                    // Cancel edit
                    document.querySelectorAll('.cancel-edit').forEach(button => {
                        button.addEventListener('click', function() {
                            const row = this.closest('tr');
                            const input = row.querySelector('.translation-value');
                            // Reset to original value
                            input.value = input.dataset.original;
                        });
                    });
                });
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>