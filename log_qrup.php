<?php

session_start();

require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   DAXİL OLMUŞ İSTİFADƏÇİNİN MƏLUMATLARI
========================================================= */

$my_id = (int)$_SESSION['user_id'];
$qrupPdo = new PDO(
    "mysql:host=localhost;dbname=qrup_doyus;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);

/* =========================================================
   QRUPA DAXİL OL
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['go']) &&
    $_GET['go'] === 'daxil' &&
    isset($_GET['id'])
) {

    $qrup_id = (int)$_GET['id'];

    if ($qrup_id <= 0) {
        exit('Yanlış qrup.');
    }

   $stmt_qrup = $qrupPdo->prepare("
    SELECT *
    FROM qruplar
    WHERE id = :id
      AND status = 1
    LIMIT 1
");


    $stmt_qrup->execute([
        ':id' => $qrup_id
    ]);

    $qrup = $stmt_qrup->fetch(PDO::FETCH_ASSOC);

    if (!$qrup) {
        exit('Qrup tapılmadı və ya artıq bağlıdır.');
    }


    /* OYUNÇU ARTİQ BU QRUPDADIR? */

    $stmt_yoxla = $qrupPdo->prepare("
        SELECT id
        FROM qrup_uzvleri
        WHERE qrup_id = :qrup_id
          AND user_id = :user_id
          AND status = 1
        LIMIT 1
    ");

    $stmt_yoxla->execute([
        ':qrup_id' => $qrup_id,
        ':user_id' => $my_id
    ]);

    if ($stmt_yoxla->fetch()) {
        header("Location: qrup_yarad.php?go=qrup&id=" . $qrup_id);
        exit;
    }


    /* QRUPDA NEÇƏ NƏFƏR VAR? */

    $stmt_say = $qrupPdo->prepare("
        SELECT COUNT(*)
        FROM qrup_uzvleri
        WHERE qrup_id = :qrup_id
          AND status = 1
    ");

    $stmt_say->execute([
        ':qrup_id' => $qrup_id
    ]);

    $uzv_sayi = (int)$stmt_say->fetchColumn();


    /* QRUP DOLUDUR? */

    if ($uzv_sayi >= (int)$qrup['oyuncu_tutumu']) {
        exit('Qrup artıq doludur.');
    }


    /* =====================================================
       RƏQİB TƏRƏFİ
    ===================================================== */

    /*
       Burada qrup yaradan tərəfi 0 qəbul edirik.
       Rəqib daxil olanda 1 olacaq.
    */

    /* =====================================================
   OYUNÇUNUN MOVQE-SİNƏ GÖRƏ TƏRƏFİ TƏYİN ET
===================================================== */

/* Oyuncunun movqe-sini götür */
$stmt_movqe = $pdo->prepare("
    SELECT movqe
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_movqe->execute([
    ':id' => $my_id
]);

$oyuncu = $stmt_movqe->fetch(PDO::FETCH_ASSOC);

if (!$oyuncu) {
    exit('Oyunçu tapılmadı.');
}

$oyuncu_movqe = (int)$oyuncu['movqe'];


/*
   movqe:
   1 = İnsan
   2 = Vampir
   3 = Neytral
*/

/* Qrupun tərəfi */
$qrup_terefi = (int)$qrup['terefi'];


/* Yalnız İnsan və Vampir daxil ola bilər */
if (!in_array($oyuncu_movqe, [1, 2], true)) {
    exit('Bu oyunçu bu döyüşə daxil ola bilməz.');
}


/* =====================================================
   QRUPUN TƏRƏFİNƏ GÖRƏ OYUNÇUNU YERLƏŞDİR
===================================================== */

if ($qrup_terefi === 1) {

    /*
       Qrup İnsan tərəfidir.

       İnsan  -> öz tərəfi 1
       Vampir -> rəqib tərəf 2
    */

    if ($oyuncu_movqe === 1) {
        $terefi = 1;
    } elseif ($oyuncu_movqe === 2) {
        $terefi = 2;
    }

} elseif ($qrup_terefi === 2) {

    /*
       Qrup Vampir tərəfidir.

       Vampir -> öz tərəfi 2
       İnsan  -> rəqib tərəf 1
    */

    if ($oyuncu_movqe === 2) {
        $terefi = 2;
    } elseif ($oyuncu_movqe === 1) {
        $terefi = 1;
    }

} else {

    exit('Qrupun tərəfi düzgün təyin edilməyib.');
}


    /* =====================================================
       GİRİŞ SIRASI
    ===================================================== */

    $giris_sirasi = $uzv_sayi + 1;


    /* =====================================================
       QRUP ÜZVÜNÜ ƏLAVƏ ET
    ===================================================== */

    $stmt_daxil = $qrupPdo->prepare("
        INSERT INTO qrup_uzvleri (
            qrup_id,
            user_id,
            terefi,
            giris_sirasi,
            gelme_novu,
            devet_eden_id,
            daxil_olma_vaxti,
            status
        )
        VALUES (
            :qrup_id,
            :user_id,
            :terefi,
            :giris_sirasi,
            0,
            NULL,
            NOW(),
            1
        )
    ");

    $stmt_daxil->execute([
        ':qrup_id'       => $qrup_id,
        ':user_id'       => $my_id,
        ':terefi'        => $terefi,
        ':giris_sirasi'  => $giris_sirasi
    ]);


    /* OTAĞA QAYIT */

    header("Location: qrup_yarad.php?go=qrup&id=" . $qrup_id);
    exit;
}

/* =========================================================
   AÇIQ QRUPLARI GƏTİR
========================================================= */

$qrupPdo = new PDO(
    "mysql:host=localhost;dbname=qrup_doyus;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);

$stmt_qruplar = $qrupPdo->prepare("
    SELECT
        q.id,
        q.yaradan_id,
        q.otaq_adi,
        q.oyuncu_tutumu,
        q.minimum_merhele,
        q.maksimum_merhele,
        q.qizil,
        q.doyus_novu,
        q.terefi,
        q.status,
        COUNT(u.id) AS uzv_sayi
    FROM qruplar q
    LEFT JOIN qrup_uzvleri u
        ON u.qrup_id = q.id
       AND u.status = 1
    WHERE q.status = 1
      AND NOT EXISTS (
          SELECT 1
          FROM qrup_doyusleri d
          WHERE d.qrup_id = q.id
            AND d.bitdi = 1
      )
    GROUP BY
        q.id,
        q.yaradan_id,
        q.otaq_adi,
        q.oyuncu_tutumu,
        q.minimum_merhele,
        q.maksimum_merhele,
        q.qizil,
        q.doyus_novu,
        q.terefi,
        q.status
    ORDER BY q.id DESC
");

$stmt_qruplar->execute();

$acıq_qruplar = $stmt_qruplar->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("
    SELECT qızıl, brılyant, enerjı,oyuncunun_seviyyesi
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
   DAXİL OLMUŞ İSTİFADƏÇİNİN ADI
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
   MƏNİM ONLINE VAXTIMI YENİLƏ
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
   OXUNMAMIŞ MƏKTUBLARIN SAYI
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
   ONLINE OYUNÇULARI MYSQL-DƏN GƏTİR
========================================================= */

$stmt = $pdo->prepare("
    SELECT 
        id,
        login,
        movqe,
        oyuncunun_seviyyesi,
        vip,
        online_oyuncu_vaxti
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
<meta name="robots" content="ALL" /> 
<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, " /> 
<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" /> 

<link rel="stylesheet" href="css.css">

 <meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"><title>doyus log</title>
<script>
    function goGeri() {
        window.history.back();
    }
</script>

</head><body><div class='main' style='word-wrap:break-word;'><div id="header">

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
<?php if ($unread_count > 0) { ?>
    <a href="arxiv.php?go=goster">
        <img src="img/mektub.gif" title="Məktub" alt="Məktub"/>
    </a>
    (<?php echo $unread_count; ?>)
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

<div class='menu'>
    <b>Qrup Döyüşleri</b>
    <br/><br/>

    <?php if (!empty($acıq_qruplar)): ?>

        <?php foreach ($acıq_qruplar as $qrup): ?>

            <li>
                <a href="log_qrup.php?go=daxil&id=<?php echo (int)$qrup['id']; ?>">
                    <img src="muxtelif/qrupda.png" alt=" "/>
                   Döyüş otaqı
(<?php echo (int)$qrup['uzv_sayi']; ?> nefer)

                </a>
            </li>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

<hr/>

[<a href="qrup_yarad.php?go=neww">Yeni Qrup Yarad</a>]

<hr/>

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
    
<a href="menu.php?dil=tr">Türkce: <img alt="türkce" src="http://macera.az/klan/muxtelif/tr.gif" title="Türkce"/></a><br/>
Sciript name: Qanlı efsane(modern version)<br/>
    
<a href="http://klanaz.com/klan/" class="xgame.az">&#169; Klanaz.com 2026</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>


