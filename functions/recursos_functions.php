<?php
// Processar filtros do formulário
$materia = isset($_GET['materia']) ? $_GET['materia'] : '';
$formato = isset($_GET['formato']) ? $_GET['formato'] : '';

// Construir a consulta SQL com base nos filtros
$sql = "SELECT m.*, u.nome as autor 
        FROM materiais m 
        INNER JOIN usuario u ON m.usuario_id = u.idusuario 
        WHERE 1=1";
$params = [];

if (!empty($materia)) {
    $sql .= " AND m.materia = ?";
    $params[] = $materia;
}

if (!empty($formato)) {
    $sql .= " AND m.tipo = ?";
    $params[] = $formato;
}

$sql .= " ORDER BY m.data_publicacao DESC";

// Executar a consulta
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>