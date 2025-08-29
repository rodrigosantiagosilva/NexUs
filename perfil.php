<?php
session_start();
require 'includes/conexao.php';

// Verifica se o usuário está logado
$usuarioAtualId = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

if (!$usuarioAtualId) {
    header('Location: login.php');
    exit;
}

// Funções para perfil
require 'functions/perfil_functions.php';

// Buscar dados do usuário
$usuario = buscarUsuario($pdo, $usuarioAtualId);

// Buscar dados adicionais
$config = buscarConfigUsuario($pdo, $usuarioAtualId);
$aparencia = buscarAparenciaUsuario($pdo, $usuarioAtualId);

// Buscar estatísticas
$estatisticas = buscarEstatisticasUsuario($pdo, $usuarioAtualId);

// Buscar grupos do usuário
$grupos = buscarGruposUsuario($pdo, $usuarioAtualId);

// Buscar conexões do usuário
$conectados = buscarConectadosAoUsuario($pdo, $usuarioAtualId);
$conexoes = buscarConexoesUsuario($pdo, $usuarioAtualId);

// Buscar atividades recentes
$atividades = buscarAtividadesUsuario($pdo, $usuarioAtualId);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - TydraPI</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .perfil-avatar-container {
            top: 50px !important;

        }
    </style>
    <?php include 'includes/header.php' ?>
