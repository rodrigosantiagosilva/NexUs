<?php
// File: hub.php
// Tela principal (hub) conectada ao banco, usa functions/hub_functions.php
session_start();

// Ajuste o caminho se necessário
include "functions/hub_functions.php";
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

if (!function_exists('getUserRegisteredEvents')) {
    /**
     * Retorna os últimos eventos em que o usuário está inscrito
     * @param PDO $pdo
     * @param int $userId
     * @param int $limit
     * @return array
     */
    function getUserRegisteredEvents(PDO $pdo, int $userId, int $limit = 3): array {
        $out = [];
        try {
            $sql = "SELECT e.*, u.nome AS criador_nome, ep.data_confirmacao
                    FROM eventos e
                    JOIN evento_participantes ep ON e.id = ep.evento_id
                    JOIN usuario u ON e.criador_id = u.idusuario
                    WHERE ep.usuario_id = :uid
                    ORDER BY ep.data_confirmacao DESC
                    LIMIT :lim";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
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
                    'registration_date' => $r['data_confirmacao']
                ];
            }
        } catch (Exception $e) {
            error_log('getUserRegisteredEvents: '.$e->getMessage());
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

if (!function_exists('getLastFollowedUsers')) {
    /**
     * Retorna os últimos usuários seguidos pelo usuário atual
     * @param PDO $pdo
     * @param int $userId
     * @param int $limit
     * @return array
     */
    function getLastFollowedUsers(PDO $pdo, int $userId, int $limit = 3): array {
        $out = [];
        try {
            $sql = "SELECT u.idusuario, u.nome, u.foto, u.email, us.data_seguimento
                    FROM usuario_seguidores us
                    JOIN usuario u ON us.seguido_id = u.idusuario
                    WHERE us.seguidor_id = :uid
                    ORDER BY us.data_seguimento DESC
                    LIMIT :lim";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $out[] = [
                    'id' => (int)$r['idusuario'],
                    'name' => $r['nome'],
                    'username' => 'u' . $r['idusuario'],
                    'avatar' => $r['foto'] ? $r['foto'] : 'https://i.pravatar.cc/150?u=' . $r['idusuario'],
                    'bio' => $r['email'],
                    'follow_date' => $r['data_seguimento'],
                    'isUser' => true
                ];
            }
        } catch (Exception $e) {
            error_log('getLastFollowedUsers: '.$e->getMessage());
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

require 'includes/conexao.php'; // deve expor $pdo (PDO)

// Usuário logado (ajuste a variável de sessão conforme seu sistema)
$usuarioAtualId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 11;

// Função auxiliar de escape
function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
// Função para gerar avatar padrão para grupos
function gerarAvatarGrupo(string $nomeGrupo): string {
    $cor = substr(md5($nomeGrupo), 0, 6); // cor única baseada no nome
    $inicial = strtoupper(mb_substr($nomeGrupo, 0, 1)); // primeira letra
    return "https://dummyimage.com/100x100/{$cor}/ffffff&text={$inicial}";
}

// Carregar dados via funções
$upcomingEvents      = getUpcomingEvents($pdo, 8);
$userRegisteredEvents = getUserRegisteredEvents($pdo, $usuarioAtualId, 3);
$activeMatches       = getActiveMatches($pdo, $usuarioAtualId, 8);
$lastFollowedUsers   = getLastFollowedUsers($pdo, $usuarioAtualId, 3);
$posts               = getGroupPosts($pdo, 8);
$pendingConnections  = getPendingConnections($pdo, $usuarioAtualId, 6);

// Buscar quantidade de mensagens por grupo
$sql = "SELECT grupo_id, COUNT(*) as total 
        FROM mensagens_grupo 
        GROUP BY grupo_id";
$stmt = $pdo->query($sql);
$msgsPorGrupo = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); 
// $msgsPorGrupo[grupo_id] = total de mensagens
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Hub - TydraPI</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome (ícones) -->
  <script src="https://kit.fontawesome.com/a2d9b3a6d6.js" crossorigin="anonymous"></script>

  <style>
    /* Pequenos estilos para o hub */
    .border-priority-high { border-left: 4.8px solid red !important; }
    .border-priority-medium { border-left: 4.8px solid #FFC107 !important; }
    .border-priority-low { border-left: 4.8px solid greenyellow !important; }
    .card-custom { box-shadow: 0 2px 6px rgba(0,0,0,0.06); border-radius: 10px; }
    .text-secondary-custom { color: #6c757d; }
    .vh-minus-0 { min-height: 100vh; }
    .truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .badge-inscrito { background-color: #198754; }
    .badge-seguindo { background-color: #0d6efd; }
  </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="d-flex vh-minus-0">
  <?php if (file_exists('includes/sidebar.php')): ?>
    <?php include 'includes/sidebar.php'; ?>
  <?php else: ?>
    <!-- placeholder sidebar -->
    <nav class="d-none d-md-block bg-light border-end" style="width:220px;padding:1rem;">
      <h5>TydraPI</h5>
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="#">Início</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Mensagens</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Grupos</a></li>
      </ul>
    </nav>
  <?php endif; ?>

  <main class="flex-fill overflow-auto">
    <div class="container-fluid py-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Início</h1>
        <div>
          <small class="text-muted">Olá, <?= e($_SESSION['usuario_nome'] ?? 'Usuário') ?></small>
        </div>
      </div>

      <!-- Meus Eventos Inscritos -->
      <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0"><i class="fas fa-calendar-check me-2 text-secondary-custom"></i>Meus Eventos</h4>
          <a href="eventos.php" class="text-danger">Ver todos</a>
        </div>

        <div class="row g-4">
          <?php if (empty($userRegisteredEvents)): ?>
            <div class="col-12">
              <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Você ainda não se inscreveu em nenhum evento.
                <a href="eventos.php" class="alert-link">Explore os eventos disponíveis</a>.
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($userRegisteredEvents as $event):
              $borderClass = ($event['priority'] === 'high') ? 'border-priority-high' : (($event['priority'] === 'medium') ? 'border-priority-medium' : 'border-priority-low');
            ?>
              <div class="col-12 col-md-4">
                <div class="card card-custom <?= e($borderClass) ?>">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h5 class="card-title mb-1"><?= e($event['title']) ?></h5>
                      <span class="badge badge-inscrito">Inscrito</span>
                    </div>
                    <p class="card-text text-secondary-custom mb-2"><?= e($event['subject']) ?></p>
                    <p class="text-secondary-custom mb-0"><i class="fas fa-calendar-check me-1"></i><?= e($event['date']) ?></p>
                    <small class="text-muted">Inscrito em: <?= date('d/m/Y', strtotime($event['registration_date'])) ?></small>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <!-- Últimos Usuários Seguidos -->
      <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0"><i class="fas fa-user-plus me-2 text-secondary-custom"></i>Últimos Seguidos</h4>
          <a href="conexoes.php" class="text-danger">Ver todos</a>
        </div>

        <div class="row g-4">
          <?php if (empty($lastFollowedUsers)): ?>
            <div class="col-12">
              <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Você ainda não seguiu nenhum usuário.
                <a href="descobrir.php" class="alert-link">Encontre novos usuários</a>.
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($lastFollowedUsers as $user): ?>
              <div class="col-12 col-md-4">
                <div class="card card-custom">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                      <img src="<?= e($user['avatar']) ?>" class="rounded-circle me-3" width="50" height="50" alt="avatar">
                      <div>
                        <h6 class="mb-0"><?= e($user['name']) ?></h6>
                        <small class="text-secondary-custom">@<?= e($user['username']) ?></small>
                      </div>
                    </div>
                    <p class="text-secondary-custom mb-2 truncate-2"><?= e($user['bio']) ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="badge badge-seguindo">Seguindo</span>
                      <small class="text-muted">Desde: <?= date('d/m/Y', strtotime($user['follow_date'])) ?></small>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <!-- Matches Ativos -->
      <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Matches Ativos</h4>
          <a href="conversas.php" class="text-danger">Ver todos</a>
        </div>

        <div class="row g-4">
          <?php if (empty($activeMatches)): ?>
            <div class="col-12"><div class="alert alert-secondary mb-0">Nenhuma conversa encontrada.</div></div>
          <?php else: ?>
            <?php foreach ($activeMatches as $match): ?>
              <div class="col-12 col-md-6">
                <a href="conversa.php?id=<?= e($match['id']) ?>" class="text-decoration-none text-dark">
                  <div class="card card-custom">
                    <div class="card-body d-flex align-items-center">
                      <div class="position-relative me-3">
                        <img src="<?= e($match['avatar']) ?>" class="rounded-circle" width="50" height="50" alt="avatar">
                        <?php if (isset($match['status']) && $match['status'] === 'online'): ?>
                          <span class="bg-success rounded-circle position-absolute" style="width:10px;height:10px;bottom:2px;right:2px;border:2px solid #fff"></span>
                        <?php endif; ?>
                      </div>
                      <div class="flex-fill">
                        <h6 class="mb-1"><?= e($match['name']) ?> <small class="text-secondary-custom"><?= e($match['time']) ?></small></h6>
                        <p class="mb-0 text-secondary-custom truncate-2"><?= e($match['lastMessage']) ?></p>
                      </div>
                      <?php if ((int)$match['unread'] > 0): ?>
                        <span class="badge bg-danger ms-3"><?= (int)$match['unread'] ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <!-- Destaques (Mensagens de Grupo) -->
      <?php
      // Lista de grupos já exibidos
      $gruposExibidos = [];
      ?>

      <!-- Destaques -->
      <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4>Grupos</h4>
          <a href="#" class="text-danger">Atualizar</a>
        </div>
        <div class="row g-3">
          <?php if (empty($posts)): ?>
            <div class="col-12"><div class="alert alert-info mb-0">Nenhuma mensagem de grupo encontrada.</div></div>
          <?php else: ?>
            <?php foreach ($posts as $post): ?>
              <?php
              // Se for grupo
              if ($post['user']['isGroup']) {
                  $nomeGrupo = $post['user']['name'];
                  $grupoId = str_replace('grupo', '', $post['user']['username']); // Extrai o ID do grupo do username

                  // Se já foi exibido, pula
                  if (in_array($nomeGrupo, $gruposExibidos)) {
                      continue;
                  }

                  // Marca como exibido
                  $gruposExibidos[] = $nomeGrupo;

                  // Gerar avatar se não existir
                  if (empty($post['user']['avatar'])) {
                      $post['user']['avatar'] = gerarAvatarGrupo($nomeGrupo);
                  }
                  
                  // Obter contagem de mensagens para este grupo
                  $totalMensagens = $msgsPorGrupo[$grupoId] ?? 0;
              }
              ?>
              <div class="col-12 col-sm-6 col-lg-4">
                <div class="card card-custom p-2 h-100" style="overflow: hidden; min-height: 180px;">
                  <div class="card-body p-2 d-flex flex-column justify-content-between">
                    
                    <!-- Cabeçalho do card -->
                    <div class="d-flex align-items-center mb-2">
                      <img src="<?= htmlspecialchars($post['user']['avatar']) ?>" class="rounded-circle me-2" width="30" height="30">
                      <div class="flex-fill">
                        <h6 class="mb-0" style="font-size: 0.9rem;"><?= htmlspecialchars($post['user']['name']) ?></h6>
                        <small class="text-secondary-custom" style="font-size: 0.75rem;">@<?= htmlspecialchars($post['user']['username']) ?> • <?= htmlspecialchars($post['time']) ?></small>
                      </div>
                      <?php if ($post['user']['isGroup']): ?>
                        <span class="badge <?= !empty($post['isAcademic'])?'bg-warning text-dark':'bg-danger' ?>" style="font-size: 0.7rem;">
                          <?= !empty($post['isAcademic'])?'Acadêmico':'Grupo' ?>
                        </span>
                      <?php endif; ?>
                    </div>

                    <!-- Conteúdo -->
                    <p class="text-secondary-custom mb-2" style="font-size: 0.85rem;"><?= htmlspecialchars($post['content']) ?></p>

                    <!-- Ações -->
                    <div class="d-flex justify-content-between align-items-center">
                      <button class="btn btn-sm btn-outline-secondary" style="font-size: 0.7rem;">
                        <i class="fas fa-comment me-1"></i><?= (int)$totalMensagens ?> mensagens
                      </button>
                      <small class="text-muted"><?= (int)$post['comments'] ?> comentários</small>
                    </div>

                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

    </div>
  </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>