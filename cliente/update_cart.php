<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
include_once("../conexao.php");

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'erro', 'msg' => 'Você precisa estar logado!']);
    exit;
}

// Inicializa carrinho se não existir
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Pega dados da requisição
$id = $_POST['id'] ?? '';
$action = $_POST['action'] ?? '';

if (!$id || !$action) {
    echo json_encode(['status' => 'erro', 'msg' => 'Parâmetros inválidos!']);
    exit;
}

// Verifica se item existe no carrinho
if (!isset($_SESSION['cart'][$id])) {
    echo json_encode(['status' => 'erro', 'msg' => 'Item não encontrado no carrinho!']);
    exit;
}

// Atualiza conforme a ação
switch ($action) {
    case 'increase':
        // Para buquês, permita múltiplos (ex: 2 buquês personalizados)
        // Para produtos, verifique estoque opcionalmente (descomente se quiser)
        $_SESSION['cart'][$id]['quantidade'] = intval($_SESSION['cart'][$id]['quantidade'] ?? 1) + 1;
        
        // Opcional: Verificar estoque para produtos normais
        // if ($_SESSION['cart'][$id]['tipo'] === 'produto') {
        //     $produto_id = intval($_SESSION['cart'][$id]['produto_id'] ?? 0);
        //     if ($produto_id > 0) {
        //         $sql = "SELECT estoque FROM produtos WHERE id = ?";
        //         $stmt = mysqli_prepare($conn, $sql);
        //         mysqli_stmt_bind_param($stmt, "i", $produto_id);
        //         mysqli_stmt_execute($stmt);
        //         $result = mysqli_stmt_get_result($stmt);
        //         $produto = mysqli_fetch_assoc($result);
        //         if (($produto['estoque'] ?? 0) < $_SESSION['cart'][$id]['quantidade']) {
        //             echo json_encode(['status' => 'erro', 'msg' => 'Estoque insuficiente!']);
        //             exit;
        //         }
        //     }
        // }
        break;

    case 'decrease':
        $_SESSION['cart'][$id]['quantidade'] = intval($_SESSION['cart'][$id]['quantidade'] ?? 1) - 1;
        if ($_SESSION['cart'][$id]['quantidade'] <= 0) {
            unset($_SESSION['cart'][$id]);
        }
        break;

    case 'remove':
        unset($_SESSION['cart'][$id]);
        break;

    default:
        echo json_encode(['status' => 'erro', 'msg' => 'Ação inválida!']);
        exit;
}

// Recalcula total de forma segura
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $preco = floatval($item['preco'] ?? 0);
    $quantidade = intval($item['quantidade'] ?? 1);
    $total += $preco * $quantidade;
}

// Verifica se carrinho ficou vazio
if (empty($_SESSION['cart'])) {
    $_SESSION['cart'] = [];  // Limpa explicitamente
}

// Retorna JSON atualizado
echo json_encode([
    'status'     => 'ok',
    'cart_count' => array_sum(array_map(function($item) {
        return intval($item['quantidade'] ?? 1);
    }, $_SESSION['cart'])),  // Mais seguro que array_column se faltar 'quantidade'
    'cart_total' => number_format($total, 2, ",", "."),
    'cart_items' => $_SESSION['cart']
]);

mysqli_close($conn);
?>
