<?php
session_start();
require_once '../../connection.php';
include '../../components/sidebar.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}

$error = "";
$success = "";

/* ---------- DELETE USER ---------- */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== $_SESSION['user_id']) { // Prevent self-deletion
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "User deleted successfully.";
            } else {
                // Check for foreign key constraint error (MySQL error code 1451)
                if ($conn->errno == 1451 || strpos($conn->error, 'foreign key constraint') !== false) {
                    $_SESSION['error'] = "Cannot delete user: This user is referenced in other records (e.g., created customers or other tables).";
                } else {
                    $_SESSION['error'] = "Error deleting user: " . $conn->error;
                }
            }
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                $_SESSION['error'] = "Cannot delete user: This user is referenced in other records (e.g., created customers or other tables).";
            } else {
                $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['error'] = "Cannot delete your own account. You are currently logged in as this user.";
    }
    header("Location: users.php");
    exit();
}

// Show error/success messages from session if set
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

/* ---------- UPDATE USER ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = (int)$_POST['id'];
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);

    if ($username && $role) {
        if ($password) {
            // Update with new password
            $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $role, $password, $id);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $role, $id);
        }
        
        if ($stmt->execute()) {
            $success = "User updated successfully.";
        } else {
            $error = "Error updating user: " . $conn->error;
        }
    } else {
        $error = "Username and role are required.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    if ($username && $role && $password) {
        $stmt = $conn->prepare("INSERT INTO users (username, role, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $role, $password);
        if ($stmt->execute()) {
            $success = "User added successfully.";
        } else {
            $error = "Error adding user: " . $conn->error;
        }
    } else {
        $error = "All fields are required.";
    }
}

// Fetch users list
$result = $conn->query("SELECT id, username, password, role FROM users ORDER BY id ASC");
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Users - KAKEE Carwash POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .input-focus {
            transition: all 0.3s ease;
        }
        .input-focus:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen p-6">

    <div class="max-w-5xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Users Management</h1>

        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="flex justify-end mb-10">
        <button
            type="button"
            id="openSheetBtn"
            class="text-white cursor-pointer border border-black rounded px-8 py-3 bg-black transition-transform duration-200 hover:text-black hover:-translate-x-1 hover:-translate-y-1 hover:bg-pink-300 hover:shadow-[0.25rem_0.25rem_0_0_black] active:translate-x-0 active:translate-y-0 active:shadow-none"
            >
            + Add New User
        </button>


        </div>
        <table class="w-full bg-white rounded shadow overflow-hidden">
            <thead class="bg-gray-200">
                <tr>
                    <th class="py-3 px-4 text-center">ID</th>
                    <th class="py-3 px-4 text-left">Username</th>
                    <th class="py-3 px-4 text-left">Password</th>
                    <th class="py-3 px-4 text-left">Role</th>
                    <th class="py-3 px-4 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($users as $user): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 px-4 text-center"><?= $i++ ?></td> <!-- This will show 1, 2, 3... -->
                        <td class="py-2 px-4"><?= htmlspecialchars($user['username']) ?></td>
                        <td class="py-2 px-4"><?= htmlspecialchars($user['password']) ?></td>
                        <td class="py-2 px-4 capitalize"><?= htmlspecialchars($user['role']) ?></td>
                        <td class="py-2 px-4">
                            <button onclick="openEditSheet(<?= htmlspecialchars(json_encode($user)) ?>)" 
                                    class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 mr-2">
                                Edit
                            </button>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <a href="?delete=<?= $user['id'] ?>" 
                                   onclick="return confirm('Are you sure you want to delete this user?')"
                                   class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                                    Delete
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Sheet Component -->
    <div id="sheet"
        class="fixed top-0 right-0 h-full w-96 bg-white shadow-lg transform translate-x-full transition-transform duration-300 z-50 flex flex-col">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="text-xl font-semibold">Add New User</h2>
            <button id="closeSheetBtn" class="text-gray-600 hover:text-gray-900 text-2xl font-bold">&times;</button>
        </div>

        <form method="POST" class="p-6 flex flex-col gap-4 flex-grow overflow-auto">
            <input type="hidden" name="add_user" value="1" />

            <label class="block">
                <span class="text-gray-700">Username</span>
                <input type="text" name="username" required
                    class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </label>

            <label class="block">
                <span class="text-gray-700">Role</span>
                <select name="role" required
                    class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select role</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                </select>
            </label>

            <label class="block">
                <span class="text-gray-700">Password</span>
                <input type="password" name="password" required
                    class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </label>

            <div class="mt-auto">
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded">
                    Add User
                </button>
            </div>
        </form>
    </div>

    <!-- Edit Sheet -->
    <div id="editSheet"
        class="fixed top-0 right-0 h-full w-96 bg-white shadow-lg transform translate-x-full transition-transform duration-300 z-50 flex flex-col">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="text-xl font-semibold">Edit User</h2>
            <button onclick="closeEditSheet()" class="text-gray-600 hover:text-gray-900 text-2xl font-bold">&times;</button>
        </div>

        <form method="POST" class="p-6 flex flex-col gap-4 flex-grow overflow-auto">
            <input type="hidden" name="update_user" value="1" />
            <input type="hidden" name="id" id="edit_user_id" />

            <label class="block">
                <span class="text-gray-700">Username</span>
                <input type="text" name="username" id="edit_username" required
                    class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </label>

            <label class="block">
                <span class="text-gray-700">Role</span>
                <select name="role" id="edit_role" required
                    class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select role</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                </select>
            </label>

            <label class="block">
                <span class="text-gray-700">New Password (leave blank to keep current)</span>
                <input type="password" name="password"
                    class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </label>

            <div class="mt-auto">
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded">
                    Update User
                </button>
            </div>
        </form>
    </div>

    <!-- Overlay -->
    <div id="overlay"
        class="fixed inset-0 bg-black bg-opacity-30 hidden z-40"></div>

    <script>
        const sheet = document.getElementById('sheet');
        const overlay = document.getElementById('overlay');
        const openBtn = document.getElementById('openSheetBtn');
        const closeBtn = document.getElementById('closeSheetBtn');

        function openSheet() {
            sheet.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
        }

        function closeSheet() {
            sheet.classList.add('translate-x-full');
            overlay.classList.add('hidden');
        }

        openBtn.addEventListener('click', openSheet);
        closeBtn.addEventListener('click', closeSheet);
        overlay.addEventListener('click', closeSheet);

        // Edit Sheet Functions
        const editSheet = document.getElementById('editSheet');

        function openEditSheet(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_role').value = user.role;
            
            editSheet.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
        }

        function closeEditSheet() {
            editSheet.classList.add('translate-x-full');
            overlay.classList.add('hidden');
        }

        // Update overlay click handler to close both sheets
        overlay.addEventListener('click', function() {
            closeSheet();
            closeEditSheet();
        });
    </script>

</body>

</html>