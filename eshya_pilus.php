<?php

session_start();
$almaz_canta_id = isset($_SESSION['guclendirme_almaz_id'])
    ? (int)$_SESSION['guclendirme_almaz_id']
    : 0;
require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   DAXÄ°L OLMUÅ Ä°STÄ°FADÆÃ‡Ä°
========================================================= */

$my_id = (int)$_SESSION['user_id'];
$almaz_rejimi = isset($_GET['almaz']) && $_GET['almaz'] == '1';

$pilus_ok = isset($_GET['go']) && $_GET['go'] === 'pilus_ok';
$secili_id = isset($_GET['idi']) ? (int)$_GET['idi'] : 0;
$dashlar_ok = isset($_GET['go']) && $_GET['go'] === 'dashlar_ok';

$guclendirme_ok = isset($_GET['go']) && $_GET['go'] === 'ok';
$guclendirme_success = isset($_GET['go']) && $_GET['go'] === 'guclendirme_success';
$guclendirme_xeta = isset($_GET['go']) && $_GET['go'] === 'guclendirme_xeta';
$guclendirme_canta_id = isset($_GET['idi']) ? (int)$_GET['idi'] : 0;

if (
    $guclendirme_ok &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save'
) {

    /*
    =====================================================
    GÜCLƏNDİRMƏ BAŞLAYIR
    =====================================================
    */

    $guclendirme_canta_id = isset($_GET['yenile'])
        ? (int)$_GET['yenile']
        : 0;

    /*
    Session-da saxlanılan almaz
    */
    $almaz_canta_id = isset($_SESSION['guclendirme_almaz_id'])
        ? (int)$_SESSION['guclendirme_almaz_id']
        : 0;

    if ($guclendirme_canta_id <= 0) {
        exit('Əşya tapılmadı.');
    }

    if ($almaz_canta_id <= 0) {
        exit('Almaz seçilməyib.');
    }


    /*
    =====================================================
    REAL UĞURSUZLUQ ÜÇÜN XÜSUSİ İŞARƏ
    =====================================================

    Yalnız random şans uduzanda true olacaq.

    Başqa texniki xəta baş verərsə:
    əşyanın əvvəlki gücləndirməsi SİLİNMƏYƏCƏK.
    */

    $real_guclendirme_ugursuz = false;


    try {

        $pdo->beginTransaction();


        /*
        =====================================================
        ƏŞYANI TAP
        =====================================================
        */

        $stmt_g = $pdo->prepare("
            SELECT
                c.id AS canta_id,
                c.esya_id,
                e.min_zerbe,
                e.max_zerbe,
                e.can,
                e.mudafie
            FROM canta c
            INNER JOIN esyalar e
                ON e.id = c.esya_id
            WHERE c.id = :canta_id
              AND c.user_id = :user_id
              AND c.say > 0
            LIMIT 1
        ");

        $stmt_g->execute([
            ':canta_id' => $guclendirme_canta_id,
            ':user_id'  => $my_id
        ]);

        $g_esya = $stmt_g->fetch(PDO::FETCH_ASSOC);

        if (!$g_esya) {
            throw new Exception('Əşya tapılmadı.');
        }


        /*
        =====================================================
        MÖVCUD GÜCLƏNDİRMƏNİ TAP
        =====================================================
        */

        $stmt_m = $pdo->prepare("
            SELECT *
            FROM esya_guclendirme
            WHERE canta_id = :canta_id
            LIMIT 1
        ");

        $stmt_m->execute([
            ':canta_id' => $guclendirme_canta_id
        ]);

        $movcud = $stmt_m->fetch(PDO::FETCH_ASSOC);


        /*
        =====================================================
        ALMAZI TAP
        =====================================================
        */

        $stmt_almaz_tip = $pdo->prepare("
            SELECT
                c.id AS almaz_canta_id,
                c.say AS almaz_say,
                e.id AS almaz_esya_id,
                e.ad
            FROM canta c
            INNER JOIN esyalar e
                ON e.id = c.esya_id
            WHERE c.id = :almaz_id
              AND c.user_id = :user_id
              AND c.say > 0
            LIMIT 1
        ");

        $stmt_almaz_tip->execute([
            ':almaz_id' => $almaz_canta_id,
            ':user_id'  => $my_id
        ]);

        $almaz_melumat = $stmt_almaz_tip->fetch(PDO::FETCH_ASSOC);

        if (!$almaz_melumat) {
            throw new Exception('Seçilmiş almaz tapılmadı.');
        }

        $almaz_esya_id = (int)$almaz_melumat['almaz_esya_id'];


        /*
        =====================================================
        ALMAZIN PARAMETR FAİZİ
        =====================================================
        */

        if ($almaz_esya_id === 368) {

            // Ağ almaz
            $guc_faizi = 0.05;
            $addim_faizi = 5;

        } elseif ($almaz_esya_id === 369) {

            // Qara almaz
            $guc_faizi = 0.10;
            $addim_faizi = 10;

        } elseif ($almaz_esya_id === 370) {

            // Yaşıl almaz
            $guc_faizi = 0.10;
            $addim_faizi = 10;

        } else {

            throw new Exception(
                'Almaz düzgün seçilməyib.'
            );
        }


        /*
        =====================================================
        NÖVBƏTİ GÜCLƏNDİRMƏNİN NÖMRƏSİ
        =====================================================
        */

        $hazirki_sayi = $movcud
            ? (int)$movcud['guclendirme_sayi']
            : 0;

        $novbeti_guclendirme = $hazirki_sayi + 1;


        /*
        =====================================================
        UĞUR ŞANSI
        =====================================================
        */

        /*
        Yaşıl almaz həmişə 100%
        */

        if ($almaz_esya_id === 370) {

            $ugur_faizi = 100;

        } else {

            if ($novbeti_guclendirme === 1) {

                $ugur_faizi = 100;

            } elseif ($novbeti_guclendirme === 2) {

                $ugur_faizi = 90;

            } elseif ($novbeti_guclendirme === 3) {

                $ugur_faizi = 80;

            } elseif ($novbeti_guclendirme === 4) {

                $ugur_faizi = 50;

            } elseif ($novbeti_guclendirme === 5) {

                $ugur_faizi = 30;

            } else {

                $ugur_faizi = 20;

            }
        }


        /*
        =====================================================
        MAKSİMUM GÜCLƏNDİRMƏ LİMİTİ
        =====================================================
        */

        if ($movcud) {

            $movcud_sayi = (int)$movcud['guclendirme_sayi'];

            $qara_yasil_var =
                (int)$movcud['guclendirme_qara_yasil'];


            /*
            Qara və ya yaşıl bir dəfə istifadə olunubsa
            maksimum 12 gücləndirmə.
            */

            if ($qara_yasil_var > 0) {

                if ($movcud_sayi >= 12) {

                    throw new Exception(
                        'Bu əşya artıq 120% gücləndirilib. Artıq Gücləndirə Bilməzsiniz!'
                    );
                }

            } else {

                /*
                Yalnız ağ almaz istifadə olunubsa
                maksimum 24 gücləndirmə.
                */

                if ($movcud_sayi >= 24) {

                    throw new Exception(
                        'Bu əşya artıq 120% gücləndirilib. Artıq Gücləndirə Bilməzsiniz!'
                    );
                }
            }
        }


        /*
        =====================================================
        REAL UĞURSUZLUQ YOXLAMASI
        =====================================================
        */

        $random = random_int(1, 100);

        if ($random > $ugur_faizi) {

            /*
            Bu artıq həqiqi uğursuz gücləndirmədir.
            */

            $real_guclendirme_ugursuz = true;

            throw new Exception(
                'Gücləndirmə uğursuz oldu.'
            );
        }


        /*
        =====================================================
        UĞURLU GÜCLƏNDİRMƏ
        =====================================================
        */

        if (!$movcud) {

            /*
            =================================================
            İLK GÜCLƏNDİRMƏ
            =================================================
            */

            $ilkin_min =
                (int)$g_esya['min_zerbe'];

            $ilkin_max =
                (int)$g_esya['max_zerbe'];

            $ilkin_can =
                (int)$g_esya['can'];

            $ilkin_mudafie =
                (int)$g_esya['mudafie'];


            /*
            İlk gücləndirmədə faiz:
            Ağ = 5%
            Qara = 10%
            Yaşıl = 10%
            */

            $yeni_min = (int)round(
                $ilkin_min * (1 + $guc_faizi)
            );

            $yeni_max = (int)round(
                $ilkin_max * (1 + $guc_faizi)
            );

            $yeni_can = (int)round(
                $ilkin_can * (1 + $guc_faizi)
            );

            $yeni_mudafie = (int)round(
                $ilkin_mudafie * (1 + $guc_faizi)
            );


            /*
            =================================================
            YENİ GÜCLƏNDİRMƏ SƏTRİNİ YARAT
            =================================================
            */

            $stmt_insert = $pdo->prepare("
                INSERT INTO esya_guclendirme (
                    canta_id,
                    ilkin_min_zerbe,
                    ilkin_max_zerbe,
                    ilkin_can,
                    ilkin_mudafie,
                    guclendirilmis_min_zerbe,
                    guclendirilmis_max_zerbe,
                    guclendirilmis_can,
                    guclendirilmis_mudafie,
                    guclendirme_sayi,
                    guclendirme_faizi,
                    guclendirme_qara_yasil
                ) VALUES (
                    :canta_id,
                    :ilkin_min,
                    :ilkin_max,
                    :ilkin_can,
                    :ilkin_mudafie,
                    :yeni_min,
                    :yeni_max,
                    :yeni_can,
                    :yeni_mudafie,
                    1,
                    :guclendirme_faizi,
                    :guclendirme_qara_yasil
                )
            ");

            $stmt_insert->execute([
                ':canta_id' =>
                    $guclendirme_canta_id,

                ':ilkin_min' =>
                    $ilkin_min,

                ':ilkin_max' =>
                    $ilkin_max,

                ':ilkin_can' =>
                    $ilkin_can,

                ':ilkin_mudafie' =>
                    $ilkin_mudafie,

                ':yeni_min' =>
                    $yeni_min,

                ':yeni_max' =>
                    $yeni_max,

                ':yeni_can' =>
                    $yeni_can,

                ':yeni_mudafie' =>
                    $yeni_mudafie,

                ':guclendirme_faizi' =>
                    $addim_faizi,

                ':guclendirme_qara_yasil' =>
                    (
                        $almaz_esya_id === 369 ||
                        $almaz_esya_id === 370
                    )
                    ? 1
                    : 0
            ]);

        } else {

            /*
            =================================================
            TƏKRAR GÜCLƏNDİRMƏ
            =================================================
            */

            $yeni_min = (int)round(
                (int)$movcud['guclendirilmis_min_zerbe']
                * (1 + $guc_faizi)
            );

            $yeni_max = (int)round(
                (int)$movcud['guclendirilmis_max_zerbe']
                * (1 + $guc_faizi)
            );

            $yeni_can = (int)round(
                (int)$movcud['guclendirilmis_can']
                * (1 + $guc_faizi)
            );

            $yeni_mudafie = (int)round(
                (int)$movcud['guclendirilmis_mudafie']
                * (1 + $guc_faizi)
            );


            /*
            Əvvəlki ümumi faiz
            +
            bu gücləndirmənin faizi
            */

            $yeni_faiz =
                (int)$movcud['guclendirme_faizi']
                + $addim_faizi;


            /*
            =================================================
            UPDATE
            =================================================
            */

            $stmt_update = $pdo->prepare("
                UPDATE esya_guclendirme
                SET
                    guclendirilmis_min_zerbe = :yeni_min,
                    guclendirilmis_max_zerbe = :yeni_max,
                    guclendirilmis_can = :yeni_can,
                    guclendirilmis_mudafie = :yeni_mudafie,
                    guclendirme_sayi =
                        guclendirme_sayi + 1,
                    guclendirme_faizi = :yeni_faiz,
                    guclendirme_qara_yasil =
                        CASE
                            WHEN :qara_yasil = 1
                            THEN 1
                            ELSE guclendirme_qara_yasil
                        END
                WHERE canta_id = :canta_id
            ");

            $stmt_update->execute([
                ':yeni_min' =>
                    $yeni_min,

                ':yeni_max' =>
                    $yeni_max,

                ':yeni_can' =>
                    $yeni_can,

                ':yeni_mudafie' =>
                    $yeni_mudafie,

                ':yeni_faiz' =>
                    $yeni_faiz,

                ':qara_yasil' =>
                    (
                        $almaz_esya_id === 369 ||
                        $almaz_esya_id === 370
                    )
                    ? 1
                    : 0,

                ':canta_id' =>
                    $guclendirme_canta_id
            ]);
        }


        /*
        =====================================================
        ALMAZI SİL
        =====================================================
        */

        $stmt_sil_almaz = $pdo->prepare("
            DELETE FROM canta
            WHERE id = :almaz_id
              AND user_id = :user_id
        ");

        $stmt_sil_almaz->execute([
            ':almaz_id' => $almaz_canta_id,
            ':user_id'  => $my_id
        ]);


        /*
        =====================================================
        SESSION MƏLUMATLARI
        =====================================================
        */

        $_SESSION['guclendirme_ugur_faizi'] =
            $addim_faizi;


        /*
        Almaz session-u artıq lazım deyil
        */

        unset($_SESSION['guclendirme_almaz_id']);


        /*
        =====================================================
        TRANSACTION COMMIT
        =====================================================
        */

        $pdo->commit();


        /*
        =====================================================
        UĞURLU MESAJ
        =====================================================
        */

        header(
            "Location: eshya_pilus.php?go=guclendirme_success&idi="
            . $guclendirme_canta_id
        );

        exit;


    } catch (Exception $e) {


        /*
        =====================================================
        TRANSACTION ROLLBACK
        =====================================================
        */

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }


        /*
        =====================================================
        REAL UĞURSUZ GÜCLƏNDİRMƏ
        =====================================================

        Yalnız random nəticə uğursuz olduqda
        əvvəlki gücləndirmə tamamilə silinir.

        Başqa xətalarda SİLİNMİR.
        */

        if ($real_guclendirme_ugursuz) {

            try {

                $stmt_sil_guclendirme = $pdo->prepare("
                    DELETE FROM esya_guclendirme
                    WHERE canta_id = :canta_id
                ");

                $stmt_sil_guclendirme->execute([
                    ':canta_id' =>
                        $guclendirme_canta_id
                ]);

            } catch (Exception $sil_error) {

                /*
                Silinmə xətası olsa belə
                əsas xəta mesajını saxlayırıq.
                */
            }


            /*
            =================================================
            UĞURSUZLUQDA ALMAZI SİL
            =================================================
            */

            try {

                $stmt_sil_almaz = $pdo->prepare("
                    DELETE FROM canta
                    WHERE id = :almaz_id
                      AND user_id = :user_id
                ");

                $stmt_sil_almaz->execute([
                    ':almaz_id' =>
                        $almaz_canta_id,

                    ':user_id' =>
                        $my_id
                ]);

            } catch (Exception $almaz_error) {

                /*
                Əsas xəta dəyişdirilmir.
                */
            }


            unset(
                $_SESSION['guclendirme_almaz_id']
            );


            /*
            =================================================
            UĞURSUZLUQ MESAJI
            =================================================
            */

            $_SESSION['guclendirme_xetasi'] =
                'Çox təəssüf ki, əşyanın gücləndirilməsi uğursuz alındı. '
                . 'Əşyanın gücləndirilmiş parametrləri silindi və '
                . 'əşya ilkin vəziyyətinə qayıtdı.';


            header(
                "Location: eshya_pilus.php?go=guclendirme_xeta&idi="
                . $guclendirme_canta_id
            );

            exit;
        }


        /*
        =====================================================
        120% LİMİTİ
        =====================================================

        Bu halda əşyanın əvvəlki gücləndirməsi
        QƏTİYYƏN silinmir.
        */

        if (
            strpos(
                $e->getMessage(),
                '120%'
            ) !== false
        ) {

            $_SESSION['guclendirme_xetasi'] =
                $e->getMessage();

            unset(
                $_SESSION['guclendirme_almaz_id']
            );


            header(
                "Location: eshya_pilus.php?go=guclendirme_xeta&idi="
                . $guclendirme_canta_id
            );

            exit;
        }


        /*
        =====================================================
        DİGƏR TEXNİKİ XƏTALAR
        =====================================================

        Burada esya_guclendirme SİLİNMİR.
        */

        $_SESSION['guclendirme_xetasi'] =
            'Gücləndirmə zamanı xəta baş verdi: '
            . $e->getMessage();


        /*
        Session-u yalnız təmizləyirik.
        Əşyanın gücləndirməsinə toxunmuruq.
        */

        unset(
            $_SESSION['guclendirme_almaz_id']
        );


        header(
            "Location: eshya_pilus.php?go=guclendirme_xeta&idi="
            . $guclendirme_canta_id
        );

        exit;
    


    /*
    =====================================================
    NORMAL UĞURSUZ GÜCLƏNDİRMƏ
    Burada əvvəlki gücləndirmələr silinir.
    Əşya ilkin vəziyyətinə qayıdır.
    =====================================================
    */

    if ($guclendirme_canta_id > 0) {

        try {

            $stmt_sil_guclendirme = $pdo->prepare("
                DELETE FROM esya_guclendirme
                WHERE canta_id = :canta_id
            ");

            $stmt_sil_guclendirme->execute([
                ':canta_id' => $guclendirme_canta_id
            ]);

        } catch (Exception $sil_error) {

            // Əsas xəta mesajını göstəririk.
        }
    }


    /*
    =====================================================
    ALMAZ SESSION-UNU TƏMİZLƏ
    =====================================================
    */

    unset($_SESSION['guclendirme_almaz_id']);


    /*
    =====================================================
    NORMAL UĞURSUZLUQ MESAJI
    =====================================================
    */

    $_SESSION['guclendirme_xetasi'] =
        'Çox təəssüf ki eşyanın gücləndirilməsi uğursuz alındı. '
        . 'Sizin əşyanın gücləndirilmiş parametrləri silindi.';


    header(
        "Location: eshya_pilus.php?go=guclendirme_xeta&idi="
        . $guclendirme_canta_id
    );

    exit;
}

}


$stmt = $pdo->prepare("
    SELECT qızıl, brılyant, enerjı
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $my_id
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit('İstifadəçi tapılmadı.');
}


/* =========================================================
   Ä°STÄ°FADÆÃ‡Ä°NÄ°N ADI
========================================================= */

$user_login = '';

$stmt_user = $pdo->prepare("
    SELECT login
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_user->execute([
    ':id' => $my_id
]);

$current_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if ($current_user) {
    $user_login = $current_user['login'];
}


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
    ':id'   => $my_id
]);


/* =========================================================
   OXUNMAMIÅ MÆKTUBLAR
========================================================= */

$stmt_unread = $pdo->prepare("
    SELECT COUNT(*)
    FROM mesajlar
    WHERE mesaji_alan_nik = :alan
      AND oxundu = 0
");

$stmt_unread->execute([
    ':alan' => $my_id
]);

$unread_count = (int)$stmt_unread->fetchColumn();


/* =========================================================
   ONLINE OYUNÃ‡ULAR
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        login,
        movqe,
        oyuncunun_seviyyesi,
        vip
    FROM users
    WHERE online_oyuncu_vaxti >= :vaxt
    ORDER BY id DESC
");

$stmt->execute([
    ':vaxt' => time() - 180
]);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html>

<head>

<meta name="robots" content="ALL">

<meta
    name="keywords"
    content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar"
>

<meta
    name="description"
    content="Azerbaycanda ilk Mobil Online oyunu."
>

<link rel="stylesheet" href="css.css">

<meta
    content="text/html; charset=utf-8"
    http-equiv="content-type"
>

<meta
    name="viewport"
    content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"
>

<title>ÆÅŸya GÃ¼clÉ™ndir</title>


<script>

function goGeri() {
    window.history.back();
}

</script>

</head>


<body>


<div class="main" style="word-wrap:break-word;">


<!-- =====================================================
     HEADER
===================================================== -->

<div id="header">

    <a href="menu.php?">

        <img src="img/logo.png">

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


            <?php if ($unread_count > 0): ?>

                <a href="arxiv.php?go=goster">

                    <img
                        src="img/mektub.gif"
                        title="MÉ™ktub"
                        alt="MÉ™ktub"
                    >

                </a>

                (<?php echo $unread_count; ?>)

            <?php endif; ?>


            <?php if ($dostluq_sayi > 0): ?>

                <a href="dostlar.php">

                    <img
                        src="muxtelif/dost_pilus.png"
                        title="Dost"
                        alt="Dost"
                    >

                </a>

                (<?php echo $dostluq_sayi; ?>)

            <?php endif; ?>


        </div>

    </div>

</div>


<div class="space"></div>


<div
    style="background: none repeat scroll 0 0 #888686; height: 1px;"
></div>


<!-- =====================================================
     EXPERIENCE
===================================================== -->

<div class="fl b exp_count">

    <div style="margin-top: -2px;">

        <span style="color:#ff3333">

            <b>
                <?php echo $progress; ?>%
            </b>

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
    style="background: none repeat scroll 0 0 #888686; height: 1px;"
></div>


<!-- =====================================================
     INFO
===================================================== -->

<?php
$pilus_ok = isset($_GET['go']) && $_GET['go'] === 'pilus_ok';
$dashlar_ok = isset($_GET['go']) && $_GET['go'] === 'dashlar_ok';
$secili_id = isset($_GET['idi']) ? (int)$_GET['idi'] : 0;
$almaz_id = isset($_GET['almaz_id']) ? (int)$_GET['almaz_id'] : 0;
?>

<div class="info">
  <?php if ($guclendirme_xeta): ?>

<?php
$xeta_mesaji = $_SESSION['guclendirme_xetasi']
    ?? 'Xəta baş verdi.';

unset($_SESSION['guclendirme_xetasi']);

$is_120_limit =
    strpos($xeta_mesaji, '120%') !== false;
?>

<div class="error">

    <img
        src="muxtelif/eror.png"
        alt="Error"
    >

   

    <?php echo htmlspecialchars(
        $xeta_mesaji,
        ENT_QUOTES,
        'UTF-8'
    ); ?>

</div>

<br>

<div class="menu">

    <?php if (!$is_120_limit && $guclendirme_canta_id > 0): ?>

        <li>
            <a href="eshya_pilus.php?almaz=1&amp;idi=<?php echo (int)$guclendirme_canta_id; ?>">
                <img src="muxtelif/okey.png" alt="">
                Əşyanı Yenidən Gücləndir
            </a>
        </li>

    <?php endif; ?>

    <li>
        <a href="eshya_pilus.php">
            <img src="muxtelif/okey.png" alt="">
            Dəmirçixanaya Qayıt
        </a>
    </li>

</div>

<?php endif; ?>


<?php if ($guclendirme_success): ?>

<?php

$ugurlu_faiz = isset($_SESSION['guclendirme_ugur_faizi'])
    ? (int)$_SESSION['guclendirme_ugur_faizi']
    : 0;

unset($_SESSION['guclendirme_ugur_faizi']);

?>

<div class="success">





    Qeyd edilən Əşya
    <span class="darkest-green">
    <?php echo $ugurlu_faiz; ?>%
</span>

    gücləndirildi.Təbriklər

</div>

<br>

<div class="menu">

    <li>
        <a href="eshya_pilus.php?almaz=1&amp;idi=<?php echo (int)$guclendirme_canta_id; ?>">
            <img src="muxtelif/okey.png" alt="">
            Əşyanı Təkrar Gücləndir
        </a>
    </li>

    <li>
        <a href="eshya_pilus.php">
            <img src="muxtelif/okey.png" alt="">
            Dəmirçixanaya Qayıt
        </a>
    </li>

</div>

<?php endif; ?>



<?php if (!$guclendirme_success && !$guclendirme_xeta): ?>


<?php if ($dashlar_ok && $secili_id > 0 && $almaz_id > 0): ?>

<?php
$stmt_esya = $pdo->prepare("
    SELECT
        c.id,
        e.id AS esya_id,
        e.ad,
        e.img,
        e.reng,
        e.min_zerbe,
        e.max_zerbe,
        e.can,
        e.mudafie,

        eg.guclendirilmis_min_zerbe,
        eg.guclendirilmis_max_zerbe,
        eg.guclendirilmis_can,
      eg.guclendirilmis_mudafie,
eg.guclendirme_sayi,
eg.guclendirme_faizi

    FROM canta c

    INNER JOIN esyalar e
        ON e.id = c.esya_id

    LEFT JOIN esya_guclendirme eg
        ON eg.canta_id = c.id

    WHERE c.id = :id
      AND c.user_id = :uid

    LIMIT 1
");

$stmt_esya->execute([
    ':id' => $secili_id,
    ':uid' => $my_id
]);

$dash_esya = $stmt_esya->fetch(PDO::FETCH_ASSOC);



$stmt_almaz = $pdo->prepare("
    SELECT c.id, e.id AS esya_id, e.ad, e.img, e.reng
    FROM canta c
    INNER JOIN esyalar e ON e.id = c.esya_id
    WHERE c.id = :id
      AND c.user_id = :uid
    LIMIT 1
");

$stmt_almaz->execute([
    ':id' => $almaz_id,
    ':uid' => $my_id
]);

$dash_almaz = $stmt_almaz->fetch(PDO::FETCH_ASSOC);
if ($dash_almaz) {
    $_SESSION['guclendirme_almaz_id'] = (int)$dash_almaz['id'];
}
?>

<?php if ($dash_esya && $dash_almaz): ?>

<div style="float:left;width:90px;">
    <img width="90" height="70" src="muxtelif/demirci.png" border="0" alt="">
</div>

<small>
Siz
<b>
<a href="chantam.php?go=info&amp;rid=<?php echo (int)$dash_esya['id']; ?>">
<?php echo htmlspecialchars($dash_esya['ad'], ENT_QUOTES, 'UTF-8'); ?>
</a>
</b>
adlı əşyanın gücləndirilməsi üçün mene

<b>
<a href="eshyalar.php?go=c_info&amp;rid=<?php echo (int)$dash_almaz['esya_id']; ?>">
<?php echo htmlspecialchars($dash_almaz['ad'], ENT_QUOTES, 'UTF-8'); ?>
</a>
</b>

verdiniz. <?php
$secili_almaz_id = (int)$dash_almaz['esya_id'];

/*
Əşya hələ heç vaxt gücləndirilməyibsə:
ilk dəfədir.
*/
$ilk_defe_guclendirme =
    (int)$dash_esya['guclendirme_sayi'] === 0;


if ($secili_almaz_id === 368) {

    // Ağ Almaz
    echo 'Bu halda men sizin əşyanızı <b>5% faiz</b> gücləndirə bilerem.';
    echo ' Bunun üçün mene <b>16500 qızıl</b> lazımdır.';
    echo ' Qeyd edim ki əşyanı gücləndirərkən neticeler ';
    echo '<b>uğurlu</b> ve <b>uğursuz</b> ola biler.';

    /*
    Yalnız gücləndirilməmiş əşyada əlavə cümlə
    */
    if ($ilk_defe_guclendirme) {

        echo ' Qeyd edilən əşya ilk dəfə dəmirçiyə gəldiyinə görə ';
        echo '<b>100% uğurla gücləndiriləcək!</b> ';
        echo 'Növbəti dəfə isə bu <b>80%</b>-ə enir. ';
        echo 'Uğursuz nəticə zamanı əşyanın gücləndirilmiş ';
        echo 'parametrləri silinir, əşya əvvəlki vəziyyətinə qayıdır.';

    }


} elseif ($secili_almaz_id === 369) {

    // Qara Almaz
    echo 'Bu halda men sizin əşyanızı <b>10% faiz</b> gücləndirə bilerem.';
    echo ' Bunun üçün mene <b>16500 qızıl</b> lazımdır.';
    echo ' Qeyd edim ki əşyanı gücləndirərkən neticeler ';
    echo '<b>uğurlu</b> ve <b>uğursuz</b> ola biler.';

    /*
    Yalnız gücləndirilməmiş əşyada əlavə cümlə
    */
    if ($ilk_defe_guclendirme) {

        echo ' Qeyd edilən əşya ilk dəfə dəmirçiyə gəldiyinə görə ';
        echo '<b>100% uğurla gücləndiriləcək!</b> ';
        echo 'Növbəti dəfə isə bu <b>80%</b>-ə enir. ';
        echo 'Uğursuz nəticə zamanı əşyanın gücləndirilmiş ';
        echo 'parametrləri silinir, əşya əvvəlki vəziyyətinə qayıdır.';

    }


} elseif ($secili_almaz_id === 370) {

    // Yaşıl Almaz
    echo 'Bu halda men sizin əşyanızı <b>10% faiz</b> gücləndirə bilerem.';
    echo ' Bunun üçün mene <b>16500 qızıl</b> lazımdır.';
    echo ' Qeyd edim ki əşyanı gücləndirərkən neticeler ';
    echo '<b>uğurlu</b> ve <b>uğursuz</b> ola biler.';

    /*
    Yalnız gücləndirilməmiş əşyada əlavə cümlə
    */
    if ($ilk_defe_guclendirme) {

        echo ' Qeyd edilən əşya ilk dəfə dəmirçiyə gəldiyinə görə ';
        echo '<b>100% uğurla gücləndiriləcək!</b> ';
        echo 'Növbəti dəfə isə bu <b>80%</b>-ə enir. ';
        echo 'Uğursuz nəticə zamanı əşyanın gücləndirilmiş ';
        echo 'parametrləri silinir, əşya əvvəlki vəziyyətinə qayıdır.';

    }

}
?>

</small>
<?php endif; ?>

<br><br>
<hr>

<table border="0">
<tr>

<td>
<img
    src="<?php echo htmlspecialchars($dash_almaz['img'], ENT_QUOTES, 'UTF-8'); ?>"
    alt="foto"
>
</td>

<td>

<a href="eshyalar.php?go=c_info&amp;rid=<?php echo (int)$dash_almaz['esya_id']; ?>">
<?php echo htmlspecialchars($dash_almaz['ad'], ENT_QUOTES, 'UTF-8'); ?>
</a>

<br>

<?php
$almaz_ad = mb_strtolower(trim($dash_almaz['ad']), 'UTF-8');

if (strpos($almaz_ad, 'yaşıl almaz') !== false || strpos($almaz_ad, 'yasil almaz') !== false) {
    echo 'Əşyanın Əsas parametrlərini 10% gücləndirmək üçün istifadə olunur.Nəticələr 100% uğurlu alınır';
} elseif (strpos($almaz_ad, 'qara almaz') !== false) {
    echo 'Əşyanın Əsas parametrlərini 10% gücləndirmək üçün istifadə olunur';
} elseif (strpos($almaz_ad, 'Ağ almaz') !== false || strpos($almaz_ad, 'ag almaz') !== false) {
    echo 'Əşyanın Əsas parametrlərini 5% gücləndirmək üçün istifadə olunur';
} else {
    echo 'Əşyanın Əsas parametrlərini gücləndirmək üçün istifadə olunur';
}

?>

</td>

</tr>
</table>
<hr>
<table border="0">
    <tr>
        <td>
            <img
                src="<?php echo htmlspecialchars($dash_esya['img'], ENT_QUOTES, 'UTF-8'); ?>"
                alt="foto"
            >
        </td>

        <td>

            <?php
            /* =====================================================
               HAZIRKI PARAMETRLÆRÄ° MÃœÆYYÆN ET
               GÃ¼clÉ™ndirilibsÉ™ son gÃ¼clÉ™ndirilmiÅŸ dÉ™yÉ™rlÉ™r,
               gÃ¼clÉ™ndirilmÉ™yibsÉ™ ilkin dÉ™yÉ™rlÉ™r gÃ¶stÉ™rilir.
            ===================================================== */

$goster_faiz = 0.10;

if ($dash_almaz) {

    $goster_almaz_id = (int)$dash_almaz['esya_id'];

    if ($goster_almaz_id === 368) {
        // Ağ almaz
        $goster_faiz = 0.05;

    } elseif (
        $goster_almaz_id === 369 ||
        $goster_almaz_id === 370
    ) {
        // Qara və yaşıl almaz
        $goster_faiz = 0.10;
    }
}


if ((int)$dash_esya['guclendirme_sayi'] > 0) {
                $goster_min = (int)$dash_esya['guclendirilmis_min_zerbe'];
                $goster_max = (int)$dash_esya['guclendirilmis_max_zerbe'];
                $goster_can = (int)$dash_esya['guclendirilmis_can'];
                $goster_mudafie = (int)$dash_esya['guclendirilmis_mudafie'];

            } else {

                $goster_min = (int)$dash_esya['min_zerbe'];
                $goster_max = (int)$dash_esya['max_zerbe'];
                $goster_can = (int)$dash_esya['can'];
                $goster_mudafie = (int)$dash_esya['mudafie'];
            }
            ?>

            <span class="dark-brown">Mini.zerbe:</span>
            <?php echo $goster_min; ?>

            <span class="darkest-green">
                + <?php echo (int)round($goster_min * $goster_faiz); ?>
            </span>
            <br>

            <span class="dark-brown">Maks.zerbe:</span>
            <?php echo $goster_max; ?>

            <span class="darkest-green">
                + <?php echo (int)round($goster_max * $goster_faiz); ?>
            </span>
            <br>

            <span class="dark-brown">Can:</span>
            <?php echo $goster_can; ?>

            <span class="darkest-green">
                + <?php echo (int)round($goster_can * $goster_faiz); ?>
                            </span>
            <br>

            <span class="dark-brown">Mudafie:</span>
            <?php echo $goster_mudafie; ?>

            <span class="darkest-green">
                + <?php echo (int)round($goster_mudafie * $goster_faiz); ?>
            </span>
            <br>

<?php if ((int)$dash_esya['guclendirme_sayi'] > 0): ?>

    <?php
    // Gücləndirmə sayı
    $guclendirme_sayi_goster =
        (int)$dash_esya['guclendirme_sayi'];

    // Ümumi gücləndirmə faizi birbaşa SQL-dən götürülür.
    $guclendirme_faizi_goster =
        (int)$dash_esya['guclendirme_faizi'];
    ?>

    <span class="darkest-green">
        <b>
            Gücləndirilib:
            +<?php echo $guclendirme_sayi_goster; ?>
            (<?php echo $guclendirme_faizi_goster; ?>%)
        </b>
    </span>

<?php else: ?>

    <span class="dark-brown">
        Gücləndirilməyib!
    </span>

<?php endif; ?>

        </td>
    </tr>
</table>


<div class="line"></div>

<form method="post" action="eshya_pilus.php?go=ok&amp;yenile=<?php echo (int)$secili_id; ?>">

<b>Tələb olunan Brilliant:</b> 0
<br>

<b>Tələb olunan qızıl:</b> 16500
<br>

<input type="hidden" name="action" value="save">
<input type="hidden" name="almaz_id" value="<?php echo (int)$almaz_canta_id; ?>">
<input
    type="submit"
    class="button_big"
    value="Əşyanı Gücləndir"
>

</form>

<br>
<hr>

<div class="menu">

<li>
<a href="eshya_pilus.php?go=del&amp;yenile=<?php echo (int)$secili_id; ?>">
<img src="img/go_back.png" alt="">
Əşyanı Geri Götür
</a>
</li>

<li>
<a href="infoforce.php?uid=<?php echo (int)$my_id; ?>">
<img src="muxtelif/doyuscu1.png" alt="">
Mənim Döyüşçüm
</a>
</li>

<li>
<a href="menu.php?793336447">
<img src="muxtelif/home.png" alt="">
Ana Səhifə
</a>
</li>

</div>

<?php exit; ?>

<?php endif; ?>
<?php endif; ?>
<?php if (!$guclendirme_success && !$guclendirme_xeta): ?>

<?php
/* =========================================================
   DEMIRCIXANA
   Ã‡ANTADAKI BÃœTÃœN ÆÅYALAR
========================================================= */

$stmt_demir = $pdo->prepare("

    SELECT

        c.id,
        c.user_id,
        c.esya_id,
        c.say,
        c.geyimde,

        c.son_daxil_olma_vaxti,
        c.son_cixarma_vaxti,

        e.id AS real_esya_id,
        e.ad,
        e.img,
        e.seviyye,
        e.reng,
        e.tip,
        e.sekil

    FROM canta c

    INNER JOIN esyalar e
        ON e.id = c.esya_id

    WHERE c.user_id = :user_id

      AND c.say > 0

      AND c.geyimde = 0

      AND e.tip != 'mecun'
      AND e.tip != 'almaz'

    ORDER BY

        c.son_daxil_olma_vaxti DESC,
        c.son_cixarma_vaxti DESC,
        c.id DESC

");


$stmt_demir->execute([
    ':user_id' => $my_id
]);


$demir_esyalar = $stmt_demir->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   ÆÅYALARIN ÃœMUMÄ° SAYI
========================================================= */

$demir_esya_sayi = 0;

foreach ($demir_esyalar as $esya) {

    $demir_esya_sayi += (int)$esya['say'];

}
/* =========================================================
   Ã‡ANTADAKI ALMAZLAR
========================================================= */
$stmt_almazlar = $pdo->prepare("
    SELECT
        c.id,
        e.id AS esya_id,
        e.ad,
        e.img,
        e.reng,
        c.say
    FROM canta c
    INNER JOIN esyalar e ON e.id = c.esya_id
    WHERE c.user_id = :user_id
      AND e.tip = 'almaz'
      AND c.say > 0
    ORDER BY c.id DESC
");

$stmt_almazlar->execute([
    ':user_id' => $my_id
]);

$almazlar = $stmt_almazlar->fetchAll(PDO::FETCH_ASSOC);




?>


<!-- =====================================================
     DEMIRCIXANA YUXARI HÄ°SSÆ
===================================================== -->

<div style="float:left;width:90px;">

    <img
        width="60"
        height="54"
        src="muxtelif/demirci.png"
        border="0"
        alt="Dəmirçi"
    >

</div>


<small>

    <?php if ($almaz_rejimi && $secili_id > 0): ?>

        <?php
        $stmt_secili = $pdo->prepare("
            SELECT e.id, e.ad
            FROM canta c
            INNER JOIN esyalar e ON e.id = c.esya_id
            WHERE c.id = :id
              AND c.user_id = :user_id
            LIMIT 1
        ");

        $stmt_secili->execute([
            ':id' => $secili_id,
            ':user_id' => $my_id
        ]);

        $secili_esya = $stmt_secili->fetch(PDO::FETCH_ASSOC);
        ?>

        <?php if ($secili_esya): ?>

           Men sizin
<a href="chantam.php?go=info&amp;rid=<?php echo (int)$secili_id; ?>">
    <?php echo htmlspecialchars($secili_esya['ad'], ENT_QUOTES, 'UTF-8'); ?>
</a>
adlı əştyanızı 5,10 faiz-e geder gücləndirə bilerem.
            Bu sizin mene vereceyiniz almaz daşından aslıdır

        
            <br>

        <?php endif; ?>

    <?php else: ?>

        Salam
        <?php echo htmlspecialchars($user_login, ENT_QUOTES, 'UTF-8'); ?>

        Men sizin istenilen əşyanızı gücləndirə bilerem.
        Bunun üçün mene lazım olan almaz daşları ve qızıl
        vermelisiz, ilk öncə əşya seçin.

    <?php endif; ?>

</small>


<hr>

<?php if (!$almaz_rejimi): ?>

<div style="width:100%; text-align:left; float:left;">

    <b>Əşyaların Sayı:</b>
    <?php echo $demir_esya_sayi; ?>

</div>

<div style="clear:both;"></div>

<?php endif; ?>


<br>

<?php if ($pilus_ok && $secili_id > 0): ?>

    <div class="success">
        Gücləndiriləcək əşya seçildi
        <br>
    </div>

    <div class="menu">
        <li>
          <a href="eshya_pilus.php?almaz=1">
    <img src="muxtelif/okey.png" alt="">
    OK
</a>

        </li>
    </div>

<?php else: ?>

    <?php if ($almaz_rejimi): ?>

        <!-- =================================================
             ALMAZLAR
        ================================================== -->

        <?php if (empty($almazlar)): ?>

            <div class="battle_log">

                <div class="content">

                    Çantada almaz yoxdur.

                </div>

            </div>

     <?php else: ?>

    <?php foreach ($almazlar as $almaz): ?>

        <div class="battle_log">

            <div class="content">

                <table border="0" cellpadding="0" cellspacing="0">

                    <tbody>

                        <tr>

                            <td>
                                <img
                                    src="<?php echo htmlspecialchars($almaz['img'], ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="foto"
                                >
                            </td>

                            <td>

                                <a href="eshyalar.php?go=c_info&amp;rid=<?php echo (int)$almaz['esya_id']; ?>">



                                    <?php echo htmlspecialchars(
                                        $almaz['ad'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </a>

                                <br>

                                <a href="eshya_pilus.php?go=dashlar_ok&amp;num=54&amp;idi=<?php echo (int)$secili_id; ?>&amp;almaz_id=<?php echo (int)$almaz['id']; ?>">

                                    Seç

                                </a>

                                <br>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
<?php if (!$almaz_rejimi): ?>

    <?php foreach ($demir_esyalar as $esya): ?>

        <?php $rid = (int)$esya['id']; ?>

        <div class="battle_log">

            <div class="content">

                <table border="0" cellpadding="0" cellspacing="0">

                    <tbody>
                        <tr>

                            <td>
                                <img
                                    src="<?php echo htmlspecialchars($esya['img'], ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="foto"
                                >
                            </td>

                            <td>

                                <u>
                                    <a href="chantam.php?go=info&amp;rid=<?php echo $rid; ?>">

                                        <font style="color:<?php echo htmlspecialchars($esya['reng'] ?: '#0000FF', ENT_QUOTES, 'UTF-8'); ?>">

                                            <?php echo htmlspecialchars(
                                                $esya['ad'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </font>

                                    </a>
                                </u>

                                <br>

                                <a href="eshya_pilus.php?almaz=1&amp;idi=<?php echo $rid; ?>">
    Gücləndir
</a>


                                <?php if ((int)$esya['say'] > 1): ?>

                                    <br>

                                    Say:
                                    <?php echo (int)$esya['say']; ?>

                                <?php endif; ?>

                            </td>

                        </tr>
                    </tbody>

                </table>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>
<?php endif; ?>
<!-- =====================================================
     ALT MENYU
===================================================== -->

<?php if (!$pilus_ok && !$guclendirme_success && !$guclendirme_xeta): ?>

<div class="menu">
    <?php if ($almaz_rejimi && $secili_id > 0): ?>

    <li>
        <a href="eshya_pilus.php?go=del&amp;yenile=<?php echo (int)$secili_id; ?>">
            <img src="img/go_back.png" alt="">
          Əşyanı Geri Götür
        </a>
    </li>

<?php endif; ?>

    <li>
        <a href="infoforce.php?uid=<?php echo (int)$my_id; ?>">
            <img src="muxtelif/doyuscu1.png" alt="">
           Mənim döyüşçüm
        </a>
    </li>

    <li>
        <a href="menu.php?793336447">
            <img src="muxtelif/home.png" alt="">
            Ana səhifə
        </a>
    </li>
</div>

<?php endif; ?>



<!-- =====================================================
     FOOTER
===================================================== -->

<div class="main_foot">

    <div class="center">

        <div class="grey">

            <div class="small">

                <div class="foot">


                    [<b>
                        <a href="dehliz">
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


                    <br>
                    <br>


              <img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>


                    <br>


                 <a href="index.php?">Çıxış (<?php echo htmlspecialchars($user_login); ?>)</a>


                    <br>
                    <br>


                    <a href="menu.php?dil=tr">

                        Türkcə:

                        <img
                            alt="türkcə"
                            src="http://macera.az/klan/muxtelif/tr.gif"
                            title="Türkcə"
                        >

                    </a>


                    <br>


                    Sciript name:
                    QanlÄ± efsane(modern version)


                    <br>


                    <a
                        href="http://klanaz.com/klan/"
                        class="xgame.az"
                    >

                        &#169; Klanaz.com 2026

                    </a>


                </div>

            </div>

        </div>

    </div>

</div>


</div>

</body>

</html>



