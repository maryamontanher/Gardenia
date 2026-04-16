<?php 
    session_start();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="./css/login.css">
    <link rel="shortcut icon" href="images/logo.png" type="image/x-icon">
    <title>Login de acesso</title>
    <style>
         body {
            background-image: url('images/login.png'); 
         }
    </style>
</head>
<body>

<div class="container">
    <h1>Login</h1>

    <form name="f2" action="proc_login.php" method="post">
        <label for="email">E-mail</label>
        <input type="text" id="email" name="email" placeholder="E-mail">

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="Senha">

        <input type="submit" value="Enviar">

        <div class="register-link">
            Não tem uma conta? <a href="cadastro.php">Cadastre-se</a>
        </div>
    </form>
</div>

</body>
</html>
