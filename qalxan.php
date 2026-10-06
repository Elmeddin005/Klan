<?php

session_start();

require_once "config.php";
require_once "user_data.php";

/* =========================================================
   GİRİŞ YOXLAMASI
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];


/* =========================================================
   YENİ MESAJLAR
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
   İSTİFADƏÇİ MƏLUMATLARI
========================================================= */

$user_id = $user['id'];
$user_login = $user['login'];
$user_ad = $user['ad'];


/* =========================================================
   SƏVİYYƏ
========================================================= */

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

?>

<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL" />

<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, " />

<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" />

<link rel="stylesheet" href="css.css">

<meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;">

<title>qalxan al</title>

<script>
function goGeri() {
    window.history.back();
}
</script>

</head>

<body>

<div class='main' style='word-wrap:break-word;'>

<div id="header">

<a href="menu.php?">
<img src="img/logo.png">
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


<?php

/* =========================================================
   QALXAN MƏLUMATINI ALIRIQ
========================================================= */

$stmt_qalxan_status = $pdo->prepare("
    SELECT qalxan_statusu, qalxan_vaxti, qalxan_terk_vaxti
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_qalxan_status->execute([
    ':id' => $my_id
]);

$q_status = $stmt_qalxan_status->fetch(PDO::FETCH_ASSOC);

if (!$q_status) {

    $q_status = [
        'qalxan_statusu' => 0,
        'qalxan_vaxti' => 0,
        'qalxan_terk_vaxti' => 0
    ];

}


/* =========================================================
   QALXAN VƏ TƏRK VAXTINI YOXLAYIRIQ
========================================================= */

$indi = time();


/* TƏRK SAYĞACI BİTİBSƏ */

if (
    (int)$q_status['qalxan_statusu'] === 1 &&
    (int)$q_status['qalxan_terk_vaxti'] > 0 &&
    (int)$q_status['qalxan_terk_vaxti'] <= $indi
) {

    $stmt_qalxan_son = $pdo->prepare("
        UPDATE users
        SET qalxan_statusu = 0,
            qalxan_vaxti = 0,
            qalxan_terk_vaxti = 0
        WHERE id = :id
    ");

    $stmt_qalxan_son->execute([
        ':id' => $my_id
    ]);

    $q_status['qalxan_statusu'] = 0;
    $q_status['qalxan_vaxti'] = 0;
    $q_status['qalxan_terk_vaxti'] = 0;
}


/* NORMAL QALXAN VAXTI BİTİBSƏ */

elseif (
    (int)$q_status['qalxan_statusu'] === 1 &&
    (int)$q_status['qalxan_vaxti'] <= $indi
) {

    $stmt_qalxan_son = $pdo->prepare("
        UPDATE users
        SET qalxan_statusu = 0,
            qalxan_vaxti = 0,
            qalxan_terk_vaxti = 0
        WHERE id = :id
    ");

    $stmt_qalxan_son->execute([
        ':id' => $my_id
    ]);

    $q_status['qalxan_statusu'] = 0;
    $q_status['qalxan_vaxti'] = 0;
    $q_status['qalxan_terk_vaxti'] = 0;
}


/* =========================================================
   NƏTİCƏ DƏYİŞƏNLƏRİ
========================================================= */

$qalxan_alindi = false;
$qalxan_evvel_var = false;

$exit_netice = '';


/* =========================================================
   QALXAN ALMA
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'qalxan_ok' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save'
) {

    $aa = isset($_POST['aa']) ? (int)$_POST['aa'] : 0;


    /* ƏGƏR ARTİQ QALXANDADIRSA */

    if ((int)$q_status['qalxan_statusu'] === 1) {

        $qalxan_evvel_var = true;

    } else {

        if ($aa === 1) {

            $qalxan_muddeti = 60 * 60;

        } elseif ($aa === 2) {

            $qalxan_muddeti = 3 * 60 * 60;

        } elseif ($aa === 3) {

            $qalxan_muddeti = 24 * 60 * 60;

        } else {

            $qalxan_muddeti = 0;
        }


        if ($qalxan_muddeti > 0) {

            $qalxan_bitis_vaxti = time() + $qalxan_muddeti;

            $stmt_qalxan_al = $pdo->prepare("
                UPDATE users
                SET qalxan_statusu = 1,
                    qalxan_vaxti = :vaxt,
                    qalxan_terk_vaxti = 0
                WHERE id = :id
                  AND qalxan_statusu = 0
            ");

            $stmt_qalxan_al->execute([
                ':vaxt' => $qalxan_bitis_vaxti,
                ':id' => $my_id
            ]);

            if ($stmt_qalxan_al->rowCount() > 0) {

                $qalxan_alindi = true;

                $q_status['qalxan_statusu'] = 1;
                $q_status['qalxan_vaxti'] = $qalxan_bitis_vaxti;
                $q_status['qalxan_terk_vaxti'] = 0;
            }
        }
    }
}


/* =========================================================
   QALXANI TƏRK ETMƏ
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'qalxan_exit' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save'
) {

    $aa = isset($_POST['aa']) ? (int)$_POST['aa'] : 0;


    /* QALXANDA DEYİLSƏ */

    if ((int)$q_status['qalxan_statusu'] === 0) {

        $exit_netice = 'deyil';

    }


    /* =====================================================
       DƏRHAL TƏRK ET
       SAYĞAC AKTİV OLSA BELƏ İŞLƏYƏCƏK
    ===================================================== */

    elseif ($aa === 4) {

        $qiymet = 1000000;

        $stmt_exit = $pdo->prepare("
            UPDATE users
            SET qızıl = qızıl - :qiymet,
                qalxan_statusu = 0,
                qalxan_vaxti = 0,
                qalxan_terk_vaxti = 0
            WHERE id = :id
              AND qalxan_statusu = 1
              AND qızıl >= :qiymet
        ");

        $stmt_exit->execute([
            ':qiymet' => $qiymet,
            ':id' => $my_id
        ]);

        if ($stmt_exit->rowCount() > 0) {

            $exit_netice = 'derhal';

            $q_status['qalxan_statusu'] = 0;
            $q_status['qalxan_vaxti'] = 0;
            $q_status['qalxan_terk_vaxti'] = 0;

        } else {

            $exit_netice = 'qizil_yoxdur';
        }
    }


    /* =====================================================
       ARTIQ TƏRK SAYĞACI VARSA
       BURADA YENİ MƏNTİQ VAR:
       YALNIZ DAHA AŞAĞI MÜDDƏTƏ SALMAQ OLAR
    ===================================================== */

    elseif ((int)$q_status['qalxan_terk_vaxti'] > time()) {

        $indiki_bitis = (int)$q_status['qalxan_terk_vaxti'];

        /* SEÇİLƏN MÜDDƏT */

        if ($aa === 3) {

            $qiymet = 10000;
            $muddet = 5 * 60;
            $yeni_netice = '5';

        } elseif ($aa === 2) {

            $qiymet = 5000;
            $muddet = 10 * 60;
            $yeni_netice = '10';

        } elseif ($aa === 1) {

            $qiymet = 1000;
            $muddet = 15 * 60;
            $yeni_netice = '15';

        } else {

            $qiymet = 0;
            $muddet = 0;
            $yeni_netice = '';
        }


        if ($muddet > 0) {

            $yeni_bitis = time() + $muddet;


            /* YENİ VAXT KÖHNƏ VAXTDAN DAHA QISA OLMALIDIR */

            if ($yeni_bitis < $indiki_bitis) {

                $stmt_exit = $pdo->prepare("
                    UPDATE users
                    SET qızıl = qızıl - :qiymet,
                        qalxan_terk_vaxti = :terk_vaxt
                    WHERE id = :id
                      AND qalxan_statusu = 1
                      AND qalxan_terk_vaxti = :kohne_vaxt
                      AND qızıl >= :qiymet
                ");

                $stmt_exit->execute([
                    ':qiymet' => $qiymet,
                    ':terk_vaxt' => $yeni_bitis,
                    ':kohne_vaxt' => $indiki_bitis,
                    ':id' => $my_id
                ]);


                if ($stmt_exit->rowCount() > 0) {

                    $exit_netice = $yeni_netice;

                    $q_status['qalxan_terk_vaxti'] = $yeni_bitis;

                } else {

                    $exit_netice = 'qalan_vaxt';
                }

            } else {

                /* YUXARI QALDIRMAQ OLMAZ */

                $exit_netice = 'qalan_vaxt';
            }

        } else {

            $exit_netice = 'qalan_vaxt';
        }
    }


    /* =====================================================
       İLK DƏFƏ 5 / 10 / 15 DƏQİQƏ SEÇİLİR
    ===================================================== */

    else {

        /* 5 DƏQİQƏ */

        if ($aa === 3) {

            $qiymet = 10000;
            $muddet = 5 * 60;

        }

        /* 10 DƏQİQƏ */

        elseif ($aa === 2) {

            $qiymet = 5000;
            $muddet = 10 * 60;

        }

        /* 15 DƏQİQƏ */

        elseif ($aa === 1) {

            $qiymet = 1000;
            $muddet = 15 * 60;

        }

        else {

            $qiymet = 0;
            $muddet = 0;
        }


        if ($muddet > 0) {

            $bitis = time() + $muddet;

            $stmt_exit = $pdo->prepare("
                UPDATE users
                SET qızıl = qızıl - :qiymet,
                    qalxan_terk_vaxti = :terk_vaxt
                WHERE id = :id
                  AND qalxan_statusu = 1
                  AND qalxan_terk_vaxti = 0
                  AND qızıl >= :qiymet
            ");

            $stmt_exit->execute([
                ':qiymet' => $qiymet,
                ':terk_vaxt' => $bitis,
                ':id' => $my_id
            ]);


            if ($stmt_exit->rowCount() > 0) {

                if ($aa === 3) {

                    $exit_netice = '5';

                } elseif ($aa === 2) {

                    $exit_netice = '10';

                } elseif ($aa === 1) {

                    $exit_netice = '15';
                }

                $q_status['qalxan_terk_vaxti'] = $bitis;

            } else {

                $exit_netice = 'qizil_yoxdur';
            }
        }
    }
}


/* =========================================================
   QALXAN AL NƏTİCƏSİ
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'qalxan_ok'
) {

    if ($qalxan_evvel_var) {
?>

<div class="info">

Siz qalxan statusundasız<br><br>

<input type="button" class="button" value="Geri" onclick="goGeri()">

</div>

<?php
    } else {
?>

<div class="info">

<b>Siz qalxan statusuna keçdiniz</b><br>

Bu halda heç bir kes size hucum ede bilmez, hemçinin siz istifadeçilere hucum ede bilmezsiz<br>

<input type="button" class="button" value="Geri" onclick="goGeri()">

</div>

<?php
    }


/* =========================================================
   QALXANI TƏRK ET NƏTİCƏSİ
========================================================= */

} elseif (
    isset($_GET['go']) &&
    $_GET['go'] === 'qalxan_exit'
) {

    if ($exit_netice === 'deyil') {
?>

<div class="info">

Siz qalxan statusunda deyilsiz<br><br>

<input type="button" class="button" value="Geri" onclick="goGeri()">

</div>

<?php

    } elseif ($exit_netice === 'qalan_vaxt') {

        $son_vaxt = (int)$q_status['qalxan_terk_vaxti'];

        $qalan_saniye = $son_vaxt - time();

        if ($qalan_saniye < 0) {
            $qalan_saniye = 0;
        }
?>

<div class="info">

Qalxan statusunun bitməsinə
<b>(<span id="qalxan_saygac">00:00</span>)</b>
dəqiqə qalıb<br>

<input type="button" class="button" value="Geri" onclick="goGeri()">

</div>

<script>

var qalxanSonVaxt = <?php echo $son_vaxt; ?>;

function qalxanSaygac() {

    var indi = Math.floor(Date.now() / 1000);

    var qalan = qalxanSonVaxt - indi;

    if (qalan <= 0) {

        document.getElementById("qalxan_saygac").innerHTML = "00:00";

        window.location.href = "qalxan.php";

        return;
    }

    var deqiqe = Math.floor(qalan / 60);
    var saniye = qalan % 60;

    document.getElementById("qalxan_saygac").innerHTML =
        String(deqiqe).padStart(2, '0') + ":" +
        String(saniye).padStart(2, '0');
}

qalxanSaygac();

setInterval(qalxanSaygac, 1000);

</script>

<?php

    } elseif ($exit_netice === 'derhal') {
?>

<div class="info">

<b>Siz qalxan statusunu tərk etdiz.</b><br><br>

<input type="button" class="button" value="Geri" onclick="goGeri()">

</div>

<?php

    } elseif ($exit_netice === '5') {
?>

<div class="info">

<b>Siz (05:00) dəqiqə ərzinde qalxan statusunu tərk edəcəksiz.</b><br><br>

<input type="button" class="button" value="Geri" onclick="goGeri()">

</div>

<script>

var qalxanSonVaxt = <?php echo (int)$q_status['qalxan_terk_vaxti']; ?>;

function qalxanSaygac() {

    var qalan = qalxanSonVaxt - Math.floor(Date.now() / 1000);

    if (qalan <= 0) {

        window.location.href = "qalxan.php";

        return;
    }

    var deqiqe = Math.floor(qalan / 60);
    var saniye = qalan % 60;

    document.querySelector(".info b").innerHTML =
        "Siz (" +
        String(deqiqe).padStart(2, '0') + ":" +
        String(saniye).padStart(2, '0') +
        ") dəqiqə ərzinde qalxan statusunu tərk edəcəksiz.";
}

qalxanSaygac();

setInterval(qalxanSaygac, 1000);

</script>

<?php

    } elseif ($exit_netice === '10') {
?>

<div class="info">

<b>Siz (10:00) dəqiqə ərzinde qalxan statusunu tərk edəcəksiz.</b><br><br>

<input type="button" class="button" value="Geri" onclick="goGeri()">

</div>

<script>

var qalxanSonVaxt = <?php echo (int)$q_status['qalxan_terk_vaxti']; ?>;

function qalxanSaygac() {

    var qalan = qalxanSonVaxt - Math.floor(Date.now() / 1000);

    if (qalan <= 0) {

        window.location.href = "qalxan.php";

        return;
    }

    var deqiqe = Math.floor(qalan / 60);
    var saniye = qalan % 60;

    document.querySelector(".info b").innerHTML =
        "Siz (" +
        String(deqiqe).padStart(2, '0') + ":" +
        String(saniye).padStart(2, '0') +
        ") dəqiqə ərzinde qalxan statusunu tərk edəcəksiz.";
}

qalxanSaygac();

setInterval(qalxanSaygac, 1000);

</script>

<?php

    } elseif ($exit_netice === '15') {
?>

<div class="info">

<b>Siz (15:00) dəqiqə ərzinde qalxan statusunu tərk edəcəksiz.</b><br><br>

<input type="button" class="button" value="Geri" onclick="goGeri()">

</div>

<script>

var qalxanSonVaxt = <?php echo (int)$q_status['qalxan_terk_vaxti']; ?>;

function qalxanSaygac() {

    var qalan = qalxanSonVaxt - Math.floor(Date.now() / 1000);

    if (qalan <= 0) {

        window.location.href = "qalxan.php";

        return;
    }

    var deqiqe = Math.floor(qalan / 60);
    var saniye = qalan % 60;

    document.querySelector(".info b").innerHTML =
        "Siz (" +
        String(deqiqe).padStart(2, '0') + ":" +
        String(saniye).padStart(2, '0') +
        ") dəqiqə ərzinde qalxan statusunu tərk edəcəksiz.";
}

qalxanSaygac();

setInterval(qalxanSaygac, 1000);

</script>

<?php

    } elseif ($exit_netice === 'qizil_yoxdur') {
?>

<div class="info">

Kifayət Qədər Qızılınız yoxdur.<br><br>

<input type="button" class="button" value="Geri" onclick="goGeri()">

</div>

<?php
    }


/* =========================================================
   NORMAL QALXAN SƏHİFƏSİ
========================================================= */

} else {
?>

<div class=battle_log>

Siz rəqibin hucumuna məruz qalmaq istəmirsizsə, o zaman qalxan statusu sizin üçündür. Bu halda sizdə digər istifadəçilərə hucum ede bilməzsiz

</div>

<b>Qalxan al (ödənişsiz)</b>

<br>

<form method="post" action="qalxan.php?go=qalxan_ok">

<select name="aa">

<option value="1">1 saat</option>

<option value="2">3 saat</option>

<option value="3">24 saat</option>

</select>

<br/>

<input type="hidden" name="action" value="save"/>

<input type="submit" class="button" value="OK"/>

</form>

<br/>

<div class='line'></div>

<b>Qalxanı tərk et</b>

<br>

<form method="post" action="qalxan.php?go=qalxan_exit">

<select name="aa">

<option value="1">15 dəq sonra (1000 qızıl)</option>

<option value="2">10 dəq sonra (5000 qızıl)</option>

<option value="3">5 dəq sonra (10000 qızıl)</option>

<option value="4">Dərhal (1000000 qızıl)</option>

</select>

<br/>

<input type="hidden" name="action" value="save"/>

<input type="submit" class="button" value="OK"/>

</form>

<br/>
<br/>

<a href="eshyalar.php?">Merkez Dükan</a>

<br/>

<?php
}

?>

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

<a href="index.php?">

Çıxış (<?php echo htmlspecialchars($user_login); ?>)

</a>

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

Sciript name: Qanlı efsane(modern version)

<br/>

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