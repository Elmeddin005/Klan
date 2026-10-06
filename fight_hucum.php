<?php

ob_start();
session_start();

require_once "config.php";
require_once "user_data.php";
require_once "guc_parametrləri.php";
require_once "doyus_sistem.php";


/* =========================================================
   LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];

$uid   = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$lis   = isset($_GET['lis']) ? (int)$_GET['lis'] : 0;
$check = isset($_GET['check']) ? (int)$_GET['check'] : 0;
$hesabla = isset($_GET['hesabla']) ? (int)$_GET['hesabla'] : 0;

if ($uid <= 0 || $lis <= 0) {
    exit('Döyüş məlumatları düzgün deyil.');
}


/* =========================================================
   QRUP DB
========================================================= */

$pdo_qrup = new PDO(
    "mysql:host=localhost;dbname=qrup_doyus;charset=utf8mb4",
    "root",
    ""
);

$pdo_qrup->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo_qrup->setAttribute(
    PDO::ATTR_DEFAULT_FETCH_MODE,
    PDO::FETCH_ASSOC
);

/* =========================================================
   QRUP TAM BİTİBSƏ — NƏTİCƏYƏ QAYIT
========================================================= */

$stmt_qrup_status = $pdo_qrup->prepare("
    SELECT status
    FROM qruplar
    WHERE id = ?
    LIMIT 1
");

$stmt_qrup_status->execute([
    $lis
]);

$qrup_status = $stmt_qrup_status->fetchColumn();

if ($qrup_status !== false && (int)$qrup_status === 0) {
    header(
        "Location: qrup_komek.php?lis=" .
        (int)$lis .
        "&t=" .
        time()
    );
    exit;
}

/* =========================================================
   İLKİN PARAMETRLƏR
========================================================= */

$ilkin_zerbe_min = 6;
$ilkin_zerbe_max = 7;

$ilkin_can = 50;

$ilkin_mudafie = 3;

$ilkin_krit = 20;
$ilkin_antikrit = 20;

$ilkin_uvorot = 20;
$ilkin_antiuvorot = 20;


/* =========================================================
   NÖVBƏ MÜDDƏTİ
========================================================= */

$novbe_muddeti = 59;


/* =========================================================
   MÜDAFİƏ
========================================================= */

function mudafieTutdu($mudafie, $hucum)
{
    $mudafie = (int)$mudafie;
    $hucum   = (int)$hucum;

    if ($mudafie === 0) {
        return ($hucum === 0 || $hucum === 1);
    }

    if ($mudafie === 1) {
        return ($hucum === 1 || $hucum === 2);
    }

    if ($mudafie === 2) {
        return ($hucum === 2 || $hucum === 3);
    }

    if ($mudafie === 3) {
        return ($hucum === 3 || $hucum === 0);
    }

    return false;
}


/* =========================================================
   PARAMETRLƏR
========================================================= */

function yekunParametrleriHesabla(
    &$oyuncu,
    $ilkin_zerbe_min,
    $ilkin_zerbe_max,
    $ilkin_can,
    $ilkin_mudafie,
    $ilkin_krit,
    $ilkin_antikrit,
    $ilkin_uvorot,
    $ilkin_antiuvorot
) {

    $oyuncu['min_zerbe'] =
        (int)$ilkin_zerbe_min +
        (int)$oyuncu['op_min_zerbe'] +
        (int)$oyuncu['guc_zerbe'];

    $oyuncu['max_zerbe'] =
        (int)$ilkin_zerbe_max +
        (int)$oyuncu['op_max_zerbe'] +
        (int)$oyuncu['guc_zerbe'];

    $oyuncu['can'] =
        (int)$ilkin_can +
        (int)$oyuncu['op_can'] +
        (int)$oyuncu['guc_can'];

    $oyuncu['mudafie'] =
        (int)$ilkin_mudafie +
        (int)$oyuncu['op_mudafie'] +
        (int)$oyuncu['guc_mudafie'];

    $oyuncu['krit'] =
        (int)$ilkin_krit +
        (int)$oyuncu['op_krit'] +
        (int)$oyuncu['guc_krit'];

    $oyuncu['anti_krit'] =
        (int)$ilkin_antikrit +
        (int)$oyuncu['op_anti_krit'] +
        (int)$oyuncu['guc_anti_krit'];

    $oyuncu['uvorot'] =
        (int)$ilkin_uvorot +
        (int)$oyuncu['op_uvorot'] +
        (int)$oyuncu['guc_uvorot'];

    $oyuncu['anti_uvorot'] =
        (int)$ilkin_antiuvorot +
        (int)$oyuncu['op_anti_uvorot'] +
        (int)$oyuncu['guc_anti_uvorot'];
}


/* =========================================================
   QRUP
========================================================= */

$stmt = $pdo_qrup->prepare("
    SELECT *
    FROM qruplar
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$lis]);

$qrup = $stmt->fetch();

if (!$qrup) {
    exit('Qrup tapılmadı.');
}


/* =========================================================
   OYUNÇU SELECT
========================================================= */

function oyuncuTap(
    PDO $pdo,
    $qrup_id,
    $user_id,
    $teref_operator = '=',
    $teref_value = null
) {

    $allowedOperators = ['=', '!='];

    if (!in_array($teref_operator, $allowedOperators, true)) {
        $teref_operator = '=';
    }

    $sql = "
        SELECT

            qu.*,

            u.login,
            u.oyuncunun_seviyyesi,
            u.movqe,
            u.vip,

            COALESCE(op.min_zerbe, 0) AS op_min_zerbe,
            COALESCE(op.max_zerbe, 0) AS op_max_zerbe,

            COALESCE(op.can, 0) AS op_can,
            COALESCE(op.mudafie, 0) AS op_mudafie,

            COALESCE(op.krit, 0) AS op_krit,
            COALESCE(op.anti_krit, 0) AS op_anti_krit,

            COALESCE(op.uvorot, 0) AS op_uvorot,
            COALESCE(op.anti_uvorot, 0) AS op_anti_uvorot,

            COALESCE(ogb.zerbe, 0) AS guc_zerbe,
            COALESCE(ogb.mudafie, 0) AS guc_mudafie,
            COALESCE(ogb.can, 0) AS guc_can,

            COALESCE(ogb.krit, 0) AS guc_krit,
            COALESCE(ogb.anti_krit, 0) AS guc_anti_krit,

            COALESCE(ogb.uvorot, 0) AS guc_uvorot,
            COALESCE(ogb.anti_uvorot, 0) AS guc_anti_uvorot

        FROM qrup_uzvleri qu

        INNER JOIN klannn.users u
            ON u.id = qu.user_id

        LEFT JOIN klannn.oyuncu_parametrleri op
            ON op.user_id = u.id

        LEFT JOIN klannn.oyuncu_guc_bonuslari ogb
            ON ogb.user_id = u.id

        WHERE qu.qrup_id = ?
          AND qu.user_id = ?
          AND qu.status = 1
          AND qu.doyuse_qosuldu = 1
    ";

    $params = [
        (int)$qrup_id,
        (int)$user_id
    ];

    if ($teref_value !== null) {

        $sql .= "
            AND qu.terefi {$teref_operator} ?
        ";

        $params[] = $teref_value;
    }

    $sql .= "
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch();
}


/* =========================================================
   SON CAN
========================================================= */

