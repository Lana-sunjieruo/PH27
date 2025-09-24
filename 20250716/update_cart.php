<?php
session_start();
include "./dbconnection.php";
$dbc = dbconnect();

// --- 削除処理 ---
if (isset($_POST['delete'])) {
    $cartID = $_POST['cartID'] ?? null;

    if ($cartID) {
        // 削除する商品の情報を取得
        $sql = "SELECT variantID, quantity FROM cart WHERE cartID = ?";
        $stmt = $dbc->prepare($sql);
        $stmt->execute([$cartID]);
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cartItem) {
            $variantID = $cartItem['variantID'];
            $quantity = $cartItem['quantity'];

            // 在庫を戻す
            $sql = "UPDATE product_variants SET stock = stock + ? WHERE variantID = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->execute([$quantity, $variantID]);

            // カートから削除
            $sql = "DELETE FROM cart WHERE cartID = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->execute([$cartID]);
        }
    }
}

// --- 数量更新処理 ---
if (isset($_POST['update'])) {
    $cartID = $_POST['cartID'] ?? null;
    $newQuantity = (int)($_POST['quantity'] ?? 1);

    if ($cartID && $newQuantity > 0) {
        // 現在の数量と variantID を取得
        $sql = "SELECT variantID, quantity FROM cart WHERE cartID = ?";
        $stmt = $dbc->prepare($sql);
        $stmt->execute([$cartID]);
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cartItem) {
            $variantID = $cartItem['variantID'];
            $oldQuantity = $cartItem['quantity'];

            // 数量差分を計算
            $diff = $newQuantity - $oldQuantity;

            // 在庫を調整（差分が正の場合は在庫を減らす、負の場合は在庫を戻す）
            $sql = "UPDATE product_variants SET stock = stock - ? WHERE variantID = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->execute([$diff, $variantID]);

            // カート数量を更新
            $sql = "UPDATE cart SET quantity = ? WHERE cartID = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->execute([$newQuantity, $cartID]);
        }
    }
}

// --- カートページにリダイレクト ---
header("Location: cart.php");
exit;
