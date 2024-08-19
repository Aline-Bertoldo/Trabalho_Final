<?php
session_start();
require_once 'db.php';
require_once 'utils.php'; // Inclua a função de log

// Gera o token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($mysqli, $_POST['username']);
    $password = sanitize_input($mysqli, $_POST['password']);
    $csrf_token = $_POST['csrf_token'];

    // Verifica o token CSRF
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $_SESSION['error'] = "Token CSRF inválido.";
        header('Location: login.php');
        exit();
    }

    // Prepara a query usando prepared statements
    $stmt = $mysqli->prepare("SELECT id, senha, autenticacao_habilitada FROM usuarios WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['senha'])) {
            $_SESSION['userid'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['perfil'] = $user['perfil']; // Adiciona o perfil à sessão

            // Regenera o token CSRF após login bem-sucedido
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            log_activity("Usuário '$username' logado com sucesso.");

            // Redireciona para o local apropriado com base no perfil e autenticação em duas etapas
            if ($user['autenticacao_habilitada']) {
                header('Location: autenticacao.php');
            } else {
                $redirect_page = ($user['perfil'] === 'admin') ? 'dashboard.php' : 'dashboard_public.php';
                header('Location: dashboard.php');
            }
            exit();
        } else {
            $_SESSION['error'] = "Credenciais incorretas. Por favor, tente novamente.";
            log_activity("Tentativa de login falhou: Credenciais incorretas para usuário '$username'.");
        }
    } else {
        $_SESSION['error'] = "Usuário não encontrado.";
        log_activity("Tentativa de login falhou: Usuário '$username' não encontrado.");
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
    <title>Login</title>
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

        .login-container {
            max-width: 400px;
            width: 100%;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #dee2e6;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .form-btn {
            text-align: center;
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
        <div class="login-container">
            <h2 class="text-center mb-4">Login</h2>
            <form action="login.php" method="post">
                <div class="mb-3">
                    <input type="text" class="form-control" name="username" placeholder="Nome de Usuário:" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Senha:" required>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-btn mb-3">
                    <input type="submit" value="Login" name="login" class="btn btn-primary">
                </div>
            </form>
            <div class="text-center">
                <p>Usuário não registrado? <a href="register.php">Registrar-se</a></p>
            </div>
        </div>
    </div>
</body>
</html>
