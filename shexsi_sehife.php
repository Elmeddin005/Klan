<?php
session_start();

require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   GİRİŞ YOXLAMASI
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];


/* =========================================================
   İSTİFADƏÇİ MƏLUMATLARI
========================================================= */

$stmt_user = $pdo->prepare("
    SELECT
        id,
        login,
        ad,
        qızıl,
        brılyant,
        enerjı,
        oyuncunun_seviyyesi,
        oyuncunun_tecrubesi,
        mesaj_qebulu,
        mob_qebulu
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_user->execute([
    ':id' => $my_id
]);

$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit('İstifadəçi tapılmadı.');
}


/* =========================================================
   DƏYİŞƏNLƏR
========================================================= */

$go = $_GET['go'] ?? '';

$mesaj_qebulu = (int)$user['mesaj_qebulu'];
$mob_qebulu   = (int)$user['mob_qebulu'];

if ($mesaj_qebulu !== 1) {
    $mesaj_qebulu = 0;
}

if ($mob_qebulu !== 1) {
    $mob_qebulu = 0;
}


/* =========================================================
   İQNOR SAYI
========================================================= */

$stmt_iqnor_sayi = $pdo->prepare("
    SELECT COUNT(*)
    FROM iqnor
    WHERE user_id = :my_id
");

$stmt_iqnor_sayi->execute([
    ':my_id' => $my_id
]);

$iqnor_sayi = (int)$stmt_iqnor_sayi->fetchColumn();


/* =========================================================
   YENİ OXUNMAMIŞ MESAJ SAYI
========================================================= */

$stmt_new_message = $pdo->prepare("
    SELECT COUNT(*)
    FROM mesajlar
    WHERE mesaji_alan_nik = :my_id
      AND oxundu = 0
");

$stmt_new_message->execute([
    ':my_id' => $my_id
]);

$new_message_count = (int)$stmt_new_message->fetchColumn();


/* =========================================================
MƏKTUBLARIN ÜMUMİ SAYI
GƏLƏN + GEDƏN / ARXİV
========================================================= */

$stmt_mektub_sayi = $pdo->prepare("
    SELECT COUNT(*)
    FROM mesajlar
    WHERE
        (
            mesaji_alan_nik = :my_id
            AND silindi_alan = 0
        )
        OR
        (
            mesaji_gonderen_nik = :my_id
            AND silindi_gonderen = 0
        )
");

$stmt_mektub_sayi->execute([
    ':my_id' => $my_id
]);

$mektub_sayi = (int)$stmt_mektub_sayi->fetchColumn();



/* =========================================================
   QƏBUL EDİLMİŞ DOSTLARIN SAYI
========================================================= */

$stmt_dost_sayi = $pdo->prepare("
    SELECT COUNT(*)
    FROM dostluq
    WHERE status = 1
      AND (
            gonderen_id = :my_id
            OR alan_id = :my_id
          )
");

$stmt_dost_sayi->execute([
    ':my_id' => $my_id
]);

$dost_sayi = (int)$stmt_dost_sayi->fetchColumn();


/* =========================================================
   GƏLƏN DOSTLUQ SORĞULARININ SAYI
========================================================= */

$stmt_gelen_dostluq = $pdo->prepare("
    SELECT COUNT(*)
    FROM dostluq
    WHERE alan_id = :my_id
      AND status = 0
");

$stmt_gelen_dostluq->execute([
    ':my_id' => $my_id
]);

$gelen_dostluq_sayi = (int)$stmt_gelen_dostluq->fetchColumn();


/* =========================================================
   ONLINE VAXTI
========================================================= */

$stmt_online = $pdo->prepare("
    UPDATE users
    SET online_oyuncu_vaxti = :vaxt
    WHERE id = :id
");

$stmt_online->execute([
    ':vaxt' => time(),
    ':id' => $my_id
]);


/* =========================================================
   ONLINE SAYI
========================================================= */

$stmt_online_count = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE online_oyuncu_vaxti >= :vaxt
");

$stmt_online_count->execute([
    ':vaxt' => time() - 180
]);

$online_sayi = (int)$stmt_online_count->fetchColumn();


/* =========================================================
   SƏVİYYƏ VƏ PROGRESS
========================================================= */

$oyuncu_seviyyesi = (int)$user['oyuncunun_seviyyesi'];

if ($oyuncu_seviyyesi < 1) {
    $oyuncu_seviyyesi = 1;
}


/*
   Əgər user_data.php artıq $progress hesablayırsa,
   onu istifadə edirik.
*/

if (!isset($progress)) {
    $progress = 0;
}

$progress = (int)$progress;

if ($progress < 0) {
    $progress = 0;
}

if ($progress > 100) {
    $progress = 100;
}


/* =========================================================
   LƏQƏB DƏYİŞMƏ
========================================================= */

if (
    $go === 'N_ok' &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $yeni_leqeb = trim($_POST['message'] ?? '');

    if ($yeni_leqeb === '') {

        $_SESSION['leqeb_netice'] =
            'Ləqəbinizi daxil edin.';

        header("Location: shexsi_sehife.php?go=deyish_netice");
        exit;
    }

    $leqeb_uzunlugu = mb_strlen($yeni_leqeb, 'UTF-8');

    if ($leqeb_uzunlugu < 3) {

        $_SESSION['leqeb_netice'] =
            'Ləqəb minimum 3 simvol olmalıdır.';

        header("Location: shexsi_sehife.php?go=deyish_netice");
        exit;
    }

    if ($leqeb_uzunlugu > 15) {

        $_SESSION['leqeb_netice'] =
            'Ləqəb maksimum 15 simvol ola bilər.';

        header("Location: shexsi_sehife.php?go=deyish_netice");
        exit;
    }

    if (
        !preg_match(
            '/^[a-zA-Z0-9əƏıİöÖüÜşŞçÇğĞ\s*_-]+$/u',
            $yeni_leqeb
        )
    ) {

        $_SESSION['leqeb_netice'] =
            'Ləqəbdə yalnız icazə verilən simvollardan istifadə etmək olar.';

        header("Location: shexsi_sehife.php?go=deyish_netice");
        exit;
    }

    $stmt_leqeb = $pdo->prepare("
        SELECT id
        FROM users
        WHERE login = :login
          AND id != :my_id
        LIMIT 1
    ");

    $stmt_leqeb->execute([
        ':login' => $yeni_leqeb,
        ':my_id' => $my_id
    ]);

    if ($stmt_leqeb->fetchColumn()) {

        $_SESSION['leqeb_netice'] =
            'Bu ləqəb artıq istifadə olunur.';

        header("Location: shexsi_sehife.php?go=deyish_netice");
        exit;
    }

    $brilliant = (int)$user['brılyant'];

    if ($brilliant < 50) {

        $_SESSION['leqeb_netice'] =
            'Ləqəbinizi dəyişmək üçün 50 Brilliant lazımdır.';

        header("Location: shexsi_sehife.php?go=deyish_netice");
        exit;
    }

    $stmt_deyis = $pdo->prepare("
        UPDATE users
        SET
            login = :login,
            brılyant = brılyant - 50
        WHERE id = :id
          AND brılyant >= 50
        LIMIT 1
    ");

    $stmt_deyis->execute([
        ':login' => $yeni_leqeb,
        ':id' => $my_id
    ]);

    if ($stmt_deyis->rowCount() > 0) {

        $_SESSION['leqeb_netice'] =
            'Ləqəbiniz uğurla dəyişdirildi.';

    } else {

        $_SESSION['leqeb_netice'] =
            'Ləqəb dəyişdirilə bilmədi.';
    }

    header("Location: shexsi_sehife.php?go=deyish_netice");
    exit;
}


/* =========================================================
   QURĞULARI YADDA SAXLA
========================================================= */

if (
    $go === 'qurgu_ok' &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save'
) {

    $aa = isset($_POST['aa'])
        ? (int)$_POST['aa']
        : 0;

    $bb = isset($_POST['bb'])
        ? (int)$_POST['bb']
        : 0;

    if ($aa !== 0 && $aa !== 1) {
        $aa = 0;
    }

    if ($bb !== 0 && $bb !== 1) {
        $bb = 0;
    }

    $stmt_qurgu = $pdo->prepare("
        UPDATE users
        SET
            mesaj_qebulu = :mesaj_qebulu,
            mob_qebulu = :mob_qebulu
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_qurgu->execute([
        ':mesaj_qebulu' => $aa,
        ':mob_qebulu' => $bb,
        ':id' => $my_id
    ]);

    $_SESSION['qurgu_netice'] =
        'Qurğular uğurla dəyişdirildi.';

    header("Location: shexsi_sehife.php?go=qurgu_netice");
    exit;
}


/* =========================================================
   1-11 MÖVQE DƏYİŞMƏ
========================================================= */

if ($go === 'Movge_ok') {

    $stmt_movqe = $pdo->prepare("
        SELECT movqe, oyuncunun_seviyyesi
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_movqe->execute([
        ':id' => $my_id
    ]);

    $menim_melumat = $stmt_movqe->fetch(PDO::FETCH_ASSOC);

    if (!$menim_melumat) {

        $_SESSION['movqe_netice'] =
            'İstifadəçi məlumatı tapılmadı.';

        header("Location: shexsi_sehife.php?go=Movge_netice");
        exit;
    }

    $cari_movqe = (int)$menim_melumat['movqe'];
    $cari_seviyye = (int)$menim_melumat['oyuncunun_seviyyesi'];

    if (
        $cari_seviyye >= 12 &&
        $cari_seviyye <= 14
    ) {

        $_SESSION['movqe_netice'] =
            '12, 13 və 14-cü mərhələlərdə mövqe yalnız "Mərhələ dəyiş" ilə dəyişdirilə bilər.';

        header("Location: shexsi_sehife.php?go=Movge_netice");
        exit;
    }

    $stmt_say = $pdo->prepare("
        SELECT
            SUM(CASE WHEN movqe = 1 THEN 1 ELSE 0 END) AS vampir,
            SUM(CASE WHEN movqe = 2 THEN 1 ELSE 0 END) AS insan
        FROM users
        WHERE oyuncunun_seviyyesi = :seviyye
    ");

    $stmt_say->execute([
        ':seviyye' => $cari_seviyye
    ]);

    $say = $stmt_say->fetch(PDO::FETCH_ASSOC);

    $insan_sayi = (int)($say['insan'] ?? 0);
    $vampir_sayi = (int)($say['vampir'] ?? 0);

    if ($cari_movqe === 2) {

        $kechid_olur =
            ($vampir_sayi === 0 ||
             $insan_sayi > $vampir_sayi);

        if ($kechid_olur) {

            $stmt_deyis = $pdo->prepare("
                UPDATE users
                SET movqe = 1
                WHERE id = :id
                  AND movqe = 2
                  AND oyuncunun_seviyyesi = :seviyye
                LIMIT 1
            ");

            $stmt_deyis->execute([
                ':id' => $my_id,
                ':seviyye' => $cari_seviyye
            ]);

            $_SESSION['movqe_netice'] =
                ($stmt_deyis->rowCount() > 0)
                ? 'Mövqeyiniz dəyişdirildi.'
                : 'Mövqenizi dəyişmək mümkün olmadı.';

        } else {

            $_SESSION['movqe_netice'] = 'Mövqeyinizi dəyişmək mümkün deyil. İnsan mövqeyindən 1 nəfər mövqeyini dəyişməlidir!';
        }

    } elseif ($cari_movqe === 1) {

        $kechid_olur =
            ($insan_sayi === 0 ||
             $vampir_sayi > $insan_sayi);

        if ($kechid_olur) {

            $stmt_deyis = $pdo->prepare("
                UPDATE users
                SET movqe = 2
                WHERE id = :id
                  AND movqe = 1
                  AND oyuncunun_seviyyesi = :seviyye
                LIMIT 1
            ");

            $stmt_deyis->execute([
                ':id' => $my_id,
                ':seviyye' => $cari_seviyye
            ]);

            $_SESSION['movqe_netice'] =
                ($stmt_deyis->rowCount() > 0)
                ? 'Mövqeyiniz dəyişdirildi.'
                : 'Mövqenizi dəyişmək mümkün olmadı.';

        } else {

            $_SESSION['movqe_netice'] = 'Qadaga';
        }

    } else {

        $_SESSION['movqe_netice'] =
            'Mövqeniz düzgün müəyyən edilmədi.';
    }

    header("Location: shexsi_sehife.php?go=Movge_netice");
    exit;
}


/* =========================================================
   12-14 MƏRHƏLƏ MÖVQE DƏYİŞMƏ
   300 BRİLLİANT
========================================================= */

if ($go === 'merhele_deyish') {

    $stmt_merhele_info = $pdo->prepare("
        SELECT movqe, oyuncunun_seviyyesi, brılyant
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_merhele_info->execute([
        ':id' => $my_id
    ]);

    $merhele_info =
        $stmt_merhele_info->fetch(PDO::FETCH_ASSOC);

    if (!$merhele_info) {

        $_SESSION['movqe_netice'] =
            'İstifadəçi məlumatı tapılmadı.';

        header("Location: shexsi_sehife.php?go=Movge_netice");
        exit;
    }

    $cari_movqe =
        (int)$merhele_info['movqe'];

    $cari_seviyye =
        (int)$merhele_info['oyuncunun_seviyyesi'];

    $brilliant =
        (int)$merhele_info['brılyant'];

    if (
        $cari_seviyye < 12 ||
        $cari_seviyye > 14
    ) {

        $_SESSION['movqe_netice'] =
            'Bu əməliyyat yalnız 12, 13 və 14-cü mərhələlər üçün keçərlidir.';

        header("Location: shexsi_sehife.php?go=Movge_netice");
        exit;
    }

    if ($brilliant < 300) {

        $_SESSION['movqe_netice'] =
            'Mövqeni dəyişmək üçün 300 Brilliant lazımdır.';

        header("Location: shexsi_sehife.php?go=Movge_netice");
        exit;
    }

    $stmt_say = $pdo->prepare("
        SELECT
            SUM(CASE WHEN movqe = 1 THEN 1 ELSE 0 END) AS vampir,
            SUM(CASE WHEN movqe = 2 THEN 1 ELSE 0 END) AS insan
        FROM users
        WHERE oyuncunun_seviyyesi = :seviyye
    ");

    $stmt_say->execute([
        ':seviyye' => $cari_seviyye
    ]);

    $say = $stmt_say->fetch(PDO::FETCH_ASSOC);

    $vampir_sayi = (int)($say['vampir'] ?? 0);
    $insan_sayi = (int)($say['insan'] ?? 0);

    if ($cari_movqe === 2) {

        $kechid_olur =
            ($vampir_sayi === 0 ||
             $insan_sayi > $vampir_sayi);

        if (!$kechid_olur) {

            $_SESSION['movqe_netice'] =
                'Mövqeyinizi dəyişmək mümkün deyil. Vampir mövqeyindən 1 nəfər mövqeyini dəyişməlidir!';

            header("Location: shexsi_sehife.php?go=Movge_netice");
            exit;
        }

        $stmt_deyis = $pdo->prepare("
            UPDATE users
            SET
                movqe = 1,
                brılyant = brılyant - 300
            WHERE id = :id
              AND movqe = 2
              AND oyuncunun_seviyyesi = :seviyye
              AND brılyant >= 300
            LIMIT 1
        ");

        $stmt_deyis->execute([
            ':id' => $my_id,
            ':seviyye' => $cari_seviyye
        ]);

        $_SESSION['movqe_netice'] =
            ($stmt_deyis->rowCount() > 0)
            ? 'Mövqeyiniz dəyişdirildi. Artıq Vampirsiniz.'
            : 'Mövqeyinizi dəyişmək mümkün olmadı.';

    } elseif ($cari_movqe === 1) {

        $kechid_olur =
            ($insan_sayi === 0 ||
             $vampir_sayi > $insan_sayi);

        if (!$kechid_olur) {

            $_SESSION['movqe_netice'] =
                'Mövqeyinizi dəyişmək mümkün deyil. İnsan mövqeyindən 1 nəfər mövqeyini dəyişməlidir!';

            header("Location: shexsi_sehife.php?go=Movge_netice");
            exit;
        }

        $stmt_deyis = $pdo->prepare("
            UPDATE users
            SET
                movqe = 2,
                brılyant = brılyant - 300
            WHERE id = :id
              AND movqe = 1
              AND oyuncunun_seviyyesi = :seviyye
              AND brılyant >= 300
            LIMIT 1
        ");

        $stmt_deyis->execute([
            ':id' => $my_id,
            ':seviyye' => $cari_seviyye
        ]);

        $_SESSION['movqe_netice'] =
            ($stmt_deyis->rowCount() > 0)
            ? 'Mövqeyiniz dəyişdirildi. Artıq İnsansınız.'
            : 'Mövqenizi dəyişmək mümkün olmadı.';

    } else {

        $_SESSION['movqe_netice'] =
            'Mövqeniz düzgün müəyyən edilmədi.';
    }

    header("Location: shexsi_sehife.php?go=Movge_netice");
    exit;
}

?>

<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL">

<meta
name="keywords"
content="klan.az, azgame, azgame.biz, online oyun">

<meta
name="description"
content="Azerbaycanda ilk Mobil Online oyunu.">

<link rel="stylesheet" href="css.css">

<meta
content="text/html; charset=utf-8"
http-equiv="content-type">

<meta
name="viewport"
content="width=device-width; initial-scale=1.0; maximum-scale=3.0;">

<title>Profil | klan.az</title>

<script>
function goGeri() {
    window.history.back();
}
</script>

</head>

<body>

<div
class="main"
style="word-wrap:break-word;"
>


<!-- =====================================================
     HEADER
===================================================== -->

<div id="header">

<a href="menu.php?">

<img
src="img/logo.png"
alt=""
>

</a>

<div class="icons"></div>

<div class="main_foot">

<div class="grey">

<img
src="img/coin.png"
title="Qızıl"
alt=""
>

<?php echo (int)$user['qızıl']; ?>


<img
src="img/brill.png"
title="Brilliant"
alt=""
>

<?php echo (int)$user['brılyant']; ?>


<img
src="img/energy.png"
title="Enerji"
alt=""
>

<?php echo (int)$user['enerjı']; ?>


<!-- =====================================================
     YENİ MƏKTUB
===================================================== -->

<?php if ($new_message_count > 0): ?>

<a href="mektublar.php?">

<img
src="img/mektub.gif"
title="Məktub"
alt="Məktub"
>

</a>

(<?php echo $new_message_count; ?>)

<?php endif; ?>


<!-- =====================================================
     GƏLƏN DOSTLUQ SORĞUSU
===================================================== -->

<?php if ($gelen_dostluq_sayi > 0): ?>

<a href="dostlar.php?">

<img
src="muxtelif/dost_pilus.png"
title="Yeni dostluq sorğusu"
alt="Dost"
>

</a>

(<?php echo $gelen_dostluq_sayi; ?>)

<?php endif; ?>

</div>

</div>

</div>


<div class="space"></div>


<div
style="background:none repeat scroll 0 0 #888686;height:1px;"
></div>


<!-- =====================================================
     TƏCRÜBƏ
===================================================== -->

<div class="fl b exp_count">

<div style="margin-top:-2px;">

<span style="color:#ff3333">

<b><?php echo $progress; ?>%</b>

</span>

</div>

</div>


<div class="experience">

<div class="exp_bg">

<div class="exp_left fl"></div>

<div class="exp_right fr"></div>

<div
style="width:<?php echo $progress; ?>%;height:10px;"
>

<div class="exp_line"></div>

<div class="exp_point"></div>

</div>

</div>

</div>


<div
style="background:none repeat scroll 0 0 #888686;height:1px;"
></div>


<div class="info">


<?php

/* =====================================================
   QURĞULAR
===================================================== */

if ($go === 'qurgu'):

?>

<form
method="post"
action="shexsi_sehife.php?go=qurgu_ok"
>

<b>Mektub qebulu:</b>

<br/>

<select name="aa">

<option
value="0"
<?php echo ($mesaj_qebulu === 0) ? 'selected' : ''; ?>
>
Hamıdan
</option>

<option
value="1"
<?php echo ($mesaj_qebulu === 1) ? 'selected' : ''; ?>
>
Dostlardan
</option>

</select>

<br/>
<br/>

<b>Döyüşəcəyim moblar:</b>

<br/>

<select name="bb">

<option
value="0"
<?php echo ($mob_qebulu === 0) ? 'selected' : ''; ?>
>

<?php

echo $oyuncu_seviyyesi
. '-'
. ($oyuncu_seviyyesi + 1)
. '-'
. ($oyuncu_seviyyesi + 2);

?>

</option>

<option
value="1"
<?php echo ($mob_qebulu === 1) ? 'selected' : ''; ?>
>
Hamısı
</option>

</select>

<br/>
<br/>

<input
type="hidden"
name="action"
value="save"
>

<input
type="submit"
class="button"
value="Ok"
>

<br/>

</form>


<?php

/* =====================================================
   QURĞU NƏTİCƏ
===================================================== */

elseif ($go === 'qurgu_netice'):

?>

<b>

<?php

echo htmlspecialchars(
    $_SESSION['qurgu_netice']
    ?? 'Qurğular uğurla dəyişdirildi.',
    ENT_QUOTES,
    'UTF-8'
);

unset($_SESSION['qurgu_netice']);

?>

</b>

<br/>
<br/>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()"
>

<br/>


<?php

/* =====================================================
   LƏQƏB DƏYİŞ
===================================================== */

elseif ($go === 'deyish'):

?>

<b>
Ləqəbinizi dəyişmək 50 Brilliant dəyərindədir.
</b>

<br/>
<br/>

<b>Yeni Ləqəb:</b>

<br/>

<form
method="post"
action="shexsi_sehife.php?go=N_ok"
>

<input
type="text"
name="message"
maxlength="15"
>

<br/>

<input
type="submit"
class="button"
value="Dəyiş"
>

</form>

<hr>


<?php

/* =====================================================
   LƏQƏB NƏTİCƏ
===================================================== */

elseif ($go === 'deyish_netice'):

?>

<b>

<?php

echo htmlspecialchars(
    $_SESSION['leqeb_netice']
    ?? '',
    ENT_QUOTES,
    'UTF-8'
);

unset($_SESSION['leqeb_netice']);

?>

</b>

<br/>
<br/>

<input
type="button"
class="button"
value="Geri"
onclick="goGeri()"
>

<br/>


<?php

/* =====================================================
   MÖVQE DƏYİŞ
===================================================== */

elseif ($go === 'm_deyish'):

$stmt_menim = $pdo->prepare("
    SELECT movqe, oyuncunun_seviyyesi
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_menim->execute([
    ':id' => $my_id
]);

$menim = $stmt_menim->fetch(PDO::FETCH_ASSOC);

if (!$menim):

    echo 'İstifadəçi məlumatı tapılmadı.';

else:

    $m_movqe =
        (int)$menim['movqe'];

    $m_seviyye =
        (int)$menim['oyuncunun_seviyyesi'];


    $stmt_say = $pdo->prepare("
        SELECT
            SUM(CASE WHEN movqe = 2 THEN 1 ELSE 0 END) AS insan,
            SUM(CASE WHEN movqe = 1 THEN 1 ELSE 0 END) AS vampir
        FROM users
        WHERE oyuncunun_seviyyesi = :seviyye
    ");

    $stmt_say->execute([
        ':seviyye' => $m_seviyye
    ]);

    $say = $stmt_say->fetch(PDO::FETCH_ASSOC);

    $insan_sayi =
        (int)($say['insan'] ?? 0);

    $vampir_sayi =
        (int)($say['vampir'] ?? 0);

?>

<br/>

<b>Mərhələniz</b> /
<b>Keçid etmək üçün mövqe</b>

<br/>
<br/>

<?php

/* =====================================================
   MƏRHƏLƏLƏR 1-14
===================================================== */

for ($i = 1; $i <= 14; $i++) {

    $stmt_i = $pdo->prepare("
        SELECT
            SUM(CASE WHEN movqe = 2 THEN 1 ELSE 0 END) AS insan,
            SUM(CASE WHEN movqe = 1 THEN 1 ELSE 0 END) AS vampir
        FROM users
        WHERE oyuncunun_seviyyesi = :seviyye
    ");

    $stmt_i->execute([
        ':seviyye' => $i
    ]);

    $say_i =
        $stmt_i->fetch(PDO::FETCH_ASSOC);

    $insan =
        (int)($say_i['insan'] ?? 0);

    $vampir =
        (int)($say_i['vampir'] ?? 0);


    if (
        $insan === 0 &&
        $vampir > 0
    ) {

        $goster = 'İnsan';

    } elseif (
        $vampir === 0 &&
        $insan > 0
    ) {

        $goster = 'Vampir';

    } elseif (
        $insan > 0 &&
        $vampir > 0
    ) {

        if ($i === $m_seviyye) {

            $goster =
                ($m_movqe === 2)
                ? 'İnsan'
                : 'Vampir';

        } elseif ($i <= 8) {

            $goster = 'İnsan';

        } else {

            $goster = 'Vampir';
        }

    } else {

        $goster =
            ($i <= 8)
            ? 'İnsan'
            : 'Vampir';
    }

    echo 'Mərhələ[' . $i . '] / ' . $goster;

    echo '<br>';
}

?>

<br/>

<?php if ($m_seviyye >= 12 && $m_seviyye <= 14): ?>

<b>
12, 13 və 14-cü mərhələlərdə mövqe dəyişmək yalnız 300 Brilliant ilə mümkündür.
</b>

<br/>
<br/>

<a href="shexsi_sehife.php?go=merhele_deyish">
Mövqeyini dəyiş
</a>


<?php else: ?>

<?php if ($m_movqe === 2): ?>

<?php if ($vampir_sayi === 0): ?>

Sizin mərhələnizə bərabər olan
<b>Vampir</b> mövqeyi boşdur.

<br/>

Siz Vampir mövqeyinə keçmək istəyirsiz?

<br/>

<a href="shexsi_sehife.php?go=Movge_ok">
He
</a>

<?php elseif ($insan_sayi > $vampir_sayi): ?>

Siz mövqeyinizi dəyişib
<b>Vampir</b> olmaq üçün
brilliant tələb olunmur.

<br/>
<br/>

Siz mövqeyinizi dəyişmək istəyirsiz?

<br/>

<a href="shexsi_sehife.php?go=Movge_ok">
He
</a>

<?php else: ?>

<u>
Mövqeyinizi dəyişmək üçün mütləq sizin mərhələnizə bərabər olan
<b>Vampir</b> mövqeyi dəyişməlidir!
</u>

<?php endif; ?>


<?php elseif ($m_movqe === 1): ?>

<?php if ($insan_sayi === 0): ?>

Sizin mərhələnizə bərabər olan
<b>İnsan</b> mövqeyi boşdur.

<br/>

Siz İnsan mövqeyinə keçmək istəyirsiz?

<br/>

<a href="shexsi_sehife.php?go=Movge_ok">
He
</a>

<?php elseif ($vampir_sayi > $insan_sayi): ?>

Siz mövqeyinizi dəyişib
<b>İnsan</b> olmaq üçün
brilliant tələb olunmur.

<br/>
<br/>

Siz mövqeyinizi dəyişmək istəyirsiz?

<br/>

<a href="shexsi_sehife.php?go=Movge_ok">
He
</a>

<?php else: ?>

<u>
Mövqeyinizi dəyişmək üçün mütləq sizin mərhələnizə bərabər olan
<b>İnsan</b> mövqeyi dəyişməlidir!
</u>

<?php endif; ?>


<?php else: ?>

<span style="color: blue; font-weight: bold; font-style: italic;">
    Movqeyiniz Neytral Olduğu üçün dəyişə bilməzsiniz!
</span>
<br><br>

<?php endif; ?>

<?php endif; ?>

<?php endif; ?>


<?php

/* =====================================================
   MÖVQE NƏTİCƏ
===================================================== */

elseif ($go === 'Movge_netice'):

/* =====================================================
   İSTİFADƏÇİNİN MÖVQƏSİNİ VƏ MƏRHƏLƏSİNİ GƏTİR
===================================================== */

$stmt_netice_user = $pdo->prepare("
    SELECT
        movqe,
        oyuncunun_seviyyesi
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_netice_user->execute([
    ':id' => $my_id
]);

$netice_user = $stmt_netice_user->fetch(PDO::FETCH_ASSOC);


/* =====================================================
   İSTİFADƏÇİ TAPILMADI
===================================================== */

if (!$netice_user) {

    $netice_mesaj = 'İstifadəçi məlumatı tapılmadı.';

} else {

    $netice_movqe =
        (int)$netice_user['movqe'];

    $netice_seviyye =
        (int)$netice_user['oyuncunun_seviyyesi'];



        /* =============================================
           İNSAN SAYI
        ============================================= */

        $stmt_insan_netice = $pdo->prepare("
            SELECT COUNT(*)
            FROM users
            WHERE oyuncunun_seviyyesi = :seviyye
              AND movqe = 2
        ");

        $stmt_insan_netice->execute([
            ':seviyye' => $netice_seviyye
        ]);

        $netice_insan_sayi =
            (int)$stmt_insan_netice->fetchColumn();


        /* =============================================
           VAMPİR SAYI
        ============================================= */

        $stmt_vampir_netice = $pdo->prepare("
            SELECT COUNT(*)
            FROM users
            WHERE oyuncunun_seviyyesi = :seviyye
              AND movqe = 1
        ");

        $stmt_vampir_netice->execute([
            ':seviyye' => $netice_seviyye
        ]);

        $netice_vampir_sayi =
            (int)$stmt_vampir_netice->fetchColumn();


        /* =============================================
           İNSAN → VAMPİR
        ============================================= */

        if ($netice_movqe === 2) {

            if (
                $netice_vampir_sayi > 0 &&
                $netice_insan_sayi <= $netice_vampir_sayi
            ) {

                $netice_mesaj =
                    'Mövqeyinizi dəyişmək mümkün deyil. Vampir mövqeyindən 1 nəfər mövqeyini dəyişməlidir!';

            } else {

                $netice_mesaj =
                    $_SESSION['movqe_netice']
                    ?? 'Mövqeyiniz dəyişdirildi.';
            }


        /* =============================================
           VAMPİR → İNSAN
        ============================================= */

        } elseif ($netice_movqe === 1) {

            if (
                $netice_insan_sayi > 0 &&
                $netice_vampir_sayi <= $netice_insan_sayi
            ) {

                $netice_mesaj =
                    'Mövqeyinizi dəyişmək mümkün deyil. İnsan mövqeyindən 1 nəfər mövqeyini dəyişməlidir!';

            } else {

                $netice_mesaj =
                    $_SESSION['movqe_netice']
                    ?? 'Mövqeyiniz dəyişdirildi.';
            }


        } else {

            $netice_mesaj =
                'Mövqeniz düzgün müəyyən edilmədi.';
        }
    }



/* =====================================================
   SESSION MESAJINI TƏMİZLƏ
===================================================== */

unset($_SESSION['movqe_netice']);

?>

<b>

<?php

echo htmlspecialchars(
    $netice_mesaj,
    ENT_QUOTES,
    'UTF-8'
);

?>

</b>

<br/>
<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<br/>

<?php

/* =====================================================
   ƏSAS ŞƏXSİ SƏHİFƏ
===================================================== */

else:

?>

<b>

<?php

echo htmlspecialchars(
    $user['login'],
    ENT_QUOTES,
    'UTF-8'
);

?>

| şəxsi səhifəniz

</b>

<br/>
<br/>

<b>- Sizin ID:</b>

<?php echo (int)$user['id']; ?>

<br/>

<b>- Mərhələniz:</b>

<?php echo $oyuncu_seviyyesi; ?>

<br/>

<b>- Təcrübəniz:</b>

<?php echo (int)$user['oyuncunun_tecrubesi']; ?>

<br/>

<b>- Qızılınız:</b>

<?php echo (int)$user['qızıl']; ?>

<br/>

<b>- Brilliant:</b>

<?php echo (int)$user['brılyant']; ?>

<br/>
<br/>


<div class="menu">

<br/>


<!-- =====================================================
     MƏKTUBLAR
===================================================== -->

<li>

<a href="mektublar.php?">

<img
src="img/mektub.gif"
alt=""
>

Mektublar
(<?php echo $new_message_count; ?>/<?php echo $mektub_sayi; ?>)

</a>

</li>


<!-- =====================================================
     PROFİL
===================================================== -->

<li>

<a href="profile.php?">

<img
src="muxtelif/profil.png"
alt=""
>

Profiliniz (Məlumatlarım)

</a>

</li>


<!-- =====================================================
     E-MAIL
===================================================== -->

<li>

<a href="mail.php?">

<img
src="muxtelif/mail.png"
alt=""
>

E-mail dəyiş

</a>

</li>


<!-- =====================================================
     DOSTLAR
===================================================== -->

<li>

<a href="dostlar.php?">

<img
src="muxtelif/dost.png"
alt=""
>

Dostlar
(<?php echo $dost_sayi; ?>)

<?php if ($gelen_dostluq_sayi > 0): ?>

<b>
(+<?php echo $gelen_dostluq_sayi; ?> yeni sorğu)
</b>

<?php endif; ?>

</a>

</li>


<!-- =====================================================
     İQNOR
===================================================== -->

<li>

<a href="ignor.php?">

<img
src="muxtelif/iqnor.png"
alt=""
>

İqnor olunanlar
(+<?php echo $iqnor_sayi; ?>)

</a>

</li>


<!-- =====================================================
     AXTARIŞ
===================================================== -->

<li>

<a href="axtar.php?">

<img
src="muxtelif/axtar.png"
alt=""
>

Axtarış Sistemi

</a>

</li>


<!-- =====================================================
     QURĞULAR
===================================================== -->

<li>

<a href="shexsi_sehife.php?go=qurgu">

<img
src="muxtelif/qurgu.png"
alt=""
>

Qurğular

</a>

</li>


<!-- =====================================================
     LƏQƏB DƏYİŞ
===================================================== -->

<li>

<a href="shexsi_sehife.php?go=deyish">

<img
src="muxtelif/deyis.png"
alt=""
>

Ləqəb Dəyiş

</a>

</li>


<!-- =====================================================
     MÖVQE DƏYİŞ
===================================================== -->

<li>

<a href="shexsi_sehife.php?go=m_deyish">

<img
src="muxtelif/deyis1.png"
alt=""
>

Mövqeyini Dəyiş

</a>

</li>


<!-- =====================================================
     SMAYLİKLƏR
===================================================== -->

<li>

<a href="smaylikler.php?">

<img
src="muxtelif/smile.png"
alt=""
>

Smayliklər

</a>

</li>


</div>

<?php endif; ?>


</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">


[<b>
<a href="menu.php?">
Menu
</a>
</b>]

[<b>
<a href="axtar.php?">
Axtarış
</a>
</b>]

[
<a href="forum/mozu2.php?">
Forum
</a>
]

[
<a href="shexsi_sehife.php?">
Qurğular
</a>
]


<br/>
<br/>


<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>


<br/>


<a href="index.php?">

Çıxış
(<?php

echo htmlspecialchars(
    $user['login'],
    ENT_QUOTES,
    'UTF-8'
);

?>)

</a>


<br/>
<br/>


<a href="menu.php?dil=tr">

Türkcə:

<img
alt="türkce"
src="http://macera.az/klan/muxtelif/tr.gif"
title="Türkcə"
>

</a>


<br/>

Sciript name:
Qanlı efsane(modern version)

<br/>

<a
href="http://klanaz.com/klan/"
class="xgame.az"
>

© Klanaz.com 2026

</a>


</div>

</div>

</div>

</div>

</div>


</div>

</body>

</html>
