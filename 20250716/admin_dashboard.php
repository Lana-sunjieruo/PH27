<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "./dbconnection.php";

// 检查管理员登录
if(!isset($_SESSION['admin'])){
    header("Location: logoin.php");
    exit;
}

$dbc = dbconnect();

// 上传文件函数
function uploadImage($file){
    if(isset($file) && $file['error'] === UPLOAD_ERR_OK){
        $uploadDir = __DIR__ . '/images/';
        if(!is_dir($uploadDir)){
            if(!mkdir($uploadDir, 0755, true)){
                die("画像保存用のフォルダが作成できません。");
            }
        }

        $fileName = time().'_'.basename($file['name']);
        $targetPath = $uploadDir . $fileName;

        if(move_uploaded_file($file['tmp_name'], $targetPath)){
            return 'images/' . $fileName;
        } else {
            die("画像のアップロードに失敗しました。");
        }
    }
    return null;
}

// === 后端操作 ===

// 修改变体库存/图片
if(isset($_POST['update_variant'])){
    $variantID = $_POST['variantID'];
    $stock = $_POST['stock'];
    $imageURL = $_POST['imageURL'];

    if(!empty($_FILES['imageFile']['name'])){
        $imageURL = uploadImage($_FILES['imageFile']);
    }

    $stmt = $dbc->prepare("UPDATE product_variants SET stock=?, imageURL=? WHERE variantID=?");
    $stmt->execute([$stock,$imageURL,$variantID]);
}
// === 商品更新 ===
if(isset($_POST['update_product'])){
    $productID = $_POST['productID'];
    $productName = $_POST['productName'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $size = $_POST['size'];

    $stmt = $dbc->prepare("UPDATE products SET productName=?, description=?, price=?, category=?, size=? WHERE productID=?");
    $stmt->execute([$productName, $description, $price, $category, $size, $productID]);
}

// 添加新变体
if(isset($_POST['add_variant'])){
    $productID = $_POST['existingProductID'];
    $color = $_POST['newColor'];
    $stock = $_POST['newStock'];
    $imageURL = uploadImage($_FILES['newImage']);

    $stmt = $dbc->prepare("INSERT INTO product_variants (productID, color, stock, imageURL) VALUES (?,?,?,?)");
    $stmt->execute([$productID,$color,$stock,$imageURL]);
}

// 删除变体
if(isset($_POST['delete_variant'])){
    $variantID = $_POST['variantID'];
    $stmt = $dbc->prepare("DELETE FROM product_variants WHERE variantID=?");
    $stmt->execute([$variantID]);
}

// 删除商品（及其所有变体）
if(isset($_POST['delete_product'])){
    $productID = $_POST['productID'];

    // 先删除变体
    $stmt = $dbc->prepare("DELETE FROM product_variants WHERE productID=?");
    $stmt->execute([$productID]);

    // 再删除商品
    $stmt = $dbc->prepare("DELETE FROM products WHERE productID=?");
    $stmt->execute([$productID]);
}

// 添加新商品
if(isset($_POST['add_product'])){
    $productName = $_POST['productName'] ?? '';
    $price = $_POST['price'] ?? 0;
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $size = $_POST['size'] ?? '';

    $variantColor = $_POST['variantColor'] ?? '';
    $variantStock = isset($_POST['variantStock']) ? (int)$_POST['variantStock'] : 0;
    $variantImageURL = isset($_FILES['variantImage']) ? uploadImage($_FILES['variantImage']) : '';

    if($productName && $price && $description && $category && $variantColor && $variantStock >= 0 && $variantImageURL){
        $stmt = $dbc->prepare("
            INSERT INTO products 
                (productName, description, price, category, size, created_at) 
            VALUES 
                (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$productName, $description, $price, $category, $size]);
        $productID = $dbc->lastInsertId();

        $stmt = $dbc->prepare("
            INSERT INTO product_variants 
                (productID, color, stock, imageURL) 
            VALUES 
                (?, ?, ?, ?)
        ");
        $stmt->execute([$productID, $variantColor, $variantStock, $variantImageURL]);

        echo "<p>新しい商品を追加しました。</p><a href='admin_dashboard.php'>管理ページに戻る</a>";
        exit;
    } else {
        echo "<p>全ての必須項目を入力してください。</p>";
    }
}

// 查询所有商品
$stmt = $dbc->prepare("SELECT * FROM products ORDER BY productID DESC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>管理者ページ</title>
<link rel="stylesheet" href="css/admin.css">
</head>
<body>

<header id="header">
    <nav>
        <ul>
           <a href="./index.php"><li>Top</li></a>
            <li>管理者: <?= htmlspecialchars($_SESSION['admin']['username']) ?></li>
            <a href="logout.php"><li>ログアウト</li></a>
        </ul>
    </nav>
</header>

<main>
<h1>管理者商品ページ</h1>

<?php foreach($products as $product): ?>
<div class="productbox">
    <h3><?= htmlspecialchars($product['productName']) ?> (カテゴリ: <?= htmlspecialchars($product['category']) ?>)</h3>
    <p>価格: <?= htmlspecialchars($product['price']) ?>円</p>
    <p><?= htmlspecialchars($product['description']) ?></p> 
    <hr class="line">
    <?php
        $stmt2 = $dbc->prepare("SELECT * FROM product_variants WHERE productID=?");
        $stmt2->execute([$product['productID']]);
        $variants = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php foreach($variants as $v): ?>
    <form class="variant-form" method="post" enctype="multipart/form-data">
        <span style="display:inline-block;width:30px;height:30px;background-color:<?= htmlspecialchars($v['color']) ?>;border:1px solid #333;border-radius:4px;"></span>
        <input type="number" name="stock" value="<?= htmlspecialchars($v['stock']) ?>" min="0" required>
        <input type="text" name="imageURL" value="<?= htmlspecialchars($v['imageURL']) ?>" placeholder="画像URL">
        <input type="file" name="imageFile" accept="image/*">
        <?php if(!empty($v['imageURL'])): ?>
            <img src="<?= htmlspecialchars($v['imageURL']) ?>" alt="サムネイル" style="width:50px;height:50px;object-fit:cover;border:1px solid #ccc;">
        <?php endif; ?>
        <input type="hidden" name="variantID" value="<?= $v['variantID'] ?>">
        <button type="submit" name="update_variant" class="btn-update">更新</button>
        <button type="submit" name="delete_variant" class="btn-delete" onclick="return confirm('この変体を削除しますか？');">削除</button>
    </form>
    <?php endforeach; ?>
     <!-- 商品情報を編集 -->
    <form method="post">
        <input type="hidden" name="productID" value="<?= $product['productID'] ?>">
        <input type="text" name="productName" value="<?= htmlspecialchars($product['productName']) ?>" required>
        <input type="number" name="price" value="<?= htmlspecialchars($product['price']) ?>" min="0" required>
        <textarea name="description" required><?= htmlspecialchars($product['description']) ?></textarea>
        <input type="text" name="category" value="<?= htmlspecialchars($product['category']) ?>" required>
        <input type="text" name="size" value="<?= htmlspecialchars($product['size']) ?>">
        <button type="submit" name="update_product">商品情報更新</button>
    </form>

    <!-- 添加新变体 -->
    <form method="post" enctype="multipart/form-data">
        <h4>新しい色を追加</h4>
        <input type="hidden" name="existingProductID" value="<?= $product['productID'] ?>">
        <input type="text" name="newColor" placeholder="色（英文）" required>
        <input type="number" name="newStock" placeholder="在庫" min="0" required>
        <input type="file" name="newImage" accept="image/*" required>
        <button type="submit" name="add_variant">追加</button>
    </form>

    <!-- 删除商品 -->
    <form method="post" onsubmit="return confirm('この商品と全ての変体を削除しますか？');">
        <input type="hidden" name="productID" value="<?= $product['productID'] ?>">
        <button class="alldelete" type="submit" name="delete_product">商品削除</button>
    </form>

</div>

<?php endforeach; ?>

<h2>新しい商品を追加</h2>
<div class="addproducts">
    <form method="post" enctype="multipart/form-data">
    <input type="text" name="productName" placeholder="商品名" required>
    <input type="number" name="price" placeholder="価格" min="0" required>
    <textarea name="description" placeholder="説明" required></textarea>
    <input type="text" name="category" placeholder="カテゴリー" required>
    <input type="text" name="size" placeholder="サイズ（例: S/M/L）">

    <h4>商品１</h4>
    <input type="text" name="variantColor" placeholder="色（英文）" required>
    <input type="number" name="variantStock" placeholder="在庫" min="0" required>
    <input type="file" name="variantImage" accept="image/*" required>

    <button type="submit" name="add_product">追加</button>
</form>
</div>


</main>
</body>
</html>
