<?php
session_start();
include 'config.php';

// Fetch active products
$query = "SELECT * FROM products WHERE is_active = 1 ORDER BY is_popular DESC";
$result = $conn->query($query);

$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Separate popular and regular products
$popular = array_filter($products, fn($p) => $p['is_popular']);
$regular = array_filter($products, fn($p) => !$p['is_popular']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Barberang Ina Mo</title>
    <link rel="stylesheet" href="style.php">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #000000;
            color: #ffffff;
            margin: 0;
            padding: 0;
        }
        
        .products-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .products-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .products-title {
            font-size: 3rem;
            color: #FFD700;
            margin-bottom: 10px;
        }
        
        .products-subtitle {
            color: #c4c4c4;
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .section-title {
            font-size: 2rem;
            color: #FFD700;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }
        
        .product-card {
            background: #1a1a1a;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 215, 0, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            border-color: #FFD700;
            box-shadow: 0 15px 40px rgba(255, 215, 0, 0.1);
        }
        
        .product-image {
            height: 250px;
            width: 100%;
            object-fit: cover;
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(90deg, #FFD700, #b8860b);
            color: #000000;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        
        .product-content {
            padding: 25px;
        }
        
        .product-name {
            font-size: 1.3rem;
            color: #FFD700;
            margin-bottom: 10px;
        }
        
        .product-description {
            color: #c4c4c4;
            margin-bottom: 20px;
            line-height: 1.5;
            font-size: 0.95rem;
        }
        
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #FFD700;
        }
        
        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #c4c4c4;
            font-size: 1.2rem;
        }
        
        @media (max-width: 992px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 25px;
            }
        }
        
        @media (max-width: 768px) {
            .products-container {
                padding: 20px;
            }
            
            .products-title {
                font-size: 2.5rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="products-container">
        <div class="products-header">
            <h1 class="products-title">Our Products</h1>
            <p class="products-subtitle">Premium grooming products for professional home care</p>
        </div>
        
        <?php if (!empty($popular)): ?>
            <div style="margin-bottom: 60px;">
                <h2 class="section-title">
                    <span style="color: #FFD700;">★</span> Popular Products
                </h2>
                <div class="products-grid">
                    <?php foreach ($popular as $product): ?>
                        <a href="product_view.php?id=<?php echo $product['id']; ?>" class="product-card">
                            <div style="position: relative;">
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                <?php else: ?>
                                    <div style="height: 250px; background: #2a2a2a; display: flex; align-items: center; justify-content: center;">
                                        <span style="font-size: 3rem; color: #FFD700;">🛍️</span>
                                    </div>
                                <?php endif; ?>
                                <div class="product-badge">POPULAR</div>
                            </div>
                            <div class="product-content">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars(mb_strimwidth($product['description'], 0, 100, "...")); ?></p>
                                <div class="product-footer">
                                    <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                    <span class="btn" style="padding: 8px 20px; background: #FFD700; color: #222; font-weight: 600; pointer-events: none;">View Details</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($regular)): ?>
            <div>
                <h2 class="section-title">All Products</h2>
                <div class="products-grid">
                    <?php foreach ($regular as $product): ?>
                        <a href="product_view.php?id=<?php echo $product['id']; ?>" class="product-card">
                            <div>
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                <?php else: ?>
                                    <div style="height: 250px; background: #2a2a2a; display: flex; align-items: center; justify-content: center;">
                                        <span style="font-size: 3rem; color: #FFD700;">🛍️</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="product-content">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars(mb_strimwidth($product['description'], 0, 100, "...")); ?></p>
                                <div class="product-footer">
                                    <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                    <span class="btn" style="padding: 8px 20px; background: #FFD700; color: #222; font-weight: 600; pointer-events: none;">View Details</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (empty($products)): ?>
            <div class="no-products">
                <p>No products available at the moment.</p>
                <p style="margin-top: 10px; font-size: 1rem;">Check back soon for new arrivals!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>