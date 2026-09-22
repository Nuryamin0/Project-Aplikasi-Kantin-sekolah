<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pemesanan Berhasil - Kantin Sehat</title>

    <link rel="stylesheet" href="assets/style.css">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #dcfce7, #f0fdf4, #ffffff);
            font-family: Arial, sans-serif;
        }

        .success-page {
            width: 90%;
            max-width: 450px;
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 35px rgba(0,0,0,0.12);
            animation: popupIn 0.4s ease;
        }

        .success-icon {
            width: 90px;
            height: 90px;
            margin: 0 auto 20px;
            background: #dcfce7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 45px;
        }

        .success-page h1 {
            color: #16a34a;
            margin-bottom: 12px;
        }

        .success-page p {
            color: #64748b;
            line-height: 1.6;
        }

        .success-thanks {
            margin-top: 15px;
        }

        .success-decoration {
            font-size: 30px;
            margin: 25px 0;
        }

        .btn-back-menu {
            display: inline-block;
            width: 100%;
            box-sizing: border-box;
            padding: 13px;
            background: #16a34a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn-back-menu:hover {
            background: #15803d;
            transform: translateY(-2px);
        }

        @keyframes popupIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body>

    <div class="success-page">

        <div class="success-icon">
            🍽️
        </div>

        <h1>Pemesanan Berhasil! 🎉</h1>

        <p>
            Pesanan kamu telah berhasil diproses.
        </p>

        <p class="success-thanks">
            Terima kasih telah jajan di <b>Kantin Sehat</b>! 😍
        </p>

        <div class="success-decoration">
            🍜 🍔 🧋
        </div>

        <a href="index.php" class="btn-back-menu">
            Kembali ke Menu
        </a>

    </div>

</body>
</html>