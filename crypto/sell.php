<?php
// sell.php

// Database connection
$host = 'localhost';
$db = 'crypto_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Only one user for now
$UserID = 1;

// Initialize messages
$message = '';
$error = '';

// Get WalletID
$walletQuery = $conn->prepare("SELECT WalletID FROM Wallets WHERE UserID = ?");
$walletQuery->bind_param("i", $UserID);
$walletQuery->execute();
$walletResult = $walletQuery->get_result();
if ($walletResult->num_rows == 0) {
    die("No wallet found for the user.");
}
$walletRow = $walletResult->fetch_assoc();
$WalletID = $walletRow['WalletID'];

// Allowed cryptos - make sure symbols are correctly ordered
$allowedCryptos = ['BTC', 'ETH', 'DOGE'];

// Get crypto data - make sure query fetches correct data
$placeholders = implode(',', array_fill(0, count($allowedCryptos), '?'));
$sql = "SELECT CryptoID, Name, Symbol FROM Cryptocurrencies WHERE Symbol IN ($placeholders)";
$stmt = $conn->prepare($sql);
$types = str_repeat('s', count($allowedCryptos));
$stmt->bind_param($types, ...$allowedCryptos);
$stmt->execute();
$result = $stmt->get_result();

$cryptos = [];
while ($row = $result->fetch_assoc()) {
    $cryptos[$row['Symbol']] = [
        'CryptoID' => $row['CryptoID'],
        'Name' => $row['Name'],
        'MarketPrice' => 0
    ];
}
$stmt->close();

// CoinGecko API with proper mapping
$coinGeckoIds = [
    'BTC' => 'bitcoin',
    'ETH' => 'ethereum',
    'DOGE' => 'dogecoin'
];

try {
    $apiUrl = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,dogecoin&vs_currencies=usd';
    $response = @file_get_contents($apiUrl);

    if ($response === FALSE) {
        throw new Exception("API request failed.");
    }

    $priceData = json_decode($response, true);

    if (!$priceData) {
        throw new Exception("Failed to decode API response.");
    }

    foreach ($cryptos as $symbol => $cryptoData) {
      if (isset($coinGeckoIds[$symbol]) && isset($priceData[$coinGeckoIds[$symbol]])) {
          $cryptos[$symbol]['MarketPrice'] = $priceData[$coinGeckoIds[$symbol]]['usd'];
      }
  
  
    }
} catch (Exception $e) {
    $error = "Failed to fetch current crypto prices. Please try again later.";
}

// Selling logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sellSymbol = $_POST['crypto'] ?? '';
    $sellAmount = floatval($_POST['amount'] ?? 0);

    if (!in_array($sellSymbol, $allowedCryptos)) {
        $error = "Invalid cryptocurrency selected.";
    } elseif ($sellAmount <= 0) {
        $error = "Please enter a valid amount to sell.";
    } elseif (empty($cryptos[$sellSymbol]['MarketPrice'])) {
        $error = "Cannot sell right now because price data is unavailable.";
    } else {
        $cryptoID = $cryptos[$sellSymbol]['CryptoID'];
        $marketPrice = $cryptos[$sellSymbol]['MarketPrice'];

        // Check balance
        $stmt = $conn->prepare("SELECT amount FROM walletcryptos WHERE walletid = ? AND CryptoID = ?");
        $stmt->bind_param("ii", $WalletID, $cryptoID);
        $stmt->execute();
        $stmt->bind_result($currentAmount);
        if (!$stmt->fetch()) {
            $currentAmount = 0;
        }
        $stmt->close();

        if ($sellAmount > $currentAmount) {
            $error = "Insufficient $sellSymbol balance to sell.";
        } else {
            $saleValue = $sellAmount * $marketPrice;

            $conn->begin_transaction();
            try {
                // Update walletcryptos
                $newAmount = $currentAmount - $sellAmount;
                $stmt = $conn->prepare("UPDATE walletcryptos SET amount = ? WHERE walletid = ? AND CryptoID = ?");
                $stmt->bind_param("dii", $newAmount, $WalletID, $cryptoID);
                $stmt->execute();
                $stmt->close();

                // Update Wallet balance
                $stmt = $conn->prepare("UPDATE Wallets SET Balance = Balance + ? WHERE WalletID = ?");
                $stmt->bind_param("di", $saleValue, $WalletID);
                $stmt->execute();
                $stmt->close();

                // Record Transaction
                $stmt = $conn->prepare("INSERT INTO Transactions (WalletID, CryptoID, Amount, Type) VALUES (?, ?, ?, 'sell')");
                $stmt->bind_param("iid", $WalletID, $cryptoID, $sellAmount);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "Successfully sold " . htmlspecialchars($sellAmount) . " " . htmlspecialchars($sellSymbol) . " for $" . number_format($saleValue, 2);
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Transaction failed: " . $e->getMessage();
            }
        }
    }
}