function baslangicCaniniTap(
    PDO $pdo,
    $qrup_id,
    $user_id,
    $max_can
) {

    $max_can = max(
        1,
        (int)$max_can
    );

    $stmt = $pdo->prepare("
        SELECT son_can
        FROM qrup_uzvleri
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");

    $stmt->execute([
        (int)$qrup_id,
        (int)$user_id
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['son_can'] === null) {
        return $max_can;
    }

    return max(
        0,
        min(
            (int)$row['son_can'],
            $max_can
        )
    );
}


/* =========================================================
   SON CANI YAZ
========================================================= */

function sonCaniYaz(
    PDO $pdo,
    $qrup_id,
    $user_id,
    $can
) {

    $stmt = $pdo->prepare("
        UPDATE qrup_uzvleri
        SET son_can = ?
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");

    $stmt->execute([
        max(0, (int)$can),
        (int)$qrup_id,
        (int)$user_id
    ]);
}
/* =========================================================
   YENİ OYUNÇUNU DÖYÜŞƏ YERLƏŞDİR
========================================================= */
function yeniOyuncunuDoyuseYerlestir(
    PDO $pdo,
    $qrup_id,
    $doyus_id,
    $yeni_oyuncu_id,
    $slot,
    $ilkin_zerbe_min,
    $ilkin_zerbe_max,
    $ilkin_can,
    $ilkin_mudafie,
    $ilkin_krit,
    $ilkin_antikrit,
    $ilkin_uvorot,
    $ilkin_antiuvorot
) {

    $qrup_id = (int)$qrup_id;
    $doyus_id = (int)$doyus_id;
    $yeni_oyuncu_id = (int)$yeni_oyuncu_id;
    $slot = ((int)$slot === 2) ? 2 : 1;


    /* =================================================
       YENİ OYUNÇUNU AKTİV ET
    ================================================= */

    $stmt = $pdo->prepare("
        UPDATE qrup_uzvleri
        SET
            doyuse_qosuldu = 1
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");

    $stmt->execute([
        $qrup_id,
        $yeni_oyuncu_id
    ]);


    /* =================================================
       YENİ OYUNÇUNUN PARAMETRLƏRİNİ GÖTÜR
    ================================================= */

    $yeni_oyuncu = oyuncuTap(
        $pdo,
        $qrup_id,
        $yeni_oyuncu_id
    );

    if (!$yeni_oyuncu) {
        return false;
    }


    yekunParametrleriHesabla(
        $yeni_oyuncu,
        $ilkin_zerbe_min,
        $ilkin_zerbe_max,
        $ilkin_can,
        $ilkin_mudafie,
        $ilkin_krit,
        $ilkin_antikrit,
        $ilkin_uvorot,
        $ilkin_antiuvorot
    );


    /* =================================================
       YENİ OYUNÇUNUN ÖZ CANINI GÖTÜR
    ================================================= */

    $yeni_max_can = max(
        1,
        (int)$yeni_oyuncu['can']
    );

    $yeni_can = baslangicCaniniTap(
        $pdo,
        $qrup_id,
        $yeni_oyuncu_id,
        $yeni_max_can
    );


    /* =================================================
       HANSI YERƏ YAZILACAĞINI MÜƏYYƏNLƏŞDİR
    ================================================= */

    if ($slot === 1) {

        $slot_id = 'oyuncu1_id';
        $slot_can = 'oyuncu1_can';
        $slot_hucum = 'oyuncu1_hucum';
        $slot_mudafie = 'oyuncu1_mudafie';
        $slot_vaxt = 'oyuncu1_hucum_vaxti';

    } else {

        $slot_id = 'oyuncu2_id';
        $slot_can = 'oyuncu2_can';
        $slot_hucum = 'oyuncu2_hucum';
        $slot_mudafie = 'oyuncu2_mudafie';
        $slot_vaxt = 'oyuncu2_hucum_vaxti';
    }


    /* =================================================
       KÖHNƏ OYUNÇUNUN YERİNƏ YENİ OYUNÇUNU YAZ
       KÖHNƏ RAUND NƏTİCƏLƏRİNİ TƏMİZLƏ
    ================================================= */

    $sql = "
        UPDATE qrup_doyusleri
        SET
            {$slot_id} = ?,
            {$slot_can} = ?,

            raund = 1,

            {$slot_hucum} = NULL,
            {$slot_mudafie} = NULL,
            {$slot_vaxt} = NULL,

            son_oyuncu1_hucum = NULL,
            son_oyuncu1_zarar = 0,
            son_oyuncu1_kritik = 0,
            son_oyuncu1_xeta = 0,

            son_oyuncu2_hucum = NULL,
            son_oyuncu2_zarar = 0,
            son_oyuncu2_kritik = 0,
            son_oyuncu2_xeta = 0,

            son_hucum_eden = NULL,
            son_mudafie_eden = NULL,
            son_zarar = 0,
            son_kritik = 0,
            son_xeta = 0,
            son_mudafie = 0

        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $yeni_oyuncu_id,
        $yeni_can,
        $doyus_id
    ]);
/* =================================================
   QARŞI TƏRƏFƏ "RƏQİB DƏYİŞDİ" BİLDİRİŞİ
================================================= */

if ($slot === 1) {

    $stmt = $pdo->prepare("
        SELECT oyuncu2_id
        FROM qrup_doyusleri
        WHERE id = ?
        LIMIT 1
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT oyuncu1_id
        FROM qrup_doyusleri
        WHERE id = ?
        LIMIT 1
    ");
}

$stmt->execute([
    $doyus_id
]);

$reqib_id = (int)$stmt->fetchColumn();

if ($reqib_id > 0) {

    $stmt = $pdo->prepare("
        UPDATE qrup_uzvleri
        SET doyus_bildirisi = 1
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");

    $stmt->execute([
        $qrup_id,
        $reqib_id
    ]);
}

    /* =================================================
       YENİ OYUNÇUNUN SON CANINI SAXLA
    ================================================= */

    sonCaniYaz(
        $pdo,
        $qrup_id,
        $yeni_oyuncu_id,
        $yeni_can
    );


    return [
        'id'  => $yeni_oyuncu_id,
        'can' => $yeni_can
    ];
}
/* =========================================================
   RƏQİB DƏYİŞDİ BİLDİRİŞİ

   Yalnız əvvəlki döyüşdə həqiqətən sağ qalan
   oyunçunun rəqibi dəyişəndə bildiriş verilir.

   Yeni qoşulan oyunçuya və ilk rəhbərlərə
   bildiriş göstərilmir.
========================================================= */
function qrupReqibBildirisStatusu(
    PDO $pdo,
    $qrup_id,
    $my_id,
    $doyus_id,
    $hazirki_reqib_id
) {

    $qrup_id = (int)$qrup_id;
    $my_id = (int)$my_id;
    $doyus_id = (int)$doyus_id;
    $hazirki_reqib_id = (int)$hazirki_reqib_id;

    if (!isset($_SESSION['qrup_reqib_durumleri']) ||
        !is_array($_SESSION['qrup_reqib_durumleri'])) {
        $_SESSION['qrup_reqib_durumleri'] = [];
    }

    $key = (string)$qrup_id;

    /* İlk dəfə bu qrupda görünürsə:
       ilkin rəhbər / yeni qoşulan oyunçu bildiriş almır. */
    if (!isset($_SESSION['qrup_reqib_durumleri'][$key]) ||
        !is_array($_SESSION['qrup_reqib_durumleri'][$key])) {

        $_SESSION['qrup_reqib_durumleri'][$key] = [
            'doyus_id' => $doyus_id,
            'reqib_id' => $hazirki_reqib_id
        ];

        return 'AKTIV';
    }

    $evvelki_durum =
        $_SESSION['qrup_reqib_durumleri'][$key];

    $evvelki_doyus_id =
        isset($evvelki_durum['doyus_id'])
            ? (int)$evvelki_durum['doyus_id']
            : 0;

    $evvelki_reqib_id =
        isset($evvelki_durum['reqib_id'])
            ? (int)$evvelki_durum['reqib_id']
            : 0;

    /* Eyni döyüşdürsə yalnız real rəqib ID dəyişməsini yoxla. */
    if ($evvelki_doyus_id === $doyus_id) {

        if ($evvelki_reqib_id !== $hazirki_reqib_id) {

            $_SESSION['qrup_reqib_durumleri'][$key] = [
                'doyus_id' => $doyus_id,
                'reqib_id' => $hazirki_reqib_id
            ];

            return 'REQIB_DEYISDI';
        }

        return 'AKTIV';
    }

    /*
     * Yeni döyüş yaranıb.
     * Bildiriş yalnız əvvəlki, dərhal əvvəlki döyüşün
     * iştirakçısı olan və həmin döyüşdə sağ qalan oyunçuya verilir.
     */
    $stmt = $pdo->prepare("\n        SELECT\n            id,\n            oyuncu1_id,\n            oyuncu2_id,\n            oyuncu1_can,\n            oyuncu2_can\n        FROM qrup_doyusleri\n        WHERE qrup_id = ?\n          AND bitdi = 1\n          AND id < ?\n        ORDER BY id DESC\n        LIMIT 1\n    ");

    $stmt->execute([
        $qrup_id,
        $doyus_id
    ]);

    $evvelki_doyus = $stmt->fetch(PDO::FETCH_ASSOC);

    $survivor = false;
    $evvelki_doyus_reqib_id = 0;

    if ($evvelki_doyus) {

        if ((int)$evvelki_doyus['oyuncu1_id'] === $my_id) {

            $evvelki_doyus_reqib_id =
                (int)$evvelki_doyus['oyuncu2_id'];

            $survivor =
                (int)$evvelki_doyus['oyuncu1_can'] > 0;

        } elseif ((int)$evvelki_doyus['oyuncu2_id'] === $my_id) {

            $evvelki_doyus_reqib_id =
                (int)$evvelki_doyus['oyuncu1_id'];

            $survivor =
                (int)$evvelki_doyus['oyuncu2_can'] > 0;
        }
    }

    $_SESSION['qrup_reqib_durumleri'][$key] = [
        'doyus_id' => $doyus_id,
        'reqib_id' => $hazirki_reqib_id
    ];

    if (
        $survivor &&
        $evvelki_doyus_reqib_id > 0 &&
        $evvelki_doyus_reqib_id !== $hazirki_reqib_id
    ) {
        return 'REQIB_DEYISDI';
    }

    return 'AKTIV';
}

/* =========================================================
   DÖYÜŞDƏN ÇIXARILMA / RƏQİB DƏYİŞİKLİYİ YOXLAMASI
========================================================= */

if (
    isset($_GET['cixaris']) &&
    (int)$_GET['cixaris'] === 1
) {

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');


    /*
     * MƏN QRUPDA VARAMMI?
     */
    $stmt = $pdo_qrup->prepare("
        SELECT
            user_id,
            terefi,
            doyuse_qosuldu,
               doyus_bildirisi
        FROM qrup_uzvleri
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");

    $stmt->execute([
        $lis,
        $my_id
    ]);

    $menim_uzv = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
     * QRUPDA YOXAMSAM
     */
    if (!$menim_uzv) {
        echo 'CIxARILDI';
        exit;
    }


    /*
     * MƏN DÖYÜŞƏ QOŞULMAMIŞAMSA
     *
     * Bu artıq xəta deyil.
     * Mən gözləmə otağındayam.
     */
    if ((int)$menim_uzv['doyuse_qosuldu'] !== 1) {
        echo 'GOZLEME';
        exit;
    }


    /*
     * MƏNİM AKTİV DÖYÜŞÜM
     */
    $stmt = $pdo_qrup->prepare("
        SELECT
            id,
            oyuncu1_id,
            oyuncu2_id
        FROM qrup_doyusleri
        WHERE qrup_id = ?
          AND bitdi = 0
          AND (
                oyuncu1_id = ?
                OR
                oyuncu2_id = ?
          )
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->execute([
        $lis,
        $my_id,
        $my_id
    ]);

    $aktiv_doyus = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
     * HƏR İKİ TƏRƏF ZƏRBƏSİNİ ARTİQ SEÇİBSƏ,
     * SESSION / RƏQİB YOXLAMASINDAN ƏVVƏL RAUND HAZIRDIR.
     *
     * Gözləmə ekranında olan oyunçunun session-u yeni qurulsa belə,
     * iki hücum artıq yazılıbsa raund dərhal hesablanmalıdır.
     */
    if ($aktiv_doyus) {

        $stmt = $pdo_qrup->prepare("
            SELECT
                oyuncu1_hucum,
                oyuncu2_hucum
            FROM qrup_doyusleri
            WHERE id = ?
              AND bitdi = 0
            LIMIT 1
        ");

        $stmt->execute([
            (int)$aktiv_doyus['id']
        ]);

        $raund_yoxla = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $raund_yoxla &&
            $raund_yoxla['oyuncu1_hucum'] !== null &&
            $raund_yoxla['oyuncu2_hucum'] !== null
        ) {
            echo 'RAUND_HAZIRDIR';
            exit;
        }
    }


    /*
     * AKTİV DÖYÜŞÜM YOXDURSA
     *
     * Qoşulmuşam, amma hələ döyüş yaradılmayıb.
     */
    if (!$aktiv_doyus) {
        echo 'GOZLEME';
        exit;
    }


    /*
     * HAZIRKI RƏQİBİ TAP
     */
    if (
        (int)$aktiv_doyus['oyuncu1_id'] === $my_id
    ) {

        $hazirki_reqib_id =
            (int)$aktiv_doyus['oyuncu2_id'];

    } else {

        $hazirki_reqib_id =
            (int)$aktiv_doyus['oyuncu1_id'];
    }


    /*
     * HAZIRKI RƏQİB QRUPDA HƏLƏ DÖYÜŞDƏDİRMİ?
     */
   if ($hazirki_reqib_id <= 0) {
    echo 'GOZLEME';
    exit;
}


    $stmt = $pdo_qrup->prepare("
        SELECT
            user_id,
            doyuse_qosuldu,
son_can
        FROM qrup_uzvleri
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");

    $stmt->execute([
        $lis,
        $hazirki_reqib_id
    ]);

    $reqib_uzv = $stmt->fetch(PDO::FETCH_ASSOC);


echo qrupReqibBildirisStatusu(
    $pdo_qrup,
    $lis,
    $my_id,
    (int)$aktiv_doyus['id'],
    $hazirki_reqib_id
);
exit;
}
/* =========================================================
   MƏNİM OYUNÇUM
========================================================= */

$menim = oyuncuTap(
    $pdo_qrup,
    $lis,
    $my_id
);


if (!$menim) {

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    ?>
    <!DOCTYPE html PUBLIC
    "-//WAPFORUM//DTD XHTML Mobile 1.0//EN"
    "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">

    <html
    xmlns="http://www.w3.org/1999/xhtml"
    xml:lang="az"
    lang="az"
    >

    <head>

    <meta
    http-equiv="content-type"
    content="text/html; charset=utf-8"
    />

    <meta
    name="viewport"
    content="width=device-width; initial-scale=1.0; maximum-scale=3.0"
    />

    <link
    rel="stylesheet"
    href="css.css"
    type="text/css"
    />

    <title>Gözləmə otağı</title>

    </head>

    <body>

    <div
    class="main"
    style="word-wrap:break-word;"
    >

    <div class="left">

    <b>Diqqət:</b>

    Siz gözləmə otağına yönləndirilirsiniz,
    qrupunuz qalib gəldiyi halda siz də qalib gələcəksiniz.

    <br/>

    <a
    href="qrup_komek.php?lis=<?php echo (int)$lis; ?>"
    >
    Gözləmə Otağı...
    </a>

    </div>

    </div>

    </body>

    </html>
    <?php

    exit;
}


/* =========================================================
   RƏQİB DƏYİŞDİ EKRANI
========================================================= */

if (
    isset($_GET['reqib_deyisdi']) &&
    (int)$_GET['reqib_deyisdi'] === 1
) {

    /*
     * Yeni rəqibi session-da yadda saxlayırıq.
     */
    $stmt = $pdo_qrup->prepare("
        SELECT id, oyuncu1_id, oyuncu2_id
        FROM qrup_doyusleri
        WHERE qrup_id = ?
          AND bitdi = 0
          AND (oyuncu1_id = ? OR oyuncu2_id = ?)
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->execute([
        (int)$lis,
        (int)$my_id,
        (int)$my_id
    ]);

    $yeni_reqib = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($yeni_reqib) {

        if ((int)$yeni_reqib['oyuncu1_id'] === (int)$my_id) {
            $yeni_reqib_id =
                (int)$yeni_reqib['oyuncu2_id'];
        } else {
            $yeni_reqib_id =
                (int)$yeni_reqib['oyuncu1_id'];
        }

        if (!isset($_SESSION['qrup_reqib_durumleri']) ||
            !is_array($_SESSION['qrup_reqib_durumleri'])) {
            $_SESSION['qrup_reqib_durumleri'] = [];
        }

        $_SESSION['qrup_reqib_durumleri'][(string)$lis] = [
            'doyus_id' => (int)$yeni_reqib['id'],
            'reqib_id' => $yeni_reqib_id
        ];
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    ?>
    <!DOCTYPE html PUBLIC
    "-//WAPFORUM//DTD XHTML Mobile 1.0//EN"
    "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">

    <html
    xmlns="http://www.w3.org/1999/xhtml"
    xml:lang="az"
    lang="az"
    >

    <head>

    <meta
    http-equiv="content-type"
    content="text/html; charset=utf-8"
    />

    <meta
    name="viewport"
    content="width=device-width; initial-scale=1.0; maximum-scale=3.0"
    />

    <link
    rel="stylesheet"
    href="css.css"
    type="text/css"
    />

    <title>Rəqib dəyişdirildi</title>

    </head>

    <body>

    <div
    class="main"
    style="word-wrap:break-word;"
    >

    <div class="left">

    <b>Diqqət:</b>

    Sizin rəqib dəyişdirildi.

    <br/>

    <a
    href="fight_hucum.php?uid=<?php echo (int)$my_id; ?>&amp;lis=<?php echo (int)$lis; ?>"
    >
    Döyüşə daxil ol...
    </a>

    <br/>

    </div>

    </div>

    </body>

    </html>
    <?php

    exit;
}



yekunParametrleriHesabla(
    $menim,
    $ilkin_zerbe_min,
    $ilkin_zerbe_max,
    $ilkin_can,
    $ilkin_mudafie,
    $ilkin_krit,
    $ilkin_antikrit,
    $ilkin_uvorot,
    $ilkin_antiuvorot
);

$menim_max_can = max(
    1,
    (int)$menim['can']
);

$menim_id    = (int)$menim['user_id'];
$menim_login = $menim['login'];
$menim_level = (int)$menim['oyuncunun_seviyyesi'];
$menim_teref = (int)$menim['terefi'];


/* =========================================================
   RƏQİB
========================================================= */

$stmt = $pdo_qrup->prepare("
    SELECT

        qu.*,

        u.login,
        u.oyuncunun_seviyyesi,
        u.movqe,
        u.vip,

        COALESCE(op.min_zerbe, 0) AS op_min_zerbe,
        COALESCE(op.max_zerbe, 0) AS op_max_zerbe,

        COALESCE(op.can, 0) AS op_can,
        COALESCE(op.mudafie, 0) AS op_mudafie,

        COALESCE(op.krit, 0) AS op_krit,
        COALESCE(op.anti_krit, 0) AS op_anti_krit,

        COALESCE(op.uvorot, 0) AS op_uvorot,
        COALESCE(op.anti_uvorot, 0) AS op_anti_uvorot,

        COALESCE(ogb.zerbe, 0) AS guc_zerbe,
        COALESCE(ogb.mudafie, 0) AS guc_mudafie,
        COALESCE(ogb.can, 0) AS guc_can,

        COALESCE(ogb.krit, 0) AS guc_krit,
        COALESCE(ogb.anti_krit, 0) AS guc_anti_krit,

        COALESCE(ogb.uvorot, 0) AS guc_uvorot,
        COALESCE(ogb.anti_uvorot, 0) AS guc_anti_uvorot

    FROM qrup_uzvleri qu

    INNER JOIN klannn.users u
        ON u.id = qu.user_id

    LEFT JOIN klannn.oyuncu_parametrleri op
        ON op.user_id = u.id

    LEFT JOIN klannn.oyuncu_guc_bonuslari ogb
        ON ogb.user_id = u.id

    WHERE qu.qrup_id = ?
      AND qu.user_id != ?
      AND qu.terefi != ?
      AND qu.status = 1
      AND qu.doyuse_qosuldu = 1
            AND (
          qu.son_can IS NULL
          OR qu.son_can > 0
      )

    ORDER BY qu.id ASC

    LIMIT 1
");

$stmt->execute([
    $lis,
    $my_id,
    $menim_teref
]);

$reqib = $stmt->fetch();

if (!$reqib) {

    /*
     * Rəqib tapılmadı.
     * Əgər qrup döyüşü artıq bitibsə,
     * gözləmə / rəqib seçilməyib göstərmə.
     * Birbaşa nəticə səhifəsinə göndər.
     */
    $stmt_qrup_status = $pdo_qrup->prepare("
        SELECT status
        FROM qruplar
        WHERE id = ?
        LIMIT 1
    ");

    $stmt_qrup_status->execute([
        $lis
    ]);

    $qrup_status = $stmt_qrup_status->fetchColumn();

    if ($qrup_status !== false && (int)$qrup_status === 0) {
        echo 'DOVUS_BITDI';
        exit;
    }

    exit('Rəqib döyüşçü hələ seçilməyib.');
}

yekunParametrleriHesabla(
    $reqib,
    $ilkin_zerbe_min,
    $ilkin_zerbe_max,
    $ilkin_can,
    $ilkin_mudafie,
    $ilkin_krit,
    $ilkin_antikrit,
    $ilkin_uvorot,
    $ilkin_antiuvorot
);

$reqib_max_can = max(
    1,
    (int)$reqib['can']
);

$reqib_id    = (int)$reqib['user_id'];
$reqib_login = $reqib['login'];
$reqib_level = (int)$reqib['oyuncunun_seviyyesi'];


/* =========================================================
   AKTİV DÖYÜŞÜ TAP
========================================================= */

/*
 * Qrupdakı ən son bitməmiş döyüşü tapırıq.
 *
 * Əgər həmin döyüş hazırda bizim seçdiyimiz iki oyunçudur:
 *     -> həmin döyüşlə davam edirik.
 *
 * Əgər qrupdakı son döyüş başqa oyunçularladır:
 *     -> yeni döyüş yaradılır.
 *
 * Bununla A+B döyüşündən sonra A+C başlayanda,
 * A+B-yə geri qayıdanda köhnə A+B döyüşü istifadə olunmur.
 */

$stmt = $pdo_qrup->prepare("
    SELECT *
    FROM qrup_doyusleri
    WHERE qrup_id = ?
      AND bitdi = 0
    ORDER BY id DESC
    LIMIT 1
");

$stmt->execute([
    $lis
]);

$son_aktiv_doyus = $stmt->fetch();

/*
 * QRUP DÖYÜŞÜ TAM BİTİBSƏ,
 * YENİ DÖYÜŞ YARATMA.
 */
$stmt_qrup_status = $pdo_qrup->prepare("
    SELECT status
    FROM qruplar
    WHERE id = ?
    LIMIT 1
");

$stmt_qrup_status->execute([
    $lis
]);

$qrup_status = $stmt_qrup_status->fetchColumn();

if ($qrup_status !== false && (int)$qrup_status === 0) {
    echo 'DOVUS_BITDI';
    exit;
}

$doyus = null;


/*
 * Ən son aktiv döyüş bizim iki oyunçunun
 * qarşılaşmasıdırsa, onu istifadə et.
 */
if ($son_aktiv_doyus) {

    $s1 = (int)$son_aktiv_doyus['oyuncu1_id'];
    $s2 = (int)$son_aktiv_doyus['oyuncu2_id'];

    $eyni_cüt = (
        ($s1 === $menim_id && $s2 === $reqib_id)
        ||
        ($s1 === $reqib_id && $s2 === $menim_id)
    );

    if ($eyni_cüt) {

        $doyus = $son_aktiv_doyus;
    }
}


/* =========================================================
   YENİ DÖYÜŞ
========================================================= */

if (!$doyus) {

    /*
     * HƏR YENİ QARŞILAŞMA AYRI ID İLƏ YARADILIR.
     *
     * Əvvəlki döyüşün:
     *
     * - raund
     * - can
     * - son hücum
     * - son zərər
     * - son kritik
     * - son xəta
     * - müdafiə
     *
     * məlumatları yeni döyüşə keçmir.
     */

   $can1 = baslangicCaniniTap(
    $pdo_qrup,
    $lis,
    $menim_id,
    $menim_max_can
);

$can2 = baslangicCaniniTap(
    $pdo_qrup,
    $lis,
    $reqib_id,
    $reqib_max_can
);

    $stmt = $pdo_qrup->prepare("
        INSERT INTO qrup_doyusleri
        (
            qrup_id,

            oyuncu1_id,
            oyuncu2_id,

            qalib_id,

            yaradilis_tarixi,
            qebul_edildi,

            novbe_id,
            raund,

            oyuncu1_can,
            oyuncu2_can,

            oyuncu1_mudafie,
            oyuncu1_hucum,

            oyuncu2_mudafie,
            oyuncu2_hucum,

            son_hucum_eden,

            son_oyuncu1_hucum,
            son_oyuncu2_hucum,

            son_mudafie_eden,

            son_zarar,
            son_kritik,
            son_xeta,
            son_mudafie,

            novbe_baslama_tarixi,

            son_oyuncu1_zarar,
            son_oyuncu2_zarar,

            son_oyuncu1_kritik,
            son_oyuncu2_kritik,

            son_oyuncu1_xeta,
            son_oyuncu2_xeta,

            bitdi,
            bitdi_qalib,
            bitdi_meglub,
            hec_hece,

            oyuncu1_hucum_vaxti,
            oyuncu2_hucum_vaxti
        )

        VALUES
        (
            ?,
            ?,
            ?,

            NULL,

            NOW(),
            1,

            NULL,
            1,

            ?,
            ?,

            NULL,
            NULL,

            NULL,
            NULL,

            NULL,

            NULL,
            NULL,

            NULL,

            0,
            0,
            0,
            0,

            NOW(),

            0,
            0,

            0,
            0,

            0,
            0,

            0,
            0,
            0,
            0,

            NULL,
            NULL
        )
    ");

    $stmt->execute([
        $lis,
        $menim_id,
        $reqib_id,
        $can1,
        $can2
    ]);


    /*
     * Mütləq yeni döyüşün ID-si.
     */
    $id = (int)$pdo_qrup->lastInsertId();
    /*
     * İlk döyüşün başlanma vaxtını saxla.
     * Səhifə yenilənəndə 59-a qayıtmasın.
     */
    $stmt_ilk_doyus_vaxt = $pdo_qrup->prepare("
        UPDATE qrup_uzvleri
        SET son_doyuse_qosulma_vaxti = NOW()
        WHERE qrup_id = ?
          AND user_id IN (?, ?)
          AND status = 1
    ");

    $stmt_ilk_doyus_vaxt->execute([
        $lis,
        $menim_id,
        $reqib_id
    ]);

    /*
     * Yeni yaradılmış döyüşü oxuyuruq.
     */
    $stmt = $pdo_qrup->prepare("
        SELECT *
        FROM qrup_doyusleri
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $id
    ]);

    $doyus = $stmt->fetch();


    if (!$doyus) {
        exit('Döyüş yaradıla bilmədi.');
    }
}

/* =========================================================
   AJAX CHECK
========================================================= */

if ($check === 1) {

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');


try {

    /*
     * =========================================================
     * MƏNİM QRUP ÜZVLÜYÜM
     * =========================================================
     */

    $stmt = $pdo_qrup->prepare("
        SELECT
            user_id,
            terefi,
            doyuse_qosuldu,
            doyus_bildirisi
        FROM qrup_uzvleri
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");

    $stmt->execute([
        $lis,
        $my_id
    ]);

    $menim_uzv = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
     * QRUPDA YOXAMSAM
     */
    if (!$menim_uzv) {
        echo 'DOVUS_YOXDUR';
        exit;
    }
/* =================================================
   RƏQİB DƏYİŞDİ BİLDİRİŞİ
================================================= */

if ((int)$menim_uzv['doyus_bildirisi'] === 1) {

    $stmt = $pdo_qrup->prepare("
        UPDATE qrup_uzvleri
        SET doyus_bildirisi = 0
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");

    $stmt->execute([
        $lis,
        $my_id
    ]);

    echo 'REQIB_DEYISDI';
    exit;
}

    /*
     * MƏN DÖYÜŞƏ QOŞULMAMIŞAM.
     *
     * Bu halda artıq "çıxarıldı" deyil.
     * Gözləmə ekranına gedirik.
     */
    if ((int)$menim_uzv['doyuse_qosuldu'] !== 1) {
        echo 'GOZLEME';
        exit;
    }


    /*
     * =========================================================
     * MƏNİM AKTİV DÖYÜŞÜM
     * =========================================================
     */

    $stmt = $pdo_qrup->prepare("
        SELECT
            id,
            oyuncu1_id,
            oyuncu2_id
        FROM qrup_doyusleri
        WHERE qrup_id = ?
          AND bitdi = 0
          AND (
                oyuncu1_id = ?
                OR
                oyuncu2_id = ?
          )
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->execute([
        $lis,
        $my_id,
        $my_id
    ]);

    $aktiv_doyus = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
     * DÖYÜŞ HƏLƏ YARANMAYIB.
     *
     * Mən döyüşə qoşulmuşam,
     * amma qarşıma döyüşçü təyin edilməyib.
     */
    if (!$aktiv_doyus) {
        echo 'GOZLEME';
        exit;
    }


    /*
     * =========================================================
     * HAZIRKI RƏQİBİ TAP
     * =========================================================
     */

    if (
        (int)$aktiv_doyus['oyuncu1_id'] === $my_id
    ) {

        $hazirki_reqib_id =
            (int)$aktiv_doyus['oyuncu2_id'];

    } else {

        $hazirki_reqib_id =
            (int)$aktiv_doyus['oyuncu1_id'];
    }


    /*
     * RƏQİB YOXDURSA
     */
 if ($hazirki_reqib_id <= 0) {
     echo 'GOZLEME';
    exit;
}


    /*
     * =========================================================
     * RƏQİBİN HƏLƏ DÖYÜŞDƏ OLDUĞUNU YOXLAYIRIQ
     * =========================================================
     */

    $stmt = $pdo_qrup->prepare("
        SELECT
            user_id,
            doyuse_qosuldu, son_can
        FROM qrup_uzvleri
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");

    $stmt->execute([
        $lis,
        $hazirki_reqib_id
    ]);

    $reqib_uzv = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
     * RƏQİB DÖYÜŞDƏN ÇIXIBSA
     *
     * Bu tərəfdə "Rəqib dəyişdirildi" göstərilir.
     */
$bildiris_statusu = qrupReqibBildirisStatusu(
    $pdo_qrup,
    $lis,
    $my_id,
    (int)$aktiv_doyus['id'],
    $hazirki_reqib_id
);

/*
 * Yalnız həqiqi rəqib dəyişikliyində dərhal çıxırıq.
 *
 * AKTIV cavabında isə burada exit etmək olmaz;
 * aşağıdakı İKİ TƏRƏF DƏ ZƏRBƏ VURUB yoxlamasına
 * keçməliyik. Əks halda gözləmə ekranı ilişib qalır.
 */
if ($bildiris_statusu === 'REQIB_DEYISDI') {
    echo 'REQIB_DEYISDI';
    exit;
}

    /*
     * =========================================================
     * İNDİKİ DÖYÜŞÜ OXU
     * =========================================================
     */

    $stmt = $pdo_qrup->prepare("
        SELECT
            id,
            bitdi,
            oyuncu1_hucum,
            oyuncu2_hucum,
            raund,
            oyuncu1_hucum_vaxti,
            oyuncu2_hucum_vaxti
        FROM qrup_doyusleri
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        (int)$aktiv_doyus['id']
    ]);

    $c = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
     * DÖYÜŞ TAPILMADI
     */
    if (!$c) {
        echo 'DOVUS_YOXDUR';
        exit;
    }


    /*
     * DÖYÜŞ BİTİB
     */
 if ((int)$c['bitdi'] === 1) {
    echo 'DOVUS_BITDI';
    exit;
}

    /*
     * =========================================================
     * İKİ TƏRƏF DƏ ZƏRBƏ VURUB
     * =========================================================
     */

    if (
        $c['oyuncu1_hucum'] !== null &&
        $c['oyuncu2_hucum'] !== null
    ) {
        echo 'RAUND_HAZIRDIR';
        exit;
    }


    /*
     * =========================================================
     * 59 SANİYƏ TIMEOUT
     * =========================================================
     */

    $baslama = null;

    if (
        $c['oyuncu1_hucum'] !== null &&
        $c['oyuncu1_hucum_vaxti'] !== null
    ) {

        $baslama =
            strtotime(
                $c['oyuncu1_hucum_vaxti']
            );
    }


    if (
        $c['oyuncu2_hucum'] !== null &&
        $c['oyuncu2_hucum_vaxti'] !== null
    ) {

        $baslama2 =
            strtotime(
                $c['oyuncu2_hucum_vaxti']
            );

        if (
            $baslama === null ||
            $baslama2 < $baslama
        ) {
            $baslama = $baslama2;
        }
    }


    if (
        $baslama !== null &&
        (time() - $baslama) >= $novbe_muddeti
    ) {
        echo 'RAUND_HAZIRDIR';
        exit;
    }


    /*
     * NORMAL GÖZLƏMƏ
     */
    echo 'GOZLE';
    exit;


} catch (Throwable $e) {

    /*
     * Xəta olduqda AJAX-ı qırmırıq.
     */
    echo 'GOZLE';
    exit;
}
}


/* =========================================================
   RAUND HESABLAYICI
========================================================= */

if ($hesabla === 1) {

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    $pdo_qrup->beginTransaction();

    try {

        /*
         * FOR UPDATE sayəsində iki brauzer
         * eyni raundu eyni anda hesablamır.
         */
        $stmt = $pdo_qrup->prepare("
            SELECT *
            FROM qrup_doyusleri
            WHERE id = ?
            LIMIT 1
            FOR UPDATE
        ");

        $stmt->execute([
            (int)$doyus['id']
        ]);

        $c = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$c) {

            $pdo_qrup->commit();

            echo 'DOVUS_YOXDUR';
            exit;
        }

        if ((int)$c['bitdi'] === 1) {

    $pdo_qrup->commit();

    echo 'DOVUS_BITDI';
    exit;
}


        /* =================================================
           HÜCUMLAR
        ================================================= */

        $o1_hucum = $c['oyuncu1_hucum'];
        $o2_hucum = $c['oyuncu2_hucum'];

        $o1_mudafie = $c['oyuncu1_mudafie'];
        $o2_mudafie = $c['oyuncu2_mudafie'];


        /*
         * Hər iki tərəf vurubsa normal raund.
         */
        $iki_teref_vurub =
            $o1_hucum !== null &&
            $o2_hucum !== null;


        /*
         * Timeout vəziyyəti.
         */
$timeout_oyuncu = null;
$timeout_o1 = false;
$timeout_o2 = false;

if (!$iki_teref_vurub) {

    /*
     * Heç kim vurmayıbsa belə raundun
     * başlanğıc vaxtından 59 saniyə hesablanır.
     */
    $timeout_baslama = null;

    if (!empty($c['novbe_baslama_tarixi'])) {
        $timeout_baslama = strtotime(
            $c['novbe_baslama_tarixi']
        );
    }

    /*
     * O1 vurmayıb.
     */
    if ($o1_hucum === null) {

        if (
            $timeout_baslama !== false &&
            $timeout_baslama !== null &&
            (time() - $timeout_baslama) >= $novbe_muddeti
        ) {
            $timeout_o1 = true;
        }
    }

    /*
     * O2 vurmayıb.
     */
    if ($o2_hucum === null) {

        if (
            $timeout_baslama !== false &&
            $timeout_baslama !== null &&
            (time() - $timeout_baslama) >= $novbe_muddeti
        ) {
            $timeout_o2 = true;
        }
    }

    /*
     * 1 = O1 timeout
     * 2 = O2 timeout
     * 3 = hər ikisi timeout
     */
    if ($timeout_o1 && $timeout_o2) {
        $timeout_oyuncu = 3;
    } elseif ($timeout_o1) {
        $timeout_oyuncu = 1;
    } elseif ($timeout_o2) {
        $timeout_oyuncu = 2;
    }

        }


        /*
         * Hələ raund hesablanmağa hazır deyil.
         */
        if (
            !$iki_teref_vurub &&
            $timeout_oyuncu === null
        ) {

            $pdo_qrup->commit();

            echo 'GOZLE';
            exit;
        }


        /* =================================================
           OYUNÇU PARAMETRLƏRİ
        ================================================= */

        if ((int)$c['oyuncu1_id'] === $menim_id) {

            $o1_min =
                (int)$menim['min_zerbe'];

            $o1_max =
                (int)$menim['max_zerbe'];

            $o1_krit =
                (int)$menim['krit'];

            $o1_anti_krit =
                (int)$menim['anti_krit'];

            $o1_uvorot =
                (int)$menim['uvorot'];

            $o1_anti_uvorot =
                (int)$menim['anti_uvorot'];

            $o1_mudafie_gucu =
                (int)$menim['mudafie'];

        } else {

            $o1_min =
                (int)$reqib['min_zerbe'];

            $o1_max =
                (int)$reqib['max_zerbe'];

            $o1_krit =
                (int)$reqib['krit'];

            $o1_anti_krit =
                (int)$reqib['anti_krit'];

            $o1_uvorot =
                (int)$reqib['uvorot'];

            $o1_anti_uvorot =
                (int)$reqib['anti_uvorot'];

            $o1_mudafie_gucu =
                (int)$reqib['mudafie'];
        }


        if ((int)$c['oyuncu2_id'] === $menim_id) {

            $o2_min =
                (int)$menim['min_zerbe'];

            $o2_max =
                (int)$menim['max_zerbe'];

            $o2_krit =
                (int)$menim['krit'];

            $o2_anti_krit =
                (int)$menim['anti_krit'];

            $o2_uvorot =
                (int)$menim['uvorot'];

            $o2_anti_uvorot =
                (int)$menim['anti_uvorot'];

            $o2_mudafie_gucu =
                (int)$menim['mudafie'];

        } else {

            $o2_min =
                (int)$reqib['min_zerbe'];

            $o2_max =
                (int)$reqib['max_zerbe'];

            $o2_krit =
                (int)$reqib['krit'];

            $o2_anti_krit =
                (int)$reqib['anti_krit'];

            $o2_uvorot =
                (int)$reqib['uvorot'];

            $o2_anti_uvorot =
                (int)$reqib['anti_uvorot'];

            $o2_mudafie_gucu =
                (int)$reqib['mudafie'];
        }


       /* =================================================
   TIMEOUT
================================================= */

if (
    $timeout_oyuncu === 1 ||
    $timeout_oyuncu === 2 ||
    $timeout_oyuncu === 3
) {

    /*
     * TIMEOUT ZƏRBƏ DEYİL.
     * Ona görə canlar olduğu kimi qalır.
     */
    $o1_can = max(
        0,
        (int)$c['oyuncu1_can']
    );

    $o2_can = max(
        0,
        (int)$c['oyuncu2_can']
    );

    $o1_zarar = 0;
    $o2_zarar = 0;

    $o1_kritik = false;
    $o2_kritik = false;

    $o1_xeta = false;
    $o2_xeta = false;

    $o1_mudafie_tutdu = false;
    $o2_mudafie_tutdu = false;

    $son_hucum_eden = null;
    $son_mudafie_eden = null;

    $bitdi = 0;
    $qalib_id = null;
    $bitdi_qalib = 0;
    $bitdi_meglub = 0;
    $hec_hece = 0;


    /* =================================================
       NÖVBƏTİ OYUNÇULARI YOXLA
    ================================================= */

    $novbeti_teref1 = null;
    $novbeti_teref2 = null;


    /*
     * O1 timeout olubsa, onun tərəfində
     * növbəti oyunçu varmı?
     */
    if (
        $timeout_oyuncu === 1 ||
        $timeout_oyuncu === 3
    ) {

        $stmt = $pdo_qrup->prepare("
            SELECT user_id
            FROM qrup_uzvleri
            WHERE qrup_id = ?
              AND user_id != ?
              AND status = 1
              AND doyuse_qosuldu = 0
              AND timeoutdan_cixdi = 0
              AND terefi = (
                  SELECT terefi
                  FROM qrup_uzvleri
                  WHERE qrup_id = ?
                    AND user_id = ?
                    AND status = 1
                  LIMIT 1
              )
              AND (
                  son_can IS NULL
                  OR son_can > 0
              )
            ORDER BY giris_sirasi ASC
            LIMIT 1
        ");

        $stmt->execute([
            $lis,
            (int)$c['oyuncu1_id'],
            $lis,
            (int)$c['oyuncu1_id']
        ]);

        $novbeti_teref1 =
            $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /*
     * O2 timeout olubsa, onun tərəfində
     * növbəti oyunçu varmı?
     */
    if (
        $timeout_oyuncu === 2 ||
        $timeout_oyuncu === 3
    ) {

        $stmt = $pdo_qrup->prepare("
            SELECT user_id
            FROM qrup_uzvleri
            WHERE qrup_id = ?
              AND user_id NOT IN (?, ?)
              AND status = 1
              AND doyuse_qosuldu = 0
              AND timeoutdan_cixdi = 0
              AND terefi = (
                  SELECT terefi
                  FROM qrup_uzvleri
                  WHERE qrup_id = ?
                    AND user_id = ?
                    AND status = 1
                  LIMIT 1
              )
              AND (
                  son_can IS NULL
                  OR son_can > 0
              )
            ORDER BY giris_sirasi ASC
            LIMIT 1
        ");

        $stmt->execute([
            $lis,
            (int)$c['oyuncu1_id'],
            (int)$c['oyuncu2_id'],
            $lis,
            (int)$c['oyuncu2_id']
        ]);

        $novbeti_teref2 =
            $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /* =================================================
       TƏK TƏRƏF TIMEOUT
    ================================================= */

    if ($timeout_oyuncu === 1) {

        /*
         * O1 timeout olub.
         */

        if ($novbeti_teref1) {

            /*
             * Köhnə O1-i döyüşdən çıxar.
             * CANINI SIFIRLAMIYORUQ.
             */
            $stmt = $pdo_qrup->prepare("
                UPDATE qrup_uzvleri
                SET
                    doyuse_qosuldu = 0,
                    timeoutdan_cixdi = 1,
                    son_can = ?
                WHERE qrup_id = ?
                  AND user_id = ?
                  AND status = 1
                LIMIT 1
            ");

            $stmt->execute([
                $o1_can,
                $lis,
                (int)$c['oyuncu1_id']
            ]);


            /*
             * Növbəti O1-i döyüşə daxil et.
             */
            $yeni_oyuncu_id =
                (int)$novbeti_teref1['user_id'];

            $yeni_doyuscu =
                yeniOyuncunuDoyuseYerlestir(
                    $pdo_qrup,
                    $lis,
                    (int)$c['id'],
                    $yeni_oyuncu_id,
                    1,
                    $ilkin_zerbe_min,
                    $ilkin_zerbe_max,
                    $ilkin_can,
                    $ilkin_mudafie,
                    $ilkin_krit,
                    $ilkin_antikrit,
                    $ilkin_uvorot,
                    $ilkin_antiuvorot
                );

            if ($yeni_doyuscu) {

                $o1_can =
                    $yeni_doyuscu['can'];

                $c['oyuncu1_can'] =
                    $yeni_doyuscu['can'];

                $c['oyuncu1_id'] =
                    $yeni_doyuscu['id'];

                $bitdi = 0;
                $qalib_id = null;
                $bitdi_qalib = 0;
                $bitdi_meglub = 0;
            }

        } else {

            /*
             * O1 son oyunçudur.
             * O2 vaxtında vurub.
             * O2 qalibdir.
             */
            $bitdi = 1;

            $qalib_id =
                (int)$c['oyuncu2_id'];

            $bitdi_qalib = 2;
            $bitdi_meglub = 1;
            $hec_hece = 0;

            /*
             * O1 sadəcə döyüşdən çıxır.
             * CAN 0 EDİLMİR.
             */
            $stmt = $pdo_qrup->prepare("
                UPDATE qrup_uzvleri
                SET
                    doyuse_qosuldu = 0,
                    timeoutdan_cixdi = 1,
                    son_can = ?
                WHERE qrup_id = ?
                  AND user_id = ?
                  AND status = 1
                LIMIT 1
            ");

            $stmt->execute([
                $o1_can,
                $lis,
                (int)$c['oyuncu1_id']
            ]);
        }


    } elseif ($timeout_oyuncu === 2) {

        /*
         * O2 timeout olub.
         */

        if ($novbeti_teref2) {

            /*
             * Köhnə O2-ni döyüşdən çıxar.
             */
            $stmt = $pdo_qrup->prepare("
                UPDATE qrup_uzvleri
                SET
                    doyuse_qosuldu = 0,
                    timeoutdan_cixdi = 1,
                    son_can = ?
                WHERE qrup_id = ?
                  AND user_id = ?
                  AND status = 1
                LIMIT 1
            ");

            $stmt->execute([
                $o2_can,
                $lis,
                (int)$c['oyuncu2_id']
            ]);


            /*
             * Növbəti O2-ni daxil et.
             */
            $yeni_oyuncu_id =
                (int)$novbeti_teref2['user_id'];

            $yeni_doyuscu =
                yeniOyuncunuDoyuseYerlestir(
                    $pdo_qrup,
                    $lis,
                    (int)$c['id'],
                    $yeni_oyuncu_id,
                    2,
                    $ilkin_zerbe_min,
                    $ilkin_zerbe_max,
                    $ilkin_can,
                    $ilkin_mudafie,
                    $ilkin_krit,
                    $ilkin_antikrit,
                    $ilkin_uvorot,
                    $ilkin_antiuvorot
                );

            if ($yeni_doyuscu) {

                $o2_can =
                    $yeni_doyuscu['can'];

                $c['oyuncu2_can'] =
                    $yeni_doyuscu['can'];

                $c['oyuncu2_id'] =
                    $yeni_doyuscu['id'];

                $bitdi = 0;
                $qalib_id = null;
                $bitdi_qalib = 0;
                $bitdi_meglub = 0;
            }

        } else {

            /*
             * O2 son oyunçudur.
             * O1 qalibdir.
             */
            $bitdi = 1;

            $qalib_id =
                (int)$c['oyuncu1_id'];

            $bitdi_qalib = 1;
            $bitdi_meglub = 2;
            $hec_hece = 0;

            /*
             * CAN 0 EDİLMİR.
             */
            $stmt = $pdo_qrup->prepare("
                UPDATE qrup_uzvleri
                SET
                    doyuse_qosuldu = 0,
                    timeoutdan_cixdi = 1,
                    son_can = ?
                WHERE qrup_id = ?
                  AND user_id = ?
                  AND status = 1
                LIMIT 1
            ");

            $stmt->execute([
                $o2_can,
                $lis,
                (int)$c['oyuncu2_id']
            ]);
        }


    } else {

        /* =================================================
           HƏR İKİ TƏRƏF TIMEOUT
        ================================================= */

        if (
            $novbeti_teref1 &&
            $novbeti_teref2
        ) {

            /*
             * İKİSİ DƏ SON DEYİL.
             * İkisini də çıxar, yeni oyunçuları daxil et.
             */

            $kohne_o1_id =
                (int)$c['oyuncu1_id'];

            $kohne_o2_id =
                (int)$c['oyuncu2_id'];


            /*
             * O1-i çıxar.
             */
            $stmt = $pdo_qrup->prepare("
                UPDATE qrup_uzvleri
                SET
                    doyuse_qosuldu = 0,
                    timeoutdan_cixdi = 1,
                    son_can = ?
                WHERE qrup_id = ?
                  AND user_id = ?
                  AND status = 1
                LIMIT 1
            ");

            $stmt->execute([
                $o1_can,
                $lis,
                $kohne_o1_id
            ]);


            /*
             * O2-ni çıxar.
             */
            $stmt->execute([
                $o2_can,
                $lis,
                $kohne_o2_id
            ]);


            /*
             * Yeni O1.
             */
            $yeni_o1_id =
                (int)$novbeti_teref1['user_id'];

            $yeni_o1 =
                yeniOyuncunuDoyuseYerlestir(
                    $pdo_qrup,
                    $lis,
                    (int)$c['id'],
                    $yeni_o1_id,
                    1,
                    $ilkin_zerbe_min,
                    $ilkin_zerbe_max,
                    $ilkin_can,
                    $ilkin_mudafie,
                    $ilkin_krit,
                    $ilkin_antikrit,
                    $ilkin_uvorot,
                    $ilkin_antiuvorot
                );


            /*
             * Yeni O2.
             */
            $yeni_o2_id =
                (int)$novbeti_teref2['user_id'];

            $yeni_o2 =
                yeniOyuncunuDoyuseYerlestir(
                    $pdo_qrup,
                    $lis,
                    (int)$c['id'],
                    $yeni_o2_id,
                    2,
                    $ilkin_zerbe_min,
                    $ilkin_zerbe_max,
                    $ilkin_can,
                    $ilkin_mudafie,
                    $ilkin_krit,
                    $ilkin_antikrit,
                    $ilkin_uvorot,
                    $ilkin_antiuvorot
                );


            if (
                $yeni_o1 &&
                $yeni_o2
            ) {

                $o1_can = $yeni_o1['can'];
                $o2_can = $yeni_o2['can'];

                $c['oyuncu1_id'] =
                    $yeni_o1['id'];

                $c['oyuncu2_id'] =
                    $yeni_o2['id'];

                $c['oyuncu1_can'] =
                    $yeni_o1['can'];

                $c['oyuncu2_can'] =
                    $yeni_o2['can'];

                $bitdi = 0;
                $qalib_id = null;
                $bitdi_qalib = 0;
                $bitdi_meglub = 0;
                $hec_hece = 0;
            }


        } elseif (
            !$novbeti_teref1 &&
            !$novbeti_teref2
        ) {

            /*
             * HƏR İKİSİ SON OYUNÇUDUR.
             * HEÇ-HEÇƏ.
             *
             * CANLAR DƏYİŞMİR.
             */
            $bitdi = 1;
            $qalib_id = null;
            $bitdi_qalib = 0;
            $bitdi_meglub = 0;
            $hec_hece = 1;


            /*
             * O1.
             */
            $stmt = $pdo_qrup->prepare("
                UPDATE qrup_uzvleri
                SET
                    doyuse_qosuldu = 0,
                    son_can = ?
                WHERE qrup_id = ?
                  AND user_id = ?
                  AND status = 1
                LIMIT 1
            ");

            $stmt->execute([
                $o1_can,
                $lis,
                (int)$c['oyuncu1_id']
            ]);


            /*
             * O2.
             */
            $stmt->execute([
                $o2_can,
                $lis,
                (int)$c['oyuncu2_id']
            ]);


        } elseif (
            $novbeti_teref1
        ) {

            /*
             * O1-də növbəti var,
             * O2 son oyunçudur.
             *
             * O2 məğlub olur.
             */
            $bitdi = 1;

            $qalib_id =
                (int)$c['oyuncu1_id'];

            $bitdi_qalib = 1;
            $bitdi_meglub = 2;
            $hec_hece = 0;


            $stmt = $pdo_qrup->prepare("
                UPDATE qrup_uzvleri
                SET
                    doyuse_qosuldu = 0,
                    son_can = ?
                WHERE qrup_id = ?
                  AND user_id = ?
                  AND status = 1
                LIMIT 1
            ");

            $stmt->execute([
                $o2_can,
                $lis,
                (int)$c['oyuncu2_id']
            ]);


        } else {

            /*
             * O2-də növbəti var,
             * O1 son oyunçudur.
             *
             * O1 məğlub olur.
             */
            $bitdi = 1;

            $qalib_id =
                (int)$c['oyuncu2_id'];

            $bitdi_qalib = 2;
            $bitdi_meglub = 1;
            $hec_hece = 0;


            $stmt = $pdo_qrup->prepare("
                UPDATE qrup_uzvleri
                SET
                    doyuse_qosuldu = 0,
                    son_can = ?
                WHERE qrup_id = ?
                  AND user_id = ?
                  AND status = 1
                LIMIT 1
            ");

            $stmt->execute([
                $o1_can,
                $lis,
                (int)$c['oyuncu1_id']
            ]);
        }
    }


} else {


            /* =================================================
               NORMAL RAUND
            ================================================= */

            $o1_hucum =
                (int)$o1_hucum;

            $o2_hucum =
                (int)$o2_hucum;

            $o1_mudafie =
                (int)$o1_mudafie;

            $o2_mudafie =
                (int)$o2_mudafie;


            /* =================================================
               MÜDAFİƏ
            ================================================= */

            $o1_mudafie_tutdu =
                mudafieTutdu(
                    $o2_mudafie,
                    $o1_hucum
                );

            $o2_mudafie_tutdu =
                mudafieTutdu(
                    $o1_mudafie,
                    $o2_hucum
                );


            /* =================================================
               UVOROT
            ================================================= */

            $o1_uvorot_faizi =
                uvorotFaiziHesabla(
                    $o1_uvorot,
                    $o2_anti_uvorot
                );

            $o2_uvorot_faizi =
                uvorotFaiziHesabla(
                    $o2_uvorot,
                    $o1_anti_uvorot
                );


            $o1_xeta = false;
            $o2_xeta = false;


            if (!$o1_mudafie_tutdu) {

                $o1_xeta =
                    uvorotOldu(
                        $o1_uvorot_faizi
                    );
            }


            if (!$o2_mudafie_tutdu) {

                $o2_xeta =
                    uvorotOldu(
                        $o2_uvorot_faizi
                    );
            }


            /* =================================================
               KRİTİK
            ================================================= */

            $o1_krit_faizi =
                kritFaiziHesabla(
                    $o1_krit,
                    $o2_anti_krit
                );

            $o2_krit_faizi =
                kritFaiziHesabla(
                    $o2_krit,
                    $o1_anti_krit
                );


            $o1_kritik =
                !$o1_mudafie_tutdu &&
                !$o1_xeta &&
                kritOldu($o1_krit_faizi);


            $o2_kritik =
                !$o2_mudafie_tutdu &&
                !$o2_xeta &&
                kritOldu($o2_krit_faizi);


            /* =================================================
               ZƏRƏR
            ================================================= */

            if (
                $o1_mudafie_tutdu ||
                $o1_xeta
            ) {

                $o1_zarar = 0;

            } elseif ($o1_kritik) {

                $o1_zarar =
                    kritZerbeHesabla(
                        $o1_min,
                        $o1_max,
                        $o2_mudafie_gucu
                    );

            } else {

                $o1_zarar =
                    zerbeHesabla(
                        $o1_min,
                        $o1_max,
                        $o2_mudafie_gucu
                    );
            }


            if (
                $o2_mudafie_tutdu ||
                $o2_xeta
            ) {

                $o2_zarar = 0;

            } elseif ($o2_kritik) {

                $o2_zarar =
                    kritZerbeHesabla(
                        $o2_min,
                        $o2_max,
                        $o1_mudafie_gucu
                    );

            } else {

                $o2_zarar =
                    zerbeHesabla(
                        $o2_min,
                        $o2_max,
                        $o1_mudafie_gucu
                    );
            }


            $o1_zarar =
                max(
                    0,
                    (int)$o1_zarar
                );

            $o2_zarar =
                max(
                    0,
                    (int)$o2_zarar
                );


            /* =================================================
               CAN
            ================================================= */

            $o1_can =
                max(
                    0,
                    (int)$c['oyuncu1_can']
                    -
                    $o2_zarar
                );

            $o2_can =
                max(
                    0,
                    (int)$c['oyuncu2_can']
                    -
                    $o1_zarar
                );



/* =================================================
   BİTMƏ
================================================= */

$bitdi = 0;

$qalib_id = null;

$bitdi_qalib = 0;
$bitdi_meglub = 0;
$hec_hece = 0;


/* =================================================
   HƏR İKİ OYUNÇU 0 CAN — HEÇ-HEÇƏ
================================================= */

if (
    $o1_can <= 0 &&
    $o2_can <= 0
) {

    $bitdi = 1;
    $hec_hece = 1;


   /* =================================================
   1-Cİ OYUNÇUNU DÖYÜŞDƏN ÇIXAR
================================================= */

$stmt = $pdo_qrup->prepare("
    UPDATE qrup_uzvleri
    SET
        doyuse_qosuldu = 0,
        son_can = 0
    WHERE qrup_id = ?
      AND user_id = ?
      AND status = 1
    LIMIT 1
");

$stmt->execute([
    $lis,
    (int)$c['oyuncu1_id']
]);


/* =================================================
   2-Cİ OYUNÇUNU DÖYÜŞDƏN ÇIXAR
================================================= */

$stmt = $pdo_qrup->prepare("
    UPDATE qrup_uzvleri
    SET
        doyuse_qosuldu = 0,
        son_can = 0
    WHERE qrup_id = ?
      AND user_id = ?
      AND status = 1
    LIMIT 1
");

$stmt->execute([
    $lis,
    (int)$c['oyuncu2_id']
]);


/* =================================================
   1-Cİ TƏRƏFDƏN NÖVBƏTİ OYUNÇUNU DÖYÜŞƏ QOŞ
================================================= */

$stmt = $pdo_qrup->prepare("
    SELECT user_id
    FROM qrup_uzvleri
    WHERE qrup_id = ?
      AND user_id != ?
      AND status = 1
      AND doyuse_qosuldu = 0
      AND terefi = (
          SELECT terefi
          FROM qrup_uzvleri
          WHERE qrup_id = ?
            AND user_id = ?
            AND status = 1
          LIMIT 1
      )
      AND (
          son_can IS NULL
          OR son_can > 0
      )
    ORDER BY giris_sirasi ASC
    LIMIT 1
");

$stmt->execute([
    $lis,
    (int)$c['oyuncu1_id'],
    $lis,
    (int)$c['oyuncu1_id']
]);

$novbeti_teref1 = $stmt->fetch(PDO::FETCH_ASSOC);


if ($novbeti_teref1) {

    $yeni_oyuncu_id =
        (int)$novbeti_teref1['user_id'];

    $yeni_doyuscu = yeniOyuncunuDoyuseYerlestir(
        $pdo_qrup,
        $lis,
        (int)$c['id'],
        $yeni_oyuncu_id,
        1,
        $ilkin_zerbe_min,
        $ilkin_zerbe_max,
        $ilkin_can,
        $ilkin_mudafie,
        $ilkin_krit,
        $ilkin_antikrit,
        $ilkin_uvorot,
        $ilkin_antiuvorot
    );

    if ($yeni_doyuscu) {

        $o1_can =
            $yeni_doyuscu['can'];

        $c['oyuncu1_can'] =
            $yeni_doyuscu['can'];

        $c['oyuncu1_id'] =
            $yeni_doyuscu['id'];

        $bitdi = 0;
        $bitdi_qalib = 0;
        $bitdi_meglub = 0;
        $qalib_id = null;
    }
}


/* =================================================
   2-Cİ TƏRƏFDƏN NÖVBƏTİ OYUNÇUNU DÖYÜŞƏ QOŞ
================================================= */

$stmt = $pdo_qrup->prepare("
    SELECT user_id
    FROM qrup_uzvleri
    WHERE qrup_id = ?
      AND user_id NOT IN (?, ?)
      AND status = 1
      AND doyuse_qosuldu = 0
      AND terefi = (
          SELECT terefi
          FROM qrup_uzvleri
          WHERE qrup_id = ?
            AND user_id = ?
            AND status = 1
          LIMIT 1
      )
      AND (
          son_can IS NULL
          OR son_can > 0
      )
    ORDER BY giris_sirasi ASC
    LIMIT 1
");

$stmt->execute([
    $lis,
    (int)$c['oyuncu1_id'],
    (int)$c['oyuncu2_id'],
    $lis,
    (int)$c['oyuncu2_id']
]);

$novbeti_teref2 = $stmt->fetch(PDO::FETCH_ASSOC);


if ($novbeti_teref2) {

    $yeni_oyuncu_id =
        (int)$novbeti_teref2['user_id'];

    $yeni_doyuscu = yeniOyuncunuDoyuseYerlestir(
        $pdo_qrup,
        $lis,
        (int)$c['id'],
        $yeni_oyuncu_id,
        2,
        $ilkin_zerbe_min,
        $ilkin_zerbe_max,
        $ilkin_can,
        $ilkin_mudafie,
        $ilkin_krit,
        $ilkin_antikrit,
        $ilkin_uvorot,
        $ilkin_antiuvorot
    );

    if ($yeni_doyuscu) {

        $o2_can =
            $yeni_doyuscu['can'];

        $c['oyuncu2_id'] =
            $yeni_doyuscu['id'];

        $bitdi = 0;
        $bitdi_qalib = 0;
        $bitdi_meglub = 0;
        $qalib_id = null;
    }
}


/* =================================================
   1-Cİ OYUNÇU 0 CAN
================================================= */

} elseif ($o1_can <= 0) {

    $bitdi = 1;

    $qalib_id =
        (int)$c['oyuncu2_id'];

    $bitdi_qalib = 2;
$bitdi_meglub = 1;


    /* =================================================
       MƏĞLUB OYUNÇUNU DÖYÜŞDƏN ÇIXAR
    ================================================= */

    $stmt = $pdo_qrup->prepare("
        UPDATE qrup_uzvleri
        SET
            doyuse_qosuldu = 0,
            son_can = 0
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");

    $stmt->execute([
        $lis,
        (int)$c['oyuncu1_id']
    ]);


    /* =================================================
       NÖVBƏTİ OYUNÇUNU TAP
    ================================================= */

    $stmt = $pdo_qrup->prepare("
        SELECT user_id
        FROM qrup_uzvleri
        WHERE qrup_id = ?
          AND user_id != ?
          AND status = 1
          AND doyuse_qosuldu = 0
          AND terefi = (
              SELECT terefi
              FROM qrup_uzvleri
              WHERE qrup_id = ?
                AND user_id = ?
                AND status = 1
              LIMIT 1
          )
          AND (
              son_can IS NULL
              OR son_can > 0
          )
        ORDER BY giris_sirasi ASC
        LIMIT 1
    ");

    $stmt->execute([
        $lis,
        (int)$c['oyuncu1_id'],
        $lis,
        (int)$c['oyuncu1_id']
    ]);

    $novbeti_reqib =
        $stmt->fetch(PDO::FETCH_ASSOC);


   if ($novbeti_reqib) {

    $yeni_oyuncu_id =
        (int)$novbeti_reqib['user_id'];

    $yeni_doyuscu = yeniOyuncunuDoyuseYerlestir(
        $pdo_qrup,
        $lis,
        (int)$c['id'],
        $yeni_oyuncu_id,
        1,
        $ilkin_zerbe_min,
        $ilkin_zerbe_max,
        $ilkin_can,
        $ilkin_mudafie,
        $ilkin_krit,
        $ilkin_antikrit,
        $ilkin_uvorot,
        $ilkin_antiuvorot
    );

    if ($yeni_doyuscu) {

        $o1_can =
            $yeni_doyuscu['can'];

        $c['oyuncu1_can'] =
            $yeni_doyuscu['can'];

        $c['oyuncu1_id'] =
            $yeni_doyuscu['id'];

        $bitdi = 0;
        $bitdi_qalib = 0;
        $bitdi_meglub = 0;
        $qalib_id = null;
    }
}
}


/* =================================================
   2-Cİ OYUNÇU 0 CAN
================================================= */

if ($o2_can <= 0) {

    $bitdi = 1;

    $qalib_id =
        (int)$c['oyuncu1_id'];

   $bitdi_qalib = 1;
$bitdi_meglub = 2;


    /* =================================================
       MƏĞLUB OYUNÇUNU DÖYÜŞDƏN ÇIXAR
    ================================================= */

    $stmt = $pdo_qrup->prepare("
        UPDATE qrup_uzvleri
        SET
            doyuse_qosuldu = 0,
            son_can = 0
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");

    $stmt->execute([
        $lis,
        (int)$c['oyuncu2_id']
    ]);


    /* =================================================
       NÖVBƏTİ OYUNÇUNU TAP
    ================================================= */

    $stmt = $pdo_qrup->prepare("
        SELECT user_id
        FROM qrup_uzvleri
        WHERE qrup_id = ?
          AND user_id NOT IN (?, ?)
          AND status = 1
          AND doyuse_qosuldu = 0
          AND terefi = (
              SELECT terefi
              FROM qrup_uzvleri
              WHERE qrup_id = ?
                AND user_id = ?
                AND status = 1
              LIMIT 1
          )
          AND (
              son_can IS NULL
              OR son_can > 0
          )
        ORDER BY giris_sirasi ASC
        LIMIT 1
    ");

  $stmt->execute([
    $lis,
    (int)$c['oyuncu1_id'],
    (int)$c['oyuncu2_id'],
    $lis,
    (int)$c['oyuncu2_id']
]);

    $novbeti_reqib =
        $stmt->fetch(PDO::FETCH_ASSOC);


   if ($novbeti_reqib) {

    $yeni_oyuncu_id =
        (int)$novbeti_reqib['user_id'];

    $yeni_doyuscu = yeniOyuncunuDoyuseYerlestir(
        $pdo_qrup,
        $lis,
        (int)$c['id'],
        $yeni_oyuncu_id,
        2,
        $ilkin_zerbe_min,
        $ilkin_zerbe_max,
        $ilkin_can,
        $ilkin_mudafie,
        $ilkin_krit,
        $ilkin_antikrit,
        $ilkin_uvorot,
        $ilkin_antiuvorot
    );

    if ($yeni_doyuscu) {

        $o2_can =
            $yeni_doyuscu['can'];

        $c['oyuncu2_can'] =
            $yeni_doyuscu['can'];

        $c['oyuncu2_id'] =
            $yeni_doyuscu['id'];

        $bitdi = 0;
        $bitdi_qalib = 0;
        $bitdi_meglub = 0;
        $qalib_id = null;
             }
         }
     }

               
            /* =================================================
               SON MÜDAFİƏ
            ================================================= */

            $son_mudafie = 0;

            if (
                $o1_mudafie_tutdu &&
                $o2_mudafie_tutdu
            ) {

                $son_mudafie = 3;

            } elseif ($o1_mudafie_tutdu) {

                $son_mudafie = 2;

            } elseif ($o2_mudafie_tutdu) {

                $son_mudafie = 1;
            }


            /* =================================================
               SON MÜDAFİƏ EDƏN
            ================================================= */

            $son_mudafie_eden = null;

            if (
                $o1_mudafie_tutdu &&
                !$o2_mudafie_tutdu
            ) {

                $son_mudafie_eden =
                    (int)$c['oyuncu2_id'];

            } elseif (
                $o2_mudafie_tutdu &&
                !$o1_mudafie_tutdu
            ) {

                $son_mudafie_eden =
                    (int)$c['oyuncu1_id'];
            }


            /* =================================================
               SON HÜCUM EDƏN
            ================================================= */

            $son_hucum_eden = null;

            if (
                $o1_zarar > 0 &&
                $o2_zarar <= 0
            ) {

                $son_hucum_eden =
                    (int)$c['oyuncu1_id'];

            } elseif (
                $o2_zarar > 0 &&
                $o1_zarar <= 0
            ) {

                $son_hucum_eden =
                    (int)$c['oyuncu2_id'];

            } elseif (
                $o1_zarar > 0 &&
                $o2_zarar > 0
            ) {

                /*
                 * İkisi də zərər vurubsa,
                 * son zərbə vuranı müəyyənləşdirə bilmək üçün
                 * son_hucum_eden NULL saxlanılır.
                 */
                $son_hucum_eden = null;
            }
        }



        /* =================================================
           SON ZƏRƏR
        ================================================= */

        $son_zarar =
            (int)$o1_zarar +
            (int)$o2_zarar;


        /* =================================================
           DB UPDATE
        ================================================= */

        $stmt = $pdo_qrup->prepare("
            UPDATE qrup_doyusleri

            SET

                qalib_id = ?,

                raund = raund + 1,

                oyuncu1_can = ?,
                oyuncu2_can = ?,

                son_hucum_eden = ?,

                son_oyuncu1_hucum = ?,
                son_oyuncu2_hucum = ?,

                son_mudafie_eden = ?,

                son_zarar = ?,

                son_kritik = ?,
                son_xeta = ?,
                son_mudafie = ?,

                son_oyuncu1_zarar = ?,
                son_oyuncu2_zarar = ?,

                son_oyuncu1_kritik = ?,
                son_oyuncu2_kritik = ?,

                son_oyuncu1_xeta = ?,
                son_oyuncu2_xeta = ?,

                novbe_baslama_tarixi = NOW(),

                bitdi = ?,
                bitdi_qalib = ?,
                bitdi_meglub = ?,
                hec_hece = ?

            WHERE id = ?

            LIMIT 1
        ");

        $stmt->execute([

            $qalib_id,

            $o1_can,
            $o2_can,

            $son_hucum_eden,

            $o1_hucum,
            $o2_hucum,

            $son_mudafie_eden,

            $son_zarar,

            ($o1_kritik || $o2_kritik)
                ? 1
                : 0,

            ($o1_xeta || $o2_xeta)
                ? 1
                : 0,

            isset($son_mudafie)
                ? $son_mudafie
                : 0,

            $o1_zarar,
            $o2_zarar,

            $o1_kritik ? 1 : 0,
            $o2_kritik ? 1 : 0,

            $o1_xeta ? 1 : 0,
            $o2_xeta ? 1 : 0,

            $bitdi,
            $bitdi_qalib,
            $bitdi_meglub,
            $hec_hece,

            (int)$c['id']
        ]);
sonCaniYaz(
    $pdo_qrup,
    $lis,
    (int)$c['oyuncu1_id'],
    $o1_can
);

sonCaniYaz(
    $pdo_qrup,
    $lis,
    (int)$c['oyuncu2_id'],
    $o2_can
);

        /* =================================================
           NÖVBƏTİ RAUND
        ================================================= */

        if (!$bitdi) {

            $stmt = $pdo_qrup->prepare("
                UPDATE qrup_doyusleri

                SET

                    oyuncu1_hucum = NULL,
                    oyuncu1_mudafie = NULL,

                    oyuncu2_hucum = NULL,
                    oyuncu2_mudafie = NULL,

                    oyuncu1_hucum_vaxti = NULL,
                    oyuncu2_hucum_vaxti = NULL,

                    novbe_baslama_tarixi = NOW()

                WHERE id = ?

                LIMIT 1
            ");

            $stmt->execute([
                (int)$c['id']
            ]);
        }




     if ($bitdi) {

    $stmt_qrup_bitir = $pdo_qrup->prepare("
        UPDATE qruplar
        SET status = 0
        WHERE id = ?
        LIMIT 1
    ");

  $stmt_qrup_bitir->execute([
    (int)$lis
]);
}

$pdo_qrup->commit();

if ($bitdi) {
    echo 'DOVUS_BITDI';
} else {
    echo 'DOVUS_HAZIRDIR';
}
exit;


   } catch (Throwable $e) {

    if ($pdo_qrup->inTransaction()) {
        $pdo_qrup->rollBack();
    }

    error_log(
        'FIGHT_HUCUM XETA: ' .
        $e->getMessage() .
        ' | PHP SETIR: ' .
        $e->getLine()
    );

    echo 'XETA: ' .
         $e->getMessage() .
         ' | PHP SETIR: ' .
         $e->getLine();

    exit;
}
}


/* =========================================================
   POST — HÜCUM / MÜDAFİƏ
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hucum = isset($_POST['hucum'])
        ? (int)$_POST['hucum']
        : -1;

    $mudafie = isset($_POST['mudafie'])
        ? (int)$_POST['mudafie']
        : -1;


    if ($hucum < 0 || $hucum > 3) {
        exit('Hücum seçimi düzgün deyil.');
    }

    if ($mudafie < 0 || $mudafie > 3) {
        exit('Müdafiə seçimi düzgün deyil.');
    }


    $pdo_qrup->beginTransaction();

    try {

        $stmt = $pdo_qrup->prepare("
            SELECT *
            FROM qrup_doyusleri
            WHERE id = ?
            LIMIT 1
            FOR UPDATE
        ");

        $stmt->execute([
            (int)$doyus['id']
        ]);

        $doyus_post = $stmt->fetch();

        if (!$doyus_post) {
            throw new Exception('Döyüş tapılmadı.');
        }


        if ((int)$doyus_post['bitdi'] === 1) {

            $pdo_qrup->commit();

            header(
                "Location: fight_hucum.php"
                . "?uid=" . $uid
                . "&lis=" . $lis
                . "&t=" . time()
            );

            exit;
        }


        $men_oyuncu1 =
            ((int)$doyus_post['oyuncu1_id'] === $menim_id);


        $menim_evvelki =
            $men_oyuncu1
                ? $doyus_post['oyuncu1_hucum']
                : $doyus_post['oyuncu2_hucum'];


        /*
         * Bu raundda artıq vurubsa,
         * ikinci dəfə vurmağa icazə vermirik.
         */
        if ($menim_evvelki !== null) {

            $pdo_qrup->commit();

            header(
                "Location: fight_hucum.php"
                . "?uid=" . $uid
                . "&lis=" . $lis
                . "&wait=1"
                . "&t=" . time()
            );

            exit;
        }


        if ($men_oyuncu1) {

            $stmt = $pdo_qrup->prepare("
                UPDATE qrup_doyusleri

                SET

                    oyuncu1_hucum = ?,
                    oyuncu1_mudafie = ?,
                    oyuncu1_hucum_vaxti = NOW()

                WHERE id = ?

                  AND bitdi = 0

                  AND oyuncu1_hucum IS NULL

                LIMIT 1
            ");

        } else {

            $stmt = $pdo_qrup->prepare("
                UPDATE qrup_doyusleri

                SET

                    oyuncu2_hucum = ?,
                    oyuncu2_mudafie = ?,
                    oyuncu2_hucum_vaxti = NOW()

                WHERE id = ?

                  AND bitdi = 0

                  AND oyuncu2_hucum IS NULL

                LIMIT 1
            ");
        }


        $stmt->execute([
            $hucum,
            $mudafie,
            (int)$doyus_post['id']
        ]);


        $pdo_qrup->commit();


        header(
            "Location: fight_hucum.php"
            . "?uid=" . $uid
            . "&lis=" . $lis
            . "&wait=1"
            . "&t=" . time()
        );

        exit;


    } catch (Throwable $e) {

        if ($pdo_qrup->inTransaction()) {
            $pdo_qrup->rollBack();
        }

        exit(
            'Döyüş xətası: ' .
            htmlspecialchars(
                $e->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }
}


/* =========================================================
   DÖYÜŞÜ YENİDƏN OXU
========================================================= */

$stmt = $pdo_qrup->prepare("
    SELECT *
    FROM qrup_doyusleri
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    (int)$doyus['id']
]);
$menim_timeout = false;
$reqib_timeout = false;

if (
    (int)$doyus['bitdi'] === 0 &&
    !empty($doyus['novbe_baslama_tarixi'])
) {

    $timeout_baslama = strtotime(
        $doyus['novbe_baslama_tarixi']
    );

    if (
        $timeout_baslama !== false &&
        (time() - $timeout_baslama) >= 59
    ) {

        if ($doyus['oyuncu1_hucum'] === null) {

            if (
                (int)$doyus['oyuncu1_id'] ===
                (int)$menim_id
            ) {
                $menim_timeout = true;
            } else {
                $reqib_timeout = true;
            }
        }

        if ($doyus['oyuncu2_hucum'] === null) {

            if (
                (int)$doyus['oyuncu2_id'] ===
                (int)$menim_id
            ) {
                $menim_timeout = true;
            } else {
                $reqib_timeout = true;
            }
        }
    }
}


$doyus = $stmt->fetch();

if (!$doyus) {
    exit('Döyüş məlumatı tapılmadı.');
}


/* =========================================================
   MƏN / RƏQİB
========================================================= */

$men_oyuncu1 =
    ((int)$doyus['oyuncu1_id'] === $menim_id);


if ($men_oyuncu1) {

    $menim_secim =
        $doyus['oyuncu1_hucum'];

    $reqib_secim =
        $doyus['oyuncu2_hucum'];

    $menim_mudafie_secim =
        $doyus['oyuncu1_mudafie'];

    $reqib_mudafie_secim =
        $doyus['oyuncu2_mudafie'];

    $menim_can =
        (int)$doyus['oyuncu1_can'];

    $reqib_can =
        (int)$doyus['oyuncu2_can'];

} else {

    $menim_secim =
        $doyus['oyuncu2_hucum'];

    $reqib_secim =
        $doyus['oyuncu1_hucum'];

    $menim_mudafie_secim =
        $doyus['oyuncu2_mudafie'];

    $reqib_mudafie_secim =
        $doyus['oyuncu1_mudafie'];

    $menim_can =
        (int)$doyus['oyuncu2_can'];

    $reqib_can =
        (int)$doyus['oyuncu1_can'];
}


/* =========================================================
   GÖZLƏMƏ
========================================================= */

$gozleyirik = false;

if (
    (int)$doyus['bitdi'] === 0 &&
    (
        $menim_secim !== null &&
        $reqib_secim === null
    )
) {
    $gozleyirik = true;
}

if (
    (int)$doyus['bitdi'] === 0 &&
    isset($_GET['wait']) &&
    (int)$_GET['wait'] === 1
) {
    $gozleyirik = true;
}

if (
    (int)$doyus['bitdi'] === 0 &&
    $menim_secim !== null &&
    $reqib_secim !== null
) {
    $gozleyirik = true;
}


/* =========================================================
   CAN FAİZİ
========================================================= */

$menim_max_can =
    max(
        1,
        (int)$menim_max_can
    );

$reqib_max_can =
    max(
        1,
        (int)$reqib_max_can
    );

$menim_faiz =
    ($menim_can / $menim_max_can) * 100;

$reqib_faiz =
    ($reqib_can / $reqib_max_can) * 100;

$menim_faiz =
    max(
        0,
        min(
            100,
            $menim_faiz
        )
    );

$reqib_faiz =
    max(
        0,
        min(
            100,
            $reqib_faiz
        )
    );


/* =========================================================
   HÜCUM MƏTNLƏRİ
========================================================= */

$hucum_metnleri = [

    0 => 'başa',
    1 => 'sinəyə',
    2 => 'gövdəyə',
    3 => 'ayağa'

];


/* =========================================================
   SON NƏTİCƏ
========================================================= */

$doyus_bitib =
    ((int)$doyus['bitdi'] === 1);

$berabere =
    $doyus_bitib &&
    (int)$doyus['hec_hece'] === 1;

$menim_qalib =
    $doyus_bitib &&
    !$berabere &&
    (int)$doyus['qalib_id'] === (int)$menim_id;

$reqib_qalib =
    $doyus_bitib &&
    !$berabere &&
    (int)$doyus['qalib_id'] !== (int)$menim_id;


/* =========================================================
   HTML
========================================================= */

?>

<!DOCTYPE html PUBLIC
"-//WAPFORUM//DTD XHTML Mobile 1.0//EN"
"http://www.wapforum.org/DTD/xhtml-mobile10.dtd">

<html
xmlns="http://www.w3.org/1999/xhtml"
xml:lang="az"
lang="az"
>

<head>

<meta
http-equiv="content-type"
content="text/html; charset=utf-8"
/>

<meta
name="viewport"
content="width=device-width; initial-scale=1.0; maximum-scale=3.0"
/>

<link
rel="stylesheet"
href="css.css"
type="text/css"
/>

<title>Döyüş | QANLI EFSANE</title>

</head>

<body>

<div
class="main"
style="word-wrap:break-word;"
>

<?php if ($gozleyirik): ?>

<div class="center">

<b>
Rəqibi gözləyin...
</b>

<br/><br/>

Rəqib zərbə atdıqda raund hesablanacaq.

<br/><br/>

<span id="status">
Rəqibin zərbəsi gözlənilir...
</span>

<div class="menu">

<br/>

<li>

<a
href="fight_hucum.php?uid=<?php echo (int)$uid; ?>&lis=<?php echo (int)$lis; ?>&t=<?php echo time(); ?>"

>

Yenilə

</a>

</li>

<li>

<a
href="qrup_komek.php?lis=<?php echo (int)$lis; ?>"

>

Döyüş otağı

</a>

</li>

</div>

</div>

<script type="text/javascript">

(function () {

    "use strict";

    var uid =
        <?php echo (int)$uid; ?>;

    var lis =
        <?php echo (int)$lis; ?>;

    var aktiv = true;

    var sorğuGedir = false;

    var interval = null;

    var redirect = false;

    var hesablanir = false;


    function statusYaz(metn) {

        var status =
            document.getElementById("status");

        if (status) {
            status.innerHTML = metn;
        }
    }


    function dayan() {

        aktiv = false;

        if (interval !== null) {

            clearInterval(interval);

            interval = null;
        }
    }


    /*
     * ƏSAS SƏHİFƏYƏ QAYIT
     */
    function esasSehifeyeQayit() {

        if (redirect) {
            return;
        }

        redirect = true;

        dayan();

        statusYaz(
            "<b>Raund hesablandı...</b>"
        );

        setTimeout(function () {

            window.location.replace(
                "fight_hucum.php"
                + "?uid="
                + encodeURIComponent(uid)
                + "&lis="
                + encodeURIComponent(lis)
                + "&t="
                + new Date().getTime()
            );

        }, 150);
    }


    /*
     * GÖZLƏMƏ OTAĞINA KEÇ
     *
     * doyuse_qosuldu = 0 olduqda
     * bu ekran açılır.
     */
    function gozlemeOtaginaGet() {

        if (redirect) {
            return;
        }

        redirect = true;

        dayan();

        window.location.replace(
            "fight_hucum.php"
            + "?uid="
            + encodeURIComponent(uid)
            + "&lis="
            + encodeURIComponent(lis)
            + "&wait=1"
            + "&t="
            + new Date().getTime()
        );
    }


    /*
     * RƏQİB DƏYİŞDİ EKRANI
     */
    function reqibDeyisdi() {

        if (redirect) {
            return;
        }

        redirect = true;

        dayan();

        window.location.replace(
            "fight_hucum.php"
            + "?uid="
            + encodeURIComponent(uid)
            + "&lis="
            + encodeURIComponent(lis)
            + "&reqib_deyisdi=1"
            + "&t="
            + new Date().getTime()
        );
    }


    /*
     * RAUNDU HESABLA
     */
    function raunduHesabla() {

        if (redirect || hesablanir) {
            return;
        }

        hesablanir = true;

        statusYaz(
            "<b>Raund hesablanır...</b>"
        );


        var xhr =
            new XMLHttpRequest();


        var url =
            "fight_hucum.php"
            + "?uid="
            + encodeURIComponent(uid)
            + "&lis="
            + encodeURIComponent(lis)
            + "&hesabla=1"
            + "&t="
            + new Date().getTime();


        xhr.open(
            "GET",
            url,
            true
        );


        try {

            xhr.setRequestHeader(
                "Cache-Control",
                "no-cache, no-store, must-revalidate"
            );

            xhr.setRequestHeader(
                "Pragma",
                "no-cache"
            );

        } catch (e) {}


        xhr.onreadystatechange =
            function () {

                if (xhr.readyState !== 4) {
                    return;
                }


                if (redirect) {
                    return;
                }


                var cavab =
                    xhr.responseText
                    .replace(
                        /^\s+|\s+$/g,
                        ""
                    );


                if (xhr.status !== 200) {

                    hesablanir = false;

                    statusYaz(
                        "Server gözlənilir..."
                    );

                    return;
                }

if (cavab === "DOVUS_BITDI") {

    redirect = true;

    dayan();

    window.location.replace(
        "qrup_komek.php"
        + "?lis="
        + encodeURIComponent(lis)
        + "&t="
        + new Date().getTime()
    );

    return;
}
                /*
                 * DÖYÜŞ YOXDUR / HAZIRDIR
                 */
                if (
                    cavab === "DOVUS_HAZIRDIR"
                    ||
                    cavab === "DOVUS_YOXDUR"
                ) {

                    esasSehifeyeQayit();

                    return;
                }


                /*
                 * OYUNÇU GÖZLƏMƏ OTAĞINA QAYTARILIB
                 */
                if (cavab === "GOZLEME") {

                    gozlemeOtaginaGet();

                    return;
                }


                /*
                 * RƏQİB DƏYİŞİB
                 */
                if (cavab === "REQIB_DEYISDI") {

                    reqibDeyisdi();

                    return;
                }


                /*
                 * HƏLƏ RƏQİBİN ZƏRBƏSİ GÖZLƏNİLİR
                 */
                if (cavab === "GOZLE") {

                    hesablanir = false;

                    statusYaz(
                        "Rəqibin zərbəsi gözlənilir..."
                    );

                    return;
                }


                /*
                 * XƏTA
                 */
               if (cavab.indexOf("XETA:") === 0) {

    hesablanir = false;

    statusYaz(cavab);

    return;
}

                hesablanir = false;

                statusYaz(
                    "Server cavabı gözlənilir..."
                );
            };


        xhr.onerror =
            function () {

                hesablanir = false;

                if (!redirect) {

                    statusYaz(
                        "Server gözlənilir..."
                    );
                }
            };


        xhr.ontimeout =
            function () {

                hesablanir = false;

                if (!redirect) {

                    statusYaz(
                        "Server gözlənilir..."
                    );
                }
            };


        try {

            xhr.timeout = 7000;

        } catch (e) {}


        try {

            xhr.send(null);

        } catch (e) {

            hesablanir = false;
        }
    }


    /*
     * SERVERİ YOXLA
     */
    function yoxla() {

        if (!aktiv) {
            return;
        }

        if (sorğuGedir || hesablanir) {
            return;
        }

        sorğuGedir = true;


        var xhr =
            new XMLHttpRequest();


        var url =
            "fight_hucum.php"
            + "?uid="
            + encodeURIComponent(uid)
            + "&lis="
            + encodeURIComponent(lis)
            + "&check=1"
            + "&t="
            + new Date().getTime();


        xhr.open(
            "GET",
            url,
            true
        );


        try {

            xhr.setRequestHeader(
                "Cache-Control",
                "no-cache, no-store, must-revalidate"
            );

            xhr.setRequestHeader(
                "Pragma",
                "no-cache"
            );

        } catch (e) {}


        xhr.onreadystatechange =
            function () {

                if (xhr.readyState !== 4) {
                    return;
                }


                sorğuGedir = false;


                if (redirect) {
                    return;
                }


                if (xhr.status !== 200) {

                    statusYaz(
                        "Server yoxlanılır..."
                    );

                    return;
                }


                var cavab =
                    xhr.responseText
                    .replace(
                        /^\s+|\s+$/g,
                        ""
                    );


                /*
                 * OYUNÇU ARTİQ DÖYÜŞDƏ DEYİL
                 *
                 * doyuse_qosuldu = 0
                 */
                if (cavab === "GOZLEME") {

                    gozlemeOtaginaGet();

                    return;
                }


                /*
                 * RƏQİB DƏYİŞİB
                 */
                if (cavab === "REQIB_DEYISDI") {

                    reqibDeyisdi();

                    return;
                }

if (cavab === "DOVUS_BITDI") {

    redirect = true;

    dayan();

    window.location.replace(
        "qrup_komek.php"
        + "?lis="
        + encodeURIComponent(lis)
        + "&t="
        + new Date().getTime()
    );

    return;
}
                /*
                 * DÖYÜŞ YOXDUR / HAZIRDIR
                 */
                if (
                    cavab === "DOVUS_HAZIRDIR"
                    ||
                    cavab === "DOVUS_YOXDUR"
                ) {

                    esasSehifeyeQayit();

                    return;
                }


                /*
                 * RAUND HAZIRDIR
                 */
                if (
                    cavab === "RAUND_HAZIRDIR"
                ) {

                    dayan();

                    setTimeout(
                        raunduHesabla,
                        50
                    );

                    return;
                }


                /*
                 * RƏQİBİN ZƏRBƏSİ GÖZLƏNİLİR
                 */
                if (cavab === "GOZLE") {

                    statusYaz(
                        "Rəqibin zərbəsi gözlənilir..."
                    );

                    return;
                }


                /*
                 * NORMAL AKTİV VƏZİYYƏT
                 */
                if (cavab === "AKTIV") {

                    statusYaz(
                        "Döyüş davam edir..."
                    );

                    return;
                }


                statusYaz(
                    "Server yoxlanılır..."
                );
            };


        xhr.onerror =
            function () {

                sorğuGedir = false;

                if (!redirect) {

                    statusYaz(
                        "Server gözlənilir..."
                    );
                }
            };


        xhr.ontimeout =
            function () {

                sorğuGedir = false;

                if (!redirect) {

                    statusYaz(
                        "Server gözlənilir..."
                    );
                }
            };


        try {

            xhr.timeout = 7000;

        } catch (e) {}


        try {

            xhr.send(null);

        } catch (e) {

            sorğuGedir = false;
        }
    }


    /*
     * İLK YOXLAMA
     */
    yoxla();


    /*
     * HƏR 1 SANİYƏDƏ BİR YOXLAMA
     */
    interval =
        setInterval(
            yoxla,
            1000
        );


    window.onbeforeunload =
        function () {

            dayan();
        };

})();

</script>


<?php else: ?>

<div class="center">

<div class="menu">

<a
href="qrup_komek.php?lis=<?php echo (int)$lis; ?>"

>

Döyüş otağı

</a>

</div>

</div>

<?php


/* =========================================================
   SON RAUND NƏTİCƏSİ
========================================================= */

$son_neticə_var =
(
    (int)$doyus['raund'] > 1
    &&
    (
        $doyus['son_oyuncu1_hucum'] !== null
        ||
        $doyus['son_oyuncu2_hucum'] !== null
    )
);


if ($son_neticə_var) {


    if ($men_oyuncu1) {

        $menim_hucum =
            $doyus['son_oyuncu1_hucum'];

        $menim_zarar =
            (int)$doyus['son_oyuncu1_zarar'];

        $menim_kritik =
            (int)$doyus['son_oyuncu1_kritik'];

        $menim_xeta =
            (int)$doyus['son_oyuncu1_xeta'];


        $reqib_hucum =
            $doyus['son_oyuncu2_hucum'];

        $reqib_zarar =
            (int)$doyus['son_oyuncu2_zarar'];

        $reqib_kritik =
            (int)$doyus['son_oyuncu2_kritik'];

        $reqib_xeta =
            (int)$doyus['son_oyuncu2_xeta'];

    } else {

        $menim_hucum =
            $doyus['son_oyuncu2_hucum'];

        $menim_zarar =
            (int)$doyus['son_oyuncu2_zarar'];

        $menim_kritik =
            (int)$doyus['son_oyuncu2_kritik'];

        $menim_xeta =
            (int)$doyus['son_oyuncu2_xeta'];


        $reqib_hucum =
            $doyus['son_oyuncu1_hucum'];

        $reqib_zarar =
            (int)$doyus['son_oyuncu1_zarar'];

        $reqib_kritik =
            (int)$doyus['son_oyuncu1_kritik'];

        $reqib_xeta =
            (int)$doyus['son_oyuncu1_xeta'];
    }


    $son_mudafie =
        (int)$doyus['son_mudafie'];


    $menim_mudafie_tutdu = false;
    $reqib_mudafie_tutdu = false;


    if ($men_oyuncu1) {

        if (
            $son_mudafie === 1 ||
            $son_mudafie === 3
        ) {
            $menim_mudafie_tutdu = true;
        }

        if (
            $son_mudafie === 2 ||
            $son_mudafie === 3
        ) {
            $reqib_mudafie_tutdu = true;
        }

    } else {

        if (
            $son_mudafie === 2 ||
            $son_mudafie === 3
        ) {
            $menim_mudafie_tutdu = true;
        }

        if (
            $son_mudafie === 1 ||
            $son_mudafie === 3
        ) {
            $reqib_mudafie_tutdu = true;
        }
    }


    $menim_hucum_metn =
        isset($hucum_metnleri[$menim_hucum])
            ? $hucum_metnleri[$menim_hucum]
            : 'zərbə';


    $reqib_hucum_metn =
        isset($hucum_metnleri[$reqib_hucum])
            ? $hucum_metnleri[$reqib_hucum]
            : 'zərbə';

?>

<br/>

<?php if ($menim_hucum !== null): ?>

<div>

Siz vurdunuz

<b>

<?php

echo htmlspecialchars(
    $menim_hucum_metn,
    ENT_QUOTES,
    'UTF-8'
);

?>

</b>

<?php if ($reqib_mudafie_tutdu): ?>

<span style="color:#777;">
— rəqib zərbəni dəf etdi.
</span>

<?php elseif ($menim_xeta): ?>

<span style="color:seagreen;">
— rəqib zərbədən yayındı.
</span>

<?php elseif ($menim_zarar > 0): ?>

—

<b>

<?php if ($menim_kritik): ?>

<span style="color:#FF0000;">

<?php echo $menim_zarar; ?>

(krit)

</span>

<?php else: ?>

<?php echo $menim_zarar; ?>

<?php endif; ?>

</b>

zərər vurdunuz.

<?php else: ?>

<span style="color:#777;">
— zərər vermədi.
</span>

<?php endif; ?>

</div>

<hr/>

<?php endif; ?>

<?php if ($reqib_hucum !== null): ?>

<div>

Rəqib vurdu

<b>

<?php

echo htmlspecialchars(
    $reqib_hucum_metn,
    ENT_QUOTES,
    'UTF-8'
);

?>

</b>

<?php if ($menim_mudafie_tutdu): ?>

<span style="color:#777;">
— siz zərbəni dəf etdiniz.
</span>

<?php elseif ($reqib_xeta): ?>

<span style="color:seagreen;">
— siz zərbədən yayındınız.
</span>

<?php elseif ($reqib_zarar > 0): ?>

—

<b>

<?php if ($reqib_kritik): ?>

<span style="color:#FF0000;">

<?php echo $reqib_zarar; ?>

(krit)

</span>

<?php else: ?>

<?php echo $reqib_zarar; ?>

<?php endif; ?>

</b>

zərər vurdu.

<?php else: ?>

<span style="color:#777;">
— zərər vermədi.
</span>

<?php endif; ?>

</div>

<?php endif; ?>

<br/>

<?php

}

?>

<?php if (!$doyus_bitib): ?>

<br/>

<small>
59 saniyə ərzində zərbə atmasanız məğlub olacaqsınız.
</small>

<br/>

<?php endif; ?>

<!-- =====================================================
     MƏN
===================================================== -->

<div class="battle_log">

<b>

<a
href="infoforce.php?uid=<?php echo (int)$menim_id; ?>"

>

<?php

echo htmlspecialchars(
    $menim_login,
    ENT_QUOTES,
    'UTF-8'
);

?>

</a>

[<?php echo (int)$menim_level; ?>]

</b>

<br/>

<div
style="
width:130px;
height:14px;
background:#d90000;
border:1px solid #111;
margin:5px 0;
position:relative;
overflow:hidden;
"
>

<div
style="
width:<?php

echo max(
0,
min(
100,
$menim_faiz
)
);

?>%;
height:100%;
background:#39a852;
"

>

</div>

<div
style="
position:absolute;
top:0;
left:0;
width:100%;
height:100%;
text-align:center;
color:#fff;
font-size:11px;
line-height:14px;
font-weight:bold;
"
>

<?php echo (int)$menim_can; ?>

/

<?php echo (int)$menim_max_can; ?>

</div>

</div>

</div>

<div class="left">

<b>
VS
</b>

</div>

<!-- =====================================================
     RƏQİB
===================================================== -->

<div class="battle_log">

<b>

<a
href="infoforce.php?uid=<?php echo (int)$reqib_id; ?>"

>

<?php

echo htmlspecialchars(
    $reqib_login,
    ENT_QUOTES,
    'UTF-8'
);

?>

</a>

[<?php echo (int)$reqib_level; ?>]

</b>

<br/>

<div
style="
width:130px;
height:14px;
background:#d90000;
border:1px solid #111;
margin:5px 0;
position:relative;
overflow:hidden;
"
>

<div
style="
width:<?php

echo max(
0,
min(
100,
$reqib_faiz
)
);

?>%;
height:100%;
background:#39a852;
"

>

</div>

<div
style="
position:absolute;
top:0;
left:0;
width:100%;
height:100%;
text-align:center;
color:#fff;
font-size:11px;
line-height:14px;
font-weight:bold;
"
>

<?php echo (int)$reqib_can; ?>

/

<?php echo (int)$reqib_max_can; ?>

</div>

</div>

</div>

<br/>

<!-- =====================================================
     RAUND
===================================================== -->

<div class="left">

Raund:

<b>

<?php

echo max(
    1,
    (int)$doyus['raund']
);

?>

</b>

</div>

<?php if ($doyus_bitib): ?>

<br/>

<div class="center">

<?php if ($berabere): ?>

<b>
Döyüş heç-heçə bitdi.
</b>

<?php elseif ($menim_qalib): ?>

<b style="color:green;">
Siz qalib gəldiniz!
</b>

<div style="font-size:10px;color:#fff;background:#222;padding:5px;margin-top:5px;">
MENIM ID=<?php echo (int)$menim_id; ?>
<br>
QALIB ID=<?php echo (int)$doyus['qalib_id']; ?>
<br>
MENIM QALIB=<?php echo $menim_qalib ? '1' : '0'; ?>
<br>
REQIB QALIB=<?php echo $reqib_qalib ? '1' : '0'; ?>
</div>

<?php elseif ($reqib_qalib): ?>

<b style="color:red;">
Siz məğlub oldunuz.
</b>

<div style="font-size:10px;color:#fff;background:#222;padding:5px;margin-top:5px;">
MENIM ID=<?php echo (int)$menim_id; ?>
<br>
QALIB ID=<?php echo (int)$doyus['qalib_id']; ?>
<br>
MENIM QALIB=<?php echo $menim_qalib ? '1' : '0'; ?>
<br>
REQIB QALIB=<?php echo $reqib_qalib ? '1' : '0'; ?>
</div>

<?php endif; ?>

<br/><br/>

<a
href="qrup_komek.php?lis=<?php echo (int)$lis; ?>"

>

Döyüş otağına qayıt </a>

</div>

<?php else: ?>

<!-- =====================================================
     HÜCUM FORMU
===================================================== -->

<form
method="post"
action="fight_hucum.php?uid=<?php echo (int)$uid; ?>&amp;lis=<?php echo (int)$lis; ?>"
>

<b>
Hücum:
</b>

<br/>

<select name="hucum">

<option value="0">
Başdan
</option>

<option value="1">
Sinədən
</option>

<option value="2">
Gövdədən
</option>

<option value="3">
Ayaqdan
</option>

</select>

<br/>

<b>
Müdafiə:
</b>

<br/>

<select name="mudafie">

<option value="0">
Baş və Sinə
</option>

<option value="1">
Sinə və Gövdə
</option>

<option value="2">
Gövdə və Ayaq
</option>

<option value="3">
Ayaq və Baş
</option>

</select>

<br/>

<input
type="submit"
class="button_big"
value="Zərbə Vur"
/>

</form>

<?php endif; ?>

<?php endif; ?>

</div>
<script type="text/javascript">

(function () {

    "use strict";

    var uid =
        <?php echo (int)$uid; ?>;

    var lis =
        <?php echo (int)$lis; ?>;

    var yoxlamaGedir = false;

    var aktiv = true;


    function yoxlaCixaris() {

        if (!aktiv || yoxlamaGedir) {
            return;
        }

        yoxlamaGedir = true;


        var xhr =
            new XMLHttpRequest();


        var url =
            "fight_hucum.php"
            + "?uid="
            + encodeURIComponent(uid)
            + "&lis="
            + encodeURIComponent(lis)
            + "&cixaris=1"
            + "&t="
            + new Date().getTime();


        xhr.open(
            "GET",
            url,
            true
        );


        xhr.onreadystatechange =
            function () {

                if (xhr.readyState !== 4) {
                    return;
                }

                yoxlamaGedir = false;


                if (xhr.status !== 200) {
                    return;
                }


                var cavab =
                    xhr.responseText
                    .replace(
                        /^\s+|\s+$/g,
                        ""
                    );
if (cavab === "REQIB_DEYISDI") {

    aktiv = false;

    window.location.replace(
        "fight_hucum.php"
        + "?uid="
        + encodeURIComponent(uid)
        + "&lis="
        + encodeURIComponent(lis)
        + "&reqib_deyisdi=1"
        + "&t="
        + new Date().getTime()
    );

    return;
}

                if (cavab === "CIxARILDI") {

                    aktiv = false;


                    window.location.replace(
                        "fight_hucum.php"
                        + "?uid="
                        + encodeURIComponent(uid)
                        + "&lis="
                        + encodeURIComponent(lis)
                        + "&t="
                        + new Date().getTime()
                    );

                }

            };


        xhr.onerror =
            function () {

                yoxlamaGedir = false;

            };


        try {

            xhr.timeout = 5000;

        } catch (e) {}


        try {

            xhr.send(null);

        } catch (e) {

            yoxlamaGedir = false;

        }

    }


    yoxlaCixaris();


    setInterval(
        yoxlaCixaris,
        1000
    );


    window.onbeforeunload =
        function () {

            aktiv = false;

        };

})();

</script>

</body>

</html>

<?php

ob_end_flush();

?>
