
<?php
session_start();

include 'includes/conexao.php'; 
include 'includes/crud.php';

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = intval($_POST['notification_id'] ?? 0);

    if ($action === 'mark_read' && $id > 0) {
        if (markRead($pdo, $id)) {
            $_SESSION['message'] = "Notificação marcada como lida";
        }
    }
    elseif ($action === 'mark_all_read') {
        if (markAllRead($pdo)) {
            $_SESSION['message'] = "Todas as notificações foram marcadas como lidas";
        }
    }
    elseif ($action === 'delete' && $id > 0) {
        if (deleteNotification($pdo, $id)) {
            $_SESSION['message'] = "Notificação removida";
        }
    }

    // Redirecionamento seguro
    $redirect_url = 'notifications.php';
    if (isset($_GET['type'])) {
        $redirect_url .= '?type=' . urlencode($_GET['type']);
    }
    header('Location: ' . $redirect_url);
    exit;
}

// Pega o filtro ativo, padrão 'all'
$activeTab = $_GET['type'] ?? 'all';

// Busca notificações conforme filtro
$notifications = getNotifications($pdo, $activeTab);

// Contagem de notificações não lidas por tipo
$allUnreadCount      = countUnread($pdo, 'all');
$academicUnreadCount = countUnread($pdo, 'academic');
$calendarUnreadCount = countUnread($pdo, 'calendar');
$materialsUnreadCount = countUnread($pdo, 'materials');

include 'functions/notificfunctions.php';
include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Notificações - TydraPI</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        .sidebar {
            width: 20%;
            position: fixed;
        }

        .content-wrapper {
            align-self: center;
            margin-left: 260px;
            padding: 20px;
            width: calc(83%);
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'?>
    <div class="tydrapi-container py-6 content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 fw-bold">Notificações</h1>

            <form method="post" class="d-inline">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="tydrapi-button-outline">
                    <i class="bi-check me-2"></i> Marcar todas como lidas
                </button>
            </form>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="notification-toast">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <div class="tabs-container mb-6">
            <a href="?type=all" class="tab <?= $activeTab === 'all' ? 'active' : '' ?>">
                <i class="fas fa-bell me-2"></i> Todas
                <?php if ($allUnreadCount > 0): ?>
                    <span class="badge"><?= $allUnreadCount ?></span>
                <?php endif; ?>
            </a>
            <a href="?type=academic" class="tab <?= $activeTab === 'academic' ? 'active' : '' ?>">
                <i class="fas fa-book me-2"></i> Acadêmicas
                <?php if ($academicUnreadCount > 0): ?>
                    <span class="badge"><?= $academicUnreadCount ?></span>
                <?php endif; ?>
            </a>
            <a href="?type=calendar" class="tab <?= $activeTab === 'calendar' ? 'active' : '' ?>">
                <i class="fas fa-calendar me-2"></i> Calendário
                <?php if ($calendarUnreadCount > 0): ?>
                    <span class="badge"><?= $calendarUnreadCount ?></span>
                <?php endif; ?>
            </a>
            <a href="?type=materials" class="tab <?= $activeTab === 'materials' ? 'active' : '' ?>">
                <i class="fas fa-tasks me-2"></i> Materiais
                <?php if ($materialsUnreadCount > 0): ?>
                    <span class="badge"><?= $materialsUnreadCount ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="tab-content">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="tydrapi-card <?= !$notification['read'] ? 'unread' : '' ?>">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0 mt-1">
                                <?= getNotificationIcon($notification) ?>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="h5 fw-medium d-flex align-items-center">
                                            <?= htmlspecialchars($notification['title']) ?>
                                            <span class="ms-2"><?= getPriorityIcon($notification['priority']) ?></span>
                                        </h3>
                                        <p class="text-sm mt-1"><?= htmlspecialchars($notification['message']) ?></p>
                                    </div>
                                    <div class="text-end">
                                        <p class="text-xs text-tydrapi-gray small mb-0"><?= htmlspecialchars($notification['date']) ?></p>
                                        <p class="text-xs text-tydrapi-gray small"><?= htmlspecialchars($notification['time']) ?></p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-3">
                                    <div>
                                        <?php if (!$notification['read']): ?>
                                            <span class="badge bg-danger ">Não lida</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php if (!$notification['read']): ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                <button type="submit" class="btn-ghost-sm">
                                                    <i class="bi-check me-1"></i> Marcar como lida
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                            <button type="submit" class="btn-ghost-sm">
                                                Remover
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <p class="text-muted">Não há notificações para exibir.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>