// Check for each cryptocurrency
foreach ($allowedCryptos as $symbol) {
    if (!isset($cryptos[$symbol])) {
        $error = "Missing cryptocurrency data. Please check database setup.";
        break;
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CryptoTrack - Sell Crypto</title>
    <link rel="stylesheet" href="styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <style>
        /* Add additional styles to match dashboard */
        .main-content {
            background-color: #f5f7fa;
            padding: 30px;
        }
        .sell-crypto-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }
        .sell-crypto-container h2 {
            color: #333;
            margin-bottom: 24px;
            font-size: 1.5rem;
        }
        .sell-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }
        .form-control {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #5dade2;
            outline: none;
            box-shadow: 0 0 0 2px rgba(93, 173, 226, 0.2);
        }
        .btn {
            padding: 12px 16px;
            font-size: 1rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-primary {
            background-color: #5dade2;
            color: white;
        }
        .btn-primary:hover {
            background-color: #3498db;
        }
        .price-display {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .price-display p {
            margin: 0;
            font-size: 0.9rem;
            color: #555;
        }
        .price-display .price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
      <!-- Sidebar -->
      <aside class="sidebar">
        <div class="sidebar-header">
          <a href="dashboard.php" style="text-decoration: none">
            <h1><i class="fas fa-coins"></i> CryptoTrack</h1>
          </a>
        </div>
        <nav class="sidebar-nav">
          <ul>
            <li>
              <a href="dashboard.php">
                <i class="fas fa-chart-line"></i> Dashboard
              </a>
            </li>
            <li>
              <a href="portfolio.php">
                <i class="fas fa-wallet"></i> Portfolio
              </a>
            </li>
            <li>
              <a href="buy.php">
                <i class="fas fa-shopping-cart"></i> Buy Crypto
              </a>
            </li>
            <li class="active">
              <a href="#" aria-current="page">
                <i class="fas fa-exchange-alt"></i> Sell Crypto
              </a>
            </li>
            <li>
              <a href="transactions.php">
                <i class="fas fa-history"></i> Transactions
              </a>
            </li>
            <li>
              <a href="settings.php">
                <i class="fas fa-cog"></i> Settings
              </a>
            </li>
          </ul>
        </nav>
        <div class="sidebar-footer">
          <div class="user-profile">
          <img
              src="/assets/images/default-profile.png"
              alt="Profile Picture"
            />
            <div class="user-info">
              <h4>John Doe</h4>
              <p>Premium User</p>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="main-content">
        <header class="main-header">
          <button class="sidebar-toggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
          </button>
          <div class="header-actions">
            <button class="btn btn-outline" aria-label="Notifications">
              <i class="fas fa-bell"></i>
            </button>
            <a href="buy.php" class="btn btn-primary">
              <i class="fas fa-shopping-cart"></i> Buy Crypto
            </a>
          </div>
        </header>

        <!-- Sell Crypto Section -->
        <div class="sell-crypto-container">
          <h2><i class="fas fa-exchange-alt"></i> Sell Cryptocurrency</h2>
          
          <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>
          
          <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          
          <form class="sell-form" method="POST" action="">
            <div class="form-group">
              <label for="crypto">Select Cryptocurrency</label>
              <select id="crypto" name="crypto" class="form-control" required>
                <option value="" selected disabled>Choose a cryptocurrency</option>
                <?php foreach ($allowedCryptos as $symbol): ?>
                  <?php if (isset($cryptos[$symbol])): ?>
                    <option value="<?= htmlspecialchars($symbol) ?>"><?= htmlspecialchars($cryptos[$symbol]['Name']) ?> (<?= htmlspecialchars($symbol) ?>)</option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label for="amount">Amount to Sell</label>
              <input type="number" step="0.00000001" min="0" name="amount" id="amount" class="form-control" placeholder="Enter amount to sell" required>
            </div>
            
            <div class="price-display">
              <p>Current Price: <span id="currentPrice" class="price">-</span></p>
              <p>You will receive: <span id="usdAmount" class="price">-</span></p>
            </div>
            
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-check-circle"></i> Complete Sale
            </button>
          </form>
        </div>
      </main>
    </div>

    <script>
    // Store crypto prices
    const cryptoPrices = {
        <?php foreach ($cryptos as $symbol => $data): ?>
        '<?= $symbol ?>': <?= $data['MarketPrice'] ?: 0 ?>,
        <?php endforeach; ?>
    };

    // Update USD amount when crypto amount changes
    document.getElementById('amount').addEventListener('input', calculateUsdAmount);
    document.getElementById('crypto').addEventListener('change', calculateUsdAmount);

    function calculateUsdAmount() {
        const amount = parseFloat(document.getElementById('amount').value);
        const selected = document.getElementById('crypto');
        const selectedOption = selected.options[selected.selectedIndex];
        
        if (!selectedOption || selectedOption.disabled) {
            document.getElementById('currentPrice').textContent = '-';
            document.getElementById('usdAmount').textContent = '-';
            return;
        }
        
        const symbol = selectedOption.value;

        if (amount > 0 && cryptoPrices[symbol]) {
            const cryptoPrice = cryptoPrices[symbol];
            document.getElementById('currentPrice').textContent = `$${cryptoPrice.toLocaleString()}`;
            
            const usdValue = amount * cryptoPrice;
            document.getElementById('usdAmount').textContent = `$${usdValue.toFixed(2)}`;
        } else {
            document.getElementById('currentPrice').textContent = cryptoPrices[symbol] ? `$${cryptoPrices[symbol].toLocaleString()}` : '-';
            document.getElementById('usdAmount').textContent = '-';
        }
    }

    // Toggle sidebar on mobile
    document.querySelector('.sidebar-toggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show-sidebar');
    });
    </script>
</body>
</html>