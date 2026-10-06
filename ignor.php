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
   İSTİFADƏÇİ MƏLUMATLARI
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        login,
        qızıl,
        brılyant,
        enerjı
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
   DƏYİŞƏNLƏR
========================================================= */

$go  = $_GET['go'] ?? '';
$mod = $_GET['mod'] ?? '';

$iqnor_mesaj = '';
$emeliyyat_sonrasi = false;


/* =========================================================
   OXUNMAMIŞ MƏKTUBLAR
========================================================= */

$stmt_unread = $pdo->prepare("
    SELECT COUNT(*)
    FROM mesajlar
    WHERE mesaji_alan_nik = :my_id
      AND oxundu = 0
");

$stmt_unread->execute([
    ':my_id' => $my_id
]);

$unread_count = (int)$stmt_unread->fetchColumn();

/* =========================================================
   GÖZLƏYƏN DOSTLUQ SORĞULARI
========================================================= */

$stmt_dostluq = $pdo->prepare("
    SELECT COUNT(*)
    FROM dostluq
    WHERE alan_id = :my_id
      AND status = 0
");

$stmt_dostluq->execute([
    ':my_id' => $my_id
]);

$dostluq_sayi = (int)$stmt_dostluq->fetchColumn();



/* =========================================================
   DOSTLUQ SAYI
   YALNIZ QƏBUL EDİLMİŞ DOSTLAR
========================================================= */

/* =========================================================
   GƏLƏN DOSTLUQ TƏKLİFLƏRİ SAYI
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
   İQNOR ET
========================================================= */

if (
    $mod === 'add' &&
    (
        isset($_POST['nick']) ||
        isset($_GET['nick'])
    )
) {

    $emeliyyat_sonrasi = true;

    $nick = trim(
        $_POST['nick'] ?? $_GET['nick'] ?? ''
    );

    if ($nick === '') {

        $iqnor_mesaj = 'İqnor Etmək istədiyiniz Ləqəbi yazın!';

    } else {

        /* İSTİFADƏÇİNİ TAP */

        $stmt_user = $pdo->prepare("
            SELECT
                id,
                login
            FROM users
            WHERE login = :login
            LIMIT 1
        ");

        $stmt_user->execute([
            ':login' => $nick
        ]);

        $iqnor_user = $stmt_user->fetch(PDO::FETCH_ASSOC);


        /* İSTİFADƏÇİ YOXDUR */

        if (!$iqnor_user) {

            $iqnor_mesaj =
                'Bu Ləqəbli İstifadəçi Mövcud Deyil!';

        }

        /* ÖZÜNÜ İQNORA ATMA */

        elseif ((int)$iqnor_user['id'] === $my_id) {

            $iqnor_mesaj =
                'Özünüzü iqnora ata bilməzsiniz!';

        }

        else {

            $iqnor_user_id =
                (int)$iqnor_user['id'];


            /* ARTIQ İQNORDA OLUB-OLMADIĞINI YOXLAYIRIQ */

            $stmt_check = $pdo->prepare("
                SELECT id
                FROM iqnor
                WHERE user_id = :user_id
                  AND iqnor_id = :iqnor_id
                LIMIT 1
            ");

            $stmt_check->execute([
                ':user_id'  => $my_id,
                ':iqnor_id' => $iqnor_user_id
            ]);


            if ($stmt_check->fetch()) {

                $iqnor_mesaj =
                    '<b>'
                    . htmlspecialchars(
                        $iqnor_user['login'],
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    . '</b>, Artıq İqnor siyahınızdadır!';

            }

            else {

                /* İQNORA ƏLAVƏ ET */

                $stmt_add = $pdo->prepare("
                    INSERT INTO iqnor
                    (
                        user_id,
                        iqnor_id,
                        tarix
                    )
                    VALUES
                    (
                        :user_id,
                        :iqnor_id,
                        :tarix
                    )
                ");

                $stmt_add->execute([
                    ':user_id'  => $my_id,
                    ':iqnor_id' => $iqnor_user_id,
                    ':tarix'    => time()
                ]);


                $iqnor_mesaj =
                    '<b>'
                    . htmlspecialchars(
                        $iqnor_user['login'],
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    . '</b>, İqnor Edildi!';
            }
        }
    }
}


/* =========================================================
   İQNORDAN SİL
========================================================= */

if (
    $go === 'del' &&
    isset($_GET['nk'])
) {

    $emeliyyat_sonrasi = true;

    $iqnor_id = (int)$_GET['nk'];


    if ($iqnor_id > 0) {

        $stmt_del = $pdo->prepare("
            DELETE FROM iqnor
            WHERE user_id = :user_id
              AND iqnor_id = :iqnor_id
        ");

        $stmt_del->execute([
            ':user_id'  => $my_id,
            ':iqnor_id' => $iqnor_id
        ]);


        if ($stmt_del->rowCount() > 0) {

            $iqnor_mesaj =
                '<b>İqnor siyahısından silindi!</b>';

        } else {

            $iqnor_mesaj =
                'İqnor siyahısında belə istifadəçi yoxdur.';
        }
    }
}


/* =========================================================
   İQNOR SİYAHISINI GƏTİR
========================================================= */

$stmt_iqnor = $pdo->prepare("
    SELECT
        i.iqnor_id,
        u.login
    FROM iqnor i
    INNER JOIN users u
        ON u.id = i.iqnor_id
    WHERE i.user_id = :user_id
    ORDER BY i.id DESC
");

$stmt_iqnor->execute([
    ':user_id' => $my_id
]);

$iqnorlar = $stmt_iqnor->fetchAll(PDO::FETCH_ASSOC);

$iqnor_sayi = count($iqnorlar);


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

?>

<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL">

<meta
    name="keywords"
    content="klan.az, azgame, azgame.biz, online oyun"
>

<meta
    name="description"
    content="Azerbaycanda ilk Mobil Online oyunu."
>

<link
    rel="stylesheet"
    href="css.css"
>

<meta
    content="text/html; charset=utf-8"
    http-equiv="content-type"
>

<meta
    name="viewport"
    content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"
>

<title>İqnor siyahısı</title>

<script>
function goGeri() {
    window.location.href = 'ignor.php';
}
</script>


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


<?php if ($unread_count > 0): ?>

<a href="mektublar.php?go=arxiv">

<img
    src="img/mektub.gif"
    title="Məktub"
    alt="Məktub"
>

</a>

(<?php echo $unread_count; ?>)

<?php endif; ?>


<?php if ($gelen_dostluq_sayi > 0) { ?>

    <a href="dostlar.php">
        <img
            src="muxtelif/dost_pilus.png"
            title="Yeni dostluq təklifi"
            alt="Dost"
        />
    </a>

    (<?php echo $gelen_dostluq_sayi; ?>)

<?php } ?>



</div>

</div>

</div>


<div class="space"></div>


<div
    style="background:none repeat scroll 0 0 #888686;height:1px;"
></div>


<!-- =====================================================
     ƏSAS MƏZMUN
===================================================== -->

<div class="info">


<?php if ($emeliyyat_sonrasi): ?>


<!-- =====================================================
     ƏMƏLİYYAT NƏTİCƏSİ
     SİYAHI BURADA GÖSTƏRİLMİR
===================================================== -->

<?php echo $iqnor_mesaj; ?>

<br/>
<br/>


<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>


<?php else: ?>


<!-- =====================================================
     NORMAL İQNOR SİYAHISI
===================================================== -->

<b>İqnor siyahınız!</b>

<br/>


<?php if ($iqnor_sayi === 0): ?>

İqnor siyahınız boşdur.

<br/>


<?php else: ?>

İqnor siyahınızda
<?php echo $iqnor_sayi; ?>
nefer var

<br/>


<?php

$nomre = 1;

foreach ($iqnorlar as $iqnor):

    $iqnor_id = (int)$iqnor['iqnor_id'];

    $iqnor_login = htmlspecialchars(
        $iqnor['login'],
        ENT_QUOTES,
        'UTF-8'
    );

?>


<?php echo $nomre; ?>)


<a
    href="ignor.php?go=del&amp;nk=<?php echo $iqnor_id; ?>"
>
[x]
</a>

|

<a
    href="infoforce.php?uid=<?php echo $iqnor_id; ?>"
>
<?php echo $iqnor_login; ?>
</a>


<br/>


<?php

$nomre++;

endforeach;

?>


<?php endif; ?>


<!-- =====================================================
     İQNOR ET FORMU
===================================================== -->

<form
    method="post"
    action="ignor.php?mod=add"
>

<br/>

<hr/>

<b>Ləqəb:</b>

<br/>

<input
    type="text"
    name="nick"
    maxlength="25"
>

<br/>

<input
    type="hidden"
    name="action"
    value="save"
>

<input
    type="submit"
    value="İqnor Et"
>

<hr/>

</form>


<a href="shexsi_sehife.php?">
Şəxsi səhifəniz
</a>

<br/>


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

[<a href="forum/mozu2.php?">
Forum
</a>]

[<a href="shexsi_sehife.php?">
Qurğular
</a>]


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


Sciript name: Qanlı efsane(modern version)


<br/>


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

