<?php
$conn = new mysqli('localhost', 'root', '', 'gardenia');
if ($conn->connect_error) {
    die('Erro na conexão: ' . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST['nome'] ?? '';
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $sexo = $_POST['sexo'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    $departamento = $_POST['departamento'] ?? '';
    $salario = $_POST['salario'] ?? '';
    $data_admissao = $_POST['data_admissao'] ?? '';
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $email = $_POST['email'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Verificação de duplicidade de CPF ou email
    $stmt = $conn->prepare("SELECT id_funcionario FROM funcionario WHERE cpf = ? OR email = ?");
    $stmt->bind_param("ss", $cpf, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Já existe um funcionário com este CPF ou e-mail.'); window.history.back();</script>";
        exit;
    }
    $stmt->close();

    // Hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO funcionario (nome, cpf, data_nascimento, sexo, cargo, departamento, salario, data_admissao, telefone, email, endereco, senha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssdsssss", $nome, $cpf, $data_nascimento, $sexo, $cargo, $departamento, $salario, $data_admissao, $telefone, $email, $endereco, $senha_hash);

    if ($stmt->execute()) {
        echo "<script>alert('Funcionário cadastrado com sucesso!'); window.location.href='adm_funcionario.php';</script>";
        exit;
    } else {
        echo "<script>alert('Erro ao cadastrar: " . $stmt->error . "'); window.history.back();</script>";
        exit;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="../css/cadastrofuncionario.css">
  <link rel="shortcut icon" href="../images/logo.png">
  
  <title>Cadastro Funcionario - Gardenia</title>
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
  <div class="container">
    <h1 style=" font-family: 'tan pearl', serif; color: #584316;">Cadastro do Funcionário</h1>
    <br><br>
        <center><?php if (!empty($mensagem)) echo $mensagem; ?></center>

        <form name="f2" action="" method="post" id="formCadastro">
            <div class="separando">
                <div class="formdadireita">
                    <label style="font-family: 'Times New Roman', serif;">Nome:</label>
                    <input type="text" name="nome" required placeholder="Digite o nome do funcionário">

                    <label style="font-family: 'Times New Roman', serif;">CPF:</label>
                    <input type="text" name="cpf" required placeholder="Digite o CPF">

                    <label style="font-family: 'Times New Roman', serif;">Data de Nascimento:</label>
                    <input type="date" name="data_nascimento" required>

                    <label style="font-family: 'Times New Roman', serif;">Sexo:</label>
                    <select name="sexo" required>
                        <option value="">Selecione</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Feminino">Feminino</option>
                        <option value="Outro">Outro</option>
                    </select>

                    <label style="font-family: 'Times New Roman', serif;">Cargo:</label>
                    <input type="text" name="cargo" required placeholder="Digite o cargo">

                    <label style="font-family: 'Times New Roman', serif;">Departamento:</label>
                    <input type="text" name="departamento" required placeholder="Digite o departamento">
                </div>

                <div class="formdadireita">
                  <br>
                    <label style="font-family: 'Times New Roman', serif;">Salário:</label>
                    <input type="number" step="0.01" name="salario" required placeholder="Digite o salário">

                    <label style="font-family: 'Times New Roman', serif;">Data de Admissão:</label>
                    <input type="date" name="data_admissao" required>

                    <label style="font-family: 'Times New Roman', serif;">Telefone:</label>
                    <input type="text" name="telefone" required placeholder="Digite o telefone">

                    <label style="font-family: 'Times New Roman', serif;">E-mail:</label>
                    <input type="email" name="email" required placeholder="Digite o e-mail">

                    <label style="font-family: 'Times New Roman', serif;">Endereço:</label>
                    <input type="text" name="endereco" required placeholder="Digite o endereço">

                    <label style="font-family: 'Times New Roman', serif;">Senha:</label>
                    <input type="password" name="senha" required placeholder="Digite a senha">
                </div>
            </div>

            <input type="submit" value="Cadastrar" class="entrar" style=" font-family: 'tan pearl', serif;">
        </form>
    </div>

  
</body>
</html>
