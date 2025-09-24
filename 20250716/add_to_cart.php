<?php
session_start();
/*var_dump($_POST);
exit;*/
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "./dbconnection.php";

// --- 未ログインの場合 ---
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$dbc = dbconnect();
$customerID = $_SESSION['user']['customerID'];

// --- フォームから送信されたデータ ---
$productID = $_POST['productID'] ?? null;
$variantID = $_POST['variantID'] ?? null;
$quantity = (int)($_POST['quantity'] ?? 1);

if (!$productID || !$variantID) {
    die("商品情報が不足しています。");
}

// --- 在庫確認 ---
$sql = "SELECT stock FROM product_variants WHERE variantID = ? AND productID = ?";
$stmt = $dbc->prepare($sql);
$stmt->execute([$variantID, $productID]);
$stock = $stmt->fetchColumn();

if ($stock === false) {
    die("この商品またはカラーは存在しません。");
}

if ($stock < $quantity) {
    die("在庫が足りません。");
}

// --- すでに同じ商品+カラーがカートにあるか確認 ---
$sql = "SELECT cartID, quantity FROM cart WHERE customerID = ? AND productID = ? AND variantID = ?";
$stmt = $dbc->prepare($sql);
$stmt->execute([$customerID, $productID, $variantID]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // 数量を更新
    $new_qty = $existing['quantity'] + $quantity;
    $sql = "UPDATE cart SET quantity = ? WHERE cartID = ?";
    $stmt = $dbc->prepare($sql);
    $stmt->execute([$new_qty, $existing['cartID']]);
} else {
    // 新規追加
    $sql = "INSERT INTO cart (customerID, productID, variantID, quantity, added_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $dbc->prepare($sql);
    $stmt->execute([$customerID, $productID, $variantID, $quantity]);
}

// --- 在庫を減らす ---
$sql = "UPDATE product_variants SET stock = stock - ? WHERE variantID = ?";
$stmt = $dbc->prepare($sql);
$stmt->execute([$quantity, $variantID]);

// カートページにリダイレクト
header("Location: cart.php");
exit;
?>
