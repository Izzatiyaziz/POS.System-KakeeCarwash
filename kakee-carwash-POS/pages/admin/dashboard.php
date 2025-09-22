<?php
session_start();
require_once '../../connection.php';
include '../../components/sidebar.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}

$username = $_SESSION['user'];

// Fetch recent customers with staff info
$recent_customers = $conn->query("
    SELECT c.*, 
           s.name AS service_name, 
           p.name AS package_name,
           u.username as created_by
    FROM customers c
    LEFT JOIN services s ON c.service_id = s.id
    LEFT JOIN packages p ON c.package_id = p.id
    LEFT JOIN users u ON c.created_by = u.id
    ORDER BY c.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - KAKEE Carwash POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }
        
        .dashboard-card {
            transition: all 0.2s ease;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <?php include '../../components/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Welcome back, <?= htmlspecialchars($username) ?></h1>
                <p class="text-gray-600">Here's what's happening today</p>
            </div>
            
            <!-- Dashboard Cards -->
           <div class="flex flex-wrap justify-center gap-6">

                <!-- Users Management Card -->
                <a href="users.php" class="dashboard-card bg-white p-6">
                    <div class="flex items-center space-x-4">
                        <div class="card-icon bg-blue-50 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Users</h2>
                            <p class="text-sm text-gray-500">Manage staff accounts</p>
                        </div>
                    </div>
                </a>

                <!-- Services & Packages Card -->
                <a href="services_packages.php" class="dashboard-card bg-white p-6">
                    <div class="flex items-center space-x-4">
                        <div class="card-icon bg-green-50 text-green-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Services</h2>
                            <p class="text-sm text-gray-500">Manage wash packages</p>
                        </div>
                    </div>
                </a>

                 <!-- Customer Card -->
                <a href="customer.php" class="dashboard-card bg-white p-6">
                    <div class="flex items-center space-x-4">
                        <div class="card-icon bg-red-50 text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Customers</h2>
                            <p class="text-sm text-gray-500">View customer records</p>
                        </div>
                    </div>
                </a>

                <!-- Reports Card -->
                <a href="reports.php" class="dashboard-card bg-white p-6">
                    <div class="flex items-center space-x-4">
                        <div class="card-icon bg-purple-50 text-purple-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Reports</h2>
                            <p class="text-sm text-gray-500">View sales analytics</p>
                        </div>
                    </div>
                </a>
            </div>
       <!-- Recent Customers Table -->
            <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">Recent Customers</h2>
                        <a href="customer.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All Customers →
                        </a>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service/Package</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($recent_customers)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No customers found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_customers as $customer): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($customer['name']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($customer['phone']) ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm text-gray-900"><?= htmlspecialchars($customer['vehicle_brand']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($customer['plate_no']) ?> • <?= ucfirst($customer['vehicle_type'] ?? '') ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($customer['service_id']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?= htmlspecialchars($customer['service_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <?= htmlspecialchars($customer['package_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($customer['created_by'] ?? 'System') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M j, Y', strtotime($customer['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

