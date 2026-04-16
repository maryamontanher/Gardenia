<?php
include_once("../conexao.php");

$mensagem = ''; // Variável para armazenar a mensagem

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $preco = $_POST['preco'] ?? 0;
    $estoque = $_POST['estoque'] ?? 0;
    $categoria = $_POST['categoria'] ?? '';
    $cores = $_POST['cores'] ?? '';

    $imagemNome = '';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../images/";
        $imagemNome = basename($_FILES['imagem']['name']);
        $uploadFile = $uploadDir . $imagemNome;

        if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadFile)) {
            $mensagem = "<p style='color: red;'>Erro ao fazer upload da imagem.</p>";
        }
    }

    $sql = "INSERT INTO produtos 
            (nome, descricao, preco, estoque, imagem, categoria, cores_disponiveis) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        $mensagem = "<p style='color: red;'>Erro na preparação da query: " . mysqli_error($conn) . "</p>";
    } else {
        mysqli_stmt_bind_param(
            $stmt,
            "ssdisss",
            $nome,
            $descricao,
            $preco,
            $estoque,
            $imagemNome,
            $categoria,
            $cores
        );

        if (mysqli_stmt_execute($stmt)) {
            $mensagem = "<p style='color: green;'>Produto adicionado com sucesso!</p>";
        } else {
            $mensagem = "<p style='color: red;'>Erro ao adicionar produto: " . mysqli_stmt_error($stmt) . "</p>";
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>


<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="shortcut icon" href="../images/logo.png">
    <link rel="stylesheet" href="../css/add_produto.css">
    <link rel="shortcut icon" href="../images/logo.png">
    <title>Gardenia | Adicionar Produto</title>

</head>

<body>
    <nav class="navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="adm_funcionario.php">
                <span style="color:#00332c;font-family:'tan pearl';">Gardenia</span>
            </a>
            <div class="nav-right">
                <a href="adm_funcionario.php" style="color:#00332c;font-family:'tan pearl';">Voltar ao painel</a>
            </div>
        </div>
    </nav>
    <div class="container">
        
        <h1>Adicione Produtos</h1><br>
        <center><?php if (!empty($mensagem)) echo $mensagem; ?></center>
        <form name="f2" action="" method="post" id="formCadastro">
            <div class="separando">
                <div class="formdadireita">
                    <labeln style="font-family: 'Times New Roman', serif;">Nome:</label>
                    <input type="text" name="nome" required placeholder="Digite nome do produto">
                    <label style="font-family: 'Times New Roman', serif;">Descrição:</label>
                    <textarea name="descricao" required placeholder="Digite a descricao do produto" cols="38" rows="2.5"></textarea>
                    <label style="font-family: 'Times New Roman', serif;">Preço:</label>
                    <input type="number" step="0.01" name="preco" required placeholder="Digite o preço do produto">
                </div>

                <div class="formdadireita">
                    <label style="font-family: 'Times New Roman', serif;">Estoque:</label>
                    <input type="number" name="estoque" required placeholder="Quantidade em estoque" style="font-family: 'Times New Roman', serif;">
                    <label style="font-family: 'Times New Roman', serif;">Categoria:</label>
                    <input type="text" name="categoria" placeholder="ex: flores, embalagens, etc" style="font-family: 'Times New Roman', serif;">
                    <label style="font-family: 'Times New Roman', serif;">Cores disponíveis:
                    </label>
                    <input type="text" name="cores" placeholder="ex: vermelho, azul, amarelo"style="font-family: 'Times New Roman', serif;">
                </div>
                <div>
                    <center><label style="font-family: 'Times New Roman', serif;">Imagem:</label></center>
                    <center><input type="file" name="imagem" accept="image/*" required></center>
                    <br>
                    <input type="submit" value="Adicionar Produto" style="font-family:'tan pearl', serif;;">
                </div>
            </div>
        </form>
    </div>
</body>

</html>