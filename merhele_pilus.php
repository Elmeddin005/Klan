<?php

session_start();

require_once "config.php";
require_once "user_data.php";
require_once "guc_parametrləri.php";

/* =========================================================
   GİRİŞ YOXLAMASI
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];

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
   İSTİFADƏÇİ MƏLUMATLARI
========================================================= */

$user_id = $user['id'];
$user_login = $user['login'];
$user_ad = $user['ad'];

$stmt_seviyye = $pdo->prepare("
    SELECT oyuncunun_seviyyesi
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_seviyye->execute([
    ':id' => $my_id
]);

$oyuncu_seviyyesi = (int)$stmt_seviyye->fetchColumn();

if ($oyuncu_seviyyesi < 1) {
    $oyuncu_seviyyesi = 1;
}


/* =========================================================
   MƏRHƏLƏ GÜC BONUSU
========================================================= */

/* =========================================================
   MƏRHƏLƏ GÜC BONUSU - TOPLAM
   Cari səviyyəyə qədər bütün bonuslar toplanır
========================================================= */

$stmt_merhele_bonus = $pdo->prepare("
    SELECT COALESCE(SUM(bonus), 0)
    FROM merhele_bonuslari
    WHERE merhele <= :merhele
");

$stmt_merhele_bonus->execute([
    ':merhele' => $oyuncu_seviyyesi
]);

$merhele_bonus = (int)$stmt_merhele_bonus->fetchColumn();


/* =========================================================
   ONLINE VAXTINI YENİLƏYİRİK
========================================================= */

$stmt_online = $pdo->prepare("
    UPDATE users
    SET online_oyuncu_vaxti = :vaxt
    WHERE id = :id
");

$stmt_online->execute([
    ':vaxt' => time(),
    ':id' => $user_id
]);


/* =========================================================
   ONLINE OYUNCULARIN SAYI
========================================================= */

$stmt_online_count = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE online_oyuncu_vaxti >= :vaxt
");

$stmt_online_count->execute([
    ':vaxt' => time() - 180
]);

$online_sayi = $stmt_online_count->fetchColumn();


/* =========================================================
   NƏTİCƏ DƏYİŞƏNLƏRİ
========================================================= */

$bonus_ok = false;

$alish_max = 0;
$can = 0;
$krit = 0;
$uvorot = 0;
$bonus_xeta = '';
/* =========================================================
   BU ƏMƏLİYYATDA VERİLƏN YENİ BONUS
========================================================= */

$yeni_zerbe_bonus = 0;
$yeni_can_bonus = 0;
$yeni_krit_bonus = 0;
$yeni_uvorot_bonus = 0;

/* =========================================================
   ƏŞYALARDAN GƏLƏN PARAMETRLƏR
========================================================= */

$stmt_oyuncu_parametrleri = $pdo->prepare("
    SELECT
        min_zerbe,
        max_zerbe,
        can,
        mudafie,
        krit,
        anti_krit,
        uvorot,
        anti_uvorot
    FROM oyuncu_parametrleri
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt_oyuncu_parametrleri->execute([
    ':user_id' => $my_id
]);

$oyuncu_parametrleri = $stmt_oyuncu_parametrleri->fetch(PDO::FETCH_ASSOC);

if (!$oyuncu_parametrleri) {

    $oyuncu_parametrleri = [
        'min_zerbe' => 0,
        'max_zerbe' => 0,
        'can' => 0,
        'mudafie' => 0,
        'krit' => 0,
        'anti_krit' => 0,
        'uvorot' => 0,
        'anti_uvorot' => 0
    ];
}

/* =========================================================
   FORMAT MƏLUMAT
========================================================= */

$format_melumat = false;

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'format_melumat'
) {
    $format_melumat = true;
}
/* =========================================================
   FORMAT - NƏTİCƏ
========================================================= */

$format_ok = false;
$format_xeta = '';

/* =========================================================
   FORMAT SAYINI YOXLAYIRIQ
========================================================= */

$stmt_format_sayi = $pdo->prepare("
    SELECT format_sayi
    FROM oyuncu_guc_bonuslari
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt_format_sayi->execute([
    ':user_id' => $my_id
]);

$format_sayi = (int)$stmt_format_sayi->fetchColumn();

if ($format_sayi < 0) {
    $format_sayi = 0;
}

/* =========================================================
   FORMAT ARTİQ EDİLİB XƏBƏRDARLIĞI
========================================================= */

$format_xeber = false;

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'format_xeber'
) {

    /* Cari bonusların olub-olmadığını yoxlayırıq */

    $stmt_format_bonus = $pdo->prepare("
        SELECT
            zerbe,
            mudafie,
            can,
            krit,
            anti_krit,
            uvorot,
            anti_uvorot
        FROM oyuncu_guc_bonuslari
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $stmt_format_bonus->execute([
        ':user_id' => $my_id
    ]);

    $format_bonus = $stmt_format_bonus->fetch(PDO::FETCH_ASSOC);

    if ($format_bonus) {

        $cari_bonus_cemi =
            (int)$format_bonus['zerbe'] +
            (int)$format_bonus['mudafie'] +
            (int)$format_bonus['can'] +
            (int)$format_bonus['krit'] +
            (int)$format_bonus['anti_krit'] +
            (int)$format_bonus['uvorot'] +
            (int)$format_bonus['anti_uvorot'];

    } else {

        $cari_bonus_cemi = 0;
    }

    /*
       Əgər format_sayi >= 1 olsa belə,
       cari bonuslar varsa, deməli istifadəçi yeni
       bonuslar verib və format edə bilər.
    */

    if (
        $format_sayi >= 1 &&
        $cari_bonus_cemi == 0
    ) {
        $format_xeber = true;
    }
}

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'format'
) {

    /* =====================================================
       GEYİMDƏ ƏŞYA VARMI YOXLAYIRIQ
    ===================================================== */

    $stmt_geyim = $pdo->prepare("
        SELECT COUNT(*)
        FROM canta
        WHERE user_id = :user_id
          AND geyimde = 1
          AND say > 0
    ");

    $stmt_geyim->execute([
        ':user_id' => $my_id
    ]);

    $geyimde_esya_sayi = (int)$stmt_geyim->fetchColumn();

    if ($geyimde_esya_sayi > 0) {

        $format_xeta = "Format üçün bütün eşyalarınızı çixartmalısız";

    } else {

        $format_ok = true;

    }
}
/* =========================================================
   FORMAT - GÜC BONUSLARINI SIFIRLAYIRIQ
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'format'
) {

    if ($format_xeta == '') {

        /* =====================================================
           1-Cİ FORMAT PULSUZDUR
           2-Cİ VƏ SONRAKI FORMATLAR 200 BRİLLİANTDIR
        ===================================================== */

        if ($format_sayi >= 1) {

            $stmt_brilliant = $pdo->prepare("
                UPDATE users
                SET brılyant = brılyant - 200
                WHERE id = :id
                  AND brılyant >= 200
            ");

            $stmt_brilliant->execute([
                ':id' => $my_id
            ]);

            if ($stmt_brilliant->rowCount() == 0) {

                $format_xeta = "Format üçün hesabınızda 200 Brilliant olmalıdır.";
                $format_ok = false;

            } else {

                /* 200 Brilliant uğurla çıxıldı */
                $format_ok = true;

            }

        } else {

            /* 1-ci Format pulsuzdur */
            $format_ok = true;
        }


        /* =====================================================
           YALNIZ FORMAT ÜÇÜN ŞƏRTLƏR ÖDƏNİLDİKDƏ SIFIRLAYIRIQ
        ===================================================== */

        if ($format_xeta == '') {

            $stmt_format = $pdo->prepare("
                UPDATE oyuncu_guc_bonuslari
                SET
                    zerbe = 0,
                    mudafie = 0,
                    can = 0,
                    krit = 0,
                    anti_krit = 0,
                    uvorot = 0,
                    anti_uvorot = 0,
                    son_merhele = 0,
                    format_sayi = format_sayi + 1
                WHERE user_id = :user_id
            ");

            $stmt_format->execute([
                ':user_id' => $my_id
            ]);

            $format_ok = true;
        }
    }
}

/* =========================================================
   BU MƏRHƏLƏDƏ İNDİYƏ QƏDƏR VERİLƏN BONUS
========================================================= */

$stmt_verilen_bonus = $pdo->prepare("
    SELECT
        son_merhele,
        zerbe,
        mudafie,
        can,
        krit,
        anti_krit,
        uvorot,
        anti_uvorot
    FROM oyuncu_guc_bonuslari
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt_verilen_bonus->execute([
    ':user_id' => $my_id
]);

$verilen_bonus = $stmt_verilen_bonus->fetch(PDO::FETCH_ASSOC);

if (!$verilen_bonus) {

    $verilen_bonus = [
        'son_merhele' => 0,
        'zerbe' => 0,
        'can' => 0,
        'krit' => 0,
        'uvorot' => 0
    ];

}

/* =========================================================
   İNDİYƏ QƏDƏR VERİLƏN BONUS
   Zərbə, Can, Krit və Uvorot istifadə olunan bonus vahididir.
   Müdafiə, Anti Krit və Anti Uvorot ayrıca sayılmır.
========================================================= */

$indiyek_verilen =
    (int)$verilen_bonus['zerbe'] +
    (int)$verilen_bonus['can'] +
    (int)$verilen_bonus['krit'] +
    (int)$verilen_bonus['uvorot'];

/* =========================================================
   YEKUN PARAMETRLƏR
   İLKİN + GÜC BONUSU + ƏŞYA
========================================================= */

/*
 * İlkin güc parametrləri
 */
$yekun_min_zerbe =
    $ilkin_zerbe_min;

$yekun_max_zerbe =
    $ilkin_zerbe_max;

$yekun_mudafie =
    $ilkin_mudafie;

$yekun_can =
    $ilkin_can;

$yekun_krit =
    $ilkin_krit;

$yekun_anti_krit =
    $ilkin_antikrit;

$yekun_uvorot =
    $ilkin_uvorot;

$yekun_anti_uvorot =
    $ilkin_antiuvorot;
/* =========================================================
   GÜC BONUSLARINI İLKİN PARAMETRLƏRƏ ƏLAVƏ EDİRİK
========================================================= */

if ((int)$verilen_bonus['son_merhele'] === $oyuncu_seviyyesi) {

    $yekun_min_zerbe += (int)$verilen_bonus['zerbe'];
    $yekun_max_zerbe += (int)$verilen_bonus['zerbe'];

    $yekun_mudafie += (int)$verilen_bonus['mudafie'];

    $yekun_can += (int)$verilen_bonus['can'];

    $yekun_krit += (int)$verilen_bonus['krit'];
    $yekun_anti_krit += (int)$verilen_bonus['anti_krit'];

    $yekun_uvorot += (int)$verilen_bonus['uvorot'];
    $yekun_anti_uvorot += (int)$verilen_bonus['anti_uvorot'];
}

/*
 * ƏŞYALARDAN GƏLƏN PARAMETRLƏRİ ƏLAVƏ EDİRİK
 */
$yekun_min_zerbe +=
    (int)$oyuncu_parametrleri['min_zerbe'];

$yekun_max_zerbe +=
    (int)$oyuncu_parametrleri['max_zerbe'];

$yekun_mudafie +=
    (int)$oyuncu_parametrleri['mudafie'];

$yekun_can +=
    (int)$oyuncu_parametrleri['can'];

$yekun_krit +=
    (int)$oyuncu_parametrleri['krit'];

$yekun_anti_krit +=
    (int)$oyuncu_parametrleri['anti_krit'];

$yekun_uvorot +=
    (int)$oyuncu_parametrleri['uvorot'];

$yekun_anti_uvorot +=
    (int)$oyuncu_parametrleri['anti_uvorot'];

/* =========================================================
   GÜC BONUSU - OK
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] == 'ok' &&
    isset($_POST['action']) &&
    $_POST['action'] == 'save'
) {

    $alish_max = (int)($_POST['alish_max'] ?? 0);
    $can = (int)($_POST['can'] ?? 0);
    $krit = (int)($_POST['krit'] ?? 0);
    $uvorot = (int)($_POST['uvorot'] ?? 0);

    $yeni_zerbe_bonus = $alish_max;
$yeni_can_bonus = $can;
$yeni_krit_bonus = $krit;
$yeni_uvorot_bonus = $uvorot;


    /* Mənfi rəqəmlərə icazə vermirik */

    if ($alish_max < 0) {
        $alish_max = 0;
    }

    if ($can < 0) {
        $can = 0;
    }

    if ($krit < 0) {
        $krit = 0;
    }

    if ($uvorot < 0) {
        $uvorot = 0;
    }

    /* Verilən yeni bonusun cəmi */
    $cemi_bonus = $alish_max + $can + $krit + $uvorot;

    /* Əvvəlki + yeni bonus */
    $yekun_verilen = $indiyek_verilen + $cemi_bonus;

    /* Bonusdan artıq istifadə etmək olmaz */
    if ($yekun_verilen > $merhele_bonus) {

        $bonus_xeta =
            "Siz verilen bonusdan artiq parametr artira bilmezsiniz.";

    }

    /* Bonus artıq tam istifadə olunub */
    elseif ($indiyek_verilen >= $merhele_bonus) {

        $bonus_xeta =
            "Siz Artıq Guc bonus parametrlerini vermisiz.";

    }

    /* =====================================================
       BONUSLARI BAZAYA YAZIRIQ
    ===================================================== */

    if ($bonus_xeta == '') {

        $stmt_bonus = $pdo->prepare("
            INSERT INTO oyuncu_guc_bonuslari
            (
                user_id,
                zerbe,
                mudafie,
                can,
                krit,
                anti_krit,
                uvorot,
                anti_uvorot,
                son_merhele
            )
            VALUES
            (
                :user_id,
                :zerbe,
                :mudafie,
                :can,
                :krit,
                :anti_krit,
                :uvorot,
                :anti_uvorot,
                :son_merhele
            )
            ON DUPLICATE KEY UPDATE
                zerbe = zerbe + VALUES(zerbe),
                mudafie = mudafie + VALUES(mudafie),
                can = can + VALUES(can),
                krit = krit + VALUES(krit),
                anti_krit = anti_krit + VALUES(anti_krit),
                uvorot = uvorot + VALUES(uvorot),
                anti_uvorot = anti_uvorot + VALUES(anti_uvorot),
                son_merhele = VALUES(son_merhele)
        ");

        $stmt_bonus->execute([
            ':user_id' => $my_id,
            ':zerbe' => $alish_max,
            ':mudafie' => $alish_max,
            ':can' => $can,
            ':krit' => $krit,
            ':anti_krit' => $krit,
            ':uvorot' => $uvorot,
            ':anti_uvorot' => $uvorot,
            ':son_merhele' => $oyuncu_seviyyesi
        ]);

        $bonus_ok = true;

    }

}

?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN""http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="az" lang="az">

<head>

<meta name="robots" content="ALL" /> 

<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, " /> 

<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" /> 

<link rel="stylesheet" href="css.css">

<meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;">

<title>guc bonuslari</title>

<script>
function goGeri() {
    window.location.href = 'merhele_pilus.php?';
}
</script>

</head>

<body>

<div class='main' style='word-wrap:break-word;'>

<div id="header">

<a href="menu.php?">
    <img src="img/logo.png" />
</a>

<div class="icons"></div>

<div class=main_foot>
<div class=grey>

<img src="img/coin.png" title="Qızıl" alt=""/>
<?php echo (int)$user['qızıl']; ?>

<img src="img/brill.png" title="Brilliant" alt=""/>
<?php echo (int)$user['brılyant']; ?>

<img src="img/energy.png" title="Enerji" alt=""/>
<?php echo (int)$user['enerjı']; ?>

<?php if ($new_message_count > 0) { ?>

<a href="arxiv.php?go=goster">
    <img src="img/mektub.gif" title="Məktub" alt="Məktub"/>
</a>

(<?php echo $new_message_count; ?>)

<?php } ?>
<?php if ($dostluq_sayi > 0) { ?>
    <a href="dostlar.php">
        <img
            src="muxtelif/dost_pilus.png"
            title="Dost"
            alt="Dost"
        />
    </a>
    (<?php echo $dostluq_sayi; ?>)
<?php } ?>
</div>
</div>
</div>

<div class="space"></div> 

<div style="background: none repeat scroll 0 0 #888686; height: 1px;"></div> 
 
<div class="fl b exp_count">

<div style="margin-top: -2px;">

<span style="color: #ff3333">

<b><?php echo $progress; ?>%</b>

</span>

</div>

</div> 

<div class="experience"> 

<div class="exp_bg"> 

<div class="exp_left fl"></div> 

<div class="exp_right fr"></div> 

<div style="width: <?php echo $progress; ?>%; height: 10px;">

<div class="exp_line"></div>

<div class="exp_point"></div>

</div>    

</div> 

</div> 

<div style="background: none repeat scroll 0 0 #888686; height: 1px;"></div>



<div class='info'>

<?php if ($format_melumat) { ?>

Siz hal hazırda olan parametrlerinizden narazısızsa bu xidmetden istifade ederek bonusları geri qaytara bilersiz ve yeniden istediyiniz parametrleri artıra bilersiz<br/><br/>

<b>Xidmetden 1 defe istifade etmek pulsuzdu</b><br/>
<b>Növbeti defe xidmetin haqqi 200 Brilliant deyerindedir</b>

<hr/>

Siz bu xidmetden istifade etmek isteyirsiz?<br/>

<?php
$stmt_format_yoxlama = $pdo->prepare("
    SELECT
        zerbe,
        mudafie,
        can,
        krit,
        anti_krit,
        uvorot,
        anti_uvorot
    FROM oyuncu_guc_bonuslari
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt_format_yoxlama->execute([
    ':user_id' => $my_id
]);

$format_yoxlama = $stmt_format_yoxlama->fetch(PDO::FETCH_ASSOC);

$format_bonus_cemi = 0;

if ($format_yoxlama) {

    $format_bonus_cemi =
        (int)$format_yoxlama['zerbe'] +
        (int)$format_yoxlama['mudafie'] +
        (int)$format_yoxlama['can'] +
        (int)$format_yoxlama['krit'] +
        (int)$format_yoxlama['anti_krit'] +
        (int)$format_yoxlama['uvorot'] +
        (int)$format_yoxlama['anti_uvorot'];
}

if ($format_sayi >= 1 && $format_bonus_cemi == 0) {
?>

<a href="merhele_pilus.php?go=format_xeber">He</a>

<?php } else { ?>

<a href="merhele_pilus.php?go=format">He</a>

<?php } ?>

<a href="merhele_pilus.php?">Yox</a><br/>

<?php } elseif ($format_xeber) { ?>

<b>Siz Artıq Guc Bonuslarini Format etmisiniz.</b><br/>

<hr/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
><br/>

<?php } elseif ($format_xeta != '') { ?>

<b><?php echo $format_xeta; ?></b><br/>

<hr/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
><br/>

<?php } elseif ($format_ok) { ?>

<b>Emelyat yerine yetirildi</b><br/>

<hr/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
><br/>

<?php } elseif ($bonus_xeta != '') { ?>

<b><?php echo $bonus_xeta; ?></b>

<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
/>

<?php } elseif ($bonus_ok) { ?>

<b>Əməliyat yerinə yetirildi</b><br/>

<u>Artırdığınız parametrlər</u><br/>

<br/>



<?php if ($alish_max > 0) { ?>

<b>Zərbənizi və Müdafiənizi:</b>
<?php echo $alish_max; ?><br/>

<?php } ?>

<?php if ($can > 0) { ?>

<b>Canınızı:</b>
<?php echo $can; ?><br/>

<?php } ?>

<?php if ($krit > 0) { ?>

<b>Krit və Anti Krit:</b>
<?php echo $krit; ?><br/>

<?php } ?>

<?php if ($uvorot > 0) { ?>

<b>Uvorot və Anti Uvorot:</b>
<?php echo $uvorot; ?><br/>

<?php } ?>

<br/>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
/>

<?php } else { ?>

<?php
$qalan_bonus = $merhele_bonus - $indiyek_verilen;

if ($qalan_bonus > 0) {
?>

<b>
Size <?php echo $qalan_bonus; ?>
 Parametr artırmaq şansı verilib.
</b></br>

<br>

<?php } ?>

Siz merhele keçdikden sonra oyunçunun parametrlerni artıra bilersiz<br/>

Siz <b>zerbe</b>nizi artırdıqda hemçinin
<b>müdafie</b>nizde hemin sayda artır.<br/>

<b>Can</b>nınızı artırdıqda artırılan say
2-ye vurulur.Mes: <b>Can</b>a 10 artırdıqda 20 artır.<br/>

<b>Krit</b>nizi artırdıqda hemçinin
<b>Anti Krit</b>nizde hemin sayda artır.<br/>

<b>Uvorot</b>unuzu artırdıqda hemçinin
<b>Anti Uvorot</b>unuzda hemin sayda artır.<br/>

<form method="post" action="merhele_pilus.php?go=ok">

<hr>

<b>Zerbe:</b>
<?php echo $yekun_min_zerbe; ?>-<?php echo $yekun_max_zerbe; ?> |

<b>Müdafie:</b>
<?php echo $yekun_mudafie; ?> +
<input
    size="4"
    name="alish_max"
    maxlength="4"
    value="0"
/>


<hr/>

<b>Can:</b>
<?php echo $yekun_can; ?> +

<input
    size="4"
    name="can"
    maxlength="5"
    value="0"
/>


<hr/>

<b>Krit:</b>
<?php echo $yekun_krit; ?> +

|

<b>Anti krit:</b>
<?php echo $yekun_anti_krit; ?> +

<input
    size="4"
    name="krit"
    maxlength="5"
    value="0"
/>


<hr/>

<b>Uvorot:</b>
<?php echo $yekun_uvorot; ?> +

|

<b>Anti uvorot:</b>
<?php echo $yekun_anti_uvorot; ?> +

<input
    size="4"
    name="uvorot"
    maxlength="5"
    value="0"
/>


<hr/>

<input type="hidden" name="action" value="save"/>

<input type="submit" class="button" value="Ok"/><br/>

<a href="merhele_pilus.php?go=format_melumat">
Bonusları geri qaytar
</a>(Format)<br/>

</form>


<?php } ?>

</div>


<div class="main_foot">

<div class="center">

<div class="grey">

<div class="small">

<div class="foot">
    
[<b><a href="menu.php?">Menu</a></b>] 

[<b><a href="axtar.php?">Axtarış</a></b>] 

[<a href="forum/mozu2.php?">Forum</a>] 

[<a href="shexsi_sehife.php?">Qurğular</a>]

<br/><br/>
    
<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>

<br/>

<a href="index.php?">Çıxış (<?php echo htmlspecialchars($user_login); ?>)</a>

<br/><br/>  

<a href="menu.php?dil=tr">
Türkce:
<img
alt="türkce"
src="http://macera.az/klan/muxtelif/tr.gif"
title="Türkce"
/>
</a>

<br/>

Sciript name: Qanlı efsane(modern version)<br/>
    
<a href="http://klanaz.com/klan/" class="xgame.az">
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