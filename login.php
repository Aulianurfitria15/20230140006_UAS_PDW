<?php
session_start();
require_once 'config.php';

// Jika sudah login, redirect ke halaman yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'asisten') {
        header("Location: asisten/dashboard.php");
    } elseif ($_SESSION['role'] == 'mahasiswa') {
        header("Location: mahasiswa/dashboard.php");
    }
    exit();
}

$message = '';
$message_type = ''; // 'error' or 'success'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "Email dan password harus diisi!";
        $message_type = 'error';
    } else {
        $sql = "SELECT id, nama, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Password benar, simpan semua data penting ke session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];

                // Logika untuk mengarahkan pengguna berdasarkan peran (role)
                if ($user['role'] == 'asisten') {
                    header("Location: asisten/dashboard.php");
                    exit();
                } elseif ($user['role'] == 'mahasiswa') {
                    header("Location: mahasiswa/dashboard.php");
                    exit();
                } else {
                    $message = "Peran pengguna tidak valid.";
                    $message_type = 'error';
                }

            } else {
                $message = "Password yang Anda masukkan salah.";
                $message_type = 'error';
            }
        } else {
            $message = "Akun dengan email tersebut tidak ditemukan.";
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Cek untuk pesan registrasi berhasil
if (isset($_GET['status']) && $_GET['status'] == 'registered') {
    $message = 'Registrasi berhasil! Silakan login.';
    $message_type = 'success';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Praktikum</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a69bd;
            --primary-hover-color: #3e5a9e;
            --success-color: #2ecc71;
            --error-color: #e74c3c;
            --light-bg-color: #f8f9fa;
            --dark-text-color: #343a40;
            --light-text-color: #6c757d;
            --border-color: #dee2e6;
            --white-color: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background-color: var(--white-color);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }

        .login-container h2 {
            color: var(--dark-text-color);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .login-container p {
            color: var(--light-text-color);
            margin-bottom: 30px;
            font-size: 14px;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
            text-align: left;
            display: none; /* Disembunyikan secara default */
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-group .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text-color);
        }

        .form-group input {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.2);
        }

        .btn {
            background-color: var(--primary-color);
            color: var(--white-color);
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            background-color: var(--primary-hover-color);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: var(--light-text-color);
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .register-link a:hover {
            text-decoration: underline;
            color: var(--primary-hover-color);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Selamat Datang!</h2>
        <p>Silakan masuk untuk melanjutkan.</p>

        <?php 
            if (!empty($message)) {
                echo '<div class="message ' . htmlspecialchars($message_type) . '">' . htmlspecialchars($message) . '</div>';
            }
        ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" id="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>

        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>
</body>
</html>