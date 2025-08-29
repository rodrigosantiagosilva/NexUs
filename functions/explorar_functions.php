<?php
require 'includes/conexao.php';

/**
 * Busca grupos de estudo
 */
function buscarGrupos($pdo, $termoBusca = null, $pagina = 1, $itensPorPagina = 10) {
    $offset = ($pagina - 1) * $itensPorPagina;
    
    $sql = "SELECT g.*, u.nome AS criador_nome, 
            (SELECT COUNT(*) FROM grupo_membros WHERE grupo_id = g.id) AS total_membros
            FROM grupos g
            JOIN usuario u ON g.criador_id = u.idusuario
            WHERE 1=1";
    
    $params = [];
    
    if ($termoBusca) {
        $sql .= " AND (g.nome LIKE :termo OR g.descricao LIKE :termo)";
        $params[':termo'] = "%$termoBusca%";
    }
    
    $sql .= " ORDER BY g.data_criacao DESC LIMIT :offset, :limit";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca eventos acadêmicos
 */
function buscarEventos($pdo, $termoBusca = null, $tipo = null, $pagina = 1, $itensPorPagina = 10) {
    $offset = ($pagina - 1) * $itensPorPagina;
    
    $sql = "SELECT e.*, u.nome AS organizador_nome 
            FROM eventos e
            JOIN usuario u ON e.criador_id = u.idusuario
            WHERE 1=1";
    
    $params = [];
    
    if ($termoBusca) {
        $sql .= " AND (e.titulo LIKE :termo OR e.descricao LIKE :termo)";
        $params[':termo'] = "%$termoBusca%";
    }
    
    if ($tipo) {
        $sql .= " AND e.tipo = :tipo";
        $params[':tipo'] = $tipo;
    }
    
    $sql .= " ORDER BY e.data_evento ASC, e.hora_evento ASC LIMIT :offset, :limit";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca outros usuários
 */
function buscarUsuarios($pdo, $termoBusca = null, $usuarioAtualId, $pagina = 1, $itensPorPagina = 10) {
    $offset = ($pagina - 1) * $itensPorPagina;
    
    $sql = "SELECT u.idusuario, u.nome, u.matricula, u.email, 
            uc.bio
            FROM usuario u
            LEFT JOIN usuario_config uc ON u.idusuario = uc.usuario_id
            WHERE u.idusuario != :usuarioAtualId
            AND u.status = 'ativo'";
    
    $params = [':usuarioAtualId' => $usuarioAtualId];
    
    if ($termoBusca) {
        $sql .= " AND (u.nome LIKE :termo OR u.matricula LIKE :termo OR u.email LIKE :termo)";
        $params[':termo'] = "%$termoBusca%";
    }
    
    $sql .= " LIMIT :offset, :limit";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Participar de um grupo
 */
function participarGrupo($pdo, $grupoId, $usuarioId) {
    try {
        $sql = "INSERT INTO grupo_membros (grupo_id, usuario_id) 
                VALUES (:grupo_id, :usuario_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':grupo_id', $grupoId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        // Se for erro de duplicata (já está no grupo), retorna true
        if ($e->getCode() == 23000) {
            return true;
        }
        return false;
    }
}

/**
 * Verificar se usuário está em um grupo
 */
function estaNoGrupo($pdo, $grupoId, $usuarioId) {
    $sql = "SELECT COUNT(*) 
            FROM grupo_membros 
            WHERE grupo_id = :grupo_id AND usuario_id = :usuario_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':grupo_id', $grupoId, PDO::PARAM_INT);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Seguir um usuário
 */
function seguirUsuario($pdo, $seguidorId, $seguidoId) {
    try {
        $sql = "INSERT INTO usuario_seguidores (seguidor_id, seguido_id) 
                VALUES (:seguidor_id, :seguido_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':seguidor_id', $seguidorId, PDO::PARAM_INT);
        $stmt->bindValue(':seguido_id', $seguidoId, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        // Se for erro de duplicata (já está seguindo), retorna true
        if ($e->getCode() == 23000) {
            return true;
        }
        return false;
    }
}

/**
 * Verificar se usuário está seguindo outro
 */
function estaSeguindo($pdo, $seguidorId, $seguidoId) {
    $sql = "SELECT COUNT(*) 
            FROM usuario_seguidores 
            WHERE seguidor_id = :seguidor_id AND seguido_id = :seguido_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':seguidor_id', $seguidorId, PDO::PARAM_INT);
    $stmt->bindValue(':seguido_id', $seguidoId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Participar de um evento
 */
function participarEvento($pdo, $eventoId, $usuarioId) {
    try {
        $sql = "INSERT INTO evento_participantes (evento_id, usuario_id) 
                VALUES (:evento_id, :usuario_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':evento_id', $eventoId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        // Se for erro de duplicata (já está participando), retorna true
        if ($e->getCode() == 23000) {
            return true;
        }
        return false;
    }
}

/**
 * Verificar se usuário está em um evento
 */
function estaNoEvento($pdo, $eventoId, $usuarioId) {
    $sql = "SELECT COUNT(*) 
            FROM evento_participantes 
            WHERE evento_id = :evento_id AND usuario_id = :usuario_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':evento_id', $eventoId, PDO::PARAM_INT);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchColumn() > 0;
}