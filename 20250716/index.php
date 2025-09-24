<?php
session_start();

include "./dbconnection.php";
$dbc = dbconnect();

// 外层查询：取得所有商品
$sql = "SELECT * FROM products";
$stmt = $dbc->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取会话中用户信息
$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>商品一覧</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header id="header">
    <nav>
       <ul>
        <a href="#"><li>Top</li></a>
        <a href="./cart.php"><li>Cart</li></a>
         <?php if($user): ?>
        <!-- 用户已登录显示名字 -->
        <li style="color: #7e298b;">ようこそ、<?= htmlspecialchars($user['name']) ?> さん</li>
        <a href="logout.php"><li>ログアウト</li></a>
        <?php else: ?>
          <a href="login.php"><li>Login</li></a>
        <?php endif; ?>
        <!-- 如果管理员已登录，显示返回后台 -->
        <?php if(isset($_SESSION['admin'])): ?>
          <a href="admin_dashboard.php"><li>管理者ページに戻る</li></a>
        <?php endif; ?>
       </ul>
    </nav>
</header>

<main>

  <div class="backlogo"><img src="./images/backlogo.jpg" alt="backlogo" width="200"></div>
    <h1>商品一覧</h1>
  <div class="product-list">
    <?php foreach ($products as $product): ?>
      <div class="productbox">
        <?php
          // 查询变体
          $stmt2 = $dbc->prepare("SELECT variantID, color, imageURL, stock FROM product_variants WHERE productID = ?");
          $stmt2->execute([$product["productID"]]);
          $variants = $stmt2->fetchAll(PDO::FETCH_ASSOC);

          // 默认变体（第一个）
          $defaultVariant = $variants[0] ?? null;
        ?>

        <!-- 主图 -->
        <img class="product-image" src="<?= htmlspecialchars($defaultVariant["imageURL"] ?? $product["imageURL"]) ?>" width="200">

        <h2><?= htmlspecialchars($product["productName"]) ?></h2>
        <p class="pricetext"><?= htmlspecialchars($product["price"]) ?>円</p>
        <p class="text"><?= htmlspecialchars($product["description"]) ?></p>

        <p class="stock-info">在庫：<span><?= htmlspecialchars($defaultVariant["stock"] ?? "--") ?></span></p>

        <!-- 颜色选择 -->
        <div class="color">
          <?php foreach ($variants as $index => $v): ?>
            <?php $isDefault = ($index === 0); ?>
            <span class="color-box <?= $isDefault ? 'active' : '' ?>"
                  data-variant-id="<?= $v['variantID'] ?>"
                  data-image="<?= htmlspecialchars($v["imageURL"]) ?>"
                  data-stock="<?= htmlspecialchars($v["stock"]) ?>"
                  style="background-color: <?= htmlspecialchars($v["color"]) ?>;">
            </span>
          <?php endforeach; ?>
        </div>

        <!-- 加入购物车表单 -->
        <?php if ($user && $defaultVariant): ?>
          <form action="add_to_cart.php" method="post" class="cart-form">
            <input type="hidden" name="productID" value="<?= htmlspecialchars($product["productID"]) ?>">
            <input type="hidden" name="variantID" value="<?= $defaultVariant['variantID'] ?>" class="variant-id-field">
            <input type="number" name="quantity" value="1" min="1" required>
            <button type="submit">カートに入れる</button>
          </form>
        <?php elseif (!$user): ?>
          <button class="cart-button" onclick="alert('ログインしてください');">カートに入れる</button>
        <?php endif; ?>

      </div>
    <?php endforeach; ?>
  </div>
</main>

<!-- JS：颜色选择更新隐藏字段和库存/图片 -->
<script src="./js/index.js"></script>

</body>
</html>


