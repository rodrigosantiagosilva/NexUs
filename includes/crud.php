<?php
require 'includes/conexao.php';

function getNotifications($pdo, $tipo = 'all') {
    $sql = "SELECT * FROM notificacoes";
    if ($tipo !== 'all') {
        $sql .= " WHERE type = :type";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':type', $tipo);
    } else {
        $stmt = $pdo->prepare($sql);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countUnread($pdo, $tipo = null) {
    $sql = "SELECT COUNT(*) FROM notificacoes WHERE `read` = 0";
    if ($tipo !== null && $tipo !== 'all') {
        $sql .= " AND type = :type";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':type', $tipo);
    } else {
        $stmt = $pdo->prepare($sql);
    }
    $stmt->execute();
    return $stmt->fetchColumn();
}

function markRead($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE notificacoes SET `read` = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

function markAllRead($pdo) {
    $stmt = $pdo->prepare("UPDATE notificacoes SET `read` = 1");
    return $stmt->execute();
}

function deleteNotification($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM notificacoes WHERE id = ?");
    return $stmt->execute([$id]);
}


// Buscar informações básicas do usuário
function buscarUser($usuarioId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT nome, email, matricula FROM usuario WHERE idusuario = ?");
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $userSettings['profile'] = [
            'name' => $usuario['nome'],
            'username' => $usuario['matricula'],
            'email' => $usuario['email']
        ];
    }
}