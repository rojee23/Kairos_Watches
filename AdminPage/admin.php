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

// Function to safely sanitize input
function sanitize($conn, $data)
{
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data))));
}

function productImageUrl($imagePath)
{
    return strpos($imagePath, 'upload/') === 0 ? '../' . $imagePath : $imagePath;
}

$message = '';
$messageType = 'success';
$conn = null;
$products = [];
$edit_product = null;
$uploadedFilePath = null;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");

    //create or update product
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = sanitize($conn, $_POST['name'] ?? '');
        $priceInput = trim($_POST['price'] ?? '');
        $description = sanitize($conn, $_POST['description'] ?? '');
        $product_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $image_url = '';
        $uploadError = '';

        if ($product_id > 0) {
            $existing = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
            $existing->bind_param("i", $product_id);
            $existing->execute();
            $existing->bind_result($existingImagePath);
            if (!$existing->fetch()) {
                $uploadError = "The product you are trying to edit no longer exists.";
            }
            $existing->close();
            $image_url = $existingImagePath ?? '';
        }

        if ($uploadError === '') {
            $file = $_FILES['image'] ?? null;

            if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
                if ($product_id === 0) {
                    $uploadError = "Please select a product image.";
                }
            } else {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => "The image exceeds the server upload limit.",
                    UPLOAD_ERR_FORM_SIZE => "The image exceeds the form upload limit.",
                    UPLOAD_ERR_PARTIAL => "The image was only partially uploaded. Please try again.",
                    UPLOAD_ERR_NO_TMP_DIR => "The server upload temporary folder is missing.",
                    UPLOAD_ERR_CANT_WRITE => "The server could not save the uploaded image.",
                    UPLOAD_ERR_EXTENSION => "A server extension prevented the image upload."
                ];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $uploadError = $uploadErrors[$file['error']] ?? "The image upload failed.";
                } else {
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowedMimeTypes = [
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'webp' => 'image/webp'
                    ];
                    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $fileInfo->file($file['tmp_name']);
                    $imageInfo = getimagesize($file['tmp_name']);

                    if (!isset($allowedMimeTypes[$extension]) || $mimeType !== $allowedMimeTypes[$extension] || $imageInfo === false) {
                        $uploadError = "Only valid JPG, JPEG, PNG, and WEBP image files are allowed.";
                    } else {
                        $uploadDirectory = __DIR__ . '/../upload';
                        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
                            $uploadError = "The upload folder could not be created.";
                        } else {
                            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
                            $uploadedFilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

                            if (!move_uploaded_file($file['tmp_name'], $uploadedFilePath)) {
                                $uploadedFilePath = null;
                                $uploadError = "The uploaded image could not be saved. Please try again.";
                            } else {
                                $image_url = 'upload/' . $fileName;
                            }
                        }
                    }
                }
            }
        }

        if ($uploadError === '' && ($name === '' || !is_numeric($priceInput) || (float) $priceInput < 0)) {
            $uploadError = "Enter a product name and a valid non-negative price.";
        }

        if ($uploadError !== '') {
            $message = $uploadError;
            $messageType = 'error';
            if ($uploadedFilePath !== null && file_exists($uploadedFilePath)) {
                unlink($uploadedFilePath);
                $uploadedFilePath = null;
            }
        } else {
            $price = (float) $priceInput;
            if ($product_id > 0) {
                $stmt = $conn->prepare("UPDATE products SET name=?, price=?, description=?, image_url=? WHERE id=?");
                $stmt->bind_param("sdssi", $name, $price, $description, $image_url, $product_id);
                $successMessage = "Product updated successfully!";
            } else {
                $stmt = $conn->prepare("INSERT INTO products (name, price, description, image_url) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sdss", $name, $price, $description, $image_url);
                $successMessage = "Product added successfully!";
            }

            if ($stmt->execute()) {
                $message = $successMessage;
            } else {
                $message = "The product could not be saved. Please try again later.";
                $messageType = 'error';
                if ($uploadedFilePath !== null && file_exists($uploadedFilePath)) {
                    unlink($uploadedFilePath);
                    $uploadedFilePath = null;
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
            $message = "The product could not be deleted. Please try again later.";
        }
        $stmt->close();
        // clean the URL after deletion
        header("Location: admin.php?msg=" . urlencode($message));
        exit();
    }

    // fetch all products
    $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }

    // fetch single product for editing
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
} catch (Throwable $exception) {
    error_log("Product management operation failed: " . $exception->getMessage());
    if ($uploadedFilePath !== null && file_exists($uploadedFilePath)) {
        unlink($uploadedFilePath);
    }
    $message = 'Product management is temporarily unavailable. Please try again later.';
    $messageType = 'error';
    $products = [];
    $edit_product = null;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kairos Admin - Product Management</title>
    <link rel="stylesheet" href="../WomanWatches(ProductPage)/products.css">
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

        <?php if ($message !== ''): ?>
            <div class="message-box <?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- add/edit product form -->
        <div class="form-card">
            <h2><?php echo $edit_product ? 'Edit Product ID: ' . $edit_product['id'] : 'Add New Product'; ?></h2>
            <form method="POST"
                action="admin.php<?php echo $edit_product ? '?edit=' . (int) $edit_product['id'] : ''; ?>"
                class="product-form" enctype="multipart/form-data">
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
                    <label for="image">Product Image <?php echo $edit_product ? '(optional)' : '*'; ?></label>
                    <label class="upload-dropzone" id="upload-dropzone" for="image">
                        <span class="upload-title">Drag and drop an image here</span>
                        <span class="upload-hint">or click to choose JPG, JPEG, PNG, or WEBP</span>
                        <span class="upload-filename" id="upload-filename">
                            <?php echo $edit_product ? 'Choose a new image to replace the current one' : 'No image selected'; ?>
                        </span>
                    </label>
                    <input class="image-file-input" type="file" id="image" name="image"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        <?php echo $edit_product ? '' : 'required'; ?>>
                    <?php if ($edit_product): ?>
                        <?php $editImageUrl = productImageUrl($edit_product['image_url']); ?>
                        <img class="image-preview" id="image-preview"
                            src="<?php echo htmlspecialchars($editImageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="Current product image">
                    <?php else: ?>
                        <img class="image-preview" id="image-preview" alt="Selected product image" hidden>
                    <?php endif; ?>
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
                                    <?php $productImage = productImageUrl($product['image_url']); ?>
                                    <img src="<?php echo htmlspecialchars($productImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Product image"
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
        const imageInput = document.getElementById('image');
        const dropzone = document.getElementById('upload-dropzone');
        const preview = document.getElementById('image-preview');
        const fileNameLabel = document.getElementById('upload-filename');
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        function showImagePreview(file) {
            const extension = file.name.split('.').pop().toLowerCase();
            if (!allowedExtensions.includes(extension)) {
                imageInput.value = '';
                fileNameLabel.textContent = 'Only JPG, JPEG, PNG, or WEBP files are allowed.';
                return;
            }

            fileNameLabel.textContent = file.name;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        }

        imageInput.addEventListener('change', function () {
            if (this.files.length > 0) {
                showImagePreview(this.files[0]);
            }
        });

        dropzone.addEventListener('dragover', function (event) {
            event.preventDefault();
            this.classList.add('drag-over');
        });

        dropzone.addEventListener('dragleave', function () {
            this.classList.remove('drag-over');
        });

        dropzone.addEventListener('drop', function (event) {
            event.preventDefault();
            this.classList.remove('drag-over');
            if (event.dataTransfer.files.length > 0) {
                const transfer = new DataTransfer();
                transfer.items.add(event.dataTransfer.files[0]);
                imageInput.files = transfer.files;
                showImagePreview(event.dataTransfer.files[0]);
            }
        });

        function confirmDelete(id, name) {
            if (window.confirm(`Are you sure you want to delete product: "${name}" (ID: ${id})?`)) {
                window.location.href = `admin.php?delete=${id}`;
            }
        }
    </script>
    <?php if ($conn instanceof mysqli): $conn->close(); endif; ?>
</body>

</html>