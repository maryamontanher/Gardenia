<?php
session_start();

if (!isset($_SESSION['funcionario_id'])) {
  header('Location: ../login.php');
  exit;
}

$funcionario_id = $_SESSION['funcionario_id'];

// Conexão com o banco
$conn = new mysqli('localhost', 'root', '', 'gardenia');
if ($conn->connect_errno) {
  die("Falha na conexão: " . $conn->connect_error);
}

// Buscar dados atuais incluindo a foto
$stmt = $conn->prepare("SELECT nome, cpf, data_nascimento, sexo, cargo, departamento, salario, data_admissao, telefone, email, endereco, foto FROM funcionario WHERE id_funcionario = ?");
$stmt->bind_param("i", $funcionario_id);
$stmt->execute();
$stmt->bind_result($nome, $cpf, $data_nascimento, $sexo, $cargo, $departamento, $salario, $data_admissao, $telefone, $email, $endereco, $foto);
$stmt->fetch();
$stmt->close();

// Definir foto padrão se não tiver
$foto_perfil = !empty($foto) ? 'funcionario/img/uploads/' . $foto : 'funcionario/img/default-avatar.jpg';

// Variáveis para feedback
$erro = '';
$sucesso = '';

// Se enviou o formulário para atualizar perfil:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = $_POST['nome'] ?? '';
  $telefone = $_POST['telefone'] ?? '';
  $sexo = $_POST['sexo'] ?? '';
  $data_nascimento = $_POST['data_nascimento'] ?? '';
  $cargo = $_POST['cargo'] ?? '';
  $departamento = $_POST['departamento'] ?? '';
  $salario = $_POST['salario'] ?? '';
  $data_admissao = $_POST['data_admissao'] ?? '';
  $email = $_POST['email'] ?? '';
  $endereco = $_POST['endereco'] ?? '';

  $senha_atual = $_POST['senha_atual'] ?? '';
  $nova_senha = $_POST['nova_senha'] ?? '';
  $confirmar_senha = $_POST['confirmar_senha'] ?? '';

  // Validação básica
  if (!$nome || !$telefone || !$sexo || !$data_nascimento || !$cargo || !$salario || !$data_admissao || !$email) {
    $erro = "Preencha todos os campos obrigatórios.";
  } else {
    // Se alterar senha
    $alterar_senha = false;
    if ($senha_atual || $nova_senha || $confirmar_senha) {
      if (!$senha_atual || !$nova_senha || !$confirmar_senha) {
        $erro = "Para alterar a senha, preencha todos os campos de senha.";
      } elseif ($nova_senha !== $confirmar_senha) {
        $erro = "Nova senha e confirmação não coincidem.";
      } else {
        $alterar_senha = true;
      }
    }

    if (!$erro) {
      // Se alterar senha, validar senha atual
      if ($alterar_senha) {
        $stmt = $conn->prepare("SELECT senha FROM funcionario WHERE id_funcionario = ?");
        $stmt->bind_param("i", $funcionario_id);
        $stmt->execute();
        $stmt->bind_result($senha_hash);
        if (!$stmt->fetch()) {
          $erro = "Usuário não encontrado.";
        }
        $stmt->close();

        if ($erro === '' && $senha_atual !== $senha_hash) {
          $erro = "Senha atual incorreta.";
        }
      }

      if (!$erro) {
        if ($alterar_senha) {
          $nova_senha_hash = $nova_senha; // Adapte para hash se desejar
          $stmt = $conn->prepare("UPDATE funcionario SET nome = ?, telefone = ?, sexo = ?, data_nascimento = ?, cargo = ?, departamento = ?, salario = ?, data_admissao = ?, email = ?, endereco = ?, senha = ? WHERE id_funcionario = ?");
          $stmt->bind_param("ssssssdssssi", $nome, $telefone, $sexo, $data_nascimento, $cargo, $departamento, $salario, $data_admissao, $email, $endereco, $nova_senha_hash, $funcionario_id);
        } else {
          $stmt = $conn->prepare("UPDATE funcionario SET nome = ?, telefone = ?, sexo = ?, data_nascimento = ?, cargo = ?, departamento = ?, salario = ?, data_admissao = ?, email = ?, endereco = ? WHERE id_funcionario = ?");
          $stmt->bind_param("ssssssdsssi", $nome, $telefone, $sexo, $data_nascimento, $cargo, $departamento, $salario, $data_admissao, $email, $endereco, $funcionario_id);
        }

        if ($stmt->execute()) {
          $sucesso = "Perfil atualizado com sucesso!";
        } else {
          $erro = "Erro ao atualizar perfil: " . $stmt->error;
        }
        $stmt->close();
      }
    }
  }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Perfil do Funcionario - Gardenia</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="shortcut icon" href="../images/logo.png">
  <style>
    @font-face {
      font-family: 'tan pearl';
      /* Um nome para usar a fonte */
      src: url('../fonte/fonnts.com-tan-pearl.otf');
      /* O caminho para o arquivo da fonte */
    }

    :root {
      --verde: #584316;
      --verde-claro: #e3f7f5;
      --cinza: #f0f0f0;
      --cinza-escuro: #6c757d;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--cinza);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background-color: #ebe3d5;
    }

    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #eae0ff;
      padding: 0px 10px;
      position: fixed;
      top: 0;
      left: 0;
      height: 70px;
      width: 100%;
      z-index: 1000;
    }



    .perfil-container {
      background: white;
      width: 90%;
      max-width: 950px;
      min-height: 500px;
      border-radius: 20px;
      display: flex;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      margin-top: 100px;
    }

    .foto-lado {
      width: 35%;
      background-color: #eae0ff;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }

    .foto-lado h2 {
      color: #9ba885;
      margin-bottom: 20px;
      text-align: center;
      font-family: 'tan pearl', serif;
    }

    .foto-perfil {
      width: 160px;
      height: 160px;
      border-radius: 50%;
      background-color: #fff;
      background-size: cover;
      background-position: center;
      border: 4px solid #9ba885;
      cursor: pointer;
    }

    .input-file-wrapper {
      position: relative;
      overflow: hidden;
      display: inline-block;
      margin-top: 15px;
    }

    .btn-upload {
      border: none;
      color: white;
      background-color: #9ba885;
      padding: 10px 16px;
      border-radius: 8px;
      font-size: 14px;
      cursor: pointer;
      font-weight: bold;
      transition: 0.3s;
    }

    .btn-upload:hover {
      background-color: #9ba885;
    }

    .input-file-wrapper input[type="file"] {
      font-size: 100px;
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      cursor: pointer;
    }

    .form-lado {
      width: 65%;
      padding: 2.5rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 15px;
    }

    .form-lado h2 {
      color: #584316;
      margin-bottom: 20px;
    }

    .form-group {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .form-col {
      flex: 1;
      min-width: 240px;
      display: flex;
      flex-direction: column;
    }

    .form-col label {
      margin-bottom: 6px;
      font-weight: 500;
      color: #584316;
      font-size: 14px;
    }

    .form-col input,
    .form-col select {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
      background-color: #fff;
    }

    .form-col input[disabled] {
      background-color: #f9f9f9;
      color: #888;
    }

    .observacao {
      font-size: 11px;
      color: #999;
      margin-top: 4px;
    }

    .botoes {
      display: flex;
      gap: 15px;
      margin-top: 20px;
    }

    .btn-editar,
    .btn-senha {
      background-color: #9ba885;
      color: white;
      padding: 12px 18px;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
      font-size: 14px;
    }

    .btn-editar:hover,
    .btn-senha:hover {
      background-color: #9ba885;
    }

    /* Modal senha */
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-conteudo {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      width: 350px;
      box-shadow: 0 0 10px #0003;
    }

    .modal-conteudo h3 {
      margin-top: 0;
      margin-bottom: 1rem;
      color: #584316;
    }

    .modal-conteudo label {
      display: block;
      margin-bottom: 6px;
      font-weight: 500;
      color: #584316;
    }

    .modal-conteudo input {
      width: 100%;
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 14px;
    }

    .modal-botoes {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .btn-cancelar,
    .btn-confirmar {
      padding: 10px 16px;
      border-radius: 8px;
      border: none;
      font-weight: bold;
      cursor: pointer;
    }

    .btn-cancelar {
      background: #ccc;
      color: #333;
    }

    .btn-confirmar {
      background: #9ba885;
      color: white;
    }

    .btn-cancelar:hover {
      background: #999;
    }

    .btn-confirmar:hover {
      background: #584316;
    }

    .msg-erro {
      color: #c00;
      font-weight: 600;
      margin-bottom: 10px;
    }

    .msg-sucesso {
      color: green;
      font-weight: 600;
      margin-bottom: 10px;
    }

    .nav-right a {
      color: #584316;
      text-decoration: none;

    }

    .nav-left a:hover,
    .nav-right a:hover {
      color: #464d3c;
    }

    .nav-center {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .nav-center img {
      max-height: 60px;
      width: auto;
      display: block;
      filter: drop-shadow(0 2px 4px #464d3c);
    }

    .nav-center .logo {
      width: 50px;
      height: auto;
      vertical-align: middle;
    }

    .nav-right {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .nav-right .cart i {
      font-size: 1.5rem;
      color: #584316;
      transition: color 0.3s;
    }

    .nav-right .cart i:hover {
      color: #464d3c;
    }


    .logo img {
      width: 120px;
      background: no-repeat left center / contain;
    }

    .nav-links {
      list-style: none;
      display: flex;
    }

    .nav-links li {
      margin: 0 15px;
    }

    .nav-links a {
      color: white;
      text-decoration: none;
      font-size: 18px;
      transition: color 0.3s;
    }

    .nav-links a:hover {
      color: rgb(50, 97, 81);
    }
     .modal-foto {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-foto-conteudo {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      width: 400px;
      box-shadow: 0 0 10px #0003;
      text-align: center;
    }

    .photo-preview {
      max-width: 200px;
      max-height: 200px;
      margin: 15px auto;
      display: none;
      border-radius: 10px;
    }

    .photo-options {
      display: flex;
      flex-direction: column;
      gap: 15px;
      margin: 20px 0;
    }

    .photo-option {
      padding: 15px;
      border: 2px dashed #ddd;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .photo-option:hover {
      border-color: #9ba885;
      background: #f9f9f9;
    }

    .photo-option i {
      font-size: 2rem;
      color: #9ba885;
      margin-bottom: 10px;
    }

    .file-input {
      display: none;
    }
  </style>
</head>

<body>
  <nav class="navbar">
    <div class="container-fluid">
      <a>
        
  <span style="color:#00332c;font-family:'tan pearl'; font-size:20px; margin-left:25px;">Gardenia</span>
      </a>

    </div>
    <div class="nav-right">
      <a href="adm_funcionario.php" style="color:#00332c;font-family:'tan pearl'; font-size:20pxrem; margin-right:25px">Voltar ao
        painel</a>
    </div>
  </nav>

  <br><br><br><br>
  <div class="perfil-container">
    <div class="foto-lado">
      <form method="post" action="" id="formPerfil" enctype="multipart/form-data" class="form-foto">
        <h2 style="font-family: 'Times New Roman', serif;">Foto de Perfil</h2>

        
      <div class="foto-perfil" id="imagemPerfil"
        style="background-image: url('<?= $foto_perfil ?>?t=<?= time() ?>');"
        onclick="abrirModalFoto()" title="Clique para alterar a foto"></div>

        <div class="input-file-wrapper" style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
          <button type="button" class="btn-upload" onclick="document.getElementById('fotoInput').click()" style="font-family: 'Times New Roman', serif;">Escolher foto</button>
          <input type="file" accept="image/*" id="fotoInput" name="foto" onchange="carregarImagem(this)" />
        </div>

        <!-- Campos ocultos para senha -->
        <input type="hidden" name="senha_atual" />
        <input type="hidden" name="nova_senha" />
        <input type="hidden" name="confirmar_senha" />
    </div>

    <div class="form-lado">
      <h2 style="font-family:'tan pearl';">Dados do Funcionario</h2>

      <?php if ($erro): ?>
        <div class="msg-erro"><?= htmlspecialchars($erro) ?></div>
      <?php elseif ($sucesso): ?>
        <div class="msg-sucesso"><?= htmlspecialchars($sucesso) ?></div>
      <?php endif; ?>


      <div class="form-group">
        <div class="form-col">
          <label style="font-family: 'Times New Roman', serif;">Nome</label>
          <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" disabled
            style="font-family: 'Times New Roman', serif;" />
        </div>
        <div class="form-col">
          <label style="font-family: 'Times New Roman', serif;">CPF</label>
          <input type="text" value="<?= htmlspecialchars($cpf) ?>" disabled
            style="font-family: 'Times New Roman', serif;" />
          <span class="observacao" style="font-family: 'Times New Roman', serif;">Solicite alteração à
            administração</span>
        </div>
      </div>

      <div class="form-group">
        <div class="form-col">
          <label style="font-family: 'Times New Roman', serif;">Telefone</label>
          <input type="text" name="telefone" value="<?= htmlspecialchars($telefone) ?>" disabled
            style="font-family: 'Times New Roman', serif;" />
        </div>
        <div class="form-col">
          <label style="font-family: 'Times New Roman', serif;">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" disabled
            style="font-family: 'Times New Roman', serif;" />
        </div>
      </div>

      <div class="form-group">
        <div class="form-col">
          <label style="font-family: 'Times New Roman', serif;">Sexo</label>
          <select name="sexo" disabled style="font-family: 'Times New Roman', serif;">
            <option value="M" <?= $sexo === 'M' ? 'selected' : '' ?>>Masculino</option>
            <option value="F" <?= $sexo === 'F' ? 'selected' : '' ?>>Feminino</option>
            <option value="O" <?= $sexo === 'O' ? 'selected' : '' ?>>Outro</option>
          </select>
        </div>
        <div class="form-col">
          <label style="font-family: 'Times New Roman', serif;">Data de Nascimento</label>
          <input type="date" name="data_nascimento" value="<?= htmlspecialchars($data_nascimento) ?>" disabled
            style="font-family: 'Times New Roman', serif;" />
        </div>
      </div>

      <div class="form-group">
        <div class="form-col">
          <label style="font-family: 'Times New Roman', serif;">Cargo</label>
          <input type="text" name="cargo" value="<?= htmlspecialchars($cargo) ?>" disabled
            style="font-family: 'Times New Roman', serif;" />
        </div>
        <div class="form-col">
          <label style="font-family: 'Times New Roman', serif;">Departamento</label>
          <input type="text" name="departamento" value="<?= htmlspecialchars($departamento) ?>" disabled
            style="font-family: 'Times New Roman', serif;" />
        </div>
      </div>

      <div class="form-group">
        <div class="form-col">
          <label style="font-family: 'Times New Roman', serif;">Salário</label>
          <input type="number" step="0.01" name="salario" value="<?= htmlspecialchars($salario) ?>" disabled
            style="font-family: 'Times New Roman', serif;" />
        </div>
        <div class="form-col">
          <label style="font-family: 'Times New Roman', serif;">Data de Admissão</label>
          <input type="date" name="data_admissao" value="<?= htmlspecialchars($data_admissao) ?>" disabled
            style="font-family: 'Times New Roman', serif;" />
        </div>
      </div>

      <div class="form-group">
        <div class="form-col" style="flex:2;">
          <label style="font-family: 'Times New Roman', serif;">Endereço</label>
          <input type="text" name="endereco" value="<?= htmlspecialchars($endereco) ?>" disabled
            style="font-family: 'Times New Roman', serif;" />
        </div>
      </div>

      <div class="botoes">
        <button type="submit" class="btn-editar" style="font-family: 'Times New Roman', serif;">Salvar
          Alterações</button>
        <button type="button" class="btn-senha" onclick="abrirModal()"
          style="font-family: 'Times New Roman', serif;">Alterar Senha</button>

        <!-- Campos ocultos para senha (preenchidos pelo modal) -->
        <input type="hidden" name="senha_atual" />
        <input type="hidden" name="nova_senha" />
        <input type="hidden" name="confirmar_senha" />
        </form>
      </div>
    </div>

    <!-- Modal Alterar Senha -->
    <div class="modal" id="modalSenha">
      <div class="modal-conteudo">
        <h3 style="font-family: 'Times New Roman', serif;">Alterar Senha</h3>
        <form method="post" id="formSenha">
          <label for="senha_atual"style="font-family: 'Times New Roman', serif;">Senha Atual</label>
          <input type="password" id="senha_atual" name="senha_atual"style="font-family: 'Times New Roman', serif;" />

          <label for="nova_senha"style="font-family: 'Times New Roman', serif;">Nova Senha</label>
          <input type="password" id="nova_senha" name="nova_senha"style="font-family: 'Times New Roman', serif;" />

          <label for="confirmar_senha"style="font-family: 'Times New Roman', serif;">Confirmar Nova Senha</label>
          <input type="password" id="confirmar_senha" name="confirmar_senha" style="font-family: 'Times New Roman', serif;"/>

          <div class="modal-botoes">
            <button type="button" class="btn-cancelar" onclick="fecharModal()"style="font-family: 'Times New Roman', serif;">Cancelar</button>
            <button type="button" class="btn-confirmar" onclick="confirmarSenha()"style="font-family: 'Times New Roman', serif;">Confirmar</button>
          </div>
        </form>
      </div>
    </div>

  <!-- Modal para Alterar Foto -->
  <div class="modal-foto" id="modalFoto">
    <div class="modal-foto-conteudo">
      <h3 style="font-family: 'Times New Roman', serif;">Alterar Foto de Perfil</h3>
      
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
        
        <div class="modal-botoes">
          <button type="button" class="btn-cancelar" onclick="fecharModalFoto()" style="font-family: 'Times New Roman', serif;">Cancelar</button>
          <button type="button" class="btn-confirmar" id="save-photo" disabled onclick="salvarFoto()" style="font-family: 'Times New Roman', serif;">Salvar Foto</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    // Modal de Foto
    function abrirModalFoto() {
      document.getElementById('modalFoto').style.display = 'flex';
    }

    function fecharModalFoto() {
      document.getElementById('modalFoto').style.display = 'none';
      resetModalFoto();
    }

    function resetModalFoto() {
      document.getElementById('photo-preview').style.display = 'none';
      document.getElementById('photo-upload').value = '';
      document.getElementById('save-photo').disabled = true;
    }

    // Upload de foto
    document.getElementById('upload-photo-option').addEventListener('click', () => {
      document.getElementById('photo-upload').click();
    });

    document.getElementById('photo-upload').addEventListener('change', function(e) {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          document.getElementById('photo-preview').src = e.target.result;
          document.getElementById('photo-preview').style.display = 'block';
          document.getElementById('save-photo').disabled = false;
        };
        
        reader.readAsDataURL(this.files[0]);
      }
    });

    // Remover foto
    document.getElementById('remove-photo-option').addEventListener('click', () => {
      if (confirm('Tem certeza que deseja remover sua foto de perfil?')) {
        removerFoto();
      }
    });

    // Salvar foto
    function salvarFoto() {
      const fileInput = document.getElementById('photo-upload');
      
      if (!fileInput.files[0]) {
        alert('Selecione uma foto para upload.');
        return;
      }

      const formData = new FormData();
      formData.append('foto', fileInput.files[0]);
      
      // Mostrar loading
      const saveButton = document.getElementById('save-photo');
      saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
      saveButton.disabled = true;
      
      fetch('upload_foto_funcionario.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Atualizar foto
          document.getElementById('imagemPerfil').style.backgroundImage = 'url(' + data.foto_url + '?t=' + new Date().getTime() + ')';
          fecharModalFoto();
          alert(data.message);
        } else {
          alert(data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Erro ao processar solicitação.');
      })
      .finally(() => {
        saveButton.innerHTML = 'Salvar Foto';
        saveButton.disabled = false;
      });
    }

    // Remover foto
    function removerFoto() {
      // Mostrar loading
      const removeOption = document.getElementById('remove-photo-option');
      const originalHTML = removeOption.innerHTML;
      removeOption.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removendo...';
      
      fetch('upload_foto_funcionario.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'remover_foto=true'
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Atualizar foto
          document.getElementById('imagemPerfil').style.backgroundImage = 'url(' + data.foto_url + '?t=' + new Date().getTime() + ')';
          fecharModalFoto();
          alert(data.message);
        } else {
          alert(data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Erro ao processar solicitação.');
      })
      .finally(() => {
        removeOption.innerHTML = originalHTML;
      });
    }

    // Funções do modal de senha (mantidas iguais)
    function abrirModal() {
      document.getElementById('modalSenha').style.display = 'flex';
    }
    
    function fecharModal() {
      document.getElementById('modalSenha').style.display = 'none';
      document.getElementById('senha_atual').value = '';
      document.getElementById('nova_senha').value = '';
      document.getElementById('confirmar_senha').value = '';
    }
    
    function confirmarSenha() {
      const formPerfil = document.getElementById('formPerfil');
      formPerfil.querySelector('input[name="senha_atual"]').value = document.getElementById('senha_atual').value;
      formPerfil.querySelector('input[name="nova_senha"]').value = document.getElementById('nova_senha').value;
      formPerfil.querySelector('input[name="confirmar_senha"]').value = document.getElementById('confirmar_senha').value;
      fecharModal();
      formPerfil.submit();
    }
  </script>
</body>
</html>