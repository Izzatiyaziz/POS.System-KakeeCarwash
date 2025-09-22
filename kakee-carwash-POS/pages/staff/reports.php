<?php
session_start();
require_once '../../connection.php';
include '../../components/sidebar.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header("Location: ../../index.php");
    exit();
}

// Get current user ID from username
$user_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_query->bind_param("s", $_SESSION['user']);
$user_query->execute();
$user_result = $user_query->get_result();
$user_data = $user_result->fetch_assoc();
$current_user_id = $user_data['id'];

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate   = $_GET['end_date'] ?? $startDate;

// --- Main Summary Query: Total Customers and Revenue ---
$sql = "
SELECT
  COUNT(DISTINCT c.id) AS customer_count,
  COALESCE(SUM(CASE WHEN c.service_id IS NOT NULL THEN cs.price ELSE 0 END), 0) + 
  COALESCE(SUM(CASE WHEN c.package_id IS NOT NULL THEN p.price ELSE 0 END), 0) AS total_revenue
FROM customers c
LEFT JOIN customer_services cs ON c.id = cs.customer_id AND c.service_id IS NOT NULL
LEFT JOIN packages p ON c.package_id = p.id
WHERE DATE(c.created_at) BETWEEN ? AND ? AND c.created_by = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $startDate, $endDate, $current_user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Service Breakdown (only standalone services, not from packages) ---
$detailedSql = "
SELECT
  s.name AS service_name,
  COUNT(cs.service_id) AS service_count,
  SUM(cs.price) AS service_revenue
FROM customers c
JOIN customer_services cs ON c.id = cs.customer_id
JOIN services s ON cs.service_id = s.id
WHERE DATE(c.created_at) BETWEEN ? AND ? 
  AND c.created_by = ?
  AND c.service_id IS NOT NULL
  AND cs.via_package IS NULL
GROUP BY s.name
ORDER BY s.name
";
$detailedStmt = $conn->prepare($detailedSql);
$detailedStmt->bind_param("ssi", $startDate, $endDate, $current_user_id);
$detailedStmt->execute();
$detailedData = $detailedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$detailedStmt->close();

// --- Package Breakdown ---
$packageDetailedSql = "
SELECT
  p.name AS package_name,
  COUNT(c.id) AS package_count,
  SUM(p.price) AS package_revenue
FROM customers c
JOIN packages p ON c.package_id = p.id
WHERE DATE(c.created_at) BETWEEN ? AND ? AND c.created_by = ?
GROUP BY p.name
";
$packageStmt = $conn->prepare($packageDetailedSql);
$packageStmt->bind_param("ssi", $startDate, $endDate, $current_user_id);
$packageStmt->execute();
$packageDetailedData = $packageStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$packageStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer & Revenue Report</title>
  <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }

        .input-focus:focus {
          border-color: #6366f1;
          box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="ml-20 p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Customer & Revenue Report</h1>
      <div class="text-sm text-gray-500">
        <i class="fas fa-calendar-alt mr-2"></i>
        <?= htmlspecialchars($startDate ?? '', ENT_QUOTES, 'UTF-8') ?> 
        <?= $startDate != $endDate ? ' - ' . htmlspecialchars($endDate ?? '', ENT_QUOTES, 'UTF-8') : '' ?>
      </div>
    </div>

    <!-- filter -->
    <form method="get" class="mb-8 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
          <input type="date" name="start_date" value="<?= htmlspecialchars($startDate ?? '', ENT_QUOTES, 'UTF-8') ?>"
                class="w-full border border-gray-300 px-3 py-2 rounded-md input-focus">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
          <input type="date" name="end_date" value="<?= htmlspecialchars($endDate ?? '', ENT_QUOTES, 'UTF-8') ?>"
                class="w-full border border-gray-300 px-3 py-2 rounded-md input-focus">
        </div>
        <div class="flex items-end">
          <button type="submit"
                class="w-full h-10 px-6 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200">
            <i class="fas fa-filter mr-2"></i> Apply Filter
          </button>
        </div>
      </div>
    </form>

    <!-- cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
            <i class="fas fa-users text-lg"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Total Customers</p>
            <p class="text-3xl font-bold text-gray-800 mt-1"><?= htmlspecialchars($data['customer_count'] ?? 0, ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        <div class="flex items-center">
          <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
            <i class="fas fa-money-bill-wave text-lg"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Total Revenue</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">
              RM<?= number_format((float)($data['total_revenue'] ?? 0), 2) ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Breakdown -->
    <div class="mt-8 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
      <h3 class="font-medium text-gray-700 mb-3"><i class="fas fa-info-circle text-indigo-500 mr-2"></i> Service Breakdown</h3>
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-100">
            <th class="p-2 text-left">#</th>
            <th class="p-2 text-left">Service Name</th>
            <th class="p-2 text-left">Count</th>
            <th class="p-2 text-left">Revenue (RM)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($detailedData as $index => $row): ?>
            <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">
              <td class="p-2"><?= htmlspecialchars($index + 1, ENT_QUOTES, 'UTF-8') ?></td>
              <td class="p-2"><?= htmlspecialchars($row['service_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="p-2 text-center"><?= htmlspecialchars($row['service_count'] ?? 0, ENT_QUOTES, 'UTF-8') ?></td>
              <td class="p-2 text-right"><?= number_format((float)($row['service_revenue'] ?? 0), 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Package Breakdown -->
    <div class="mt-8 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
      <h3 class="font-medium text-gray-700 mb-3"><i class="fas fa-box-open text-indigo-500 mr-2"></i> Package Breakdown</h3>
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-100">
            <th class="p-2 text-left">#</th>
            <th class="p-2 text-left">Package Name</th>
            <th class="p-2 text-left">Count</th>
            <th class="p-2 text-left">Revenue (RM)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($packageDetailedData as $index => $row): ?>
            <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">
              <td class="p-2"><?= htmlspecialchars($index + 1, ENT_QUOTES, 'UTF-8') ?></td>
              <td class="p-2"><?= htmlspecialchars($row['package_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="p-2 text-center"><?= htmlspecialchars($row['package_count'] ?? 0, ENT_QUOTES, 'UTF-8') ?></td>
              <td class="p-2 text-right"><?= number_format((float)($row['package_revenue'] ?? 0), 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Additional Info -->
    <div class="mt-8 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
      <h3 class="font-medium text-gray-700 mb-3"><i class="fas fa-info-circle text-indigo-500 mr-2"></i> Report Information</h3>
      <p class="text-sm text-gray-600">
        This report shows customers who purchased services or packages. Revenue is calculated based on service prices and package totals.
      </p>
    </div>
  </div>
</body>
</html>