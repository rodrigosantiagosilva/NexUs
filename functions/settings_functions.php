<?php

/**
 * Busca todas as configurações de um usuário no banco de dados.
 *
 * @param PDO $pdo O objeto de conexão com o banco de dados.
 * @param int $usuarioId O ID do usuário.
 * @return array Um array associativo com todas as configurações do usuário.
 */
function getUserSettings(PDO $pdo, int $usuarioId): array
{
    // Array padrão de configurações para garantir que nenhuma chave esteja faltando
    $userSettings = [
        'profile' => [
            'name' => '', 'username' => '', 'email' => '', 'bio' => '', 'phone' => ''
        ],
        'notifications' => [
            'messages' => false, 'matches' => false, 'groups' => false, 'mentions' => false,
            'email_notifications' => false, 'push_notifications' => false
        ],
        'privacy' => [
            'profile_visibility' => 'all', 'online_status' => true, 'read_receipts' => true,
            'data_collection' => true, 'chat_expiration' => '24h'
        ],
        'appearance' => [
            'theme' => 'dark', 'font_size' => 'medium', 'language' => 'pt-BR'
        ],
        'security' => ['two_factor' => false] // Exemplo, pode ser expandido
    ];

    // 1. Buscar informações básicas do usuário (tabela `usuario`)
    $stmt = $pdo->prepare("SELECT nome, email, matricula FROM usuario WHERE idusuario = ?");
    $stmt->execute([$usuarioId]);
    if ($usuario = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userSettings['profile']['name'] = $usuario['nome'];
        $userSettings['profile']['username'] = $usuario['matricula'];
        $userSettings['profile']['email'] = $usuario['email'];
    }

    // 2. Buscar configurações de perfil (tabela `usuario_config`)
    $stmt = $pdo->prepare("SELECT bio, telefone FROM usuario_config WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    if ($config = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userSettings['profile']['bio'] = $config['bio'] ?? '';
        $userSettings['profile']['phone'] = $config['telefone'] ?? '';
    }

    // 3. Buscar configurações de notificações (tabela `usuario_notificacoes`)
    $stmt = $pdo->prepare("SELECT * FROM usuario_notificacoes WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    if ($notificacoes = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userSettings['notifications'] = [
            'messages' => (bool)$notificacoes['mensagens'],
            'matches' => (bool)$notificacoes['matches'],
            'groups' => (bool)$notificacoes['grupos'],
            'mentions' => (bool)$notificacoes['mencoes'],
            'email_notifications' => (bool)$notificacoes['email_notificacoes'],
            'push_notifications' => (bool)$notificacoes['push_notificacoes']
        ];
    }
    
    // 4. Buscar configurações de privacidade (tabela `usuario_privacidade`)
    $stmt = $pdo->prepare("SELECT * FROM usuario_privacidade WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    if ($privacidade = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userSettings['privacy'] = [
            'profile_visibility' => $privacidade['perfil_visibilidade'],
            'online_status' => (bool)$privacidade['status_online'],
            'read_receipts' => (bool)$privacidade['confirmacoes_leitura'],
            'data_collection' => (bool)$privacidade['coleta_dados'],
            'chat_expiration' => $privacidade['expiracao_chat']
        ];
    }

    // 5. Buscar configurações de aparência (tabela `usuario_aparencia`)
    $stmt = $pdo->prepare("SELECT * FROM usuario_aparencia WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    if ($aparencia = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userSettings['appearance'] = [
            'theme' => $aparencia['tema'],
            'font_size' => $aparencia['tamanho_fonte'],
            'language' => $aparencia['idioma']
        ];
    }

    return $userSettings;
}

/**
 * Função genérica para inserir ou atualizar (UPSERT) configurações em tabelas associadas.
 */
