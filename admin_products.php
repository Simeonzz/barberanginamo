<?php
session_start(); 


if (!isset($_SESSION['user_id'])) {
    header("Location: auth_login.php");
    exit();
}

ob_start();
// --- Database connection ---
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "barberanginamodb";


$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// --- Handle Add or Update ---
if (isset($_POST['save_product'])) {
    $id = $_POST['product_id'] ?? null;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    // Handle image upload
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/products/';
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . uniqid('prod_') . '_' . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileType, $allowedTypes) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image_url = $targetFile;
            }
        }
    } else if (!empty($_POST['existing_image'])) {
        $image_url = $_POST['existing_image'];
    }
    $ingredients = $_POST['ingredients'] ?: null;
    $benefits = $_POST['benefits'] ?: null;
    $stock_quantity = $_POST['stock_quantity'] ?: 0;
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;

    if ($id) {
        $sql = "UPDATE products SET name=?, description=?, price=?, image_url=?, ingredients=?, benefits=?, stock_quantity=?, is_popular=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsdssii", $name, $description, $price, $image_url, $ingredients, $benefits, $stock_quantity, $is_popular, $id);
    } else {
        $sql = "INSERT INTO products (name, description, price, image_url, ingredients, benefits, stock_quantity, is_popular)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsdssi", $name, $description, $price, $image_url, $ingredients, $benefits, $stock_quantity, $is_popular);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Handle Delete ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM products WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Handle Activate/Deactivate ---
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $conn->query("UPDATE products SET is_active = NOT is_active WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Fetch All Products ---
$products = $conn->query("SELECT * FROM products ORDER BY name");
$content = ob_get_clean();
include 'admin_layout.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Products</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/admin-theme.css">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="fw-bold">Manage Products</h2>
      <p class="text-muted">Add, edit, or remove products</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">+ Add Product</button>
  </div>

  <div class="row g-3">
    <?php while ($row = $products->fetch_assoc()): ?>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title d-flex justify-content-between">
              <span><?= htmlspecialchars($row['name']) ?></span>
              <div>
                <?php if ($row['is_popular']): ?>
                  <span class="badge bg-warning text-dark">Popular</span>
                <?php endif; ?>
                <span class="badge <?= $row['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                  <?= $row['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              </div>
            </h5>
            <p class="text-muted small mb-3"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
            <p><strong>₱<?= number_format($row['price'], 2) ?></strong></p>
            <p><small>Stock: <?= $row['stock_quantity'] ?> units</small></p>
          </div>
          <div class="card-footer bg-white d-flex justify-content-between">
            <a href="?toggle=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary">
              <?= $row['is_active'] ? 'Deactivate' : 'Activate' ?>
            </a>
            <button class="btn btn-sm btn-outline-primary"
              data-bs-toggle="modal"
              data-bs-target="#productModal"
              data-id="<?= $row['id'] ?>"
              data-name="<?= htmlspecialchars($row['name']) ?>"
              data-description="<?= htmlspecialchars($row['description']) ?>"
              data-price="<?= $row['price'] ?>"
              data-image="<?= htmlspecialchars($row['image_url']) ?>"
              data-ingredients="<?= htmlspecialchars($row['ingredients']) ?>"
              data-benefits="<?= htmlspecialchars($row['benefits']) ?>"
              data-stock="<?= $row['stock_quantity'] ?>"
              data-popular="<?= $row['is_popular'] ?>"
            >Edit</button>
            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this product?')" class="btn btn-sm btn-danger">Delete</a>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data" id="productForm">
        <div class="modal-header">
          <h5 class="modal-title">Add / Edit Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="product_id" id="product_id">
          <div class="mb-3">
            <label class="form-label">Product Name</label>
            <input type="text" class="form-control" name="name" id="name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" id="description" rows="3" required></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Price (₱)</label>
              <input type="number" step="0.01" class="form-control" name="price" id="price" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Stock Quantity</label>
              <input type="number" class="form-control" name="stock_quantity" id="stock_quantity" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Product Image (Optional)</label>
            <input type="file" class="form-control" name="image" id="image" accept="image/*">
            <input type="hidden" name="existing_image" id="existing_image">
            <div class="form-text">Max 5MB. JPG, PNG, GIF, WEBP</div>
            <div class="image-preview mt-3">
                <img id="imagePreview" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px; display: none;">
                <div id="noImage" class="text-center text-muted p-4 border rounded">
                    <i class="bi bi-image" style="font-size: 3rem;"></i>
                    <p class="mt-2">No image selected</p>
                </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Ingredients</label>
            <textarea class="form-control" name="ingredients" id="ingredients" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Benefits</label>
            <textarea class="form-control" name="benefits" id="benefits" rows="2"></textarea>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_popular" id="is_popular">
            <label class="form-check-label" for="is_popular">Mark as Popular Product</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="save_product" class="btn btn-primary">Save Product</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const productModal = document.getElementById('productModal');
  productModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    if (id) {
      document.getElementById('product_id').value = id;
      document.getElementById('name').value = button.getAttribute('data-name');
      document.getElementById('description').value = button.getAttribute('data-description');
      document.getElementById('price').value = button.getAttribute('data-price');
      document.getElementById('existing_image').value = button.getAttribute('data-image');
      document.getElementById('ingredients').value = button.getAttribute('data-ingredients');
      document.getElementById('benefits').value = button.getAttribute('data-benefits');
      document.getElementById('stock_quantity').value = button.getAttribute('data-stock');
      document.getElementById('is_popular').checked = button.getAttribute('data-popular') == 1;
      // Show image preview
      if (button.getAttribute('data-image')) {
        document.getElementById('imagePreview').src = button.getAttribute('data-image');
        document.getElementById('imagePreview').style.display = 'block';
        document.getElementById('noImage').style.display = 'none';
      } else {
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('noImage').style.display = 'block';
      }
    } else {
      document.querySelector('form').reset();
      document.getElementById('product_id').value = '';
      document.getElementById('existing_image').value = '';
      document.getElementById('imagePreview').style.display = 'none';
      document.getElementById('noImage').style.display = 'block';
    }
  });

  // Image preview
  document.getElementById('image').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('imagePreview').src = e.target.result;
        document.getElementById('imagePreview').style.display = 'block';
        document.getElementById('noImage').style.display = 'none';
      };
      reader.readAsDataURL(file);
    } else {
      document.getElementById('imagePreview').style.display = 'none';
      document.getElementById('noImage').style.display = 'block';
    }
  });

  // Form validation
  document.getElementById('productForm').addEventListener('submit', function(event) {
    const name = document.getElementById('name').value.trim();
    const description = document.getElementById('description').value.trim();
    const price = document.getElementById('price').value;
    const stock = document.getElementById('stock_quantity').value;
    if (!name || !description || !price || !stock) {
      event.preventDefault();
      alert('Please fill in all required fields.');
      return false;
    }
    if (parseFloat(price) <= 0) {
      event.preventDefault();
      alert('Price must be greater than 0.');
      return false;
    }
    if (parseInt(stock) < 0) {
      event.preventDefault();
      alert('Stock cannot be negative.');
      return false;
    }
    // Image validation
    const image = document.getElementById('image').files[0];
    if (image && image.size > 5 * 1024 * 1024) {
      event.preventDefault();
      alert('Image file must be less than 5MB.');
      return false;
    }
    return true;
  });
</script>
</body>
</html>
