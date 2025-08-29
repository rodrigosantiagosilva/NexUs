<?php
require 'includes/conexao.php';




/**
 * Busca os grupos do usuário atual
 */
function buscarMeusGrupos($pdo, $usuarioId) {
    $sql = "SELECT g.id, g.nome, g.descricao, g.criador_id, 
                   COUNT(gm.usuario_id) AS total_membros,
                   MAX(mg.data_envio) AS ultima_mensagem_data
            FROM grupos g
            JOIN grupo_membros gm ON gm.grupo_id = g.id
            LEFT JOIN mensagens_grupo mg ON mg.grupo_id = g.id
            WHERE gm.usuario_id = :usuario_id
            GROUP BY g.id
            ORDER BY ultima_mensagem_data DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Busca todos os grupos disponíveis
 */
function buscarTodosGrupos($pdo) {
    $sql = "SELECT g.id, g.nome, g.descricao, 
                   COUNT(gm.usuario_id) AS total_membros
            FROM grupos g
            LEFT JOIN grupo_membros gm ON gm.grupo_id = g.id
            GROUP BY g.id
            ORDER BY g.data_criacao DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca eventos
 */
function buscarEventos($pdo) {
    $sql = "SELECT e.*, u.nome AS organizador_nome 
            FROM eventos e
            JOIN usuario u ON e.criador_id = u.idusuario
            WHERE data_evento >= CURDATE()
            ORDER BY e.data_evento ASC, e.hora_evento ASC";
    
    $stmt = $pdo->prepare($sql);
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
    $sql = "SELECT COUNT(*) FROM grupo_membros 
            WHERE grupo_id = :grupo_id 
            AND usuario_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':grupo_id', $grupoId, PDO::PARAM_INT);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
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

/**
 * Criar um novo grupo
 */
function criarGrupo($pdo, $nome, $descricao, $criadorId) {
    $sql = "INSERT INTO grupos (nome, descricao, criador_id) 
            VALUES (:nome, :descricao, :criador_id)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':nome', $nome);
    $stmt->bindValue(':descricao', $descricao);
    $stmt->bindValue(':criador_id', $criadorId, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        return $pdo->lastInsertId();
    }
    
    return false;
}