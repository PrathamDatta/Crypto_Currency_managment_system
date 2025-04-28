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

    echo "<p style='color:green; text-align:center;'>Purchase successful!</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buy Crypto</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 400px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        select, input[type="number"], button {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }

        select:focus, input:focus {
            outline: none;
            border-color: #5dade2;
        }

        button {
            background: #5dade2;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #3498db;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Buy Cryptocurrency</h2>
    <form method="POST" action="">
        <label>Select Cryptocurrency:</label>
        <select id="cryptoSelect" name="crypto" required>
            <option value="Bitcoin" data-api-id="bitcoin">Bitcoin (BTC)</option>
            <option value="Ethereum" data-api-id="ethereum">Ethereum (ETH)</option>
            <option value="Dogecoin" data-api-id="dogecoin">Dogecoin (DOGE)</option>
        </select>

        <label>USD Amount:</label>
        <input type="number" step="0.01" name="usdAmount" id="usdAmount" required>

        <label>Crypto Amount (auto-calculated):</label>
        <input type="number" step="0.00000001" name="cryptoAmount" id="cryptoAmount" readonly required>

        <button type="submit">Buy</button>
    </form>
</div>

<script>
// Fetch prices from CoinGecko API
let prices = {};

async function fetchPrices() {
    const response = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,dogecoin&vs_currencies=usd');
    prices = await response.json();
}

fetchPrices(); // call immediately on page load

// Update crypto amount when USD amount changes
document.getElementById('usdAmount').addEventListener('input', calculateCryptoAmount);
document.getElementById('cryptoSelect').addEventListener('change', calculateCryptoAmount);

function calculateCryptoAmount() {
    const usd = parseFloat(document.getElementById('usdAmount').value);
    const selected = document.getElementById('cryptoSelect');
    const apiId = selected.options[selected.selectedIndex].getAttribute('data-api-id');

    if (usd > 0 && prices[apiId]) {
        const cryptoPrice = prices[apiId].usd;
        const cryptoAmount = usd / cryptoPrice;
        document.getElementById('cryptoAmount').value = cryptoAmount.toFixed(8);
    } else {
        document.getElementById('cryptoAmount').value = '';
    }
}
</script>

</body>
</html>
