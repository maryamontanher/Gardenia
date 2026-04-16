<?php
session_start();
include_once("conexao.php");

// Recebendo e sanitizando os dados do formulário
$nome       = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
$email      = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$cpf        = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
$telefone   = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
$nascimento = filter_input(INPUT_POST, 'nascimento', FILTER_SANITIZE_STRING);
$senha      = filter_input(INPUT_POST, 'senha', FILTER_SANITIZE_STRING);

// Endereço principal
$cep        = filter_input(INPUT_POST, 'cep', FILTER_SANITIZE_STRING);
$estado     = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
$cidade     = filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING);
$bairro     = filter_input(INPUT_POST, 'bairro', FILTER_SANITIZE_STRING);
$rua        = filter_input(INPUT_POST, 'rua', FILTER_SANITIZE_STRING);
$numero     = filter_input(INPUT_POST, 'numero', FILTER_SANITIZE_STRING);
$complemento= filter_input(INPUT_POST, 'complemento', FILTER_SANITIZE_STRING);

// Validação básica
if (empty($nome) || empty($email) || empty($cpf) || empty($telefone) || empty($senha)) {
    $_SESSION['msg'] = "<p style='color:red;'>Preencha os campos obrigatórios.</p>";
    header("Location: cadastro.php");
    exit;
}

// Verifica duplicidade
$sql_verifica = "SELECT * FROM usuarios WHERE email = ? OR cpf = ? OR telefone = ? LIMIT 1";
$stmt_verifica = mysqli_prepare($conn, $sql_verifica);
mysqli_stmt_bind_param($stmt_verifica, "sss", $email, $cpf, $telefone);
mysqli_stmt_execute($stmt_verifica);
$resultado = mysqli_stmt_get_result($stmt_verifica);

if ($usuario_existente = mysqli_fetch_assoc($resultado)) {
    if ($usuario_existente['email'] === $email) {
        $_SESSION['msg'] = "<p style='color:red;'>Este e-mail já está cadastrado.</p>";
    } elseif ($usuario_existente['cpf'] === $cpf) {
        $_SESSION['msg'] = "<p style='color:red;'>Este CPF já está cadastrado.</p>";
    } elseif ($usuario_existente['telefone'] === $telefone) {
        $_SESSION['msg'] = "<p style='color:red;'>Este telefone já está cadastrado.</p>";
    } else {
        $_SESSION['msg'] = "<p style='color:red;'>Dados já cadastrados.</p>";
    }

    header("Location: cadastro.php");
    exit;
}

// Cria hash da senha
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// Insere usuário
$sql_user = "INSERT INTO usuarios (nome, email, senha, cpf, telefone, nascimento) 
             VALUES (?, ?, ?, ?, ?, ?)";
$stmt_user = mysqli_prepare($conn, $sql_user);
mysqli_stmt_bind_param($stmt_user, "ssssss", $nome, $email, $senha_hash, $cpf, $telefone, $nascimento);

if (mysqli_stmt_execute($stmt_user)) {
    $usuario_id = mysqli_insert_id($conn);

    // Insere endereço principal
    $sql_end = "INSERT INTO enderecos (usuario_id, apelido, cep, estado, cidade, bairro, rua, numero, complemento, principal) 
                VALUES (?, 'Principal', ?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt_end = mysqli_prepare($conn, $sql_end);
    mysqli_stmt_bind_param($stmt_end, "isssssss", 
        $usuario_id, $cep, $estado, $cidade, $bairro, $rua, $numero, $complemento
    );
    mysqli_stmt_execute($stmt_end);

    $_SESSION['msg'] = "<p style='color:green;'>Cadastro realizado com sucesso! Faça login.</p>";
    header("Location: login.php");
    exit;
} else {
    $_SESSION['msg'] = "<p style='color:red;'>Erro ao cadastrar: " . mysqli_error($conn) . "</p>";
    header("Location: cadastro.php");
    exit;
}
?>
