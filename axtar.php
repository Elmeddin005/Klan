

<?php

session_start();

require_once "config.php";
require_once "user_data.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];


/* =========================================================
   GİRİŞ YOXLAMASI
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];


/* =========================================================
   YENİ MESAJ SAYI
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

$user_id = (int)$user['id'];
$user_login = $user['login'];
$user_ad = $user['ad'];


/* =========================================================
   OYUNÇU SƏVİYYƏSİ
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
   ONLINE VAXTI
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
   ONLINE OYUNÇULARIN SAYI
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
   AXTARIŞ DƏYİŞƏNLƏRİ
========================================================= */

$axtaris_neticəsi = null;

$oxsar_leqebler = [];

$oxsar_leqeb_sayi = 0;

$nick = '';

$səhifə = isset($_GET['page'])
    ? max(1, (int)$_GET['page'])
    : 1;

$sehife_basina = 10;

$umumi_neticeler = [];

$umumi_neticeler_sayi = 0;

$sehife_sayi = 1;


/* =========================================================
   AXTARIŞ
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'view'
) {

    /*
     * Birinci səhifədə POST-dan gəlir.
     * Sonrakı səhifələrdə GET ?q= ilə saxlanılır.
     */

    $nick = trim($_POST['nick'] ?? '');

    if ($nick === '' && isset($_GET['q'])) {
        $nick = trim($_GET['q']);
    }


    if ($nick !== '') {


        /* =================================================
           ID İLƏ AXTARIŞ
        ================================================= */

        if (ctype_digit($nick)) {

            $stmt_search = $pdo->prepare("
                SELECT
                    id,
                    login,
                    oyuncunun_seviyyesi,
                    movqe,
                    vip
                FROM users
                WHERE id = :id
                LIMIT 1
            ");

            $stmt_search->execute([
                ':id' => (int)$nick
            ]);

            $axtaris_neticəsi =
                $stmt_search->fetch(PDO::FETCH_ASSOC);

            /*
             * ID axtarışında oxşar nəticə göstərilmir.
             */

            $oxsar_leqebler = [];

            $oxsar_leqeb_sayi = 0;


        } else {


            /* =================================================
               XÜSUSİ SİMVOLLARI ESCAPE ET
            ================================================= */

            $axtaris = $nick;

            /*
             * Backslash
             */

            $axtaris = str_replace(
                '\\',
                '\\\\',
                $axtaris
            );

            /*
             * %
             */

            $axtaris = str_replace(
                '%',
                '\\%',
                $axtaris
            );

            /*
             * _
             */

            $axtaris = str_replace(
                '_',
                '\\_',
                $axtaris
            );


            /* =================================================
               OYUNÇULARI AXTAR
            ================================================= */

            $stmt_users = $pdo->prepare("
                SELECT
                    id,
                    login,
                    oyuncunun_seviyyesi,
                    movqe,
                    vip
                FROM users
                WHERE login LIKE :pattern
                ORDER BY login ASC
            ");

            $stmt_users->execute([
                ':pattern' => '%' . $axtaris . '%'
            ]);

            $neticeler_users =
                $stmt_users->fetchAll(PDO::FETCH_ASSOC);


            /* =================================================
               BOTLARI AXTAR
            ================================================= */

            $stmt_bots = $pdo->prepare("
                SELECT
                    id,
                    ad,
                    seviyye
                FROM botlar
                WHERE ad LIKE :pattern
                ORDER BY ad ASC
            ");

            $stmt_bots->execute([
                ':pattern' => '%' . $axtaris . '%'
            ]);

            $neticeler_bots =
                $stmt_bots->fetchAll(PDO::FETCH_ASSOC);


            /* =================================================
               DƏQİQ OYUNÇUNU AYIR
            ================================================= */

            foreach ($neticeler_users as $netice) {

                /*
                 * Dəqiq ləqəbdirsə:
                 * yalnız onu saxlayırıq.
                 */

                if (
                    strcasecmp(
                        $netice['login'],
                        $nick
                    ) === 0
                ) {

                    $axtaris_neticəsi =
                        $netice;

                } else {

                    /*
                     * Oxşar oyunçu
                     */

                    $netice['tip'] = 'oyuncu';

                    $netice['siralanan_ad'] =
                        $netice['login'];

                    $umumi_neticeler[] =
                        $netice;
                }
            }


            /* =================================================
               BOTLARI OYUNÇULARA QAT
            ================================================= */

            foreach ($neticeler_bots as $bot) {

                $bot_netice = [

                    'id' => (int)$bot['id'],

                    'login' => $bot['ad'],

                    'oyuncunun_seviyyesi' =>
                        (int)$bot['seviyye'],

                    'movqe' => 0,

                    'tip' => 'bot',

                    'siralanan_ad' =>
                        $bot['ad']
                ];

                $umumi_neticeler[] =
                    $bot_netice;
            }


            /* =================================================
               OYUNÇU + BOTLARI AD ÜZRƏ SIRALA
            ================================================= */

            usort(
                $umumi_neticeler,
                function ($a, $b) {

                    return strcasecmp(
                        $a['siralanan_ad'],
                        $b['siralanan_ad']
                    );
                }
            );


            /* =================================================
               ÜMUMİ NƏTİCƏ SAYI
            ================================================= */

            $umumi_neticeler_sayi =
                count($umumi_neticeler);


            /*
             * Burada ümumi say saxlanılır.
             * Məsələn 27 nəticə varsa:
             *
             * 1-ci səhifə = 10
             * 2-ci səhifə = 10
             * 3-cü səhifə = 7
             */

            $oxsar_leqeb_sayi =
                $umumi_neticeler_sayi;


            /* =================================================
               SƏHİFƏ SAYI
            ================================================= */

            $sehife_sayi = max(
                1,
                (int)ceil(
                    $umumi_neticeler_sayi /
                    $sehife_basina
                )
            );


            /* =================================================
               SƏHİFƏNİ YOXLA
            ================================================= */

            if ($səhifə > $sehife_sayi) {
                $səhifə = $sehife_sayi;
            }


            /* =================================================
               AKTUAL SƏHİFƏNİN NƏTİCƏLƏRİ
            ================================================= */

            $baslangic =
                ($səhifə - 1) *
                $sehife_basina;


            $oxsar_leqebler =
                array_slice(
                    $umumi_neticeler,
                    $baslangic,
                    $sehife_basina
                );
        }
    }
}

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
    content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры"
>

<link
    rel="stylesheet"
    href="css.css"
    type="text/css"
>

<meta
    content="text/html; charset=utf-8"
    http-equiv="content-type"
>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, maximum-scale=3.0"
>

<title>
    Axtaris | Klan.Az Qanli Efsane
</title>


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


<!-- =========================================================
     HEADER
========================================================= -->

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

            <?php
            echo (int)$user['qızıl'];
            ?>


            <img
                src="img/brill.png"
                title="Brilliant"
                alt=""
            >

            <?php
            echo (int)$user['brılyant'];
            ?>


            <img
                src="img/energy.png"
                title="Enerji"
                alt=""
            >

            <?php
            echo (int)$user['enerjı'];
            ?>


            <?php if ($new_message_count > 0) { ?>

                <a href="arxiv.php?go=goster">

                    <img
                        src="img/mektub.gif"
                        title="Məktub"
                        alt="Məktub"
                    >

                </a>

                (<?php echo $new_message_count; ?>)

            <?php } ?>


            <?php if ($dostluq_sayi > 0) { ?>

                <a href="dostlar.php">

                    <img
                        src="muxtelif/dost_pilus.png"
                        title="Dost"
                        alt="Dost"
                    >

                </a>

                (<?php echo $dostluq_sayi; ?>)

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


<!-- =========================================================
     EXP BAR
========================================================= -->

<div class="fl b exp_count">

    <div style="margin-top:-2px;">

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
            style="
                width:<?php echo $progress; ?>%;
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


<?php if (!isset($_GET['go'])) { ?>


    <!-- =====================================================
         AXTARIŞ FORMU
    ====================================================== -->

    <center>

        <br>

        <form
            method="post"
            action="axtar.php?go=view"
        >

            <b>
                Leqəb və ya ID:
            </b>

            <br>

            <input
                type="text"
                size="16"
                name="nick"
                maxlength="50"
                value=""
            >

            <br>

            <input
                type="submit"
                class="button"
                value="Axtar"
            >

            <br><br>

        </form>

    </center>


<?php } else { ?>


    <!-- =====================================================
         DƏQİQ NƏTİCƏ TAPILMADI
    ====================================================== -->

    <?php if (!$axtaris_neticəsi) { ?>


        <div class="error">

            <img
                src="muxtelif/eror.png"
                alt=""
            >

            Axtardığınız istifadəçi tapılmadı
        </div>


        <?php if ($oxsar_leqeb_sayi > 0) { ?>


            <!-- =================================================
                 SARI XƏTT
            ================================================== -->
<br>
            <div class="line"></div>


            <!-- =================================================
                 OXŞAR LƏQƏBLƏR + BOTLAR
            ================================================== -->

            <grey>

                "<b>
                    <?php echo $oxsar_leqeb_sayi; ?>
                </b>"

                oxşar ləqəb tapıldı
<br>
            </grey>


            <br>


            <div class="standart">


                <?php foreach ($oxsar_leqebler as $oxsar) { ?>


                    <?php if (
                        ($oxsar['tip'] ?? '') === 'bot'
                    ) { ?>


                        <!-- =====================================
                             BOT
                        ====================================== -->

                        <attent>

                            <a
                                href="bot_info.php?uid=<?php echo (int)$oxsar['id']; ?>"
                                style="
                                    color:rgb(0, 0, 255) !important;
                                "
                            >

                                <?php

                                echo htmlspecialchars(
                                    $oxsar['login'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                                [

                                <?php

                                echo (int)$oxsar[
                                    'oyuncunun_seviyyesi'
                                ];

                                ?>

                                ]

                            </a>

                        </attent>


                    <?php } else { ?>


                        <!-- =====================================
                             OYUNÇU
                        ====================================== -->

                        <?php

                        $movqe =
                            (int)(
                                $oxsar['movqe'] ?? 0
                            );


                        if ($movqe === 1) {

                            $movqe_rengi = 'red';

                        } elseif ($movqe === 2) {

                            $movqe_rengi = 'green';
                             } elseif ($movqe === 3) {

                            $movqe_rengi = 'blue';

                        } else {

                            $movqe_rengi = 'white';

                        }

                        ?>


                        <attent>

                          <a href="infoforce.php?uid=<?php echo (int)$oxsar['id']; ?>"
    style="
        color:<?php
        echo htmlspecialchars(
            $movqe_rengi,
            ENT_QUOTES,
            'UTF-8'
        );
        ?> !important;
        <?php if ((int)($oxsar['vip'] ?? 0) === 1) { ?>
        text-decoration: underline !important;
        text-shadow: 1px 1px 1px #888;
        <?php } ?>
    "
>


                                <?php

                                echo htmlspecialchars(
                                    $oxsar['login'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                                [

                                <?php

                                echo (int)$oxsar[
                                    'oyuncunun_seviyyesi'
                                ];

                                ?>

                                ]

                            </a>

                        </attent>


                    <?php } ?>


                    <br>


                <?php } ?>


            </div>


            <!-- =================================================
                 SƏHİFƏ KEÇİDLƏRİ
                 SOLDAN
            ================================================== -->

            <?php if ($sehife_sayi > 1) { ?>


                <div
                    style="
                        text-align:left;
                        margin-top:8px;
                    "
                >


                    <?php if ($səhifə > 1) { ?>

                        <a
                            href="axtar.php?go=view&amp;page=<?php echo $səhifə - 1; ?>&amp;q=<?php echo urlencode($nick); ?>"
                        >

                            &lt;&lt;<?php echo $səhifə - 1; ?>

                        </a>


                        &nbsp;|&nbsp;


                    <?php } ?>


                    <b>
                        <?php echo $səhifə; ?>
                    </b>


                    <?php if ($səhifə < $sehife_sayi) { ?>


                        &nbsp;|&nbsp;


                        <a
                            href="axtar.php?go=view&amp;page=<?php echo $səhifə + 1; ?>&amp;q=<?php echo urlencode($nick); ?>"
                        >

                            <?php echo $səhifə + 1; ?>&gt;&gt;

                        </a>


                    <?php } ?>


                </div>


            <?php } ?>


        <?php } ?>


    <?php } else { ?>


        <!-- =====================================================
             DƏQİQ LƏQƏB TAPILDI
             YALNIZ 1 NƏTİCƏ
        ====================================================== -->

        <?php

        $movqe =
            (int)(
                $axtaris_neticəsi['movqe'] ?? 0
            );


        if ($movqe === 1) {

            $movqe_rengi = 'red';

        } elseif ($movqe === 2) {

            $movqe_rengi = 'green';
             } elseif ($movqe === 3) {

                            $movqe_rengi = 'blue';

        } else {

            $movqe_rengi = 'white';

        }

        ?>


        <div class="center">

            <div class="block_line">

                <b>
                    Axtardığınız istifadəçi tapıldı
                </b>

            </div>

        </div>


        <br>


        <div class="standart">

            <attent>

                Leqəb:

               <a
    href="infoforce.php?uid=<?php echo (int)$axtaris_neticəsi['id']; ?>"
    style="
        color:<?php
        echo htmlspecialchars(
            $movqe_rengi,
            ENT_QUOTES,
            'UTF-8'
        );
        ?> !important;
        <?php if ((int)($axtaris_neticəsi['vip'] ?? 0) === 1) { ?>
        text-decoration: underline !important;
        text-shadow: 1px 1px 1px #888;
        <?php } ?>
    "
>


                    <?php

                    echo htmlspecialchars(
                        $axtaris_neticəsi['login'],
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                    [

                    <?php

                    echo (int)$axtaris_neticəsi[
                        'oyuncunun_seviyyesi'
                    ];

                    ?>

                    ]

                </a>

            </attent>

        </div>


    <?php } ?>


    <br>


    <input
        type="button"
        class="button"
        value="Geri"
        onclick="goGeri('axtar.php?')"
    >


<?php } ?>


<!-- =========================================================
     FOOTER
========================================================= -->

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


                    <br><br>


              <img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>

                    <br>


                    <a href="index.php?">

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


                    <br><br>


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