<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection setup
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$db = 'crypto_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data
$sql = "SELECT Name FROM Users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get WalletID
$userID = $_SESSION['user_id'];
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

// Fetch crypto IDs and names from database
$placeholders = implode(',', array_fill(0, count($cryptoSymbols), '?'));
$sql = "SELECT CryptoID, Symbol, Name FROM Cryptocurrencies WHERE Symbol IN ($placeholders)";
$stmt = $conn->prepare($sql);
$types = str_repeat('s', count($cryptoSymbols));
$stmt->bind_param($types, ...$cryptoSymbols);
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

// Fetch current prices from CoinGecko API or use database values as fallback
$coinGeckoIds = [
    'BTC' => 'bitcoin',
    'ETH' => 'ethereum',
    'DOGE' => 'dogecoin'
];

$apiError = false;
try {
    $apiUrl = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,dogecoin&vs_currencies=usd';
    $response = file_get_contents($apiUrl);
    $priceData = json_decode($response, true);
    
    if ($priceData) {
        foreach ($cryptos as $symbol => &$cryptoData) {
            if (isset($coinGeckoIds[$symbol]) && isset($priceData[$coinGeckoIds[$symbol]])) {
                $cryptoData['MarketPrice'] = $priceData[$coinGeckoIds[$symbol]]['usd'];
            }
        }
        unset($cryptoData);
    }
} catch (Exception $e) {
    $apiError = true;
    $symbols = array_keys($cryptos);
    $placeholders = implode(',', array_fill(0, count($symbols), '?'));
    $sql = "SELECT Symbol, MarketPrice FROM Cryptocurrencies WHERE Symbol IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($symbols)), ...$symbols);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cryptos[$row['Symbol']]['MarketPrice'] = $row['MarketPrice'];
    }
    $stmt->close();
}

// Fetch amounts of these cryptos in user's wallet
$cryptoIDs = array_column($cryptos, 'CryptoID');
$holdings = [];
if (count($cryptoIDs) > 0) {
    $placeholders = implode(',', array_fill(0, count($cryptoIDs), '?'));
    $types = str_repeat('i', count($cryptoIDs) + 1);
    $sql = "SELECT CryptoID, amount FROM walletcryptos WHERE WalletID = ? AND CryptoID IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $params = array_merge([$walletID], $cryptoIDs);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $holdings[$row['CryptoID']] = $row['amount'];
    }
    $stmt->close();
}

// Prepare portfolio data with values
$portfolio = [];
$totalValue = 0;

foreach ($cryptos as $symbol => $data) {
    $cryptoID = $data['CryptoID'];
    $amount = isset($holdings[$cryptoID]) ? $holdings[$cryptoID] : 0;
    $value = $amount * $data['MarketPrice'];
    $portfolio[$symbol] = [
        'name' => $data['Name'],
        'amount' => $amount,
        'price' => $data['MarketPrice'],
        'value' => $value
    ];
    $totalValue += $value;
}

// Calculate % distribution for pie chart
$percentages = [];
foreach ($portfolio as $symbol => $data) {
    $percentages[$symbol] = $totalValue > 0 ? round(($data['value'] / $totalValue) * 100, 2) : 0;
}

// Prepare data for script.js
$labels = [];
$data = [];
$colors = [];
$displayCryptos = ['BTC', 'ETH', 'DOGE'];
$colorMap = [
    'BTC' => '#f7931a', // Bitcoin
    'ETH' => '#627eea', // Ethereum
    'DOGE' => '#c3a634' // Dogecoin
];

foreach ($displayCryptos as $symbol) {
    if (isset($percentages[$symbol]) && $percentages[$symbol] > 0) {
        $labels[] = $portfolio[$symbol]['name'];
        $data[] = $percentages[$symbol];
        $colors[] = $colorMap[$symbol];
    }
}

