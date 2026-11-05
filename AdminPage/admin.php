<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
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


// Function to safely sanitize input
function sanitize($conn, $data)
{
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data))));
}

//create or update product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($conn, $_POST['name']);
    $price = sanitize($conn, $_POST['price']);
    $description = sanitize($conn, $_POST['description']);
    $image_url = sanitize($conn, $_POST['image_url']);
    $product_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    // Validate fields
    if (empty($name) || empty($price) || empty($image_url)) {
        $message = "Please fill in all required fields (Name, Price, Image URL).";
    } else {
        if ($product_id > 0) {
            // update existing product
            $stmt = $conn->prepare("UPDATE products SET name=?, price=?, description=?, image_url=? WHERE id=?");
            $stmt->bind_param("sdssi", $name, $price, $description, $image_url, $product_id);
            if ($stmt->execute()) {
                $message = "Product updated successfully!";
            } else {
                $message = "Error updating product: " . $stmt->error;
            }
        } else {
            // insert new product
            $stmt = $conn->prepare("INSERT INTO products (name, price, description, image_url) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdss", $name, $price, $description, $image_url);
            if ($stmt->execute()) {
                $message = "Product added successfully!";
            } else {
                $message = "Error adding product: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// delete product
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Product deleted successfully.";
    } else {
        $message = "Error deleting product: " . $stmt->error;
    }
    $stmt->close();
    // clean the URL after deletion
    header("Location: admin.php?msg=" . urlencode($message));
    exit();
}

// fetch all products
$products = [];
$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// fetch single product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_product = $result->fetch_assoc();
    $stmt->close();
}

// handle message display
if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kairos Admin - Product Management</title>
    <link rel="stylesheet" href="products.css">
    <link rel="stylesheet" href="admin.css">
</head>

<body>
    <header>
        <div class="site-logo">Kairos Admin</div>
        <nav class="main-nav">
            <ul>
                <li><a href="admin.php">Product Management</a></li>
                <li><a href="../LogIn/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section class="admin-container">
        <h1>Product Management Dashboard</h1>

        <?php if (isset($message)): ?>
            <div class="message-box success"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- add/edit product form -->
        <div class="form-card">
            <h2><?php echo $edit_product ? 'Edit Product ID: ' . $edit_product['id'] : 'Add New Product'; ?></h2>
            <form method="POST" action="admin.php" class="product-form">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_product['id']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name"
                        value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="price">Price ($) *</label>
                    <input type="number" id="price" name="price" step="0.01"
                        value="<?php echo htmlspecialchars($edit_product['price'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="image_url">Image URL *</label>
                    <input type="text" id="image_url" name="image_url"
                        value="<?php echo htmlspecialchars($edit_product['image_url'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"
                        rows="4"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="submit-button">
                    <?php echo $edit_product ? 'Save Changes' : 'Add Product'; ?>
                </button>

                <?php if ($edit_product): ?>
                    <a href="admin.php" class="cancel-button">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Product List Table -->
        <div class="list-card">
            <h2>Current Products (<?php echo count($products); ?>)</h2>
            <?php if (empty($products)): ?>
                <p>No products found in the database.</p>
            <?php else: ?>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Image Preview</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td data-label="ID"><?php echo htmlspecialchars($product['id']); ?></td>
                                <td data-label="Name"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td data-label="Price">$<?php echo htmlspecialchars(number_format($product['price'], 2)); ?>
                                </td>
                                <td data-label="Preview">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Product image"
                                        class="thumb"
                                        onerror="this.onerror=null; this.src='https://placehold.co/50x50/ccc/000?text=No+Img';">
                                </td>
                                <td data-label="Actions" class="actions">
                                    <a href="admin.php?edit=<?php echo $product['id']; ?>" class="action-button edit">Edit</a>
                                    <button
                                        onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')"
                                        class="action-button delete">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <script>
        function confirmDelete(id, name) {
            if (window.confirm(`Are you sure you want to delete product: "${name}" (ID: ${id})?`)) {
                window.location.href = `admin.php?delete=${id}`;
            }
        }
    </script>
    <?php $conn->close(); ?>
</body>

</html>