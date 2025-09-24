<?php
session_start();
$user = $_SESSION['user'] ?? null;
include "./dbconnection.php";

// --- ログイン確認 ---
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$dbc = dbconnect();
$user_id = $_SESSION['user']['customerID']; // セッションから customerID を取得

// --- カート情報取得 ---
$sql = "
    SELECT c.cartID, c.quantity, 
           p.productName, p.price, v.imageURL, 
           v.color, v.variantID, v.stock
    FROM cart c
    JOIN products p ON c.productID = p.productID
    JOIN product_variants v ON c.variantID = v.variantID
    WHERE c.customerID = ?
";
$stmt = $dbc->prepare($sql);
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_price = 0;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ショッピングカート</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
<header id="header">
    <nav>
       <ul>
        <a href="./index.php"><li>Top</li></a>
        <a href="./cart.php"><li>Cart</li></a>
         <?php if($user): ?>
        <!-- ログイン中のユーザー名を表示 -->
        <li style="color: #7e298b;">ようこそ、<?= htmlspecialchars($user['name']) ?> さん</li>
        <a href="logout.php"><li>ログアウト</li></a>
        <?php else: ?>
          <a href="login.php"><li>Login</li></a>
        <?php endif; ?>
        <!-- 管理者ログイン中なら管理者ページリンクを表示 -->
        <?php if(isset($_SESSION['admin'])): ?>
          <a href="admin_dashboard.php"><li>管理者ページに戻る</li></a>
        <?php endif; ?>
       </ul>
    </nav>
</header>
<main>
    <h1>ショッピングカート</h1>

    <?php if (empty($cart_items)): ?>
        <p>カートは空です。</p>
    <?php else: ?>
        <?php foreach ($cart_items as $item): ?>
            <div class="cart-item">
                <img src="<?= htmlspecialchars($item['imageURL'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <div>
                    <p><?= htmlspecialchars($item['productName'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>カラー：<?= htmlspecialchars($item['color'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>数量：<?= $item['quantity']; ?></p>
                    <p>在庫残り：<?= $item['stock']; ?></p>
                    <p>小計：<?= $item['price'] * $item['quantity']; ?>円</p>
                </div>

                <!-- 数量更新フォーム -->
                <form action="update_cart.php" method="post">
                    <input type="hidden" name="cartID" value="<?= $item['cartID'] ?>">
                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" required>
                    <button type="submit" name="update" value="1">更新</button>
                </form>

                <!-- 削除フォーム -->
                <form action="update_cart.php" method="post" style="margin-top:5px;">
                    <input type="hidden" name="cartID" value="<?= $item['cartID'] ?>">
                    <button type="submit" name="delete" value="1" onclick="return confirm('この商品をカートから削除しますか？');">削除</button>
                </form>
            </div>
            <?php $total_price += $item['price'] * $item['quantity']; ?>
        <?php endforeach; ?>

        <h2>合計金額：<?= $total_price; ?>円</h2>
        <a href="checkout.php">購入手続きへ</a>
    <?php endif; ?>
</main>
</body>
</html>
