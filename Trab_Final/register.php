<?php
session_start();
require_once 'db.php';
require_once 'utils.php'; // Adiciona o arquivo utils.php

// Gera o token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitiza e valida os dados do formulário
    $username = sanitize_input($mysqli, $_POST['username']);
    $email = sanitize_input($mysqli, $_POST['email']);
    $password = sanitize_input($mysqli, $_POST['senha']);
    $confirm_password = sanitize_input($mysqli, $_POST['confirm_password']);
    $csrf_token = $_POST['csrf_token'];
    $concorda_lgpd = isset($_POST['concorda_lgpd']);

    // Verifica o token CSRF
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $_SESSION['error'] = "Token CSRF inválido.";
        header('Location: register.php');
        exit();
    }

    if (!$concorda_lgpd) {
        $_SESSION['error'] = "Você deve concordar com os termos da LGPD.";
        header('Location: register.php');
        exit();
    }

    if ($senha !== $confirm_senha) {
        $_SESSION['error'] = "As senhas não coincidem. Por favor, tente novamente.";
        header('Location: register.php');
        exit();
    }

    $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE username=? OR email=?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result_check_user = $stmt->get_result();

    if ($result_check_user->num_rows > 0) {
        $_SESSION['error'] = "Usuário ou e-mail já registrado. Por favor, escolha outro.";
        header('Location: register.php');
        exit();
    }

    $password_hash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("INSERT INTO usuarios (username, email, senha) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $senha_hash);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Usuário registrado com sucesso!";

        // Logando a atividade de registro de usuário
        $log_message = "Novo usuário registrado: $username, E-mail: $email";
        log_activity($log_message); // Chama a função de log

         if (isset($_POST['autenticacao_duas_etapas']) && $_POST['autenticacao_duas_etapas'] == 1) {
            $userid = $mysqli->insert_id;
            $codigo_autenticacao = rand(100000, 999999);

            $stmt = $mysqli->prepare("UPDATE usuarios SET autenticacao_habilitada=1, codigo_autenticacao=? WHERE id=?");
            $stmt->bind_param("ii", $codigo_autenticacao, $userid);
            $stmt->execute();

            $_SESSION['message'] = "Autenticação em duas etapas habilitada. Um código de autenticação foi enviado para você.";
            header('Location: autenticacao.php');
            exit();
        } else {
            header('Location: login.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Erro ao registrar o usuário: " . $stmt->error;
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f3f4f6, #e2e8f0);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .register-container {
            max-width: 500px;
            width: 100%;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #dee2e6;
            margin-top: 20px;
            background: #fff;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .register-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .form-btn {
            text-align: center;
        }

        .form-check-input {
            margin-top: 0.3rem;
        }

        .terms-message {
            margin-bottom: 20px;
            font-size: 14px;
            color: #6c757d;
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

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 8px;
            padding: 10px 20px;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }

        .text-center a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }

        .text-center a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <div class="register-container">
            <h2 class="text-center mb-4">Registro de Usuário</h2>
            <form action="register.php" method="post">
                <div class="mb-3">
                    <input type="text" class="form-control" name="username" placeholder="Nome de Usuário:" required>
                </div>
                <div class="mb-3">
                    <input type="email" class="form-control" name="email" placeholder="Email:" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Senha:" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="confirm_password" placeholder="Confirme a Senha:" required>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="autenticacao_duas_etapas" value="1">
                    <label class="form-check-label">Habilitar Autenticação em Duas Etapas</label>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="concorda_lgpd" value="1" required>
                    <label class="form-check-label">Eu concordo com os termos da LGPD</label>
                </div>

                <div class="terms-message">
                    <p>Eu compreendo que o e-mail fornecido será utilizado exclusivamente para comunicação relacionada a esta aplicação. Ao marcar esta caixa, você confirma que leu e concorda com os termos e condições de uso e políticas de privacidade.</p>
                </div>
                <div class="form-btn mb-4">
                    <input type="submit" class="btn btn-primary" value="Registrar" name="submit">
                </div>
            </form>
            <div class="text-center mt-3">
                <p>Usuário já registrado?<a href="login.php"> Login Aqui </a></p>
