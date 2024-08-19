<?php
session_start();

if (!isset($_SESSION['userid']) || $_SESSION['perfil'] != 'admin') {
    header('Location: dashboard_public.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<style>
     body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            padding: 80px; 
            text-align: center;
        }

        .dashboard-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 10px 20px 15px 25px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

</style>
<body>
    <div class="dashboard-container">
        <h1>Bem-vindo ao Painel de Administração , <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        <p>Aqui você pode gerenciar o sistema.</p>
    <!-- Botão de Logout estilizado com Bootstrap -->
    <a href="criar_backup.php" class="btn btn-danger"></a>Criar Backup</a>
    <a href="logout.php" class="btn btn-danger">Sair</a>
</div>
</body>
</html>