<?php
session_start();

// Incluir funções do CRUD para explorar
require './functions/explorar_functions.php';

// Conectar ao banco de dados
require 'includes/conexao.php';

// Obter o ID do usuário logado
$usuarioAtualId = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

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

            case 'seguir_usuario':
                if (isset($_POST['usuario_id']) && $usuarioAtualId) {
                    $result = seguirUsuario($pdo, $usuarioAtualId, $_POST['usuario_id']);
                    $msg = $result ? "Você começou a seguir este usuário!" : "Erro ao seguir usuário";
                }
                break;

            case 'participar_evento':
                if (isset($_POST['evento_id']) && $usuarioAtualId) {
                    $result = participarEvento($pdo, $_POST['evento_id'], $usuarioAtualId);
                    $msg = $result ? "Você confirmou presença no evento!" : "Erro ao confirmar presença";
                }
                break;
        }
    }
}


$termoBusca = isset($_GET['search']) ? trim($_GET['search']) : null;

$tendencias = [];
$grupos = buscarGrupos($pdo, $termoBusca, 1, 10000);
$eventos = buscarEventos($pdo, $termoBusca, null, 1, 10000);
$usuarios = buscarUsuarios($pdo, $termoBusca, $usuarioAtualId, 1, 10000);

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'tendencias';
$valid_tabs = ['tendencias', 'grupos', 'eventos', 'usuarios'];
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'tendencias';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar - TydraPI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php include 'includes/header.php'; ?>
    <style>
        .sidebar {
            width: 20%;
            position: fixed;
        }


        .content-wrapper {
            align-self: center;
            margin-left: 260px;
            padding: 20px;
            width: calc(100%);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php' ?>
    <div class="d-flex">
        <!-- Main content -->
        <div class="content-wrapper">
            <div class="container">
                <header class="header row">
                    <div class="col align-items-center p-3">
                        <h1>Explorar</h1>
                    </div>

                    <!-- Search Bar -->
                    <div class="search-container col justify-center search-container-explorar">
                        <form method="GET" action="">
                            <input type="hidden" name="tab" value="<?= $active_tab ?>">
                            <input type="text" class="search-bar" name="search" placeholder="Buscar..."
                                aria-label="Buscar" value="<?= htmlspecialchars($termoBusca ?? '') ?>">
                        </form>
                    </div>
                </header>

                <?php if (isset($msg)): ?>
                    <div class="alert alert-info"><?= $msg ?></div>
                <?php endif; ?>

                <!-- Navigation Tabs -->
                <nav class="tabs">
                    <a href="?tab=tendencias" class="tab-link <?= $active_tab === 'tendencias' ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i> Tendências
                    </a>
                                
                    <a href="?tab=eventos" class="tab-link <?= $active_tab === 'eventos' ? 'active' : '' ?>">
                        <i class="far fa-calendar-alt"></i> Eventos
                    </a>
                    <a href="?tab=usuarios" class="tab-link <?= $active_tab === 'usuarios' ? 'active' : '' ?>">
                        <i class="fas fa-user"></i> Usuários
                    </a>
                </nav>

                <main class="content">
                    <?php if ($active_tab === 'tendencias'): ?>
                        <!-- Tendências Tab Content -->
                        <div class="tendencias-container">
                            <div class="alert alert-info">
                                As tendências serão implementadas na próxima versão.
                            </div>
                        </div>

                    

                    <?php elseif ($active_tab === 'eventos'): ?>
                        <!-- Eventos Tab Content -->
                        <div class="eventos-container">
                            <?php foreach ($eventos as $evento): ?>
                                <div class="card">
                                    <div class="card-header">
                                        <div>
                                            <h2 class="card-title"><?= htmlspecialchars($evento['titulo']) ?></h2>
                                            <div class="event-date">
                                                <?= date('d M, Y', strtotime($evento['data_evento'])) ?> •
                                                <?= date('H:i', strtotime($evento['hora_evento'])) ?>
                                            </div>
                                            <div class="event-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($evento['local_evento']) ?>
                                            </div>
                                            <div class="event-organizer">
                                                Organizado por: <?= htmlspecialchars($evento['organizador_nome']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p><?= htmlspecialchars($evento['descricao']) ?></p>
                                        <div class="d-flex justify-content-end">
                                            <?php if ($usuarioAtualId): ?>
                                                <?php if (estaNoEvento($pdo, $evento['id'], $usuarioAtualId)): ?>
                                                    <button class="btn btn-following" disabled>Participando</button>
                                                <?php else: ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="action" value="participar_evento">
                                                        <input type="hidden" name="evento_id" value="<?= $evento['id'] ?>">
                                                        <button type="submit" class="btn btn-participate">Participar</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button class="btn btn-participate" disabled>Faça login</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($eventos)): ?>
                                <div class="alert alert-info">Nenhum evento encontrado.</div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($active_tab === 'usuarios'): ?>
                        <!-- Usuários Tab Content -->
                        <div class="usuarios-container">
                            <?php foreach ($usuarios as $usuario): ?>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="user-profile">
                                            <div class="avatar">
                                                <?php
                                                // Gerar avatar baseado no nome do usuário
                                                $nome = urlencode($usuario['nome']);
                                                $cor = substr(md5($usuario['nome']), 0, 6);
                                                ?>
                                                <img src="https://ui-avatars.com/api/?name=<?= $nome ?>&background=<?= $cor ?>&color=fff"
                                                    alt="<?= htmlspecialchars($usuario['nome']) ?>"
                                                    class="perfil-avatar-img">
                                            </div>
                                            <div class="user-info">
                                                <div class="user-name">
                                                    <?= htmlspecialchars($usuario['nome']) ?>
                                                </div>
                                                <div class="username">
                                                    @<?= htmlspecialchars($usuario['matricula']) ?>
                                                </div>
                                                <div class="user-bio"><?= htmlspecialchars($usuario['bio'] ?? '') ?></div>
                                            </div>
                                            <?php if ($usuarioAtualId && $usuarioAtualId != $usuario['idusuario']): ?>
                                                <?php if (estaSeguindo($pdo, $usuarioAtualId, $usuario['idusuario'])): ?>
                                                    <button class="btn btn-following">Seguindo</button>
                                                <?php else: ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="action" value="seguir_usuario">
                                                        <input type="hidden" name="usuario_id" value="<?= $usuario['idusuario'] ?>">
                                                        <button type="submit" class="btn btn-participate">Seguir</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php elseif ($usuarioAtualId == $usuario['idusuario']): ?>
                                                <button class="btn btn-outline-secondary" disabled>Você</button>
                                            <?php else: ?>
                                                <button class="btn btn-participate" disabled>Faça login</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($usuarios)): ?>
                                <div class="alert alert-info">Nenhum usuário encontrado.</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Formulário de busca
            const searchBar = document.querySelector('.search-bar');
            if (searchBar) {
                searchBar.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        this.closest('form').submit();
                    }
                });
            }

            // Botões de ação
            const actionButtons = document.querySelectorAll('.btn-participate');
            actionButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.disabled) {
                        e.preventDefault();
                        return;
                    }

                    if (this.textContent.trim() === 'Participar' ||
                        this.textContent.trim() === 'Seguir') {

                        // Mudar aparência do botão
                        const originalText = this.textContent;
                        this.textContent = originalText === 'Participar' ? 'Solicitado' : 'Seguindo';
                        this.classList.remove('btn-participate');
                        this.classList.add('btn-following');
                        this.disabled = true;

                        // Enviar o formulário após um pequeno delay para dar feedback visual
                        setTimeout(() => {
                            this.closest('form').submit();
                        }, 300);
                    }
                });
            });
        });
    </script>
</body>

</html>