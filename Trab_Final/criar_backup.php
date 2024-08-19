<?php
session_start();
require_once 'db.php';
require_once 'utils.php'; // Inclua o arquivo de funções utilitárias

// Verificar se o usuário está autenticado
if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

// Obtém o ID do usuário autenticado
$user_id = $_SESSION['userid'];

// Obtém o nome do usuário (se disponível)
$user_query = "SELECT username FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_row = $user_result->fetch_assoc();
$username = $user_row['username'] ?? 'Usuário Desconhecido';
$stmt->close();

// Verificar CSRF token
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ação não autorizada.");
    }

    // Define o nome do arquivo de backup
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.csv';
    $backup_path = __DIR__ . '/backups/' . $backup_file;

    // Certifique-se de que a pasta 'backups' existe
    if (!is_dir(__DIR__ . '/backups')) {
        mkdir(__DIR__ . '/backups', 0755, true);
    }

    // Abre o arquivo para escrita
    $fp = fopen($backup_path, 'w');

    // Verifica se o arquivo foi aberto com sucesso
    if ($fp === false) {
        $log_message = "Erro ao criar o arquivo de backup: $backup_file. Usuário: $username.";
        log_activity($log_message); // Registra o erro no log
        $_SESSION['error'] = "Erro ao criar o arquivo de backup.";
        header('Location: criar_backup.php');
        exit();
    }
        
    // Consulta para selecionar todos os dados da tabela 'usuarios'
    $query = "SELECT * FROM usuarios";
    $result = $mysqli->query($query);

    // Verifica se a consulta foi executada com sucesso
    if ($result === false) {
        $log_message = "Erro ao executar a consulta para o backup. Usuário: $username.";
        log_activity($log_message); // Registra o erro no log
        fclose($fp);
        $_SESSION['error'] = "Erro ao executar a consulta.";
        header('Location: criar_backup.php');
        exit();
    }

    // Obtém os nomes das colunas
    $fields = $result->fetch_fields();
    $headers = [];
    foreach ($fields as $field) {
        $headers[] = $field->name;
    }

    // Escreve os nomes das colunas no arquivo CSV
    fputcsv($fp, $headers);

    // Escreve os dados no arquivo CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($fp, $row);
    }

    // Fecha o arquivo e a conexão com o banco de dados
    fclose($fp);
    $mysqli->close();

    $_SESSION['message'] = "Backup criado com sucesso: <a href='backups/$backup_file'>$backup_file</a>";
    header('Location: criar_backup.php'); // Redireciona para evitar reenvio do formulário
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Criar Backup</title>
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

        .backup-container {
            max-width: 600px;
            width: 100%;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .backup-container h2 {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="backup-container">
        <h2>Criar Backup</h2>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <!-- Botão de Logout estilizado com Bootstrap -->
            <button type="submit" class="btn btn-primary">Criar Backup</button>
            <a href="dashboard.php" class="btn btn-danger">Voltar ao Dashboard</a>
        </form>
    </div>
</body>
</html>
