<?php
// Database connection setup
$host = 'localhost';
$db = 'crypto_db';
$user = 'root';        // Change to your DB user
$pass = '';    // Change to your DB password

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Hardcoded user ID = 1
$userID = 1;

// Get WalletID
$stmt = $conn->prepare("SELECT WalletID FROM Wallets WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($walletID);
if (!$stmt->fetch()) {
    die("Wallet not found for user.");
}
$stmt->close();

// Fetch all transactions for this wallet
$sql = "SELECT t.TransactionID, c.Symbol, c.Name, t.Amount, t.Type, t.Timestamp
        FROM Transactions t
        JOIN Cryptocurrencies c ON t.CryptoID = c.CryptoID
        WHERE t.WalletID = ?
        ORDER BY t.Timestamp DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $walletID);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Crypto Transactions - User 1</title>
<style>
  /* Dark mode base */
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
    max-width: 900px;
    margin: 30px auto;
    padding: 0 20px 40px;
  }

  table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
  }

  thead tr {
    background-color: #1e1e1e;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(255, 179, 71, 0.25);
  }

  thead th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 1rem;
    color: #ffb347;
    letter-spacing: 0.05em;
  }

  tbody tr {
    background: #222;
    transition: background 0.3s ease;
    border-radius: 8px;
    box-shadow: inset 0 0 10px rgba(247,147,26,0.3);
  }

  tbody tr:hover {
    background: #333;
  }

  tbody td {
    padding: 12px 15px;
    font-size: 0.95rem;
    vertical-align: middle;
    color: #ddd;
  }

  .type-buy {
    color: #4caf50; /* Green for buy */
    font-weight: 600;
  }
  .type-sell {
    color: #e53935; /* Red for sell */
    font-weight: 600;
  }
  .type-transfer {
    color: #2196f3; /* Blue for transfer */
    font-weight: 600;
  }

  .symbol {
    font-weight: 700;
    font-size: 1.1rem;
    color: #f7931a; /* Bitcoin orange-ish */
    letter-spacing: 0.05em;
  }

  .timestamp {
    font-size: 0.85rem;
    color: #999;
    font-family: monospace;
  }

  /* Responsive */
  @media (max-width: 600px) {
    thead tr {
      display: none;
    }

    tbody tr {
      display: block;
      margin-bottom: 15px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(255, 179, 71, 0.2);
    }

    tbody td {
      display: flex;
      justify-content: space-between;
      padding: 10px 15px;
      font-size: 0.9rem;
      border-bottom: 1px solid #444;
    }

    tbody td:last-child {
      border-bottom: 0;
    }

    tbody td::before {
      content: attr(data-label);
      font-weight: 700;
      color: #ffb347;
      flex: 1;
    }
  }
</style>
</head>
<body>

<header>Crypto Transactions</header>

<main>
  <?php if (count($transactions) === 0): ?>
    <p style="text-align:center; margin-top:50px; font-size:1.2rem; color:#ffb347;">No transactions found.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Transaction ID</th>
        <th>Crypto</th>
        <th>Amount</th>
        <th>Type</th>
        <th>Date & Time</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($transactions as $t): ?>
        <tr>
          <td data-label="Transaction ID"><?= htmlspecialchars($t['TransactionID']) ?></td>
          <td data-label="Crypto" class="symbol"><?= htmlspecialchars($t['Symbol']) ?></td>
          <td data-label="Amount"><?= number_format($t['Amount'], 8) ?></td>
          <td data-label="Type" class="type-<?= htmlspecialchars($t['Type']) ?>"><?= ucfirst($t['Type']) ?></td>
          <td data-label="Date & Time" class="timestamp"><?= htmlspecialchars($t['Timestamp']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>

</body>
</html>
