<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CryptoTrack - Settings</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <style>
      a {
        text-decoration: none;
      }
      :root {
        --primary-color: #6c5ce7;
        --background-color: #f8f9fb;
        --card-bg: #ffffff;
        --text-primary: #2d3436;
        --text-secondary: #636e72;
        --border-color: #dfe6e9;
        --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        --border-radius: 8px;
        --success-color: #00b894;
        --danger-color: #d63031;
        --warning-color: #fdcb6e;
      }

      /* Dark Mode Variables */
      body.dark-mode {
        --background-color: #1a202c;
        --card-bg: #2d3748;
        --text-primary: #e2e8f0;
        --text-secondary: #a0aec0;
        --border-color: #4a5568;
        --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      body {
        font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
        background-color: var(--background-color);
        color: var(--text-primary);
        line-height: 1.6;
        transition: background-color 0.3s ease, color 0.3s ease;
      }

      .container {
        display: flex;
        min-height: 100vh;
      }

      .sidebar {
        width: 250px;
        background-color: var(--card-bg);
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 24px 16px;
        transition: transform 0.3s ease, background-color 0.3s ease;
      }

      .sidebar-header h1 {
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--primary-color);
      }

      .sidebar-nav ul {
        list-style: none;
        margin-top: 32px;
      }

      .sidebar-nav li {
        margin-bottom: 8px;
      }

      .sidebar-nav a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: var(--text-secondary);
        text-decoration: none;
        border-radius: var(--border-radius);
        transition: all 0.2s ease;
      }

      .sidebar-nav li.active a {
        color: var(--primary-color);
        background-color: rgba(108, 92, 231, 0.1);
        font-weight: 600;
        border-left: 3px solid var(--primary-color);
      }

      .sidebar-nav a:hover {
        background-color: rgba(108, 92, 231, 0.05);
        color: var(--primary-color);
      }

      .sidebar-footer {
        margin-top: auto;
      }

      .user-profile {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .user-profile img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
      }

      .user-info h4 {
        font-size: 0.95rem;
        color: var(--text-primary);
      }

      .user-info p {
        font-size: 0.8rem;
        color: var(--text-secondary);
      }

      .main-content {
        flex: 1;
        padding: 24px;
        overflow-y: auto;
      }

      .main-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
      }

      .sidebar-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.2rem;
        color: var(--text-primary);
        cursor: pointer;
      }

      .search-bar {
        flex: 1;
        position: relative;
      }

      .search-bar i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
      }

      .search-bar input {
        width: 100%;
        padding: 10px 16px 10px 40px;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        background-color: var(--card-bg);
        color: var(--text-primary);
        font-size: 0.95rem;
        outline: none;
        transition: background-color 0.3s ease, color 0.3s ease;
      }

      .header-actions {
        display: flex;
        gap: 12px;
      }

      .btn {
        padding: 10px 16px;
        border-radius: var(--border-radius);
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
      }

      .btn-primary {
        background-color: var(--primary-color);
        color: #fff;
        border: none;
      }

      .btn-primary:hover {
        background-color: #5a4bc6;
      }

      .btn-outline {
        background-color: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-primary);
      }

      .btn-outline:hover {
        background-color: rgba(0, 0, 0, 0.03);
      }

      .btn i {
        margin-right: 8px;
      }

      .settings-section {
        background-color: var(--card-bg);
        border-radius: var(--border-radius);
        padding: 24px;
        box-shadow: var(--box-shadow);
        margin-bottom: 24px;
        transition: background-color 0.3s ease;
      }

      .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
      }

      .section-header h2 {
        font-size: 1.5rem;
        color: var(--text-primary);
      }

      .settings-form {
        max-width: 600px;
        display: flex;
        flex-direction: column;
        gap: 20px;
      }

      .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
      }

      .form-group label {
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 500;
      }

      .form-group input,
      .form-group select {
        padding: 10px 16px;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        background-color: var(--card-bg);
        color: var(--text-primary);
        font-size: 0.95rem;
        outline: none;
        transition: background-color 0.3s ease, color 0.3s ease;
      }

      .form-group input:disabled {
        background-color: #f8f9fb;
        color: var(--text-secondary);
      }

      .theme-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .theme-toggle label {
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 500;
      }

      .switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 20px;
      }

      .switch input {
        opacity: 0;
        width: 0;
        height: 0;
      }

      .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--border-color);
        transition: 0.4s;
        border-radius: 20px;
      }

      .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 2px;
        bottom: 2px;
        background-color: var(--text-primary);
        transition: 0.4s;
        border-radius: 50%;
      }

      input:checked + .slider {
        background-color: var(--primary-color);
      }

      input:checked + .slider:before {
        transform: translateX(20px);
      }

      .checkbox-group {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .checkbox-group input {
        width: 16px;
        height: 16px;
      }

      .btn-save {
        padding: 12px;
        font-size: 1rem;
      }

      @media (max-width: 768px) {
        .sidebar {
          position: fixed;
          top: 0;
          left: 0;
          height: 100%;
          transform: translateX(-100%);
          z-index: 1000;
        }

        .sidebar.active {
          transform: translateX(0);
        }

        .sidebar-toggle {
          display: block;
        }

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
          <h1><i class="fas fa-coins"></i> CryptoTrack</h1>
        </div>
        <nav class="sidebar-nav">
          <ul>
            <li>
              <a href="dashboard.php"
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
            <li class="active">
              <a href="settings.php" aria-current="page"
                ><i class="fas fa-cog"></i> Settings</a
              >
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
              placeholder="Search settings..."
              aria-label="Search settings"
            />
          </div>
          <div class="header-actions">
            <button class="btn btn-outline" aria-label="Notifications">
              <i class="fas fa-bell"></i>
            </button>
            <button class="btn btn-primary">
              <i class="fas fa-save"></i> Save Changes
            </button>
          </div>
        </header>

        <!-- Settings Section -->
        <section class="settings-section">
          <div class="section-header">
            <h2>Settings</h2>
          </div>
          <div class="settings-form">
            <!-- Profile Settings -->
            <div class="form-group">
              <label for="username">Username</label>
              <input
                type="text"
                id="username"
                value="JohnDoe123"
                aria-label="Username"
              />
            </div>
            <div class="form-group">
              <label for="email">Email</label>
              <input
                type="email"
                id="email"
                value="john.doe@example.com"
                aria-label="Email"
              />
            </div>
            <div class="form-group">
              <label for="language">Language</label>
              <select id="language" aria-label="Select language">
                <option value="en">English</option>
                <option value="es">Spanish</option>
                <option value="fr">French</option>
              </select>
            </div>

            <!-- Theme Settings -->
            <div class="theme-toggle">
              <label for="themeToggle">Dark Mode</label>
              <label class="switch">
                <input type="checkbox" id="themeToggle" />
                <span class="slider"></span>
              </label>
            </div>

            <!-- Notification Settings -->
            <div class="form-group">
              <label>Notifications</label>
              <div class="checkbox-group">
                <input type="checkbox" id="emailNotifications" checked />
                <label for="emailNotifications">Email Notifications</label>
              </div>
              <div class="checkbox-group">
                <input type="checkbox" id="pushNotifications" />
                <label for="pushNotifications">Push Notifications</label>
              </div>
            </div>

            <button class="btn btn-primary btn-save">Save Changes</button>
          </div>
        </section>
      </main>
    </div>

    <script>
      // Sidebar Toggle
      const sidebar = document.querySelector(".sidebar");
      const sidebarToggle = document.querySelector(".sidebar-toggle");

      sidebarToggle.addEventListener("click", () => {
        sidebar.classList.toggle("active");
      });

      // Theme Toggle
      const themeToggle = document.getElementById("themeToggle");
      const body = document.body;

      // Load saved theme from localStorage
      if (localStorage.getItem("theme") === "dark") {
        body.classList.add("dark-mode");
        themeToggle.checked = true;
      }

      themeToggle.addEventListener("change", () => {
        if (themeToggle.checked) {
          body.classList.add("dark-mode");
          localStorage.setItem("theme", "dark");
        } else {
          body.classList.remove("dark-mode");
          localStorage.setItem("theme", "light");
        }
      });

      // Save Settings (Placeholder)
      const saveButton = document.querySelector(".btn-save");
      saveButton.addEventListener("click", () => {
        const username = document.getElementById("username").value;
        const email = document.getElementById("email").value;
        const language = document.getElementById("language").value;
        const emailNotifications =
          document.getElementById("emailNotifications").checked;
        const pushNotifications =
          document.getElementById("pushNotifications").checked;

        const settings = {
          username,
          email,
          language,
          emailNotifications,
          pushNotifications,
        };

        localStorage.setItem("settings", JSON.stringify(settings));
        alert("Settings saved successfully!");
      });

      // Load saved settings
      const savedSettings = JSON.parse(localStorage.getItem("settings"));
      if (savedSettings) {
        document.getElementById("username").value =
          savedSettings.username || "JohnDoe123";
        document.getElementById("email").value =
          savedSettings.email || "john.doe@example.com";
        document.getElementById("language").value =
          savedSettings.language || "en";
        document.getElementById("emailNotifications").checked =
          savedSettings.emailNotifications ?? true;
        document.getElementById("pushNotifications").checked =
          savedSettings.pushNotifications ?? false;
      }
    </script>
  </body>
</html>
