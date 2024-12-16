<?php
// Start session at the very beginning
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include 'includes/header.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Email already exists";
        } else {
            // Insert new user with role 3 (simple user)
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, user_role) VALUES (?, ?, ?, 3)");
            if ($stmt->execute([$name, $email, $password])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_role'] = 3;
                $_SESSION['user_name'] = $name;
                header('Location: login.php?registered=success');
                exit;
            } else {
                $error = "Registration failed";
            }
        }
    } catch (PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}

?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Register</h3>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
                
                <p class="mt-3">Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>