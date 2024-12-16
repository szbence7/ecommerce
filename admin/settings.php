<?php include 'layout/header.php'; ?>
<?php require_once '../includes/db.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include 'layout/sidebar.php'; ?>
        
        <div class="col-md-10" id="content">
            <h2>Settings</h2>

            <?php
            // Árfolyam mentése
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['currency'])) {
                    $currency = $_POST['currency'];
                    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'currency'");
                    if ($stmt->execute([$currency])) {
                        echo '<div class="alert alert-success">Currency settings updated successfully!</div>';
                    }
                }
                
                if (isset($_POST['rates'])) {
                    foreach ($_POST['rates'] as $currency => $rate) {
                        if (is_numeric($rate)) {
                            $stmt = $pdo->prepare("INSERT INTO exchange_rates (currency, rate) VALUES (?, ?) 
                                                 ON DUPLICATE KEY UPDATE rate = ?");
                            $stmt->execute([$currency, $rate, $rate]);
                        }
                    }
                    echo '<div class="alert alert-success">Exchange rates updated successfully!</div>';
                }
            }

            // Jelenlegi beállítások lekérése
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'currency'");
            $currentCurrency = $stmt->fetchColumn();

            // Árfolyamok lekérése
            $stmt = $pdo->query("SELECT currency, rate FROM exchange_rates");
            $rates = [];
            while ($row = $stmt->fetch()) {
                $rates[$row['currency']] = $row['rate'];
            }
            ?>

            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Currency Settings</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="currency" class="form-label">Shop Currency</label>
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

            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Exchange Rates</h5>
                    <p class="text-muted">Set exchange rates relative to EUR (EUR is always 1.00)</p>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">EUR</label>
                            <input type="number" class="form-control" value="1.00" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">HUF</label>
                            <input type="number" name="rates[HUF]" class="form-control" step="0.01" 
                                   value="<?php echo isset($rates['HUF']) ? $rates['HUF'] : '390.00'; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">USD</label>
                            <input type="number" name="rates[USD]" class="form-control" step="0.01" 
                                   value="<?php echo isset($rates['USD']) ? $rates['USD'] : '1.08'; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Exchange Rates</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'layout/footer.php'; ?> 