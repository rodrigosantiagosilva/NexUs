<?php
require 'includes/conexao.php';

// Inclui a conexão com o banco de dados
require_once 'includes/conexao.php';

// Função para contar mensagens não lidas
function contarMensagensNaoLidas($pdo, $usuarioId) {
    // Mensagens de conversas privadas não lidas
    $sqlConversas = "SELECT COUNT(*) as total 
                    FROM mensagens m
                    JOIN conversas c ON m.conversa_id = c.id
                    WHERE (c.usuario1_id = :usuario_id OR c.usuario2_id = :usuario_id)
                    AND m.remetente_id != :usuario_id
                    AND m.lida = 0";
    
    $stmt = $pdo->prepare($sqlConversas);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $naoLidasConversas = $stmt->fetchColumn();
    
    // Mensagens de grupos não lidas
    $sqlGrupos = "SELECT COUNT(*) as total
                 FROM mensagens_grupo mg
                 JOIN grupo_membros gm ON mg.grupo_id = gm.grupo_id
                 WHERE gm.usuario_id = :usuario_id
                 AND mg.id > COALESCE(
                     (SELECT ultima_visualizacao 
                      FROM grupo_visualizacoes 
                      WHERE grupo_id = mg.grupo_id AND usuario_id = :usuario_id), 0)";
    
    $stmt = $pdo->prepare($sqlGrupos);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $naoLidasGrupos = $stmt->fetchColumn();
    
    return $naoLidasConversas + $naoLidasGrupos;
}

?>