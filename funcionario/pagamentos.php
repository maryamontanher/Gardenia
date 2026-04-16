<?php
session_start();
if (!isset($_SESSION['funcionario_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once('../src/PHPMailer.php');
require_once('../src/SMTP.php');
require_once('../src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$conn = new mysqli("localhost", "root", "", "gardenia");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Função para gerar nota fiscal em PDF
function gerarNotaFiscal($pedidoId, $conn) {
    // Buscar informações completas do pedido
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            u.nome as cliente_nome,
            u.cpf as cliente_cpf,
            u.email as cliente_email,
            e.rua, e.numero, e.complemento, e.bairro, e.cidade, e.estado, e.cep
        FROM pedidos p
        INNER JOIN usuarios u ON p.usuario_id = u.usuario_id
        LEFT JOIN enderecos e ON p.endereco_id = e.id_endereco
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $pedido = $result->fetch_assoc();
    $stmt->close();

    // Buscar itens do pedido
    $stmt_itens = $conn->prepare("
        SELECT 
            pi.*,
            pr.nome as produto_nome,
            pr.descricao as produto_descricao
        FROM pedido_itens pi
        LEFT JOIN produtos pr ON pi.produto_id = pr.id
        WHERE pi.pedido_id = ?
    ");
    $stmt_itens->bind_param("i", $pedidoId);
    $stmt_itens->execute();
    $itens_result = $stmt_itens->get_result();
    $itens = [];
    while($item = $itens_result->fetch_assoc()) {
        $itens[] = $item;
    }
    $stmt_itens->close();

    // Gerar HTML da nota fiscal
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Nota Fiscal - Pedido #{$pedidoId}</title>
        <style>
            @font-face {
                font-family: 'tan pearl';
                src: url('../fonte/fonnts.com-tan-pearl.otf');
            }
            body { 
                font-family: 'Times New Roman', Times, serif; 
                margin: 0; 
                padding: 20px; 
                color: #333;
                background: white;
            }
            .nota-fiscal { 
                max-width: 800px; 
                margin: 0 auto; 
                border: 2px solid #584316;
                border-radius: 10px;
                padding: 30px;
                background: white;
            }
            .header { 
                text-align: center; 
                border-bottom: 2px solid #9ba885;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .header h1 { 
                color: #584316; 
                font-family: 'tan pearl', serif;
                font-size: 28px;
                margin: 0;
            }
            .header .subtitle {
                color: #8a7f6e;
                font-size: 16px;
                margin: 5px 0;
            }
            .info-section { 
                margin-bottom: 25px; 
                padding: 15px;
                background: #f9f5ef;
                border-radius: 8px;
            }
            .info-section h3 { 
                color: #584316; 
                border-bottom: 1px solid #d4c9b8;
                padding-bottom: 8px;
                margin-top: 0;
            }
            .info-grid { 
                display: grid; 
                grid-template-columns: 1fr 1fr; 
                gap: 15px; 
            }
            .info-item { margin-bottom: 8px; }
            .info-label { 
                font-weight: bold; 
                color: #584316;
                display: inline-block;
                width: 120px;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0;
            }
            table th { 
                background: #9ba885; 
                color: white; 
                padding: 12px; 
                text-align: left;
                font-weight: 600;
            }
            table td { 
                padding: 10px 12px; 
                border-bottom: 1px solid #e9e1d5;
            }
            .total-section { 
                text-align: right; 
                margin-top: 20px;
                padding: 15px;
                background: #f9f5ef;
                border-radius: 8px;
            }
            .total-line {
                display: flex;
                justify-content: space-between;
                margin: 5px 0;
                max-width: 300px;
                margin-left: auto;
            }
            .total-final {
                font-size: 18px;
                font-weight: bold;
                color: #584316;
                border-top: 2px solid #9ba885;
                padding-top: 10px;
            }
            .footer { 
                text-align: center; 
                margin-top: 30px; 
                padding-top: 20px;
                border-top: 1px solid #d4c9b8;
                color: #8a7f6e;
                font-size: 14px;
            }
            .qr-code {
                text-align: center;
                margin: 20px 0;
                padding: 15px;
                border: 1px dashed #9ba885;
                border-radius: 8px;
            }
            .status-badge {
                display: inline-block;
                padding: 5px 10px;
                background: #28a745;
                color: white;
                border-radius: 15px;
                font-size: 12px;
                font-weight: bold;
            }
            @media print {
                body { padding: 0; }
                .nota-fiscal { border: none; box-shadow: none; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class='nota-fiscal'>
            <div class='header'>
                <h1>GARDENIA FLORICULTURA</h1>
                <div class='subtitle'>NOTA FISCAL ELETRÔNICA</div>
                <div class='subtitle'>Pedido #{$pedidoId}</div>
            </div>

            <div class='info-grid'>
                <div class='info-section'>
                    <h3>Dados do Pedido</h3>
                    <div class='info-item'><span class='info-label'>Número:</span> #{$pedidoId}</div>
                    <div class='info-item'><span class='info-label'>Data:</span> " . date('d/m/Y H:i', strtotime($pedido['data_pedido'])) . "</div>
                    <div class='info-item'><span class='info-label'>Status:</span> <span class='status-badge'>" . ucfirst($pedido['status']) . "</span></div>
                    <div class='info-item'><span class='info-label'>Pagamento:</span> " . ucfirst($pedido['pagamento']) . "</div>
                </div>

                <div class='info-section'>
                    <h3> Dados do Cliente</h3>
                    <div class='info-item'><span class='info-label'>Nome:</span> {$pedido['cliente_nome']}</div>
                    <div class='info-item'><span class='info-label'>CPF:</span> {$pedido['cliente_cpf']}</div>
                    <div class='info-item'><span class='info-label'>Email:</span> {$pedido['cliente_email']}</div>
                </div>
            </div>";

    // Adicionar endereço de entrega se existir
    if ($pedido['rua']) {
        $html .= "
            <div class='info-section'>
                <h3> Endereço de Entrega</h3>
                <div class='info-item'><span class='info-label'>Endereço:</span> {$pedido['rua']}, {$pedido['numero']}</div>
                <div class='info-item'><span class='info-label'>Complemento:</span> " . ($pedido['complemento'] ?: '—') . "</div>
                <div class='info-item'><span class='info-label'>Bairro:</span> {$pedido['bairro']}</div>
                <div class='info-item'><span class='info-label'>Cidade/UF:</span> {$pedido['cidade']}/{$pedido['estado']}</div>
                <div class='info-item'><span class='info-label'>CEP:</span> {$pedido['cep']}</div>
            </div>";
    }

    $html .= "
            <div class='info-section'>
                <h3> Itens do Pedido</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Descrição</th>
                            <th>Quantidade</th>
                            <th>Valor Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>";

    $total_pedido = 0;
    foreach($itens as $item) {
        $subtotal = $item['quantidade'] * $item['preco_unitario'];
        $total_pedido += $subtotal;
        
        $nome_produto = $item['produto_nome'] ?: 'Buque Personalizado';
        $descricao = $item['produto_descricao'] ?: 'Arranjo floral especial';
        
        $html .= "
                        <tr>
                            <td>{$nome_produto}</td>
                            <td>{$descricao}</td>
                            <td>{$item['quantidade']}</td>
                            <td>R$ " . number_format($item['preco_unitario'], 2, ',', '.') . "</td>
                            <td>R$ " . number_format($subtotal, 2, ',', '.') . "</td>
                        </tr>";
    }

    $html .= "
                    </tbody>
                </table>
            </div>

            <div class='total-section'>
                <div class='total-line'>
                    <span>Subtotal:</span>
                    <span>R$ " . number_format($total_pedido, 2, ',', '.') . "</span>
                </div>
                <div class='total-line'>
                    <span>Frete:</span>
                    <span>R$ 0,00</span>
                </div>
                <div class='total-line total-final'>
                    <span>TOTAL:</span>
                    <span>R$ " . number_format($total_pedido, 2, ',', '.') . "</span>
                </div>
            </div>

            <div class='qr-code'>
                <h4> Código de Verificação</h4>
                <p><strong>Pedido #{$pedidoId}</strong></p>
                <p>Data: " . date('d/m/Y H:i', strtotime($pedido['data_pedido'])) . "</p>
                <p>Valor: R$ " . number_format($total_pedido, 2, ',', '.') . "</p>
            </div>

            <div class='footer'>
                <p><strong>Gardenia Floricultura</strong></p>
                <p> (11) 9999-9999 |  contato@gardenia.com</p>
                <p>Este documento não substitui a Nota Fiscal Eletrônica</p>
                <p>Emitido em: " . date('d/m/Y H:i') . "</p>
            </div>

            <div class='no-print' style='text-align: center; margin-top: 20px;'>
                <button onclick='window.print()' style='
                    background: #9ba885; 
                    color: white; 
                    border: none; 
                    padding: 10px 20px; 
                    border-radius: 5px; 
                    cursor: pointer;
                    font-family: inherit;
                '>
                     Imprimir Nota Fiscal
                </button>
            </div>
        </div>
    </body>
    </html>";

    return $html;
}

// Função para enviar email de confirmação
function enviarEmailConfirmacao($emailCliente, $nomeCliente, $pedidoId, $total, $conn) {
    // ... (código da função enviarEmailConfirmacao permanece igual) ...
    // Buscar detalhes do pedido - CORREÇÃO: usando a tabela correta pedido_itens
    $stmt = $conn->prepare("
        SELECT p.*, pi.quantidade, pi.preco_unitario, pr.nome as produto_nome
        FROM pedidos p
        LEFT JOIN pedido_itens pi ON p.id = pi.pedido_id
        LEFT JOIN produtos pr ON pi.produto_id = pr.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $produtos = [];
    while($item = $result->fetch_assoc()) {
        if ($item['produto_nome']) {
            $produtos[] = $item['produto_nome'];
        }
    }
    $stmt->close();

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'medsyncc@gmail.com';
        $mail->Password = 'rcdu jzij lotr akgk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom('medsyncc@gmail.com', 'Gardenia - Confirmação de Pagamento');
        $mail->addAddress($emailCliente, $nomeCliente);

        $mail->isHTML(true);
        $mail->Subject = '✅ Pagamento Confirmado - Pedido #' . $pedidoId;
        
        $produtos_lista = !empty($produtos) ? implode(', ', $produtos) : 'Buque personalizado';
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; background: #f9f9f9; }
                    .container { max-width: 600px; margin: 0 auto; background: white; }
                    .header { background: linear-gradient(135deg, #584316, #8a6d3b); color: white; padding: 30px; text-align: center; }
                    .content { padding: 30px; }
                    .order-details { background: #f9f5ef; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #9ba885; }
                    .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 14px; }
                    .button { background: #9ba885; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
                    .product-list { background: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🎉 Pagamento Confirmado!</h1>
                        <p>Gardenia - Sua floricultura especial</p>
                    </div>
                    <div class='content'>
                        <h2>Olá, {$nomeCliente}!</h2>
                        <p>Seu pagamento foi confirmado com sucesso. Agora vamos preparar seu pedido com todo cuidado e carinho.</p>
                        
                        <div class='order-details'>
                            <h3>📦 Detalhes do Pedido #{$pedidoId}</h3>
                            <div class='product-list'>
                                <p><strong>Produtos:</strong> {$produtos_lista}</p>
                                <p><strong>Total:</strong> R$ " . number_format($total, 2, ',', '.') . "</p>
                            </div>
                            <p><strong>Status:</strong> ✅ Pagamento Confirmado</p>
                            <p><strong>Data da Confirmação:</strong> " . date('d/m/Y H:i') . "</p>
                        </div>
                        
                        <p><strong>📋 Próximos Passos:</strong></p>
                        <ul>
                            <li>✅ Seu pedido está sendo preparado por nossa equipe</li>
                            <li>📧 Você receberá atualizações por email</li>
                            <li>🚚 Previsão de entrega: 5-10 dias úteis</li>
                            <li>💚 Qualquer dúvida, entre em contato conosco</li>
                        </ul>
                        
                        <p style='text-align: center; margin-top: 30px;'>
                            <a href='#' class='button'>Acompanhar Meu Pedido</a>
                        </p>
                    </div>
                    <div class='footer'>
                        <p><strong>Gardenia Floricultura</strong></p>
                        <p>Obrigado pela sua compra! 💚</p>
                        <p>Dúvidas? Entre em contato: contato@gardenia.com</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $mail->AltBody = "Olá {$nomeCliente}!\n\nSeu pagamento do pedido #{$pedidoId} no valor de R$ " . number_format($total, 2, ',', '.') . " foi confirmado com sucesso!\n\nProdutos: {$produtos_lista}\n\nAgora vamos preparar seu pedido com todo cuidado.\n\nPróximos passos:\n- ✅ Pedido em preparação\n- 📧 Atualizações por email\n- 🚚 Previsão de entrega: 5-10 dias úteis\n- 💚 Qualquer dúvida, entre em contato\n\nObrigado pela sua compra!\nGardenia Floricultura";

        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Erro ao enviar email: " . $mail->ErrorInfo);
        return false;
    }
}

// VERIFICAR SE É UMA REQUISIÇÃO PARA IMPRIMIR NOTA FISCAL
if (isset($_GET['imprimir']) && isset($_GET['id'])) {
    $pedidoId = intval($_GET['id']);
    $html_nota = gerarNotaFiscal($pedidoId, $conn);
    echo $html_nota;
    exit();
}

// VERIFICAR SE É UMA REQUISIÇÃO AJAX PARA CONFIRMAR PAGAMENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Limpar qualquer saída anterior
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    
    // Desativar exibição de erros para não corromper o JSON
    error_reporting(0);
    ini_set('display_errors', 0);

    $id = intval($_POST['id']);
    $status = 'concluido';

    // Buscar informações do pedido e usuário
    $check = $conn->prepare("
        SELECT p.*, u.nome AS usuario_nome, u.email AS usuario_email 
        FROM pedidos p 
        INNER JOIN usuarios u ON p.usuario_id = u.usuario_id 
        WHERE p.id = ?
    ");
    
    if (!$check) {
        echo json_encode(["success" => false, "message" => "Erro na preparação da consulta"]);
        exit();
    }
    
    $check->bind_param("i", $id);
    
    if (!$check->execute()) {
        echo json_encode(["success" => false, "message" => "Erro ao executar consulta"]);
        exit();
    }
    
    $res = $check->get_result();

    if (!$res || $res->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Pedido não encontrado"]);
        exit();
    }

    $row = $res->fetch_assoc();
    
    // Verificar se o pedido já está concluído
    if (strtolower($row['status']) !== 'pendente') {
        echo json_encode(["success" => false, "message" => "Pedido já confirmado ou em outro status"]);
        exit();
    }

    $check->close();

    // Atualizar status do pedido
    $stmt = $conn->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
    
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Erro ao preparar atualização"]);
        exit();
    }
    
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Tentar enviar email de confirmação
        $emailEnviado = false;
        $emailError = "";
        
        try {
            $emailEnviado = enviarEmailConfirmacao(
                $row['usuario_email'], 
                $row['usuario_nome'], 
                $id, 
                $row['total'],
                $conn
            );
        } catch (Exception $e) {
            $emailError = $e->getMessage();
            error_log("Erro no envio de email: " . $emailError);
        }

        $response = [
            "success" => true, 
            "message" => "Pagamento confirmado" . ($emailEnviado ? " e email enviado com sucesso" : " (email não enviado: " . $emailError . ")")
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode(["success" => false, "message" => "Erro ao atualizar status do pedido"]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// CÓDIGO PARA EXIBIÇÃO DA PÁGINA (apenas para GET)
$sql = "
    SELECT 
        p.id, p.total, p.status, p.data_pedido, p.pagamento, 
        p.endereco_id, p.cartao_id, 
        u.nome AS usuario_nome, u.cpf AS usuario_cpf, u.email AS usuario_email
    FROM pedidos p
    INNER JOIN usuarios u ON p.usuario_id = u.usuario_id
    ORDER BY p.data_pedido DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Relatório de Pagamentos - Gardenia</title>
<link rel="stylesheet" href="../css/pagamento.css">
<link rel="shortcut icon" href="../images/logo.png">
<style>
    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
    }
    .print-btn {
        background: #8a6d3b;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s ease;
    }
    .print-btn:hover {
        background: #584316;
        transform: translateY(-2px);
    }
    .print-btn:disabled {
        background: #d4c9b8;
        cursor: not-allowed;
        transform: none;
    }
</style>
</head>

<body>
<nav class="navbar">
   <span class="navbar-brand"><span>Gardenia</span></span>
    <div class="nav-right">
        <a href="adm_funcionario.php">Voltar ao painel</a>
    </div>
</nav>

<div class="container">
    <h2>Relatório de Pagamentos</h2>

    <div class="filtro-container">
        <input type="text" id="filtroId" placeholder="Filtrar por ID do pedido">
        <input type="text" id="filtroCpf" placeholder="Filtrar por CPF">
        <input type="text" id="filtroNome" placeholder="Filtrar por Nome">
        <select id="filtroStatus">
            <option value="todos">Todos</option>
            <option value="pendente">Pendente</option>
            <option value="concluido">Concluído</option>
        </select>
    </div>

    <div class="table-container">
        <table class="table" id="tabelaPedidos">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome do Usuário</th>
                    <th>CPF</th>
                    <th>Total (R$)</th>
                    <th>Status</th>
                    <th>Data do Pedido</th>
                    <th>Pagamento</th>
                    <th>Endereço ID</th>
                    <th>Cartão ID</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr 
                        data-id="<?= $row['id'] ?>" 
                        data-status="<?= strtolower($row['status']) ?>"
                        data-cpf="<?= htmlspecialchars($row['usuario_cpf']) ?>"
                        data-nome="<?= strtolower(htmlspecialchars($row['usuario_nome'])) ?>"
                    >
                        <td>#<?= htmlspecialchars($row['id']) ?></td>
                        <td>
                            <?= htmlspecialchars($row['usuario_nome']) ?>
                            <br><small><?= htmlspecialchars($row['usuario_email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['usuario_cpf']) ?></td>
                        <td><strong>R$ <?= number_format($row['total'], 2, ',', '.') ?></strong></td>
                        <td>
                            <span class="status-<?= strtolower($row['status']) ?>">
                                <?= ucfirst(htmlspecialchars($row['status'])) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($row['data_pedido'])) ?></td>
                        <td>
                            <span class="pagamento-badge pagamento-<?= strtolower($row['pagamento']) ?>">
                                <?= ucfirst(htmlspecialchars($row['pagamento'])) ?>
                            </span>
                        </td>
                        <td><?= $row['endereco_id'] ?? '—' ?></td>
                        <td><?= $row['cartao_id'] ?? '—' ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if (strtolower($row['status']) === 'pendente'): ?>
                                    <button class="confirm-btn" id="btn-<?= $row['id'] ?>" onclick="confirmarPagamento(<?= $row['id'] ?>)">
                                         Confirmar
                                    </button>
                                <?php else: ?>
                                    <button class="confirm-btn" disabled>
                                         Confirmado
                                    </button>
                                <?php endif; ?>
                                <button class="print-btn" onclick="imprimirNotaFiscal(<?= $row['id'] ?>)">
                                     Nota
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center">
                        <p style="text-align: center; padding: 2rem; color: #666;">
                            Nenhum pedido encontrado.
                        </p>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Função para imprimir nota fiscal
function imprimirNotaFiscal(pedidoId) {
    const url = `?imprimir=1&id=${pedidoId}`;
    const janela = window.open(url, '_blank', 'width=900,height=700');
    
    // Focar na janela após carregar
    janela.focus();
}

// Função para mostrar toast
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        toast.style.transform = 'translateX(400px)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

async function confirmarPagamento(id) {
    if (!confirm("Deseja realmente confirmar o pagamento deste pedido?\nUm email será enviado ao cliente.")) {
        return;
    }

    const btn = document.getElementById(`btn-${id}`);
    const originalText = btn.textContent;
    
    btn.disabled = true;
    btn.textContent = " Confirmando...";
    btn.classList.add('loading');

    try {
        const response = await fetch(window.location.href, {
            method: "POST",
            headers: { 
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "id=" + encodeURIComponent(id)
        });
        
        // Verificar se a resposta é JSON válido
        const text = await response.text();
        let data;
        
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Resposta não é JSON:', text);
            throw new Error('Resposta inválida do servidor');
        }
        
        if (data.success) {
            const linha = btn.closest("tr");
            const statusTd = linha.querySelector('td:nth-child(5)');
            
            // Atualizar status
            statusTd.innerHTML = '<span class="status-concluido">Concluído</span>';
            linha.setAttribute("data-status", "concluido");
            
            // Atualizar botão
            btn.textContent = " Confirmado";
            btn.style.background = "#6c757d";
            btn.classList.remove('loading');
            
            showToast(data.message, 'success');
            
        } else {
            throw new Error(data.message || "Erro desconhecido");
        }
        
    } catch (error) {
        console.error('Erro:', error);
        btn.disabled = false;
        btn.textContent = originalText;
        btn.classList.remove('loading');
        showToast(error.message || "Erro ao confirmar pagamento", 'error');
    }
}

// Filtros
const filtroStatus = document.getElementById('filtroStatus');
const filtroId = document.getElementById('filtroId');
const filtroCpf = document.getElementById('filtroCpf');
const filtroNome = document.getElementById('filtroNome');
const linhas = document.querySelectorAll('#tabelaPedidos tbody tr');

function filtrarPedidos() {
    const statusSelecionado = filtroStatus.value.toLowerCase();
    const idDigitado = filtroId.value.trim();
    const cpfDigitado = filtroCpf.value.trim();
    const nomeDigitado = filtroNome.value.trim().toLowerCase();

    linhas.forEach(linha => {
        const status = linha.getAttribute('data-status');
        const id = linha.getAttribute('data-id');
        const cpf = linha.getAttribute('data-cpf');
        const nome = linha.getAttribute('data-nome');

        const condStatus = (statusSelecionado === 'todos') || (status === statusSelecionado);
        const condId = (!idDigitado) || id.includes(idDigitado);
        const condCpf = (!cpfDigitado) || cpf.includes(cpfDigitado);
        const condNome = (!nomeDigitado) || nome.includes(nomeDigitado);

        linha.style.display = (condStatus && condId && condCpf && condNome) ? '' : 'none';
    });
}

[filtroStatus, filtroId, filtroCpf, filtroNome].forEach(el => {
    el.addEventListener('input', filtrarPedidos);
    el.addEventListener('change', filtrarPedidos);
});
</script>
</body>
</html>

<?php
$conn->close();
?>