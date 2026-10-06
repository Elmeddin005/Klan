<?php
date_default_timezone_set('Asia/Baku');
if (!isset($_SESSION['user_id'])) {
    return;
}

/*
==========================================================
 İSTİFADƏÇİ MƏLUMATLARINI MYSQL-DAN GÖTÜR
==========================================================
*/

$stmt_user = $pdo->prepare("
    SELECT *
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_user->execute([
    ':id' => (int)$_SESSION['user_id']
]);

$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit;
}
/*
==========================================================
 DUELDƏN ÇIXIŞ / NƏTİCƏNİN BİR DƏFƏ GÖSTƏRİLMƏSİ
==========================================================
*/

$my_id = (int)$_SESSION['user_id'];


/*
 * Əgər istifadəçi hazırda fight.php?duel_id=... açıbsa
 * və həmin duel artıq bitibsə, bu nəticəni "görüldü"
 * kimi SESSION-da yadda saxlayırıq.
 */
if (
    basename($_SERVER['PHP_SELF']) === 'fight.php' &&
    isset($_GET['duel_id'])
) {

    $current_duel_id = (int)$_GET['duel_id'];

    if ($current_duel_id > 0) {

        $stmt_current_duel = $pdo->prepare("
            SELECT
                id,
                oyuncu1_id,
                oyuncu2_id,
                qalib_id,
                bitdi_qalib,
                bitdi_meglub
            FROM duel
            WHERE id = :duel_id
              AND (oyuncu1_id = :my_id OR oyuncu2_id = :my_id)
            LIMIT 1
        ");

        $stmt_current_duel->execute([
            ':duel_id' => $current_duel_id,
            ':my_id'   => $my_id
        ]);

        $current_duel = $stmt_current_duel->fetch(PDO::FETCH_ASSOC);

        if ($current_duel) {

            $duel_bitib = (
                (int)$current_duel['bitdi_qalib'] > 0 ||
                (int)$current_duel['bitdi_meglub'] > 0 ||
                (
                    $current_duel['qalib_id'] !== null &&
                    (int)$current_duel['qalib_id'] >= 0
                )
            );

            if ($duel_bitib) {

                $_SESSION['son_gosterilen_duel_id'] =
                    $current_duel_id;
            }
        }
    }
}


/*
==========================================================
 SAYTA QAYIDANDA BİTMİŞ DUEL VARSA NƏTİCƏSİNİ GÖSTƏR
==========================================================
*/

if (
    !isset($_GET['duel_id']) &&
    !isset($_GET['ok']) &&
    !isset($_GET['lis']) &&
    !isset($_GET['hec_hece'])
) {

    $son_gosterilen_duel_id =
        isset($_SESSION['son_gosterilen_duel_id'])
            ? (int)$_SESSION['son_gosterilen_duel_id']
            : 0;


    $stmt_geri_duel = $pdo->prepare("
        SELECT
            id,
            oyuncu1_id,
            oyuncu2_id,
            qalib_id,
            bitdi_qalib,
            bitdi_meglub,
            oyuncu1_hucum,
            oyuncu2_hucum
        FROM duel
        WHERE
            (oyuncu1_id = :my_id OR oyuncu2_id = :my_id)

            AND id > :son_gosterilen_duel_id

            AND (
                (
                    bitdi_qalib IS NOT NULL
                    AND bitdi_qalib > 0
                )
                OR
                (
                    bitdi_meglub IS NOT NULL
                    AND bitdi_meglub > 0
                )
                OR
                (
                    qalib_id IS NOT NULL
                    AND qalib_id >= 0
                )
            )

        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt_geri_duel->execute([
        ':my_id' => $my_id,
        ':son_gosterilen_duel_id' => $son_gosterilen_duel_id
    ]);

    $geri_duel = $stmt_geri_duel->fetch(PDO::FETCH_ASSOC);



if ($geri_duel) {

    /*
     * Bu duel artıq nəticə göstəriləcək kimi SESSION-da qeyd olunur.
     * Beləliklə istifadəçi fight.php-dən başqa səhifəyə keçsə belə,
     * eyni nəticə yenidən göstərilməyəcək.
     */
    $_SESSION['son_gosterilen_duel_id'] =
        (int)$geri_duel['id'];

    header(
        "Location: fight.php?duel_id="
        . (int)$geri_duel['id']
    );

    exit;
             }
         }

/*
==========================================================
 İSTİFADƏÇİNİN ONLINE VAXTINI YENİLƏ
==========================================================
*/

/*
==========================================================
 ENERJİ SİSTEMİ
==========================================================
*/

$enerjı_max = 50;

$user_id = (int)$_SESSION['user_id'];

$indi = time();

$enerjı = isset($user['enerjı'])
    ? (int)$user['enerjı']
    : 0;

$last_energy_time = isset($user['last_energy_time'])
    ? (int)$user['last_energy_time']
    : 0;


/*
==========================================================
 İLK DƏFƏDİRƏSƏ ENERJİ VAXTIN YARAT
==========================================================
*/

if ($last_energy_time <= 0) {

    $last_energy_time = $indi;

    $stmt_energy_time = $pdo->prepare("
        UPDATE users
        SET last_energy_time = :last_time
        WHERE id = :user_id
        LIMIT 1
    ");

    $stmt_energy_time->execute([
        ':last_time' => $last_energy_time,
        ':user_id' => $user_id
    ]);

}


/*
==========================================================
 HƏR 1 DƏQİQƏYƏ +1 ENERJİ
==========================================================
*/

elseif ($enerjı < $enerjı_max) {

    $keçən_dəqiqələr = intdiv(
        max(0, $indi - $last_energy_time),
        60
    );

    if ($keçən_dəqiqələr > 0) {

        $yeni_enerjı = min(
            $enerjı_max,
            $enerjı + $keçən_dəqiqələr
        );

        $yeni_last_time =
            $last_energy_time + ($keçən_dəqiqələr * 60);

        if ($yeni_enerjı >= $enerjı_max) {
            $yeni_last_time = $indi;
        }

        $stmt_energy_update = $pdo->prepare("
            UPDATE users
            SET
                enerjı = :enerji,
                last_energy_time = :last_time
            WHERE id = :user_id
            LIMIT 1
        ");

        $stmt_energy_update->execute([
            ':enerji' => $yeni_enerjı,
            ':last_time' => $yeni_last_time,
            ':user_id' => $user_id
        ]);

        $enerjı = $yeni_enerjı;

        /*
         * $user massivini də yenilə ki,
         * səhifənin qalan hissəsi aktual enerjini görsün.
         */
        $user['enerjı'] = $yeni_enerjı;
        $user['last_energy_time'] = $yeni_last_time;
    }

}


/*
==========================================================
 ENERJİ 50-DİRSƏ TIMERİ İNDİYƏ SIFIRLA
==========================================================
*/

else {

    $stmt_energy_update = $pdo->prepare("
        UPDATE users
        SET last_energy_time = :last_time
        WHERE id = :user_id
        LIMIT 1
    ");

    $stmt_energy_update->execute([
        ':last_time' => $indi,
        ':user_id' => $user_id
    ]);

    $last_energy_time = $indi;

    $user['last_energy_time'] = $indi;
}


/*
==========================================================
 HAZIR ENERJİ DƏYƏRLƏRİ
==========================================================
*/

$menim_enerjim = $enerjı;


/*
==========================================================
 SƏVİYYƏ SİSTEMİ
==========================================================
*/

require_once __DIR__ . '/seviyyeler.php';


/*
==========================================================
 TƏCRÜBƏNİ TƏHLÜKƏSİZ GÖTÜR
==========================================================
*/

$tecrube = isset($user['oyuncunun_tecrubesi'])
    ? (int)$user['oyuncunun_tecrubesi']
    : 0;


/*
==========================================================
 SƏVİYYƏNİ HESABLA
==========================================================
*/

$yeni_seviyyye = tecrubeye_gore_seviyye($tecrube);


/*
==========================================================
 MYSQL-DA SƏVİYYƏNİ YENİLƏ
==========================================================
*/

$indiki_seviyyye = isset($user['oyuncunun_seviyyesi'])
    ? (int)$user['oyuncunun_seviyyesi']
    : 1;


if ($indiki_seviyyye !== $yeni_seviyyye) {

    $stmt_seviyye = $pdo->prepare("
        UPDATE users
        SET oyuncunun_seviyyesi = :seviyye
        WHERE id = :id
    ");

    $stmt_seviyye->execute([
        ':seviyye' => $yeni_seviyyye,
        ':id' => (int)$_SESSION['user_id']
    ]);

    $user['oyuncunun_seviyyesi'] = $yeni_seviyyye;
}


/*
==========================================================
 SƏVİYYƏ DƏYİŞƏN ZAMAN DƏYİŞƏN DƏYİŞƏN
==========================================================
*/

$seviyye = isset($user['oyuncunun_seviyyesi'])
    ? (int)$user['oyuncunun_seviyyesi']
    : 1;


/*
==========================================================
 PROGRESS HESABLANMASI
==========================================================
*/

$progress = 0;

$baslangic_tecrubesi = 0;
$bitis_tecrubesi = 0;


/*
 * ƏSAS YOXLAMA:
 * səviyyə həqiqətən mövcuddurmu?
 */

if (
    isset($seviyye_tecrubeleri) &&
    is_array($seviyye_tecrubeleri) &&
    isset($seviyye_tecrubeleri[$seviyye])
) {

    $baslangic_tecrubesi =
        isset($seviyye_tecrubeleri[$seviyye]['min'])
            ? (int)$seviyye_tecrubeleri[$seviyye]['min']
            : 0;

    $bitis_tecrubesi =
        isset($seviyye_tecrubeleri[$seviyye]['max'])
            ? (int)$seviyye_tecrubeleri[$seviyye]['max']
            : 0;


    if ($bitis_tecrubesi > $baslangic_tecrubesi) {

        $progress = (
            ($tecrube - $baslangic_tecrubesi) /
            ($bitis_tecrubesi - $baslangic_tecrubesi)
        ) * 100;
    }
}


/*
==========================================================
 PROGRESS 0-100 ARASINDA OLSUN
==========================================================
*/

$progress = max(0, min(100, $progress));

$progress = round($progress);
/* =========================================================
   İSTİFADƏÇİNİN LOGIN ADI
========================================================= */

$user_login = isset($user['login'])
    ? $user['login']
    : '';
/* =========================================================
   GƏLƏN DOSTLUQ SORĞULARI
========================================================= */

$stmt_dostluq = $pdo->prepare("
    SELECT COUNT(*)
    FROM dostluq
    WHERE alan_id = :my_id
      AND status = 0
");

$stmt_dostluq->execute([
    ':my_id' => (int)$_SESSION['user_id']
]);

$dostluq_sayi = (int)$stmt_dostluq->fetchColumn();

/* =========================================================
   VIP İSTİFADƏÇİ ADI RƏNGİ
========================================================= */

if ((int)($user['vip'] ?? 0) === 1) {
    $user_ad_reng = '#0F7100';
} else {
    $user_ad_reng = 'green';
}

?>
<script>
(function () {

    function aktivliyiYenile() {

        fetch('heartbeat.php', {
            method: 'GET',
            cache: 'no-store'
        }).catch(function () {
        });

    }

    aktivliyiYenile();

    setInterval(
        aktivliyiYenile,
        3000
    );

})();
</script>
