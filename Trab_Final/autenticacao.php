<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

require_once 'db.php';

$userid = $_SESSION['userid'];

// Verifica se a autenticação em duas etapas já está habilitada
$sql_check_auth = "SELECT autenticacao_habilitada, codigo_autenticacao FROM usuarios WHERE id=?";
$stmt_check_auth = $mysqli->prepare($sql_check_auth);
$stmt_check_auth->bind_param("i", $userid);
$stmt_check_auth->execute();
$result_check_auth = $stmt_check_auth->get_result();

if ($result_check_auth->num_rows > 0) {
    $row = $result_check_auth->fetch_assoc();
    if (!$row['autenticacao_habilitada']) {
        // Se a autenticação em duas etapas não estiver habilitada, redireciona para o dashboard
        $_SESSION['message'] = "Autenticação em duas etapas não está habilitada.";
        header('Location: dashboard.php');
        exit();
    }

    // Se a autenticação em duas etapas estiver habilitada, gera um novo código de autenticação
    $codigo_autenticacao = $row['codigo_autenticacao'];
    if (!$codigo_autenticacao) {
        $codigo_autenticacao = rand(100000, 999999);
        $sql_update = "UPDATE usuarios SET codigo_autenticacao=? WHERE id=?";
        $stmt_update = $mysqli->prepare($sql_update);
        $stmt_update->bind_param("ii", $codigo_autenticacao, $userid);
        $stmt_update->execute();
        $stmt_update->close();
    }
} else {
    $_SESSION['error'] = "Erro ao verificar autenticação em duas etapas.";
    header('Location: dashboard.php');
    exit();
}

$stmt_check_auth->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticação em Duas Etapas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg, #e2e8f0, #f3f4f6);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            max-width: 500px;
            width: 100%;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 8px;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075);
            border: 1px solid #ced4da;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(38, 143, 255, 0.25);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #ffffff;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Autenticação em Duas Etapas</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <p>Um código de autenticação foi enviado para você. Por favor, insira o código abaixo:</p>
        <form action="verificar_codigo.php" method="post">
            <div class="form-group mb-3">
                <label for="codigo">Código de Autenticação:</label>
                <p></p>
                <input type="text" id="codigo" name="codigo" class="form-control" required>
            </div>
            <div class="d-flex justify-content-between">
                <input type="submit" value="Verificar Código" class="btn btn-primary">
                <button type="button" class="btn btn-secondary" id="showCodigo">Mostrar Código de Autenticação</button>
            </div>
        </form>
    </div>

    <!-- Modal -->
    <div id="modalCodigo" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Código de Autenticação</h3>
            <p>O código de autenticação é: <strong><?php echo htmlspecialchars($codigo_autenticacao); ?></strong></p>
            <p>Use este código para completar o processo de autenticação em duas etapas.</p>
        </div>
    </div>

    <script>
        // Mostrar modal ao clicar no botão
        document.getElementById('showCodigo').addEventListener('click', function() {
            var modal = document.getElementById('modalCodigo');
            modal.style.display = 'block';

            // Fechar modal ao clicar no botão de fechar
            var closeBtn = document.getElementsByClassName('close')[0];
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            // Fechar modal ao clicar fora do conteúdo do modal
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

    
