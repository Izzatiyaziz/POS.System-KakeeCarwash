<?php
require_once '../../connection.php';
session_start();
include '../../components/sidebar.php';

/* ---- only admin allowed ---- */
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

/* ---------- ADD NEW SERVICE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $conn->begin_transaction();
        /* insert into services */
        $stmt = $conn->prepare("INSERT INTO services (name,status) VALUES (?,1)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $serviceId = $conn->insert_id;

        /* insert into service_prices */
        $map = [
            'car_s'    => $_POST['price_s']        ?? null,
            'car_m'    => $_POST['price_m']        ?? null,
            'car_l'    => $_POST['price_l']        ?? null,
            'car_xl'   => $_POST['price_xl']       ?? null,
            'motor_lt' => $_POST['price_motor_lt'] ?? null,
            'motor_gt' => $_POST['price_motor_gt'] ?? null
        ];
        $stmtP = $conn->prepare(
            "INSERT INTO service_prices (service_id, vehicle_type, price)
             VALUES (?,?,?)"
        );
        foreach ($map as $veh => $pr) {
            if ($pr !== null && $pr !== '') {
                $stmtP->bind_param("isd", $serviceId, $veh, $pr);
                $stmtP->execute();
            }
        }
        $conn->commit();
    }
    header("Location: services_packages.php");
    exit();
}

/* ---------- ADD NEW PACKAGE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_package'])) {
    $pkgName = trim($_POST['package_name'] ?? '');
    $pkgPrice = $_POST['package_price'] !== '' ? (float)$_POST['package_price'] : 0;
    $serviceIds = $_POST['service_ids'] ?? [];

    if ($pkgName !== '' && !empty($serviceIds)) {
        $conn->begin_transaction();

        // Insert into packages
        $stmt = $conn->prepare("INSERT INTO packages (name, price, status) VALUES (?, ?, 1)");
        $stmt->bind_param("sd", $pkgName, $pkgPrice);
        $stmt->execute();
        $packageId = $conn->insert_id;

        // Insert into package_services
        $stmt2 = $conn->prepare("INSERT INTO package_services (package_id, service_id) VALUES (?, ?)");
        foreach ($serviceIds as $sid) {
            $sid = (int)$sid;
            $stmt2->bind_param("ii", $packageId, $sid);
            $stmt2->execute();
        }

        $conn->commit();
    }

    header("Location: services_packages.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM services WHERE id = {$id}");
    header("Location: services_packages.php");
    exit();
}

/* ---------- TOGGLE SERVICE STATUS ---------- */
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $conn->query("UPDATE services SET status = NOT status WHERE id = {$id}");
    header("Location: services_packages.php");
    exit();
}

/* ---------- DELETE PACKAGE ---------- */
if (isset($_GET['delete_package'])) {
    $id = (int)$_GET['delete_package'];
    $conn->begin_transaction();
    $conn->query("DELETE FROM package_services WHERE package_id = {$id}");
    $conn->query("DELETE FROM packages WHERE id = {$id}");
    $conn->commit();
    header("Location: services_packages.php");
    exit();
}

