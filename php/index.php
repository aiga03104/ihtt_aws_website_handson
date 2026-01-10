<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <title>AWS Icons</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
  </head>
  <body>
  <?php
    require 'vendor/autoload.php';

    use Aws\SecretsManager\SecretsManagerClient;
    use Aws\Exception\AwsException;

    $client = new SecretsManagerClient([
        'version' => '2017-10-17',
        'region' => 'ap-northeast-1',
    ]);

    try {
        $result = $client->getSecretValue([
            'SecretId' => '<RDSのSecrets Manager ARN>',
        ]);
    } catch (AwsException $e) {
        throw $e;
    }

    $values = json_decode($result['SecretString'],true);

    $host = '<RDS Endpoint>';
    $dbname = 'aws_training_db';
    $username = $values['username'];
    $password = $values['password'];
    $charset = 'utf8mb4';

    try {
      // PDOで接続
      $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
      $options = [
          PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES   => false,
      ];

      $pdo = new PDO($dsn, $username, $password, $options);

      // 検索クエリ
      $sql = "SELECT * FROM icons";
      $stmt = $pdo->prepare($sql);
      $stmt->execute();
      $icons = $stmt->fetchAll();

      // // 結果を表示
      // while ($row = $stmt->fetch()) {
      //     echo "ID: " . $row['id'] . " | Title: " . $row['title'] . "<br>";
      // }

    } catch (PDOException $e) {
        echo "DB接続エラー: " . $e->getMessage();
    }
  ?>
    <h2 class="text-center mt-3 mb-5">AWS Icons</h2>
    <div class="container">

      <?php foreach ($icons as $icon) : ?>
      <div class="mb-5 row">
        <div class="col-sm-2 mb-2">
          <img src=<?php echo $icon["image"]; ?>></img>
        </div>
        <div class="col-sm-10">
          <h3><?php echo $icon["title"]; ?></h3>
        </div>
      </div>
      <?php endforeach; ?>

    </div>
  </body>
</html>
