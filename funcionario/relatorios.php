<?php
session_start();
if (!isset($_SESSION['funcionario_id'])) {
    header("Location: ../login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "gardenia");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Consultas para os relatórios
$hoje = date('Y-m-d');
$mes_atual = date('Y-m');
$primeiro_dia_mes = date('Y-m-01');

// Total de vendas do mês
$sql_vendas_mes = "SELECT SUM(total) as total_mes FROM pedidos WHERE DATE(data_pedido) >= '$primeiro_dia_mes' AND status = 'concluido'";
$result_vendas_mes = $conn->query($sql_vendas_mes);
$vendas_mes = $result_vendas_mes->fetch_assoc();
$total_mes = $vendas_mes['total_mes'] ?? 0;

// Total de vendas do dia
$sql_vendas_hoje = "SELECT SUM(total) as total_hoje FROM pedidos WHERE DATE(data_pedido) = '$hoje' AND status = 'concluido'";
$result_vendas_hoje = $conn->query($sql_vendas_hoje);
$vendas_hoje = $result_vendas_hoje->fetch_assoc();
$total_hoje = $vendas_hoje['total_hoje'] ?? 0;

// Total de pedidos
$sql_total_pedidos = "SELECT COUNT(*) as total_pedidos FROM pedidos WHERE status = 'concluido'";
$result_total_pedidos = $conn->query($sql_total_pedidos);
$total_pedidos = $result_total_pedidos->fetch_assoc()['total_pedidos'];

// Pedidos pendentes
$sql_pedidos_pendentes = "SELECT COUNT(*) as pendentes FROM pedidos WHERE status = 'pendente'";
$result_pendentes = $conn->query($sql_pedidos_pendentes);
$pedidos_pendentes = $result_pendentes->fetch_assoc()['pendentes'];

// Produtos mais vendidos
$sql_produtos_vendidos = "
    SELECT p.nome, p.categoria, SUM(pi.quantidade) as total_vendido, 
           SUM(pi.quantidade * pi.preco_unitario) as receita_total
    FROM pedido_itens pi
    INNER JOIN produtos p ON pi.produto_id = p.id
    INNER JOIN pedidos pd ON pi.pedido_id = pd.id
    WHERE pd.status = 'concluido'
    GROUP BY p.id, p.nome, p.categoria
    ORDER BY total_vendido DESC
    LIMIT 10
";
$result_produtos_vendidos = $conn->query($sql_produtos_vendidos);

// Buquês mais vendidos (pedidos com tipo 'buque')
$sql_buques_vendidos = "
    SELECT COUNT(*) as total_buques, 
           SUM(preco_unitario) as receita_buques
    FROM pedido_itens 
    WHERE tipo = 'buque'
";
$result_buques_vendidos = $conn->query($sql_buques_vendidos);
$buques_vendidos = $result_buques_vendidos->fetch_assoc();

// Vendas por método de pagamento
$sql_metodos_pagamento = "
    SELECT pagamento, COUNT(*) as total_pedidos, SUM(total) as total_valor
    FROM pedidos 
    WHERE status = 'concluido'
    GROUP BY pagamento
    ORDER BY total_valor DESC
";
$result_metodos_pagamento = $conn->query($sql_metodos_pagamento);

// Vendas por mês (últimos 6 meses)
$sql_vendas_mensais = "
    SELECT 
        DATE_FORMAT(data_pedido, '%Y-%m') as mes,
        DATE_FORMAT(data_pedido, '%b/%Y') as mes_formatado,
        COUNT(*) as total_pedidos,
        SUM(total) as total_vendas
    FROM pedidos 
    WHERE status = 'concluido' 
    AND data_pedido >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mes
    ORDER BY mes DESC
    LIMIT 6
";
$result_vendas_mensais = $conn->query($sql_vendas_mensais);

// Clientes mais frequentes
$sql_clientes_frequentes = "
    SELECT u.nome, u.email, COUNT(p.id) as total_pedidos, SUM(p.total) as total_gasto
    FROM pedidos p
    INNER JOIN usuarios u ON p.usuario_id = u.usuario_id
    WHERE p.status = 'concluido'
    GROUP BY u.usuario_id, u.nome, u.email
    ORDER BY total_gasto DESC
    LIMIT 5
";
$result_clientes_frequentes = $conn->query($sql_clientes_frequentes);

// Estoque baixo
$sql_estoque_baixo = "
    SELECT nome, estoque, preco, categoria
    FROM produtos 
    WHERE estoque <= 10 
    ORDER BY estoque ASC
    LIMIT 10
";
$result_estoque_baixo = $conn->query($sql_estoque_baixo);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Relatório de Desempenho - Gardenia</title>
<link rel="stylesheet" href="../css/relatorio.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="shortcut icon" href="../images/logo.png">
</head>
<body>
<nav class="navbar">
   <span class="navbar-brand"><span>Gardenia</span></span>
    <div class="nav-right">
        <a href="adm_funcionario.php"><i class="fa fa-arrow-left"></i> Voltar ao painel</a>
    </div>
</nav>

<div class="container">
    <h2><i class="fa fa-chart-line"></i> Relatório de Desempenho</h2>
    
    <!-- Cards de Métricas Principais -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">
                <i class="fa fa-money-bill"></i>
            </div>
            <div class="metric-info">
                <h3>Vendas do Mês</h3>
                <span class="metric-value">R$ <?= number_format($total_mes, 2, ',', '.') ?></span>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon">
                <i class="fa fa-shopping-bag"></i>
            </div>
            <div class="metric-info">
                <h3>Pagamentos Confirmados</h3>
                <span class="metric-value"><?= $total_pedidos ?></span>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon">
                <i class="fa fa-clock"></i>
            </div>
            <div class="metric-info">
                <h3>Pagamentos Pendentes</h3>
                <span class="metric-value"><?= $pedidos_pendentes ?></span>
            </div>
        </div>
        
    </div>
<br><br>
    <!-- Seção de Gráficos e Análises -->
    <div class="summary-section">
        <!-- Produtos Mais Vendidos -->
        <div class="analytics-card">
            <h3><i class="fa fa-trophy"></i> Produtos Mais Vendidos</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Quantidade</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_produtos_vendidos->num_rows > 0): ?>
                            <?php while($produto = $result_produtos_vendidos->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                                    <td>
                                        <span class="categoria-badge categoria-<?= strtolower($produto['categoria']) ?>">
                                            <?= ucfirst($produto['categoria']) ?>
                                        </span>
                                    </td>
                                    <td><?= $produto['total_vendido'] ?></td>
                                    <td>R$ <?= number_format($produto['receita_total'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">Nenhum produto vendido ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
                        </div>
                        <br>
        <!-- Métodos de Pagamento -->
         <div class="analytics-grid">
        <div class="analytics-card">
            <h3><i class="fa fa-credit-card"></i> Métodos de Pagamento</h3>
            <div class="payment-methods">
                <?php if ($result_metodos_pagamento->num_rows > 0): ?>
                    <?php while($metodo = $result_metodos_pagamento->fetch_assoc()): ?>
                        <div class="payment-method">
                            <div class="payment-info">
                                <span class="payment-type"><?= ucfirst($metodo['pagamento']) ?></span>
                                <span class="payment-count"><?= $metodo['total_pedidos'] ?> pedidos</span>
                            </div>
                            <span class="payment-amount">R$ <?= number_format($metodo['total_valor'], 2, ',', '.') ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center">Nenhum pedido concluído.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vendas Mensais -->
        <div class="analytics-card">
            <h3><i class="fa fa-chart-bar"></i> Vendas dos Últimos 6 Meses</h3>
            <div class="monthly-sales">
                <?php if ($result_vendas_mensais->num_rows > 0): ?>
                    <?php while($mes = $result_vendas_mensais->fetch_assoc()): ?>
                        <div class="month-sale">
                            <span class="month-name"><?= $mes['mes_formatado'] ?></span>
                            <div class="sale-bar-container">
                                <div class="sale-bar" style="width: <?= min(($mes['total_vendas'] / 1000) * 10, 100) ?>%"></div>
                            </div>
                            <span class="sale-amount">R$ <?= number_format($mes['total_vendas'], 2, ',', '.') ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center">Nenhuma venda nos últimos 6 meses.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Clientes Frequentes -->
        <div class="analytics-card">
            <h3><i class="fa fa-users"></i> Clientes Mais Frequentes</h3>
            <div class="top-customers">
                <?php if ($result_clientes_frequentes->num_rows > 0): ?>
                    <?php while($cliente = $result_clientes_frequentes->fetch_assoc()): ?>
                        <div class="customer">
                            <div class="customer-info">
                                <span class="customer-name"><?= htmlspecialchars($cliente['nome']) ?></span>
                                <span class="customer-email"><?= htmlspecialchars($cliente['email']) ?></span>
                            </div>
                            <div class="customer-stats">
                                <span class="customer-orders"><?= $cliente['total_pedidos'] ?> pedidos</span>
                                <span class="customer-total">R$ <?= number_format($cliente['total_gasto'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center">Nenhum cliente frequente.</p>
                <?php endif; ?>
            </div>
        </div>

       
    </div>
 <!-- Estoque Baixo -->
        <div class="analytics-card">
            <h3><i class="fa fa-exclamation-triangle"></i> Estoque Baixo</h3>
            <div class="low-stock">
                <?php if ($result_estoque_baixo->num_rows > 0): ?>
                    <?php while($produto = $result_estoque_baixo->fetch_assoc()): ?>
                        <div class="stock-item <?= $produto['estoque'] <= 5 ? 'critical' : 'low' ?>">
                            <div class="stock-info">
                                <span class="product-name"><?= htmlspecialchars($produto['nome']) ?></span>
                                <span class="product-category"><?= ucfirst($produto['categoria']) ?></span>
                            </div>
                            <div class="stock-details">
                                <span class="stock-quantity"><?= $produto['estoque'] ?> unidades</span>
                                <span class="stock-price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center">Estoque em níveis adequados.</p>
                <?php endif; ?>
            </div>
        </div>
<script>
// Atualizar dados a cada 5 minutos
setTimeout(() => {
    window.location.reload();
}, 300000);

// Adicionar interações
document.addEventListener('DOMContentLoaded', function() {
    const metricCards = document.querySelectorAll('.metric-card');
    metricCards.forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
});
</script>
</body>
</html>

<?php
$conn->close();
?>