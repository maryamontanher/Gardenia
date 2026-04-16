<?php
session_start();
include_once("../conexao.php");

// Verifica se usuário está logado
if(!isset($_SESSION['usuario_id'])){
    echo json_encode(['status' => 'erro', 'msg' => 'Você precisa estar logado.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Receber dados do POST
$cep = $_POST['cep'] ?? '';
$estado = $_POST['estado'] ?? '';
$cidade = $_POST['cidade'] ?? '';
$bairro = $_POST['bairro'] ?? '';
$rua = $_POST['rua'] ?? '';
$numero = $_POST['numero'] ?? '';
$complemento = $_POST['complemento'] ?? '';

// Validação mínima
if(!$cep || !$estado || !$cidade || !$rua || !$numero){
    echo json_encode(['status' => 'erro', 'msg' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

// Inserir no banco
$sql = "INSERT INTO enderecos (usuario_id, cep, estado, cidade, bairro, rua, numero, complemento, principal) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "isssssss", $usuario_id, $cep, $estado, $cidade, $bairro, $rua, $numero, $complemento);

if(mysqli_stmt_execute($stmt)){
    $id_endereco = mysqli_insert_id($conn);
    echo json_encode([
        'status' => 'sucesso',
        'msg' => 'Endereço adicionado com sucesso!',
        'endereco' => [
            'id_endereco' => $id_endereco,
            'cep' => $cep,
            'estado' => $estado,
            'cidade' => $cidade,
            'bairro' => $bairro,
            'rua' => $rua,
            'numero' => $numero,
            'complemento' => $complemento
        ]
    ]);
} else {
    echo json_encode(['status' => 'erro', 'msg' => 'Erro ao adicionar endereço.']);
}
?>
