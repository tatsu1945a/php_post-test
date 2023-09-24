<?php 

  // ローカル環境変数読み込み
  require __DIR__ . '/vendor/autoload.php';
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();
  
  //echo $_ENV['DB_USER']; 
  //echo $_ENV['DB_PASS'];

  // タイムゾーンセット
  date_default_timezone_set("Asia/Tokyo");

  //環境変数初期化
  $comment_array = array();
  $dbh = null;
  $stmt = null;
  $error_message = array();
  $escaped = array();
  $resu = null;
  $success_msg = null;


  // DB接続
  try {
    $dbh = new PDO('mysql:host=localhost;dbname=bbs-test', $_ENV['DB_USER'], $_ENV['DB_PASS']);
  } catch (PDOException $e) {
    echo $e->getMessage();
  }

  // フォームへ入力したときの動作
  if (!empty($_POST["submitButton"])) {

    // 名前のチェック
    if (empty($_POST["username"])){
      echo "名前を入力ください";
      $error_msg["username"] = "名前を入力ください";
    } else {
      $escaped["username"] = htmlspecialchars($_POST["username"], ENT_QUOTES,"UTF-8");
    } 

    if (empty($_POST["comment"])){
      echo "コメントを入力ください";
      $error_msg["comment"] = "コメントを入力ください";
    } else {
      $escaped["comment"] = htmlspecialchars($_POST["comment"], ENT_QUOTES,"UTF-8");
    }

    if (empty($error_msg)) {
      $postDate = date("Y-m-d H:i:s");

      // DBトランザクション開始
      $dbh->beginTransaction();

      try {
        // SQL文
        $stmt = $dbh->prepare("INSERT INTO `bbs-table` (`username`, `comment`, `postDate`) VALUES (:username, :comment, :postDate)");
        
        // 値をセット
        $stmt->bindParam(':username', $escaped["username"], PDO::PARAM_STR);
        $stmt->bindParam(':comment', $escaped["comment"], PDO::PARAM_STR);
        $stmt->bindParam(':postDate', $postDate, PDO::PARAM_STR);
    
        // SQLクエリ実行
        //$stmt->execute();
        $resu = $stmt->execute();

        // 問題なければコミット
        $resu = $dbh->commit();
        //$_POST = array();

      } catch (PDOException $e) {
        // エラー時は、ロールバック
        $resu = $dbh->rollback();
        echo $e->getMessage();
      }

      if ($resu) {
        $success_msg = "コメント書き込み成功";
      } else {
        $error_msg[] = "コメント書き込み失敗";
      }

      $stmt = null;      
      
      header('Location: ./');
		  exit;

    }

    
  }


  // DBからコメントデータを取得
  $sth = "SELECT `id`, `username`, `comment`, `postDate` FROM `bbs-table`;";
  $comment_array = $dbh->query($sth);

  // DBの接続をClose
  $dbh = null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PHP掲示板テスト</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1 class="title">PHP掲示板テスト</h1>
  <hr>
  <div class="boardWrapper">
    <!-- メッセージ送信成功時 -->
    <?php if (!empty($success_msg)) : ?>
      <p class="success_message"><?php echo $success_msg; ?></p>
    <?php endif; ?>

    <section>
      <?php foreach($comment_array as $comment) : ?>
        <article>
          <div class="wrapper">
            <div class="nameArea">
              <sapn>名前：</sapn>
              <p class="username"><?php echo $comment["username"] ?></p>
              <time><?php echo $comment["postDate"] ?></time>
            </div>
            <p class="comment"><?php echo $comment["comment"] ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
    <form class="formWrapper" method="POST">
      <div>
        <input type="submit" value="書き込む" name="submitButton">
        <label for="">名前：</label>
        <input type="text" name="username">
      </div>
      <div>
        <textarea class="commentTextArea" name="comment"></textarea>
      </div>
    </form>
  </div>
</body>
</html>