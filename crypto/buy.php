<?php
// Show errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connect to your database
$mysqli = new mysqli('localhost', 'root', '', 'crypto_db');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Only one user for now
$UserID = 1;

// Get WalletID for user
$walletQuery = $mysqli->prepare("SELECT WalletID FROM Wallets WHERE UserID = ?");
$walletQuery->bind_param("i", $UserID);
$walletQuery->execute();
$walletResult = $walletQuery->get_result();
if ($walletResult->num_rows == 0) {
    die("No wallet found for the user.");
}
$walletRow = $walletResult->fetch_assoc();
$WalletID = $walletRow['WalletID'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cryptoName = $_POST['crypto'];
    $usdAmount = floatval($_POST['usdAmount']);
    $cryptoAmount = floatval($_POST['cryptoAmount']);

    // Find CryptoID
    $cryptoQuery = $mysqli->prepare("SELECT CryptoID FROM Cryptocurrencies WHERE Name = ?");
    $cryptoQuery->bind_param("s", $cryptoName);
    $cryptoQuery->execute();
    $cryptoResult = $cryptoQuery->get_result();
    if ($cryptoResult->num_rows == 0) {
        die("Cryptocurrency not found.");
    }
    $cryptoRow = $cryptoResult->fetch_assoc();
    $CryptoID = $cryptoRow['CryptoID'];

    // Insert into Transactions
    $transactionInsert = $mysqli->prepare("INSERT INTO Transactions (WalletID, CryptoID, Amount, Type) VALUES (?, ?, ?, 'buy')");
    $transactionInsert->bind_param("iid", $WalletID, $CryptoID, $cryptoAmount);
    $transactionInsert->execute();

    // Check if crypto already in WalletCryptos
    $walletCryptoCheck = $mysqli->prepare("SELECT Amount FROM WalletCryptos WHERE WalletID = ? AND CryptoID = ?");
    $walletCryptoCheck->bind_param("ii", $WalletID, $CryptoID);
    $walletCryptoCheck->execute();
    $walletCryptoResult = $walletCryptoCheck->get_result();

    if ($walletCryptoResult->num_rows > 0) {
        // Update amount
        $existing = $walletCryptoResult->fetch_assoc();
        $newAmount = $existing['Amount'] + $cryptoAmount;

        $updateWalletCrypto = $mysqli->prepare("UPDATE WalletCryptos SET Amount = ? WHERE WalletID = ? AND CryptoID = ?");
        $updateWalletCrypto->bind_param("dii", $newAmount, $WalletID, $CryptoID);
        $updateWalletCrypto->execute();
    } else {
        // Insert new
        $insertWalletCrypto = $mysqli->prepare("INSERT INTO WalletCryptos (WalletID, CryptoID, Amount) VALUES (?, ?, ?)");
        $insertWalletCrypto->bind_param("iid", $WalletID, $CryptoID, $cryptoAmount);
        $insertWalletCrypto->execute();
    }

    // Update Wallet balance (in USD)
    $updateWallet = $mysqli->prepare("UPDATE Wallets SET Balance = Balance + ? WHERE WalletID = ?");
    $updateWallet->bind_param("di", $usdAmount, $WalletID);
    $updateWallet->execute();

    $successMessage = "<div class='alert alert-success'>Purchase successful! Bought $cryptoAmount $cryptoName for $$usdAmount.</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CryptoTrack - Buy Crypto</title>
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
        .buy-crypto-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }
        .buy-crypto-container h2 {
            color: #333;
            margin-bottom: 24px;
            font-size: 1.5rem;
        }
        .buy-form {
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
            <li class="active">
              <a href="#" aria-current="page">
                <i class="fas fa-shopping-cart"></i> Buy Crypto
              </a>
            </li>
            <li>
              <a href="sell.php">
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
            <a href="sell.php" class="btn btn-primary">
              <i class="fas fa-exchange-alt"></i> Sell Crypto
            </a>
          </div>
        </header>

        <!-- Buy Crypto Section -->
        <div class="buy-crypto-container">
          <h2><i class="fas fa-shopping-cart"></i> Buy Cryptocurrency</h2>
          
          <?php if (isset($successMessage)) echo $successMessage; ?>
          
          <form class="buy-form" method="POST" action="">
            <div class="form-group">
              <label for="cryptoSelect">Select Cryptocurrency</label>
              <select id="cryptoSelect" name="crypto" class="form-control" required>
                <option value="" selected disabled>Choose a cryptocurrency</option>
                <option value="Bitcoin" data-api-id="bitcoin">Bitcoin (BTC)</option>
                <option value="Ethereum" data-api-id="ethereum">Ethereum (ETH)</option>
                <option value="Dogecoin" data-api-id="dogecoin">Dogecoin (DOGE)</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="usdAmount">USD Amount</label>
              <input type="number" step="0.01" name="usdAmount" id="usdAmount" class="form-control" placeholder="Enter USD amount" required>
            </div>
            
            <div class="price-display">
              <p>Current Price: <span id="currentPrice" class="price">-</span></p>
              <p>You will receive: <span id="cryptoAmount" class="price">-</span></p>
            </div>
            
            <input type="hidden" name="cryptoAmount" id="cryptoAmountHidden">
            
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-check-circle"></i> Complete Purchase
            </button>
          </form>
        </div>
      </main>
    </div>

    <script>
    // Fetch prices from CoinGecko API
    let prices = {};

    async function fetchPrices() {
        try {
            const response = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,dogecoin&vs_currencies=usd');
            prices = await response.json();
            console.log('Prices fetched:', prices);
        } catch (error) {
            console.error('Error fetching crypto prices:', error);
        }
    }

    fetchPrices(); // call immediately on page load

    // Update crypto amount when USD amount changes
    document.getElementById('usdAmount').addEventListener('input', calculateCryptoAmount);
    document.getElementById('cryptoSelect').addEventListener('change', calculateCryptoAmount);

    function calculateCryptoAmount() {
        const usd = parseFloat(document.getElementById('usdAmount').value);
        const selected = document.getElementById('cryptoSelect');
        const selectedOption = selected.options[selected.selectedIndex];
        
        if (!selectedOption || selectedOption.disabled) {
            document.getElementById('currentPrice').textContent = '-';
            document.getElementById('cryptoAmount').textContent = '-';
            return;
        }
        
        const apiId = selectedOption.getAttribute('data-api-id');
        const cryptoName = selectedOption.text;

        if (usd > 0 && prices[apiId]) {
            const cryptoPrice = prices[apiId].usd;
            document.getElementById('currentPrice').textContent = `$${cryptoPrice.toLocaleString()}`;
            
            const cryptoAmount = usd / cryptoPrice;
            document.getElementById('cryptoAmount').textContent = `${cryptoAmount.toFixed(8)} ${cryptoName.split(' ')[1].replace('(', '').replace(')', '')}`;
            document.getElementById('cryptoAmountHidden').value = cryptoAmount.toFixed(8);
        } else {
            document.getElementById('currentPrice').textContent = prices[apiId] ? `$${prices[apiId].usd.toLocaleString()}` : '-';
            document.getElementById('cryptoAmount').textContent = '-';
        }
    }

    // Toggle sidebar on mobile
    document.querySelector('.sidebar-toggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show-sidebar');
    });
    </script>
</body>
</html>