<?php
require_once 'config.php';

// Fetch categories for the sidebar filter
try {
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Get filter parameters from URL
$selectedCategories = $_GET['category_id'] ?? [];
if (!is_array($selectedCategories)) {
    $selectedCategories = [$selectedCategories];
}

$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 10000;
$sort = $_GET['sort'] ?? 'popular';

// Build dynamic SQL query joining vendors and categories
$sql = "SELECT p.*, v.store_name FROM products p JOIN vendors v ON p.vendor_id = v.id WHERE p.price BETWEEN ? AND ?";
$params = [$minPrice, $maxPrice];

if (!empty($selectedCategories)) {
    $placeholders = implode(',', array_fill(0, count($selectedCategories), '?'));
    $sql .= " AND p.category_id IN ($placeholders)";
    $params = array_merge($params, $selectedCategories);
}

// Apply sorting
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.id DESC";
        break;
    case 'popular':
    default:
        $sql .= " ORDER BY p.id ASC";
        break;
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LocalKart — Products</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --paper:#FBF8F1;
    --paper-alt:#F1E9DA;
    --ink:#23291D;
    --ink-soft:#565C4E;
    --line:#E3DBC8;
    --moss:#2F5233;
    --moss-dark:#20391F;
    --marigold:#E7A62F;
    --marigold-dark:#C98A1B;
    --brick:#A63D2F;
    --white:#FFFFFF;
    --radius-card:10px;
    --radius-pill:999px;
    --shadow-soft: 0 1px 2px rgba(35,41,29,0.06), 0 6px 16px rgba(35,41,29,0.05);
    --shadow-lift: 0 10px 28px rgba(35,41,29,0.12);
  }
  *{box-sizing:border-box;}
  body{ margin:0; background:var(--paper); color:var(--ink); font-family:'Inter',sans-serif; line-height:1.55; }
  h1,h2,h3,h4,.display{ font-family:'Fraunces',serif; color:var(--ink); margin:0; font-weight:600; letter-spacing:-0.01em; }
  p{margin:0;} a{color:inherit; text-decoration:none;} img{max-width:100%; display:block;}
  .wrap{max-width:1180px; margin:0 auto; padding:0 28px;}

  .page-head{ padding:40px 0 24px; }
  .page-head h1{ font-size:32px; }
  .page-head p{ color:var(--ink-soft); margin-top:6px; font-size:14.5px; }

  .products-layout{ display:grid; grid-template-columns:240px 1fr; gap:32px; max-width:1180px; margin:0 auto; padding:0 28px 70px; align-items:start; }
  .filter-box{ background:var(--white); border:1px solid var(--line); border-radius:var(--radius-card); padding:20px; opacity:0; animation:fadeIn .4s ease forwards; }
  .filter-box + .filter-box{ margin-top:16px; }
  .filter-box h5{ font-size:13px; font-weight:700; margin-bottom:14px; }
  .filter-row{ display:flex; align-items:center; gap:9px; font-size:13.5px; color:var(--ink-soft); padding:6px 0; cursor:pointer; }
  .filter-row input{ accent-color:var(--moss); }
  
  .price-inputs{ display:flex; gap:8px; align-items:center; margin-top:10px; }
  .price-inputs input{ width:100%; padding:6px 10px; border:1px solid var(--line); border-radius:6px; font:inherit; font-size:13px; background:var(--white); }
  
  .results-bar{ display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; font-size:13.5px; color:var(--ink-soft); }
  .sort-select{ border:1px solid var(--line); border-radius:8px; padding:8px 12px; font:inherit; background:var(--white); color:var(--ink); cursor:pointer; }

  /* Natural, subtle scrollable product container */
  .product-scroll-box {
    max-height: 640px;
    overflow-y: auto;
    padding-right: 8px;
    padding-bottom: 12px;
    padding-top: 4px;
    /* Soft, subtle scrollbar */
    scrollbar-width: thin;
    scrollbar-color: var(--line) transparent;
  }
  .product-scroll-box::-webkit-scrollbar {
    width: 5px;
  }
  .product-scroll-box::-webkit-scrollbar-track {
    background: transparent;
  }
  .product-scroll-box::-webkit-scrollbar-thumb {
    background: var(--line);
    border-radius: 999px;
  }
  .product-scroll-box::-webkit-scrollbar-thumb:hover {
    background: var(--ink-soft);
  }

  .product-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
  
  .product-card{
    background:var(--white); border:1px solid var(--line); border-radius:8px; overflow:hidden; position:relative;
    opacity:0; transform:translateY(16px);
    animation:riseIn .45s ease forwards;
    transition:transform .2s ease, box-shadow .2s ease;
  }
  .product-card:hover{ transform:translateY(-5px); box-shadow:var(--shadow-lift); }
  @keyframes riseIn{ to{ opacity:1; transform:translateY(0); } }
  @keyframes fadeIn{ to{ opacity:1; } }

  .product-img{ 
    width: 100%;
    aspect-ratio: 1 / 1; 
    overflow: hidden; 
    position: relative; 
    background: var(--paper-alt); 
  }
  .product-img img{ 
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
    object-position: center;
    transition: transform .4s ease; 
  }
  .product-card:hover .product-img img{ transform:scale(1.06); }
  
  .badge{ position:absolute; top:10px; left:10px; font-size:10.5px; font-weight:700; padding:4px 10px; border-radius:var(--radius-pill); background:var(--moss); color:#fff; z-index:2; }
  .badge.sale{ background:var(--brick); }
  
  .product-info{ padding:14px 16px 16px; }
  .product-info .store-name{ font-size:11.5px; color:var(--ink-soft); margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .product-info h4{ font-family:'Inter'; font-weight:600; font-size:14.5px; margin:0 0 6px; display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden; }
  .price-row{ display:flex; align-items:baseline; gap:8px; margin-top:8px; }
  .price{ font-family:'Fraunces',serif; font-size:19px; font-weight:600; color:var(--ink); }
  .add-row{ margin-top:12px; display:flex; gap:8px; }
  .add-row .btn{ flex:1; }
  .add-row .btn.added{ background:var(--marigold-dark); }

  .btn{ display:inline-flex; align-items:center; justify-content:center; gap:8px; border:none; cursor:pointer; font-family:inherit; font-weight:600; font-size:14px; padding:11px 22px; border-radius:var(--radius-pill); transition:background .15s, color .15s, transform .1s, box-shadow .15s; }
  .btn-primary{ background:var(--moss); color:#fff; }
  .btn-primary:hover{ background:var(--moss-dark); box-shadow:0 6px 16px rgba(47,82,51,.28); }
  .btn-sm{ padding:8px 16px; font-size:13px; }

  @media (max-width: 980px){
    .products-layout{ grid-template-columns:1fr; }
    .product-grid{ grid-template-columns:repeat(2,1fr); }
    .product-scroll-box { max-height: none; overflow-y: visible; }
  }
</style>
</head>
<body>

<?php include 'partials/header.php'; ?>

<div class="wrap page-head">
  <h1>All products</h1>
  <p>Browse listings from every store on LocalKart</p>
</div>

<div class="products-layout">
  <aside>
    <form method="GET" id="filterForm">
      <!-- Category Filter Box -->
      <div class="filter-box" style="animation-delay:.05s">
        <h5>Category</h5>
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <label class="filter-row">
                    <input type="checkbox" name="category_id[]" value="<?= $cat['id'] ?>" 
                        <?= in_array($cat['id'], $selectedCategories) ? 'checked' : '' ?> onchange="this.form.submit()">
                    <?= htmlspecialchars($cat['name']) ?>
                </label>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="font-size:13px; color:var(--ink-soft);">No categories found.</p>
        <?php endif; ?>
      </div>

      <!-- Price Range Filter Box -->
      <div class="filter-box" style="animation-delay:.1s">
        <h5>Price range (₹)</h5>
        <div class="price-inputs">
            <input type="number" name="min_price" value="<?= htmlspecialchars($minPrice) ?>" placeholder="Min" min="0">
            <span>-</span>
            <input type="number" name="max_price" value="<?= htmlspecialchars($maxPrice) ?>" placeholder="Max" min="0">
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="width:100%; margin-top:12px;">Apply Filter</button>
      </div>

      <!-- Preserve sort parameter during filter submission -->
      <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
    </form>
  </aside>

  <div>
    <div class="results-bar">
      <span>Showing <strong><?= count($products) ?></strong> results</span>
      <select class="sort-select" onchange="location = this.value;">
        <?php 
            $queryString = $_GET;
            unset($queryString['sort']);
            $baseQuery = http_build_query($queryString);
            $prefix = $baseQuery ? '?' . $baseQuery . '&sort=' : '?sort=';
        ?>
        <option value="<?= $prefix ?>popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Sort: Popular</option>
        <option value="<?= $prefix ?>price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
        <option value="<?= $prefix ?>price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
        <option value="<?= $prefix ?>newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
      </select>
    </div>
    
    <!-- Natural Scrollable Container for Products -->
    <div class="product-scroll-box">
      <div class="product-grid" id="grid">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $index => $product): ?>
                <?php 
                    $imagePath = "uploads/products/" . htmlspecialchars($product['image']);
                    $displayImage = (!empty($product['image']) && file_exists($imagePath)) ? $imagePath : "uploads/products/default.jpg";
                    $delay = $index * 0.04;
                ?>
                <div class="product-card" style="animation-delay:<?= $delay ?>s">
                  <div class="product-img">
                    <?php if (isset($product['stock']) && $product['stock'] > 0 && $product['stock'] < 5): ?>
                        <span class="badge sale">Low stock</span>
                    <?php elseif ($index === 0): ?>
                        <span class="badge">Local pick</span>
                    <?php endif; ?>
                    <img src="<?= $displayImage ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                  </div>
                  <div class="product-info">
                    <div class="store-name"><?= htmlspecialchars($product['store_name']) ?></div>
                    <h4><?= htmlspecialchars($product['name']) ?></h4>
                    <div class="price-row">
                      <span class="price">₹<?= number_format($product['price'], 2) ?></span>
                    </div>
                    <div class="add-row">
                      <a href="cart.php?action=add&id=<?= $product['id'] ?>" class="btn btn-primary btn-sm" onclick="addToCart(this)">Add to cart</a>
                    </div>
                  </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1/-1; color: var(--ink-soft); text-align: center; padding: 40px 0;">No products match your filter criteria.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include 'partials/footer.php'; ?>

<script>
  function addToCart(el) {
    const original = el.textContent;
    el.textContent = 'Added ✓';
    el.classList.add('added');
    setTimeout(() => { el.textContent = original; el.classList.remove('added'); }, 1100);
  }
</script>

</body>
</html>