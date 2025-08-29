<?php
// functions/hub_functions.php
// Funções para uso em inicio.php
// Presupõe que você injete um PDO ($pdo) vindo de includes/conexao.php
// Se seu conexao.php usar outra variável, ajuste ao chamar as funções.

if (!function_exists('timeAgo')) {
    /**
     * Retorna string curta tipo '5m','1h','2d' a partir de uma datetime
     */
    function timeAgo(string $datetime): string {
        $dt = new DateTime($datetime);
        $now = new DateTime();
        $diff = $now->getTimestamp() - $dt->getTimestamp();

        if ($diff < 60) return $diff . 's';
        if ($diff < 3600) return floor($diff/60) . 'm';
        if ($diff < 86400) return floor($diff/3600) . 'h';
        if ($diff < 2592000) return floor($diff/86400) . 'd';
        return $dt->format('d/m/Y');
    }
}

if (!function_exists('getUpcomingEvents')) {
    /**
     * @return array Lista de eventos futuros (mapeado para uso no frontend)
     */
    function getUpcomingEvents(PDO $pdo, int $limit = 8): array {
        $out = [];
        try {
            $sql = "SELECT e.*, u.nome AS criador_nome
                    FROM eventos e
                    JOIN usuario u ON e.criador_id = u.idusuario
                    WHERE e.data_evento >= CURDATE()
                    ORDER BY e.data_evento, e.hora_evento
                    LIMIT :lim";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dateLabel = date('d/m/Y', strtotime($r['data_evento']));
                if ($r['data_evento'] === date('Y-m-d')) {
                    $dateLabel = 'Hoje, ' . substr($r['hora_evento'], 0, 5);
                } else {
                    $dateLabel .= ' • ' . substr($r['hora_evento'], 0, 5);
                }

                // prioridade simples
                $priority = 'low';
                if ($r['tipo'] === 'workshop') $priority = 'high';
                elseif ($r['tipo'] === 'palestra') $priority = 'medium';

                $out[] = [
                    'id' => (int)$r['id'],
                    'title' => $r['titulo'],
                    'subject' => $r['criador_nome'],
                    'date' => $dateLabel,
                    'type' => $r['tipo'],
                    'priority' => $priority,
                ];
            }
        } catch (Exception $e) {
            error_log('getUpcomingEvents: '.$e->getMessage());
        }
        return $out;
    }
}

if (!function_exists('getActiveMatches')) {
    /**
     * Retorna conversas recentes do usuário com última mensagem e contador de não lidas
     * @param PDO $pdo
     * @param int $userId
     * @param int $limit
     * @return array
     */
    function getActiveMatches(PDO $pdo, int $userId, int $limit = 8): array {
        $out = [];
        try {
            $sql = "SELECT c.id,
                           CASE WHEN c.usuario1_id = :uid THEN c.usuario2_id ELSE c.usuario1_id END AS other_id,
                           c.data_criacao
                    FROM conversas c
                    WHERE c.usuario1_id = :uid OR c.usuario2_id = :uid
                    ORDER BY c.data_criacao DESC
                    LIMIT :lim";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $convs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($convs as $c) {
                $otherId = (int)$c['other_id'];

                // dados do outro usuário
                $uStmt = $pdo->prepare("SELECT idusuario, nome, foto FROM usuario WHERE idusuario = :oid LIMIT 1");
                $uStmt->execute([':oid' => $otherId]);
                $u = $uStmt->fetch(PDO::FETCH_ASSOC);

                // última mensagem
                $mStmt = $pdo->prepare("SELECT conteudo, data_envio, remetente_id, lida FROM mensagens WHERE conversa_id = :cid ORDER BY data_envio DESC LIMIT 1");
                $mStmt->execute([':cid' => $c['id']]);
                $m = $mStmt->fetch(PDO::FETCH_ASSOC);

                // não lidas
                $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM mensagens WHERE conversa_id = :cid AND remetente_id <> :uid AND lida = 0");
                $unreadStmt->execute([':cid' => $c['id'], ':uid' => $userId]);
                $unread = (int)$unreadStmt->fetchColumn();

                $out[] = [
                    'id' => (int)$c['id'],
                    'name' => $u ? $u['nome'] : 'Usuário',
                    'username' => $u ? 'u'.$u['idusuario'] : 'u'.$otherId,
                    'avatar' => ($u && $u['foto']) ? $u['foto'] : 'https://i.pravatar.cc/150?u='.$otherId,
                    'status' => 'offline', // se quiser, pode buscar em usuario_privacidade.status_online
                    'lastMessage' => $m ? $m['conteudo'] : '',
                    'time' => $m ? timeAgo($m['data_envio']) : '',
                    'unread' => $unread,
                ];
            }
        } catch (Exception $e) {
            error_log('getActiveMatches: '.$e->getMessage());
        }
        return $out;
    }
}

if (!function_exists('getGroupPosts')) {
    /**
     * Retorna últimas mensagens de grupos para a timeline/destaques
     */
    function getGroupPosts(PDO $pdo, int $limit = 8): array {
        $out = [];
        try {
            $sql = "SELECT mg.*, g.nome AS grupo_nome, u.nome AS remetente_nome, u.foto AS remetente_foto
                    FROM mensagens_grupo mg
                    JOIN grupos g ON mg.grupo_id = g.id
                    JOIN usuario u ON mg.remetente_id = u.idusuario
                    ORDER BY mg.data_envio DESC
                    LIMIT :lim";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $out[] = [
                    'id' => (int)$r['id'],
                    'user' => [
                        'name' => $r['grupo_nome'],
                        'username' => 'grupo' . $r['grupo_id'],
                        'avatar' => $r['remetente_foto'] ? $r['remetente_foto'] : 'https://i.pravatar.cc/150?u=grp'.$r['grupo_id'],
                        'isGroup' => true,
                    ],
                    'content' => $r['conteudo'],
                    'time' => (new DateTime($r['data_envio']))->format('d/m H:i'),
                    'likes' => rand(0, 50),      // sem tabela de likes no dump — placeholder
                    'comments' => rand(0, 20),   // placeholder
                    'liked' => false,
                    'isAcademic' => false,
                ];
            }
        } catch (Exception $e) {
            error_log('getGroupPosts: '.$e->getMessage());
        }
        return $out;
    }
}

if (!function_exists('getPendingConnections')) {
    /**
     * Usuários que seguem $userId mas que o $userId ainda não segue de volta
     */
    function getPendingConnections(PDO $pdo, int $userId, int $limit = 6): array {
        $out = [];
        try {
            $sql = "SELECT s.seguidor_id, u.nome, u.foto, u.email
                    FROM usuario_seguidores s
                    JOIN usuario u ON s.seguidor_id = u.idusuario
                    WHERE s.seguido_id = :uid
                      AND NOT EXISTS(
                        SELECT 1 FROM usuario_seguidores s2
                        WHERE s2.seguido_id = s.seguidor_id AND s2.seguidor_id = :uid
                      )
                    ORDER BY s.data_seguimento DESC
                    LIMIT :lim";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $out[] = [
                    'id' => (int)$r['seguidor_id'],
                    'name' => $r['nome'],
                    'username' => 'u'.$r['seguidor_id'],
                    'avatar' => $r['foto'] ? $r['foto'] : 'https://i.pravatar.cc/150?u='.$r['seguidor_id'],
                    'bio' => $r['email'],
                    'mutualInterests' => [], // você pode estender aqui com tags reais
                ];
            }
        } catch (Exception $e) {
            error_log('getPendingConnections: '.$e->getMessage());
        }
        return $out;
    }
}
