<?php
session_start();
include_once("../conexao.php");

if(!isset($_SESSION['usuario_id'])){
    header("Location: ../login.php");
    exit;
}

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


$sql_produtos = "SELECT * FROM produtos ORDER BY 
    FIELD(categoria, 'buque','flor','kits','embalagem','laço')";
$result_produtos = mysqli_query($conn, $sql_produtos);
$produtos = mysqli_fetch_all($result_produtos, MYSQLI_ASSOC);

$carrinho = $_SESSION['cart'] ?? [];

$categorias = [
    'buque' => 'Buquês',
    'flor' => 'Flores Unitárias',
    'kits' => 'Kits',
    'embalagem' => 'Embalagens',
    'laço' => 'Laços'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartões Cadastrados | Gardenia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/cartoes.css"> 
    <style>
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
        <a href="painel_cliente.php">Início</a>
        <a href="produtos.php">Produtos</a>
        <a href="#" class="cart"><i class="fa fa-shopping-cart"></i> 
            <span id="cart-count">
                <?php 
                // Verifica se $carrinho existe e é um array antes de usar array_column
                if (isset($carrinho) && is_array($carrinho) && !empty($carrinho)) {
                    echo array_sum(array_column($carrinho, 'quantidade'));
                } else {
                    echo "0";
                }
                ?>
            </span>
        </a>
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
  </div> 
  </div>
</form>

<!-- Container onde os endereços vão aparecer -->
<div id="enderecos-container"></div>

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

 
    <!-- Conteúdo Principal -->
    <div class="container">
        <h1 class="page-title">Meus Cartões</h1>
        
        <div class="message success" id="success-message">
            Cartão adicionado com sucesso!
        </div>
        
        <div class="message error" id="error-message">
            Ocorreu um erro ao processar sua solicitação.
        </div>

        <button class="add-card-btn" id="add-card-btn">
            <i class="fas fa-plus"></i> Adicionar Novo Cartão
        </button>

        <!-- Formulário Adicionar Cartão -->
        <div class="card-form-container" id="card-form-container">
            <form class="card-form" id="card-form" action="adicionar_cartao.php" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="card-number">Número do Cartão</label>
                        <input type="text" id="card-number" name="card-number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="card-holder">Nome no Cartão</label>
                        <input type="text" id="card-holder" name="card-holder" placeholder="JOÃO DA SILVA" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="card-expiry-month">Mês de Validade</label>
                        <select id="card-expiry-month" name="card-expiry-month" required>
                            <option value="">Mês</option>
                            <option value="01">01</option>
                            <option value="02">02</option>
                            <option value="03">03</option>
                            <option value="04">04</option>
                            <option value="05">05</option>
                            <option value="06">06</option>
                            <option value="07">07</option>
                            <option value="08">08</option>
                            <option value="09">09</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="card-expiry-year">Ano de Validade</label>
                        <select id="card-expiry-year" name="card-expiry-year" required>
                            <option value="">Ano</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                            <option value="2028">2028</option>
                            <option value="2029">2029</option>
                            <option value="2030">2030</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="card-cvv">CVV</label>
                        <input type="text" id="card-cvv" name="card-cvv" placeholder="123" maxlength="3" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="card-brand">Bandeira</label>
                        <select id="card-brand" name="card-brand" required>
                            <option value="">Selecione a bandeira</option>
                            <option value="Visa">Visa</option>
                            <option value="Mastercard">Mastercard</option>
                            <option value="American Express">American Express</option>
                            <option value="Elo">Elo</option>
                            <option value="Hipercard">Hipercard</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="default-card" name="default-card">
                            Definir como cartão principal
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="cancel-btn" id="cancel-btn">Cancelar</button>
                    <button type="submit" class="submit-btn">Salvar Cartão</button>
                </div>
            </form>
        </div>

        <!-- Lista de Cartões -->
        <div class="cards-section">
            <h2>Cartões Cadastrados</h2>
            
            <div class="cards-grid" id="cards-grid">
                <?php if(empty($cartoes)): ?>
                    <div id="no-cards-message" style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-credit-card" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <p>Você ainda não possui cartões cadastrados.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($cartoes as $cartao): ?>
                        <div class="card-item" data-id="<?= $cartao['id_cartao'] ?>">
                            <div class="card-header">
                                <span class="card-brand"><?= htmlspecialchars($cartao['bandeira']) ?></span>
                                <?php if($cartao['principal']): ?>
                                    <span class="card-default">Principal</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-number">•••• •••• •••• <?= htmlspecialchars($cartao['ultimos_digitos']) ?></div>
                            <div class="card-details">
                                <span><?= htmlspecialchars($cartao['titular']) ?></span>
                                <span><?= $cartao['validade_mes'] ?>/<?= substr($cartao['validade_ano'], 2) ?></span>
                            </div>
                            <div class="card-actions">
                                <button class="card-btn set-default" data-id="<?= $cartao['id_cartao'] ?>">
                                    <?= $cartao['principal'] ? 'Principal' : 'Tornar Principal' ?>
                                </button>
                                <button class="card-btn delete" data-id="<?= $cartao['id_cartao'] ?>">Excluir</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Elementos DOM
        const addCardBtn = document.getElementById('add-card-btn');
        const cardFormContainer = document.getElementById('card-form-container');
        const cardForm = document.getElementById('card-form');
        const cancelBtn = document.getElementById('cancel-btn');
        const cardsGrid = document.getElementById('cards-grid');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');

        // Formatar número do cartão
        function formatCardNumber(input) {
            let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            input.value = formattedValue;
        }

        // Mostrar/ocultar formulário
        addCardBtn.addEventListener('click', () => {
            cardFormContainer.classList.toggle('active');
            if (cardFormContainer.classList.contains('active')) {
                addCardBtn.innerHTML = '<i class="fas fa-times"></i> Cancelar';
            } else {
                addCardBtn.innerHTML = '<i class="fas fa-plus"></i> Adicionar Novo Cartão';
            }
        });

        cancelBtn.addEventListener('click', () => {
            cardFormContainer.classList.remove('active');
            addCardBtn.innerHTML = '<i class="fas fa-plus"></i> Adicionar Novo Cartão';
            cardForm.reset();
        });

        // Formatar número do cartão enquanto digita
        document.getElementById('card-number').addEventListener('input', function() {
            formatCardNumber(this);
        });

        // Definir cartão como principal
        function setDefaultCard(cardId) {
            fetch('definir_cartao_principal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cartao_id=${cardId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Cartão definido como principal!', 'success');
                    // Recarregar a página para atualizar os dados
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage('Erro ao definir cartão principal.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Erro ao processar solicitação.', 'error');
            });
        }

        // Excluir cartão
        function deleteCard(cardId) {
            if (confirm('Tem certeza que deseja excluir este cartão?')) {
                fetch('excluir_cartao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cartao_id=${cardId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Cartão excluído com sucesso!', 'success');
                        // Remover o cartão da interface
                        document.querySelector(`.card-item[data-id="${cardId}"]`).remove();
                        
                        // Verificar se não há mais cartões
                        if (document.querySelectorAll('.card-item').length === 0) {
                            cardsGrid.innerHTML = `
                                <div id="no-cards-message" style="text-align: center; padding: 40px; color: #666;">
                                    <i class="fas fa-credit-card" style="font-size: 48px; margin-bottom: 15px;"></i>
                                    <p>Você ainda não possui cartões cadastrados.</p>
                                </div>
                            `;
                        }
                    } else {
                        showMessage('Erro ao excluir cartão.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Erro ao processar solicitação.', 'error');
                });
            }
        }

        // Mostrar mensagem
        function showMessage(text, type) {
            const message = type === 'success' ? successMessage : errorMessage;
            message.textContent = text;
            message.style.display = 'block';
            
            setTimeout(() => {
                message.style.display = 'none';
            }, 5000);
        }

        // Adicionar eventos aos botões após o carregamento da página
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar eventos aos botões de definir como principal
            document.querySelectorAll('.set-default').forEach(btn => {
                btn.addEventListener('click', function() {
                    const cardId = this.getAttribute('data-id');
                    setDefaultCard(cardId);
                });
            });
            
            // Adicionar eventos aos botões de excluir
            document.querySelectorAll('.delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    const cardId = this.getAttribute('data-id');
                    deleteCard(cardId);
                });
            });
        });

        // Processar formulário via AJAX
        cardForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Obter dados do formulário
            const formData = new FormData(this);
            
            // Enviar via AJAX
            fetch('adicionar_cartao.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Cartão adicionado com sucesso!', 'success');
                    cardForm.reset();
                    cardFormContainer.classList.remove('active');
                    addCardBtn.innerHTML = '<i class="fas fa-plus"></i> Adicionar Novo Cartão';
                    
                    // Recarregar a página após um tempo
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(data.message || 'Erro ao adicionar cartão.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Erro ao processar solicitação.', 'error');
            });
        });
    </script>
    
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
  </div>
</section>
<script src="../js/cliente.js"></script>
</body>
</html>