/* ---------- UPDATE SERVICE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $id = (int)$_POST['service_id'];
    $name = trim($_POST['name'] ?? '');
    
    if ($name !== '') {
        $conn->begin_transaction();
        
        // Update service name
        $stmt = $conn->prepare("UPDATE services SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();

        // Delete existing prices
        $conn->query("DELETE FROM service_prices WHERE service_id = {$id}");

        // Insert new prices
        $map = [
            'car_s'    => $_POST['price_s']        ?? null,
            'car_m'    => $_POST['price_m']        ?? null,
            'car_l'    => $_POST['price_l']        ?? null,
            'car_xl'   => $_POST['price_xl']       ?? null,
            'motor_lt' => $_POST['price_motor_lt'] ?? null,
            'motor_gt' => $_POST['price_motor_gt'] ?? null
        ];
        $stmtP = $conn->prepare(
            "INSERT INTO service_prices (service_id, vehicle_type, price)
             VALUES (?,?,?)"
        );
        foreach ($map as $veh => $pr) {
            if ($pr !== null && $pr !== '') {
                $stmtP->bind_param("isd", $id, $veh, $pr);
                $stmtP->execute();
            }
        }
        $conn->commit();
    }
    header("Location: services_packages.php");
    exit();
}

/* ---------- UPDATE PACKAGE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_package'])) {
    $id = (int)$_POST['package_id'];
    $pkgName = trim($_POST['package_name'] ?? '');
    $pkgPrice = $_POST['package_price'] !== '' ? (float)$_POST['package_price'] : 0;
    $serviceIds = $_POST['service_ids'] ?? [];

    if ($pkgName !== '' && !empty($serviceIds)) {
        $conn->begin_transaction();

        // Update package
        $stmt = $conn->prepare("UPDATE packages SET name = ?, price = ? WHERE id = ?");
        $stmt->bind_param("sdi", $pkgName, $pkgPrice, $id);
        $stmt->execute();

        // Delete existing package services
        $conn->query("DELETE FROM package_services WHERE package_id = {$id}");

        // Insert new package services
        $stmt2 = $conn->prepare("INSERT INTO package_services (package_id, service_id) VALUES (?, ?)");
        foreach ($serviceIds as $sid) {
            $sid = (int)$sid;
            $stmt2->bind_param("ii", $id, $sid);
            $stmt2->execute();
        }

        $conn->commit();
    }
    header("Location: services_packages.php");
    exit();
}

/* ---------- FETCH SERVICES (pivot) ---------- */
$sqlServices = "
SELECT  s.id, s.name, s.status,
        MAX(CASE WHEN sp.vehicle_type='car_s'    THEN sp.price END) AS price_s,
        MAX(CASE WHEN sp.vehicle_type='car_m'    THEN sp.price END) AS price_m,
        MAX(CASE WHEN sp.vehicle_type='car_l'    THEN sp.price END) AS price_l,
        MAX(CASE WHEN sp.vehicle_type='car_xl'   THEN sp.price END) AS price_xl,
        MAX(CASE WHEN sp.vehicle_type='motor_lt' THEN sp.price END) AS price_motor_lt,
        MAX(CASE WHEN sp.vehicle_type='motor_gt' THEN sp.price END) AS price_motor_gt
FROM    services s
LEFT JOIN service_prices sp ON sp.service_id = s.id
GROUP BY s.id, s.name, s.status
ORDER BY s.id";
$services = $conn->query($sqlServices)->fetch_all(MYSQLI_ASSOC);

/* ---------- FETCH PACKAGES + service list ---------- */
$sqlPackages = "
SELECT  p.id, p.name, p.price,
        GROUP_CONCAT(s.name SEPARATOR ', ') AS service_list
FROM    packages p
LEFT JOIN package_services ps ON ps.package_id = p.id
LEFT JOIN services s ON s.id = ps.service_id
GROUP BY p.id, p.name, p.price
ORDER BY p.id";
$packages = $conn->query($sqlPackages)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Services & Packages - KAKEE Carwash</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Poppins',sans-serif}
    .sheet{transition:transform .3s ease;transform:translateX(100%)}
    .sheet.open{transform:translateX(0)}
    .sheet-backdrop{background:rgba(0,0,0,.4)}
  </style>
  <script>
    function openSheet(id){document.getElementById(id).classList.add('open');document.getElementById(id+'-backdrop').classList.remove('hidden')}
    function closeSheet(id){document.getElementById(id).classList.remove('open');document.getElementById(id+'-backdrop').classList.add('hidden')}
  </script>