</head>
<body>
    <?php include 'includes/sidebar.php'?>
    <div class="perfil-body content-wrapper">
        <div class="perfil-container perfil-animate-fade-in">
            <div class="perfil-flex perfil-flex-col perfil-md-flex-row perfil-gap-6">
                <!-- Coluna de perfil -->
                <div class="perfil-w-full perfil-md-w-1-3 perfil-space-y-4">
                    <div class="perfil-card perfil-overflow-hidden">
                        <div class="perfil-gradient-banner"></div>
                        <div class="perfil-card-content perfil-pt-0 perfil-relative">
                            <div class="perfil-avatar-container">
                                <div class="perfil-avatar">
                                    <?php
                                    // Gerar avatar baseado no nome do usuário
                                    $nome = urlencode($usuario['nome']);
                                    $cor = substr(md5($usuario['nome']), 0, 6);
                                    ?>
                                    <img src="https://ui-avatars.com/api/?name=<?= $nome ?>&background=<?= $cor ?>&color=fff" 
                                         alt="<?= htmlspecialchars($usuario['nome']) ?>" 
                                         class="perfil-avatar-img">
                                </div>
                            </div>
                            <div class="perfil-pt-12 perfil-pb-4">
                                <div class="perfil-flex perfil-justify-between perfil-items-start">
                                    <div>
                                        <h2 class="perfil-name"><?= htmlspecialchars($usuario['nome']) ?></h2>
                                        <p class="perfil-username">@<?= htmlspecialchars($usuario['matricula']) ?></p>
                                    </div>
                                    <button class="perfil-btn-outline" onclick="location.href='configuracao.php'">
                                        <i data-lucide="edit" class="perfil-icon-sm"></i> Editar
                                    </button>
                                </div>
                                
                                <p class="perfil-mt-4 perfil-bio">
                                    <?= !empty($config['bio']) ? htmlspecialchars($config['bio']) : 'Nenhuma biografia fornecida' ?>
                                </p>
                                
                                <div class="perfil-flex perfil-items-center perfil-mt-4 perfil-text-gray perfil-text-sm">
                                    <i data-lucide="calendar" class="perfil-icon-sm perfil-mr-2"></i>
                                    <span>Membro desde <?= date('M Y', strtotime($usuario['criado_em'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="perfil-card">
                        <div class="perfil-card-header">
                            <div class="perfil-card-title">Estatísticas</div>
                        </div>
                        <div class="perfil-card-content">
                            <div class="perfil-grid perfil-grid-cols-4 perfil-gap-4 perfil-text-center">
                                 <div>
                                    <p class="perfil-stats-value"><?= $estatisticas['conectados'] ?></p>
                                    <p class="perfil-stats-label">conectados</p>
                                </div>
                                <div>
                                    <p class="perfil-stats-value"><?= $estatisticas['conexoes'] ?></p>
                                    <p class="perfil-stats-label">Conexões</p>
                                </div>
                                <div>
                                    <p class="perfil-stats-value"><?= $estatisticas['grupos'] ?></p>
                                    <p class="perfil-stats-label">Grupos</p>
                                </div>
                                <div>
                                    <p class="perfil-stats-value"><?= $estatisticas['eventos'] ?></p>
                                    <p class="perfil-stats-label">Eventos</p>
                                </div>
                                <div>
                                    <p class="perfil-stats-value"><?= $estatisticas['materiais'] ?></p>
                                    <p class="perfil-stats-label">Materiais</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="perfil-card">
                        <div class="perfil-card-header">
                            <div class="perfil-card-title">Conquistas</div>
                        </div>
                        <div class="perfil-card-content">
                            <div class="perfil-space-y-3">
                                <?php if (in_array('Conectivo', $estatisticas['conquistas'])): ?>
                                <div class="perfil-flex perfil-items-start">
                                    <div class="perfil-badge perfil-mr-3">1</div>
                                    <div>
                                        <h4 class="perfil-badge-title">Conectivo</h4>
                                        <p class="perfil-badge-desc">Possui conexões com outros usuários</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (in_array('Participativo', $estatisticas['conquistas'])): ?>
                                <div class="perfil-flex perfil-items-start">
                                    <div class="perfil-badge perfil-mr-3">2</div>
                                    <div>
                                        <h4 class="perfil-badge-title">Participativo</h4>
                                        <p class="perfil-badge-desc">Participa de grupos da comunidade</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (in_array('Organizador', $estatisticas['conquistas'])): ?>
                                <div class="perfil-flex perfil-items-start">
                                    <div class="perfil-badge perfil-mr-3">3</div>
                                    <div>
                                        <h4 class="perfil-badge-title">Organizador</h4>
                                        <p class="perfil-badge-desc">Criou eventos na plataforma</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (in_array('Contribuidor', $estatisticas['conquistas'])): ?>
                                <div class="perfil-flex perfil-items-start">
                                    <div class="perfil-badge perfil-mr-3">4</div>
                                    <div>
                                        <h4 class="perfil-badge-title">Contribuidor</h4>
                                        <p class="perfil-badge-desc">Compartilhou materiais úteis</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (empty($estatisticas['conquistas'])): ?>
                                    <p class="perfil-text-gray">Nenhuma conquista ainda. Participe mais da comunidade!</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Coluna de conteúdo -->
                <div class="perfil-w-full perfil-md-w-2-3">
                    <div class="perfil-tabs">
                        <ul class="perfil-tabs-list">
                            <li class="perfil-tab perfil-tab-active" data-tab="activity">Atividade</li>
                            <li class="perfil-tab" data-tab="connections">Conexões</li>
                            <li class="perfil-tab" data-tab="groups">Grupos</li>
                        </ul>
                        
                        <div class="perfil-tab-content perfil-tab-content-active" id="activity">
                            <div class="perfil-card">
                                <div class="perfil-card-header">
                                    <div class="perfil-card-title">Atividades Recentes</div>
                                </div>
                                <div class="perfil-card-content">
                                    <div class="perfil-space-y-6 perfil-relative">
                                        <div class="perfil-timeline-line"></div>
                                        
                                        <?php if (empty($atividades)): ?>
                                            <p class="perfil-text-gray">Nenhuma atividade recente.</p>
                                        <?php else: ?>
                                            <?php foreach ($atividades as $atividade): ?>
                                                <div class="perfil-timeline-item">
                                                    <div class="perfil-timeline-icon">
                                                        <?php 
                                                        $icone = 'users';
                                                        $cor = '#0d6efd';
                                                        
                                                        if ($atividade['tipo'] === 'match') {
                                                            $icone = 'users';
                                                            $cor = '#4f46e5';
                                                        } elseif ($atividade['tipo'] === 'grupo') {
                                                            $icone = 'users';
                                                            $cor = '#7c3aed';
                                                        } elseif ($atividade['tipo'] === 'evento') {
                                                            $icone = 'calendar';
                                                            $cor = '#0ea5e9';
                                                        } elseif ($atividade['tipo'] === 'material') {
                                                            $icone = 'file-text';
                                                            $cor = '#10b981';
                                                        }
                                                        ?>
                                                        <i data-lucide="<?= $icone ?>" class="perfil-icon-xs" style="color: <?= $cor ?>"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="perfil-timeline-title"><?= htmlspecialchars($atividade['titulo']) ?></h4>
                                                        <p class="perfil-timeline-desc"><?= htmlspecialchars($atividade['descricao']) ?></p>
                                                        <p class="perfil-timeline-time">
                                                            <?= date('d M, Y H:i', strtotime($atividade['data'])) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="perfil-tab-content" id="connections">
                            <div class="perfil-card">
                                <div class="perfil-card-header">
                                    <div class="perfil-card-title">Minhas Conexões</div>
                                </div>
                                <div class="perfil-card-content">
                                    <p class="perfil-text-gray perfil-mb-4">Você possui <?= $estatisticas['conexoes'] ?> conexoes</p>
                            


                                    <?php if (empty($conexoes)): ?>
                                        <p class="perfil-text-gray">Você ainda não tem conexões. Explore a comunidade para conhecer novas pessoas!</p>
                                    <?php else: ?>
                                        <div class="perfil-grid perfil-grid-cols-1 perfil-md-grid-cols-2 perfil-gap-4">
                                            <?php foreach ($conexoes as $conexao): ?>
                                                <div class="perfil-connection-item">
                                                    <div class="perfil-connection-avatar">
                                                        <?php 
                                                        $nome = urlencode($conexao['nome']);
                                                        $cor = substr(md5($conexao['nome']), 0, 6);
                                                        ?>
                                                        <img src="https://ui-avatars.com/api/?name=<?= $nome ?>&background=<?= $cor ?>&color=fff" 
                                                             alt="<?= htmlspecialchars($conexao['nome']) ?>">
                                                    </div>
                                                    <div class="perfil-ml-3 perfil-flex-1">
                                                        <h4 class="perfil-connection-name"><?= htmlspecialchars($conexao['nome']) ?></h4>
                                                        <p class="perfil-connection-username">@<?= htmlspecialchars($conexao['matricula']) ?></p>
                                                    </div>
    
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                  
                                    <?php endif; ?>
                                    <p class="perfil-text-gray perfil-mb-4">Você possui <?= $estatisticas['conectados'] ?> conectados</p>
                                    
                                    <?php if (empty($conectados)): ?>
                                        <p class="perfil-text-gray">Você ainda não tem conexões. Explore a comunidade para conhecer novas pessoas!</p>
                                    <?php else: ?>
                                        <div class="perfil-grid perfil-grid-cols-1 perfil-md-grid-cols-2 perfil-gap-4">
                                            <?php foreach ($conectados as $conectado): ?>
                                                <div class="perfil-connection-item">
                                                    <div class="perfil-connection-avatar">
                                                        <?php 
                                                        $nome = urlencode($conectado['nome']);
                                                        $cor = substr(md5($conectado['nome']), 0, 6);
                                                        ?>
                                                        <img src="https://ui-avatars.com/api/?name=<?= $nome ?>&background=<?= $cor ?>&color=fff" 
                                                             alt="<?= htmlspecialchars($conectado['nome']) ?>">
                                                    </div>
                                                    <div class="perfil-ml-3 perfil-flex-1">
                                                        <h4 class="perfil-connection-name"><?= htmlspecialchars($conectado['nome']) ?></h4>
                                                        <p class="perfil-connection-username">@<?= htmlspecialchars($conectado['matricula']) ?></p>
                                                    </div>
                                                    <button class="perfil-btn-ghost" 
                                                            onclick="location.href='chat.php?usuario_id=<?= $conectado['idusuario'] ?>'">
                                                        <i data-lucide="message-square" class="perfil-icon-sm"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <button class="perfil-btn-secondary perfil-w-full perfil-mt-4"
                                                onclick="location.href='conexoes.php'">
                                            Ver todas as conexões
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="perfil-tab-content" id="groups">
                            <div class="perfil-card">
                                <div class="perfil-card-header">
                                    <div class="perfil-card-title">Meus Grupos</div>
                                </div>
                                <div class="perfil-card-content">
                                    <p class="perfil-text-gray perfil-mb-4">Você participa de <?= $estatisticas['grupos'] ?> grupos</p>
                                    
                                    <?php if (empty($grupos)): ?>
                                        <p class="perfil-text-gray">Você ainda não participa de nenhum grupo. Explore a aba comunidades para encontrar grupos interessantes!</p>
                                    <?php else: ?>
                                        <div class="perfil-space-y-4">
                                            <?php foreach ($grupos as $grupo): ?>
                                                <div class="perfil-group-item">
                                                    <div class="perfil-group-avatar">
                                                        <?php 
                                                        $nome = urlencode($grupo['nome']);
                                                        $cor = substr(md5($grupo['nome']), 0, 6);
                                                        ?>
                                                        <img src="https://ui-avatars.com/api/?name=<?= $nome ?>&background=<?= $cor ?>&color=fff" 
                                                             alt="<?= htmlspecialchars($grupo['nome']) ?>">
                                                    </div>
                                                    <div class="perfil-ml-3 perfil-flex-1">
                                                        <h4 class="perfil-group-name"><?= htmlspecialchars($grupo['nome']) ?></h4>
                                                        <p class="perfil-group-members"><?= $grupo['total_membros'] ?> membros</p>
                                                        <p class="perfil-group-desc perfil-mt-1">
                                                            <?= htmlspecialchars(mb_strimwidth($grupo['descricao'], 0, 60, '...')) ?>
                                                        </p>
                                                    </div>
                                                    <button class="perfil-btn-outline"
                                                            onclick="location.href='grupo.php?id=<?= $grupo['id'] ?>'">
                                                        Ver grupo
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <button class="perfil-btn-secondary perfil-w-full perfil-mt-4"
                                                onclick="location.href='comunidades.php?tab=meus_grupos'">
                                            Ver todos os grupos
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Inicialização dos ícones Lucide
        lucide.createIcons();
        
        // Funcionalidade das abas
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.perfil-tab');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remover classe ativa de todas as abas
                    tabs.forEach(t => t.classList.remove('perfil-tab-active'));
                    
                    // Adicionar classe ativa à aba clicada
                    this.classList.add('perfil-tab-active');
                    
                    // Ocultar todos os conteúdos
                    const contents = document.querySelectorAll('.perfil-tab-content');
                    contents.forEach(content => content.classList.remove('perfil-tab-content-active'));
                    
                    // Mostrar o conteúdo correspondente
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('perfil-tab-content-active');
                });
            });
        });
    </script>
</body>
</html>