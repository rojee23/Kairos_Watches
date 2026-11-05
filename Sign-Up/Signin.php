<?php

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once __DIR__ . '/Connection.php';

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === "" || $password === "") {
        $message = "<p style='color:#ff6b6b; font-weight:bold;'>Please fill all fields!</p>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<p style='color:#ff6b6b; font-weight:bold;'>Invalid email format!</p>";
    } elseif (strlen($password) < 6) {
        $message = "<p style='color:#ff6b6b; font-weight:bold;'>Password must be at least 6 characters.</p>";
    } else {
        // تحقق إذا الإيميل موجود بالفعل
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "<p style='color:#ff6b6b; font-weight:bold;'>This email is already registered.</p>";
            $stmt->close();
        } else {
            $stmt->close();

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $insert->bind_param("ss", $email, $hashedPassword);

            if ($insert->execute()) {
                header("Location: ../LogIn/login.php?msg=Account+created+successfully");
                exit();
            } else {
                $message = "<p style='color:#ff6b6b; font-weight:bold;'>Error: " . htmlspecialchars($conn->error) . "</p>";
            }

            $insert->close();
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kairos Watches Sign Up</title>
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

        .signup-container {
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
            background-color: #0d0d0d;
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

        .signup-btn {
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

        .signup-btn:active {
            transform: translateY(1px);
        }

        .signup-btn:hover {
            box-shadow: 0 10px 28px rgba(229, 190, 0, 0.22);
        }

        .alt {
            margin-top: 14px;
            color: #f3d88b;
            font-size: 0.95rem;
        }

        a {
            color: #C9A050;
            font-weight: 700;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* رسالة */
        .message {
            margin-bottom: 12px;
        }
    </style>
</head>

<body>
    <div class="signup-container">
        <h2>Create Account</h2>

        <div class="message">
            <?php echo $message; ?>
        </div>

        <form action="" method="POST" autocomplete="off">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" />
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" />
            </div>

            <button type="submit" class="signup-btn">Sign Up</button>
        </form>

        <p class="alt">Already have an account? <a href="../LogIn/login.php">Login</a></p>
    </div>
</body>

</html>