// Get portfolio performance (mock data)
$portfolioChange = 320.45;
$portfolioChangePercent = 3.8;
$isPositive = $portfolioChange >= 0;

// Fetch recent transactions
$sql = "SELECT t.TransactionID, c.Symbol, c.Name, t.Amount, t.Type, t.Timestamp
        FROM Transactions t
        JOIN Cryptocurrencies c ON t.CryptoID = c.CryptoID
        WHERE t.WalletID = ?
        ORDER BY t.Timestamp DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $walletID);
$stmt->execute();
$result = $stmt->get_result();
$recentTransactions = [];
while ($row = $result->fetch_assoc()) {
    $recentTransactions[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* Additional styling for Recent Transactions to match dashboard UI */
        .recent-transactions {
            margin-top: 30px;
        }

        .recent-transactions .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .recent-transactions .section-header h2 {
            font-size: 1.5rem;
            color: #333;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .recent-transactions .transactions-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow-x: auto;
        }

        .recent-transactions .transactions-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .recent-transactions .transactions-table th {
            text-align: left;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            color: #495057;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .recent-transactions .transactions-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #212529;
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .recent-transactions .transactions-table tr:last-child td {
            border-bottom: none;
        }

        .recent-transactions .crypto-symbol {
            font-weight: 600;
            color: #5dade2;
        }

        .recent-transactions .positive {
            color: #28a745;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .recent-transactions .negative {
            color: #dc3545;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .recent-transactions .neutral {
            color: #17a2b8;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .recent-transactions .btn-outline {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background-color: transparent;
            color: #555;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .recent-transactions .btn-outline:hover {
            background-color: #f5f7fa;
        }

        .recent-transactions .empty-message {
            text-align: center;
            margin-top: 40px;
            font-size: 1.1rem;
            color: #6c757d;
            font-weight: 500;
        }

        /* Responsive styling */
        @media (max-width: 768px) {
            .recent-transactions .transactions-table thead {
                display: none;
            }

            .recent-transactions .transactions-table tbody tr {
                display: block;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                margin-bottom: 10px;
                background-color: #fff;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            }

            .recent-transactions .transactions-table tbody td {
                display: flex;
                justify-content: space-between;
                text-align: right;
                border-bottom: 1px solid #e9ecef;
                padding: 10px 15px;
            }

            .recent-transactions .transactions-table tbody td:before {
                content: attr(data-label);
                float: left;
                font-weight: 600;
                color: #495057;
                width: 120px;
            }

            .recent-transactions .transactions-table tbody td:last-child {
                border-bottom: none;
            }
        }

        /* Portfolio Summary Styling */
        .portfolio-summary {
            margin-top: 30px;
        }

        .portfolio-value {
            padding: 20px;
        }

        .value-card {
            background-color: #6b5b95;
            color: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .value-card h3 {
            font-size: 1rem;
            margin: 0 0 10px;
            opacity: 0.8;
        }

        .value-card .value {
            font-size: 2rem;
            margin: 0;
            font-weight: 700;
        }

        .value-card .change {
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .portfolio-distribution {
            margin-top: 20px;
        }

        .distribution-chart {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow-x: auto;
            text-align: center;
        }

        #portfolioDistributionChart {
            width: 100%;
            height: 180px;
            position: relative;
        }

        .distribution-legend {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .color-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .color-dot.btc { background-color: #f7931a; } /* Bitcoin */
        .color-dot.eth { background-color: #627eea; } /* Ethereum */
        .color-dot.other { background-color: #c3a634; } /* Dogecoin */
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
                    <li class="active">
                        <a href="#" aria-current="page"><i class="fas fa-chart-line"></i> Dashboard</a>
                    </li>
                    <li>
                        <a href="portfolio.php"><i class="fas fa-wallet"></i> Portfolio</a>
                    </li>
                    <li>
                        <a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a>
                    </li>
                    <li>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    </li>
                    <li>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <img src="pratham-datta.png" alt="Profile Picture" />
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($user['Name']); ?></h4>
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
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search for cryptocurrencies..." aria-label="Search cryptocurrencies" />
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Funds
                    </button>
                </div>
            </header>

            <!-- Portfolio & Charts -->
            <div class="portfolio-chart-container">
                <section class="portfolio-summary">
                    <div class="section-header">
                        <h2>Your Portfolio</h2>
                        <button class="btn btn-outline btn-sm btn-refresh" aria-label="Refresh portfolio">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    
                    <?php if ($apiError): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Unable to fetch current crypto prices. Some values may be outdated.
                    </div>
                    <?php endif; ?>
                    
                    <div class="portfolio-value">
                        <div class="value-card">
                            <h3>Total Value</h3>
                            <p class="value">$<?php echo isset($totalValue) ? number_format($totalValue, 2) : '0.00'; ?></p>
                            <p class="change <?php echo $isPositive ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-caret-<?php echo $isPositive ? 'up' : 'down'; ?>"></i> 
                                $<?php echo number_format($portfolioChange, 2); ?> (<?php echo $portfolioChangePercent; ?>%)
                            </p>
                        </div>
                    </div>
                    <div class="portfolio-distribution">
                        <h3>Distribution</h3>
                        <div class="distribution-chart" id="portfolioDistribution">
                            <?php if ($totalValue <= 0): ?>
                                <div style="width: 100%; height: 180px; display: flex; align-items: center; justify-content: center; color: #888; font-size: 0.9rem;">
                                    No cryptocurrencies in your portfolio
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="distribution-legend">
                            <?php if ($totalValue > 0): ?>
                                <?php 
                                $colorClasses = ['btc', 'eth', 'other'];
                                
                                for ($i = 0; $i < count($displayCryptos); $i++) {
                                    $symbol = $displayCryptos[$i];
                                    if (isset($percentages[$symbol]) && $percentages[$symbol] > 0) {
                                        echo '<div class="legend-item">
                                            <span class="color-dot ' . $colorClasses[$i] . '"></span>
                                            <span>' . htmlspecialchars($portfolio[$symbol]['name']) . '</span>
                                            <span>' . $percentages[$symbol] . '%</span>
                                        </div>';
                                    }
                                }
                                ?>
                            <?php else: ?>
                                <p>No cryptocurrencies in your portfolio</p>
                            <?php endif; ?>
                        </div>
                        <!-- Hidden element to pass portfolio data to script.js -->
                        <div id="portfolioData"
                             style="display: none;"
                             data-labels='<?php echo json_encode($labels); ?>'
                             data-data='<?php echo json_encode($data); ?>'
                             data-colors='<?php echo json_encode($colors); ?>'></div>
                    </div>
                </section>

                <section class="chart-section">
                    <div class="section-header">
                        <div class="chart-selector">
                            <select id="cryptoSelector" aria-label="Select cryptocurrency">
                                <option value="">Loading cryptocurrencies...</option>
                            </select>
                            <p class="error-message" id="selectorError" style="display: none"></p>
                        </div>
                        <div class="time-filter">
                            <button class="active">24h</button>
                            <button>7d</button>
                            <button>30d</button>
                            <button>1y</button>
                        </div>
                    </div>
                    <div class="crypto-price-info">
                        <div class="price-header">
                            <img src="/assets/images/default-crypto.png" alt="Cryptocurrency logo" class="crypto-icon" />
                            <div>
                                <h3>Bitcoin</h3>
                                <span class="symbol">BTC</span>
                            </div>
                        </div>
                        <div class="price-value">
                            <h2>$67,245.32</h2>
                            <p class="change positive">
                                <i class="fas fa-caret-up"></i> 3.2%
                            </p>
                        </div>
                    </div>
                    <div class="price-chart-container">
                        <canvas id="priceChart"></canvas>
                    </div>
                    <div class="price-stats">
                        <div class="stat">
                            <span class="label">Market Cap</span>
                            <span class="value">$1.31T</span>
                        </div>
                        <div class="stat">
                            <span class="label">Volume (24h)</span>
                            <span class="value">$32.5B</span>
                        </div>
                        <div class="stat">
                            <span class="label">Circulating Supply</span>
                            <span class="value">19.38M BTC</span>
                        </div>
                        <div class="stat">
                            <span class="label">All-time High</span>
                            <span class="value">$69,045</span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Top Cryptocurrencies -->
            <section class="top-cryptos">
                <div class="section-header">
                    <h2>Top Cryptocurrencies</h2>
                    <button class="btn btn-outline btn-sm">View All</button>
                </div>
                <div class="crypto-table-container">
                    <table class="crypto-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>24h %</th>
                                <th>7d %</th>
                                <th>Market Cap</th>
                                <th>Volume (24h)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="cryptoTableBody">
                            <tr>
                                <td colspan="8" class="loading-data">
                                    <div class="loading-spinner"></div>
                                    <p>Loading cryptocurrency data...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Recent Transactions -->
            <section class="recent-transactions">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent Transactions</h2>
                    <a href="transactions.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="transactions-container">
                    <?php if (count($recentTransactions) === 0): ?>
                    <p class="empty-message">No recent transactions found.</p>
                    <?php else: ?>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Cryptocurrency</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $t): ?>
                            <tr>
                                <td data-label="Transaction ID"><?php echo htmlspecialchars($t['TransactionID']); ?></td>
                                <td data-label="Cryptocurrency">
                                    <span class="crypto-symbol"><?php echo htmlspecialchars($t['Symbol']); ?></span>
                                    <span> - <?php echo htmlspecialchars($t['Name']); ?></span>
                                </td>
                                <td data-label="Amount"><?php echo number_format($t['Amount'], 8); ?></td>
                                <td data-label="Type">
                                    <?php
                                    $typeClass = strtolower(trim($t['Type']));
                                    $class = '';
                                    $icon = '';
                                    if ($typeClass == 'buy') {
                                        $class = 'positive';
                                        $icon = '<i class="fas fa-arrow-down"></i>';
                                    } elseif ($typeClass == 'sell') {
                                        $class = 'negative';
                                        $icon = '<i class="fas fa-arrow-up"></i>';
                                    } else {
                                        $class = 'neutral';
                                        $icon = '<i class="fas fa-exchange-alt"></i>';
                                    }
                                    ?>
                                    <span class="<?php echo $class; ?>">
                                        <?php echo $icon . ' ' . ucfirst($t['Type']); ?>
                                    </span>
                                </td>
                                <td data-label="Date & Time">
                                    <?php
                                    $transactionTime = strtotime($t['Timestamp']);
                                    $currentTime = time();
                                    $timeDiff = abs($currentTime - $transactionTime); // Use abs to avoid negative values
                                    $timeAgo = '';
                                    if ($timeDiff < 60) {
                                        $timeAgo = $timeDiff . ' seconds ago';
                                    } elseif ($timeDiff < 3600) {
                                        $timeAgo = floor($timeDiff / 60) . ' mins ago';
                                    } elseif ($timeDiff < 86400) {
                                        $timeAgo = floor($timeDiff / 3600) . ' hours ago';
                                    } else {
                                        $timeAgo = floor($timeDiff / 86400) . ' days ago';
                                    }
                                    echo htmlspecialchars($timeAgo);
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal for Buy/Sell Actions -->
    <div class="modal" id="actionModal" style="display: none">
        <div class="modal-content">
            <h2 id="modalTitle">Action</h2>
            <p id="modalMessage">Please confirm your action.</p>
            <div class="modal-actions">
                <button class="btn btn-primary" id="modalConfirm">Confirm</button>
                <button class="btn btn-outline" id="modalCancel">Cancel</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="script.js"></script>
</body>
</html>