<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require 'includes/conexao.php';

// Verifica se o usuário está logado
$usuarioAtualId = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;


// Funções para comunidades
require 'functions/comunidades_functions.php';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'participar_grupo':
                if (isset($_POST['grupo_id']) && $usuarioAtualId) {
                    $result = participarGrupo($pdo, $_POST['grupo_id'], $usuarioAtualId);
                    $msg = $result ? "Você entrou no grupo!" : "Erro ao entrar no grupo";
                }
                break;

            case 'participar_evento':
                if (isset($_POST['evento_id']) && $usuarioAtualId) {
                    $result = participarEvento($pdo, $_POST['evento_id'], $usuarioAtualId);
                    $msg = $result ? "Você confirmou presença no evento!" : "Erro ao confirmar presença";
                }
                break;

            case 'criar_grupo':
                if ($usuarioAtualId) {
                    $nome = $_POST['nome'];
                    $descricao = $_POST['descricao'];
                    $result = criarGrupo($pdo, $nome, $descricao, $usuarioAtualId);
                    if ($result) {
                        $msg = "Grupo criado com sucesso!";
                        // Adiciona o criador como membro do grupo
                        participarGrupo($pdo, $result, $usuarioAtualId);
                    } else {
                        $msg = "Erro ao criar o grupo";
                    }
                }
                break;
        }
    }
}

// Determinar aba ativa
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'descobrir';
$valid_tabs = ['meus_grupos', 'descobrir', 'eventos'];
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'descobrir';
}

// Buscar dados do banco
$meusGrupos = $usuarioAtualId ? buscarMeusGrupos($pdo, $usuarioAtualId) : [];
$todosGrupos = buscarTodosGrupos($pdo);
$eventos = buscarEventos($pdo);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidades - TydraPI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            position: fixed;
        }
    </style>
    <?php include 'includes/header.php' ?>
</head>

