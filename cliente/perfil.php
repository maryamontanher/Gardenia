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
    <title>Meu Perfil | Gardenia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="../css/perfil.css"> 
   
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
        <h1 class="page-title">Meu Perfil</h1>
        
        <div class="message success" id="success-message">
            Alterações salvas com sucesso!
        </div>
        
        <div class="message error" id="error-message">
            Ocorreu um erro ao processar sua solicitação.
        </div>

        <div class="profile-container">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <!-- Na seção de foto do perfil -->
<div class="profile-picture-section">
    <img src="<?= $foto_perfil ?>" alt="Foto de perfil" class="profile-picture" id="profile-picture">
    <button class="change-photo-btn" id="change-photo-btn">
        <i class="fas fa-camera"></i> Alterar Foto
    </button>
</div>
                
                <ul class="profile-menu">
                    <li><a href="#" class="active" data-section="personal-data">Dados Pessoais</a></li>
                    <li><a href="#" data-section="change-password">Alterar Senha</a></li>
                    <li><a href="#" data-section="preferences">Preferências</a></li>
                </ul>
            </div>
            
            <!-- Conteúdo -->
            <div class="profile-content">
                <!-- Dados Pessoais -->
                <div class="profile-section active" id="personal-data-section">
                    <div class="section-header">
                        <h2>Dados Pessoais</h2>
                        <button class="edit-btn" id="edit-personal-data">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                    
                    <form id="personal-data-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="user-name">Nome Completo</label>
                                <input type="text" id="user-name" name="user-name" value="Marya Eduarda" disabled required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="user-email">E-mail</label>
                                <input type="email" id="user-email" name="user-email" value="marya@gmail.com" disabled required>
                            </div>
                            <div class="form-group">
                                <label for="user-phone">Telefone</label>
                                <input type="text" id="user-phone" name="user-phone" value="(18) 99728-4482" disabled required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="user-cpf">CPF</label>
                                <input type="text" id="user-cpf" name="user-cpf" value="138.209.818-96" disabled required>
                            </div>
                            <div class="form-group">
                                <label for="user-birthdate">Data de Nascimento</label>
                                <input type="date" id="user-birthdate" name="user-birthdate" value="2007-05-03" disabled required>
                            </div>
                        </div>
                        
                        <div class="form-actions" id="personal-data-actions" style="display: none;">
                            <button type="button" class="cancel-btn" id="cancel-personal-data">Cancelar</button>
                            <button type="submit" class="submit-btn">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
                
                <!-- Alterar Senha -->
                <div class="profile-section" id="change-password-section">
                    <div class="section-header">
                        <h2>Alterar Senha</h2>
                    </div>
                    
                    <form id="change-password-form">
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="current-password">Senha Atual</label>
                                <input type="password" id="current-password" name="current-password" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="new-password">Nova Senha</label>
                                <input type="password" id="new-password" name="new-password" required>
                                <div class="password-strength" id="password-strength">
                                    <div class="password-strength-bar"></div>
                                </div>
                                <div class="password-requirements">
                                    A senha deve conter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e símbolos.
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="confirm-password">Confirmar Nova Senha</label>
                                <input type="password" id="confirm-password" name="confirm-password" required>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="submit-btn">Alterar Senha</button>
                        </div>
                    </form>
                </div>
                
                <!-- Preferências -->
                <div class="profile-section" id="preferences-section">
                    <div class="section-header">
                        <h2>Preferências</h2>
                        <button class="edit-btn" id="edit-preferences">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                    
                    <form id="preferences-form">
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="newsletter">
                                    <input type="checkbox" id="newsletter" name="newsletter" disabled>
                                    Desejo receber novidades e promoções por e-mail
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="notifications">
                                    <input type="checkbox" id="notifications" name="notifications" disabled>
                                    Desejo receber notificações sobre meus pedidos
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="preferred-payment">Forma de Pagamento Preferida</label>
                                <select id="preferred-payment" name="preferred-payment" disabled>
                                    <option value="pix">PIX</option>
                                    <option value="cartao" selected>Cartão de Crédito</option>
                                    <option value="boleto">Boleto</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions" id="preferences-actions" style="display: none;">
                            <button type="button" class="cancel-btn" id="cancel-preferences">Cancelar</button>
                            <button type="submit" class="submit-btn">Salvar Preferências</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Alterar Foto -->
    <div class="modal" id="photo-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Alterar Foto de Perfil</h3>
                <button class="close-modal" id="close-photo-modal">&times;</button>
            </div>
            
            <div class="photo-options">
                <div class="photo-option" id="upload-photo-option">
                    <i class="fas fa-upload"></i>
                    <p>Fazer upload de uma foto</p>
                    <input type="file" id="photo-upload" class="file-input" accept="image/*">
                </div>
                
                <div class="photo-option" id="remove-photo-option">
                    <i class="fas fa-trash-alt"></i>
                    <p>Remover foto atual</p>
                </div>
                
                <img id="photo-preview" class="photo-preview" alt="Pré-visualização">
                
                <div class="form-actions">
                    <button type="button" class="cancel-btn" id="cancel-photo">Cancelar</button>
                    <button type="button" class="submit-btn" id="save-photo" disabled>Salvar Foto</button>
                </div>
            </div>
        </div>
    </div>
