<?php
require 'includes/conexao.php';

/**
 * Busca conversas do usuário (apenas conversas privadas)
 */
function buscarConversasUsuario($pdo, $usuarioId) {
    $sql = "SELECT c.id, 
                   CASE 
                     WHEN c.usuario1_id = :usuario_id THEN u2.idusuario
                     ELSE u1.idusuario
                   END AS outro_usuario_id,
                   CASE 
                     WHEN c.usuario1_id = :usuario_id THEN u2.nome
                     ELSE u1.nome
                   END AS nome,
                   m.conteudo AS ultima_mensagem,
                   m.data_envio AS ultima_mensagem_data,
                   SUM(CASE WHEN m.lida = 0 AND m.remetente_id != :usuario_id THEN 1 ELSE 0 END) AS nao_lidas,
                   'privada' AS tipo  -- Adicione este campo
            FROM conversas c
            JOIN usuario u1 ON u1.idusuario = c.usuario1_id
            JOIN usuario u2 ON u2.idusuario = c.usuario2_id
            LEFT JOIN (
                SELECT conversa_id, MAX(id) AS max_id
                FROM mensagens
                GROUP BY conversa_id
            ) ultima ON ultima.conversa_id = c.id
            LEFT JOIN mensagens m ON m.id = ultima.max_id
            WHERE c.usuario1_id = :usuario_id OR c.usuario2_id = :usuario_id
            GROUP BY c.id
            ORDER BY m.data_envio DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Busca grupos do usuário
 */
function buscarGruposUsuario($pdo, $usuarioId) {
    $sql = "SELECT g.id, g.nome, 
                   mg.conteudo AS ultima_mensagem,
                   mg.data_envio AS ultima_mensagem_data,
                   (SELECT COUNT(*) FROM mensagens_grupo mg2 
                    WHERE mg2.grupo_id = g.id 
                    AND mg2.id > COALESCE((SELECT ultima_visualizacao 
                                          FROM grupo_visualizacoes 
                                          WHERE grupo_id = g.id 
                                          AND usuario_id = :usuario_id), 0)) AS nao_lidas,
                   'grupo' AS tipo  -- Adicione este campo
            FROM grupos g
            JOIN grupo_membros gm ON gm.grupo_id = g.id
            LEFT JOIN (
                SELECT grupo_id, MAX(id) AS max_id
                FROM mensagens_grupo
                GROUP BY grupo_id
            ) ultima ON ultima.grupo_id = g.id
            LEFT JOIN mensagens_grupo mg ON mg.id = ultima.max_id
            WHERE gm.usuario_id = :usuario_id
            ORDER BY mg.data_envio DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca mensagens de uma conversa privada
 */
function buscarMensagensConversa($pdo, $conversaId, $usuarioId) {
    // Primeiro marcamos as mensagens como lidas
    $sqlUpdate = "UPDATE mensagens SET lida = TRUE 
                  WHERE conversa_id = :conversa_id 
                  AND remetente_id != :usuario_id";
    
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->bindValue(':conversa_id', $conversaId, PDO::PARAM_INT);
    $stmtUpdate->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmtUpdate->execute();

    // Buscamos as mensagens
    $sql = "SELECT m.*, u.nome AS remetente_nome
            FROM mensagens m
            JOIN usuario u ON u.idusuario = m.remetente_id
            WHERE m.conversa_id = :conversa_id
            ORDER BY m.data_envio ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':conversa_id', $conversaId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Busca mensagens de um grupo
 */
function buscarMensagensGrupo($pdo, $grupoId, $usuarioId) {
    // Atualiza a última visualização do usuário
    $sqlUpdate = "INSERT INTO grupo_visualizacoes (grupo_id, usuario_id, ultima_visualizacao)
                  VALUES (:grupo_id, :usuario_id, (SELECT COALESCE(MAX(id), 0) FROM mensagens_grupo WHERE grupo_id = :grupo_id))
                  ON DUPLICATE KEY UPDATE ultima_visualizacao = VALUES(ultima_visualizacao)";
    
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->bindValue(':grupo_id', $grupoId, PDO::PARAM_INT);
    $stmtUpdate->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmtUpdate->execute();

    // Busca as mensagens
    $sql = "SELECT mg.*, u.nome AS remetente_nome
            FROM mensagens_grupo mg
            JOIN usuario u ON u.idusuario = mg.remetente_id
            WHERE mg.grupo_id = :grupo_id
            ORDER BY mg.data_envio ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':grupo_id', $grupoId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Envia mensagem para conversa privada
 */
function enviarMensagem($pdo, $conversaId, $remetenteId, $conteudo) {
    $sql = "INSERT INTO mensagens (conversa_id, remetente_id, conteudo)
            VALUES (:conversa_id, :remetente_id, :conteudo)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':conversa_id', $conversaId, PDO::PARAM_INT);
    $stmt->bindValue(':remetente_id', $remetenteId, PDO::PARAM_INT);
    $stmt->bindValue(':conteudo', $conteudo, PDO::PARAM_STR);
    
    return $stmt->execute();
}

/**
 * Envia mensagem para grupo
 */
function enviarMensagemGrupo($pdo, $grupoId, $remetenteId, $conteudo) {
    $sql = "INSERT INTO mensagens_grupo (grupo_id, remetente_id, conteudo)
            VALUES (:grupo_id, :remetente_id, :conteudo)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':grupo_id', $grupoId, PDO::PARAM_INT);
    $stmt->bindValue(':remetente_id', $remetenteId, PDO::PARAM_INT);
    $stmt->bindValue(':conteudo', $conteudo, PDO::PARAM_STR);
    
    return $stmt->execute();
}

/**
 * Cria ou busca conversa privada
 */
function criarOuBuscarConversa($pdo, $usuario1Id, $usuario2Id) {
    // Verifica se já existe conversa
    $sql = "SELECT id FROM conversas 
            WHERE (usuario1_id = :usuario1_id AND usuario2_id = :usuario2_id)
            OR (usuario1_id = :usuario2_id AND usuario2_id = :usuario1_id)
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario1_id', $usuario1Id, PDO::PARAM_INT);
    $stmt->bindValue(':usuario2_id', $usuario2Id, PDO::PARAM_INT);
    $stmt->execute();
    
    $conversa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conversa) {
        return $conversa['id'];
    }
    
    // Cria nova conversa
    $sqlInsert = "INSERT INTO conversas (usuario1_id, usuario2_id) 
                  VALUES (:usuario1_id, :usuario2_id)";
    
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':usuario1_id', $usuario1Id, PDO::PARAM_INT);
    $stmtInsert->bindValue(':usuario2_id', $usuario2Id, PDO::PARAM_INT);
    $stmtInsert->execute();
    
    return $pdo->lastInsertId();
}

