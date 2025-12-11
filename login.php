
<?php
/** 
* login page- majlish_e_sarkar System
* responsibilites of this file:
redirect already logged in users
accept email+password
validate inputs
verify user credentials
start session and set user info
redirect to combined dashboard
show proper error messages
 * The login system uses secure password hashing (password_hash/password_verify)
 *  and does NOT expose raw passwords.
 */







// Load configuration + DB + session + helper functions
require_once "configuration.php";


// If user is already logged in → redirect them (no need to login again)

if (loggedIn()) {
    header("Location: index(combined).php");
    exit;
}

$error = "";

//process submission only when log in button is pressed
if (isset($_POST['login'])) {
    
    //sanitize inputs
    $email = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";

  //validity check
    if ($email === "" || $password === "") {
        $error = "Please enter both email and password!";
    }
     else {
        try {
           // Fetch user by email

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            /**
             * Conditions to allow login:
             *   - User exists
             *   - User status is ACTIVE
             *   - Password matches hashed password in DB
             */

            if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
         
                // SUCCESSFUL LOGIN → STORE SESSION DATA


                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
               
               
                // If member → fetch member_id from members table

                if ($user['role'] === "member") {
                    $stmt = $pdo->prepare("SELECT id FROM members WHERE user_id=?");
                    $stmt->execute([$user['id']]);
                    $m = $stmt->fetch();

                    if ($m) {
                        $_SESSION['member_id'] = $m['id'];
                    }
                }
                // Redirect after success (ADMIN + MEMBER goes here)

                header("Location: index(combined).php");
                exit;

            } 
            else {
                // Wrong email OR wrong password
               
                $error = "Invalid email or password!";
            }

        } 
        catch (Exception $e) {

        // Generic message for unexpected database/server issues

            $error = "Server error — try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Majlish E Sarkar</title>
    <style>
        /*reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        /* Background with radial orange glow */
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1E1E1E 0%, #2B2B2B 50%, #1E1E1E 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Top-right orange glow effect */        
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 140, 66, 0.15) 0%, transparent 70%);
            top: -250px;
            right: -250px;
            border-radius: 50%;
        }

         /* Bottom-left glow */
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 140, 66, 0.1) 0%, transparent 70%);
            bottom: -200px;
            left: -200px;
            border-radius: 50%;
        }

        /* Main login box */        
        .login-container {
            background: linear-gradient(135deg, #2B2B2B 0%, #242424 100%);
            padding: 50px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
            width: 100%;
            max-width: 450px;
            position: relative;
            border: 1px solid rgba(255, 140, 66, 0.2);
            z-index: 1;
        }

        /* Top accent border */        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FF8C42 0%, #FFB366 50%, #FF8C42 100%);
            border-radius: 24px 24px 0 0;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        /*floating emoji */
        .login-header .emoji {
            font-size: 64px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .login-header h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #FF8C42 0%, #FFB366 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .login-header p {
            color: #B0B0B0;
            font-size: 15px;
            font-weight: 500;
        }

        /* Error alert box */        
        .error-message {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.2) 0%, rgba(244, 67, 54, 0.1) 100%);
            color: #F44336;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 4px solid #F44336;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.2);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #F5F5F5;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #3A3A3A;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: #1E1E1E;
            color: #F5F5F5;
            font-weight: 500;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.1);
            background: #242424;
        }
        
        .form-group input::placeholder {
            color: #666;
        }

        /* Login button */        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 140, 66, 0.5);
        }
        
        .btn-login:active {
            transform: translateY(0px);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #3A3A3A;
        }
        
        .login-footer p {
            font-size: 13px;
            color: #B0B0B0;
            font-weight: 500;
        }
        
        .hint {
            background: linear-gradient(135deg, rgba(255, 140, 66, 0.1) 0%, rgba(255, 140, 66, 0.05) 100%);
            padding: 15px;
            border-radius: 12px;
            margin-top: 20px;
            font-size: 13px;
            color: #FF8C42;
            text-align: center;
            border: 1px solid rgba(255, 140, 66, 0.2);
        }
        
        .hint strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="emoji">🍽️</div>
            <h1>Majlish E Sarkar</h1>
            <p>Sign in to continue</p>
        </div>
        
        <?php if(!empty($error)): ?>
            <div class="error-message">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email"
                    name="email" 
                    placeholder="Enter your email" 
                    required 
                    autofocus
                    value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    placeholder="Enter your password" 
                    required
                >
            </div>
            
            <button type="submit" name="login" class="btn-login">
                Sign In
            </button>
        </form>
        
        <div class="login-footer">
            <p>&copy; 2025 Majlish E Sarkar. All rights reserved.</p>
        </div>
    </div>
</body>
</html>