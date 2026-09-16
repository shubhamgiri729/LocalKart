<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid store ID.');
}

$storeId = (int) $_GET['id'];
$isCustomer = isLoggedIn() && getUserRole() === 'customer';

$stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->execute([$storeId]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    die('Store not found.');
}

if ($isCustomer && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    $rating = (int) $_POST['rating'];

    if ($rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare(
            "INSERT INTO vendor_ratings (vendor_id, user_id, rating)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = NOW()"
        );
        $stmt->execute([$storeId, $_SESSION['user_id'], $rating]);
    }

    header("Location: store.php?id=$storeId");
    exit();
}

$stmt = $pdo->prepare(
    "SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS total
     FROM vendor_ratings WHERE vendor_id = ?"
);
$stmt->execute([$storeId]);
$ratingStats = $stmt->fetch(PDO::FETCH_ASSOC);

$myRating = 0;
if ($isCustomer) {
    $stmt = $pdo->prepare("SELECT rating FROM vendor_ratings WHERE vendor_id = ? AND user_id = ?");
    $stmt->execute([$storeId, $_SESSION['user_id']]);
    $myRating = (int) ($stmt->fetchColumn() ?: 0);
}

$stmt = $pdo->prepare("SELECT id, name, price, stock, image FROM products WHERE vendor_id = ?");
$stmt->execute([$storeId]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($store['store_name']) ?> — LocalKart</title>
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

  /* store banner */
  .store-banner{
    background:var(--moss-dark); color:#EFE9D8; padding:46px 0 40px;
    opacity:0; animation:fadeDown .5s ease forwards;
  }
  @keyframes fadeDown{ from{ opacity:0; transform:translateY(-10px);} to{ opacity:1; transform:translateY(0);} }
  .store-banner-inner{ display:flex; align-items:center; gap:22px; flex-wrap:wrap; }
  .store-avatar{
    width:76px; height:76px; border-radius:16px; background:rgba(255,255,255,.1);
    display:flex; align-items:center; justify-content:center; font-size:2.1rem; flex-shrink:0;
    border:1px solid rgba(255,255,255,.15);
  }
  .store-banner h1{ color:#fff; font-size:28px; }
  .store-meta{ display:flex; align-items:center; gap:14px; margin-top:8px; font-size:13.5px; color:#C9C4B2; flex-wrap:wrap; }
  .store-meta .stars{ color:var(--marigold); }
  .store-banner-actions{ margin-left:auto; display:flex; gap:10px; }

  /* rating */
  .rate-card{
    background:var(--white); border:1px solid var(--line); border-radius:var(--radius-card);
    box-shadow:var(--shadow-soft); padding:18px 22px; margin:28px 0 8px;
    display:flex; align-items:center; gap:18px; flex-wrap:wrap;
  }
  .rate-card p{ font-size:13.5px; color:var(--ink-soft); font-weight:600; }
  .star-input{ display:inline-flex; flex-direction:row-reverse; gap:2px; }
  .star-input input{ display:none; }
  .star-input label{ font-size:26px; color:var(--line); cursor:pointer; transition:color .15s, transform .1s; }
  .star-input input:checked ~ label,
  .star-input label:hover,
  .star-input label:hover ~ label{ color:var(--marigold); }
  .star-input label:hover{ transform:scale(1.15); }

  .section{ padding:44px 0 70px; }
  .results-bar{ display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; font-size:13.5px; color:var(--ink-soft); }
  .sort-select{ border:1px solid var(--line); border-radius:8px; padding:8px 12px; font:inherit; background:var(--white); color:var(--ink); cursor:pointer; }

  .product-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:22px; }
  .product-card{
    background:var(--white); border:1px solid var(--line); border-radius:8px; overflow:hidden; position:relative;
    opacity:0; transform:translateY(16px);
    animation:riseIn .45s ease forwards;
    transition:transform .2s ease, box-shadow .2s ease;
  }
  .product-card:hover{ transform:translateY(-5px); box-shadow:var(--shadow-lift); }
  @keyframes riseIn{ to{ opacity:1; transform:translateY(0); } }

  .product-img{ aspect-ratio:1/1; overflow:hidden; position:relative; background:var(--paper-alt); }
  .product-img img{ width:100%; height:100%; object-fit:cover; transition:transform .4s ease; }
  .product-card:hover .product-img img{ transform:scale(1.06); }
  .badge{ position:absolute; top:10px; left:10px; font-size:10.5px; font-weight:700; padding:4px 10px; border-radius:var(--radius-pill); background:var(--moss); color:#fff; }
  .product-info{ padding:14px 16px 16px; }
  .product-info h4{ font-family:'Inter'; font-weight:600; font-size:14.5px; margin:0 0 6px; }
  .price-row{ display:flex; align-items:baseline; gap:8px; margin-top:8px; }
  .price{ font-family:'Fraunces',serif; font-size:19px; font-weight:600; color:var(--ink); }
  .add-row{ margin-top:12px; display:flex; gap:8px; }
  .add-row .btn{ flex:1; }
  .add-row .btn.added{ background:var(--marigold-dark); }

  .btn{ display:inline-flex; align-items:center; justify-content:center; gap:8px; border:none; cursor:pointer; font-family:inherit; font-weight:600; font-size:14px; padding:11px 22px; border-radius:var(--radius-pill); transition:background .15s, color .15s, transform .1s, box-shadow .15s; }
  .btn-primary{ background:var(--moss); color:#fff; }
  .btn-primary:hover{ background:var(--moss-dark); box-shadow:0 6px 16px rgba(47,82,51,.28); }
  .btn-outline{ background:transparent; color:#fff; border:1.5px solid rgba(255,255,255,.5); }
  .btn-outline:hover{ border-color:#fff; background:rgba(255,255,255,.08); }
  .btn-sm{ padding:8px 16px; font-size:13px; }

  @media (max-width: 980px){
    .product-grid{ grid-template-columns:repeat(2,1fr); }
    .store-banner-actions{ margin-left:0; width:100%; }
  }
</style>
</head>
<body>

<?php include 'partials/header.php'; ?>

<div class="store-banner">
  <div class="wrap store-banner-inner">
    <div class="store-avatar">🥦</div>
    <div>
      <h1><?= htmlspecialchars($store['store_name']) ?></h1>
      <div class="store-meta">
        <?php if ($ratingStats['total'] > 0): ?>
          <span class="stars">★</span>
          <span><?= $ratingStats['avg_rating'] ?> (<?= $ratingStats['total'] ?> rating<?= $ratingStats['total'] == 1 ? '' : 's' ?>)</span>
        <?php else: ?>
          <span>No ratings yet</span>
        <?php endif; ?>
        <span>·</span>
        <span>Verified Local Vendor</span>
      </div>
    </div>
    <div class="store-banner-actions">
      <a href="helpdesk.php?subject=Vendor%20account%20or%20storefront%20issue" class="btn btn-outline btn-sm">Message store</a>
    </div>
  </div>
</div>

<div class="wrap section">
  <?php if ($isCustomer): ?>
    <div class="rate-card">
      <p><?= $myRating ? 'Update your rating' : 'Rate this store' ?></p>
      <form method="POST" style="display:flex; align-items:center; gap:14px;">
        <div class="star-input">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $myRating === $i ? 'checked' : '' ?>>
            <label for="star<?= $i ?>">★</label>
          <?php endfor; ?>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= $myRating ? 'Update' : 'Submit' ?></button>
      </form>
    </div>
  <?php endif; ?>

  <div class="results-bar">
    <span><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?> from this store</span>
    <select class="sort-select">
      <option>Sort: Popular</option>
      <option>Price: low to high</option>
      <option>Newest</option>
    </select>
  </div>
  
  <div class="product-grid" id="grid">
    <?php if (!empty($products)): ?>
        <?php foreach ($products as $index => $product): ?>
            <?php 
                $imagePath = "uploads/products/" . htmlspecialchars($product['image']);
                $displayImage = (!empty($product['image']) && file_exists($imagePath)) ? $imagePath : "uploads/products/default.jpg";
                $delay = $index * 0.06;
            ?>
            <div class="product-card" style="animation-delay:<?= $delay ?>s">
              <div class="product-img">
                <?php if (isset($product['stock']) && $product['stock'] > 0 && $product['stock'] < 5): ?>
                    <span class="badge">Low stock</span>
                <?php elseif ($index === 0): ?>
                    <span class="badge">Local pick</span>
                <?php endif; ?>
                <img src="<?= $displayImage ?>" alt="<?= htmlspecialchars($product['name']) ?>">
              </div>
              <div class="product-info">
                <h4><?= htmlspecialchars($product['name']) ?></h4>
                <div class="price-row"><span class="price">₹<?= number_format($product['price'], 2) ?></span></div>
                <div class="add-row">
                  <a href="cart.php?action=add&id=<?= $product['id'] ?>" class="btn btn-primary btn-sm" onclick="addToCart(this)">Add to cart</a>
                </div>
              </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="grid-column: 1/-1; color: var(--ink-soft); text-align: center; padding: 40px 0;">No products available in this store yet.</p>
    <?php endif; ?>
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