/**
 * Busca o outro usuário em uma conversa privada
 */
function buscarOutroUsuarioConversa($pdo, $conversaId, $usuarioAtualId) {
    $sql = "SELECT u.idusuario, u.nome, u.email
            FROM conversas c
            JOIN usuario u ON u.idusuario = CASE 
                WHEN c.usuario1_id = :usuario_id THEN c.usuario2_id
                ELSE c.usuario1_id
            END
            WHERE c.id = :conversa_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':conversa_id', $conversaId, PDO::PARAM_INT);
    $stmt->bindValue(':usuario_id', $usuarioAtualId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Busca informações de um grupo
 */
function buscarGrupo($pdo, $grupoId) {
    $sql = "SELECT * FROM grupos WHERE id = :grupo_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':grupo_id', $grupoId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Busca membros de um grupo
 */
function buscarMembrosGrupo($pdo, $grupoId) {
    $sql = "SELECT u.idusuario, u.nome
            FROM grupo_membros gm
            JOIN usuario u ON u.idusuario = gm.usuario_id
            WHERE gm.grupo_id = :grupo_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':grupo_id', $grupoId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Verifica se usuário é membro do grupo
 */
function usuarioEhMembro($pdo, $grupoId, $usuarioId) {
    $sql = "SELECT COUNT(*) FROM grupo_membros 
            WHERE grupo_id = :grupo_id 
            AND usuario_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':grupo_id', $grupoId, PDO::PARAM_INT);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}