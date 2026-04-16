<?php
session_start();
if (!isset($_SESSION['funcionario_id'])) {
    header("Location: ../login.php");
    exit();
}

$funcionario_id = $_SESSION['funcionario_id'];
$conn = new mysqli("localhost", "root", "", "gardenia");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}
$sql = "SELECT nome FROM funcionario WHERE id_funcionario = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Erro no prepare: " . $conn->error);
}
$stmt->bind_param("i", $funcionario_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$funcionario_nome = $row['nome'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="shortcut icon" href="../images/logo.png">
  <link rel="stylesheet" href="../css/funcionario.css">
  <link rel="shortcut icon" href="../images/logo.png">
  <title>Gardenia | Painel do Funcionário</title>
  
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" >
      <img src="../images/logo.png" alt="Logo" width="40" height="40" class="d-inline-block align-text-top">
      <span style="color:#00332c;font-family:'tan pearl';">Gardenia</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item" style="margin-right: 30px;">
          <a class="nav-link-marrom" href="#" onclick="confirmarSaida(event)" style="text-decoration: none; border-bottom: none;"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<br><br>
  <div class="dashboard container">
    <h2 class="section-title tan-pearl" style="color:#584316;">Painel do Funcionário</h2>
    <h4 style="text-align:center; color:#157c5d; font-family:'Times New Roman', serif;">Bem-vindo, <?php echo htmlspecialchars($funcionario_nome); ?>!</h4>

 <br>
<div class="row g-4">
  <div class="col-md-4">
    <a href="cadastro-funcionario.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa-solid fa-users"></i>
        <h5 style="font-family: 'Times New Roman', serif;">Cadastro de funcionários</h5>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="pagamentos.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa fa-dollar-sign"></i>
        <h5 style="font-family: 'Times New Roman', serif;">Pagamentos</h5>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="add_product.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa-solid fa-box-open"></i>
        <h5 style="font-family: 'Times New Roman', serif;">Inserir produtos</h5>
      </div>
    </a>
  </div>
</div>
<br>
<div class="row justify-content-center g-4">
    <div class="col-md-4">
      <a href="estoque.php" style="text-decoration: none;">
        <div class="card p-4 text-center">
          <i class="fa-solid fa-dolly"></i>
          <h5 style="font-family: 'Times New Roman', serif;">Estoque</h5>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="perfil-funcionario.php" style="text-decoration: none;">
        <div class="card p-4 text-center">
          <i class="fa-solid fa-user-tie"></i>
          <h5 style="font-family: 'Times New Roman', serif;">Perfil</h5>
        </div>
      </a>
    </div>
    <div class="col-md-4">
    <a href="relatorios.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa-solid fa-chart-bar"></i>
        <h5 style="font-family: 'Times New Roman', serif;">Relatórios</h5>
      </div>
    </a>
  </div>
  </div>
  <script>
   function confirmarSaida(event) {
  event.preventDefault();
  if (confirm("Deseja realmente sair da conta?")) {
    window.location.href = "../sair.php";
  }
}
  </script>
</body>
</html>
