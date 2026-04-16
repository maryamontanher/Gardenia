<?php
session_start();
error_reporting(E_ALL);  // Ativa debug - REMOVA EM PRODUÇÃO
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');  // Para localhost
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include_once("../conexao.php");

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Usuário não logado.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$frete = floatval($_POST['frete'] ?? 0);
$pagamento = $_POST['pagamento'] ?? 'pix';
$cartao_id = !empty($_POST['cartao_id']) ? intval($_POST['cartao_id']) : null;
$endereco_id = intval($_POST['endereco_id'] ?? 0);

// Validações
if ($endereco_id <= 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Endereço de entrega não selecionado.']);
    exit;
}
if ($pagamento === 'cartao' && !$cartao_id) {
    echo json_encode(['status' => 'error', 'msg' => 'Selecione um cartão de crédito.']);
    exit;
}
if (empty($_SESSION['cart'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Carrinho vazio.']);
    exit;
}

// Calcular total
$total_produtos = 0;
$carrinho = $_SESSION['cart'];
foreach ($carrinho as $item) {
    $total_produtos += floatval($item['preco'] ?? 0) * intval($item['quantidade'] ?? 1);
}
$total = $total_produtos + $frete;

// Tratar nulls para bind
$cartao_id_bind = $cartao_id ?? 0;  // 0 se null (para FK, 0 é OK se não usado)

// Iniciar transação
mysqli_autocommit($conn, FALSE);
$success = true;
$error_msg = '';
$pedido_id = null;

try {
    // Inserir pedido (bind: idsii)
    $sql_pedido = "INSERT INTO pedidos (usuario_id, total, status, pagamento, endereco_id, cartao_id) VALUES (?, ?, 'pendente', ?, ?, ?)";
    $stmt_pedido = mysqli_prepare($conn, $sql_pedido);
    if (!$stmt_pedido) {
        throw new Exception("Erro na preparação do pedido: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt_pedido, "idsii", $usuario_id, $total, $pagamento, $endereco_id, $cartao_id_bind);
    if (!mysqli_stmt_execute($stmt_pedido)) {
        throw new Exception("Erro ao inserir pedido: " . mysqli_stmt_error($stmt_pedido));
    }
    $pedido_id = mysqli_insert_id($conn);
    if (!$pedido_id) {
        throw new Exception('Erro ao criar pedido (ID não gerado).');
    }

    // Inserir itens (agora com produto_id NULL para buquês)
    foreach ($carrinho as $item) {
        $produto_id = intval($item['produto_id'] ?? 0);
        $quantidade = intval($item['quantidade'] ?? 1);
        $preco_unitario = floatval($item['preco'] ?? 0);
        $tipo = $item['tipo'] ?? 'produto';
        $detalhes = $item['detalhes'] ?? null;

        // Para buquês customizados: use NULL em produto_id
        if ($tipo === 'buque') {
            $produto_id = null;  // Agora possível após ALTER TABLE
        } else {
            // Para produtos normais, valide se ID existe (opcional, mas bom)
            if ($produto_id <= 0) {
                throw new Exception("Produto inválido no carrinho (ID: $produto_id).");
            }
        }

        $detalhes_bind = $detalhes ? $detalhes : '';  // String vazia se null

        $sql_item = "INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, tipo, detalhes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_item = mysqli_prepare($conn, $sql_item);
        if (!$stmt_item) {
            throw new Exception("Erro na preparação do item: " . mysqli_error($conn));
        }
        
        // Bind dinâmico: iiidss se produto_id não null, mas MySQLi aceita NULL em 'i'
        mysqli_stmt_bind_param($stmt_item, "iiidss", $pedido_id, $produto_id, $quantidade, $preco_unitario, $tipo, $detalhes_bind);
        if (!mysqli_stmt_execute($stmt_item)) {
            throw new Exception("Erro ao inserir item (" . ($produto_id ? "produto_id $produto_id" : "buquê customizado") . "): " . mysqli_stmt_error($stmt_item));
        }
    }

    // Limpar carrinho
    $_SESSION['cart'] = [];

    // Confirmar
mysqli_commit($conn);

// Buscar informações do pedido criado
$sql_pedido_info = "SELECT p.*, e.rua, e.numero, e.cidade, e.estado 
                    FROM pedidos p 
                    LEFT JOIN enderecos e ON p.endereco_id = e.id_endereco 
                    WHERE p.id = ?";
$stmt_info = mysqli_prepare($conn, $sql_pedido_info);
mysqli_stmt_bind_param($stmt_info, "i", $pedido_id);
mysqli_stmt_execute($stmt_info);
$result_info = mysqli_stmt_get_result($stmt_info);
$pedido_info = mysqli_fetch_assoc($result_info);

echo json_encode([
    'status' => 'ok', 
    'pedido_id' => $pedido_id,
    'total' => $total,
    'data_pedido' => $pedido_info['data_pedido'],
    'endereco_entrega' => $pedido_info['rua'] . ', ' . $pedido_info['numero'] . ' - ' . $pedido_info['cidade'] . '/' . $pedido_info['estado'],
    'pagamento' => $pedido_info['pagamento']
]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    $error_msg = $e->getMessage();
    echo json_encode(['status' => 'error', 'msg' => $error_msg]);
} finally {
    mysqli_autocommit($conn, TRUE);
}
?>
