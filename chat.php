<?php
session_start();
require 'includes/conexao.php';
require 'functions/chat_functions.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioAtualId = $_SESSION['usuario_id'];

// Processar envio de mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conteudo'])) {
    $conteudo = trim($_POST['conteudo']);
    
    if (!empty($conteudo)) {
        if (isset($_POST['conversa_id'])) {
            enviarMensagem($pdo, $_POST['conversa_id'], $usuarioAtualId, $conteudo);
            $active_conversation_id = $_POST['conversa_id'];
        } elseif (isset($_POST['grupo_id'])) {
            enviarMensagemGrupo($pdo, $_POST['grupo_id'], $usuarioAtualId, $conteudo);
            $active_group_id = $_POST['grupo_id'];
        }
    }
    
    // Redireciona para evitar reenvio ao atualizar
    if (isset($active_conversation_id)) {
        header("Location: chat.php?id=$active_conversation_id");
    } elseif (isset($active_group_id)) {
        header("Location: chat.php?grupo_id=$active_group_id");
    } else {
        header("Location: chat.php");
    }
    exit;
}

// Obter conversa ativa
$active_conversation_id = 0;
$active_group_id = 0;
$outroUsuario = null;
$grupoAtivo = null;

if (isset($_GET['id'])) {
    $active_conversation_id = intval($_GET['id']);
} elseif (isset($_GET['grupo_id'])) {
    $active_group_id = intval($_GET['grupo_id']);
} elseif (isset($_GET['usuario_id'])) {
    $outroUsuarioId = intval($_GET['usuario_id']);
    $active_conversation_id = criarOuBuscarConversa($pdo, $usuarioAtualId, $outroUsuarioId);
}

// Buscar conversas e grupos do usuário
$conversasPrivadas = buscarConversasUsuario($pdo, $usuarioAtualId);
$grupos = buscarGruposUsuario($pdo, $usuarioAtualId);
$conversations = array_merge($conversasPrivadas, $grupos);

// Ordenar por data da última mensagem
usort($conversations, function($a, $b) {
    $timeA = strtotime($a['ultima_mensagem_data'] ?? '1970-01-01');
    $timeB = strtotime($b['ultima_mensagem_data'] ?? '1970-01-01');
    return $timeB - $timeA;
});

// Buscar mensagens
$mensagens = [];
if ($active_conversation_id > 0) {
    $mensagens = buscarMensagensConversa($pdo, $active_conversation_id, $usuarioAtualId);
    $outroUsuario = buscarOutroUsuarioConversa($pdo, $active_conversation_id, $usuarioAtualId);
} elseif ($active_group_id > 0) {
    $mensagens = buscarMensagensGrupo($pdo, $active_group_id, $usuarioAtualId);
    $grupoAtivo = buscarGrupo($pdo, $active_group_id);
}

