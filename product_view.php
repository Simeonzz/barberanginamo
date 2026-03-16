<?php
include 'db_connect.php';
include 'navbar.php';

$id = $_GET['id'] ?? null;

if (!$id) {
  echo "<div class='container py-5 text-center'><h3>Product not found</h3></div>";
  exit;
}

$result = $conn->query("SELECT * FROM products WHERE id = $id");
$product = $result->fetch_assoc();

if (!$product) {
  echo "<div class='container py-5 text-center'><h3>Product not found</h3></div>";
  exit;
}
?>

<div class="container py-5">
  <a href="products.php" class="btn btn-light mb-4">← Back to Products</a>
  
  <div class="row g-4">
    <div class="col-md-6">
      <?php if (!empty($product['image_url'])): ?>
        <img src="<?php echo $product['image_url']; ?>" class="img-fluid rounded shadow">
      <?php else: ?>
        <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height:400px;">
          <i class="bi bi-box-seam fs-1 text-muted"></i>
        </div>
      <?php endif; ?>
    </div>
    <div class="col-md-6">
      <h1 class="fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
      <p class="text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
      <h3 class="text-primary fw-bold mb-3">₱<?php echo number_format($product['price'], 2); ?></h3>

      <?php if (!empty($product['ingredients'])): ?>
        <h5>Ingredients</h5>
        <p><?php echo htmlspecialchars($product['ingredients']); ?></p>
      <?php endif; ?>

      <?php if (!empty($product['benefits'])): ?>
        <h5>Benefits</h5>
        <p><?php echo htmlspecialchars($product['benefits']); ?></p>
      <?php endif; ?>

      <p><strong>Stock:</strong> 
        <?php echo $product['stock_quantity'] > 0 ? $product['stock_quantity'] . " in stock" : "Out of stock"; ?>
      </p>

      <button class="btn btn-primary btn-lg" <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
        <?php echo $product['stock_quantity'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
      </button>
    </div>
  </div>
</div>
