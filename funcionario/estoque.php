<?php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$dbname = "gardenia";
$conn = mysqli_connect($servidor, $usuario, $senha, $dbname);
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Obter parâmetros de filtro
$filtro_nome = $_GET['filtro_nome'] ?? '';
$filtro_categoria = $_GET['filtro_categoria'] ?? '';
$filtro_estoque_baixo = isset($_GET['filtro_estoque_baixo']) ? true : false;
$ordenar_por = $_GET['ordenar_por'] ?? 'nome';
$ordenar_direcao = $_GET['ordenar_direcao'] ?? 'ASC';

// Construir consulta com filtros
$sql_produtos = "SELECT * FROM produtos WHERE 1=1";
$params = [];
$types = "";

if (!empty($filtro_nome)) {
    $sql_produtos .= " AND nome LIKE ?";
    $params[] = "%" . $filtro_nome . "%";
    $types .= "s";
}

if (!empty($filtro_categoria)) {
    $sql_produtos .= " AND categoria LIKE ?";
    $params[] = "%" . $filtro_categoria . "%";
    $types .= "s";
}

if ($filtro_estoque_baixo) {
    $sql_produtos .= " AND estoque <= 10";
}

// Ordenação
$ordenar_por = in_array($ordenar_por, ['nome', 'preco', 'estoque', 'categoria']) ? $ordenar_por : 'nome';
$ordenar_direcao = $ordenar_direcao === 'DESC' ? 'DESC' : 'ASC';
$sql_produtos .= " ORDER BY $ordenar_por $ordenar_direcao";

// Preparar e executar consulta
$stmt = $conn->prepare($sql_produtos);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_produtos = $stmt->get_result();

// Obter categorias únicas para o filtro
$sql_categorias = "SELECT DISTINCT categoria FROM produtos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria";
$result_categorias = $conn->query($sql_categorias);

if (isset($_POST['acao_produto']) && $_POST['acao_produto'] === 'deletar' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Produto deletado com sucesso!'); window.location.href='estoque.php';</script>";
        exit;
    } else {
        echo "<script>alert('Erro ao deletar produto.'); window.location.href='estoque.php';</script>";
        exit;
    }
}