// Gerar avatar com base no nome
function gerarAvatar($nome) {
    $nomeCodificado = urlencode($nome);
    $cor = substr(md5($nome), 0, 6);
    return "https://ui-avatars.com/api/?name=$nomeCodificado&background=$cor&color=fff";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens - TydraPI</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .sidebar {
            position: fixed;
            width: 20%;
        }
        
        /* Estilos para grupos */
        .group-indicator {
            background-color: #800000;
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 0.75rem;
            margin-left: 5px;
        }
        
        .message-sender-name {
            font-weight: 600;
            margin-bottom: 3px;
            color: #555;
        }
        
        .group-header-members {
            font-size: 0.85rem;
            color: #777;
        }
    </style>
    <?php include 'includes/header.php' ?>
</head>
<body>
    <?php include 'includes/sidebar.php' ?>
    
    <div class="chat-container">
        <!-- Lista de conversas -->
        <div class="conversation-list">
            <div class="conversation-list-header">
                Mensagens
            </div>
            <div class="search-container-chat">
                <input type="text" class="search-bar" placeholder="Buscar contatos..." aria-label="Buscar">
            </div>
            
            <!-- Conversas e Grupos -->
            <?php foreach ($conversations as $conversa): ?>
                <?php 
                // Determina se é um grupo verificando se existe o campo específico de grupos
                $isGroup = isset($conversa['tipo']) && $conversa['tipo'] === 'grupo';
                ?>
                <a href="<?= $isGroup ? 'chat.php?grupo_id='.$conversa['id'] : 'chat.php?id='.$conversa['id'] ?>" class="text-decoration-none">
                    <div class="conversation-item <?= 
                        ($isGroup && $active_group_id == $conversa['id']) || 
                        (!$isGroup && $active_conversation_id == $conversa['id']) ? 'active' : '' 
                    ?>">
                        <div class="avatar-wrapper">
                            <img src="<?= gerarAvatar($conversa['nome']) ?>" 
                                 alt="<?= htmlspecialchars($conversa['nome']) ?>" 
                                 class="avatar">
                            <?php if (!$isGroup): ?>
                                <div class="online-indicator"></div>
                            <?php endif; ?>
                        </div>
                        <div class="conversation-info">
                            <div class="conversation-name">
                                <?= htmlspecialchars($conversa['nome']) ?>
                                <?php if ($isGroup): ?>
                                    <span class="group-indicator">Grupo</span>
                                <?php endif; ?>
                            </div>
                            <div class="conversation-message">
                                <?= htmlspecialchars($conversa['ultima_mensagem'] ?? 'Sem mensagens') ?>
                            </div>
                        </div>
                        <div class="conversation-meta">
                            <div class="timestamp">
                                <?= $conversa['ultima_mensagem_data'] ? date('H:i', strtotime($conversa['ultima_mensagem_data'])) : '' ?>
                            </div>
                            <?php if ($conversa['nao_lidas'] > 0): ?>
                                <div class="unread-badge"><?= $conversa['nao_lidas'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Área de chat -->
        <div class="chat-area">
            <?php if ($active_conversation_id > 0 && $outroUsuario): ?>
                <!-- Cabeçalho do chat privado -->
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="avatar-wrapper">
                            <img src="<?= gerarAvatar($outroUsuario['nome']) ?>" 
                                 alt="<?= htmlspecialchars($outroUsuario['nome']) ?>" 
                                 class="avatar">
                            <div class="online-indicator"></div>
                        </div>
                        <div>
                            <div class="chat-header-name"><?= htmlspecialchars($outroUsuario['nome']) ?></div>
                            <div class="chat-status">Online</div>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <button class="chat-action-btn">
                            <i class="fas fa-phone-alt"></i>
                        </button>
                        <button class="chat-action-btn">
                            <i class="fas fa-video"></i>
                        </button>
                        <button class="chat-action-btn">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Área de mensagens -->
                <div id="messageArea" class="message-area">
                    <?php foreach ($mensagens as $mensagem): ?>
                        <div class="message <?= $mensagem['remetente_id'] == $usuarioAtualId ? 'message-sender' : 'message-receiver' ?>">
                            <div class="message-content">
                                <?= htmlspecialchars($mensagem['conteudo']) ?>
                            </div>
                            <div class="message-time">
                                <?= date('H:i', strtotime($mensagem['data_envio'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Área de entrada de mensagem -->
                <form method="POST" class="message-input-area">
                    <input type="hidden" name="conversa_id" value="<?= $active_conversation_id ?>">
                    
                    <button type="button" class="message-action-btn">
                        <i class="far fa-image"></i>
                    </button>
                    
                    <input type="text" name="conteudo" class="message-input" 
                           placeholder="Digite sua mensagem..." aria-label="Mensagem" required>
                    
                    <button type="button" class="message-action-btn">
                        <i class="fas fa-microphone"></i>
                    </button>
                    
                    <button type="submit" class="message-action-btn send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            <?php elseif ($active_group_id > 0 && $grupoAtivo): ?>
                <!-- Cabeçalho do grupo -->
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="avatar-wrapper">
                            <img src="<?= gerarAvatar($grupoAtivo['nome']) ?>" 
                                 alt="<?= htmlspecialchars($grupoAtivo['nome']) ?>" 
                                 class="avatar">
                        </div>
                        <div>
                            <div class="chat-header-name"><?= htmlspecialchars($grupoAtivo['nome']) ?></div>
                            <div class="group-header-members">
                                <?php 
                                    $membros = buscarMembrosGrupo($pdo, $grupoAtivo['id']);
                                    echo count($membros) . " membros";
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <button class="chat-action-btn">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Área de mensagens do grupo -->
                <div id="messageArea" class="message-area">
                    <?php foreach ($mensagens as $mensagem): ?>
                        <div class="message <?= $mensagem['remetente_id'] == $usuarioAtualId ? 'message-sender' : 'message-receiver' ?>">
                            <?php if ($mensagem['remetente_id'] != $usuarioAtualId): ?>
                                <div class="message-sender-name"><?= htmlspecialchars($mensagem['remetente_nome']) ?></div>
                            <?php endif; ?>
                            <div class="message-content">
                                <?= htmlspecialchars($mensagem['conteudo']) ?>
                            </div>
                            <div class="message-time">
                                <?= date('H:i', strtotime($mensagem['data_envio'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Área de entrada de mensagem do grupo -->
                <form method="POST" class="message-input-area">
                    <input type="hidden" name="grupo_id" value="<?= $active_group_id ?>">
                    
                    <button type="button" class="message-action-btn">
                        <i class="far fa-image"></i>
                    </button>
                    
                    <input type="text" name="conteudo" class="message-input" 
                           placeholder="Digite sua mensagem..." aria-label="Mensagem" required>
                    
                    <button type="button" class="message-action-btn">
                        <i class="fas fa-microphone"></i>
                    </button>
                    
                    <button type="submit" class="message-action-btn send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            <?php else: ?>
                <!-- Tela quando nenhuma conversa está selecionada -->
                <div class="empty-chat">
                    <div class="empty-chat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="empty-chat-title">Selecione uma conversa</h3>
                    <p>Escolha uma conversa existente ou inicie uma nova</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Rolagem automática para a última mensagem
        function scrollToBottom() {
            const messageArea = document.getElementById('messageArea');
            if (messageArea) {
                messageArea.scrollTop = messageArea.scrollHeight;
            }
        }
        
        // Enviar mensagem com AJAX
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const form = this;
                const formData = new FormData(form);
                const input = form.querySelector('input[name="conteudo"]');
                
                if (input.value.trim() === '') return;
                
                fetch('chat.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        // Recarregar a página para atualizar as mensagens
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Erro ao enviar mensagem:', error);
                });
            });
        });
        
        // Rolagem para baixo ao carregar
        window.addEventListener('load', scrollToBottom);
        
        // Enviar com Enter
        document.querySelectorAll('.message-input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.form.dispatchEvent(new Event('submit'));
                }
            });
        });
    </script>
</body>
</html>