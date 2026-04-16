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
    <title>Ajuda & Contato | Gardenia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/ajuda.css"> 
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

    <!-- Conteúdo Principal -->
    <div class="container">
        <h1 class="page-title">Central de Ajuda</h1>
        
        <div class="message success" id="success-message">
            Mensagem enviada com sucesso! Entraremos em contato em breve.
        </div>
        
        <div class="message error" id="error-message">
            Ocorreu um erro ao enviar sua mensagem. Tente novamente.
        </div>

        <div class="help-container">
            <!-- Sidebar -->
            <div class="help-sidebar">
                <ul class="help-menu">
                    <li><a href="#" class="active" data-section="faq"><i class="fas fa-question-circle"></i> Perguntas Frequentes</a></li>
                    <li><a href="#" data-section="how-to-buy"><i class="fas fa-shopping-cart"></i> Como Comprar</a></li>
                    <li><a href="#" data-section="about"><i class="fas fa-store"></i> Sobre a Loja</a></li>
                    <li><a href="#" data-section="contact"><i class="fas fa-envelope"></i> Contato</a></li>
                    <li><a href="#" data-section="shipping"><i class="fas fa-truck"></i> Entrega & Frete</a></li>
                    <li><a href="#" data-section="payments"><i class="fas fa-credit-card"></i> Pagamentos</a></li>
                </ul>
            </div>
            
            <!-- Conteúdo -->
            <div class="help-content">
                <!-- FAQ -->
                <div class="help-section active" id="faq-section">
                    <div class="section-header">
                        <i class="fas fa-question-circle"></i>
                        <h2>Perguntas Frequentes</h2>
                    </div>
                    
                    <div class="faq-list">
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Como faço para rastrear meu pedido?</span>
                                <i class="fas fa-chevron-down faq-toggle"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Após a confirmação do pagamento, você receberá um código de rastreamento por e-mail. Você pode usar esse código em nosso site na seção "Meus Pedidos" ou no site dos Correios.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Qual o prazo de entrega?</span>
                                <i class="fas fa-chevron-down faq-toggle"></i>
                            </div>
                            <div class="faq-answer">
                                <p>O prazo de entrega varia de acordo com sua localização. Em média, são de 2 a 5 dias úteis para a Grande São Paulo e 5 a 10 dias úteis para outras regiões do Brasil.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Posso alterar ou cancelar meu pedido?</span>
                                <i class="fas fa-chevron-down faq-toggle"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Pedidos podem ser alterados ou cancelados até 1 hora após a confirmação do pagamento. Após esse período, o pedido já estará em processo de separação e não poderá ser modificado.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Como funciona a garantia das flores?</span>
                                <i class="fas fa-chevron-down faq-toggle"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Garantimos que suas flores chegarão frescas e em perfeito estado. Caso receba flores murchas ou danificadas, entre em contato conosco em até 24 horas para resolvermos a situação.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Quais são as formas de pagamento aceitas?</span>
                                <i class="fas fa-chevron-down faq-toggle"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Aceitamos cartão de crédito (até 12x), débito, PIX e boleto bancário. Todas as transações são 100% seguras.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Como Comprar -->
                <div class="help-section" id="how-to-buy-section">
                    <div class="section-header">
                        <i class="fas fa-shopping-cart"></i>
                        <h2>Como Comprar</h2>
                    </div>
                    
                    <p>Fazer compras na Gardenia é simples e seguro. Siga estes passos:</p>
                    
                    <div class="steps-container">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-icon"><i class="fas fa-search"></i></div>
                            <h3>Navegue</h3>
                            <p>Explore nossa seleção de flores, buquês e arranjos</p>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-icon"><i class="fas fa-cart-plus"></i></div>
                            <h3>Adicione</h3>
                            <p>Escolha os produtos e adicione ao carrinho</p>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-icon"><i class="fas fa-credit-card"></i></div>
                            <h3>Pague</h3>
                            <p>Finalize o pedido com sua forma de pagamento preferida</p>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-icon"><i class="fas fa-truck"></i></div>
                            <h3>Receba</h3>
                            <p>Acompanhe a entrega e receba suas lindas flores</p>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px; padding: 20px; background: #fff; border-radius: 10px; border-left: 4px solid #9ba885;">
                        <h3 style="color: #9ba885; margin-bottom: 10px;">Dica Importante</h3>
                        <p>Para garantir a frescura das flores, recomendamos fazer pedidos com pelo menos 48 horas de antecedência para datas especiais.</p>
                    </div>
                </div>
                
                <!-- Sobre a Loja -->
                <div class="help-section" id="about-section">
                    <div class="section-header">
                        <i class="fas fa-store"></i>
                        <h2>Sobre a Gardenia</h2>
                    </div>
                    
                    <div class="about-content">
                        <div class="about-text">
                            <p>Há mais de 15 anos, a Gardenia traz beleza e emoção para momentos especiais através de flores cuidadosamente selecionadas.</p>
                            <p>Nossa missão é conectar pessoas através da linguagem universal das flores, oferecendo produtos de qualidade superior e um atendimento excepcional.</p>
                            <p>Trabalhamos diretamente com produtores locais para garantir a frescura e durabilidade de nossas flores, além de apoiar a economia sustentável.</p>
                        </div>
                        <div class="about-image">
                            <img src=../images/loja.jpg alt="Nossa loja">
                        </div>
                    </div>
                    
                    <div class="features">
                        <div class="feature">
                            <i class="fas fa-award"></i>
                            <h3>Qualidade</h3>
                            <p>Flores frescas selecionadas rigorosamente</p>
                        </div>
                        
                        <div class="feature">
                            <i class="fas fa-shipping-fast"></i>
                            <h3>Entrega Rápida</h3>
                            <p>Entregamos em todo o Brasil</p>
                        </div>
                        
                        
                        <div class="feature">
                            <i class="fas fa-leaf"></i>
                            <h3>Sustentabilidade</h3>
                            <p>Práticas eco-friendly em todos os processos</p>
                        </div>
                    </div>
                </div>
                
                <!-- Contato -->
                <div class="help-section" id="contact-section">
                    <div class="section-header">
                        <i class="fas fa-envelope"></i>
                        <h2>Entre em Contato</h2>
                    </div>
                    
                    <div class="contact-info">
                        <div class="contact-card">
                            <i class="fas fa-phone"></i>
                            <h3>Telefone</h3>
                            <p>(11) 3456-7890</p>
                            <p>Segunda a Sexta: 8h às 18h</p>
                        </div>
                        
                        <div class="contact-card">
                            <i class="fas fa-envelope"></i>
                            <h3>E-mail</h3>
                            <p>contato@gardenia.com</p>
                            <p>Respondemos em até 24h</p>
                        </div>
                        
                        <div class="contact-card">
                            <i class="fas fa-map-marker-alt"></i>
                            <h3>Endereço</h3>
                            <p>Rua das Flores, 123</p>
                            <p>São Paulo - SP</p>
                        </div>
                        
                        <div class="contact-card">
                            <i class="fas fa-clock"></i>
                            <h3>Horário</h3>
                            <p>Segunda a Sábado</p>
                            <p>8h às 20h</p>
                        </div>
                    </div>
                    
                    <div class="contact-form">
                        <h3>Envie sua Mensagem</h3>
                        <form id="contact-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="contact-name">Nome Completo</label>
                                    <input type="text" id="contact-name" name="contact-name" required>
                                </div>
                                <div class="form-group">
                                    <label for="contact-email">E-mail</label>
                                    <input type="email" id="contact-email" name="contact-email" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="contact-phone">Telefone</label>
                                    <input type="text" id="contact-phone" name="contact-phone">
                                </div>
                                <div class="form-group">
                                    <label for="contact-subject">Assunto</label>
                                    <select id="contact-subject" name="contact-subject" required>
                                        <option value="">Selecione um assunto</option>
                                        <option value="duvida">Dúvida sobre produtos</option>
                                        <option value="pedido">Acompanhamento de pedido</option>
                                        <option value="problema">Problema com pedido</option>
                                        <option value="sugestao">Sugestão</option>
                                        <option value="outro">Outro</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="contact-message">Mensagem</label>
                                    <textarea id="contact-message" name="contact-message" rows="5" required></textarea>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="submit-btn">Enviar Mensagem</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Entrega & Frete -->
                <div class="help-section" id="shipping-section">
                    <div class="section-header">
                        <i class="fas fa-truck"></i>
                        <h2>Entrega & Frete</h2>
                    </div>
                    
                    <h3>Política de Entrega</h3>
                    <p>Entregamos em todo o Brasil através dos Correios e transportadoras parceiras. O prazo de entrega varia conforme a localidade:</p>
                    
                    <ul style="margin: 15px 0 15px 20px;">
                        <li><strong>Grande São Paulo:</strong> 1-2 dias úteis</li>
                        <li><strong>Demais capitais:</strong> 3-5 dias úteis</li>
                        <li><strong>Interior:</strong> 5-10 dias úteis</li>
                    </ul>
                    
                    <h3>Valor do Frete</h3>
                    <p>O valor do frete é calculado automaticamente no checkout de acordo com o CEP de entrega e o peso do pedido.</p>
                    
                    <h3>Entregas Especiais</h3>
                    <p>Para datas comemorativas como Dia dos Namorados, Dia das Mães e Natal, recomendamos fazer o pedido com antecedência mínima de 5 dias úteis.</p>
                </div>
                
                <!-- Pagamentos -->
                <div class="help-section" id="payments-section">
                    <div class="section-header">
                        <i class="fas fa-credit-card"></i>
                        <h2>Formas de Pagamento</h2>
                    </div>
                    
                    <h3>Opções Disponíveis</h3>
                    
                    <div class="features" style="margin-top: 20px;">
                        <div class="feature">
                            <i class="fas fa-credit-card"></i>
                            <h3>Cartão de Crédito</h3>
                            <p>Até 12x sem juros</p>
                            <p style="font-size: 0.8rem; margin-top: 5px;">Visa, Mastercard, Elo, American Express</p>
                        </div>
                        
                        <div class="feature">
                            <i class="fas fa-barcode"></i>
                            <h3>Boleto</h3>
                            <p>À vista com 5% de desconto</p>
                        </div>
                        
                        <div class="feature">
                            <i class="fas fa-mobile-alt"></i>
                            <h3>PIX</h3>
                            <p>Pagamento instantâneo</p>
                            <p style="font-size: 0.8rem; margin-top: 5px;">5% de desconto</p>
                        </div>

                    </div>
                    
                    <div style="margin-top: 25px; padding: 20px; background: #fff; border-radius: 10px;">
                        <h3 style="color: #9ba885; margin-bottom: 10px;">Segurança</h3>
                        <p>Todas as transações são processadas em ambiente seguro e criptografado. Suas informações financeiras são protegidas e não são armazenadas em nossos sistemas.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Elementos DOM
        const helpMenuLinks = document.querySelectorAll('.help-menu a');
        const helpSections = document.querySelectorAll('.help-section');
        const faqItems = document.querySelectorAll('.faq-item');
        const contactForm = document.getElementById('contact-form');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');

        // Navegação entre seções
        helpMenuLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remover classe active de todos os links
                helpMenuLinks.forEach(l => l.classList.remove('active'));
                
                // Adicionar classe active ao link clicado
                this.classList.add('active');
                
                // Obter a seção alvo
                const targetSection = this.getAttribute('data-section');
                
                // Ocultar todas as seções
                helpSections.forEach(section => {
                    section.classList.remove('active');
                });
                
                // Mostrar a seção alvo
                document.getElementById(`${targetSection}-section`).classList.add('active');
            });
        });

        // FAQ - Abrir/fechar perguntas
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            
            question.addEventListener('click', () => {
                // Fechar todas as outras perguntas
                faqItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('active');
                    }
                });
                
                // Alternar a pergunta clicada
                item.classList.toggle('active');
            });
        });

        // Formatar telefone
        document.getElementById('contact-phone').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
            } else if (value.length > 0) {
                value = value.replace(/^(\d{0,2})/, '($1');
            }
            
            this.value = value;
        });

        // Enviar formulário de contato
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Obter dados do formulário
            const name = document.getElementById('contact-name').value;
            const email = document.getElementById('contact-email').value;
            const subject = document.getElementById('contact-subject').value;
            const message = document.getElementById('contact-message').value;
            
            // Validar dados
            if (!name || !email || !subject || !message) {
                showMessage('Por favor, preencha todos os campos obrigatórios.', 'error');
                return;
            }
            
            // Simular envio (em um sistema real, seria uma chamada AJAX)
            setTimeout(() => {
                contactForm.reset();
                showMessage('Mensagem enviada com sucesso! Entraremos em contato em breve.', 'success');
            }, 1000);
        });

        // Mostrar mensagem
        function showMessage(text, type) {
            const message = type === 'success' ? successMessage : errorMessage;
            message.textContent = text;
            message.style.display = 'block';
            
            // Rolar para a mensagem
            message.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            setTimeout(() => {
                message.style.display = 'none';
            }, 5000);
        }

        // Inicializar página
        document.addEventListener('DOMContentLoaded', function() {
            // Abrir primeira pergunta do FAQ por padrão
            if (faqItems.length > 0) {
                faqItems[0].classList.add('active');
            }
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