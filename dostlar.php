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
$stmt_dost_sorgu = $pdo->prepare("
    SELECT COUNT(*)
    FROM dostluq
    WHERE status = 0
      AND alan_id = :my_id
");

$stmt_dost_sorgu->execute([
    ':my_id' => $my_id
]);

$dost_sorgu_sayi = (int)$stmt_dost_sorgu->fetchColumn();


/* =========================================================
   DOSTLUQ MESAJI
========================================================= */

$dostluq_mesaji = '';

if (isset($_GET['mesaj'])) {

    $mesaj_tipi = $_GET['mesaj'];

    $mesaj_nick = isset($_GET['nick'])
        ? htmlspecialchars($_GET['nick'], ENT_QUOTES, 'UTF-8')
        : '';

    if ($mesaj_tipi === 'ugurlu') {

        $dostluq_mesaji =
            '<b>' . $mesaj_nick .
            '</b> ləqəbli şəxsə dostluq təklifi göndərildi.';

    } elseif ($mesaj_tipi === 'qebul') {

        $dostluq_mesaji =
            '<b>' . $mesaj_nick .
            '</b>-in dostluq təklifi qəbul edildi.';

    } elseif ($mesaj_tipi === 'imtina') {

        $dostluq_mesaji =
            '<b>' . $mesaj_nick .
            '</b>-in dostluq təklifi red edildi.';

    } elseif ($mesaj_tipi === 'silindi') {

        $dostluq_mesaji =
            '<b>' . $mesaj_nick .
            '</b> Dostlar siyahınızdan silindi!';

    } elseif ($mesaj_tipi === 'dostdur') {

        $dostluq_mesaji =
            '<b>Error! ' . $mesaj_nick .
            '</b> Artıq sizin dostlar siyahınızdadır.';

    } elseif ($mesaj_tipi === 'gozleyir') {

        $dostluq_mesaji =
            '<b>Error!</b> ' . $mesaj_nick .
            ' üçün dostluq təklifi artıq göndərilib.';

    } elseif ($mesaj_tipi === 'tapilmadi') {

        $dostluq_mesaji =
            '<b>Error!</b> Oyunçu tapılmadı.';

    } elseif ($mesaj_tipi === 'ozun') {

        $dostluq_mesaji =
            '<b>Error!</b> Özünüzə dostluq göndərə bilməzsiniz.';
    }
}


/* =========================================================
   DOSTU SİL
   go=del&nk=ISTIFADECI_ID
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'del' &&
    isset($_GET['nk'])
) {

    $sil_id = (int)$_GET['nk'];

    if ($sil_id > 0 && $sil_id !== $my_id) {

        /* Əvvəl dostun adını tap */

        $stmt_sil_info = $pdo->prepare("
            SELECT login
            FROM users
            WHERE id = :id
            LIMIT 1
        ");

        $stmt_sil_info->execute([
            ':id' => $sil_id
        ]);

        $sil_info = $stmt_sil_info->fetch(PDO::FETCH_ASSOC);


        if ($sil_info) {

            /* Dostluğu MySQL-dən sil */

            $stmt_sil = $pdo->prepare("
                DELETE FROM dostluq
                WHERE status = 1
                AND (
                    (
                        gonderen_id = :my_id1
                        AND alan_id = :dost_id1
                    )
                    OR
                    (
                        gonderen_id = :dost_id2
                        AND alan_id = :my_id2
                    )
                )
                LIMIT 1
            ");

            $stmt_sil->execute([
                ':my_id1'   => $my_id,
                ':dost_id1' => $sil_id,
                ':dost_id2' => $sil_id,
                ':my_id2'   => $my_id
            ]);


            /* Silinmə mesajı */

            header(
                "Location: dostlar.php?mesaj=silindi&nick=" .
                urlencode($sil_info['login'])
            );

            exit;
        }
    }

    header("Location: dostlar.php");
    exit;
}


/* =========================================================
   QƏBUL ET
========================================================= */

if (
    isset($_GET['gu']) &&
    $_GET['gu'] === 'qebul' &&
    isset($_GET['idi'])
) {

    $dostluq_id = (int)$_GET['idi'];


    /* Təklifi göndərən şəxsin adını tap */

    $stmt_qebul_info = $pdo->prepare("
        SELECT u.login
        FROM dostluq d
        INNER JOIN users u
            ON u.id = d.gonderen_id
        WHERE d.id = :id
          AND d.alan_id = :alan_id
          AND d.status = 0
        LIMIT 1
    ");

    $stmt_qebul_info->execute([
        ':id'      => $dostluq_id,
        ':alan_id' => $my_id
    ]);

    $qebul_info = $stmt_qebul_info->fetch(PDO::FETCH_ASSOC);


    if ($qebul_info) {

        /* Status 0 -> 1 */

        $stmt_qebul = $pdo->prepare("
            UPDATE dostluq
            SET status = 1
            WHERE id = :id
              AND alan_id = :alan_id
              AND status = 0
            LIMIT 1
        ");

        $stmt_qebul->execute([
            ':id'      => $dostluq_id,
            ':alan_id' => $my_id
        ]);


        $nick = $qebul_info['login'];


        header(
            "Location: dostlar.php?mesaj=qebul&nick=" .
            urlencode($nick)
        );

        exit;
    }


    header("Location: dostlar.php");
    exit;
}


/* =========================================================
   İMTİNA ET
========================================================= */

if (
    isset($_GET['del']) &&
    $_GET['del'] === 'del' &&
    isset($_GET['idi'])
) {

    $dostluq_id = (int)$_GET['idi'];


    /* Təklifi göndərən şəxsin adını tap */

    $stmt_imtina_info = $pdo->prepare("
        SELECT u.login
        FROM dostluq d
        INNER JOIN users u
            ON u.id = d.gonderen_id
        WHERE d.id = :id
          AND d.alan_id = :alan_id
          AND d.status = 0
        LIMIT 1
    ");

    $stmt_imtina_info->execute([
        ':id'      => $dostluq_id,
        ':alan_id' => $my_id
    ]);

    $imtina_info = $stmt_imtina_info->fetch(PDO::FETCH_ASSOC);


    if ($imtina_info) {

        /* Sorğunu MySQL-dən sil */

        $stmt_imtina = $pdo->prepare("
            DELETE FROM dostluq
            WHERE id = :id
              AND alan_id = :alan_id
              AND status = 0
            LIMIT 1
        ");

        $stmt_imtina->execute([
            ':id'      => $dostluq_id,
            ':alan_id' => $my_id
        ]);


        $nick = $imtina_info['login'];


        header(
            "Location: dostlar.php?mesaj=imtina&nick=" .
            urlencode($nick)
        );

        exit;
    }


    header("Location: dostlar.php");
    exit;
}


/* =========================================================
   DOSTLUQ GÖNDƏR
========================================================= */

if (
    isset($_GET['mod']) &&
    $_GET['mod'] === 'add' &&
    isset($_GET['nk'])
) {

    $alan_id = (int)$_GET['nk'];


    /* Özünə göndərmək olmaz */

    if ($alan_id === $my_id) {

        header("Location: dostlar.php?mesaj=ozun");
        exit;
    }


    /* Oyunçunu tap */

    $stmt_check_user = $pdo->prepare("
        SELECT id, login
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_check_user->execute([
        ':id' => $alan_id
    ]);

    $alan_user = $stmt_check_user->fetch(PDO::FETCH_ASSOC);


    if (!$alan_user) {

        header("Location: dostlar.php?mesaj=tapilmadi");
        exit;
    }


    $alan_login = $alan_user['login'];


    /* Dostluq / gözləyən sorğu yoxla */

    $stmt_check_friend = $pdo->prepare("
        SELECT id, status, gonderen_id, alan_id
        FROM dostluq
        WHERE
        (
            gonderen_id = :my_id
            AND alan_id = :alan_id
        )
        OR
        (
            gonderen_id = :alan_id
            AND alan_id = :my_id
        )
        LIMIT 1
    ");

    $stmt_check_friend->execute([
        ':my_id'   => $my_id,
        ':alan_id' => $alan_id
    ]);

    $friend_request = $stmt_check_friend->fetch(PDO::FETCH_ASSOC);


    /* Artıq dostdursa */

    if (
        $friend_request &&
        (int)$friend_request['status'] === 1
    ) {

        header(
            "Location: dostlar.php?mesaj=dostdur&nick=" .
            urlencode($alan_login)
        );

        exit;
    }


    /* Gözləyən sorğu varsa */

    if ($friend_request) {

        header(
            "Location: dostlar.php?mesaj=gozleyir&nick=" .
            urlencode($alan_login)
        );

        exit;
    }


    /* Yeni dostluq sorğusu */

    $stmt_add_friend = $pdo->prepare("
        INSERT INTO dostluq
        (
            gonderen_id,
            alan_id,
            status,
            tarix
        )
        VALUES
        (
            :gonderen_id,
            :alan_id,
            0,
            :tarix
        )
    ");

    $stmt_add_friend->execute([
        ':gonderen_id' => $my_id,
        ':alan_id'     => $alan_id,
        ':tarix'       => time()
    ]);


    header(
        "Location: dostlar.php?mesaj=ugurlu&nick=" .
        urlencode($alan_login)
    );

    exit;
}


/* =========================================================
   NƏTİCƏ REJİMİ
========================================================= */

$netice_modu = !empty($dostluq_mesaji);


/* =========================================================
   GƏLƏN DOSTLUQ TƏKLİFLƏRİ
========================================================= */

$gelen_dostluqlar = [];

if (!$netice_modu) {

    $stmt_gelen = $pdo->prepare("
        SELECT
            d.id,
            d.gonderen_id,
            d.tarix,
            u.login
        FROM dostluq d
        INNER JOIN users u
            ON u.id = d.gonderen_id
        WHERE d.alan_id = :alan_id
          AND d.status = 0
        ORDER BY d.id DESC
    ");

    $stmt_gelen->execute([
        ':alan_id' => $my_id
    ]);

    $gelen_dostluqlar =
        $stmt_gelen->fetchAll(PDO::FETCH_ASSOC);
}


/* =========================================================
   DOSTLAR SİYAHISI
========================================================= */

$dostlar = [];

if (!$netice_modu) {

    $stmt_dostlar = $pdo->prepare("
        SELECT
            d.id,
            d.gonderen_id,
            d.alan_id,
            d.tarix,

            CASE
                WHEN d.gonderen_id = :my_id_1
                THEN d.alan_id
                ELSE d.gonderen_id
            END AS dost_id

        FROM dostluq d

        WHERE
        (
            d.gonderen_id = :my_id_2
            OR d.alan_id = :my_id_3
        )

        AND d.status = 1

        ORDER BY d.id DESC
    ");

    $stmt_dostlar->execute([
        ':my_id_1' => $my_id,
        ':my_id_2' => $my_id,
        ':my_id_3' => $my_id
    ]);

    $dostlar =
        $stmt_dostlar->fetchAll(PDO::FETCH_ASSOC);
}


/* =========================================================
   DOST ID-LƏRİ
========================================================= */

$dost_idler = [];

foreach ($dostlar as $dost) {

    $dost_idler[] = (int)$dost['dost_id'];
}


/* =========================================================
   DOSTLARIN MƏLUMATLARI
========================================================= */

$dost_melumatlari = [];

if (!empty($dost_idler)) {

    $dost_idler =
        array_values(array_unique($dost_idler));

    $placeholders = [];
    $params = [];

    foreach ($dost_idler as $index => $dost_id) {

        $key = ':dost' . $index;

        $placeholders[] = $key;

        $params[$key] = $dost_id;
    }


    $sql_dost_melumat = "
        SELECT id, login
        FROM users
        WHERE id IN (
            " . implode(',', $placeholders) . "
        )
    ";


    $stmt_dost_melumat =
        $pdo->prepare($sql_dost_melumat);

    $stmt_dost_melumat->execute($params);

    $dost_melumatlari =
        $stmt_dost_melumat->fetchAll(PDO::FETCH_ASSOC);
}


/* =========================================================
   LOGINLƏRI ID İLƏ MASSİVƏ ÇEVİR
========================================================= */

$dost_users = [];

foreach ($dost_melumatlari as $dost_user) {

    $dost_users[(int)$dost_user['id']] =
        $dost_user['login'];
}


/* =========================================================
   MESAJ SAYI
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

$new_message_count =
    (int)$stmt_new_message->fetchColumn();


/* =========================================================
   İSTİFADƏÇİ MƏLUMATLARI
========================================================= */

$user_id = (int)$user['id'];
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

$oyuncu_seviyyesi =
    (int)$stmt_seviyye->fetchColumn();

if ($oyuncu_seviyyesi < 1) {
    $oyuncu_seviyyesi = 1;
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
    ':id'   => $user_id
]);


/* =========================================================
   ONLINE OYUNÇU SAYI
========================================================= */

$stmt_online_count = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE online_oyuncu_vaxti >= :vaxt
");

$stmt_online_count->execute([
    ':vaxt' => time() - 180
]);

$online_sayi =
    (int)$stmt_online_count->fetchColumn();

?>
<!DOCTYPE html>

<html>

<head>

<meta name="robots" content="ALL">

<meta
    name="keywords"
    content="klan.az, azgame, online oyun"
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

<title>Dostlar siyahım</title>

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

<a href="menu.php">

<img
    src="img/logo.png"
    alt="Klan.az"
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


<?php if ($new_message_count > 0): ?>

<a href="arxiv.php?go=goster">

<img
    src="img/mektub.gif"
    title="Məktub"
    alt="Məktub"
>

</a>

(<?php echo $new_message_count; ?>)

<?php endif; ?>
<?php if ($dost_sorgu_sayi > 0) { ?>

    <a href="dostlar.php">
        <img
            src="muxtelif/dost_pilus.png"
            title="Dostluq sorğusu"
            alt="Dostluq sorğusu"
        />
    </a>

    (<?php echo $dost_sorgu_sayi; ?>)

<?php } ?>

</div>

</div>

</div>


<div class="space"></div>


<div
    style="
        background:none repeat scroll 0 0 #888686;
        height:1px;
    "
></div>


<!-- =====================================================
     PROGRESS
===================================================== -->

<div class="fl b exp_count">

<div style="margin-top:-2px;">

<span style="color:#ff3333">

<b>

<?php
echo isset($progress)
    ? (int)$progress
    : 0;
?>%

</b>

</span>

</div>

</div>


<div class="experience">

<div class="exp_bg">

<div class="exp_left fl"></div>

<div class="exp_right fr"></div>

<div
    style="
        width:<?php
        echo isset($progress)
            ? (int)$progress
            : 0;
        ?>%;
        height:10px;
    "
>

<div class="exp_line"></div>

<div class="exp_point"></div>

</div>

</div>

</div>


<div
    style="
        background:none repeat scroll 0 0 #888686;
        height:1px;
    "
></div>


<div class="info">


<!-- =====================================================
     NƏTİCƏ
===================================================== -->

<?php if ($netice_modu): ?>

<div class="battle_log">

<div class="standart">

<?php echo $dostluq_mesaji; ?>

</div>

</div>

<br>

<div class="line"></div>

<div class="menu">

<li>

<a href="dostlar.php?yenile=">

<img
    src="muxtelif/on.png"
    alt=""
>

Dostlar

</a>

</li>

</div>


<?php else: ?>


<!-- =====================================================
     GƏLƏN TƏKLİFLƏR
===================================================== -->

<?php if (!empty($gelen_dostluqlar)): ?>

<?php foreach ($gelen_dostluqlar as $dostluq): ?>

<div class="battle_log">

<div class="standart">

Dostluq təklif edən:

<a
    href="infoforce.php?uid=<?php
        echo (int)$dostluq['gonderen_id'];
    ?>"
>

<?php
echo htmlspecialchars(
    $dostluq['login'],
    ENT_QUOTES,
    'UTF-8'
);
?>

</a>

<br>

</div>


[

<a
    href="dostlar.php?gu=qebul&amp;idi=<?php
        echo (int)$dostluq['id'];
    ?>"
>
Qəbul et
</a>

]

|

[

<a
    href="dostlar.php?del=del&amp;idi=<?php
        echo (int)$dostluq['id'];
    ?>"
>
İmtina et
</a>

]

<br>

</div>

<br>

<?php endforeach; ?>

<?php endif; ?>


<!-- =====================================================
     DOSTLAR BAŞLIĞI
===================================================== -->

<div class="center">

<div class="block_line">

<span class="green">

<b>Dostlar siyahınız</b>

</span>

</div>

</div>

<br>


<!-- =====================================================
     DOSTLAR SİYAHISI
===================================================== -->

<?php if (!empty($dost_users)): ?>

Cəmi: <?php echo count($dost_users); ?><br>

<?php $dost_sira = 1; ?>

<?php foreach ($dost_users as $dost_id => $dost_login): ?>

<div class="standart">

<?php echo $dost_sira; ?>)

<a
    href="infoforce.php?uid=<?php echo (int)$dost_id; ?>"
>

<?php
echo htmlspecialchars(
    $dost_login,
    ENT_QUOTES,
    'UTF-8'
);
?>

</a>

On |

(<a
    href="dostlar.php?go=del&amp;nk=<?php echo (int)$dost_id; ?>"
>
Sil
</a>)

<br>

<div class="point-line"></div>

</div>







<?php $dost_sira++; ?>

<?php endforeach; ?>


<?php else: ?>

Dostlar siyahınız boşdur.

<br>
<br>

<?php endif; ?>


<!-- =====================================================
     DOSTLAR MENYUSU
===================================================== -->

<div class="line"></div>

<div class="menu">

<li>

<a href="dostlar.php?yenile=">

<img
    src="muxtelif/on.png"
    alt=""
>

Dostlar

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

[

<b>
<a href="menu.php">Menu</a>
</b>

]

[

<b>
<a href="axtar.php">Axtarış</a>
</b>

]

[

<a href="forum/mozu2.php">Forum</a>
]

[

<a href="shexsi_sehife.php">Qurğular</a>
]

<br>
<br>

<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>

<br>

<a href="index.php">

Çıxış

(

<?php
echo htmlspecialchars(
    $user_login,
    ENT_QUOTES,
    'UTF-8'
);
?>

)

</a>

<br>
<br>

<a href="menu.php?dil=tr">

Türkce:

<img
    alt="türkce"
    src="http://macera.az/klan/muxtelif/tr.gif"
    title="Türkce"
>

</a>

<br>

Sciript name:
Qanlı efsane(modern version)

<br>

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

</div>

</body>

</html>
