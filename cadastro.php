<?php 
    session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

    <meta charset="utf-8">
    <link rel="stylesheet" href="./css/cadastro.css">
    <link rel="shortcut icon" href="images/logo.png" type="image/x-icon">
    <title>Cadastro de cliente</title>
    <style>
         body {
            background-image: url('images/login.png'); 
         }
    </style>
    <title>cadastro</title>
</head>
<body>
<div class="container">
    <h1>Cadastro</h1>
    <?php
if (isset($_SESSION['msg'])) {
    echo $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>
    <form name="f2" action="proc_cadastro.php" method="post">
        <div class="col">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" placeholder="Nome" required>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="E-mail" required>
            
            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf" placeholder="CPF" maxlength="11" required>

            <label for="telefone">Telefone</label>
            <input type="tel" id="telefone" name="telefone" placeholder="Telefone" required>

            <label for="nascimento">Data de nascimento</label>
            <input type="date" id="nascimento" name="nascimento" required>
            
        </div>

        <div class="col">
            <label for="cep">CEP</label>
            <input type="text" id="cep" name="cep" placeholder="CEP"required>

            <label for="estado">Estado</label>
            <input type="text" id="estado" name="estado" placeholder="Estado" required>

            <label for="cidade">Cidade</label>
            <input type="text" id="cidade" name="cidade" placeholder="Cidade" required>
            
            <label for="estado">Rua</label>
            <input type="text" id="rua" name="rua" placeholder="Rua" required>
            
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="Senha" required>
        </div>

        <input type="submit" value="Enviar">

        <div class="register-link">
            <font color="#000000">é usuário?
                <a rel="noopener noreferrer" href="login.php"><font color="blue">entre</font></a>
            </font>
        </div>
    </form>
</div>
<script>
// Máscara CPF
const cpfInput = document.getElementById('cpf');
cpfInput.addEventListener('input', function(e) {
    let value = cpfInput.value.replace(/\D/g, '');
    if (value.length > 11) value = value.slice(0, 11);
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    cpfInput.value = value;
});

// Máscara Telefone
const telefoneInput = document.getElementById('telefone');
telefoneInput.addEventListener('input', function(e) {
    let value = telefoneInput.value.replace(/\D/g, '');
    if (value.length > 11) value = value.slice(0, 11);

    // Aplica máscara
    value = value.replace(/^(\d{2})(\d)/g, '($1) $2');       // Coloca parênteses
    value = value.replace(/(\d{5})(\d)/, '$1-$2');           // Coloca traço
    telefoneInput.value = value;
});
</script>
<script>
$(document).ready(function(){
  $('#cpf').mask('000.000.000-00');
  $('#telefone').mask('(00) 00000-0000');
});
</script>



</body>
</html>