function upsertSettings(PDO $pdo, string $tableName, array $data, int $usuarioId)
{
    $stmt = $pdo->prepare("SELECT usuario_id FROM {$tableName} WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    $exists = $stmt->fetch();

    if ($exists) {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "{$key} = ?";
        }
        $sql = "UPDATE {$tableName} SET " . implode(', ', $setParts) . " WHERE usuario_id = ?";
        $values = array_values($data);
        $values[] = $usuarioId;
    } else {
        $data['usuario_id'] = $usuarioId;
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$tableName} ({$columns}) VALUES ({$placeholders})";
        $values = array_values($data);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
}

/**
 * Atualiza a senha do usuário.
 */
function updateUserPassword(PDO $pdo, int $usuarioId, string $currentPassword, string $newPassword, string $confirmPassword): string
{
    $stmt = $pdo->prepare("SELECT senha FROM usuario WHERE idusuario = ?");
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($currentPassword, $usuario['senha'])) {
        return "Senha atual incorreta!";
    }
    if ($newPassword !== $confirmPassword) {
        return "As novas senhas não coincidem!";
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE usuario SET senha = ? WHERE idusuario = ?");
    $stmt->execute([$newHash, $usuarioId]);

    return "Senha atualizada com sucesso!";
}

/**
 * Roteador para processar os envios de formulário da página de configurações.
 */
function handleSettingsPostRequest(PDO $pdo, int $usuarioId, array $postData)
{
    try {
        if (isset($postData['save_profile'])) {
            $data = ['bio' => $postData['bio'] ?? '', 'telefone' => $postData['phone'] ?? ''];
            upsertSettings($pdo, 'usuario_config', $data, $usuarioId);
            $_SESSION['settings_message'] = "Perfil atualizado com sucesso!";

        } elseif (isset($postData['save_password'])) {
            $_SESSION['settings_message'] = updateUserPassword($pdo, $usuarioId, $postData['current_password'], $postData['new_password'], $postData['confirm_password']);

        } elseif (isset($postData['save_notification_settings'])) {
            $notifications = $postData['notifications'] ?? [];
            $data = [
                'mensagens' => isset($notifications['messages']) ? 1 : 0,
                'matches' => isset($notifications['matches']) ? 1 : 0,
                'grupos' => isset($notifications['groups']) ? 1 : 0,
                'mencoes' => isset($notifications['mentions']) ? 1 : 0,
                'email_notificacoes' => isset($notifications['email_notifications']) ? 1 : 0,
                'push_notificacoes' => isset($notifications['push_notifications']) ? 1 : 0
            ];
            upsertSettings($pdo, 'usuario_notificacoes', $data, $usuarioId);
            $_SESSION['settings_message'] = "Configurações de notificação atualizadas com sucesso!";

        } elseif (isset($postData['save_privacy'])) {
            $privacy = $postData['privacy'] ?? [];
            $data = [
                'perfil_visibilidade' => $privacy['profile_visibility'] ?? 'all',
                'status_online' => isset($privacy['online_status']) ? 1 : 0,
                'confirmacoes_leitura' => isset($privacy['read_receipts']) ? 1 : 0,
                'coleta_dados' => isset($privacy['data_collection']) ? 1 : 0,
                'expiracao_chat' => $privacy['chat_expiration'] ?? '24h'
            ];
            upsertSettings($pdo, 'usuario_privacidade', $data, $usuarioId);
            $_SESSION['settings_message'] = "Configurações de privacidade atualizadas com sucesso!";

        } elseif (isset($postData['save_appearance'])) {
            $appearance = $postData['appearance'] ?? [];
            $data = [
                'tema' => $appearance['theme'] ?? 'dark',
                'tamanho_fonte' => $appearance['font_size'] ?? 'medium',
                'idioma' => $appearance['language'] ?? 'pt-BR'
            ];
            upsertSettings($pdo, 'usuario_aparencia', $data, $usuarioId);
            $_SESSION['settings_message'] = "Preferências de aparência atualizadas com sucesso!";
        }
    } catch (PDOException $e) {
        $_SESSION['settings_message'] = "Erro ao salvar configurações: " . $e->getMessage();
    }

    $tab = $_GET['tab'] ?? 'account';
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=" . urlencode($tab));
    exit;
}