<?php
require_once 'config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim inputs
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists';
            $stmt->close();
        } else {
            $stmt->close(); // Close SELECT before INSERT

            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);
            
            if ($stmt->execute()) {
                $stmt->close();
                // Redirect to login page with success message
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BudgetFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00B4D8;
            --primary-dark: #0096C7;
            --dark: #023047;
            --danger: #EF476F;
            --success: #06D6A0;
            --white: #FFFFFF;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .register-container {
            background: var(--white);
            border-radius: 24px;
            padding: 3rem;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .logo { font-family: 'Outfit', sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--primary); text-align: center; margin-bottom: 0.5rem; }
        .subtitle { text-align: center; color: var(--dark); opacity: 0.7; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--dark); }
        .form-group input { width: 100%; padding: 1rem; border: 2px solid #CAF0F8; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 1rem; transition: all 0.3s; }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.1); }
        .error-message { background: #FFE5E5; color: var(--danger); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .success-message { background: #D4F4E7; color: var(--success); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .btn-register {
            width: 100%; padding: 1rem; background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white); border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: 1rem;
            cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(0, 180, 216, 0.3);
        }
        .btn-register:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 180, 216, 0.4); }
        .login-link { text-align: center; margin-top: 1.5rem; color: var(--dark); opacity: 0.7; }
        .login-link a { color: var(--primary); font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">BudgetFlow</div>
        <p class="subtitle">Create Your Account</p>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn-register">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>
