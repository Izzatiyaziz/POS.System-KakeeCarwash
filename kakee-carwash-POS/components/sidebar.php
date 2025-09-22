<?php
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'] ?? 'staff';
?>

<!-- Sidebar -->
<div class="fixed top-0 left-0 h-full w-64 bg-gray-800 text-white flex flex-col shadow-lg">
    <div class="p-6 border-b border-gray-700">
        <h2 class="text-2xl font-bold mb-1">Car Wash POS</h2>
        <p class="text-sm text-gray-400">Welcome, <?= htmlspecialchars($username) ?></p>
        <p class="text-xs text-gray-500 capitalize">Role: <?= htmlspecialchars($role) ?></p>
    </div>

    <nav class="flex flex-col flex-grow p-4 space-y-2">
        <a href="/pages/<?= $role ?>/dashboard.php"
            class="block px-4 py-2 rounded hover:bg-gray-700 transition">Dashboard</a>

        <?php if ($role === 'admin'): ?>
            <a href="/pages/admin/users.php"
                class="block px-4 py-2 rounded hover:bg-gray-700 transition">Users</a>
            <a href="/pages/admin/services_packages.php"
                class="block px-4 py-2 rounded hover:bg-gray-700 transition">Services & Packages</a>
            <a href="/pages/admin/customer.php"
                class="block px-4 py-2 rounded hover:bg-gray-700 transition">Customer</a>
            <a href="/pages/admin/reports.php"
                class="block px-4 py-2 rounded hover:bg-gray-700 transition">Reports</a>
        <?php elseif ($role === 'staff'): ?>
            <a href="/pages/staff/register_cust.php"
                class="block px-4 py-2 rounded hover:bg-gray-700 transition">Register Customer</a>
            <a href="/pages/staff/customer.php"
                class="block px-4 py-2 rounded hover:bg-gray-700 transition">Customer</a>
            <a href="/pages/staff/reports.php"
                class="block px-4 py-2 rounded hover:bg-gray-700 transition">Reports</a>
        <?php endif; ?>
    </nav>

    <div class="p-4 border-t border-gray-700">
        <a href="../../index.php"
            class="block px-4 py-2 rounded bg-red-600 hover:bg-red-700 transition text-center">
            Logout
        </a>

    </div>
</div>

<div class="ml-40 p-6">