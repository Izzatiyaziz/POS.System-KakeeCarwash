<?php
session_start();
require_once '../../connection.php';
include '../../components/sidebar.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header("Location: ../../index.php");
    exit();
}

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']  ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $brand       = trim($_POST['brand'] ?? '');
    $plate       = trim($_POST['plate'] ?? '');
    $payMethod   = in_array($_POST['payment'] ?? '', ['cash','qr']) ? $_POST['payment'] : '';
    $choiceType  = $_POST['choice_type'] ?? '';
    $vehicleType = $_POST['vehicle_type'] ?? '';

    $service_id  = ($choiceType === 'service' && !empty($_POST['service_id']))  ? (int)$_POST['service_id']  : null;
    $package_id  = ($choiceType === 'package' && !empty($_POST['package_id']))  ? (int)$_POST['package_id']  : null;

    if (
        $name && $phone && $brand && $plate && $payMethod && $vehicleType &&
        ( ($service_id && !$package_id) || ($package_id && !$service_id) )
    ) {
        $user_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $user_query->bind_param("s", $_SESSION['user']);
        $user_query->execute();
        $user_result = $user_query->get_result();
        $user_data = $user_result->fetch_assoc();
        $created_by = $user_data['id'];

        $stmt = $conn->prepare("
        INSERT INTO customers
        (name, phone, vehicle_brand, plate_no, vehicle_type, service_id, package_id, payment_method, created_by)
        VALUES (?,?,?,?,?,?,?,?,?)
      ");
      
      $null = NULL;
      $sid  = $service_id ?: $null;
      $pid  = $package_id ?: $null;
      
      $stmt->bind_param("sssssiisi",
          $name,
          $phone,
          $brand,
          $plate,
          $vehicleType,
          $sid,
          $pid,
          $payMethod,
          $created_by
      );
      
      if ($stmt->execute()) {
          $customer_id = $conn->insert_id;
          
          // For individual services
          if ($service_id) {
              // Get price for this vehicle type
              $price_query = $conn->prepare("
                  SELECT price FROM service_prices 
                  WHERE service_id = ? 
                  AND vehicle_type = ?
              ");
              $price_query->bind_param("is", $service_id, $vehicleType);
              $price_query->execute();
              $price_result = $price_query->get_result();
              $price = $price_result->fetch_assoc()['price'] ?? 0;
              
              $stmt_cs = $conn->prepare("
                  INSERT INTO customer_services 
                  (customer_id, service_id, via_package, price) 
                  VALUES (?,?,NULL,?)
              ");
              $stmt_cs->bind_param("iid", $customer_id, $service_id, $price);
              $stmt_cs->execute();
          }
          // For packages
          elseif ($package_id) {
              // Get all services in this package with their prices
              $package_services = $conn->prepare("
                  SELECT ps.service_id, sp.price 
                  FROM package_services ps
                  JOIN service_prices sp ON ps.service_id = sp.service_id
                  WHERE ps.package_id = ?
                  AND sp.vehicle_type = ?
              ");
              $package_services->bind_param("is", $package_id, $vehicleType);
              $package_services->execute();
              $result = $package_services->get_result();
              
              while ($ps = $result->fetch_assoc()) {
                  $stmt_cs = $conn->prepare("
                      INSERT INTO customer_services 
                      (customer_id, service_id, via_package, price) 
                      VALUES (?,?,?,?)
                  ");
                  $stmt_cs->bind_param("iiid", $customer_id, $ps['service_id'], $package_id, $ps['price']);
                  $stmt_cs->execute();
              }
          }
          
          $flash = "Customer registered successfully!";
      } else {
          $flash = "Error: ".$stmt->error;
      }
    } else {
        $flash = "Please fill all fields, pick either a service OR a package, and select a vehicle type.";
    }
}

/* Fetch available services with prices grouped by vehicle type */
$services = $conn->query("
    SELECT s.id, s.name AS service_name, sp.vehicle_type, sp.price 
    FROM services s
    JOIN service_prices sp ON s.id = sp.service_id
    ORDER BY s.name, sp.vehicle_type
")->fetch_all(MYSQLI_ASSOC);

// Group services by vehicle type for easier JavaScript access
$servicesByType = [];
foreach ($services as $service) {
    $servicesByType[$service['vehicle_type']][] = $service;
}

/* Fetch available packages with their base prices */
$packages = $conn->query("
    SELECT id, name, price AS package_price, description 
    FROM packages 
    WHERE status = 1
    ORDER BY name
")->fetch_all(MYSQLI_ASSOC);

/* Standard vehicle types for packages */
$standardVehicleTypes = [
    'car_s' => 'CAR S - Small Car',
    'car_m' => 'CAR M - Medium Car',
    'car_l' => 'CAR L - Large Car',
    'car_xl' => 'CAR XL - Extra Large Car',
    'motor_lt' => 'MOTOR LT - Light Motorcycle (Below 150cc)',
    'motor_gt' => 'MOTOR GT - Heavy Motorcycle (Above 150cc)'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register Customer</title>
<script src="https://cdn.tailwindcss.com"></script>    
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
 body{font-family:'Poppins',sans-serif}
 .input-focus:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.25)}    
</style>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="p-6 max-w-6xl mx-auto">
  <h1 class="text-3xl font-bold mb-6">Customer Registration</h1>

  <?php if ($flash): ?>
  <div class="mb-6 p-4 rounded-lg <?= str_contains($flash,'successfully')?'bg-green-100 text-green-800':'bg-red-100 text-red-800' ?>">
      <?= htmlspecialchars($flash) ?>
  </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Info side -->
    <div class="space-y-6">
      <!-- Available Services Table -->
      <div class="bg-white border p-6 rounded-xl shadow">
        <h2 class="text-xl font-semibold mb-4">Available Services</h2>
        <table class="w-full border-collapse">
          <thead>
            <tr>
              <th class="border px-4 py-2 text-left">#</th>
              <th class="border px-4 py-2 text-left">Service Name</th>
              <th class="border px-4 py-2 text-left">Vehicle Type</th>
              <th class="border px-4 py-2 text-left">Price (RM)</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($services): ?>
              <?php $i = 1; foreach ($services as $s): ?>
                <tr>
                  <td class="border px-4 py-2"><?= $i++ ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars($s['service_name']) ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars($s['vehicle_type']) ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars(number_format($s['price'], 2)) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4" class="border px-4 py-2 text-center italic text-gray-500">No services.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Available Packages Table -->
      <div class="bg-white border p-6 rounded-xl shadow">
        <h2 class="text-xl font-semibold mb-4">Available Packages</h2>
        <table class="w-full border-collapse">
          <thead>
            <tr>
              <th class="border px-4 py-2 text-left">#</th>
              <th class="border px-4 py-2 text-left">Package Name</th>
              <th class="border px-4 py-2 text-left">Price (RM)</th>
              <th class="border px-4 py-2 text-left">Description</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($packages): ?>
              <?php $i = 1; foreach ($packages as $p): ?>
                <tr>
                  <td class="border px-4 py-2"><?= $i++ ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars($p['name']) ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars(number_format($p['package_price'], 2)) ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars($p['description']) ?></td>
                  </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4" class="border px-4 py-2 text-center italic text-gray-500">No packages.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Form side -->
    <div class="bg-white border p-6 rounded-xl shadow">
      <h2 class="text-xl font-semibold mb-6">Customer Information</h2>
      <form method="POST" class="space-y-6">
        <!-- Personal -->
        <div class="grid md:grid-cols-2 gap-6">
          <label class="block">Full Name
            <input name="name" required class="w-full border rounded-lg px-4 py-2.5 input-focus">
          </label>
          <label class="block">Phone Number
          <input name="phone" required 
                class="w-full border rounded-lg px-4 py-2.5 input-focus" 
                placeholder="e.g., 0123456789"
                oninput="validatePhoneInput(this)"
                onkeypress="return restrictInput(event)"
                maxlength="12">
          <span id="phone-error" class="text-xs text-red-500 hidden">
            Please enter a valid Malaysian phone number (10-11 digits, numbers only)
          </span>
        </label>
        </div>

        <!-- Vehicle -->
        <div class="grid md:grid-cols-2 gap-6">
          <label class="block">Vehicle Brand
            <input name="brand" required class="w-full border rounded-lg px-4 py-2.5 input-focus">
          </label>
         <label class="block">Plate Number
          <input 
            name="plate"
            required
            class="w-full border rounded-lg px-4 py-2.5 input-focus uppercase"
            pattern="[A-Z0-9\s]{5,10}"
            title="Plate number must be 5–10 characters, using letters, numbers, or spaces only."
            oninput="this.value = this.value.toUpperCase()"
            maxlength="7"
          >
         </label>

        </div>

        <!-- Vehicle Type -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Vehicle Type</label>
          <select name="vehicle_type" required class="w-full border rounded-lg px-4 py-2.5 input-focus" id="vehicle_type">
            <option value="">-- Select Vehicle Type --</option>
            <?php foreach($standardVehicleTypes as $value => $label): ?>
              <option value="<?= $value ?>"><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Payment -->
        <fieldset class="space-y-2 border border-gray-200 p-4 rounded">
          <legend class="text-sm font-medium text-gray-700 px-2">Payment Method</legend>
          <label class="inline-flex items-center mr-6">
            <input type="radio" name="payment" value="cash" required class="form-radio h-4 w-4 text-indigo-600">
            <span class="ml-2">Cash</span>
          </label>
          <label class="inline-flex items-center">
            <input type="radio" name="payment" value="qr" required class="form-radio h-4 w-4 text-indigo-600">
            <span class="ml-2">QR</span>
          </label>
        </fieldset>

        <!-- Choice -->
        <fieldset class="space-y-3">
          <legend class="text-sm font-medium text-gray-700">Select Service or Package</legend>
          <label class="flex items-center space-x-2">
            <input type="radio" name="choice_type" value="service" required class="form-radio" id="choice_service">
            <span>Service</span>
          </label>
          <select name="service_id" class="w-full border rounded-lg px-4 py-2.5 input-focus" id="service_id">
            <option value="">-- Select Service --</option>
            <?php foreach($services as $index => $s): ?>
                <option value="<?= $s['id'] ?>" 
                        data-price="<?= $s['price'] ?>"
                        data-vehicle-type="<?= $s['vehicle_type'] ?>"
                        class="service-option">
                    #<?= $index + 1 ?> - <?= htmlspecialchars($s['service_name']) ?>
                </option>
            <?php endforeach; ?>
          </select>
          <label class="flex items-center space-x-2">
            <input type="radio" name="choice_type" value="package" required class="form-radio" id="choice_package">
            <span>Package</span>
          </label>
          <select name="package_id" class="w-full border rounded-lg px-4 py-2.5 input-focus" id="package_id">
            <option value="">-- Select Package --</option>
            <?php foreach($packages as $p): ?>
              <option value="<?= $p['id'] ?>" data-price="<?= $p['package_price'] ?>"><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </fieldset>

        <!-- Price Display -->
        <div id="price_display" class="mt-4 p-4 bg-gray-100 rounded-lg hidden">
          <strong>Estimated Price:</strong> RM <span id="estimated_price">0.00</span>
        </div>

        <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-medium">
          Register Customer
        </button>
      </form>
    </div>
  </div>
</div>

<script>
// Toggle between service/package selection
const svcRadio = document.querySelector('input[value="service"][name="choice_type"]');
const pkgRadio = document.querySelector('input[value="package"][name="choice_type"]');
const svcSel = document.querySelector('#service_id');
const pkgSel = document.querySelector('#package_id');
const priceDisplay = document.querySelector('#price_display');
const estimatedPrice = document.querySelector('#estimated_price');

// Filter services based on vehicle type
function filterServicesByVehicleType() {
    const vehicleType = document.querySelector('#vehicle_type').value;
    const serviceOptions = document.querySelectorAll('#service_id .service-option');
    
    serviceOptions.forEach(option => {
        const optionVehicleType = option.getAttribute('data-vehicle-type');
        if (!vehicleType || optionVehicleType === vehicleType) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });

    // Reset service selection if current selection is not valid for vehicle type
    const serviceSelect = document.querySelector('#service_id');
    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
    if (selectedOption && selectedOption.getAttribute('data-vehicle-type') !== vehicleType) {
        serviceSelect.value = '';
        updatePrice();
    }
}

// Add vehicle type change listener
document.querySelector('#vehicle_type').addEventListener('change', function() {
    filterServicesByVehicleType();
    updatePrice();
});

// Initial filter on page load
filterServicesByVehicleType();

// Update the toggle function to also handle service filtering
function toggle() {
    const isService = svcRadio.checked;
    svcSel.disabled = !isService;
    pkgSel.disabled = isService;
    if (isService) {
        pkgSel.value = '';
        filterServicesByVehicleType(); // Filter services when switching to service selection
    } else {
        svcSel.value = '';
    }
    updatePrice();
}

[svcRadio, pkgRadio].forEach(r => r.addEventListener('change', toggle));
toggle();

// Calculate and display price
function updatePrice() {
  let price = 0;
  if (svcRadio.checked) {
    const selectedService = svcSel.options[svcSel.selectedIndex];
    price = parseFloat(selectedService.getAttribute('data-price') || '0');
  } else if (pkgRadio.checked) {
    const selectedPackage = pkgSel.options[pkgSel.selectedIndex];
    price = parseFloat(selectedPackage.getAttribute('data-price') || '0');
  }

  // Ensure a vehicle type is selected
  const vehicleType = document.querySelector('#vehicle_type').value;
  if (!vehicleType) {
    priceDisplay.classList.add('hidden');
    return;
  }

  // Display price
  priceDisplay.classList.remove('hidden');
  estimatedPrice.textContent = price.toFixed(2);
}

// Attach event listeners
document.querySelector('#vehicle_type').addEventListener('change', updatePrice);
document.querySelector('#service_id').addEventListener('change', updatePrice);
document.querySelector('#package_id').addEventListener('change', updatePrice);

// Restrict input to numbers only and limit length
function restrictInput(event) {
  const charCode = event.which ? event.which : event.keyCode;
  // Allow only numbers (0-9) and backspace/delete
  if (charCode > 31 && (charCode < 48 || charCode > 57)) {
    event.preventDefault();
    return false;
  }
  return true;
}

// Phone number validation function
function validatePhoneInput(input) {
  const errorSpan = document.getElementById('phone-error');
  const phone = input.value.trim();
  
  // Remove any non-digit characters (except + at start)
  let cleanedPhone = phone.replace(/[^0-9+]/g, '');
  if (cleanedPhone.length > 0 && cleanedPhone[0] !== '+') {
    cleanedPhone = cleanedPhone.replace(/\+/g, '');
  }
  // Update the input value
  if (phone !== cleanedPhone) {
    input.value = cleanedPhone;
  }

  // Only validate if length is exactly 12 digits
  if (cleanedPhone.length !== 12) {
    input.classList.remove('border-red-500', 'border-green-500');
    errorSpan.classList.add('hidden');
    return;
  }

  if (validatePhoneNumber(phone)) {
    input.classList.remove('border-red-500');
    input.classList.add('border-green-500');
    errorSpan.classList.add('hidden');
  } else {
    input.classList.remove('border-green-500');
    input.classList.add('border-red-500');
    errorSpan.classList.remove('hidden');
  }
}

// Update phone number validation for exactly 12 digits
function validatePhoneNumber(phone) {
  // Malaysian phone number validation (exactly 12 digits)
  const regex = /^(\+?6?01)[0-46-9]\d{8}$/;
  return regex.test(phone);
}

// Keep the form submission validation
document.querySelector('form').addEventListener('submit', function(e) {
  const phoneInput = document.querySelector('input[name="phone"]');
  if (!validatePhoneNumber(phoneInput.value.trim())) {
    e.preventDefault();
    validatePhoneInput(phoneInput);
    phoneInput.focus();
    return false;
  }
  return true;
});

</script>
</body>
</html>