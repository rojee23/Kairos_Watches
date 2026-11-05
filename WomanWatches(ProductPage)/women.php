<?php
session_start();
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: ../LogIn/login.php");
    exit();
}
?>

<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kairos_db";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM products ORDER BY name ASC";
$result = $conn->query($sql);

$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

function productImageUrl($imagePath)
{
    return strpos($imagePath, 'upload/') === 0 ? '../' . $imagePath : $imagePath;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Kairos Watches - Women's Collection</title>
    <link rel="stylesheet" href="products.css">
</head>

<body>
    <!--Header-->
    <header>
        <div class="site-logo">Kairos Watches</div>
        <nav class="main-nav">
            <ul>
                <li><a href="../HomePage/home.html">Home</a></li>
                <li><a href="#">Products▾</a>
                    <div class="dropdown-menu">
                        <a id="men" href="../MenWatches(AnyPage)/men.html">Men Watches</a>
                        <a id="women" href="women.php">Women Watches</a>
                    </div>
                </li>
                <li><a href="../HomePage/home.html#about">About</a></li>
                <li><a href="../HomePage/home.html#contact">Contact Us</a></li>
                <li><a href="../LogIn/logout.php">Log Out</a></li>
            </ul>
        </nav>
    </header>

    <!--main product gallery section-->
    <section class="products-gallery" id="products-gallery">
        <h1>The Women's Collection</h1>
        <p class="subtitle">Discover the Serene Collection: Elegant timepieces crafted with delicate precision and
            timeless style.</p>

        <div class="products-grid">
            <?php if (empty($products)): ?>
                <p style="grid-column: 1 / -1; text-align: center; padding: 40px; font-size: 1.2rem;">
                    No women's watches are currently available. Please check back soon!
                </p>
            <?php else: ?>
                <!--  generates product cards from the database -->
                <?php foreach ($products as $product): ?>
                    <?php $productImage = productImageUrl($product['image_url']); ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($productImage, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                            onerror="this.onerror=null; this.src='https://placehold.co/300x350/C9A050/000000?text=Women\'s+Watch';">
                        <p class="product-name"><?php echo htmlspecialchars($product['name']); ?></p>
                        <p class="product-price">$<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></p>
                        <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                        <button class="buy-button">Buy Now</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!--Footer-->
    <footer class="site-footer">
        <div class="footer-top">
            <div class="footer-links">
                <a href="../HomePage/home.html">Home</a>
                <a href="women.php">Women's Collection</a>
                <a href="../MenWatches(AnyPage)/men.html">Men's Collection</a>
                <a href="../HomePage/home.html#about">About</a>
                <a href="../HomePage/home.html#contact">Contact</a>
            </div>
            <form class="newsletter">
                <label for="newsletter-email">Subscribe for updates:</label>
                <input type="email" id="newsletter-email" placeholder="Enter your email">
                <button type="submit">Subscribe</button>
            </form>
        </div>
        <div class="footer-description">
            <p>Kairos Watches—Timeless Elegance, Crafted for the Perfect Moment.</p>
        </div>
        <div class="footer-bottom">
            <p>&copy;2024, 2025 Kairos Watches. All rights reserved.</p>
        </div>
    </footer>

    <a href="#products-gallery" id="arrow"></a>

    <script>
        const arrow = document.getElementById('arrow');
        window.addEventListener('scroll', function () {
            if (window.scrollY > 200) {
                arrow.style.display = 'block';
            } else {
                arrow.style.display = 'none';
            }
        });
        arrow.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.getElementById('products-gallery');
            window.scrollTo({ top: target.offsetTop, behavior: 'smooth' });
        });
    </script>
    <!-- Code injected by live-server -->
    <script>
        // <![CDATA[  <-- For SVG support
        if ('WebSocket' in window) {
            (function () {
                function refreshCSS() {
                    var sheets = [].slice.call(document.getElementsByTagName("link"));
                    var head = document.getElementsByTagName("head")[0];
                    for (var i = 0; i < sheets.length; ++i) {
                        var elem = sheets[i];
                        var parent = elem.parentElement || head;
                        parent.removeChild(elem);
                        var rel = elem.rel;
                        if (elem.href && typeof rel != "string" || rel.length == 0 || rel.toLowerCase() == "stylesheet") {
                            var url = elem.href.replace(/(&|\?)_cacheOverride=\d+/, '');
                            elem.href = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_cacheOverride=' + (new Date().valueOf());
                        }
                        parent.appendChild(elem);
                    }
                }
                var protocol = window.location.protocol === 'http:' ? 'ws://' : 'wss://';
                var address = protocol + window.location.host + window.location.pathname + '/ws';
                var socket = new WebSocket(address);
                socket.onmessage = function (msg) {
                    if (msg.data == 'reload') window.location.reload();
                    else if (msg.data == 'refreshcss') refreshCSS();
                };
                if (sessionStorage && !sessionStorage.getItem('IsThisFirstTime_Log_From_LiveServer')) {
                    console.log('Live reload enabled.');
                    sessionStorage.setItem('IsThisFirstTime_Log_From_LiveServer', true);
                }
            })();
        }
        else {
            console.error('Upgrade your browser. This Browser is NOT supported WebSocket for Live-Reloading.');
        }
        // ]]>
    </script>
</body>

</html>

<?php
$conn->close();
?>