<?php
session_start();
require_once '../../connection.php';
include '../../components/sidebar.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Get filter value from URL
$filter = $_GET['filter'] ?? 'all';

// Build WHERE clause based on filter
$where_clause = '';
if (strpos($filter, 'staff_') === 0) {
    $staff_id = intval(str_replace('staff_', '', $filter));
    $where_clause = "WHERE c.created_by = $staff_id";
}

// Fetch all customers
$rows = $conn->query("
    SELECT c.*, 
           s.name AS service_name, 
           p.name AS package_name,
           p.description AS package_description,
           p.price AS package_price,
           GROUP_CONCAT(DISTINCT ps.service_id) as package_service_ids,
           GROUP_CONCAT(DISTINCT sv.name) as package_service_names,
           u.username as created_by
    FROM customers c
    LEFT JOIN services s ON c.service_id = s.id
    LEFT JOIN packages p ON c.package_id = p.id
    LEFT JOIN package_services ps ON p.id = ps.package_id
    LEFT JOIN services sv ON ps.service_id = sv.id
    LEFT JOIN users u ON c.created_by = u.id
    $where_clause
    GROUP BY c.id
    ORDER BY c.created_at DESC
");

$customers = $rows ? $rows->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all staff users for the filter
$staff_users = $conn->query("
    SELECT id, username 
    FROM users 
    WHERE role = 'staff' 
    ORDER BY username
")->fetch_all(MYSQLI_ASSOC);

// Group customer_services for lookup (to show breakdown)
$cs_map = [];
$cs_result = $conn->query("
    SELECT cs.customer_id, sv.name AS service_name, cs.price 
    FROM customer_services cs
    JOIN services sv ON cs.service_id = sv.id
");
while ($row = $cs_result->fetch_assoc()) {
    $cs_map[$row['customer_id']][] = $row;
}

// Create a map of package services for easier lookup
$package_services_map = [];
$package_services_result = $conn->query("
    SELECT p.id as package_id, 
           GROUP_CONCAT(s.name) as service_names,
           GROUP_CONCAT(s.id) as service_ids
    FROM packages p
    JOIN package_services ps ON p.id = ps.package_id
    JOIN services s ON ps.service_id = s.id
    GROUP BY p.id
");
while ($row = $package_services_result->fetch_assoc()) {
    $package_services_map[$row['package_id']] = [
        'service_names' => explode(',', $row['service_names']),
        'service_ids' => explode(',', $row['service_ids'])
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customers - KAKEE Carwash POS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4361ee;
      --primary-light: #e0e7ff;
      --secondary: #3f37c9;
      --success: #4cc9f0;
      --danger: #f72585;
      --warning: #f8961e;
      --info: #4895ef;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8fafc;
    }
    
    .card {
      transition: all 0.2s ease;
      border-radius: 0.75rem;
      border: 1px solid #e2e8f0;
    }
    
    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .badge {
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
      border-radius: 0.375rem;
    }
    
    .badge-service {
      background-color: var(--primary-light);
      color: var(--primary);
    }
    
    .badge-package {
      background-color: #ecfdf5;
      color: #059669;
    }
    
    .sheet {
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: -4px 0 6px -1px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
      height: 100vh;
      max-height: 100vh;
    }
    
    .sheet-header {
      flex-shrink: 0;
      background: white;
      z-index: 1;
    }
    
    .sheet-content {
      flex: 1;
      overflow-y: auto;
      padding: 1.5rem;
      height: calc(100vh - 80px); /* Subtract header height */
    }

    /* Add smooth scrolling to the content */
    .sheet-content::-webkit-scrollbar {
      width: 6px;
    }
    
    .sheet-content::-webkit-scrollbar-track {
      background: #f1f1f1;
    }
    
    .sheet-content::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 3px;
    }
    
    .sheet-content::-webkit-scrollbar-thumb:hover {
      background: #555;
    }
    
    .data-item {
      transition: background-color 0.2s ease;
    }
    
    .data-item:hover {
      background-color: #f9fafb;
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include '../../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 overflow-auto p-8">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Customer Management</h1>
          <p class="text-gray-600">View all customer records</p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-4">
          <select 
            onchange="window.location.href='?filter=' + this.value"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
          >
            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Customers</option>
            <optgroup label="Filter by Staff">
              <?php foreach ($staff_users as $staff): ?>
                <option 
                  value="staff_<?= $staff['id'] ?>" 
                  <?= $filter === 'staff_' . $staff['id'] ? 'selected' : '' ?>
                >
                  <?= htmlspecialchars($staff['username']) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          </select>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
              <i class="fas fa-search text-gray-400"></i>
            </span>
            <input 
              type="text" 
              placeholder="Search customers..." 
              class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              oninput="filterCustomers(this.value)"
            >
          </div>
        </div>
      </div>

      <!-- Customers Grid -->
      <div id="customerContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- JS will populate this -->
      </div>
    </div>
  </div>

  <!-- Sheet Overlay -->
  <div id="sheetOverlay" class="fixed inset-0 bg-black bg-opacity-30 hidden z-40" onclick="closeSheet()"></div>

  <!-- Customer Details Sheet -->
  <div id="sheet" class="sheet fixed top-0 right-0 w-full sm:w-96 bg-white transform translate-x-full z-50">
    <div class="sheet-header p-6 border-b border-gray-200 flex justify-between items-center">
      <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-3">
        <i class="fas fa-user-circle text-blue-500"></i>
        <span>Customer Details</span>
      </h2>
      <button onclick="closeSheet()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
    </div>

    <div id="sheetContent" class="sheet-content">
      <!-- Content will be inserted here by JavaScript -->
    </div>
  </div>

  <script>
  const customers = <?= json_encode($customers) ?>;
  const servicesMap = <?= json_encode($cs_map) ?>;
  const packageServicesMap = <?= json_encode($package_services_map) ?>;
  let filteredCustomers = [...customers];

  function loadCustomers(customersToLoad = filteredCustomers) {
    const box = document.getElementById('customerContainer');
    box.innerHTML = '';
    
    if (customersToLoad.length === 0) {
      box.innerHTML = `
        <div class="col-span-3 flex flex-col items-center justify-center py-16 bg-white rounded-lg shadow-sm">
          <i class="fas fa-users-slash text-4xl text-gray-300 mb-4"></i>
          <p class="text-gray-500 text-lg">No customers found</p>
          <p class="text-sm text-gray-400 mt-2">Try adjusting your search</p>
        </div>`;
      return;
    }

    customersToLoad.forEach(c => {
      const card = document.createElement('div');
      card.className = 'card bg-white cursor-pointer';
      card.innerHTML = `
        <div class="p-5">
          <div class="flex justify-between items-start">
            <div>
              <h3 class="text-lg font-semibold text-gray-800">${c.name}</h3>
              <p class="text-sm text-gray-500 mt-1 flex items-center gap-1">
                <i class="fas fa-car text-gray-400"></i>
                <span>${c.vehicle_brand} • ${c.plate_no}</span>
              </p>
            </div>
            <span class="badge ${c.service_id ? 'badge-service' : 'badge-package'}">
              ${c.service_id ? 'Service' : 'Package'}
            </span>
          </div>
          <div class="mt-4 flex flex-wrap gap-3">
            <div class="flex items-center text-sm text-gray-500">
              <i class="fas fa-phone-alt mr-2 text-gray-400"></i>
              <span>${c.phone}</span>
            </div>
            <div class="flex items-center text-sm text-gray-500">
              <i class="fas fa-car-side mr-2 text-gray-400"></i>
              <span class="capitalize">${c.vehicle_type}</span>
            </div>
          </div>
          <div class="mt-4 pt-3 border-t border-gray-100 flex justify-between items-center">
            <span class="text-xs text-gray-400">
              ${new Date(c.created_at).toLocaleDateString()}
            </span>
            <button 
              onclick="event.stopPropagation(); openSheet(${JSON.stringify(c)})"
              class="text-xs text-blue-600 hover:text-blue-800 font-medium"
            >
              View Details
            </button>
          </div>
        </div>`;
      card.onclick = () => openSheet(c);
      box.appendChild(card);
    });
  }

  function filterCustomers(searchTerm) {
    if (!searchTerm) {
      filteredCustomers = [...customers];
      loadCustomers();
      return;
    }
    
    const term = searchTerm.toLowerCase();
    filteredCustomers = customers.filter(c => 
      (c.name && c.name.toLowerCase().includes(term)) ||
      (c.phone && c.phone.toLowerCase().includes(term)) ||
      (c.plate_no && c.plate_no.toLowerCase().includes(term)) ||
      (c.vehicle_brand && c.vehicle_brand.toLowerCase().includes(term)) ||
      (c.vehicle_type && c.vehicle_type.toLowerCase().includes(term))
    );
    
    loadCustomers();
  }

  function openSheet(c) {
    const sheet = document.getElementById('sheet');
    const overlay = document.getElementById('sheetOverlay');
    sheet.classList.remove('translate-x-full');
    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    const services = servicesMap[c.id] || [];
    let servicesHTML = '';
    
    // Only show individual service prices if it's a single service, not a package
    if (c.service_id && services.length > 0) {
        servicesHTML = `<div class="bg-gray-50 rounded-lg p-4">
           <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
             <i class="fas fa-list-check text-blue-500"></i>
             <span>Service Details</span>
           </h4>
           <ul class="space-y-2">` +
           services.map(s => `
             <li class="flex justify-between items-center bg-white p-3 rounded border border-gray-100">
               <span class="text-gray-700">${s.service_name}</span>
               <span class="font-medium text-gray-800">RM ${parseFloat(s.price).toFixed(2)}</span>
             </li>`).join('') +
           `</ul>
         </div>`;
    } else if (c.package_id && c.package_service_names) {
        // For packages, show services from the package data
        const packageServices = c.package_service_names.split(',');
        servicesHTML = `<div class="bg-gray-50 rounded-lg p-4">
           <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
             <i class="fas fa-list-check text-blue-500"></i>
             <span>Included Services</span>
           </h4>
           <ul class="space-y-2">` +
           packageServices.map(name => `
             <li class="flex items-center bg-white p-3 rounded border border-gray-100">
               <span class="text-gray-700">${name.trim()}</span>
             </li>`).join('') +
           `</ul>
         </div>`;
    } else {
        servicesHTML = `<div class="bg-gray-50 rounded-lg p-4 text-center text-gray-500">
           <i class="fas fa-info-circle mr-2"></i>
           No additional service details
         </div>`;
    }

    document.getElementById('sheetContent').innerHTML = `
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-5">
        <div class="p-4 bg-blue-50 border-b border-blue-100">
          <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
            <i class="fas fa-user text-blue-500"></i>
            ${c.name}
          </h3>
          <p class="text-sm text-gray-500 mt-1">
            Customer since ${new Date(c.created_at).toLocaleDateString()}
          </p>
        </div>
        
        <div class="divide-y divide-gray-100">
          <div class="data-item p-4 flex items-start">
            <i class="fas fa-phone-alt mt-1 mr-3 text-gray-400"></i>
            <div>
              <p class="text-xs text-gray-500">Phone Number</p>
              <p class="font-medium">${c.phone}</p>
            </div>
          </div>
          
          <div class="data-item p-4 flex items-start">
            <i class="fas fa-money-bill-wave mt-1 mr-3 text-gray-400"></i>
            <div>
              <p class="text-xs text-gray-500">Payment Method</p>
              <p class="font-medium capitalize">${c.payment_method}</p>
            </div>
          </div>

          <div class="data-item p-4 flex items-start">
            <i class="fas fa-user-check mt-1 mr-3 text-gray-400"></i>
            <div>
              <p class="text-xs text-gray-500">Created By</p>
              <p class="font-medium">${c.created_by || 'System'}</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-5">
        <div class="p-4 bg-blue-50 border-b border-blue-100">
          <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
            <i class="fas fa-car text-blue-500"></i>
            Vehicle Information
          </h3>
        </div>
        
        <div class="divide-y divide-gray-100">
          <div class="data-item p-4 flex items-start">
            <i class="fas fa-car-side mt-1 mr-3 text-gray-400"></i>
            <div>
              <p class="text-xs text-gray-500">Brand/Model</p>
              <p class="font-medium">${c.vehicle_brand}</p>
            </div>
          </div>
          
          <div class="data-item p-4 flex items-start">
            <i class="fas fa-tag mt-1 mr-3 text-gray-400"></i>
            <div>
              <p class="text-xs text-gray-500">Type</p>
              <p class="font-medium capitalize">${c.vehicle_type}</p>
            </div>
          </div>
          
          <div class="data-item p-4 flex items-start">
            <i class="fas fa-id-card mt-1 mr-3 text-gray-400"></i>
            <div>
              <p class="text-xs text-gray-500">Plate Number</p>
              <p class="font-medium uppercase">${c.plate_no}</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="p-4 bg-blue-50 border-b border-blue-100">
          <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
            <i class="fas ${c.service_id ? 'fa-tools' : 'fa-box-open'} text-blue-500"></i>
            ${c.service_id ? 'Service' : 'Package'} Details
          </h3>
        </div>
        
        <div class="p-4">
          <div class="mb-4">
            <p class="text-sm text-gray-500">Name</p>
            <p class="font-medium">${c.service_id ? c.service_name : c.package_name}</p>
          </div>
          ${c.package_description ? `
            <div class="mb-4">
              <p class="text-sm text-gray-500">Description</p>
              <p class="font-medium">${c.package_description}</p>
            </div>
          ` : ''}
          ${c.package_price ? `
            <div class="mb-4">
              <p class="text-sm text-gray-500">Price</p>
              <p class="font-medium">RM ${parseFloat(c.package_price).toFixed(2)}</p>
            </div>
          ` : ''}
          ${servicesHTML}
        </div>
      </div>
      
      <div class="mt-auto pt-4">
        <button 
          onclick="closeSheet()"
          class="w-full py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium transition-colors"
        >
          Close Details
        </button>
      </div>
    `;
  }

  function closeSheet() {
    document.getElementById('sheet').classList.add('translate-x-full');
    document.getElementById('sheetOverlay').classList.add('hidden');
    document.body.style.overflow = 'auto';
  }

  // Close sheet with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeSheet();
    }
  });

  // Initialize
  document.addEventListener('DOMContentLoaded', () => {
    loadCustomers();
    
    // Close sheet when clicking outside on mobile
    document.getElementById('sheetOverlay').addEventListener('click', closeSheet);
  });
  </script>
</body>
</html>