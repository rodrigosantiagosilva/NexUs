
<?php
function getNotificationIcon($notification)
{
    if ($notification['type'] === 'academic') {
        switch ($notification['tipo_academico'] ?? '') {
            case 'homework': return '<i class="fa-solid fa-book text-primary"></i>';
            case 'exam': return '<i class="fa-solid fa-graduation-cap text-warning"></i>';
            case 'deadline': return '<i class="fa-solid fa-clock text-danger"></i>';
            case 'event': return '<i class="fa-solid fa-calendar-days text-success"></i>';
            default: return '<i class="fa-solid fa-graduation-cap text-danger"></i>';
        }
    }
    if ($notification['type'] === 'calendar') return '<i class="fa-solid fa-calendar-days text-danger"></i>';
    if ($notification['type'] === 'materials') return '<i class="fa-solid fa-book text-danger"></i>';
    return '<i class="fa-solid fa-bell text-danger"></i>';
}

function getPriorityIcon($priority)
{
    return match ($priority) {
        'high' => '<i class="fa-solid fa-exclamation-circle text-danger"></i>',
        'medium' => '<i class="fa-solid fa-info-circle text-warning"></i>',
        default => '<i class="fa-solid fa-info-circle text-success"></i>',
    };
}
