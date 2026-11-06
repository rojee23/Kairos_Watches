<?php
session_start();
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        require_once __DIR__ . '/Connection.php';

        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($email === "" || $password === "") {
            $message = "<p style='color:#ff6b6b; font-weight:bold;'>Please fill all fields!</p>";
        } else {
            // 1. Check admin_users first
            $stmt = $conn->prepare("SELECT id, password FROM admin_users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $hashedPassword);
                $stmt->fetch();

                if (password_verify($password, $hashedPassword)) {
                    // Admin login successful
                    session_regenerate_id(true);
                    unset($_SESSION['user_id'], $_SESSION['email']);
                    $_SESSION['admin_id'] = $id;
                    $_SESSION['admin_email'] = $email;
                    setcookie('user_email', $email, [
                        'expires' => time() + (7 * 24 * 60 * 60),
                        'path' => '/',
                        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                    header("Location: ../AdminPage/admin.php");
                    exit();
                } else {
                    $message = "<p style='color:#ff6b6b; font-weight:bold;'>Wrong password!</p>";
                }
            } else {
                $stmt->close();

                // 2. Check normal users
                $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ? LIMIT 1");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($id, $hashedPassword);
                    $stmt->fetch();

                    if (password_verify($password, $hashedPassword)) {
                        // User login successful
                        session_regenerate_id(true);
                        unset($_SESSION['admin_id'], $_SESSION['admin_email']);
                        $_SESSION['user_id'] = $id;
                        $_SESSION['email'] = $email;
                        setcookie('user_email', $email, [
                            'expires' => time() + (7 * 24 * 60 * 60),
                            'path' => '/',
                            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);
                        header("Location: ../user_dashboard.php");
                        exit();
                    } else {
                        $message = "<p style='color:#ff6b6b; font-weight:bold;'>Wrong password!</p>";
                    }
                } else {
                    $message = "<p style='color:#ff6b6b; font-weight:bold;'>Email not found!</p>";
                }
            }

            $stmt->close();
        }

        $conn->close();
    } catch (Throwable $exception) {
        error_log("Login failed: " . $exception->getMessage());
        $message = "<p style='color:#ff6b6b; font-weight:bold;'>Login is temporarily unavailable. Please try again later.</p>";
    }
}

if (isset($_GET['msg'])) {
    $message = "<p style='color:#C9A050; font-weight:bold;'>" .
        htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8') . "</p>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kairos Watches Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #0d0d0d;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: linear-gradient(180deg, #0d0d0d 0%, #111 100%);
            padding: 34px;
            border-radius: 12px;
            width: 360px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(255, 215, 0, 0.12);
            border: 1px solid rgba(255, 215, 0, 0.12);
        }

        h2 {
            font-size: 1.6rem;
            margin-bottom: 18px;
            color: #C9A050;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-group {
            margin-bottom: 14px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 6px;
            color: #C9A050;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .input-group input {
            width: 100%;
            padding: 11px;
            border: 1px solid rgba(255, 215, 0, 0.25);
            border-radius: 8px;
            background-color: #000;
            color: #f3d88b;
            font-size: 1rem;
            transition: box-shadow .2s, border-color .2s;
        }

        .input-group input::placeholder {
            color: rgba(243, 216, 139, 0.45);
        }

        .input-group input:focus {
            outline: none;
            border-color: #C9A050;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.18);
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(180deg, #C9A050, #e6be00);
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform .12s, box-shadow .12s;
            box-shadow: 0 6px 18px rgba(229, 190, 0, 0.18);
        }

        .login-btn:active {
            transform: translateY(1px);
        }

        .login-btn:hover {
            box-shadow: 0 10px 28px rgba(229, 190, 0, 0.22);
        }

        .alt {
            margin-top: 14px;
            color: #f3d88b;
            font-size: 0.95rem;
        }

        a {
            color: #FFD700;
            font-weight: 700;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .message {
            margin-bottom: 12px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Login to Kairos Watches</h2>
        <div class="message"><?php echo $message; ?></div>
        <form action="" method="POST" autocomplete="off">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"/>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"  />
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
        <p class="alt">Don't have an account? <a href="../Sign-Up/Signin.php">Sign Up</a></p>
    </div>
</body>

</html>