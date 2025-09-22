<?php
require_once 'connection.php';
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($password === $row['password']) {
            $_SESSION['user'] = $row['username'];
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];

            if ($row['role'] === 'staff') {
                header("Location: pages/staff/dashboard.php");
                exit();
            } else {
                header("Location: pages/admin/dashboard.php");
                exit();
            }
        }
    }

    $error = "Invalid username or password!";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KAKEE Car Wash | Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .login-card {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-radius: 12px;
            overflow: hidden;
        }
        .input-focus:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        .btn-primary {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3), 0 2px 4px -1px rgba(99, 102, 241, 0.2);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="login-card bg-white">
            <!-- Header with logo -->
            <div class="bg-indigo-600 py-6 px-8 text-center">
                <div class="flex justify-center mb-3">
                    <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center">
                        <i class="fas fa-car text-indigo-600 text-2xl"></i>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-white">KAKEE CAR WASH</h1>
                <p class="text-indigo-100 mt-1">Point of Sale System</p>
            </div>
            
            <!-- Login form -->
            <div class="p-8">
                <?php if ($error): ?>
                    <div class="mb-6 p-3 bg-red-50 border border-red-200 text-red-600 rounded-lg flex items-start">
                        <i class="fas fa-exclamation-circle mt-1 mr-3 text-red-500"></i>
                        <div>
                            <p class="font-medium">Login Failed</p>
                            <p class="text-sm"><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" name="username" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none input-focus"
                                placeholder="Enter your username">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" required
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none input-focus"
                                placeholder="Enter your password">
                        </div>
                    </div>
                    
                    <div class="pt-2">
                        <button type="submit"
                            class="w-full btn-primary bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center text-sm text-gray-500">
                    <p>Need help? Contact <a href="#" class="text-indigo-600 hover:underline">support</a></p>
                </div>
            </div>
        </div>
        
        <div class="mt-6 text-center text-xs text-gray-500">
            <p>&copy; <?= date('Y') ?> KAKEE Car Wash. All rights reserved.</p>
        </div>
    </div>
</body>

</html>