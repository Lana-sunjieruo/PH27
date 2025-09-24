<?php
function dbconnect()
{
    //$host = "localhost"; //MySQLに接続する場合
    $host = "127.0.0.1"; //xamppのMariaDBに接続する場合
    $dbname = "shopping";
    $username = "root";
    $password = "";
    try {
        //PDOを用いて、データベースに接続する
        //PHPにおけるデータベース接続方法：現在は、「mysqli」と「PDO」の2種類ある。
        $PDO = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        //エラー発生時に例外を投げる設定
        $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $PDO;
    } catch (PDOException $e) {
        print "接続失敗：" . $e->getMessage();
    }
}

function dbDisconnect($stmt, $dbc)
{
    $stmt = null;
    $dbc = null;
    $msg = "DB切断完了";
    return $msg;
}