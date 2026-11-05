<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    if (isset($_SESSION['admin_id'])) {
        header("Location: AdminPage/admin.php");
    } else {
        header("Location: LogIn/login.php");
    }
    exit();
}

require_once __DIR__ . '/LogIn/Connection.php';

$userId = (int) $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userEmail);

if (!$stmt->fetch()) {
    $stmt->close();
    $conn->close();
    unset($_SESSION['user_id'], $_SESSION['email']);
    header("Location: LogIn/login.php?msg=Please+sign+in+again");
    exit();
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kairos Watches - Welcome</title>
    <link rel="stylesheet" href="HomePage/home.css">
    <link rel="stylesheet" href="user_dashboard.css">
</head>

<body>
    <header>
        <div class="site-logo">Kairos Watches</div>
        <nav class="main-nav">
            <ul>
                <li><a href="HomePage/home.html">Home</a></li>
                <li><a href="MenWatches(AnyPage)/men.html">Men Watches</a></li>
                <li><a href="WomanWatches(ProductPage)/women.php">Women Watches</a></li>
                <li><a href="LogIn/logout.php">Log Out</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <section class="dashboard-card">
            <h1>Welcome to Kairos Watches</h1>
            <p class="dashboard-intro">You have successfully logged in.</p>
            <p class="dashboard-intro">Explore our collections and discover your next timepiece.</p>
            <p class="dashboard-email"><?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="dashboard-actions">
                <a class="dashboard-button" href="HomePage/home.html">Go To Home Page</a>
            </div>
        </section>
    </main>
</body>

</html>