<script>
    // Elementos DOM
    const profilePicture = document.getElementById('profile-picture');
    const modalProfilePicture = document.getElementById('modal-profile-picture');
    const changePhotoBtn = document.getElementById('change-photo-btn');
    const photoModal = document.getElementById('photo-modal');
    const closePhotoModal = document.getElementById('close-photo-modal');
    const cancelPhoto = document.getElementById('cancel-photo');
    const savePhoto = document.getElementById('save-photo');
    const uploadPhotoOption = document.getElementById('upload-photo-option');
    const removePhotoOption = document.getElementById('remove-photo-option');
    const photoUpload = document.getElementById('photo-upload');
    const photoPreview = document.getElementById('photo-preview');
    
    const profileMenuLinks = document.querySelectorAll('.profile-menu a');
    const profileSections = document.querySelectorAll('.profile-section');
    
    const editPersonalDataBtn = document.getElementById('edit-personal-data');
    const personalDataForm = document.getElementById('personal-data-form');
    const personalDataInputs = personalDataForm.querySelectorAll('input');
    const personalDataActions = document.getElementById('personal-data-actions');
    const cancelPersonalData = document.getElementById('cancel-personal-data');
    
    const changePasswordForm = document.getElementById('change-password-form');
    const newPasswordInput = document.getElementById('new-password');
    const passwordStrength = document.getElementById('password-strength');
    
    const editPreferencesBtn = document.getElementById('edit-preferences');
    const preferencesForm = document.getElementById('preferences-form');
    const preferencesInputs = preferencesForm.querySelectorAll('input, select');
    const preferencesActions = document.getElementById('preferences-actions');
    const cancelPreferences = document.getElementById('cancel-preferences');
    
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');

    // Navegação entre seções
    profileMenuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover classe active de todos os links
            profileMenuLinks.forEach(l => l.classList.remove('active'));
            
            // Adicionar classe active ao link clicado
            this.classList.add('active');
            
            // Obter a seção alvo
            const targetSection = this.getAttribute('data-section');
            
            // Ocultar todas as seções
            profileSections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Mostrar a seção alvo
            document.getElementById(`${targetSection}-section`).classList.add('active');
        });
    });

    // Modal de Foto
    changePhotoBtn.addEventListener('click', () => {
        photoModal.classList.add('active');
    });

    closePhotoModal.addEventListener('click', () => {
        photoModal.classList.remove('active');
        resetPhotoModal();
    });

    cancelPhoto.addEventListener('click', () => {
        photoModal.classList.remove('active');
        resetPhotoModal();
    });

    // Upload de foto
    uploadPhotoOption.addEventListener('click', () => {
        photoUpload.click();
    });

    photoUpload.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
                photoPreview.style.display = 'block';
                savePhoto.disabled = false;
            };
            
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Remover foto
    removePhotoOption.addEventListener('click', () => {
        if (confirm('Tem certeza que deseja remover sua foto de perfil?')) {
            // Mostrar loading
            savePhoto.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removendo...';
            savePhoto.disabled = true;
            
            fetch('upload_foto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'remover_foto=true'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar foto em todos os lugares
                    profilePicture.src = data.foto_url;
                    modalProfilePicture.src = data.foto_url;
                    
                    photoModal.classList.remove('active');
                    resetPhotoModal();
                    
                    showMessage(data.message, 'success');
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Erro ao processar solicitação.', 'error');
            })
            .finally(() => {
                savePhoto.innerHTML = 'Salvar Foto';
                savePhoto.disabled = false;
            });
        }
    });

    // Salvar foto
    savePhoto.addEventListener('click', () => {
        if (!photoUpload.files[0]) {
            showMessage('Selecione uma foto para upload.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('foto', photoUpload.files[0]);
        
        // Mostrar loading
        savePhoto.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        savePhoto.disabled = true;
        
        fetch('upload_foto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar foto em todos os lugares
                profilePicture.src = data.foto_url + '?t=' + new Date().getTime(); // Cache bust
                modalProfilePicture.src = data.foto_url + '?t=' + new Date().getTime();
                
                photoModal.classList.remove('active');
                resetPhotoModal();
                
                showMessage(data.message, 'success');
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Erro ao processar solicitação.', 'error');
        })
        .finally(() => {
            savePhoto.innerHTML = 'Salvar Foto';
            savePhoto.disabled = false;
        });
    });

    function resetPhotoModal() {
        photoPreview.style.display = 'none';
        photoUpload.value = '';
        savePhoto.disabled = true;
    }

    // Editar dados pessoais
    editPersonalDataBtn.addEventListener('click', () => {
        personalDataInputs.forEach(input => {
            input.disabled = false;
        });
        personalDataActions.style.display = 'flex';
        editPersonalDataBtn.style.display = 'none';
    });

    cancelPersonalData.addEventListener('click', () => {
        personalDataInputs.forEach(input => {
            input.disabled = true;
            // Restaurar valores originais do PHP
            switch(input.id) {
                case 'user-name': input.value = '<?= htmlspecialchars($usuario["nome"]) ?>'; break;
                case 'user-email': input.value = '<?= htmlspecialchars($usuario["email"]) ?>'; break;
                case 'user-phone': input.value = '<?= htmlspecialchars($usuario["telefone"]) ?>'; break;
                case 'user-cpf': input.value = '<?= htmlspecialchars($usuario["cpf"]) ?>'; break;
                case 'user-birthdate': input.value = '<?= $usuario["nascimento"] ?>'; break;
            }
        });
        personalDataActions.style.display = 'none';
        editPersonalDataBtn.style.display = 'block';
    });

    personalDataForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar dados
        const name = document.getElementById('user-name').value;
        const email = document.getElementById('user-email').value;
        const phone = document.getElementById('user-phone').value;
        
        if (!name || !email || !phone) {
            showMessage('Por favor, preencha todos os campos obrigatórios.', 'error');
            return;
        }
        
        // Simular salvamento (em um sistema real, seria uma chamada AJAX)
        setTimeout(() => {
            // Desabilitar campos novamente
            personalDataInputs.forEach(input => {
                input.disabled = true;
            });
            personalDataActions.style.display = 'none';
            editPersonalDataBtn.style.display = 'block';
            
            showMessage('Dados pessoais atualizados com sucesso!', 'success');
        }, 1000);
    });

    // Força da senha
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        passwordStrength.className = 'password-strength';
        if (strength > 0) {
            if (strength <= 2) {
                passwordStrength.classList.add('weak');
            } else if (strength === 3) {
                passwordStrength.classList.add('medium');
            } else {
                passwordStrength.classList.add('strong');
            }
        }
    });

    // Alterar senha
    changePasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;
        
        // Validar senha
        if (newPassword.length < 8) {
            showMessage('A nova senha deve ter pelo menos 8 caracteres.', 'error');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            showMessage('As senhas não coincidem.', 'error');
            return;
        }
        
        // Simular alteração de senha (em um sistema real, seria uma chamada AJAX)
        setTimeout(() => {
            changePasswordForm.reset();
            passwordStrength.className = 'password-strength';
            showMessage('Senha alterada com sucesso!', 'success');
        }, 1000);
    });

    // Editar preferências
    editPreferencesBtn.addEventListener('click', () => {
        preferencesInputs.forEach(input => {
            input.disabled = false;
        });
        preferencesActions.style.display = 'flex';
        editPreferencesBtn.style.display = 'none';
    });

    cancelPreferences.addEventListener('click', () => {
        preferencesInputs.forEach(input => {
            input.disabled = true;
            // Restaurar valores originais
            document.getElementById('newsletter').checked = true;
            document.getElementById('notifications').checked = true;
            document.getElementById('preferred-payment').value = 'cartao';
        });
        preferencesActions.style.display = 'none';
        editPreferencesBtn.style.display = 'block';
    });

    preferencesForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Simular salvamento (em um sistema real, seria uma chamada AJAX)
        setTimeout(() => {
            // Desabilitar campos novamente
            preferencesInputs.forEach(input => {
                input.disabled = true;
            });
            preferencesActions.style.display = 'none';
            editPreferencesBtn.style.display = 'block';
            
            showMessage('Preferências atualizadas com sucesso!', 'success');
        }, 1000);
    });

    // Mostrar mensagem
    function showMessage(text, type) {
        const message = type === 'success' ? successMessage : errorMessage;
        message.textContent = text;
        message.style.display = 'block';
        
        setTimeout(() => {
            message.style.display = 'none';
        }, 5000);
    }

    // Inicializar página
    document.addEventListener('DOMContentLoaded', function() {
        // Os dados já estão carregados via PHP
        console.log('Página de perfil carregada');
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