<?php

session_start();

require_once "config.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'not_logged_in'
    ]);
    exit;
}

$my_id = (int)$_SESSION['user_id'];
$indi = time();

$stmt = $pdo->prepare("
    SELECT
        online_oyuncu_vaxti,
        aktivlik_saniye
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $my_id
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        'ok' => false,
        'error' => 'user_not_found'
    ]);
    exit;
}

$son_online = (int)($user['online_oyuncu_vaxti'] ?? 0);
$aktivlik = (int)($user['aktivlik_saniye'] ?? 0);

$kechen = 0;

if ($son_online > 0) {

    $kechen = $indi - $son_online;

    /*
     * Mənfi vaxt mümkün deyil.
     */
    if ($kechen < 0) {
        $kechen = 0;
    }

    /*
     * Heartbeat normalda 5-10 saniyədən bir gələcək.
     * 60 saniyədən artıq gecikibsə,
     * aradakı vaxtı aktivlik kimi saymırıq.
     */
    if ($kechen > 60) {
        $kechen = 0;
    }
}

$yeni_aktivlik = $aktivlik + $kechen;

$stmt_update = $pdo->prepare("
    UPDATE users
    SET
        online_oyuncu_vaxti = :online_vaxt,
        aktivlik_saniye = :aktivlik
    WHERE id = :id
    LIMIT 1
");

$stmt_update->execute([
    ':online_vaxt' => $indi,
    ':aktivlik' => $yeni_aktivlik,
    ':id' => $my_id
]);

echo json_encode([
    'ok' => true,
    'online' => true,
    'aktivlik_saniye' => $yeni_aktivlik
]);

exit;
