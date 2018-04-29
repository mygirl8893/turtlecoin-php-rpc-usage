<?php
session_start();

#Requirements
require '../vendor/autoload.php';
use chillerlan\QRCode\QRCode;

#Check conf
if (!isset($_SESSION["passconf"]) || !isset($_SESSION["ipconf"]) || !isset($_SESSION["portconf"])) {
  header('Location: login.php');
}

#Enable conf
use TurtleCoin\Walletd;

$config = [
    'rpcHost'     => $_SESSION["ipconf"],
    'rpcPort'     => intval($_SESSION["portconf"]),
    'rpcPassword' => $_SESSION["passconf"],
];
$walletd = new Walletd\Client($config);

#JSON request
$addrs = $walletd->getAddresses()->getBody()->getContents();
$vkey = $walletd->getViewKey()->getBody()->getContents();

#Decode
$decaddrs = json_decode($addrs, true);
$decvkey = json_decode($vkey, true);

#Wallet addresses out of array
$addresses = $decaddrs["result"]["addresses"];
$fcount = count($decaddrs["result"]["addresses"]);
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Your Addresses</title>
  </head>
  <body>
    <a href="index.php">Back</a>
<?php
#Check if showkeys is enabled
if (isset($_GET["showkeys"])) {
  echo '<form action="address.php" method="get">
    <input type="submit" value="Hide secret keys">
  </form>';
}
else {
#Show action to show secret keys
echo '<form action="address.php" method="get">
  Generate big qr code<input type="checkbox" name="sbqr">
  <input type="hidden" name="showkeys" value="true">
  <input type="submit" value="Show secret keys">
</form>';
}
for ($i=0; $i < $fcount; $i++) {
  #Output all addresses with balance and qrcode
  $bal = $walletd->getBalance($addresses[$i])->getBody()->getContents();
  $decbal = json_decode($bal, true);
  $balance = intval($decbal["result"]["availableBalance"]) / 100;
  $lbalance = intval($decbal["result"]["lockedAmount"]) / 100;
  #Check if keys should be shown
  if (isset($_GET["showkeys"])) {
    #Get spendkeys for each address and output them
    $spendkey = $walletd->getSpendKeys($addresses[$i])->getBody()->getContents();
    $decspendkey = json_decode($spendkey, true);
    echo "<br>Public address: " . $addresses[$i] . "<br>Balance: " . $balance . ", Locked: " . $lbalance . "<br>Public spend key: " . $decspendkey["result"]["spendPublicKey"] . "<br>Private spend key: " . $decspendkey["result"]["spendSecretKey"] . "<br>Private view key: " . $decvkey["result"]["viewSecretKey"];
    #Check if a qr code with all keys should be generated
    if (isset($_GET["sbqr"])) {
      $big = "pubaddr:" . $addresses[$i] . ";pubspend:" . $decspendkey["result"]["spendPublicKey"] . ";privspend:" . $decspendkey["result"]["spendSecretKey"] . ";privview:" . $decvkey["result"]["viewSecretKey"] . ";";
      echo '<img src="'.(new QRCode)->render($big).'" />';
    }
    else {
      echo '<br><img src="'.(new QRCode)->render($addresses[$i]).'" />';
    }
  }
  else {
    #Output without keys
    echo "<br>Public address: " . $addresses[$i] . "<br>Balance: " . $balance . ", Locked: " . $lbalance . "<br>" . '<img src="'.(new QRCode)->render($addresses[$i]).'" />';
  }
}
?>
  </body>
</html>
