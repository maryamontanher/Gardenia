<?php
session_start();
include_once("../conexao.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Buscar usuário


$usuario_id = $_SESSION['usuario_id'];
$sql_user = "SELECT * FROM usuarios WHERE usuario_id = ?";
$stmt_user = mysqli_prepare($conn, $sql_user);
mysqli_stmt_bind_param($stmt_user, "i", $usuario_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$usuario = mysqli_fetch_assoc($result_user);

// Definir foto padrão se não tiver
$foto_perfil = !empty($usuario['foto']) ? '../cliente/img/uploads/' . $usuario['foto'] : '../cliente/img/default-avatar.jpg';
// Buscar endereços
$sql_enderecos = "SELECT * FROM enderecos WHERE usuario_id = ?";
$stmt_end = mysqli_prepare($conn, $sql_enderecos);
mysqli_stmt_bind_param($stmt_end, "i", $usuario_id);
mysqli_stmt_execute($stmt_end);
$result_end = mysqli_stmt_get_result($stmt_end);
$enderecos = mysqli_fetch_all($result_end, MYSQLI_ASSOC);

// Buscar cartões
$sql_cartoes = "SELECT * FROM cartoes_credito WHERE usuario_id = ?";
$stmt_cart = mysqli_prepare($conn, $sql_cartoes);
mysqli_stmt_bind_param($stmt_cart, "i", $usuario_id);
mysqli_stmt_execute($stmt_cart);
$result_cart = mysqli_stmt_get_result($stmt_cart);
$cartoes = mysqli_fetch_all($result_cart, MYSQLI_ASSOC);

// Buscar pedidos do usuário
$sql_pedidos = "
    SELECT p.id, p.total, p.status, p.data_pedido, p.pagamento
    FROM pedidos p
    WHERE p.usuario_id = ?
    ORDER BY p.data_pedido DESC
";

// Carrinho da sessão
$carrinho = $_SESSION['cart'] ?? [];

$stmt = $conn->prepare($sql_pedidos);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result_pedidos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">   
<title>Painel do Cliente - Floricultura</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/historico.css"> 
<link rel="shortcut icon" href="../images/logo.png">
<style>
.pedido {
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 25px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.item {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  padding: 15px;
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  margin-bottom: 12px;
  background: #fafafa;
}

.item img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 8px;
  flex-shrink: 0;
}

.item-info {
  flex: 1;
}

.item-info p {
  margin: 4px 0;
  color: #333;
}

.item-info strong {
  color: #2c3e50;
  font-size: 1.1em;
}

.pedido-info {
  margin-top: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #9ba885;
}

.info-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
  padding: 5px 0;
}

.info-line.total {
  border-top: 1px solid #ddd;
  padding-top: 10px;
  margin-top: 10px;
  font-weight: bold;
  font-size: 1.1em;
}

.info-label {
  font-weight: 600;
  color: #555;
}

.info-value {
  color: #333;
}

.status {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.85em;
  font-weight: 600;
}

.status.pendente {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
}

.status.enviado {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

.status.entregue {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.observacao {
  font-style: italic;
  color: #666;
  font-size: 0.9em;
  margin-top: 8px;
  padding-left: 10px;
  border-left: 3px solid #9ba885;
}

/* Apenas ajustes internos mínimos */
.cart-modal, .profile-modal, .contact-modal { z-index: 9999; }
.checkout-step { display: none; }
</style>
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
      <ul>
        <li><a href="#" id="open-profile" class="profile-btn">Perfil</a></li>
        <li><a href="#" id="open-contact">Contato</a></li>
      </ul>
    </div>
    <div class="nav-center">
      <img src="../images/logo.png" alt="Logo">
    </div>
    <div class="nav-right">
        
      <a href="painel_cliente.php">inicio</a>
      <a href="#" class="cart"><i class="fa fa-shopping-cart"></i> <span id="cart-count"><?= array_sum(array_column($carrinho, 'quantidade')) ?></span></a>
      <a href="#" onclick="confirmarSaida(event)">Sair</a>
    </div>
</nav>

<!-- Modal Carrinho -->
<div id="cart-modal" class="cart-modal">
  <div class="cart-content">
    <div class="cart-header">
      <h3>Carrinho</h3>
      <button id="close-cart"><i class="fa fa-times"></i></button>
    </div>

    <div class="cart-body">
      <?php $total = 0; ?>
      <?php if(empty($carrinho)): ?>
        <p>Seu carrinho está vazio.</p>
      <?php else: ?>
        <ul id="cart-items">
          <?php foreach($carrinho as $id => $item):
            $subtotal = $item['preco'] * $item['quantidade'];
            $total += $subtotal;
          ?>
          <li class="cart-item" data-id="<?= $id ?>">
            <img src="../images/<?= !empty($item['imagem']) ? $item['imagem'] : 'default.jpg' ?>" alt="<?= htmlspecialchars($item['nome']) ?>">
            <div class="cart-info">
              <h4><?= htmlspecialchars($item['nome']) ?></h4>
              <p>R$ <?= number_format($item['preco'],2,",",".") ?> x 
                 <span class="item-quantity"><?= $item['quantidade'] ?></span> = 
                 R$ <span class="item-subtotal"><?= number_format($subtotal,2,",",".") ?></span>
              </p>
              <div class="cart-actions">
                <button class="decrease">-</button>
                <button class="increase">+</button>
                <button class="remove-item">Remover</button>
              </div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="cart-footer">
      <p><strong>Total: R$ <span id="cart-total"><?= number_format($total,2,",",".") ?></span></strong></p>
      <button id="open-checkout" class="checkout-btn" <?= empty($carrinho) ? 'disabled' : '' ?>>Finalizar Compra</button>
    </div>
<!-- Checkout -->
<div id="checkout-step" class="checkout-step">
  <!-- Header -->
  <div class="checkout-header">
    <h3>Finalizar Compra</h3>
    <button id="back-to-cart"><i class="fa fa-arrow-left"></i> Voltar</button>
  </div>
  
  <!-- Body -->
  <div class="checkout-body">
<h3></i> Selecione um endereço</h3>
<form id="form-endereco">

  <?php foreach($enderecos as $end): ?>
    <label>
      <input type="radio" name="endereco_id" value="<?= $end['id_endereco'] ?>">
      <i class="fa fa-location-dot"></i>
      <?= $end['rua'] ?>, <?= $end['numero'] ?> - <?= $end['cidade'] ?>/<?= $end['estado'] ?>
    </label><br>
  <?php endforeach; ?>

  <!-- Botão para adicionar novo endereço -->
  <div class="checkout-section address">
    <button type="button" id="show-endereco-form">
      <i class="fa fa-plus"></i> Adicionar novo endereço
    </button>

 <!-- Formulário oculto --> <div id="novo-endereco-form" style="display:none; margin-top:10px;">
   <input type="text" name="cep" placeholder="CEP" required> 
   <input type="text" name="estado" placeholder="Estado" required> 
   <input type="text" name="cidade" placeholder="Cidade" required> 
   <input type="text" name="bairro" placeholder="Bairro" required> 
   <input type="text" name="rua" placeholder="Rua" required> 
   <input type="text" name="numero" placeholder="Número" required> 
  <input type="text" name="complemento" placeholder="Complemento">
   <button type="button" id="add-endereco">Adicionar endereço</button> 
  </div> 
  </div>
</form>

<!-- Produtos -->
<div class="checkout-section">
  <h4><i class="fa fa-box"></i> Seus Produtos</h4>
  <ul class="checkout-products">
    <?php foreach($carrinho as $id => $item): ?>
      <li>
        <?= htmlspecialchars($item['nome']) ?> 
        (<?= $item['quantidade'] ?> un) - 
        R$ <?= number_format($item['preco'] * $item['quantidade'],2,",",".") ?>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<!-- Frete -->
<div class="checkout-section checkout-options">
  <h4><i class="fa fa-truck"></i> Opção de Envio</h4>
  <label>
    <input type="radio" name="frete" value="0" checked>
    <i class="fa fa-shipping-fast"></i> Transportadora (Grátis) - 20 dias
  </label>
  <label>
    <input type="radio" name="frete" value="18">
    <i class="fa fa-box-open"></i> Correio - R$ 18,00 (15 dias)
  </label>
</div>

<!-- Pagamento -->
<div class="checkout-section checkout-summary">
  <h4><i class="fa fa-credit-card"></i> Método de Pagamento</h4>
<br>
  <label>
    <input type="radio" name="pagamento" value="pix" checked>
    <i class="fa fa-qrcode" style="color: #9ba885;"></i> PIX
  </label><br>
  <label>
    <input type="radio" name="pagamento" value="boleto">
    <i class="fa fa-barcode" style="color: #9ba885;"></i> Boleto Bancário
  </label><br>
  <label>
    <input type="radio" name="pagamento" value="cartao">
    <i class="fa fa-credit-card"  style="background: #9ba885;"></i> Cartão de Crédito
  </label><br><p>

  <!-- Se selecionar cartão -->
   <p>
  <div id="cartao-opcoes" style="display:none;">
    <h4> Selecione um cartão</h4>
    <?php foreach($cartoes as $cartao): ?>
      <label>
        <input type="radio" name="cartao_id" value="<?= $cartao['id_cartao'] ?>">
        <?= $cartao['bandeira'] ?> ••••<?= $cartao['ultimos_digitos'] ?> (<?= $cartao['titular'] ?>)
      </label><br>
    <?php endforeach; ?>
  </div>
</div>

<!-- Resumo -->
<div class="checkout-section resumo-pagamento">
  <h4><i class="fa fa-file-invoice-dollar"></i> Detalhes do Pagamento</h4>
  <p>Total dos Produtos: 
    <span id="checkout-produtos" data-total="<?= $total ?>">
      R$ <?= number_format($total,2,",",".") ?>
    </span>
  </p>
  <p>Total do Frete: R$ <span id="checkout-frete">0,00</span></p>
  <p><strong>Pagamento Total: R$ <span id="checkout-total">
    <?= number_format($total,2,",",".") ?>
  </span></strong></p>
</div>
  </div>

  <!-- Botão Concluir -->
  <div class="checkout-footer">
    <button id="concluir-compra" class="checkout-btn">
      <i class="fa fa-check-circle"></i> Concluir Pedido
    </button>
  </div>
</div>
</div>
</div>
<!-- Modal de Perfil -->
<div id="profile-modal" class="profile-modal">
  <span class="close-profile">&times;</span>

  
 <div class="profile-banner">
    <img src="<?= $foto_perfil ?>" alt="Avatar do Cliente" class="profile-avatar" id="modal-profile-picture">
</div>

  <div class="profile-info">
    <p class="profile-name"><?= htmlspecialchars($usuario['nome']) ?></p>
    <a href="perfil.php" class="profile-link">Meu Perfil ></a>
  </div>

  <div class="profile-options">
  <a href="painel_cliente.php" class="profile-card"><i class="fas fa-home"></i> Início</a>
  <a href="ajuda.php" class="profile-card"><i class="fas fa-question-circle"></i> Ajuda</a>
  <a href="historico_compras.php" class="profile-card"><i class="fas fa-shopping-bag"></i> Minhas Compras</a>
  <a href="cartoes.php" class="profile-card"><i class="fas fa-credit-card"></i> Cartões Cadastrados</a>
  <a href="enderecos.php" class="profile-card"><i class="fas fa-map-marker" aria-hidden="true"></i> Endereços Cadastrados</a>
</div>
<a href="#" class="profile-card logout" onclick="confirmarSaida(event)">
    <i class="fas fa-sign-out-alt"></i> Sair
</a>
</div>


<!-- Modal de Contato -->
<div id="contact-modal" class="contact-modal">
  <div class="contact-modal-content">
    <span class="close-contact">&times;</span>
    <h2>Fale conosco!</h2>
    <p>Preencha o formulário abaixo para que possamos atender à sua solicitação.</p>

    <form action="contato.php" method="POST" class="row g-3">
      <div class="form-row">
        <input type="text" name="nome" placeholder="Nome" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="tel" name="telefone" placeholder="Telefone" required>
      </div>
      <textarea name="mensagem" placeholder="Mensagem" rows="6"></textarea>
      <p>
        <button type="submit" class="contact-btn">Enviar Mensagem</button>
      </p>
    </form>
  </div>
</div>
<div class="container">
  <h2 class="page-title">Histórico de Compras</h2>
    

  <!-- FILTROS -->
  <div class="filters" aria-label="Filtros de pedidos">
    <div class="filter-group" id="filter-pagamento">
      <strong style="margin-right:8px;">Pagamento:</strong>
      <button class="filter-btn" data-filter-type="status" data-filter-value="pendente">Pendente</button>
      <button class="filter-btn" data-filter-type="status" data-filter-value="concluido">Concluído</button>
    </div>

    <button class="clear-filters" id="clearFilters">Limpar filtros</button>
  </div><!-- LISTAGEM DE PEDIDOS -->
<?php if ($result_pedidos->num_rows > 0): ?>
  <?php while ($pedido = $result_pedidos->fetch_assoc()): ?>
    <div class="pedido"
         data-pagamento="<?= htmlspecialchars($pedido['pagamento']) ?>"
         data-status="<?= htmlspecialchars($pedido['status']) ?>">

      <div class="itens">
        <?php
        // Consulta modificada para incluir buquês personalizados
        $sql_itens = "
            SELECT 
                pi.quantidade, 
                pi.preco_unitario, 
                pi.tipo,
                pi.detalhes,
                COALESCE(pr.nome, 'Buquê Personalizado') as nome,
                CASE 
                    WHEN pi.tipo = 'buque' THEN 'bouquet.jpg'
                    ELSE pr.imagem 
                END as imagem
            FROM pedido_itens pi
            LEFT JOIN produtos pr ON pi.produto_id = pr.id
            WHERE pi.pedido_id = ?
        ";
        $stmt_itens = $conn->prepare($sql_itens);
        $stmt_itens->bind_param("i", $pedido['id']);
        $stmt_itens->execute();
        $result_itens = $stmt_itens->get_result();

        while ($item = $result_itens->fetch_assoc()):
          // Decodificar detalhes para buquês personalizados
          $detalhes = [];
          $nome_exibicao = $item['nome'];
          
          if ($item['tipo'] === 'buque' && !empty($item['detalhes'])) {
            $detalhes = json_decode($item['detalhes'], true);
            // Construir nome descritivo para buquês personalizados
            if (!empty($detalhes['flores'])) {
              $nomes_flores = [];
              foreach($detalhes['flores'] as $flor) {
                if($flor['quantidade'] > 0) {
                  $cor_texto = !empty($flor['cor']) ? " ({$flor['cor']})" : "";
                  $nomes_flores[] = $flor['quantidade'] . "x " . $flor['nome'] . $cor_texto;
                }
              }
              $nome_exibicao = 'Buquê Personalizado';
              if(!empty($nomes_flores)) {
                $nome_exibicao .= ' com ' . implode(', ', $nomes_flores);
              }
              if(!empty($detalhes['embalagens'])) {
                $nome_exibicao .= ' + ' . implode(', ', $detalhes['embalagens']);
              }
              if(!empty($detalhes['laco'])) {
                $nome_exibicao .= ' + ' . $detalhes['laco'];
              }
            }
          }
        ?>
          <div class="item">
            <img src="../images/<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($nome_exibicao) ?>">
            <div class="item-info">
              <p><strong><?= htmlspecialchars($nome_exibicao) ?></strong></p>
              <p>Quantidade: <?= $item['quantidade'] ?></p>
              <p>Preço: R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></p>
              <?php if ($item['tipo'] === 'buque' && !empty($detalhes['obs'])): ?>
                <p class="observacao"><em>Observações: <?= htmlspecialchars($detalhes['obs']) ?></em></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <!-- INFORMAÇÕES DO PEDIDO (EM BAIXO) -->
      <div class="pedido-info">
        <div class="info-line">
          <span class="info-label">Data:</span>
          <span class="info-value"><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></span>
        </div>
        <div class="info-line">
          <span class="info-label">Pagamento:</span>
          <span class="info-value"><?= ucfirst($pedido['pagamento']) ?></span>
        </div>
        <div class="info-line">
          <span class="info-label">Status:</span>
          <span class="info-value status <?= $pedido['status'] ?>"><?= ucfirst($pedido['status']) ?></span>
        </div>
        <div class="info-line total">
          <span class="info-label">Total:</span>
          <span class="info-value">R$ <?= number_format($pedido['total'], 2, ',', '.') ?></span>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
<?php else: ?>
  <p style="text-align:center; color:#777;">Você ainda não realizou nenhuma compra.</p>
<?php endif; ?>
</div>

<!-- SCRIPT DE FILTRO -->
<script>
(function(){
  const filterButtons = document.querySelectorAll('.filter-btn');
  const clearBtn = document.getElementById('clearFilters');
  const pedidos = document.querySelectorAll('.pedido');

  const activeFilters = { pagamento: null, status: null };

  filterButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const type = btn.dataset.filterType;
      const value = btn.dataset.filterValue;
      const isActive = btn.classList.contains('active');

      document.querySelectorAll(`.filter-btn[data-filter-type="${type}"]`).forEach(b => b.classList.remove('active'));

      if (!isActive) {
        btn.classList.add('active');
        activeFilters[type] = value;
      } else {
        activeFilters[type] = null;
      }

      applyFilters();
    });
  });

  clearBtn.addEventListener('click', () => {
    filterButtons.forEach(b => b.classList.remove('active'));
    activeFilters.pagamento = null;
    activeFilters.status = null;
    applyFilters();
  });

  function applyFilters() {
    pedidos.forEach(p => {
      const pPagamento = (p.dataset.pagamento || '').toLowerCase();
      const pStatus   = (p.dataset.status || '').toLowerCase();

      let show = true;
      if (activeFilters.pagamento)
        show = show && (pPagamento === activeFilters.pagamento.toLowerCase());
      if (activeFilters.status)
        show = show && (pStatus === activeFilters.status.toLowerCase());

      p.style.display = show ? '' : 'none';
    });
  }
})();
</script>

