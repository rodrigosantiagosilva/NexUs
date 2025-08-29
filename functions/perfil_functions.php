<?php
require 'includes/conexao.php';

/**
 * Busca informações do usuário
 */
function buscarUsuario($pdo, $usuarioId) {
    $sql = "SELECT * FROM usuario WHERE idusuario = :usuario_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Busca configurações do usuário
 */
function buscarConfigUsuario($pdo, $usuarioId) {
    $sql = "SELECT * FROM usuario_config WHERE usuario_id = :usuario_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Busca aparência do usuário
 */
function buscarAparenciaUsuario($pdo, $usuarioId) {
    $sql = "SELECT * FROM usuario_aparencia WHERE usuario_id = :usuario_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Busca estatísticas do usuário
 */
function buscarEstatisticasUsuario($pdo, $usuarioId) {
    // Conexões (seguindo)
    $sql = "SELECT COUNT(*) FROM usuario_seguidores WHERE seguidor_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $conexoes = $stmt->fetchColumn();
    
    
    // Conexões (seguidores)
    $sql = "SELECT COUNT(*) FROM usuario_seguidores WHERE seguido_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $conectados = $stmt->fetchColumn();
    
    // Grupos
    $sql = "SELECT COUNT(*) FROM grupo_membros WHERE usuario_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $grupos = $stmt->fetchColumn();
    
    // Eventos criados
    $sql = "SELECT COUNT(*) FROM eventos WHERE criador_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $eventos = $stmt->fetchColumn();
    
    // Materiais publicados
    $sql = "SELECT COUNT(*) FROM materiais WHERE usuario_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $materiais = $stmt->fetchColumn();
    
    // Conquistas baseadas em atividades reais
    $conquistas = [];
    if ($conectados > 0) $conquistas[] = 'Conectivo';
    if ($grupos > 0) $conquistas[] = 'Participativo';
    if ($eventos > 0) $conquistas[] = 'Organizador';
    if ($materiais > 0) $conquistas[] = 'Contribuidor';
    
    return [
        'conexoes' => $conexoes,
        'conectados' => $conectados,
        'grupos' => $grupos,
        'eventos' => $eventos,
        'materiais' => $materiais,
        'conquistas' => $conquistas
    ];
}

/**
 * Busca grupos do usuário
 */
function buscarGruposUsuario($pdo, $usuarioId) {
    $sql = "SELECT g.*, 
            (SELECT COUNT(*) FROM grupo_membros WHERE grupo_id = g.id) AS total_membros
            FROM grupos g
            JOIN grupo_membros gm ON g.id = gm.grupo_id
            WHERE gm.usuario_id = :usuario_id
            ORDER BY g.data_criacao DESC
            LIMIT 3";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca conexões do usuário
 */
function buscarConexoesUsuario($pdo, $usuarioId) {
    $sql = "SELECT u.idusuario, u.nome, u.matricula
            FROM usuario u
            JOIN usuario_seguidores us ON u.idusuario = us.seguido_id
            WHERE us.seguidor_id = :usuario_id
            ORDER BY us.data_seguimento DESC
            LIMIT 1000";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarConectadosAoUsuario($pdo, $usuarioId) {
    $sql = "SELECT u.idusuario, u.nome, u.matricula
            FROM usuario u
            JOIN usuario_seguidores us ON u.idusuario = us.seguidor_id
            WHERE us.seguido_id = :usuario_id
            ORDER BY us.data_seguimento DESC
            LIMIT 1000";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca atividades recentes do usuário
 */
function buscarAtividadesUsuario($pdo, $usuarioId) {
    $atividades = [];
    
    // 1. Último seguidor
    $sql = "SELECT u.nome, us.data_seguimento 
            FROM usuario_seguidores us
            JOIN usuario u ON us.seguidor_id = u.idusuario
            WHERE us.seguido_id = :usuario_id
            ORDER BY us.data_seguimento DESC
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $ultimaConexao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ultimaConexao) {
        $atividades[] = [
            'titulo' => 'Nova Conexão',
            'descricao' => $ultimaConexao['nome'] . ' começou a seguir você',
            'tipo' => 'match',
            'data' => $ultimaConexao['data_seguimento']
        ];
    }
    
    // 2. Último grupo ingressado
    $sql = "SELECT g.nome, gm.data_ingresso
            FROM grupo_membros gm
            JOIN grupos g ON gm.grupo_id = g.id
            WHERE gm.usuario_id = :usuario_id
            ORDER BY gm.data_ingresso DESC
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $ultimoGrupo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ultimoGrupo) {
        $atividades[] = [
            'titulo' => 'Entrou em Grupo',
            'descricao' => 'Você ingressou em: ' . $ultimoGrupo['nome'],
            'tipo' => 'grupo',
            'data' => $ultimoGrupo['data_ingresso']
        ];
    }
    
    // 3. Último evento criado
    $sql = "SELECT titulo, data_evento 
            FROM eventos 
            WHERE criador_id = :usuario_id
            ORDER BY data_evento DESC 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $ultimoEvento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ultimoEvento) {
        $atividades[] = [
            'titulo' => 'Evento Criado',
            'descricao' => 'Você criou: ' . $ultimoEvento['titulo'],
            'tipo' => 'evento',
            'data' => $ultimoEvento['data_evento'] . ' 00:00:00'
        ];
    }
    
    // 4. Último material publicado
    $sql = "SELECT titulo, data_publicacao 
            FROM materiais 
            WHERE usuario_id = :usuario_id
            ORDER BY data_publicacao DESC 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $ultimoMaterial = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ultimoMaterial) {
        $atividades[] = [
            'titulo' => 'Material Publicado',
            'descricao' => 'Você publicou: ' . $ultimoMaterial['titulo'],
            'tipo' => 'material',
            'data' => $ultimoMaterial['data_publicacao']
        ];
    }
    
    return $atividades;
}