if (isset($_POST['acao_produto']) && $_POST['acao_produto'] === 'editar' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $cores_disponiveis = $_POST['cores_disponiveis'] ?? '';
    $preco = floatval($_POST['preco'] ?? 0);
    $estoque = intval($_POST['estoque'] ?? 0);
    $categoria = $_POST['categoria'] ?? '';

    $stmt = $conn->prepare("UPDATE produtos SET nome=?, descricao=?, cores_disponiveis=?, preco=?, estoque=?, categoria=? WHERE id=?");
    $stmt->bind_param("sssdisi", $nome, $descricao, $cores_disponiveis, $preco, $estoque, $categoria, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Produto editado com sucesso!'); window.location.href='estoque.php';</script>";
        exit;
    } else {
        echo "<script>alert('Erro ao editar produto.'); window.location.href='estoque.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabela de Produtos</title>
    <link rel="shortcut icon" href="../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'tan pearl';
            src: url('../fonte/fonnts.com-tan-pearl.otf');
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            background: #ebe3d5;
            margin: 0;
            padding: 0;
        }

        .table-container {
            width: 100%;
            max-width: 1200px;
            margin: 100px auto 50px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow-x: auto;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        h2 {
            text-align: center;
            color: #584316;
            margin-bottom: 20px;
            font-family: 'tan pearl';
        }

        .filtros-container {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }

        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filtro-group {
            display: flex;
            flex-direction: column;
        }

        .filtro-label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #584316;
            font-size: 14px;
        }

        .filtro-input, .filtro-select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
        }

        .filtro-checkbox {
            margin-right: 5px;
        }

        .btn-filtro {
            background-color: #a5b29c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
        }

        .btn-filtro:hover {
            background-color: #8f9e87;
        }

        .btn-limpar {
            background-color: #eae0ff;
            color: #000;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-limpar:hover {
            background-color: #d1c3ff;
        }

        .filtros-actions {
            display: flex;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #d0d0c5;
        }

        th {
            background-color: #a5b29c;
            color: #fff;
            font-weight: 500;
            cursor: pointer;
            position: relative;
        }

        th:hover {
            background-color: #8f9e87;
        }

        .sort-indicator {
            margin-left: 5px;
            font-size: 12px;
        }

        tr:nth-child(even) {
            background-color: #f5f5f2;
        }

        tr:hover {
            background-color: #e6e6d8;
        }

        .estoque-baixo {
            background-color: #ffe6e6 !important;
        }

        .estoque-baixo:hover {
            background-color: #ffd6d6 !important;
        }

        input.input-table {
            width: 100%;
            box-sizing: border-box;
            padding: 4px 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: 'Roboto', sans-serif;
        }

        input.input-readonly {
            background: #f0f0e9;
            color: #888;
            border: 1px solid #ccc;
        }

        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background-color: #eae0ff;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 40px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .navbar-brand span {
            color: #00332c;
            font-family: 'Tan Pearl', serif;
            font-size: 22px;
            margin-left: 5px;
            font-weight: 300;
            letter-spacing: 1px;
        }

        .nav-right a {
            text-decoration: none;
            color: #00332c;
            font-family: 'Tan Pearl', serif;
            font-size: 15px;
            margin-right: 70px;
            transition: color 0.3s ease;
        }

        .btn-container {
            display: flex;
            gap: 5px;
        }

        .btn-editar,
        .btn-deletar {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            font-family: 'Times New Roman', Times, serif;
        }

        .btn-editar {
            background-color: #a5b29c;
            color: #fff;
        }

        .btn-editar:hover {
            background-color: #8f9e87;
        }

        .btn-deletar {
            background-color: #eae0ff;
            color: #000;
        }

        .btn-deletar:hover {
            background-color: #d1c3ff;
        }

        .info-filtros {
            background: #e8f4fd;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #a5b29c;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <span class="navbar-brand"><span>Gardenia</span></span>
        <div class="nav-right">
            <a href="adm_funcionario.php">Voltar ao painel</a>
        </div>
    </nav>

    <div class="table-container">
        <h2>Estoque de Produtos</h2>
        
        <!-- Filtros -->
        <div class="filtros-container">
            <form method="GET" action="">
                <div class="filtros-grid">
                    <div class="filtro-group">
                        <label class="filtro-label">Nome do Produto</label>
                        <input type="text" name="filtro_nome" value="<?= htmlspecialchars($filtro_nome) ?>" 
                               class="filtro-input" placeholder="Buscar por nome...">
                    </div>
                    
                    <div class="filtro-group">
                        <label class="filtro-label">Categoria</label>
                        <select name="filtro_categoria" class="filtro-select">
                            <option value="">Todas as categorias</option>
                            <?php while ($categoria_row = $result_categorias->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($categoria_row['categoria']) ?>" 
                                    <?= $filtro_categoria === $categoria_row['categoria'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria_row['categoria']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filtro-group">
                        <label class="filtro-label">
                            <input type="checkbox" name="filtro_estoque_baixo" value="1" 
                                   class="filtro-checkbox" <?= $filtro_estoque_baixo ? 'checked' : '' ?>>
                            Estoque baixo (≤ 10)
                        </label>
                    </div>

                    
                    <div class="filtros-actions">
                        <button type="submit" class="btn-filtro">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                        <a href="estoque.php" class="btn-limpar">
                            <i class="fas fa-times"></i> Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Informações sobre filtros aplicados -->
        <?php if (!empty($filtro_nome) || !empty($filtro_categoria) || $filtro_estoque_baixo): ?>
        <div class="info-filtros">
            <strong>Filtros aplicados:</strong>
            <?php
            $filtros = [];
            if (!empty($filtro_nome)) $filtros[] = "Nome: \"$filtro_nome\"";
            if (!empty($filtro_categoria)) $filtros[] = "Categoria: \"$filtro_categoria\"";
            if ($filtro_estoque_baixo) $filtros[] = "Estoque baixo";
            echo implode(', ', $filtros);
            ?>
        </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th onclick="sortTable('nome')">Nome 
                        <span class="sort-indicator">
                            <?php if ($ordenar_por === 'nome') echo ($ordenar_direcao === 'ASC' ? '↑' : '↓') ?>
                        </span>
                    </th>
                    <th>Descrição</th>
                    <th>Cores Disponíveis</th>
                    <th onclick="sortTable('preco')">Preço (R$) 
                        <span class="sort-indicator">
                            <?php if ($ordenar_por === 'preco') echo ($ordenar_direcao === 'ASC' ? '↑' : '↓') ?>
                        </span>
                    </th>
                    <th onclick="sortTable('estoque')">Estoque 
                        <span class="sort-indicator">
                            <?php if ($ordenar_por === 'estoque') echo ($ordenar_direcao === 'ASC' ? '↑' : '↓') ?>
                        </span>
                    </th>
                    <th onclick="sortTable('categoria')">Categoria 
                        <span class="sort-indicator">
                            <?php if ($ordenar_por === 'categoria') echo ($ordenar_direcao === 'ASC' ? '↑' : '↓') ?>
                        </span>
                    </th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_produtos && $result_produtos->num_rows > 0): ?>
                    <?php while ($row = $result_produtos->fetch_assoc()): ?>
                        <tr class="<?= $row['estoque'] <= 10 ? 'estoque-baixo' : '' ?>">
                            <form method="post" action="">
                                <td>
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                    <input type="text" name="nome" value="<?= htmlspecialchars($row['nome']) ?>"
                                        class="input-table" required>
                                </td>
                                <td><input type="text" name="descricao" value="<?= htmlspecialchars($row['descricao']) ?>"
                                        class="input-table"></td>
                                <td><input type="text" name="cores_disponiveis"
                                        value="<?= htmlspecialchars($row['cores_disponiveis']) ?>" class="input-table"></td>
                                <td><input type="number" step="0.01" name="preco"
                                        value="<?= htmlspecialchars($row['preco']) ?>" class="input-table" required></td>
                                <td><input type="number" name="estoque" value="<?= htmlspecialchars($row['estoque']) ?>"
                                        class="input-table" required></td>
                                <td><input type="text" name="categoria" value="<?= htmlspecialchars($row['categoria']) ?>"
                                        class="input-table"></td>
                                <td>
                                    <div class="btn-container">
                                        <button type="submit" name="acao_produto" value="editar" class="btn-editar">Editar</button>
                                        <button type="submit" name="acao_produto" value="deletar"
                                            onclick="return confirm('Deseja deletar este produto?')"
                                            class="btn-deletar">Deletar</button>
                                    </div>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">
                            <?php 
                            if (!empty($filtro_nome) || !empty($filtro_categoria) || $filtro_estoque_baixo) {
                                echo "Nenhum produto encontrado com os filtros aplicados.";
                            } else {
                                echo "Nenhum produto cadastrado.";
                            }
                            ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function sortTable(column) {
            const urlParams = new URLSearchParams(window.location.search);
            let currentOrder = '<?= $ordenar_direcao ?>';
            let currentColumn = '<?= $ordenar_por ?>';
            
            let newOrder = 'ASC';
            if (currentColumn === column && currentOrder === 'ASC') {
                newOrder = 'DESC';
            }
            
            urlParams.set('ordenar_por', column);
            urlParams.set('ordenar_direcao', newOrder);
            
            window.location.href = 'estoque.php?' + urlParams.toString();
        }
    </script>
</body>

</html>

<?php $conn->close(); ?>