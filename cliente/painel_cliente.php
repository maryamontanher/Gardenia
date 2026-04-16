<?php
session_start();
include_once("../conexao.php");

// Redireciona se não estiver logado
if(!isset($_SESSION['usuario_id'])){
    header("Location: ../login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

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

// Produtos para home
$sql_produtos = "SELECT * FROM produtos WHERE categoria = 'buque' AND estoque > 0 LIMIT 7";
$result_produtos = mysqli_query($conn, $sql_produtos);
$produtos = mysqli_fetch_all($result_produtos, MYSQLI_ASSOC);

// Carrinho da sessão
$carrinho = $_SESSION['cart'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">   
<title>Painel do Cliente - Floricultura</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/cliente.css"> 
<link rel="shortcut icon" href="../images/logo.png">
<style><style>
/* Garantir que a tela de conclusão tenha prioridade */
.completion-step {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: white;
    z-index: 100;
    display: none; /* Inicialmente escondido */
}

.completion-step.active {
    display: block !important;
}

.completion-content {
    padding: 20px;
    text-align: center;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.completion-header h3 {
    color: #28a745;
    margin-bottom: 10px;
    font-size: 24px;
}

.completion-message {
    text-align: left;
    margin: 20px 0;
    padding: 0 20px;
}

.completion-message ul {
    list-style: none;
    padding: 0;
    margin: 15px 0;
}

.completion-message li {
    padding: 8px 0;
    color: #555;
    font-size: 14px;
}

.completion-message li i {
    margin-right: 10px;
    color: #9ba885;
    width: 20px;
}

.completion-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 20px;
}

.completion-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
    min-width: 180px;
}

.completion-btn.primary {
    background-color: #9ba885;
    color: white;
}

.completion-btn.secondary {
    background-color: #6c757d;
    color: white;
}

.completion-btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Garantir que o modal do carrinho tenha z-index alto */
.cart-modal {
    z-index: 10000;
}

.cart-content {
    position: relative;
    overflow: hidden;
}
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
      <a href="produtos.php">Produtos</a>
      <a href="#" class="cart"><i class="fa fa-shopping-cart"></i> <span id="cart-count"><?= array_sum(array_column($carrinho, 'quantidade')) ?></span></a>
      <a href="#" onclick="confirmarSaida(event)">Sair</a>
    </div>
</nav><!-- Modal Carrinho -->
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
        <h3><i class="fa fa-location-dot"></i> Selecione um endereço</h3>
        <form id="form-endereco">
          <div id="enderecos-container">
            <?php foreach($enderecos as $end): ?>
              <label>
                <input type="radio" name="endereco_id" value="<?= $end['id_endereco'] ?>">
                <i class="fa fa-location-dot"></i>
                <?= $end['rua'] ?>, <?= $end['numero'] ?> - <?= $end['cidade'] ?>/<?= $end['estado'] ?>
              </label><br>
            <?php endforeach; ?>
          </div>
          
          <!-- Botão para adicionar novo endereço -->
          <div class="checkout-section address">
            <button type="button" id="btn-novo-endereco">Adicionar Novo endereço</button>

            <!-- Formulário oculto -->
            <div id="novo-endereco-form" style="display:none; margin-top:10px;">
              <form id="form-endereco">  
                <input type="text" name="cep" placeholder="CEP" required> 
                <input type="text" name="estado" placeholder="Estado" required> 
                <input type="text" name="cidade" placeholder="Cidade" required> 
                <input type="text" name="bairro" placeholder="Bairro" required> 
                <input type="text" name="rua" placeholder="Rua" required> 
                <input type="text" name="numero" placeholder="Número" required> 
                <input type="text" name="complemento" placeholder="Complemento">
                <button type="button" id="add-endereco">Adicionar endereço</button> 
              </form>
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
            <i class="fa fa-shipping-fast"></i> Transportadora (Grátis) - 10 dias
          </label>
          <label>
            <input type="radio" name="frete" value="18">
            <i class="fa fa-box-open"></i> Correio - R$ 18,00 (5 dias)
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
            <i class="fa fa-credit-card" style="color: #9ba885;"></i> Cartão de Crédito
          </label><br>
          <p>

          <!-- Se selecionar cartão -->
          <div id="cartao-opcoes" style="display:none;">
            <h4>Selecione um cartão</h4>
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

    <!-- Tela de Conclusão -->
    <div id="completion-step" class="completion-step" style="display: none;">
      <div class="completion-content">
        <div class="completion-header">
          <i class="fa fa-check-circle" style="color: #28a745; font-size: 48px; margin-bottom: 20px;"></i>
          <h3>Compra Finalizada com Sucesso!</h3>
        </div>
        
        <div class="completion-body">
          <div class="completion-message">
            <p><strong>Aguarde a confirmação do seu pedido por email</strong></p>
            <p>Enviamos todos os detalhes da sua compra para o seu email cadastrado.</p>
            <p>Você receberá em breve:</p>
            <ul>
              <li><i class="fa fa-envelope"></i> Confirmação do pedido</li>
              <li><i class="fa fa-qrcode"></i> Instruções de pagamento (se aplicável)</li>
              <li><i class="fa fa-truck"></i> Informações sobre o envio</li>
            </ul>
          </div>
        </div>

        <div class="completion-footer">
          <div class="completion-buttons">
            <button id="voltar-compras" class="completion-btn secondary">
              <i class="fa fa-shopping-cart"></i> Continuar Comprando
            </button>
            <button id="ver-minhas-compras" class="completion-btn primary">
              <i class="fa fa-list-alt"></i> Ver Minhas Compras
            </button>
          </div>
        </div>
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

    <form action="../contato.php" method="POST" class="row g-3">
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


<!-- Banner -->
<div class="image-banner">
    <img src="../images/banner.jpg" alt="Promoção de flores">
</div>
    <section class="produtos-section">
  <div class="produtos-header">
    <h2>Presenteie quem você ama</h2>
    <a href="produtos.php" class="ver-todos">Ver todos os produtos</a>
  </div>

  <div class="produtos-grid">
    <?php foreach($produtos as $produto): ?>
      <div class="produto-card" data-id="<?= $produto['id'] ?>">
        <img src="../images/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
        <h3><?= htmlspecialchars($produto['nome']) ?></h3>
        <p>R$ <?= number_format($produto['preco'],2,",",".") ?></p>
        <div class="produto-buttons">
          <button class="add-cart" data-id="<?= $produto['id'] ?>">Adicionar</button>
          <button class="saiba-mais" data-modal="modal<?= $produto['id'] ?>">Saiba Mais</button>
        </div>
      </div>

      <!-- Modal de produto -->
      <div id="modal<?= $produto['id'] ?>" class="modal">
        <div class="modal-content modal-flex">
          <span class="close">&times;</span>
          <div class="modal-left">
            <img src="../images/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
          </div>
          <div class="modal-right">
            <h3><?= htmlspecialchars($produto['nome']) ?></h3>
            <p><?= htmlspecialchars($produto['descricao']) ?></p>
            <p><strong>Preço unitário:</strong> R$ <?= number_format($produto['preco'],2,",",".") ?></p>

            <!-- Seleção de cor -->
            <?php 
              $cores = explode(',', $produto['cores_disponiveis'] ?? '');
              if(!empty($cores[0])): 
            ?>
              <label for="cor<?= $produto['id'] ?>">Escolha a cor:</label>
              <select id="cor<?= $produto['id'] ?>" class="select-cor">
                <?php foreach($cores as $cor): ?>
                  <option value="<?= trim($cor) ?>"><?= trim($cor) ?></option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>

            <button class="add-cart" data-id="<?= $produto['id'] ?>">Adicionar ao Carrinho</button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

</section>


<!-- Banner de informações -->
<section class="info-banner">
  <div class="container">
    <div class="info-card">
      <i class="fa-solid fa-shield-halved"></i>
      <h3>Site 100% Seguro</h3>
      <p>Suas compras protegidas com total segurança.</p>
    </div>
    <div class="info-card">
      <i class="fa-solid fa-truck"></i>
      <h3>Frete Grátis</h3>
      <p>Aproveite entregas sem custo adicional.</p>
    </div>
<div class="info-card">
  <i class="fa-solid fa-earth-americas fa-2x"></i>
  <h3>Entregamos para todo o Brasil</h3>
  <p>De Norte a Sul, suas flores chegam com carinho.</p>
    </div>
  </div>
</section>
<?php
// buscar os produtos por categoria
$sql_flores = "SELECT * FROM produtos WHERE categoria = 'flor' AND estoque > 0";
$sql_embalagem = "SELECT * FROM produtos WHERE categoria = 'embalagem' AND estoque > 0";
$sql_lacos = "SELECT * FROM produtos WHERE categoria = 'laço' AND estoque > 0";

$flores = mysqli_query($conn, $sql_flores);
$embalagem = mysqli_query($conn, $sql_embalagem);
$lacos = mysqli_query($conn, $sql_lacos);
?>


<!-- Personalize seu Buquê -->
<section class="personalize-section">
  <div class="container">
    <h2>Personalize o Seu Buquê</h2>
    <p>Escolha as flores, cores e complementos para criar o buquê perfeito!</p>

    <!-- CARROSSEL DE FLORES -->
    <h3>Flores</h3>
  <div class="carousel-container">
  <button class="carousel-nav prev">&#10094;</button>
  <div class="carousel" id="flowers-carousel">
    <?php while($produto = mysqli_fetch_assoc($flores)): ?>
      <div class="carousel-item" 
           data-cores="<?= htmlspecialchars($produto['cores_disponiveis']) ?>"
           data-preco="<?= htmlspecialchars($produto['preco']) ?>">
        <img src="../images/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
        <h4><?= htmlspecialchars($produto['nome']) ?></h4>
        <p>R$ <?= number_format($produto['preco'],2,",",".") ?>/un</p>
        <div class="counter">
          <button class="decrease">-</button>
          <span class="quantity">0</span>
          <button class="increase">+</button>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
  <button class="carousel-nav next">&#10095;</button>
</div>


    <!-- CARROSSEL DE EMBALAGENS -->
    <h3>Embalagens (máx 2)</h3>
    <div class="carousel-container">
      <button class="carousel-nav prev">&#10094;</button>
      <div class="carousel" id="wrappers-carousel">
        <?php while($produto = mysqli_fetch_assoc($embalagem)): ?>
          <div class="carousel-item wrapper-item" data-preco="<?= htmlspecialchars($produto['preco']) ?>">
            <img src="../images/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
            <h4><?= htmlspecialchars($produto['nome']) ?></h4>
            <p>R$ <?= number_format($produto['preco'],2,",",".") ?></p>
            <input type="checkbox" class="wrapper-checkbox">
          </div>
        <?php endwhile; ?>
      </div>
      <button class="carousel-nav next">&#10095;</button>
    </div>

    <!-- CARROSSEL DE LAÇOS -->
    <h3>Laços (máx 1)</h3>
    <div class="carousel-container">
      <button class="carousel-nav prev">&#10094;</button>
      <div class="carousel" id="bows-carousel">
        <?php while($produto = mysqli_fetch_assoc($lacos)): ?>
          <div class="carousel-item bow-item" data-preco="<?= htmlspecialchars($produto['preco']) ?>">
            <img src="../images/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
            <h4><?= htmlspecialchars($produto['nome']) ?></h4>
            <p>R$ <?= number_format($produto['preco'],2,",",".") ?></p>
            <input type="radio" name="bow" class="bow-radio">
          </div>
        <?php endwhile; ?>
      </div>
      <button class="carousel-nav next">&#10095;</button>
    </div>

    <button id="preview-btn">Ver Prévia do Buquê</button>

    <!-- PREVIEW DO BUQUÊ (INICIALMENTE ESCONDIDO) -->
<div id="preview-bouquet" class="preview-bouquet" style="display:none;">
  <h3>Prévia do Seu Buquê</h3>

  <div class="preview-grid">
    <div class="preview-col">
      <h4>Flores</h4>
      <div id="preview-flowers"></div>
    </div>

    <div class="preview-col">
      <h4>Embalagens</h4>
      <div id="preview-wrappers"></div>
    </div>

    <div class="preview-col">
      <h4>Laço</h4>
      <div id="preview-bow"></div>
    </div>
  </div>

  <div id="preview-obs">
    <label>Observações:</label>
    <textarea id="obs-buque" rows="3" placeholder="Ex: Prefiro flores mais abertas..."></textarea>
  </div>

  <div class="preview-footer">
    <p><strong>Total: R$ <span id="preview-total">0,00</span></strong></p>
    <button id="add-bouquet-cart">Adicionar ao Carrinho</button>
  </div>
</div>

    <script>
   function confirmarSaida(event) {
  event.preventDefault();
  if (confirm("Deseja realmente sair da conta?")) {
    window.location.href = "../sair.php";
  }
}// Modal de Perfil
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

  //botão endereço
  const showFormBtn = document.getElementById('show-endereco-form');
const novoEnderecoForm = document.getElementById('novo-endereco-form');

showFormBtn.addEventListener('click', () => {
  if (novoEnderecoForm.style.display === "none") {
    novoEnderecoForm.style.display = "block";
    showFormBtn.style.display = "none"; // esconde o botão
  }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se o botão de concluir compra existe
    const concluirCompraBtn = document.getElementById('concluir-compra');
    const voltarComprasBtn = document.getElementById('voltar-compras');
    const verMinhasComprasBtn = document.getElementById('ver-minhas-compras');
    
    // Quando clicar em "Concluir Pedido"
    if (concluirCompraBtn) {
        concluirCompraBtn.addEventListener('click', function() {
            console.log('Concluir pedido clicado'); // Para debug
            
            // Esconde o checkout e mostra a tela de conclusão
            const checkoutStep = document.getElementById('checkout-step');
            const completionStep = document.getElementById('completion-step');
            
            if (checkoutStep && completionStep) {
                checkoutStep.style.display = 'none';
                completionStep.style.display = 'block';
                
                // Rolar para o topo da tela de conclusão
                completionStep.scrollTop = 0;
            } else {
                console.error('Elementos checkout ou completion não encontrados');
            }
        });
    } else {
        console.error('Botão concluir-compra não encontrado');
    }

    // Botão "Continuar Comprando"
    if (voltarComprasBtn) {
        voltarComprasBtn.addEventListener('click', function() {
            console.log('Continuar comprando clicado');
            
            // Fecha o modal completamente
            const cartModal = document.getElementById('cart-modal');
            if (cartModal) {
                cartModal.style.display = 'none';
            }
            
            // Limpa o carrinho (opcional)
            // window.location.href = 'painel_cliente.php'; // Recarrega a página
        });
    }

    // Botão "Ver Minhas Compras"
    if (verMinhasComprasBtn) {
        verMinhasComprasBtn.addEventListener('click', function() {
            console.log('Ver minhas compras clicado');
            // Redireciona para a página de pedidos do usuário
            window.location.href = 'historico_compras.php';
        });
    }
    
    // Adicionar também um fallback caso o usuário tente fechar o modal durante a conclusão
    const closeCartBtn = document.getElementById('close-cart');
    if (closeCartBtn) {
        closeCartBtn.addEventListener('click', function() {
            const completionStep = document.getElementById('completion-step');
            const checkoutStep = document.getElementById('checkout-step');
            
            // Se estiver na tela de conclusão, reseta para o carrinho
            if (completionStep && completionStep.style.display === 'block') {
                completionStep.style.display = 'none';
                checkoutStep.style.display = 'none';
                // Mostra o carrinho normal
                document.querySelector('.cart-body').style.display = 'block';
                document.querySelector('.cart-footer').style.display = 'block';
            }
        });
    }
    
    // Também adicione um listener para o botão voltar do checkout
    const backToCartBtn = document.getElementById('back-to-cart');
    if (backToCartBtn) {
        backToCartBtn.addEventListener('click', function() {
            const completionStep = document.getElementById('completion-step');
            // Se estiver na tela de conclusão, não permite voltar
            if (completionStep && completionStep.style.display === 'block') {
                return;
            }
        });
    }
});

// Função auxiliar para debug - pode remover depois
function debugModal() {
    console.log('=== DEBUG MODAL ===');
    console.log('concluir-compra:', document.getElementById('concluir-compra'));
    console.log('checkout-step:', document.getElementById('checkout-step'));
    console.log('completion-step:', document.getElementById('completion-step'));
    console.log('voltar-compras:', document.getElementById('voltar-compras'));
    console.log('ver-minhas-compras:', document.getElementById('ver-minhas-compras'));
}

</script>
  </div>
</section>
<script src="../js/cliente.js"></script>
</body>
</html>