</head>
<body class="bg-gray-100 p-6 min-h-screen">
<div class="max-w-7xl mx-auto">

  <div class="mb-6">
    <h1 class="text-3xl font-bold text-black-600">Services & Packages Management</h1>
    <div class="flex justify-end space-x-4 mt-4">
      <button onclick="openSheet('serviceSheet')" class="text-white border border-black rounded px-8 py-3 bg-black transition hover:text-black hover:-translate-x-1 hover:-translate-y-1 hover:bg-pink-300 hover:shadow-[0.25rem_0.25rem_0_0_black]">+ Add New Service</button>
      <button onclick="openSheet('packageSheet')" class="text-white border border-black rounded px-8 py-3 bg-black transition hover:text-black hover:-translate-x-1 hover:-translate-y-1 hover:bg-pink-300 hover:shadow-[0.25rem_0.25rem_0_0_black]">+ Add New Package</button>
    </div>
  </div>

  <!-- Services Table -->
  <div class="bg-white shadow rounded mb-8">
    <table class="min-w-full text-sm">
      <thead class="bg-yellow-100">
        <tr>
          <th class="px-3 py-2">ID</th><th>Name</th><th>S</th><th>M</th><th>L</th><th>XL</th>
          <th>Motor &lt;150</th><th>Motor &gt;150</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$services): ?>
        <tr><td colspan="9" class="text-center p-4">No services</td></tr>
      <?php  else: $i = 1; foreach ($services as $s): ?>
        <tr>
          <td class="border p-3 text-center"><?= $i++ ?></td>
          <td class="border p-3"><?= htmlspecialchars($s['name']) ?></td>
          <td class="border p-3"><?= $s['price_s'] ?? '-' ?></td>
          <td class="border p-3"><?= $s['price_m'] ?? '-' ?></td>
          <td class="border p-3"><?= $s['price_l'] ?? '-' ?></td>
          <td class="border p-3"><?= $s['price_xl'] ?? '-' ?></td>
          <td class="border p-3"><?= $s['price_motor_lt'] ?? '-' ?></td>
          <td class="border p-3"><?= $s['price_motor_gt'] ?? '-' ?></td>
          <td class="border p-3 text-center space-x-4">
            <button onclick="openEditServiceSheet(<?= htmlspecialchars(json_encode($s)) ?>)" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</button>
            <a href="?delete=<?= $s['id'] ?>" onclick="return confirm('Delete this service?')" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700">Delete</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Packages Table -->
  <div class="bg-white shadow rounded">
    <table class="min-w-full text-sm">
      <thead class="bg-blue-100"><tr><th class="px-3 py-2">ID</th><th>Package</th><th>Price (RM)</th><th>Services</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (!$packages): ?>
        <tr><td colspan="4" class="text-center p-4">No packages</td></tr>
      <?php else:  $j = 1; foreach ($packages as $p): ?>
        <tr>
         <td class="border p-3 text-center"><?= $j++ ?></td>
          <td class="border p-3"><?= htmlspecialchars($p['name']) ?></td>
          <td class="border p-3"><?= number_format($p['price'],2) ?></td>
          <td class="border p-3"><?= htmlspecialchars($p['service_list'] ?? '-') ?></td>
          <td class="border p-3 text-center space-x-4">
            <button onclick="openEditPackageSheet(<?= htmlspecialchars(json_encode($p)) ?>)" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</button>
            <a href="?delete_package=<?= $p['id'] ?>" onclick="return confirm('Delete this package?')" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700">Delete</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- Sheet: Add Service -->
<div id="serviceSheet-backdrop" onclick="closeSheet('serviceSheet')" class="sheet-backdrop hidden fixed inset-0"></div>
<div id="serviceSheet" class="sheet fixed top-0 right-0 w-96 h-full p-6 bg-white">
  <div class="flex justify-between items-center"><h2 class="text-xl font-semibold">Add New Service</h2><button onclick="closeSheet('serviceSheet')">✕</button></div>
  <form method="POST" class="mt-4 space-y-4">
    <input type="hidden" name="add_service" value="1">
    <label class="block">Name <input name="name" required class="w-full border p-2"></label>
    <div class="grid grid-cols-2 gap-2">
      <?php $labels=['price_s'=>'S','price_m'=>'M','price_l'=>'L','price_xl'=>'XL','price_motor_lt'=>'Motor <150','price_motor_gt'=>'Motor >150'];
        foreach($labels as $k=>$v): ?>
      <label class="block"><?= $v ?>
        <input name="<?= $k ?>" type="number" step="0.01" min="0" class="w-full border p-2" placeholder="0.00">
      </label>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="w-full bg-black text-white py-2 rounded">Add Service</button>
    <button type="button" onclick="closeSheet('serviceSheet')" class="w-full bg-red-500 text-white py-2 rounded mb-2">Cancel</button>
  </form>
</div>

