<?php
session_start();
include_once("../conexao.php");

if(!isset($_SESSION['funcionario_id'])){
    echo json_encode(['success' => false, 'message' => 'Funcionário não logado']);
    exit;
}

$funcionario_id = $_SESSION['funcionario_id'];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Criar diretório se não existir
    $diretorio = "funcionario/img/uploads/";
    if(!is_dir($diretorio)) {
        mkdir($diretorio, 0777, true);
    }
    
    if(isset($_FILES['foto'])) {
        // Validar arquivo
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['foto']['type'];
        $file_size = $_FILES['foto']['size'];
        
        if(!in_array($file_type, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido. Use JPG, PNG ou GIF.']);
            exit;
        }
        
        if($file_size > 5 * 1024 * 1024) { // 5MB
            echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Tamanho máximo: 5MB.']);
            exit;
        }
        
        // Gerar nome único para o arquivo
        $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nome_arquivo = 'func_' . $funcionario_id . '_' . time() . '.' . $extensao;
        $caminho_completo = $diretorio . $nome_arquivo;
        
        if(move_uploaded_file($_FILES['foto']['tmp_name'], $caminho_completo)) {
            // Atualizar no banco
            $sql = "UPDATE funcionario SET foto = ? WHERE id_funcionario = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $nome_arquivo, $funcionario_id);
            
            if(mysqli_stmt_execute($stmt)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Foto atualizada com sucesso!',
                    'foto_url' => $caminho_completo
                ]);
            } else {
                // Se der erro no banco, remove o arquivo
                unlink($caminho_completo);
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco de dados.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload do arquivo.']);
        }
        
    } elseif(isset($_POST['remover_foto'])) {
        // Remover foto
        $sql = "UPDATE funcionario SET foto = NULL WHERE id_funcionario = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $funcionario_id);
        
        if(mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Foto removida com sucesso!',
                'foto_url' => 'funcionario/img/default-avatar.jpg'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao remover foto.']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}
?>