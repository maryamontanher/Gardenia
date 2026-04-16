<?php
session_start();
include_once("./conexao.php");

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$senha = filter_input(INPUT_POST, 'senha', FILTER_SANITIZE_STRING);

$sql = "SELECT * FROM usuarios WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if ($usuario = mysqli_fetch_assoc($resultado)) {
        // Verificar a senha
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['usuario_id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            header("Location: cliente/painel_cliente.php");
            exit;
        } else {
            $_SESSION['msg'] = "<p style='color:red;'>Senha incorreta.</p>";
            header("Location: login.php");
            exit;
        }
    } else {
        $sql_func = "SELECT * FROM funcionario WHERE email = ? AND senha = ? LIMIT 1";
        $stmt_func = mysqli_prepare($conn, $sql_func);
        if ($stmt_func) {
            mysqli_stmt_bind_param($stmt_func, "ss", $email, $senha);
            mysqli_stmt_execute($stmt_func);
            $resultado_func = mysqli_stmt_get_result($stmt_func);
            if ($funcionario = mysqli_fetch_assoc($resultado_func)) {
                $_SESSION['funcionario_id'] = $funcionario['id_funcionario'];
                $_SESSION['funcionario_nome'] = $funcionario['nome'];
                header("Location: funcionario/adm_funcionario.php");
                exit;
            } else {
                $_SESSION['msg'] = "<p style='color:red;'>Usuário ou senha incorretos.</p>";
            }
            header("Location: login.php");
            exit;
        } else {
            $_SESSION['msg'] = "<p style='color:red;'>Erro no login: " . mysqli_error($conn) . "</p>";
            header("Location: login.php");
            exit;
        }
    }
} else {
    $_SESSION['msg'] = "<p style='color:red;'>Erro no login: " . mysqli_error($conn) . "</p>";
    header("Location: login.php");
    exit;
}

?>