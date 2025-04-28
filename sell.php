<?php
// sell.php

// Database connection
$host = 'localhost';
$db = 'crypto_db';
$user = 'root';        // Update these
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userID = 1;  // Using user 1

// Fetch WalletID for user
$stmt = $conn->prepare("SELECT WalletID FROM Wallets WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($walletID);
if (!$stmt->fetch()) {
    die("Wallet not found for user.");
}
$stmt->close();

// Allowed cryptos
$allowedCryptos = ['BTC', 'ETH', 'DOGE'];

// Fetch crypto IDs & current prices for allowed cryptos
$placeholders = implode(',', array_fill(0, count($allowedCryptos), '?'));
$sql = "SELECT CryptoID, Symbol, MarketPrice FROM Cryptocurrencies WHERE Symbol IN ($placeholders)";
$stmt = $conn->prepare($sql);
$types = str_repeat('s', count($allowedCryptos));
$stmt->bind_param($types, ...$allowedCryptos);
$stmt->execute();
$result = $stmt->get_result();

$cryptos = [];
while ($row = $result->fetch_assoc()) {
    $cryptos[$row['Symbol']] = [
        'CryptoID' => $row['CryptoID'],
        'MarketPrice' => $row['MarketPrice']
    ];
}
$stmt->close();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sellSymbol = $_POST['crypto'] ?? '';
    $sellAmount = floatval($_POST['amount'] ?? 0);

    if (!in_array($sellSymbol, $allowedCryptos)) {
        $error = "Invalid cryptocurrency selected.";
    } elseif ($sellAmount <= 0) {
        $error = "Please enter a valid amount to sell.";
    } else {
        $cryptoID = $cryptos[$sellSymbol]['CryptoID'];
        $marketPrice = $cryptos[$sellSymbol]['MarketPrice'];

        // Check user's crypto balance in walletcryptos
        $stmt = $conn->prepare("SELECT amount FROM walletcryptos WHERE walletid = ? AND CryptoID = ?");
        $stmt->bind_param("ii", $walletID, $cryptoID);
        $stmt->execute();
        $stmt->bind_result($currentAmount);
        if (!$stmt->fetch()) {
            $currentAmount = 0;
        }
        $stmt->close();

        if ($sellAmount > $currentAmount) {
            $error = "Insufficient $sellSymbol balance to sell.";
        } else {
            // Calculate sale value in USD
            $saleValue = $sellAmount * $marketPrice;

            // Begin transaction
            $conn->begin_transaction();

            try {
                // 1. Subtract sold amount from walletcryptos
                $newAmount = $currentAmount - $sellAmount;
                $stmt = $conn->prepare("UPDATE walletcryptos SET amount = ? WHERE walletid = ? AND CryptoID = ?");
                $stmt->bind_param("dii", $newAmount, $walletID, $cryptoID);
                $stmt->execute();
                $stmt->close();

                // 2. Add saleValue to wallet balance
                $stmt = $conn->prepare("UPDATE Wallets SET Balance = Balance + ? WHERE WalletID = ?");
                $stmt->bind_param("di", $saleValue, $walletID);
                $stmt->execute();
                $stmt->close();

                // 3. Insert transaction of type 'sell'
                $stmt = $conn->prepare("INSERT INTO Transactions (WalletID, CryptoID, Amount, Type) VALUES (?, ?, ?, 'sell')");
                $stmt->bind_param("iid", $walletID, $cryptoID, $sellAmount);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "Successfully sold $sellAmount $sellSymbol for $" . number_format($saleValue, 2);

            } catch (Exception $e) {
                $conn->rollback();
                $error = "Transaction failed: " . $e->getMessage();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Sell Crypto - User 1</title>
<style>
  body {
    margin: 0;
    background: #121212;
    color: #e0e0e0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }
  header {
    background: linear-gradient(90deg, #f7931a, #ffb347);
    padding: 20px 30px;
    text-align: center;
    font-weight: 700;
    font-size: 2rem;
    color: #121212;
    letter-spacing: 2px;
    text-transform: uppercase;
    box-shadow: 0 3px 10px rgba(247,147,26,0.6);
  }
  main {
    max-width: 450px;
    margin: 40px auto;
    background: #1e1e1e;
    padding: 30px 25px;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(255, 179, 71, 0.25);
  }
  h1 {
    margin-bottom: 25px;
    color: #ffb347;
    letter-spacing: 0.1em;
  }
  label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #f7931a;
  }
  select, input[type="number"] {
    width: 100%;
    padding: 10px 15px;
    margin-bottom: 5px;
    border-radius: 8px;
    border: none;
    font-size: 1rem;
    background: #121212;
    color: #e0e0e0;
    box-shadow: inset 0 0 10px rgba(247,147,26,0.3);
  }
  button {
    width: 100%;
    background: #f7931a;
    color: #121212;
    font-weight: 700;
    font-size: 1.2rem;
    padding: 12px 0;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
  }
  button:hover {
    background: #ffb347;
  }
  .message {
    margin-bottom: 20px;
    padding: 12px 15px;
    border-radius: 8px;
  }
  .success {
    background: #2d662d;
    color: #b3f5b3;
  }
  .error {
    background: #662d2d;
    color: #f5b3b3;
  }
  #valueDisplay {
    margin-bottom: 20px;
    font-weight: 600;
    color: #f7931a;
    font-size: 1.1rem;
  }
</style>
</head>
<body>

<header>Sell Cryptocurrency - User 1</header>

<main>
  <h1>Sell Your Crypto</h1>

  <?php if ($message): ?>
    <div class="message success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="message error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" action="" id="sellForm">
    <label for="crypto">Select Cryptocurrency</label>
    <select name="crypto" id="crypto" required>
      <option value="" disabled selected>Choose a crypto</option>
      <?php foreach ($cryptos as $symbol => $data): ?>
        <option value="<?= htmlspecialchars($symbol) ?>"><?= htmlspecialchars($symbol) ?></option>
      <?php endforeach; ?>
    </select>

    <label for="amount">Amount to Sell</label>
    <input type="number" step="0.00000001" min="0" name="amount" id="amount" placeholder="Enter amount" required />

    <div id="valueDisplay">Value: $0.00</div>

    <button type="submit">Sell</button>
  </form>
</main>

<script>
  const cryptos = <?= json_encode(array_map(fn($c) => floatval($c['MarketPrice']), $cryptos)) ?>;
  const cryptoSelect = document.getElementById('crypto');
  const amountInput = document.getElementById('amount');
  const valueDisplay = document.getElementById('valueDisplay');

  function updateValue() {
    const symbol = cryptoSelect.value;
    const amount = parseFloat(amountInput.value);
    if (symbol && !isNaN(amount) && amount > 0) {
      const price = cryptos[symbol];
      const total = amount * price;
      valueDisplay.textContent = `Value: $${total.toFixed(2)}`;
    } else {
      valueDisplay.textContent = 'Value: $0.00';
    }
  }

  cryptoSelect.addEventListener('change', updateValue);
  amountInput.addEventListener('input', updateValue);
</script>

</body>
</html>
