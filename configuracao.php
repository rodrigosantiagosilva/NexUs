<?php
session_start();

// Inclui a conexão com o banco de dados e as novas funções
include 'includes/crud.php';
include 'functions/settings_functions.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

$usuarioId = $_SESSION['usuario_id'];

// Processa o formulário se for um POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    handleSettingsPostRequest($pdo, $usuarioId, $_POST);
}

// Busca todas as configurações do usuário usando a nova função
$userSettings = getUserSettings($pdo, $usuarioId);

// Determina a aba ativa
$activeTab = $_GET['tab'] ?? 'account';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TydraPI - Configurações</title>
    <link rel="stylesheet" href="settings-php.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <?php include 'includes/header.php'; ?>
    <style>
        .sidebar {
            width: 20%;
            position: fixed;
        }
        .content-wrapper {
            margin-left: 20%; 
            padding-left: 2rem;
        }
        /* Classe para esconder inputs de radio */
        .settings-hidden {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'?>
    <div class="settings-container content-wrapper">
        <h1 class="settings-title">Configurações</h1>
        
        <?php if (isset($_SESSION['settings_message'])): ?>
            <div class="settings-toast">
                <?php echo htmlspecialchars($_SESSION['settings_message']); unset($_SESSION['settings_message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="settings-flex settings-flex-col settings-flex-md-row settings-gap-6">
            <div class="settings-w-full settings-w-md-64">
                <div class="settings-tabs">
                    <a href="?tab=account" class="settings-tab <?php echo $activeTab === 'account' ? 'active' : ''; ?>"><i class="fa fa-user settings-mr-2"></i> Conta</a>
                    <a href="?tab=security" class="settings-tab <?php echo $activeTab === 'security' ? 'active' : ''; ?>"><i class="fa fa-lock settings-mr-2"></i> Segurança</a>
                    <a href="?tab=notifications" class="settings-tab <?php echo $activeTab === 'notifications' ? 'active' : ''; ?>"><i class="fa fa-bell settings-mr-2"></i> Notificações</a>
                    <a href="?tab=privacy" class="settings-tab <?php echo $activeTab === 'privacy' ? 'active' : ''; ?>"><i class="fa fa-eye settings-mr-2"></i> Privacidade</a>
                    <a href="?tab=appearance" class="settings-tab <?php echo $activeTab === 'appearance' ? 'active' : ''; ?>"><i class="fa fa-moon settings-mr-2"></i> Aparência</a>
                    <hr class="settings-divider">
                    <a href="functions/logout.php" class="settings-tab settings-text-red"><i class="fa fa-sign-out-alt settings-mr-2"></i> Sair</a>
                </div>
            </div>

            <div class="settings-flex-1 w-100">
                
                <?php if ($activeTab === 'account'): ?>
                    <div class="settings-card">
                        <div class="settings-mb-4">
                            <h2 class="settings-subtitle">Informações de Conta</h2>
                            <p class="settings-description">Gerencie suas informações pessoais e de perfil</p>
                        </div>
                        <form method="post" class="settings-form">
                            <div class="settings-form-field">
                                <label for="bio" class="settings-label">Biografia</label>
                                <textarea id="bio" name="bio" rows="3" class="settings-textarea"><?php echo htmlspecialchars($userSettings['profile']['bio']); ?></textarea>
                            </div>
                            <div class="settings-form-field">
                                <label for="phone" class="settings-label">Telefone</label>
                                <input id="phone" name="phone" value="<?php echo htmlspecialchars($userSettings['profile']['phone'] ?? ''); ?>" class="settings-input">
                            </div>
                            <div>
                                <button type="submit" name="save_profile" class="settings-btn">Salvar alterações</button>
                            </div>
                        </form>
                    </div>
                
                <?php elseif ($activeTab === 'security'): ?>
                    <div class="settings-card">
                        <div class="settings-mb-4">
                            <h2 class="settings-subtitle">Segurança</h2>
                            <p class="settings-description">Gerencie sua senha e configurações de segurança</p>
                        </div>
                        <form method="post" class="settings-space-y-4">
                            <h3 class="settings-group-title">Alterar senha</h3>
                            <div class="settings-form-field">
                                <label for="current-password" class="settings-label">Senha atual</label>
                                <input id="current-password" name="current_password" type="password" class="settings-input" required>
                            </div>
                            <div class="settings-form-field">
                                <label for="new-password" class="settings-label">Nova senha</label>
                                <input id="new-password" name="new_password" type="password" class="settings-input" required>
                            </div>
                            <div class="settings-form-field">
                                <label for="confirm-password" class="settings-label">Confirmar nova senha</label>
                                <input id="confirm-password" name="confirm_password" type="password" class="settings-input" required>
                            </div>
                            <div>
                                <button type="submit" name="save_password" class="settings-btn">Atualizar senha</button>
                            </div>
                        </form>
                    </div>

                <?php elseif ($activeTab === 'notifications'): ?>
                    <div class="settings-card">
                        <div class="settings-mb-4">
                            <h2 class="settings-subtitle">Notificações</h2>
                            <p class="settings-description">Gerencie como você recebe notificações</p>
                        </div>
                        <form method="post" class="settings-form">
                             <div class="settings-form-row">
                                <label for="notify-messages" class="settings-label">Mensagens</label>
                                <label class="settings-switch">
                                    <input type="checkbox" id="notify-messages" name="notifications[messages]" value="1" <?php echo $userSettings['notifications']['messages'] ? 'checked' : ''; ?>>
                                    <span class="settings-slider"></span>
                                </label>
                            </div>
                            <div class="settings-mt-4">
                                <button type="submit" name="save_notification_settings" class="settings-btn">Salvar preferências</button>
                            </div>
                        </form>
                    </div>

                <?php elseif ($activeTab === 'privacy'): ?>
                    <div class="settings-card">
                         <div class="settings-mb-4">
                            <h2 class="settings-subtitle">Privacidade</h2>
                            <p class="settings-description">Gerencie quem pode ver seu perfil e como seus dados são usados</p>
                        </div>
                        <form method="post" class="settings-form">
                            <div class="settings-form-field">
                                <label for="profile-visibility" class="settings-label">Quem pode ver meu perfil</label>
                                <select id="profile-visibility" name="privacy[profile_visibility]" class="settings-select">
                                    <option value="all" <?php echo $userSettings['privacy']['profile_visibility'] == 'all' ? 'selected' : ''; ?>>Todos</option>
                                    <option value="connections" <?php echo $userSettings['privacy']['profile_visibility'] == 'connections' ? 'selected' : ''; ?>>Apenas conexões</option>
                                    <option value="nobody" <?php echo $userSettings['privacy']['profile_visibility'] == 'nobody' ? 'selected' : ''; ?>>Ninguém</option>
                                </select>
                            </div>
                            <div class="settings-form-row">
                                <div><label class="settings-label">Mostrar status online</label></div>
                                <label class="settings-switch">
                                    <input type="checkbox" name="privacy[online_status]" value="1" <?php echo $userSettings['privacy']['online_status'] ? 'checked' : ''; ?>>
                                    <span class="settings-slider"></span>
                                </label>
                            </div>
                             <div class="settings-form-row">
                                <div><label class="settings-label">Confirmações de leitura</label></div>
                                <label class="settings-switch">
                                    <input type="checkbox" name="privacy[read_receipts]" value="1" <?php echo $userSettings['privacy']['read_receipts'] ? 'checked' : ''; ?>>
                                    <span class="settings-slider"></span>
                                </label>
                            </div>
                            <div class="settings-form-field">
                                <label for="chat-expiration" class="settings-label">Expiração de chats temporários</label>
                                <select id="chat-expiration" name="privacy[chat_expiration]" class="settings-select">
                                    <option value="12h" <?php echo $userSettings['privacy']['chat_expiration'] == '12h' ? 'selected' : ''; ?>>12 horas</option>
                                    <option value="24h" <?php echo $userSettings['privacy']['chat_expiration'] == '24h' ? 'selected' : ''; ?>>24 horas</option>
                                    <option value="never" <?php echo $userSettings['privacy']['chat_expiration'] == 'never' ? 'selected' : ''; ?>>Nunca expirar</option>
                                </select>
                            </div>
                             <div class="settings-form-row">
                                <div><label class="settings-label">Coleta de dados para melhorias</label></div>
                                <label class="settings-switch">
                                    <input type="checkbox" name="privacy[data_collection]" value="1" <?php echo $userSettings['privacy']['data_collection'] ? 'checked' : ''; ?>>
                                    <span class="settings-slider"></span>
                                </label>
                            </div>
                            <div class="settings-mt-4">
                                <button type="submit" name="save_privacy" class="settings-btn">Salvar configurações</button>
                            </div>
                        </form>
                    </div>

                <?php elseif ($activeTab === 'appearance'): ?>
                    <div class="settings-card">
                        <div class="settings-mb-4">
                            <h2 class="settings-subtitle">Aparência</h2>
                            <p class="settings-description">Personalize a aparência da aplicação</p>
                        </div>
                        <form method="post" class="settings-form">
                            <h3 class="settings-group-title">Tema</h3>
                            <div class="settings-theme-options">
                                 <label for="theme-light">Claro <input type="radio" id="theme-light" name="appearance[theme]" value="light" class="settings-hidden" <?php echo $userSettings['appearance']['theme'] === 'light' ? 'checked' : ''; ?>></label>
                                 <label for="theme-dark">Escuro <input type="radio" id="theme-dark" name="appearance[theme]" value="dark" class="settings-hidden" <?php echo $userSettings['appearance']['theme'] === 'dark' ? 'checked' : ''; ?>></label>
                            </div>
                            <div class="settings-form-field">
                                <label for="font-size" class="settings-label">Tamanho da fonte</label>
                                <select id="font-size" name="appearance[font_size]" class="settings-select">
                                    <option value="small" <?php echo $userSettings['appearance']['font_size'] === 'small' ? 'selected' : ''; ?>>Pequeno</option>
                                    <option value="medium" <?php echo $userSettings['appearance']['font_size'] === 'medium' ? 'selected' : ''; ?>>Médio</option>
                                    <option value="large" <?php echo $userSettings['appearance']['font_size'] === 'large' ? 'selected' : ''; ?>>Grande</option>
                                </select>
                            </div>
                             <div class="settings-form-field">
                                <label for="language" class="settings-label">Idioma</label>
                                <select id="language" name="appearance[language]" class="settings-select">
                                    <option value="pt-BR" <?php echo $userSettings['appearance']['language'] === 'pt-BR' ? 'selected' : ''; ?>>Português (Brasil)</option>
                                    <option value="en-US" <?php echo $userSettings['appearance']['language'] === 'en-US' ? 'selected' : ''; ?>>English (US)</option>
                                </select>
                            </div>
                            <div class="settings-mt-6">
                                <button type="submit" name="save_appearance" class="settings-btn">Salvar preferências</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>