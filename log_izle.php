<?php

session_start();

require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$my_id = (int)$_SESSION['user_id'];
$unread_count = 0;

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

/* =====================================================
   DUEL GEDİŞATINA BAX
===================================================== */

$izle_duel = null;
$izle_gedisat = [];

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'izle' &&
    isset($_GET['duel_id'])
) {

    $izle_duel_id = (int)$_GET['duel_id'];

    /* Duel məlumatları */
    $stmt_izle_duel = $pdo->prepare("
        SELECT
            d.id,
            d.oyuncu1_id,
            d.oyuncu2_id,
            d.qalib_id,
               d.bitdi,
               d.hec_hece,
            d.raund,
         d.oyuncu1_can,
d.oyuncu2_can,

d.son_oyuncu1_hucum,
d.son_oyuncu2_hucum,
d.son_oyuncu1_zarar,
d.son_oyuncu2_zarar,
d.son_oyuncu1_kritik,
d.son_oyuncu2_kritik,
d.son_oyuncu1_xeta,
d.son_oyuncu2_xeta,

p1.login AS oyuncu1_login,
            p2.login AS oyuncu2_login

        FROM duel d

        INNER JOIN users p1
            ON p1.id = d.oyuncu1_id

        INNER JOIN users p2
            ON p2.id = d.oyuncu2_id

     WHERE d.id = :duel_id

LIMIT 1
    ");

  $stmt_izle_duel->execute([
    ':duel_id' => $izle_duel_id
]);

    $izle_duel = $stmt_izle_duel->fetch(PDO::FETCH_ASSOC);

    /* Duel tapılarsa bütün gedişatı götür */
    if ($izle_duel) {

        $stmt_izle_gedisat = $pdo->prepare("
            SELECT
                g.*,
                u.login AS zerbe_eden_login

            FROM duel_gedisati g

            INNER JOIN users u
                ON u.id = g.zerbe_eden_id

            WHERE g.duel_id = :duel_id

            ORDER BY
                g.raund ASC,
                g.id ASC
        ");

        $stmt_izle_gedisat->execute([
            ':duel_id' => $izle_duel_id
        ]);

        $izle_gedisat = $stmt_izle_gedisat->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html >
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


    <div class=main_foot><div class=grey>
    <img src="img/coin.png" title="Qızıl" alt=""/>
<?php echo (int)$user['qızıl']; ?>

<img src="img/brill.png" title="Brilliant" alt=""/>
<?php echo (int)$user['brılyant']; ?>

<img src="img/energy.png" title="Enerji" alt=""/>
<?php echo (int)$user['enerjı']; ?>
<?php if ($unread_count > 0) { ?>
    <a href="arxiv.php?go=goster">
        <img src="img/mektub.gif" title="Yeni mesaj" alt="Mesaj"/>
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
</div></div></div>
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
<?php
/* =====================================================

   AKTİV DUELLƏR
===================================================== */

$stmt_aktiv_dueller = $pdo->prepare("
    SELECT
        d.id,
        d.oyuncu1_id,
        d.oyuncu2_id,

        p1.login AS oyuncu1_login,
        p1.oyuncunun_seviyyesi AS oyuncu1_level,

        p2.login AS oyuncu2_login,
        p2.oyuncunun_seviyyesi AS oyuncu2_level

    FROM duel d

    INNER JOIN users p1
        ON p1.id = d.oyuncu1_id

    INNER JOIN users p2
        ON p2.id = d.oyuncu2_id

    WHERE d.qebul_edildi = 1
      AND d.qalib_id IS NULL

    ORDER BY d.id DESC
");

$stmt_aktiv_dueller->execute();

$aktiv_dueller = $stmt_aktiv_dueller->fetchAll(PDO::FETCH_ASSOC);
?>

<div class=center>
    <div class='block_line'>
        Duel edenler <b>[<?php echo count($aktiv_dueller); ?>]</b>
    </div>
</div>

<br/>

<?php if (!$izle_duel && !empty($aktiv_dueller)): ?>

    <?php foreach ($aktiv_dueller as $aktiv_duel): ?>

        <a href="infoforce.php?uid=<?php echo (int)$aktiv_duel['oyuncu1_id']; ?>">
            <font color="green">
                <?php
                echo htmlspecialchars(
                    $aktiv_duel['oyuncu1_login'],
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
                [<?php echo (int)$aktiv_duel['oyuncu1_level']; ?>]
            </font>
        </a>

        VS

        <a href="infoforce.php?uid=<?php echo (int)$aktiv_duel['oyuncu2_id']; ?>">
            <font color="green">
                <?php
                echo htmlspecialchars(
                    $aktiv_duel['oyuncu2_login'],
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
                [<?php echo (int)$aktiv_duel['oyuncu2_level']; ?>]
            </font>
        </a>

        <a href="log_izle.php?go=izle&duel_id=<?php echo (int)$aktiv_duel['id']; ?>">
            izle
        </a>

        <br/>

    <?php endforeach; ?>

    <br/>

<?php endif; ?>
<?php if ($izle_duel): ?>

<a href="log_izle.php?go=izle&amp;duel_id=<?php echo (int)$izle_duel['id']; ?>">Sehifeni Yenile</a><br>

<?php
$oyuncu1_login = htmlspecialchars(
    $izle_duel['oyuncu1_login'],
    ENT_QUOTES,
    'UTF-8'
);

$oyuncu2_login = htmlspecialchars(
    $izle_duel['oyuncu2_login'],
    ENT_QUOTES,
    'UTF-8'
);
$oyuncu1_son_can = (int)$izle_duel['oyuncu1_can'];
$oyuncu2_son_can = (int)$izle_duel['oyuncu2_can'];
?>



<?php
$hucum_adlari = [
    0 => 'başa',
    1 => 'sinəyə',
    2 => 'gövdəyə',
    3 => 'ayaqa'
];

$raundlar = [];

foreach ($izle_gedisat as $gedisat) {

    $raund_nomresi = (int)$gedisat['raund'];

    if ($raund_nomresi <= 0) {
        $raund_nomresi = 1;
    }

    if (!isset($raundlar[$raund_nomresi])) {
        $raundlar[$raund_nomresi] = [];
    }

    $raundlar[$raund_nomresi][] = $gedisat;
}

ksort($raundlar);
krsort($raundlar);
// =========================================================
// CAN SISTEMI - BAŞLANĞIC MAX CAN HESABI
// =========================================================

$oyuncu1_son_can = (int)$izle_duel['oyuncu1_can'];
$oyuncu2_son_can = (int)$izle_duel['oyuncu2_can'];

$oyuncu1_umumi_zarar = 0;
$oyuncu2_umumi_zarar = 0;

// Bütün zərərləri hesablayırıq
foreach ($raundlar as $raund_listesi) {

    foreach ($raund_listesi as $g) {

        $zarar = (int)$g['zerbe_zarari'];

        if ($zarar <= 0) {
            continue;
        }

        $eden_id = (int)$g['zerbe_eden_id'];

        if ($eden_id === (int)$izle_duel['oyuncu1_id']) {

            // Oyunçu 1 vurub -> Oyunçu 2 can itirib
            $oyuncu2_umumi_zarar += $zarar;

        } else {

            // Oyunçu 2 vurub -> Oyunçu 1 can itirib
            $oyuncu1_umumi_zarar += $zarar;
        }
    }
}

$oyuncu1_max_can = $oyuncu1_son_can + $oyuncu1_umumi_zarar;
$oyuncu2_max_can = $oyuncu2_son_can + $oyuncu2_umumi_zarar;

if ($oyuncu1_max_can <= 0) {
    $oyuncu1_max_can = 1;
}

if ($oyuncu2_max_can <= 0) {
    $oyuncu2_max_can = 1;
}

// =========================================================
// YUXARIDAKI CAN BARLARI ÜÇÜN FINAL FAİZ
// =========================================================

$oyuncu1_can_faiz_top =
    ($oyuncu1_son_can / $oyuncu1_max_can) * 100;

$oyuncu2_can_faiz_top =
    ($oyuncu2_son_can / $oyuncu2_max_can) * 100;

if ($oyuncu1_can_faiz_top < 0) {
    $oyuncu1_can_faiz_top = 0;
}

if ($oyuncu1_can_faiz_top > 100) {
    $oyuncu1_can_faiz_top = 100;
}

if ($oyuncu2_can_faiz_top < 0) {
    $oyuncu2_can_faiz_top = 0;
}

if ($oyuncu2_can_faiz_top > 100) {
    $oyuncu2_can_faiz_top = 100;
}

$oyuncu1_itirilmis_faiz_top =
    100 - $oyuncu1_can_faiz_top;

$oyuncu2_itirilmis_faiz_top =
    100 - $oyuncu2_can_faiz_top;
    ?>
<u><font color="green"> <?php echo $oyuncu1_login; ?> </font></u> :

<span style="position:relative; display:inline-block; line-height:0;">

    <img src="img/can.png" alt="can">

    <span style="
        position:absolute;
        top:0;
        right:0;
        height:100%;
        width:<?php echo $oyuncu1_itirilmis_faiz_top; ?>%;
        background:#FF0000;
        opacity:0.8;
    "></span>

    <span style="
        position:absolute;
        top:50%;
        left:50%;
        transform:translate(-50%,-50%);
        color:#FFFFFF;
        font-weight:bold;
        font-size:11px;
        text-shadow:1px 1px 2px #000;
        z-index:2;
    ">
        <?php echo $oyuncu1_son_can; ?>
    </span>

</span>

<hr/>

<u><font color="green"> <?php echo $oyuncu2_login; ?> </font></u> :

<span style="position:relative; display:inline-block; line-height:0;">

    <img src="img/can.png" alt="can">

    <span style="
        position:absolute;
        top:0;
        right:0;
        height:100%;
        width:<?php echo $oyuncu2_itirilmis_faiz_top; ?>%;
        background:#FF0000;
        opacity:0.8;
    "></span>

    <span style="
        position:absolute;
        top:50%;
        left:50%;
        transform:translate(-50%,-50%);
        color:#FFFFFF;
        font-weight:bold;
        font-size:11px;
        text-shadow:1px 1px 2px #000;
        z-index:2;
    ">
        <?php echo $oyuncu2_son_can; ?>
    </span>

</span>

<br/>
<?php
if ((int)$izle_duel['qalib_id'] > 0) {

    if (
        (int)$izle_duel['qalib_id'] ===
        (int)$izle_duel['oyuncu1_id']
    ) {

        $qalib_adi = $oyuncu1_login;

    } elseif (
        (int)$izle_duel['qalib_id'] ===
        (int)$izle_duel['oyuncu2_id']
    ) {

        $qalib_adi = $oyuncu2_login;
    }
}

$son_gosterilen_raund = !empty($raundlar)
    ? max(array_keys($raundlar))
    : 0;
    $log_oyuncu1_can = $oyuncu1_son_can;
$log_oyuncu2_can = $oyuncu2_son_can;
?>

<br>

<?php
/*
 * =========================================================
 * YEKUN DUEL NƏTİCƏSİ
 * =========================================================
 *
 * qalib_id > 0:
 *     - can 0-dırsa → normal döyüş qələbəsi
 *     - can 0 deyilsə → timeout qələbəsi
 *
 * qalib_id = 0:
 *     - heç-heçə
 */

$duel_qalib_goster = false;
$duel_timeout_goster = false;
$duel_hec_hece_goster = false;

$qalib_id = (int)$izle_duel['qalib_id'];

$oyuncu1_can_final = (int)$izle_duel['oyuncu1_can'];
$oyuncu2_can_final = (int)$izle_duel['oyuncu2_can'];
$duel_bitdi_final = (int)$izle_duel['bitdi'];

/*
 * =========================================================
 * HƏR İKİ TƏRƏFİN CANI 0 OLUBSA → HEÇ-HEÇƏ
 * =========================================================
 */

$duel_can0_hec_hece_goster = false;

if (
    (int)$izle_duel['bitdi'] === 1 &&
    $qalib_id === 0 &&
    $oyuncu1_can_final <= 0 &&
    $oyuncu2_can_final <= 0 &&
    count($izle_gedisat) > 0
) {
    $duel_can0_hec_hece_goster = true;
}





/*
 * =========================================================
 * QALİB VAR
 * =========================================================
 */

if (
    (int)$izle_duel['bitdi'] === 1 &&
    $qalib_id > 0
) {

    $duel_qalib_goster = true;

    /*
     * Can 0-dırsa normal döyüşlə bitib.
     *
     * Can 0 deyilsə timeout-la bitib.
     */
    if (
        $oyuncu1_can_final > 0 &&
        $oyuncu2_can_final > 0
    ) {
        $duel_timeout_goster = true;
    }
}


/*
 * =========================================================
 * HEÇ-HEÇƏ
 * =========================================================
 */

/*
 * =========================================================
 * HEÇ-HEÇƏ
 * =========================================================
 *
 * Duel 3 dəqiqə ərzində heç bir tərəfin zərbə
 * cəhdi etməməsi səbəbilə bitibsə.
 *
 * Burada duel_gedisati boş olmaq məcburi deyil.
 * Əsas yoxlama: duel bitib + qalib yoxdur.
 */

/*
 * =========================================================
 * HEÇ-HEÇƏ
 * =========================================================
 *
 * Duel timeout nəticəsində heç-heçə bitibsə,
 * qalib_id = 0 olur.
 *
 * Raundun neçə olması və duel_gedisati-da
 * məlumat olub-olmaması vacib deyil.
 */

if (
    (int)$izle_duel['hec_hece'] === 1
) {
    $duel_hec_hece_goster = true;
}
?>

<?php if ($duel_qalib_goster): ?>

<?php if ($duel_timeout_goster): ?>

<?php
/*
 * =========================================================
 * TIMEOUT NƏTİCƏSİ
 * =========================================================
 *
 * Bir tərəf zərbə vurdu,
 * digər tərəf vaxtında zərbə vurmadı.
 *
 * Kim baxırsa-baxsın eyni nəticə göstərilir.
 */

/*
 * Timeout qalibinin adı
 */
if ($qalib_id === (int)$izle_duel['oyuncu1_id']) {

    $timeout_qalib_adi = $oyuncu1_login;

} else {

    $timeout_qalib_adi = $oyuncu2_login;

}
?>

<b>
    
        <font color="green">
            <?php echo $timeout_qalib_adi; ?>
        </font>
    
    Qalib Gəldi
</b>

<br>


   <i> Reqib 3 dəqiqə ərzində zərbə atmadı</i>


<br><br>


<?php else: ?>

<!-- =====================================================
     CAN 0 İLƏ BİTƏN NORMAL DUEL
===================================================== -->

<b>
    <u>
        <font color="green">
            <?php echo $qalib_adi; ?>
        </font>
    </u>
    Qalib Gəldi
</b>

<br><br>

<?php endif; ?>
<?php elseif ($duel_hec_hece_goster): ?>

    <i>
        <u>
            Döyüş heç-heçə oldu, hər iki tərəf 3 dəqiqə ərzində zərbə atmadı!
        </u>
    </i>

    <br><br>

<?php endif; ?>


<?php if ($duel_can0_hec_hece_goster): ?>

            <i style="font-size:13px;">Döyüş Heç-Heçə oldu!</i>
        </span>
    </div>

<br>

<?php endif; ?>


<?php foreach ($raundlar as $raund => $gedisat_listesi): ?>

<u>Raund: <?php echo (int)$raund; ?></u><br>


<?php foreach ($gedisat_listesi as $gedisat): ?>

<?php
$gedisat_zarar = (int)$gedisat['zerbe_zarari'];

if ($gedisat_zarar < 0) {
    $gedisat_zarar = 0;
}

if ((int)$gedisat['zerbe_eden_id'] === (int)$izle_duel['oyuncu1_id']) {

    $gedisat_oyuncu1_can = $log_oyuncu1_can;
    $gedisat_oyuncu2_can = $log_oyuncu2_can;

    $log_oyuncu2_can += $gedisat_zarar;

} else {

    $gedisat_oyuncu1_can = $log_oyuncu1_can;
    $gedisat_oyuncu2_can = $log_oyuncu2_can;

    $log_oyuncu1_can += $gedisat_zarar;
}
$zerbe_eden_id = (int)$gedisat['zerbe_eden_id'];

if ($zerbe_eden_id === (int)$izle_duel['oyuncu1_id']) {

    $zerbe_eden = $oyuncu1_login;
    $reqib_adi = $oyuncu2_login;

} else {

    $zerbe_eden = $oyuncu2_login;
    $reqib_adi = $oyuncu1_login;
}
$oyuncu1_can_faiz =
    ($gedisat_oyuncu1_can / $oyuncu1_max_can) * 100;

$oyuncu2_can_faiz =
    ($gedisat_oyuncu2_can / $oyuncu2_max_can) * 100;

if ($oyuncu1_can_faiz < 0) {
    $oyuncu1_can_faiz = 0;
}

if ($oyuncu1_can_faiz > 100) {
    $oyuncu1_can_faiz = 100;
}

if ($oyuncu2_can_faiz < 0) {
    $oyuncu2_can_faiz = 0;
}

if ($oyuncu2_can_faiz > 100) {
    $oyuncu2_can_faiz = 100;
}

$oyuncu1_itirilmis_faiz = 100 - $oyuncu1_can_faiz;
$oyuncu2_itirilmis_faiz = 100 - $oyuncu2_can_faiz;
$zerbe_yeri = $hucum_adlari[
    (int)$gedisat['zerbe_yeri']
] ?? 'başa';

$zerbe_zarari = (int)$gedisat['zerbe_zarari'];
$xeta = (int)$gedisat['xeta'];
$mudafie = (int)$gedisat['mudafie'];
$kritik = (int)$gedisat['kritik'];
?>

<?php if ($xeta): ?>

    <u>
        <font color="green">
            <?php echo $zerbe_eden; ?>
        </font>
    </u>

    vurdu [<b><?php echo $zerbe_yeri; ?></b>]

    <font color="red">
        <b>[Xəta]</b>
    </font>

    <br>

<?php elseif ($zerbe_zarari > 0): ?>

    <u>
        <font color="green">
            <?php echo $zerbe_eden; ?>
        </font>
    </u>

    <b><?php echo $zerbe_zarari; ?></b>
       <?php if ($kritik): ?>
            <b style="color:red;"><i>[krit]</i></b>
    <?php endif; ?>zərbə vurdu [<b><?php echo $zerbe_yeri; ?></b>]

 

    <br>

<?php elseif ($mudafie): ?>

    <u>
        <font color="green">
            <?php echo $zerbe_eden; ?>
        </font>
    </u>

    vurdu [<b><?php echo $zerbe_yeri; ?></b>]

    <u>
        <font color="green">
            <?php echo $reqib_adi; ?>
        </font>
    </u>

    zərbəni dəf etdi

    <br>

<?php else: ?>

    <u>
        <font color="green">
            <?php echo $zerbe_eden; ?>
        </font>
    </u>

    vurdu [<b><?php echo $zerbe_yeri; ?></b>]

    <u>
        <font color="green">
            <?php echo $reqib_adi; ?>
        </font>
    </u>

    zərbəni dəf etdi

    <br>

<?php endif; ?>


<?php endforeach; ?>



<br>

<?php endforeach; ?>

<input type="button" class="button" value="Geri" onclick="goGeri()">

<?php endif; ?>


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


