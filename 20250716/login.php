<?php
session_start();
error_reporting(E_ALL);
include "./dbconnection.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_username = $_POST['email_or_username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email_or_username && $password) {
        $dbc = dbconnect();

        // 检查管理员表
        $stmt = $dbc->prepare("SELECT * FROM admin_user WHERE username = ? AND password = ?");
        $stmt->execute([$email_or_username, $password]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $_SESSION['admin'] = [
                'userID' => $admin['userID'],
                'username' => $admin['username']
            ];
            header("Location: admin_dashboard.php");
            exit;
        }

        // 检查普通用户表
        $stmt = $dbc->prepare("SELECT * FROM m_customer WHERE email = ? AND password = ?");
        $stmt->execute([$email_or_username, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user'] = [
                'customerID' => $user['customerID'],
                'name' => $user['name'],
                'email' => $user['email'],
                'address' => $user['address']
            ];
            header("Location: index.php");
            exit;
        }

        $error = "アカウント情報が正しくありません。";
    } else {
        $error = "全ての項目を入力してください。";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ログイン</title>
 
  <link rel="stylesheet" href="./css/login.css">
</head>
<body>
  <header id="header">
    <nav>
       <ul>
        <a href="./index.php"><li>Top</li></a>
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
  <div class="auth-container">
    <h1>ログイン</h1>
    <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
    <form action="" method="post" class="auth-form">
      <label for="email_or_username">メールアドレスまたはユーザー名</label>
      <input type="text" name="email_or_username" placeholder="example@example.com または admin" required>

      <label for="password">パスワード</label>
      <input type="password" name="password" placeholder="********" required>

      <button type="submit" class="btn">ログイン</button>
    </form>
    <p class="switch-page">新規の方はこちら → <a href="register.php">新規登録</a></p>
  </div>
</body>
</html>

