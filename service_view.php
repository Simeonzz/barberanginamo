<?php
include 'db_connect.php';
include 'navbar.php';

$id = $_GET['id'] ?? null;

if (!$id) {
  echo "<div class='container py-5 text-center'><h3>Service not found</h3></div>";
  exit;
}

$result = $conn->query("SELECT * FROM services WHERE id = $id");
$service = $result->fetch_assoc();

if (!$service) {
  echo "<div class='container py-5 text-center'><h3>Service not found</h3></div>";
  exit;
}
?>

<div class="container py-5">
  <a href="services.php" class="btn btn-light mb-4">← Back to Services</a>
  <div class="row g-4">
    <div class="col-md-6">
      <?php if (!empty($service['image_url'])): ?>
        <img src="<?php echo $service['image_url']; ?>" class="img-fluid rounded shadow">
      <?php else: ?>
        <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height:400px;">
          <i class="bi bi-scissors fs-1 text-muted"></i>
        </div>
      <?php endif; ?>
    </div>
    <div class="col-md-6">
      <h1 class="fw-bold mb-3"><?php echo htmlspecialchars($service['name']); ?></h1>
      <p class="text-muted"><?php echo htmlspecialchars($service['description']); ?></p>
      <h3 class="text-primary fw-bold mb-3">₱<?php echo number_format($service['price'], 2); ?></h3>
      <?php if (!empty($service['benefits'])): ?>
        <h5>Benefits</h5>
        <p><?php echo htmlspecialchars($service['benefits']); ?></p>
      <?php endif; ?>
      <button class="btn btn-primary btn-lg">
        Book Now
      </button>
    </div>
  </div>
</div>
