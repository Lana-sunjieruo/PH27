<?php
include "./dbconnection.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $address = $_POST['address'] ?? '';

    if ($name && $email && $password && $address) {
        $dbc = dbconnect();

        // 检查邮箱是否已存在
        $stmt = $dbc->prepare("SELECT * FROM m_customer WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $error = "このメールアドレスは既に登録されています。";
        } else {

            // 插入数据库
           
            $stmt = $dbc->prepare("INSERT INTO m_customer (name, email, password, address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $password, $address]);

            $success = "登録が完了しました。<a href='login.php'>ログイン</a>";
        }
    } else {
        $error = "全ての項目を入力してください。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>新規登録</title>
  <link rel="stylesheet" href="./css/register.css">
</head>
<body>
  <header id="header">
    <nav>
       <ul>
        <a href="./index.php"><li>Top</li></a>
        <a href="./cart.php"><li>Cart</li></a>
        <a href="login.php"><li>Login</li></a>
       </ul>
    </nav>
</header>
  <div class="auth-container">
    <h1>新規会員登録</h1>

    <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if($success) echo "<p style='color:green;'>$success</p>"; ?>

    <form action="" method="post" class="auth-form">
      <label for="name">お名前</label>
      <input type="text" name="name" placeholder="例: 山田太郎" required>

      <label for="email">メールアドレス</label>
      <input type="email" name="email" placeholder="example@example.com" required>

      <label for="password">パスワード</label>
      <input type="password" name="password" placeholder="********" required>

      <label for="address">住所</label>
      <input type="text" name="address" placeholder="例: 東京都千代田区" required>

      <button type="submit" class="btn">登録する</button>
    </form>

    <p class="switch-page">すでにアカウントをお持ちの方 → <a href="login.php">ログイン</a></p>
  </div>
</body>
</html>
