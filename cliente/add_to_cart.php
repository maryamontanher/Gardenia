<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
include_once("../conexao.php");

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status'=>'erro','msg'=>'Você precisa estar logado!']);
    exit;
}

// Iniciar carrinho se não existir
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$response = [];

try {
    /* ================= BUQUÊ PERSONALIZADO ================= */
    if (isset($_POST['tipo']) && $_POST['tipo'] === 'buque') {
        $flores = json_decode($_POST['flores'] ?? '[]', true);
        $embalagens = json_decode($_POST['embalagens'] ?? '[]', true);
        $laco = $_POST['laco'] ?? '';
        $obs = $_POST['obs'] ?? '';
        $preco = (float) str_replace(",",".", $_POST['preco'] ?? 0);

        // Validar se há pelo menos uma flor
        $flores_validas = array_filter($flores, function($flor) {
            return isset($flor['quantidade']) && $flor['quantidade'] > 0;
        });

        if (empty($flores_validas)) {
            throw new Exception('Selecione pelo menos uma flor para o buquê!');
        }

        // Chave única pro carrinho
        $cart_key = "buque_" . time() . "_" . uniqid();

        // Construir nome descritivo
        $nomes_flores = [];
        foreach($flores_validas as $flor) {
            $cor_texto = !empty($flor['cor']) ? " ({$flor['cor']})" : "";
            $nomes_flores[] = $flor['quantidade'] . "x " . $flor['nome'] . $cor_texto;
        }
        
        $nome_buque = 'Buquê Personalizado';
        if(!empty($nomes_flores)) {
            $nome_buque .= ' com ' . implode(', ', $nomes_flores);
        }
        if(!empty($embalagens)) {
            $nome_buque .= ' + ' . implode(', ', $embalagens);
        }
        if($laco) {
            $nome_buque .= ' + ' . $laco;
        }

        // Preparar detalhes para o pedido
        $detalhes = json_encode([
            'flores' => $flores_validas,
            'embalagens' => $embalagens,
            'laco' => $laco,
            'obs' => $obs
        ]);

        $_SESSION['cart'][$cart_key] = [
            'nome'       => $nome_buque,
            'preco'      => $preco,
            'quantidade' => 1,
            'imagem'     => 'bouquet.jpg',
            'tipo'       => 'buque',
            'produto_id' => null,
            'detalhes'   => $detalhes
        ];

        $response = ['status' => 'ok', 'msg' => 'Buquê personalizado adicionado ao carrinho!'];

    } else {
        /* ================= PRODUTO NORMAL ================= */
        if (isset($_POST['produto_id'])) {
            $produto_id = (int) $_POST['produto_id'];
            $quantidade = (int) ($_POST['quantidade'] ?? 1);
            $cor = $_POST['cor'] ?? '';

            // Buscar produto no banco
            $sql = "SELECT * FROM produtos WHERE id = ? LIMIT 1";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $produto_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $produto = mysqli_fetch_assoc($result);

            if (!$produto) {
                throw new Exception('Produto não encontrado!');
            }

            // Usar ID + cor como chave para diferenciar cores
            $cart_key = $produto_id . ($cor ? "_$cor" : '');

            // Adicionar ou atualizar quantidade
            if (isset($_SESSION['cart'][$cart_key])) {
                $_SESSION['cart'][$cart_key]['quantidade'] += $quantidade;
            } else {
                $_SESSION['cart'][$cart_key] = [
                    'nome'       => $produto['nome'],
                    'preco'      => (float)$produto['preco'],
                    'imagem'     => !empty($produto['imagem']) ? $produto['imagem'] : 'sem-imagem.jpg',
                    'quantidade' => $quantidade,
                    'cor'        => $cor,
                    'tipo'       => 'produto',
                    'produto_id' => $produto_id
                ];
            }

            $response = ['status' => 'ok', 'msg' => 'Produto adicionado ao carrinho!'];
        } else {
            throw new Exception('Parâmetros inválidos!');
        }
    }

    /* ================= CALCULAR TOTAL ================= */
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += ((float)($item['preco'] ?? 0)) * ((int)($item['quantidade'] ?? 1));
    }

    /* ================= RETORNO JSON ================= */
    $response = array_merge($response, [
        'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantidade')),
        'cart_total' => number_format($total, 2, ",", "."),
        'cart_items' => $_SESSION['cart']
    ]);

} catch (Exception $e) {
    $response = ['status' => 'erro', 'msg' => $e->getMessage()];
}

echo json_encode($response);
mysqli_close($conn);
?>