<body>
    <?php include 'includes/sidebar.php' ?>
    <div class="content-wrapper">
        <div class="communities-header">
            <h1 class="communities-title">Comunidades</h1>
            <div class="d-flex align-items-center">
                <div class="search-container search-container-comunition">
                    <input type="text" class="search-input" placeholder="Buscar comunidades...">
                    <span class="search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                        </svg>
                    </span>
                </div>
                <button class="create-btn" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z" />
                    </svg>
                    Criar Grupo
                </button>
            </div>
        </div>


        <div class="nav-tabs">
            <div class="nav-tab <?= $active_tab === 'meus_grupos' ? 'active' : '' ?>" data-tab="meus_grupos">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816zM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275zM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
                </svg>
                Meus Grupos
            </div>
            <div class="nav-tab <?= $active_tab === 'descobrir' ? 'active' : '' ?>" data-tab="descobrir">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 16.016a7.5 7.5 0 0 0 1.962-14.74A1 1 0 0 0 9 0H7a1 1 0 0 0-.962 1.276A7.5 7.5 0 0 0 8 16.016zm6.5-7.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0z" />
                    <path d="m6.94 7.44 4.95-2.83-2.83 4.95-4.949 2.83 2.828-4.95z" />
                </svg>
                Descobrir
            </div>
            <!-- implementação futura
            
            <div class="nav-tab <?= $active_tab === 'eventos' ? 'active' : '' ?>" data-tab="eventos">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z"/>
                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                </svg>
                Eventos
            </div>
              -->

        </div>

        <!-- Conteúdo da aba Descobrir -->
        <div id="discover-content" style="<?= $active_tab === 'descobrir' ? 'display: block;' : 'display: none;' ?>">
            <?php foreach ($todosGrupos as $grupo): ?>
                <div class="group-card">
                    <div class="group-header">
                        <div class="group-avatar">
                            <?php
                            // Gerar identicon baseado no nome do grupo
                            $hash = md5($grupo['nome']);
                            $color = substr($hash, 0, 6);
                            ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($grupo['nome']) ?>&background=<?= $color ?>&color=fff&size=128" alt="<?= htmlspecialchars($grupo['nome']) ?>">
                        </div>
                        <div class="group-info">
                            <h3 class="group-title"><?= htmlspecialchars($grupo['nome']) ?></h3>
                            <div class="group-meta"><?= $grupo['total_membros'] ?> membros</div>
                        </div>
                    </div>

                    <p class="group-description">
                        <?= htmlspecialchars($grupo['descricao']) ?>
                    </p>

                    <div class="tag-container">
                        <?php
                        $tags = [];
                        if (stripos($grupo['descricao'], 'banco de dados') !== false) $tags[] = 'Banco de Dados';
                        if (stripos($grupo['descricao'], 'IA') !== false || stripos($grupo['descricao'], 'inteligência artificial') !== false) $tags[] = 'IA';
                        if (stripos($grupo['descricao'], 'front-end') !== false) $tags[] = 'Frontend';
                        if (stripos($grupo['descricao'], 'machine learning') !== false) $tags[] = 'Machine Learning';
                        if (stripos($grupo['descricao'], 'tecnologia') !== false) $tags[] = 'Tecnologia';

                        foreach ($tags as $tag): ?>
                            <span class="tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($usuarioAtualId): ?>
                        <?php if (estaNoGrupo($pdo, $grupo['id'], $usuarioAtualId)): ?>
                            <button class="join-btn" disabled>Já participa</button>
                        <?php else: ?>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="participar_grupo">
                                <input type="hidden" name="grupo_id" value="<?= $grupo['id'] ?>">
                                <button type="submit" class="join-btn">Participar</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="join-btn" disabled>Faça login para participar</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Eventos -->
        <div id="events-content" style="<?= $active_tab === 'eventos' ? 'display: block;' : 'display: none;' ?>">
            <div class="events-grid-container">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($eventos as $evento):
                        $badgeColors = ['purple', 'orange', 'red', 'blue', 'green'];
                        $badgeColor = $badgeColors[array_rand($badgeColors)];
                        $badgeTypes = ['Workshop', 'Estudo em Grupo', 'Debate', 'Palestra', 'Hackathon'];
                        $badgeType = $badgeTypes[array_rand($badgeTypes)];
                    ?>
                        <div class="col events-grid-item">
                            <div class="event-card h-100">
                                <div class="event-header">
                                    <div class="event-icon icon-square" style="background-color: var(--<?= $badgeColor ?>-badge) !important;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z" />
                                        </svg>
                                    </div>
                                    <div class="group-info">
                                        <h3 class="group-title"><?= htmlspecialchars($evento['titulo']) ?></h3>
                                        <div class="event-badges">
                                            <span class="badge badge-<?= $badgeColor ?>"><?= $badgeType ?></span>
                                            <span class="event-date">
                                                <?= date('d M, Y', strtotime($evento['data_evento'])) ?> •
                                                <?= date('H:i', strtotime($evento['hora_evento'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <p class="group-description">
                                    <?= htmlspecialchars($evento['descricao']) ?>
                                </p>

                                <div class="group-tags">
                                    <?php
                                    // Tags fictícias baseadas na descrição
                                    $tags = [];
                                    if (stripos($evento['descricao'], 'workshop') !== false) $tags[] = 'Workshop';
                                    if (stripos($evento['descricao'], 'estudo') !== false) $tags[] = 'Estudo';
                                    if (stripos($evento['descricao'], 'debate') !== false) $tags[] = 'Debate';
                                    if (stripos($evento['descricao'], 'tecnologia') !== false) $tags[] = 'Tecnologia';
                                    if (stripos($evento['descricao'], 'educação') !== false) $tags[] = 'Educação';

                                    foreach ($tags as $tag): ?>
                                        <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                    <?php endforeach; ?>
                                </div>

                                <div class="progress-bar">
                                    <div class="progress-fill <?= $badgeColor ?>"></div>
                                </div>
                                <div class="participants-count"><?= rand(5, 30) ?>/<?= rand(30, 50) ?> participantes</div>

                                <?php if ($usuarioAtualId): ?>
                                    <?php if (estaNoEvento($pdo, $evento['id'], $usuarioAtualId)): ?>
                                        <button class="join-btn mt-3" disabled>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="calendar-icon" viewBox="0 0 16 16">
                                                <path d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7z" />
                                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z" />
                                            </svg>
                                            Participando
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="participar_evento">
                                            <input type="hidden" name="evento_id" value="<?= $evento['id'] ?>">
                                            <button type="submit" class="join-btn mt-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="calendar-icon" viewBox="0 0 16 16">
                                                    <path d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7z" />
                                                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z" />
                                                </svg>
                                                Participar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="join-btn mt-3" disabled>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="calendar-icon" viewBox="0 0 16 16">
                                            <path d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7z" />
                                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z" />
                                        </svg>
                                        Faça login
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Meus Grupos -->
        <div id="my-groups-content" style="<?= $active_tab === 'meus_grupos' ? 'display: block;' : 'display: none;' ?>">
            <?php if (empty($meusGrupos)): ?>
                <div class="alert alert-info">
                    Você ainda não participa de nenhum grupo. Explore a aba "Descobrir" para encontrar comunidades interessantes.
                </div>
            <?php else: ?>
                <?php foreach ($meusGrupos as $grupo): ?>
                    <div class="group-card">
                        <div class="group-header">
                            <div class="group-avatar">
                                <?php
                                $hash = md5($grupo['nome']);
                                $color = substr($hash, 0, 6);
                                ?>
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($grupo['nome']) ?>&background=<?= $color ?>&color=fff&size=128" alt="<?= htmlspecialchars($grupo['nome']) ?>">
                            </div>
                            <div class="group-info">
                                <h3 class="group-title"><?= htmlspecialchars($grupo['nome']) ?></h3>
                                <div class="group-meta">
                                    <?php if ($grupo['criador_id'] == $usuarioAtualId): ?>
                                        <span class="admin-badge">Admin</span>
                                    <?php else: ?>
                                        <span class="member-badge">Membro</span>
                                    <?php endif; ?>
                                    <?= $grupo['total_membros'] ?> membros

                                    <?php
                                    // Calcular mensagens não lidas
                                    $sqlNaoLidas = "SELECT COUNT(*) AS nao_lidas
                                            FROM mensagens_grupo mg
                                            WHERE mg.grupo_id = :grupo_id
                                            AND mg.id > COALESCE(
                                                (SELECT ultima_visualizacao 
                                                 FROM grupo_visualizacoes 
                                                 WHERE grupo_id = :grupo_id 
                                                 AND usuario_id = :usuario_id), 0)";
                                    $stmt = $pdo->prepare($sqlNaoLidas);
                                    $stmt->bindValue(':grupo_id', $grupo['id'], PDO::PARAM_INT);
                                    $stmt->bindValue(':usuario_id', $usuarioAtualId, PDO::PARAM_INT);
                                    $stmt->execute();
                                    $naoLidas = $stmt->fetch(PDO::FETCH_ASSOC)['nao_lidas'];

                                    if ($naoLidas > 0): ?>
                                        <span class="notification-badge"><?= $naoLidas ?> novos</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="group-actions">
                                <button class="action-btn">
                                    <a href="chat.php?grupo_id=<?= $grupo['id'] ?>" style="color: white;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6-.097 1.016-.417 2.13-.771 2.966-.079.186.074.394.273.362 2.256-.37 3.597-.938 4.18-1.234A9.06 9.06 0 0 0 8 15z" />
                                        </svg>
                                    </a>
                                </button>
                            </div>
                        </div>

                        <?php if (!empty($grupo['ultima_mensagem_data'])): ?>
                            <?php
                            $now = new DateTime();
                            $lastMsg = new DateTime($grupo['ultima_mensagem_data']);
                            $interval = $now->diff($lastMsg);

                            $activeTime = "Ativo: ";
                            if ($interval->y > 0) {
                                $activeTime .= $interval->y . " ano" . ($interval->y > 1 ? "s" : "") . " atrás";
                            } elseif ($interval->m > 0) {
                                $activeTime .= $interval->m . " mês" . ($interval->m > 1 ? "es" : "") . " atrás";
                            } elseif ($interval->d > 0) {
                                $activeTime .= $interval->d . " dia" . ($interval->d > 1 ? "s" : "") . " atrás";
                            } elseif ($interval->h > 0) {
                                $activeTime .= $interval->h . " hora" . ($interval->h > 1 ? "s" : "") . " atrás";
                            } elseif ($interval->i > 0) {
                                $activeTime .= $interval->i . " minuto" . ($interval->i > 1 ? "s" : "") . " atrás";
                            } else {
                                $activeTime = "Ativo agora mesmo";
                            }
                            ?>
                            <p class="active-time"><?= $activeTime ?></p>
                        <?php else: ?>
                            <p class="active-time">Sem atividade recente</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para criar grupo -->
    <div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createGroupModalLabel">Criar Novo Grupo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label" for="groupName">Nome do Grupo</label>
                            <input type="text" class="form-control" id="groupName" name="nome" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="groupDescription">Descrição</label>
                            <textarea class="form-control" id="groupDescription" name="descricao" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="action" value="criar_grupo">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar Grupo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navegação entre abas
            const tabs = document.querySelectorAll('.nav-tab');
            const discoverContent = document.getElementById('discover-content');
            const myGroupsContent = document.getElementById('my-groups-content');
            const eventsContent = document.getElementById('events-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');

                    // Atualiza a aba ativa
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    // Mostra o conteúdo correspondente
                    discoverContent.style.display = tabName === 'descobrir' ? 'block' : 'none';
                    myGroupsContent.style.display = tabName === 'meus_grupos' ? 'block' : 'none';
                    eventsContent.style.display = tabName === 'eventos' ? 'block' : 'none';

                    // Atualiza a URL sem recarregar a página
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabName);
                    window.history.replaceState({}, '', url);
                });
            });

            // Botão de participar
            const joinButtons = document.querySelectorAll('.join-btn:not(:disabled)');
            joinButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.disabled) return;

                    const originalText = this.textContent;

                    // Feedback visual
                    this.textContent = 'Processando...';
                    this.disabled = true;

                    // Se for um formulário, submete automaticamente
                    if (this.closest('form')) {
                        this.closest('form').submit();
                    }
                });
            });
        });
    </script>
</body>

</html>