<!-- Sheet: Add Package -->
<div id="packageSheet-backdrop" onclick="closeSheet('packageSheet')" class="sheet-backdrop hidden fixed inset-0"></div>
<div id="packageSheet" class="sheet fixed top-0 right-0 w-96 h-full p-6 bg-white">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Add New Package</h2>
    <button onclick="closeSheet('packageSheet')">✕</button>
  </div>
  <form method="POST" class="mt-4 space-y-4">
    <input type="hidden" name="add_package" value="1">
    <label class="block">Name <input name="package_name" required class="w-full border p-2"></label>
    <label class="block">Price (RM) <input name="package_price" type="number" step="0.01" required class="w-full border p-2"></label>

    <!-- MULTI-SELECT SERVICES -->
    <label class="block">Select Services (hold Ctrl/⌘ to select multiple)
      <select name="service_ids[]" multiple required class="w-full border p-2 h-40">
        <?php
        $allServices = $conn->query("SELECT id, name FROM services WHERE status=1 ORDER BY name");
        while ($srv = $allServices->fetch_assoc()):
        ?>
          <option value="<?= $srv['id'] ?>"><?= htmlspecialchars($srv['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </label>
    <button type="submit" class="w-full bg-black text-white py-2 rounded">Add Package</button>
    <button type="button" onclick="closeSheet('packageSheet')" class="w-full bg-red-500 text-white py-2 rounded mb-2">Cancel</button>
  </form>
</div>

<!-- Add Edit Service Sheet -->
<div id="editServiceSheet-backdrop" onclick="closeSheet('editServiceSheet')" class="sheet-backdrop hidden fixed inset-0"></div>
<div id="editServiceSheet" class="sheet fixed top-0 right-0 w-96 h-full p-6 bg-white">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Edit Service</h2>
        <button onclick="closeSheet('editServiceSheet')">✕</button>
    </div>
    <form method="POST" class="mt-4 space-y-4">
        <input type="hidden" name="update_service" value="1">
        <input type="hidden" name="service_id" id="edit_service_id">
        <label class="block">Name <input name="name" id="edit_service_name" required class="w-full border p-2"></label>
        <div class="grid grid-cols-2 gap-2">
            <?php 
            $labels = [
                'price_s' => 'S',
                'price_m' => 'M',
                'price_l' => 'L',
                'price_xl' => 'XL',
                'price_motor_lt' => 'Motor <150',
                'price_motor_gt' => 'Motor >150'
            ];
            foreach($labels as $k => $v): 
            ?>
            <label class="block"><?= $v ?>
                <input name="<?= $k ?>" id="edit_<?= $k ?>" type="number" step="0.01" min="0" class="w-full border p-2" placeholder="0.00">
            </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="w-full bg-black text-white py-2 rounded">Update Service</button>
        <button type="button" onclick="closeSheet('editServiceSheet')" class="w-full bg-red-500 text-white py-2 rounded mb-2">Cancel</button>
    </form>
</div>

<!-- Add Edit Package Sheet -->
<div id="editPackageSheet-backdrop" onclick="closeSheet('editPackageSheet')" class="sheet-backdrop hidden fixed inset-0"></div>
<div id="editPackageSheet" class="sheet fixed top-0 right-0 w-96 h-full p-6 bg-white">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Edit Package</h2>
        <button onclick="closeSheet('editPackageSheet')">✕</button>
    </div>
    <form method="POST" class="mt-4 space-y-4">
        <input type="hidden" name="update_package" value="1">
        <input type="hidden" name="package_id" id="edit_package_id">
        <label class="block">Name <input name="package_name" id="edit_package_name" required class="w-full border p-2"></label>
        <label class="block">Price (RM) <input name="package_price" id="edit_package_price" type="number" step="0.01" required class="w-full border p-2"></label>

        <label class="block">Select Services (hold Ctrl/⌘ to select multiple)
            <select name="service_ids[]" id="edit_package_services" multiple required class="w-full border p-2 h-40">
                <?php
                $allServices = $conn->query("SELECT id, name FROM services WHERE status=1 ORDER BY name");
                while ($srv = $allServices->fetch_assoc()):
                ?>
                    <option value="<?= $srv['id'] ?>"><?= htmlspecialchars($srv['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </label>
        <button type="submit" class="w-full bg-black text-white py-2 rounded">Update Package</button>
        <button type="button" onclick="closeSheet('editPackageSheet')" class="w-full bg-red-500 text-white py-2 rounded mb-2">Cancel</button>
    </form>
</div>

<script>
function openEditServiceSheet(service) {
    document.getElementById('edit_service_id').value = service.id;
    document.getElementById('edit_service_name').value = service.name;
    document.getElementById('edit_price_s').value = service.price_s || '';
    document.getElementById('edit_price_m').value = service.price_m || '';
    document.getElementById('edit_price_l').value = service.price_l || '';
    document.getElementById('edit_price_xl').value = service.price_xl || '';
    document.getElementById('edit_price_motor_lt').value = service.price_motor_lt || '';
    document.getElementById('edit_price_motor_gt').value = service.price_motor_gt || '';
    openSheet('editServiceSheet');
}

function openEditPackageSheet(package) {
    document.getElementById('edit_package_id').value = package.id;
    document.getElementById('edit_package_name').value = package.name;
    document.getElementById('edit_package_price').value = package.price;
    
    // Get the service IDs from the service_list
    const serviceList = package.service_list.split(', ');
    const select = document.getElementById('edit_package_services');
    Array.from(select.options).forEach(option => {
        option.selected = serviceList.includes(option.text);
    });
    
    openSheet('editPackageSheet');
}
</script>

</body>
</html>
