<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Público</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #e2e8f0, #f3f4f6);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .dashboard-container {
            max-width: 600px;
            width: 100%;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .dashboard-container h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        .dashboard-container p {
            font-size: 16px;
            margin-bottom: 30px;
            color: #666;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px;
            color: #fff;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn:focus, .btn:active {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>Bem-vindo ao Dashboard, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        <p>Você está na área pública do sistema.</p>
        <!-- Botões estilizados -->
        <a href="criar_backup.php" class="btn btn-primary">Criar Backup</a>
        <a href="logout.php" class="btn btn-danger">Sair</a>
    </div>
</body>
</html>

