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

// Using user 1
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

// Cryptos to display
$cryptoSymbols = ['BTC', 'ETH', 'DOGE'];

// Fetch crypto IDs and market prices for the selected symbols
$placeholders = implode(',', array_fill(0, count($cryptoSymbols), '?'));
$sql = "SELECT CryptoID, Symbol, MarketPrice FROM Cryptocurrencies WHERE Symbol IN ($placeholders)";
$stmt = $conn->prepare($sql);

// Bind parameters dynamically
$types = str_repeat('s', count($cryptoSymbols));
$stmt->bind_param($types, ...$cryptoSymbols);
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

// Fetch amounts of these cryptos in user's wallet from walletcryptos table
// walletcryptos table structure: id, walletid, cryptoid, amount
$cryptoIDs = array_column($cryptos, 'CryptoID');
if (count($cryptoIDs) > 0) {
    $placeholders = implode(',', array_fill(0, count($cryptoIDs), '?'));
    $types = str_repeat('i', count($cryptoIDs) + 1);
    $sql = "SELECT CryptoID, amount FROM walletcryptos WHERE walletid = ? AND CryptoID IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $params = array_merge([$walletID], $cryptoIDs);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $holdings = [];
    while ($row = $result->fetch_assoc()) {
        $holdings[$row['CryptoID']] = $row['amount'];
    }
    $stmt->close();
} else {
    $holdings = [];
}

$conn->close();

// Prepare portfolio data with values
$portfolio = [];
$totalValue = 0;

foreach ($cryptos as $symbol => $data) {
    $cryptoID = $data['CryptoID'];
    $amount = $holdings[$cryptoID] ?? 0;
    $value = $amount * $data['MarketPrice'];
    $portfolio[$symbol] = [
        'amount' => $amount,
        'value' => $value
    ];
    $totalValue += $value;
}

// Calculate % distribution for pie chart
$percentages = [];
foreach ($portfolio as $symbol => $data) {
    $percentages[$symbol] = $totalValue > 0 ? round(($data['value'] / $totalValue) * 100, 2) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>User 1 Crypto Portfolio</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
    justify-content: center;
  }

  .portfolio-list {
    flex: 1 1 400px;
    background: #1e1e1e;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(255, 179, 71, 0.25);
    padding: 20px 30px;
  }

  .portfolio-list h2 {
    color: #ffb347;
    margin-bottom: 20px;
    letter-spacing: 0.1em;
  }

  table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
  }

  thead tr {
    background-color: #121212;
  }

  thead th {
    padding: 10px;
    text-align: left;
    font-weight: 600;
    font-size: 1rem;
    color: #ffb347;
    letter-spacing: 0.05em;
  }

  tbody tr {
    background: #222;
    border-radius: 8px;
    box-shadow: inset 0 0 10px rgba(247,147,26,0.3);
  }

  tbody td {
    padding: 12px 15px;
    font-size: 1rem;
    vertical-align: middle;
    color: #ddd;
  }

  .symbol {
    font-weight: 700;
    font-size: 1.1rem;
    color: #f7931a;
    letter-spacing: 0.05em;
  }

  .value {
    font-weight: 600;
    color: #ffb347;
  }

  /* Chart container */
  .chart-container {
    flex: 1 1 400px;
    background: #1e1e1e;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(255, 179, 71, 0.25);
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .chart-container h2 {
    color: #ffb347;
    margin-bottom: 20px;
    letter-spacing: 0.1em;
  }

  /* Responsive */
  @media (max-width: 800px) {
    main {
      flex-direction: column;
      align-items: center;
    }

    .portfolio-list,
    .chart-container {
      flex: 1 1 100%;
      max-width: 450px;
    }
  }
</style>
</head>
<body>

<header>Crypto Portfolio - User 1</header>

<main>
  <section class="portfolio-list">
    <h2>Your Holdings</h2>
    <table>
      <thead>
        <tr>
          <th>Crypto</th>
          <th>Amount</th>
          <th>Value (USD)</th>
          <th>% of Portfolio</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($portfolio as $symbol => $data): ?>
          <tr>
            <td class="symbol"><?= htmlspecialchars($symbol) ?></td>
            <td><?= number_format($data['amount'], 8) ?></td>
            <td class="value">$<?= number_format($data['value'], 2) ?></td>
            <td><?= $percentages[$symbol] ?>%</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="border-top: 2px solid #ffb347; font-weight: 700;">
          <td>Total</td>
          <td></td>
          <td class="value">$<?= number_format($totalValue, 2) ?></td>
          <td>100%</td>
        </tr>
      </tfoot>
    </table>
  </section>

  <section class="chart-container">
    <h2>Portfolio Distribution</h2>
    <canvas id="portfolioChart" width="300" height="300"></canvas>
  </section>
</main>

<script>
  const ctx = document.getElementById('portfolioChart').getContext('2d');

  const data = {
    labels: <?= json_encode(array_keys($portfolio)) ?>,
    datasets: [{
      label: 'Portfolio %',
      data: <?= json_encode(array_values($percentages)) ?>,
      backgroundColor: [
        '#f7931a', // Bitcoin orange
        '#3c3c3d', // Ethereum gray
        '#c2a633'  // Dogecoin gold
      ],
      borderColor: '#121212',
      borderWidth: 2,
      hoverOffset: 20,
    }]
  };

  const options = {
    plugins: {
      legend: {
        labels: {
          color: '#ffb347',
          font: {
            size: 16,
            weight: '700'
          }
        }
      },
      tooltip: {
        enabled: true,
        backgroundColor: '#222',
        titleColor: '#ffb347',
        bodyColor: '#fff'
      }
    }
  };

  new Chart(ctx, {
    type: 'pie',
    data: data,
    options: options
  });
</script>

</body>
</html>