<script>
// Modal de Perfil
const profileModal = document.getElementById("profile-modal");
const openProfileBtn = document.getElementById("open-profile");
const closeProfileBtn = document.querySelector(".close-profile");

// abrir
openProfileBtn.addEventListener("click", (e) => {
  e.preventDefault();
  profileModal.classList.add("active");
});

// fechar no X
closeProfileBtn.addEventListener("click", () => {
  profileModal.classList.remove("active");
});

// fechar clicando fora
window.addEventListener("click", (e) => {
  if (e.target === profileModal) {
    profileModal.classList.remove("active");
  }
});
  const contactModal = document.getElementById("contact-modal");
  const openContactBtn = document.getElementById("open-contact");
  const closeContactBtn = document.querySelector(".close-contact");

  // abrir modal
  openContactBtn.addEventListener("click", (e) => {
    e.preventDefault();
    contactModal.classList.add("active");
  });

  // fechar no X
  closeContactBtn.addEventListener("click", () => {
    contactModal.classList.remove("active");
  });

  // fechar clicando fora
  window.addEventListener("click", (e) => {
    if (e.target === contactModal) {
      contactModal.classList.remove("active");
    }
  });
</script>

<script>

// Atualizar ao aplicar filtros

const filtroCategoria = document.getElementById('filtro-categoria');
const filtroPreco = document.getElementById('filtro-preco');
const searchInput = document.getElementById('search-input');
function aplicarFiltros(){
  const categoria = filtroCategoria.value;
  const preco = filtroPreco.value;
  const search = searchInput.value.toLowerCase();

  document.querySelectorAll('.produtos-section').forEach(section => {
    let showSection = categoria === 'all' || section.dataset.categoria === categoria;
    section.style.display = showSection ? 'block' : 'none';

    section.querySelectorAll('.produto-card').forEach(card => {
      const valor = parseFloat(card.dataset.preco);
      const nome = card.querySelector('h3').textContent.toLowerCase();
      let precoMatch = true;
      if(preco !== 'all'){
        const [min,max] = preco.split('-').map(Number);
        precoMatch = valor >= min && valor <= max;
      }
      let searchMatch = nome.includes(search);
      card.style.display = (precoMatch && searchMatch) ? 'block' : 'none';
    });

    // Atualiza a mensagem de "sem produtos"
    atualizarMensagem(section);
  });
}
[filtroCategoria, filtroPreco].forEach(el => el.addEventListener('change', aplicarFiltros));
searchInput.addEventListener('input', aplicarFiltros);

function confirmarSaida(event) {
  event.preventDefault();
  if(confirm("Deseja realmente sair da conta?")){
    window.location.href = "../sair.php";
  }
}
function atualizarMensagem(section){
  const cards = Array.from(section.querySelectorAll('.produto-card'));
  const anyVisible = cards.some(card => card.style.display !== 'none');
  const msg = section.querySelector('.no-products');
  if(msg) {
    msg.style.display = anyVisible ? 'none' : 'flex';
  }
}

</script>
<script src="../js/cliente.js"></script>
</body>
</html>

