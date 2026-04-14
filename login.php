<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include("db_connect.php");

$error_message = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM myusers WHERE email = ?";    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $stored_password = $user['password'];
        $password_match = false;

        if (password_verify($password, $stored_password)) {
            $password_match = true;
        } elseif ($password === $stored_password) {
            $password_match = true;
        }

       // ... [Previous logic stays the same until redirection] ...

        if ($password_match) {
            $_SESSION['user_id'] = $user['user_id']; 
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user'] = $email;
            $_SESSION['role'] = $user['role'];
            $_SESSION['barangay_id'] = $user['barangay_id'];
            $_SESSION['related_pwd_id'] = $user['related_pwd_id']; 

            // UPDATED ROLE-BASED REDIRECTION
            if ($user['role'] === 'barangay_admin') {
                // Pointing to the new folder name
                header("Location: brgy_data/index.php"); 
            } elseif ($user['role'] === 'doctor') {
                header("Location: doctor/index.php");
            } elseif ($user['role'] === 'super_admin') {
                // Pointing to the root index.php (the main dashboard)
                header("Location: index.php");
            } elseif ($user['role'] === 'pwd') {
                header("Location: pwduser/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
// ... [Rest of the file stays the same] ...
            $error_message = "Incorrect password!";
        }
    } else {
        $error_message = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWD Management - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-page {
          background: linear-gradient(180deg, #063970 0%, #0e82ff 100%);

 background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            overflow-x: hidden;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 550px;
            margin: 20px;
        }
        
        .login-left {
           background: linear-gradient(180deg, #117fed 10%,   #063970 80% );
 color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .logo-wrapper {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .government-logo {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .government-logo img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        
        .system-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            line-height: 1.2;
        }
        
        .system-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .login-right {
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-form-header h2 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group i {
            position: absolute;
            right: 15px;
            bottom: 15px;
            color: #95a5a6;
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
          background: linear-gradient(180deg, #063970 0%, #0e82ff 100%);
  color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            background: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .login-container { grid-template-columns: 1fr; }
            .login-left { display: none; }
        }
    </style>
</head>
<body class="login-page">

    <div class="login-container">
        <div class="login-left">
            <div class="logo-wrapper">
                <div class="government-logo">
                    <img src="uploads/ebmag.jpg" alt="EB Magalona Logo" 
                         onerror="this.src='https://via.placeholder.com/70?text=LOGO+1'">
                </div>
                
                <div class="government-logo">
                    <img src="uploads/mswdo.jpg" alt="MSWDO Logo" 
                         onerror="this.src='https://via.placeholder.com/70?text=LOGO+2'">
                </div>
            </div>
            
            <h1 class="system-title">PWD Management System</h1>
            <p class="system-subtitle">Municipality of EB Magalona</p>
            <p style="font-size: 13px; opacity: 0.7;">Secure Access Portal</p>
        </div>
        
        <div class="login-right">
            <div class="login-form-header">
                <h2>Welcome Back</h2>
                <p style="color: #7f8c8d;">Please sign in to continue</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Email Address</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="name@example.com" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <i class="fas fa-envelope"></i>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                    <i class="fas fa-lock"></i>
                </div>
                
                <button type="submit" class="login-btn" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </div>

    <script>
        const loginForm = document.querySelector('form');
        const loginBtn = document.getElementById('loginBtn');
        
        loginForm.addEventListener('submit', function() {
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            loginBtn.style.opacity = '0.7';
        });
    </script>
</body>
</html>