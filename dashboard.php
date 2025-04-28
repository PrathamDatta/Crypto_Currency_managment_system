<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$db = "crypto_db";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);

$cryptos = ['Bitcoin', 'Dogecoin', 'Ethereum'];
$ids = array_map('strtolower', $cryptos);
$apiUrl = "https://api.coingecko.com/api/v3/simple/price?ids=" . implode(',', $ids) . "&vs_currencies=inr";

$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

foreach ($cryptos as $crypto) {
    $key = strtolower($crypto);
    if (isset($data[$key]['inr'])) {
        $price = $data[$key]['inr'];

        $stmt = $conn->prepare("UPDATE Cryptocurrencies SET MarketPrice = ? WHERE Name = ?");
        $stmt->bind_param("ds", $price, $crypto);

        if ($stmt->execute()) {
            
        } else {
           
        }

        $stmt->close();
    } else {
        
    }
}
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CryptoTrack - Live Dashboard</title>
    <link rel="stylesheet" href="styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>
  <body>
    <div class="container">
      <!-- Sidebar -->
      <aside class="sidebar">
        <div class="sidebar-header">
          <a href="dashboard.php" style="text-decoration: none"
            ><h1><i class="fas fa-coins"></i> CryptoTrack</h1></a
          >
        </div>
        <nav class="sidebar-nav">
          <ul>
            <li class="active">
              <a href="#" aria-current="page"
                ><i class="fas fa-chart-line"></i> Dashboard</a
              >
            </li>
            <li>
              <a href="portfolio.php"
                ><i class="fas fa-wallet"></i> Portfolio</a
              >
            </li>
            <li>
              <a href="transactions.php"
                ><i class="fas fa-exchange-alt"></i> Transactions</a
              >
            </li>
            <li>
              <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
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
          <div class="search-bar">
            <i class="fas fa-search"></i>
            <input
              type="text"
              placeholder="Search for cryptocurrencies..."
              aria-label="Search cryptocurrencies"
            />
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
              <button
                class="btn btn-outline btn-sm btn-refresh"
                aria-label="Refresh portfolio"
              >
                <i class="fas fa-sync-alt"></i> Refresh
              </button>
            </div>
            <div class="portfolio-value">
              <div class="value-card">
                <h3>Total Value</h3>
                <p class="value">$24,830.65</p>
                <p class="change positive">
                  <i class="fas fa-caret-up"></i> $456.20 (1.8%)
                </p>
              </div>
            </div>
            <div class="portfolio-distribution">
              <h3>Distribution</h3>
              <div class="distribution-chart" id="portfolioDistribution"></div>
              <div class="distribution-legend">
                <div class="legend-item">
                  <span class="color-dot btc"></span><span>Bitcoin</span
                  ><span>45%</span>
                </div>
                <div class="legend-item">
                  <span class="color-dot eth"></span><span>Ethereum</span
                  ><span>30%</span>
                </div>
                <div class="legend-item">
                  <span class="color-dot sol"></span><span>Solana</span
                  ><span>15%</span>
                </div>
                <div class="legend-item">
                  <span class="color-dot other"></span><span>Others</span
                  ><span>10%</span>
                </div>
              </div>
            </div>
          </section>

          <section class="chart-section">
            <div class="section-header">
              <div class="chart-selector">
                <select id="cryptoSelector" aria-label="Select cryptocurrency">
                  <option value="">Loading cryptocurrencies...</option>
                </select>
                <p
                  class="error-message"
                  id="selectorError"
                  style="display: none"
                ></p>
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
                <img
                  src="/assets/images/default-crypto.png"
                  alt="Cryptocurrency logo"
                  class="crypto-icon"
                />
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
                <span class="label">Market Cap</span
                ><span class="value">$1.31T</span>
              </div>
              <div class="stat">
                <span class="label">Volume (24h)</span
                ><span class="value">$32.5B</span>
              </div>
              <div class="stat">
                <span class="label">Circulating Supply</span
                ><span class="value">19.38M BTC</span>
              </div>
              <div class="stat">
                <span class="label">All-time High</span
                ><span class="value">$69,045</span>
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
            <h2>Recent Transactions</h2>
            <button class="btn btn-outline btn-sm">View All</button>
          </div>
          <div class="transactions-list" id="transactionsList">
            <!-- JS populates -->
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
