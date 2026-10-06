<?php

session_start();

require_once "config.php";
require_once "user_data.php";
require_once "doyus_sistem.php";
require_once "guc_parametrləri.php";


/* =========================================================
   LOGIN YOXLAMASI
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];


/* =========================================================
   DUEL ID
========================================================= */

$duel_id = isset($_GET['duel_id'])
    ? (int)$_GET['duel_id']
    : 0;

if ($duel_id <= 0) {
    header("Location: online.php");
    exit;
}


/* =========================================================
   DUEL MÆLUMATLARI
========================================================= */

$stmt_duel = $pdo->prepare("
    SELECT
        d.id,

        d.oyuncu1_id,
        d.oyuncu2_id,

      d.qalib_id,
d.bitdi,
d.hec_hece,
 d.bitdi_qalib,
    d.bitdi_meglub,
d.yaradilis_tarixi,

        d.qebul_edildi,
        d.novbe_id,
        d.raund,

        d.oyuncu1_can,
        d.oyuncu2_can,

        d.oyuncu1_mudafie,
        d.oyuncu2_mudafie,

        d.oyuncu1_hucum,
        d.oyuncu2_hucum,

        d.son_oyuncu1_hucum,
        d.son_oyuncu2_hucum,

        d.son_hucum_eden,
        d.son_mudafie_eden,

        d.son_zarar,
        d.son_kritik,
        d.son_xeta,
        d.son_mudafie,

        d.son_oyuncu1_zarar,
        d.son_oyuncu2_zarar,

        d.son_oyuncu1_kritik,
        d.son_oyuncu2_kritik,

        d.son_oyuncu1_xeta,
        d.son_oyuncu2_xeta,

        d.novbe_baslama_tarixi,
        d.oyuncu1_hucum_vaxti,
d.oyuncu2_hucum_vaxti,

        p1.login AS player1_login,
        p1.oyuncunun_seviyyesi AS player1_level,

        p2.login AS player2_login,
        p2.oyuncunun_seviyyesi AS player2_level

    FROM duel d

    INNER JOIN users p1
        ON p1.id = d.oyuncu1_id

    INNER JOIN users p2
        ON p2.id = d.oyuncu2_id

    WHERE d.id = :duel_id
      AND (
          d.oyuncu1_id = :my_id
          OR d.oyuncu2_id = :my_id
      )

    LIMIT 1
");


$stmt_duel->execute([
    ':duel_id' => $duel_id,
    ':my_id'   => $my_id
]);
$duel = $stmt_duel->fetch(PDO::FETCH_ASSOC);



if (!$duel) {
    exit('Duel tapılmadı.');
}



/*
=========================================================
 DUEL BİTİBSƏ — NƏTİCƏ REJİMİ
=========================================================
*/

$duel_bitib = ((int)$duel['bitdi'] === 1);

$duel_neticə_rejimi = false;
$duel_neticə_basligi = '';
$duel_neticə_metni = '';

if ($duel_bitib) {

    $duel_neticə_rejimi = true;

    $qalib_id = (int)$duel['qalib_id'];


    /*
    =====================================================
    HEÇ-HEÇƏ
    =====================================================
    */

    if ($qalib_id === 0) {

        $duel_neticə_basligi =
            'Duel heç-heçə bitdi';

        $duel_neticə_metni =
            'Bu dueldə qalib müəyyən edilmədi.';


    /*
    =====================================================
    MƏN QALİBƏM
    =====================================================
    */

    } elseif ($qalib_id === $my_id) {

        $duel_neticə_basligi =
            'Siz qalib gəldiniz!';

        $duel_neticə_metni =
            'Duel sizin qələbənizlə başa çatdı.';


    /*
    =====================================================
    MƏN MƏĞLUBAM
    =====================================================
    */

    } else {

        $duel_neticə_basligi =
            'Siz məğlub oldunuz!';

        $duel_neticə_metni =
            'Duel rəqibin qələbəsi ilə başa çatdı.';
    }
}



/* =========================================================
   TIMEOUT YOXLAMASI
========================================================= */

$ilk_vaxt = 300;
$sonraki_vaxt = 180;

$novbe_baslama_vaxti = !empty($duel['novbe_baslama_tarixi'])
    ? strtotime($duel['novbe_baslama_tarixi'])
    : 0;

$vaxt_limit = ((int)$duel['raund'] === 0)
    ? $ilk_vaxt
    : $sonraki_vaxt;


if (
    $novbe_baslama_vaxti > 0 &&
    (time() - $novbe_baslama_vaxti) >= $vaxt_limit &&
    (int)$duel['bitdi'] === 0 &&
    empty($duel['qalib_id'])
) {

    /*
     * Cari raundda tərəflərin zərbə vəziyyəti.
     */
    $oyuncu1_hucum_var = (
        $duel['oyuncu1_hucum'] !== null
    );

    $oyuncu2_hucum_var = (
        $duel['oyuncu2_hucum'] !== null
    );


 

    /*
     * =====================================================
     * BİR TƏRƏF VURUB, DİGƏRİ VURMAYIBSA
     * =====================================================
     */

    if (
        $oyuncu1_hucum_var &&
        !$oyuncu2_hucum_var
    ) {

        $timeout_qalib  = (int)$duel['oyuncu1_id'];
        $timeout_meglub = (int)$duel['oyuncu2_id'];

    } elseif (
        $oyuncu2_hucum_var &&
        !$oyuncu1_hucum_var
    ) {

        $timeout_qalib  = (int)$duel['oyuncu2_id'];
        $timeout_meglub = (int)$duel['oyuncu1_id'];

    } else {

        /*
         * Hər iki tərəf zərbə vurubsa,
         * timeout nəticəsi yaratmırıq.
         */
        $timeout_qalib  = 0;
        $timeout_meglub = 0;
    }


    /*
     * =====================================================
     * QALİB / MƏĞLUB DB-YƏ YAZILIR
     * =====================================================
     */

    if (
        $timeout_qalib > 0 &&
        $timeout_meglub > 0
    ) {

        $stmt_timeout = $pdo->prepare("
            UPDATE duel
            SET
                qalib_id = :qalib_id,
                bitdi_qalib = :bitdi_qalib,
                bitdi_meglub = :bitdi_meglub,
                bitdi = 1,
                novbe_id = NULL
            WHERE id = :duel_id
              AND qalib_id IS NULL
              AND bitdi = 0
        ");

        $stmt_timeout->execute([
            ':qalib_id'     => $timeout_qalib,
            ':bitdi_qalib'  => $timeout_qalib,
            ':bitdi_meglub' => $timeout_meglub,
            ':duel_id'      => (int)$duel_id
        ]);

        header(
            "Location: fight.php?duel_id="
            . (int)$duel_id
        );

        exit;
    }
}

/* =========================================================
   DUEL VAXTI
   İlk zərbə: 300 saniyə
   Sonrakı raundlar: 180 saniyə
========================================================= */

$novbe_baslama = !empty($duel['novbe_baslama_tarixi'])
    ? strtotime($duel['novbe_baslama_tarixi'])
    : time();

/*
 * İlk raund = 300 saniyə.
 * İlk raund bitdikdən sonra = 180 saniyə.
 *
 * raund = 0  -> 300 saniyə
 * raund > 0  -> 180 saniyə
 */
$vaxt_limit = ((int)$duel['raund'] === 0)
    ? 300
    : 180;
$kecen_vaxt = time() - $novbe_baslama;
$qalan_vaxt = $vaxt_limit - $kecen_vaxt;

if ($qalan_vaxt < 0) {
    $qalan_vaxt = 0;
}


/* =========================================================
   ZƏRBƏ FORMUNDAN GƏLƏN MƏLUMATLAR
========================================================= */

/* =========================================================
   ZÆRBÆ FORMUNDAN GÆLÆN MÆLUMATLAR
========================================================= */


/* =========================================================
   ZÆRBÆ VURULDU
========================================================= */

$vurdum = false;







/*
|--------------------------------------------------------------------------
| RÆQÄ°BÄ°N ID-SÄ°
|--------------------------------------------------------------------------
*/

$reqib_id = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$duel['oyuncu2_id']
    : (int)$duel['oyuncu1_id'];


/* =========================================================
   OYUNÃ‡ULAR
========================================================= */

$player1_id = (int)$duel['oyuncu1_id'];
$player2_id = (int)$duel['oyuncu2_id'];

$player1_login = $duel['player1_login'];
$player2_login = $duel['player2_login'];

$player1_level = (int)$duel['player1_level'];
$player2_level = (int)$duel['player2_level'];


/* =========================================================
   MÆNÄ°M RESURSLARIM
========================================================= */

$stmt_user = $pdo->prepare("
    SELECT
        qızıl,
        brılyant,
        enerjı,
        oyuncunun_seviyyesi
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
   OYUNÃ‡U PARAMETRLÆRÄ°
========================================================= */

$stmt_players = $pdo->prepare("
    SELECT
        u.id,
        u.login,
        u.movqe,
        u.oyuncunun_seviyyesi,

op.min_zerbe,
op.max_zerbe,
op.can,
op.mudafie,
op.krit,
op.anti_krit,
op.uvorot,
op.anti_uvorot

    FROM users u

    INNER JOIN oyuncu_parametrleri op
        ON op.user_id = u.id

    WHERE u.id IN (:player1_id, :player2_id)
");

$stmt_players->execute([
    ':player1_id' => $player1_id,
    ':player2_id' => $player2_id
]);

$players = [];

while ($row = $stmt_players->fetch(PDO::FETCH_ASSOC)) {
    $players[(int)$row['id']] = $row;
}

if (!isset($players[$player1_id]) || !isset($players[$player2_id])) {
    exit('DÃ¶yÃ¼ÅŸ parametrlÉ™ri tapÄ±lmadÄ±.');
}

$player1 = $players[$player1_id];
$player2 = $players[$player2_id];


/* =========================================================
   GÃœC BONUSLARI
========================================================= */

function guc_bonus_getir(PDO $pdo, int $user_id): array
{
    $stmt = $pdo->prepare("
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

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $bonus = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bonus) {
        return [
            'zerbe'       => 0,
            'mudafie'     => 0,
            'can'         => 0,
            'krit'        => 0,
            'anti_krit'   => 0,
            'uvorot'      => 0,
            'anti_uvorot' => 0
        ];
    }

    return $bonus;
}

$player1_guc_bonus = guc_bonus_getir($pdo, $player1_id);
$player2_guc_bonus = guc_bonus_getir($pdo, $player2_id);


/* =========================================================
   GEYÄ°NÄ°LMÄ°Å ÆÅYALARIN BONUSLARI
========================================================= */

/* =========================================================
   GEYÄ°NÄ°LMÄ°Å ÆÅYALARIN BONUSLARI
========================================================= */

function esya_bonus_getir(PDO $pdo, int $user_id): array
{
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(random_can), 0) AS can,
            COALESCE(SUM(random_mudafie), 0) AS mudafie,
            COALESCE(SUM(random_min_zerbe), 0) AS min_zerbe,
            COALESCE(SUM(random_max_zerbe), 0) AS max_zerbe,

            COALESCE(SUM(random_krit), 0) AS krit,
            COALESCE(SUM(random_krit_faiz), 0) AS krit_faiz,

            COALESCE(SUM(random_anti_krit), 0) AS anti_krit,
            COALESCE(SUM(random_anti_krit_faiz), 0) AS anti_krit_faiz,

            COALESCE(SUM(random_uvorot), 0) AS uvorot,
            COALESCE(SUM(random_uvorot_faiz), 0) AS uvorot_faiz,

            COALESCE(SUM(random_anti_uvorot), 0) AS anti_uvorot,
            COALESCE(SUM(random_anti_uvorot_faiz), 0) AS anti_uvorot_faiz

        FROM canta

        WHERE user_id = :user_id
          AND geyimde = 1
          AND say > 0
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $bonus = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bonus) {
        return [
            'can' => 0,
            'mudafie' => 0,
            'min_zerbe' => 0,
            'max_zerbe' => 0,
            'krit' => 0,
            'krit_faiz' => 0,
            'anti_krit' => 0,
            'anti_krit_faiz' => 0,
            'uvorot' => 0,
            'uvorot_faiz' => 0,
            'anti_uvorot' => 0,
            'anti_uvorot_faiz' => 0
        ];
    }

    return array_map('intval', $bonus);
}

$player1_esya = esya_bonus_getir($pdo, $player1_id);
$player2_esya = esya_bonus_getir($pdo, $player2_id);

/* =========================================================
   OYUNÃ‡U PARAMETRLÆRÄ°NÄ° HESABLAYAN FUNKSÄ°YA
========================================================= */

function doyus_parametrlÉ™rini_hesabla(
    array $player,
    array $guc_bonus,
    array $esya_bonus,
    int $ilkin_zerbe_min,
    int $ilkin_zerbe_max,
    int $ilkin_can,
    int $ilkin_mudafie,
    int $ilkin_krit,
    int $ilkin_antikrit,
    int $ilkin_uvorot,
    int $ilkin_antiuvorot
): array {

    /* -------------------------
       ÆSAS PARAMETRLÆR
    ------------------------- */

    $min_zerbe =
        $ilkin_zerbe_min
        + (int)$player['min_zerbe']
        + (int)$guc_bonus['zerbe'];

    $max_zerbe =
        $ilkin_zerbe_max
        + (int)$player['max_zerbe']
        + (int)$guc_bonus['zerbe'];

$zerbe_1 = $min_zerbe;
$zerbe_2 = $max_zerbe;

$min_zerbe = min($zerbe_1, $zerbe_2);
$max_zerbe = max($zerbe_1, $zerbe_2);
    $can =
        $ilkin_can
        + (int)$player['can']
        + (int)$guc_bonus['can'];

    $mudafie =
        $ilkin_mudafie
        + (int)$player['mudafie']
        + (int)$guc_bonus['mudafie'];


    /* -------------------------
       KRÄ°T
    ------------------------- */

    $krit =
        $ilkin_krit
        + (int)$player['krit']
        + (int)$guc_bonus['krit'];

  $krit_faiz =
    (int)$esya_bonus['krit_faiz'];

    $krit +=
        $krit * $krit_faiz / 100;

    $krit = (int)round($krit);


    /* -------------------------
       ANTÄ° KRÄ°T
    ------------------------- */

    $anti_krit =
        $ilkin_antikrit
        + (int)$player['anti_krit']
        + (int)$guc_bonus['anti_krit'];

 $anti_krit_faiz =
    (int)$esya_bonus['anti_krit_faiz'];

    $anti_krit +=
        $anti_krit * $anti_krit_faiz / 100;

    $anti_krit = (int)round($anti_krit);


    /* -------------------------
       UVOROT
    ------------------------- */

    $uvorot =
        $ilkin_uvorot
        + (int)$player['uvorot']
        + (int)$guc_bonus['uvorot'];

 $uvorot_faiz =
    (int)$esya_bonus['uvorot_faiz'];

    $uvorot +=
        $uvorot * $uvorot_faiz / 100;

    $uvorot = (int)round($uvorot);


    /* -------------------------
       ANTÄ° UVOROT
    ------------------------- */

    $anti_uvorot =
        $ilkin_antiuvorot
        + (int)$player['anti_uvorot']
        + (int)$guc_bonus['anti_uvorot'];

  $anti_uvorot_faiz =
    (int)$esya_bonus['anti_uvorot_faiz'];

    $anti_uvorot +=
        $anti_uvorot * $anti_uvorot_faiz / 100;

    $anti_uvorot = (int)round($anti_uvorot);


    return [

        'min_zerbe' => $min_zerbe,
        'max_zerbe' => $max_zerbe,

        'can' => $can,

        'mudafie' => $mudafie,

        'krit' => $krit,

        'anti_krit' => $anti_krit,

        'uvorot' => $uvorot,

        'anti_uvorot' => $anti_uvorot,

        'krit_faiz' => $krit_faiz,

        'anti_krit_faiz' => $anti_krit_faiz,

        'uvorot_faiz' => $uvorot_faiz,

        'anti_uvorot_faiz' => $anti_uvorot_faiz
    ];
}


/* =========================================================
   PLAYER 1 YEKUN PARAMETRLÆR
========================================================= */

$player1_yekun = doyus_parametrlÉ™rini_hesabla(

    $player1,
    $player1_guc_bonus,
    $player1_esya,

    (int)$ilkin_zerbe_min,
    (int)$ilkin_zerbe_max,
    (int)$ilkin_can,
    (int)$ilkin_mudafie,
    (int)$ilkin_krit,
    (int)$ilkin_antikrit,
    (int)$ilkin_uvorot,
    (int)$ilkin_antiuvorot
);


/* =========================================================
   PLAYER 2 YEKUN PARAMETRLÆR
========================================================= */

$player2_yekun = doyus_parametrlÉ™rini_hesabla(

    $player2,
    $player2_guc_bonus,
    $player2_esya,

    (int)$ilkin_zerbe_min,
    (int)$ilkin_zerbe_max,
    (int)$ilkin_can,
    (int)$ilkin_mudafie,
    (int)$ilkin_krit,
    (int)$ilkin_antikrit,
    (int)$ilkin_uvorot,
    (int)$ilkin_antiuvorot
);


/* =========================================================
   DÆYÄ°ÅÆNLÆRÄ° KÃ–HNÆ ADLARLA SAXLAYIRIQ
   HTML HÄ°SSÆSÄ°NDÆ DÆYÄ°ÅÄ°KLÄ°K AZ OLSUN
========================================================= */

$player1_yekun_min_zerbe = $player1_yekun['min_zerbe'];
$player1_yekun_max_zerbe = $player1_yekun['max_zerbe'];
$player1_yekun_can = $player1_yekun['can'];
$player1_yekun_mudafie = $player1_yekun['mudafie'];
$player1_yekun_krit = $player1_yekun['krit'];
$player1_yekun_anti_krit = $player1_yekun['anti_krit'];
$player1_yekun_uvorot = $player1_yekun['uvorot'];
$player1_yekun_anti_uvorot = $player1_yekun['anti_uvorot'];


$player2_yekun_min_zerbe = $player2_yekun['min_zerbe'];
$player2_yekun_max_zerbe = $player2_yekun['max_zerbe'];
$player2_yekun_can = $player2_yekun['can'];
$player2_yekun_mudafie = $player2_yekun['mudafie'];
$player2_yekun_krit = $player2_yekun['krit'];
$player2_yekun_anti_krit = $player2_yekun['anti_krit'];
$player2_yekun_uvorot = $player2_yekun['uvorot'];
$player2_yekun_anti_uvorot = $player2_yekun['anti_uvorot'];

if (
    !$duel_bitib &&
    isset($_GET['go']) &&
    $_GET['go'] === 'vurdum' &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    /* -----------------------------------------------------
       HÃœCUM VÆ MÃœDAFÄ°Æ SEÃ‡Ä°MÄ°
    ----------------------------------------------------- */

    $hucum = isset($_POST['hucum'])
        ? (int)$_POST['hucum']
        : -1;

    $mudafie = isset($_POST['mudafie'])
        ? (int)$_POST['mudafie']
        : -1;


    if ($hucum < 0 || $hucum > 3) {
        exit('YanlÄ±ÅŸ hÃ¼cum seÃ§imi.');
    }

    if ($mudafie < 0 || $mudafie > 3) {
        exit('YanlÄ±ÅŸ mÃ¼dafiÉ™ seÃ§imi.');
    }


    /* -----------------------------------------------------
       DUELÄ° YENÄ°DÆN GÆTÄ°R
    ----------------------------------------------------- */

    $stmt_action = $pdo->prepare("
        SELECT
            id,
            oyuncu1_id,
            oyuncu2_id,
            qalib_id,
            bitdi,
            bitdi_qalib,
            bitdi_meglub,
            qebul_edildi,
            novbe_id,
            raund,
oyuncu1_hucum_vaxti,
oyuncu2_hucum_vaxti,
            oyuncu1_can,
            oyuncu2_can,

            oyuncu1_mudafie,
            oyuncu2_mudafie,

           oyuncu1_hucum,
oyuncu2_hucum,

son_oyuncu1_hucum,
son_oyuncu2_hucum,

son_hucum_eden,

            son_mudafie_eden,
            son_zarar,
            son_kritik,
            son_xeta,
            son_mudafie,
            novbe_baslama_tarixi
        FROM duel

        WHERE id = :duel_id
          AND (
              oyuncu1_id = :my_id
              OR oyuncu2_id = :my_id
          )

        LIMIT 1
    ");

    $stmt_action->execute([
        ':duel_id' => $duel_id,
        ':my_id'   => $my_id
    ]);

    $duel_action = $stmt_action->fetch(PDO::FETCH_ASSOC);


    if (!$duel_action) {
        exit('Duel Tapılmadı.');
    }

 /* =====================================================
    ACTION ZAMANI TIMEOUT YOXLAMASI
 ===================================================== */

$action_novbe_baslama = !empty($duel_action['novbe_baslama_tarixi']) 
    ? strtotime($duel_action['novbe_baslama_tarixi']) 
    : 0;

$action_vaxt_limit = ((int)$duel_action['raund'] === 0) ? 300 : 180;


if (
    $action_novbe_baslama > 0 &&
    (time() - $action_novbe_baslama) >= $action_vaxt_limit &&
    (int)$duel_action['bitdi'] === 0 &&
    empty($duel_action['qalib_id'])
) {

    /*
     * Heç-heçə:
     * Bu raundda HƏR İKİ TƏRƏF zərbə vurmayıbsa,
     * duel heç-heçə bitir.
     */
    if (
        $duel_action['oyuncu1_hucum'] === null &&
        $duel_action['oyuncu2_hucum'] === null
    ) {

$stmt_hec_hece = $pdo->prepare("
    UPDATE duel
    SET
       qalib_id = 0,
hec_hece = 1,
bitdi = 1,
bitdi_qalib = 0,
        bitdi_meglub = 0,
        novbe_id = NULL
    WHERE id = :duel_id
      AND bitdi = 0
      AND (qalib_id IS NULL OR qalib_id = 0)
");

$stmt_hec_hece->execute([
    ':duel_id' => (int)$duel_id
]);
header(
    "Location: fight.php?duel_id="
    . (int)$duel_id
    . "&hec_hece=1"
);

exit;
}


    /*
     * Əgər tərəflərdən biri zərbə vurub,
     * digəri vurmayıbsa → növbəsi olan məğlub olur.
     */
    if (!empty($duel_action['novbe_id'])) {

        $timeout_novbe_id = (int)$duel_action['novbe_id'];

        $timeout_qalib_id =
            $timeout_novbe_id === (int)$duel_action['oyuncu1_id']
                ? (int)$duel_action['oyuncu2_id']
                : (int)$duel_action['oyuncu1_id'];

        $stmt_timeout_action = $pdo->prepare("
            UPDATE duel
            SET
                qalib_id = :qalib_id,
                bitdi_qalib = :bitdi_qalib,
                bitdi_meglub = :bitdi_meglub,
                bitdi = 1,
                novbe_id = NULL
            WHERE id = :duel_id
              AND qalib_id IS NULL
              AND bitdi = 0
        ");

        $stmt_timeout_action->execute([
            ':qalib_id'     => $timeout_qalib_id,
            ':bitdi_qalib'  => $timeout_qalib_id,
            ':bitdi_meglub' => $timeout_novbe_id,
            ':duel_id'      => $duel_id
        ]);

        header(
            "Location: fight.php?duel_id=" . (int)$duel_id
        );
        exit;
    }


    }
    /* -----------------------------------------------------
       DUEL QÆBUL EDÄ°LÄ°B?
    ----------------------------------------------------- */

    if ((int)$duel_action['qebul_edildi'] !== 1) {
        exit('Duel hÉ™lÉ™ qÉ™bul edilmÉ™yib.');
    }

/* -----------------------------------------------------
   VAXT BİTİBMİ?
----------------------------------------------------- */



if ((int)$duel_action['bitdi'] === 1) {


$duel_bitib = true;

if ((int)$duel_action['qalib_id'] === 0) {

    $qalib_mesaji = 'Duel heç-heçə başa çatdı.';

} elseif ((int)$duel_action['qalib_id'] === $my_id) {

    $qalib_mesaji = 'Siz qalib gəldiniz!';

} else {

    $qalib_mesaji = 'Rəqib qalib gəldi!';
}

/*
 * DUEL ARTIQ BİTİB.
 *
 * Aşağıdakı zərbə, müdafiə və raund
 * hesablamalarına keçmək olmaz.
 *
 * Ona görə POST-dan gələn istifadəçini
 * sadəcə nəticə səhifəsinə göndəririk.
 */
header(
    "Location: fight.php?duel_id="
    . (int)$duel_id
);

exit;


} else {


$duel_bitib = false;
$qalib_mesaji = '';


}




    /* -----------------------------------------------------
       MÆN KÄ°MÆM?
    ----------------------------------------------------- */

    $men_player_id = $my_id;

  if ($my_id === (int)$duel_action['oyuncu1_id']) {

    $reqib_id = (int)$duel_action['oyuncu2_id'];

    $menim_parametrlerim = $player1_yekun;
    $reqib_parametrleri = $player2_yekun;

    $menim_can = (int)$duel_action['oyuncu1_can'];
    $reqib_can = (int)$duel_action['oyuncu2_can'];

    $menim_mudafie_column = 'oyuncu1_mudafie';
    $menim_hucum_column   = 'oyuncu1_hucum';

    $reqib_mudafie_column = 'oyuncu2_mudafie';
    $reqib_hucum_column   = 'oyuncu2_hucum';

} else {

    $reqib_id = (int)$duel_action['oyuncu1_id'];

    $menim_parametrlerim = $player2_yekun;
    $reqib_parametrleri = $player1_yekun;

    $menim_can = (int)$duel_action['oyuncu2_can'];
    $reqib_can = (int)$duel_action['oyuncu1_can'];

    $menim_mudafie_column = 'oyuncu2_mudafie';
    $menim_hucum_column   = 'oyuncu2_hucum';

    $reqib_mudafie_column = 'oyuncu1_mudafie';
    $reqib_hucum_column   = 'oyuncu1_hucum';
}




/* -----------------------------------------------------
   İLK CAN DƏYƏRLƏRİ BOŞDURSA DOLDUR
----------------------------------------------------- */

/*
 * İlk raundda duel cədvəlində canlar hələ 0 ola bilər.
 * Bu halda hesablamanı 0-dan yox,
 * oyunçuların real yekun can parametrindən başlamalıyıq.
 */

$oyuncu1_can_baslangic = (int)$duel_action['oyuncu1_can'];
$oyuncu2_can_baslangic = (int)$duel_action['oyuncu2_can'];

if (
    (int)$duel_action['raund'] === 0 &&
    $oyuncu1_can_baslangic <= 0 &&
    $oyuncu2_can_baslangic <= 0
) {

    $oyuncu1_can_baslangic =
        (int)$player1_yekun['can'];

    $oyuncu2_can_baslangic =
        (int)$player2_yekun['can'];
}



    /* -----------------------------------------------------
       RÆQÄ°BÄ°N MÃœDAFÄ°ÆSÄ°NÄ° ÆVVÆLCÆ SAXLAYIRIQ
    ----------------------------------------------------- */

    $reqib_mudafie_secilen =
        $duel_action[$reqib_mudafie_column];

/* -----------------------------------------------------
   RÆQÄ°BÄ°N ZÆRBÆ VURUB-VURMADIÄINI YOXLAYIRIQ
----------------------------------------------------- */

$reqib_hucum = $duel_action[$reqib_hucum_column];
/* -----------------------------------------------------
   RÆQÄ°B HÆLÆ ZÆRBÆ VURMAYIBSA
----------------------------------------------------- */



/*
 * RÉ™qib hÉ™lÉ™ zÉ™rbÉ™ vurmayÄ±bsa:
 * mÉ™nim zÉ™rbÉ™mi sadÉ™cÉ™ yadda saxla,
 * zÉ™rÉ™r hesablamadan gÃ¶zlÉ™mÉ™ sÉ™hifÉ™sinÉ™ keÃ§.
 */



$menim_hucum_mevcud = $duel_action[$menim_hucum_column];


/* -----------------------------------------------------
   MƏN ARTİQ BU RAUNDA ZƏRBƏ VURMUŞAMSA
----------------------------------------------------- */

$menim_hucum_mevcud = $duel_action[$menim_hucum_column];

/*
 * Mən artıq vurmuşamsa:
 *
 * - rəqib də vurubsa → aşağıdakı hesablamaya keçirik
 * - rəqib hələ vurmayıbsa → gözləyirik
 */

if ($menim_hucum_mevcud !== null) {

    if ($reqib_hucum === null) {

        header(
            "Location: fight.php?duel_id="
            . (int)$duel_id
            . "&lis=1"
        );

        exit;
    }

    /*
     * Burada artıq EXIT ETMİRİK.
     *
     * Hər iki tərəfin zərbəsi mövcuddur.
     * Ona görə kod aşağıdakı zərər hesablamasına
     * davam etməlidir.
     */
}




/* -----------------------------------------------------
   RÆQÄ°B HÆLÆ ZÆRBÆ VURMAYIBSA
----------------------------------------------------- */

if ($reqib_hucum === null) {

    /*
     * MÉ™n birinci vuran tÉ™rÉ™fÉ™m.
     * SadÉ™cÉ™ Ã¶z zÉ™rbÉ™mi yadda saxla.
     */


$stmt_save = $pdo->prepare("
    UPDATE duel
    SET
        {$menim_hucum_column} = :hucum,
        {$menim_mudafie_column} = :mudafie,

        " . (
            $my_id === $player1_id
                ? "oyuncu1_hucum_vaxti"
                : "oyuncu2_hucum_vaxti"
        ) . " = :hucum_vaxti,

        son_hucum_eden = :son_hucum_eden,
        novbe_id = :novbe_id

    WHERE id = :duel_id
      AND qalib_id IS NULL
      AND bitdi = 0
      AND {$menim_hucum_column} IS NULL
");
$stmt_save->execute([
    ':hucum'          => $hucum,
    ':mudafie'        => $mudafie,
    ':hucum_vaxti'    => time(),
    ':son_hucum_eden' => $my_id,
    ':novbe_id'       => $reqib_id,
    ':duel_id'        => $duel_id
]);
/*
 * Birinci vuran gözləyir.
 */
header( 
    "Location: fight.php?duel_id=" 
    . (int)$duel_id 
    . "&lis=1" 
);

exit;
}

/* -----------------------------------------------------
   BURAYA YALNIZ RƏQİBİN ZƏRBƏSİ ARTİQ MÖVCUDDURSA
   GƏLİRİK.
   
   YƏNİ:
   mənim zərbəm + rəqibin zərbəsi = hesabla
----------------------------------------------------- */

/* -----------------------------------------------------
   İKİNCİ OYUNÇUNUN SEÇİMİNİ DB-YƏ YAZ
----------------------------------------------------- */

$stmt_save_second = $pdo->prepare("
    UPDATE duel
    SET
        {$menim_hucum_column} = :hucum,
        {$menim_mudafie_column} = :mudafie,

        " . (
            $my_id === $player1_id
                ? "oyuncu1_hucum_vaxti"
                : "oyuncu2_hucum_vaxti"
        ) . " = :hucum_vaxti

    WHERE id = :duel_id
      AND qalib_id IS NULL
      AND bitdi = 0
      AND {$menim_hucum_column} IS NULL
");

$stmt_save_second->execute([
    ':hucum'       => $hucum,
    ':mudafie'     => $mudafie,
    ':hucum_vaxti' => time(),
    ':duel_id'     => $duel_id
]);

/* -----------------------------------------------------
   CARI OYUNÇUNUN SEÇİMİNİ YADDAŞDA YENİLƏ
----------------------------------------------------- */

if ($my_id === (int)$duel_action['oyuncu1_id']) {

    $duel_action['oyuncu1_hucum'] = $hucum;
    $duel_action['oyuncu1_mudafie'] = $mudafie;

} else {

    $duel_action['oyuncu2_hucum'] = $hucum;
    $duel_action['oyuncu2_mudafie'] = $mudafie;
}


/* -----------------------------------------------------
   Ä°KÄ° OYUNÃ‡UNUN ZÆRBÆLÆRÄ°NÄ° HESABLA
----------------------------------------------------- */


/* =====================================================
   OYUNÃ‡U 1 â†’ OYUNÃ‡U 2
===================================================== */
/* =====================================================
   MÃœDAFÄ°Æ YOXLAMASI
===================================================== */

/*
 * MÃ¼dafiÉ™ seÃ§imlÉ™ri:
 *
 * 0 = BaÅŸ vÉ™ SinÉ™
 * 1 = SinÉ™ vÉ™ GÃ¶vdÉ™
 * 2 = GÃ¶vdÉ™ vÉ™ Ayaq
 * 3 = Ayaq vÉ™ BaÅŸ
 */

function mudafieYeriTutulur($hucum, $mudafie)
{
    $mudafie_yerleri = [
        0 => [0, 1], // BaÅŸ + SinÉ™
        1 => [1, 2], // SinÉ™ + GÃ¶vdÉ™
        2 => [2, 3], // GÃ¶vdÉ™ + Ayaq
        3 => [3, 0]  // Ayaq + BaÅŸ
    ];

    if (!isset($mudafie_yerleri[(int)$mudafie])) {
        return false;
    }

    return in_array(
        (int)$hucum,
        $mudafie_yerleri[(int)$mudafie],
        true
    );
}

$oyuncu1_krit_faizi = kritFaiziHesabla(
    $player1_yekun['krit'],
    $player2_yekun['anti_krit']
);

$oyuncu2_uvorot_faizi = uvorotFaiziHesabla(
    $player2_yekun['uvorot'],
    $player1_yekun['anti_uvorot']
);


/* UVOROT */

$oyuncu2_uvorot_oldumu = uvorotOldu(
    $oyuncu2_uvorot_faizi
);


/* BaÅŸlanÄŸÄ±c dÉ™yÉ™rlÉ™ri */

$oyuncu1_krit_oldumu = false;
$oyuncu1_zarar = 0;


/* =====================================================
   OYUNÃ‡U 2 MÃœDAFÄ°Æ EDÄ°BSÆ
===================================================== */

$oyuncu2_mudafie_tutdu =
    mudafieYeriTutulur(
        $duel_action['oyuncu1_hucum'],
        $duel_action['oyuncu2_mudafie']
    );


/* =====================================================
   MÃœDAFÄ°Æ TUTUBSA
===================================================== */

if ($oyuncu2_mudafie_tutdu) {

    $oyuncu1_zarar = 0;

} elseif ($oyuncu2_uvorot_oldumu) {

    $oyuncu1_zarar = 0;

} else {

    /* -------------------------------------------------
       KRÄ°TÄ°K YOXLAMASI
    ------------------------------------------------- */

    $oyuncu1_krit_oldumu = kritOldu(
        $oyuncu1_krit_faizi
    );


    /* -------------------------------------------------
       KRÄ°TÄ°K VÆ YA ADÄ° ZÆRBÆ
    ------------------------------------------------- */

    if ($oyuncu1_krit_oldumu) {

        $oyuncu1_zarar = kritZerbeHesabla(
            $player1_yekun['min_zerbe'],
            $player1_yekun['max_zerbe'],
            $player2_yekun['mudafie']
        );

    } else {

        $oyuncu1_zarar = zerbeHesabla(
            $player1_yekun['min_zerbe'],
            $player1_yekun['max_zerbe'],
            $player2_yekun['mudafie']
        );
    }
}



/* =====================================================
   OYUNÃ‡U 2 â†’ OYUNÃ‡U 1
===================================================== */

$oyuncu2_krit_faizi = kritFaiziHesabla(
    $player2_yekun['krit'],
    $player1_yekun['anti_krit']
);

$oyuncu1_uvorot_faizi = uvorotFaiziHesabla(
    $player1_yekun['uvorot'],
    $player2_yekun['anti_uvorot']
);


/* UVOROT */

$oyuncu1_uvorot_oldumu = uvorotOldu(
    $oyuncu1_uvorot_faizi
);


/* BaÅŸlanÄŸÄ±c dÉ™yÉ™rlÉ™ri */

$oyuncu2_krit_oldumu = false;
$oyuncu2_zarar = 0;


/* =====================================================
   OYUNÃ‡U 1 MÃœDAFÄ°Æ EDÄ°BSÆ
===================================================== */

$oyuncu1_mudafie_tutdu =
    mudafieYeriTutulur(
        $duel_action['oyuncu2_hucum'],
        $duel_action['oyuncu1_mudafie']
    );


/* =====================================================
   MÃœDAFÄ°Æ TUTUBSA
===================================================== */

if ($oyuncu1_mudafie_tutdu) {

    $oyuncu2_zarar = 0;

} elseif ($oyuncu1_uvorot_oldumu) {

    $oyuncu2_zarar = 0;

} else {

    /* -------------------------------------------------
       KRÄ°TÄ°K YOXLAMASI
    ------------------------------------------------- */

    $oyuncu2_krit_oldumu = kritOldu(
        $oyuncu2_krit_faizi
    );


    /* -------------------------------------------------
       KRÄ°TÄ°K VÆ YA ADÄ° ZÆRBÆ
    ------------------------------------------------- */

    if ($oyuncu2_krit_oldumu) {

        $oyuncu2_zarar = kritZerbeHesabla(
            $player2_yekun['min_zerbe'],
            $player2_yekun['max_zerbe'],
            $player1_yekun['mudafie']
        );

    } else {

        $oyuncu2_zarar = zerbeHesabla(
            $player2_yekun['min_zerbe'],
            $player2_yekun['max_zerbe'],
            $player1_yekun['mudafie']
        );
    }
}


/* =====================================================
   XÆTA / UVOROT NÆTÄ°CÆLÆRÄ°
===================================================== */

$oyuncu1_xeta = (
    $oyuncu2_uvorot_oldumu &&
    !$oyuncu2_mudafie_tutdu
) ? 1 : 0;

$oyuncu2_xeta = (
    $oyuncu1_uvorot_oldumu &&
    !$oyuncu1_mudafie_tutdu
) ? 1 : 0;

/* =====================================================
   CANLARI YENİLƏ
===================================================== */

/*
 * Oyunçu 1-in aldığı zərər
 * Oyunçu 2-nin vurduğu zərərdir.
 */

$oyuncu1_can_yeni =
    $oyuncu1_can_baslangic - $oyuncu2_zarar;


/*
 * Oyunçu 2-nin aldığı zərər
 * Oyunçu 1-in vurduğu zərərdir.
 */

$oyuncu2_can_yeni =
    $oyuncu2_can_baslangic - $oyuncu1_zarar;


/* Mənfi cana icazə vermirik */

$oyuncu1_can_yeni = max(0, $oyuncu1_can_yeni);
$oyuncu2_can_yeni = max(0, $oyuncu2_can_yeni);


/* =====================================================
   CAN 0 → DUEL BİTİR
===================================================== */

$qalib_id = null;
$bitdi = 0;
/* =====================================================
   SON HÜCUM EDƏNİ MÜƏYYƏN ET
===================================================== */

if ($my_id === $player1_id) {
    $son_hucum_eden = $player1_id;
} else {
    $son_hucum_eden = $player2_id;
}

/*
 * Oyunçu 1-in canı 0 oldu
 * Oyunçu 2 qalibdir
 */

if (
    $oyuncu1_can_yeni <= 0 &&
    $oyuncu2_can_yeni > 0
) {

    $qalib_id = $player2_id;
    $bitdi = 1;
}


/*
 * Oyunçu 2-nin canı 0 oldu
 * Oyunçu 1 qalibdir
 */

elseif (
    $oyuncu2_can_yeni <= 0 &&
    $oyuncu1_can_yeni > 0
) {

    $qalib_id = $player1_id;
    $bitdi = 1;
}


/*
 * Hər ikisinin canı 0 oldu
 */


elseif (
    $oyuncu1_can_yeni <= 0 &&
    $oyuncu2_can_yeni <= 0
) {

    // Hər iki oyunçunun canı 0-dırsa — HEÇ-HEÇƏ
    $qalib_id = 0;
    $bitdi = 1;
}


/* =====================================================
   NÃ–VBÆ
===================================================== */

$novbe_yeni = null;

if ($qalib_id === null) {

    if ($son_hucum_eden === $player1_id) {

        $novbe_yeni = $player2_id;

    } else {

        $novbe_yeni = $player1_id;
    }
}


/* =====================================================
   SON NÆTÄ°CÆLÆR
===================================================== */

/*
 * OyunÃ§u 1-in son zÉ™rbÉ™si
 */

$son_oyuncu1_zarar = (int)$oyuncu1_zarar;
$son_oyuncu1_kritik = $oyuncu1_krit_oldumu ? 1 : 0;
$son_oyuncu1_xeta = $oyuncu1_xeta;


/*
 * OyunÃ§u 2-nin son zÉ™rbÉ™si
 */

$son_oyuncu2_zarar = (int)$oyuncu2_zarar;
$son_oyuncu2_kritik = $oyuncu2_krit_oldumu ? 1 : 0;
$son_oyuncu2_xeta = $oyuncu2_xeta;


/* =====================================================
 Kim Hucum edib?
===================================================== */

/*
 * Son zÉ™rbÉ™ni vuran tÉ™rÉ™f.
 * HazÄ±rkÄ± request-dÉ™ mÉ™nim seÃ§diyim hÃ¼cumdur.
 */





/* =====================================================
   KÃ–HNÆ SON NÆTÄ°CÆ DÆYÄ°ÅÆNLÆRÄ°
===================================================== */

/*
 * KÃ¶hnÉ™ HTML hissÉ™sindÉ™ istifadÉ™ etdiyimiz
 * dÉ™yiÅŸÉ™nlÉ™ri dÉ™ saxlayÄ±rÄ±q.
 */

$zarar = (
    $my_id === $player1_id
        ? $oyuncu1_zarar
        : $oyuncu2_zarar
);

$krit_oldumu = (
    $my_id === $player1_id
        ? $oyuncu1_krit_oldumu
        : $oyuncu2_krit_oldumu
);

$uvorot_oldumu = (
    $my_id === $player1_id
        ? $oyuncu2_uvorot_oldumu
        : $oyuncu1_uvorot_oldumu
);


/* -----------------------------------------------------
   DB-YÆ YAZ
----------------------------------------------------- */

$stmt_update = $pdo->prepare("
    UPDATE duel
    SET
        oyuncu1_can = :oyuncu1_can,
        oyuncu2_can = :oyuncu2_can,

        oyuncu1_hucum = NULL,
        oyuncu2_hucum = NULL,

        oyuncu1_mudafie = NULL,
        oyuncu2_mudafie = NULL,

        son_oyuncu1_hucum = :son_oyuncu1_hucum,
        son_oyuncu2_hucum = :son_oyuncu2_hucum,

        son_oyuncu1_zarar = :son_oyuncu1_zarar,
        son_oyuncu2_zarar = :son_oyuncu2_zarar,

        son_oyuncu1_kritik = :son_oyuncu1_kritik,
        son_oyuncu2_kritik = :son_oyuncu2_kritik,

        son_oyuncu1_xeta = :son_oyuncu1_xeta,
        son_oyuncu2_xeta = :son_oyuncu2_xeta,

        son_hucum_eden = :hucum_eden,
        son_mudafie_eden = :mudafie_eden,

        son_zarar = :son_zarar,
        son_kritik = :son_kritik,
        son_xeta = :son_xeta,
        son_mudafie = :son_mudafie,

   qalib_id = :qalib_id,
bitdi = :bitdi,
novbe_id = :novbe_id,
        raund = raund + 1,

        novbe_baslama_tarixi = NOW()

  WHERE id = :duel_id
  AND qalib_id IS NULL
  AND bitdi = 0
");



$stmt_update->execute([

    ':oyuncu1_can' => $oyuncu1_can_yeni,
    ':oyuncu2_can' => $oyuncu2_can_yeni,

    ':son_oyuncu1_hucum' =>
        $my_id === (int)$duel_action['oyuncu1_id']
            ? $hucum
            :$duel_action['oyuncu1_hucum'],

    ':son_oyuncu2_hucum' =>
        $my_id === (int)$duel_action['oyuncu2_id']
            ? $hucum
            :$duel_action['oyuncu2_hucum'],


    /* OYUNÃ‡U 1 NÆTÄ°CÆSÄ° */
    ':son_oyuncu1_zarar' => $son_oyuncu1_zarar,
    ':son_oyuncu1_kritik' => $son_oyuncu1_kritik,
    ':son_oyuncu1_xeta' => $son_oyuncu1_xeta,


    /* OYUNÃ‡U 2 NÆTÄ°CÆSÄ° */
    ':son_oyuncu2_zarar' => $son_oyuncu2_zarar,
    ':son_oyuncu2_kritik' => $son_oyuncu2_kritik,
    ':son_oyuncu2_xeta' => $son_oyuncu2_xeta,


    ':hucum_eden' => $son_hucum_eden,
':mudafie_eden' => $reqib_id,


    ':son_zarar' => $zarar,
    ':son_kritik' => $krit_oldumu ? 1 : 0,
    ':son_xeta' => $uvorot_oldumu ? 1 : 0,

    ':son_mudafie' =>
        $reqib_mudafie_secilen !== null
            ? (int)$reqib_mudafie_secilen
            : 0,

 ':qalib_id' => $qalib_id,
':bitdi' => $bitdi,
':novbe_id' => $novbe_yeni,
    ':duel_id' => $duel_id
]);


/* =====================================================
   DÖYÜŞ STATİSTİKALARINI VƏ RANKI YENİLƏ
===================================================== */

if ($qalib_id !== null) {

    /* =================================================
       STATİSTİKA SƏTİRLƏRİNİ YARAT
       Əgər istifadəçinin sətri yoxdursa yaradılır
    ================================================= */

    $stmt_stat_create = $pdo->prepare("
        INSERT INTO oyuncu_doyus_statistikasi
        (
            user_id,
            qelebeler,
            meglubiyyetler,
            hec_heceler,
            cem_doyusler,
            rank
        )
        VALUES
        (
            :user_id,
            0,
            0,
            0,
            0,
            0
        )
        ON DUPLICATE KEY UPDATE
            user_id = user_id
    ");

    $stmt_stat_create->execute([
        ':user_id' => (int)$player1_id
    ]);

    $stmt_stat_create->execute([
        ':user_id' => (int)$player2_id
    ]);


    /* =================================================
       CƏMİ DÖYÜŞLƏR
       Hər iki oyunçu üçün +1
    ================================================= */

    $stmt_stat = $pdo->prepare("
        UPDATE oyuncu_doyus_statistikasi
        SET cem_doyusler = COALESCE(cem_doyusler, 0) + 1
        WHERE user_id = :user_id
    ");

    $stmt_stat->execute([
        ':user_id' => (int)$player1_id
    ]);

    $stmt_stat->execute([
        ':user_id' => (int)$player2_id
    ]);


    /* =================================================
       HEÇ-HEÇƏ
       Hər iki oyunçu üçün +1
    ================================================= */

    if ((int)$qalib_id === 0) {

        $stmt_hec_hece_stat = $pdo->prepare("
            UPDATE oyuncu_doyus_statistikasi
            SET hec_heceler = COALESCE(hec_heceler, 0) + 1
            WHERE user_id = :user_id
        ");

        $stmt_hec_hece_stat->execute([
            ':user_id' => (int)$player1_id
        ]);

        $stmt_hec_hece_stat->execute([
            ':user_id' => (int)$player2_id
        ]);

    }


    /* =================================================
       QƏLƏBƏ / MƏĞLUBİYYƏT
    ================================================= */

    else {

        $qalib_id_int = (int)$qalib_id;

        $meglub_id = (
            $qalib_id_int === (int)$player1_id
                ? (int)$player2_id
                : (int)$player1_id
        );


        /* =================================================
           QALİB +1
        ================================================= */

        $stmt_qalib_stat = $pdo->prepare("
            UPDATE oyuncu_doyus_statistikasi
            SET qelebeler = COALESCE(qelebeler, 0) + 1
            WHERE user_id = :user_id
        ");

        $stmt_qalib_stat->execute([
            ':user_id' => $qalib_id_int
        ]);


        /* =================================================
           MƏĞLUB +1
        ================================================= */

        $stmt_meglub_stat = $pdo->prepare("
            UPDATE oyuncu_doyus_statistikasi
            SET meglubiyyetler = COALESCE(meglubiyyetler, 0) + 1
            WHERE user_id = :user_id
        ");

        $stmt_meglub_stat->execute([
            ':user_id' => $meglub_id
        ]);


        /* =================================================
           RANK MÜKAFATI
        ================================================= */

        $qalib_movqe = null;
        $meglub_movqe = null;
        $qalib_seviyye = 0;
        $meglub_seviyye = 0;


        if ($qalib_id_int === (int)$player1_id) {

            $qalib_movqe = $player1['movqe'] ?? null;
            $meglub_movqe = $player2['movqe'] ?? null;

            $qalib_seviyye =
                (int)($player1['oyuncunun_seviyyesi'] ?? 0);

            $meglub_seviyye =
                (int)($player2['oyuncunun_seviyyesi'] ?? 0);

        } else {

            $qalib_movqe = $player2['movqe'] ?? null;
            $meglub_movqe = $player1['movqe'] ?? null;

            $qalib_seviyye =
                (int)($player2['oyuncunun_seviyyesi'] ?? 0);

            $meglub_seviyye =
                (int)($player1['oyuncunun_seviyyesi'] ?? 0);
        }


        /* =================================================
           RANK QAYDASI

           Rəqib yüksək səviyyədədirsə = +5
           Eyni mövqe = +1
           Fərqli mövqe = +4
        ================================================= */

        if ($meglub_seviyye > $qalib_seviyye) {

            $rank_artimi = 5;

        } elseif (
            $qalib_movqe !== null &&
            $meglub_movqe !== null &&
            (int)$qalib_movqe === (int)$meglub_movqe
        ) {

            $rank_artimi = 1;

        } else {

            $rank_artimi = 4;
        }


        /* =================================================
           QALİBİN RANKINI DB-YƏ YAZ
        ================================================= */

        $stmt_rank = $pdo->prepare("
            UPDATE oyuncu_doyus_statistikasi
            SET rank = COALESCE(rank, 0) + :rank_artimi
            WHERE user_id = :user_id
        ");

        $stmt_rank->execute([
            ':rank_artimi' => (int)$rank_artimi,
            ':user_id' => $qalib_id_int
        ]);
    }
}

/* =====================================================
   DUEL GEDİŞATINA YAZ
===================================================== */

$stmt_gedisat = $pdo->prepare("
    INSERT INTO duel_gedisati
    (
        duel_id,
        raund,
        zerbe_eden_id,
        zerbe_yeri,
        zerbe_zarari,
        kritik,
        xeta,
        mudafie
    )
    VALUES
    (
        :duel_id,
        :raund,
        :zerbe_eden_id,
        :zerbe_yeri,
        :zerbe_zarari,
        :kritik,
        :xeta,
        :mudafie
    )
");

/* OYUNÇU 1-IN ZƏRBƏSİ */
$stmt_gedisat->execute([
    ':duel_id'       => $duel_id,
    ':raund'         => (int)$duel_action['raund'] + 1,
    ':zerbe_eden_id' => $player1_id,
':zerbe_yeri' =>
    ($player1_id === $my_id)
        ? (int)$hucum
        : (int)$duel_action['oyuncu1_hucum'],
    ':zerbe_zarari'  => (int)$son_oyuncu1_zarar,
    ':kritik'        => (int)$son_oyuncu1_kritik,
    ':xeta'          => (int)$son_oyuncu1_xeta,
    ':mudafie'       => (int)$duel_action['oyuncu2_mudafie']
]);

/* OYUNÇU 2-NİN ZƏRBƏSİ */
$stmt_gedisat->execute([
    ':duel_id'       => $duel_id,
    ':raund'         => (int)$duel_action['raund'] + 1,
    ':zerbe_eden_id' => $player2_id,
':zerbe_yeri' =>
    ($player2_id === $my_id)
        ? (int)$hucum
        : (int)$duel_action['oyuncu2_hucum'],
    ':zerbe_zarari'  => (int)$son_oyuncu2_zarar,
    ':kritik'        => (int)$son_oyuncu2_kritik,
    ':xeta'          => (int)$son_oyuncu2_xeta,
    ':mudafie'       => (int)$duel_action['oyuncu1_mudafie']
]);

/* ----------------------------------------------------- 
   NÆTÄ°CÆYÆ GÃ–RÆ SÆHÄ°FÆ 
----------------------------------------------------- */ 

if ($qalib_id !== null) {
    header(
        "Location: fight.php?duel_id="
        . (int)$duel_id
    );
} else {
    header(
        "Location: fight.php?duel_id="
        . (int)$duel_id
        . "&ok=1"
    );
}

exit;
}

/* =========================================================
   DAXÄ°L OLMUÅ Ä°STÄ°FADÆÃ‡Ä°NÄ°N ADI
========================================================= */

$user_login = '';

$stmt_user_login = $pdo->prepare("
    SELECT login
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_user_login->execute([
    ':id' => $my_id
]);

$current_user = $stmt_user_login->fetch(PDO::FETCH_ASSOC);

if ($current_user) {
    $user_login = $current_user['login'];
}


/* =========================================================
   ONLINE VAXTIN YENÄ°LÆNÄ°R
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

$stmt_online_users = $pdo->prepare("
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

$stmt_online_users->execute([
    ':vaxt' => time() - 180
]);

$users = $stmt_online_users->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN"
    "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="az" lang="az">

<head>

<meta name="robots" content="ALL">

<meta
    name="keywords"
    content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri, qorxulu qalalar"
>
<meta charset="UTF-8">

<meta
    name="description"
    content="Azerbaycanda ilk Mobil Online oyunu. ĞŸĞµÑ€Ğ²Ñ‹Ğ¹ Ğ¼Ğ¾Ğ±Ğ¸Ğ»ÑŒĞ½Ñ‹Ğ¹ Ğ¾Ğ½Ğ»Ğ°Ğ¹Ğ½-Ğ¸Ğ³Ñ€Ñ‹"
>

<link rel="stylesheet" href="css.css">

<meta
    content="text/html; charset=utf-8"
    http-equiv="content-type"
>

<meta
    name="viewport"
    content="width=device-width; initial-scale=1.0; maximum-scale=3.0"
>

<title>duel</title>

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

   <?php if (basename($_SERVER['PHP_SELF']) === 'fight.php'): ?>

    <img 
        alt="macera.az" 
        id="logo" 
        src="img/logo.png"
    >

<?php else: ?>

    <a href="menu.php">
        <img 
            alt="macera.az" 
            id="logo" 
            src="img/logo.png"
        >
    </a>

<?php endif; ?>
    <div class="icons"></div>


    <div class="main_foot">

        <div class="grey">

            <img
                src="img/coin.png"
                title="qızıl"
                alt=""
            >

            <?php echo number_format((int)$user['qızıl'], 0, '', ' '); ?>


            <img
                src="img/brill.png"
                title="Brilliant"
                alt=""
            >

            <?php echo number_format((int)$user['brılyant'], 0, '', ' '); ?>


            <img
                src="img/energy.png"
                title="Enerji"
                alt=""
            >

            <?php echo number_format((int)$user['enerjı'], 0, '', ' '); ?>


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


            <?php if (isset($dostluq_sayi) && $dostluq_sayi > 0): ?>

                <a href="dostlar.php">

                    <img
                        src="muxtelif/dost_pilus.png"
                        title="Dost"
                        alt="Dost"
                    >

                </a>

                (<?php echo (int)$dostluq_sayi; ?>)

            <?php endif; ?>

        </div>

    </div>

</div>


<div class="space"></div>


<div
    style="background:none repeat scroll 0 0 #888686;height:1px;"
></div>


<!-- =========================================================
     EXP
========================================================= -->

<div class="fl b exp_count">

    <div style="margin-top:-2px;">

        <span style="color:#ff3333">

            <b>
                <?php echo (int)$progress; ?>%
            </b>

        </span>

    </div>

</div>


<div class="experience">

    <div class="exp_bg">

        <div class="exp_left fl"></div>

        <div class="exp_right fr"></div>


        <div
            style="width:<?php echo number_format((float)$progress, 2, '.', ''); ?>%;height:10px;"
        >

            <div class="exp_line"></div>

            <div class="exp_point"></div>

        </div>

    </div>

</div>

<div
    style="background:none repeat scroll 0 0 #888686;height:1px;"
></div>

<?php

/* =========================================================
   GÃ–ZLÆMÆ / YENÄ°LÆ SÆHÄ°FÆSÄ°
========================================================= */

/* =========================================================
   OYUNÇU ZƏRBƏ VURUBSA VƏ RƏQİB HƏLƏ VURMAYIBSA
   SƏHİFƏ YENİDƏN AÇILDIQDA DA GÖZLƏMƏ SƏHİFƏSİNDƏ SAXLA
========================================================= */

if (
    !isset($_GET['ok']) &&
    !isset($_GET['lis']) &&
    isset($duel)
) {

    $menim_hucum_reload = null;
    $reqib_hucum_reload = null;

    if ($my_id === (int)$duel['oyuncu1_id']) {

        $menim_hucum_reload = $duel['oyuncu1_hucum'];
        $reqib_hucum_reload = $duel['oyuncu2_hucum'];

    } else {

        $menim_hucum_reload = $duel['oyuncu2_hucum'];
        $reqib_hucum_reload = $duel['oyuncu1_hucum'];
    }


    /*
     * Hər iki tərəf zərbə vurmayıbsa,
     * normal döyüş səhifəsi açılsın.
     */


    /*
     * Mən zərbə vurmuşam,
     * rəqib hələ zərbə vurmayıb.
     *
     * Səhifə yenidən açılsa belə
     * avtomatik lis=1 gözləmə səhifəsinə göndəririk.
     */
   if (
    $menim_hucum_reload !== null &&
    $reqib_hucum_reload === null &&
    (int)($duel['bitdi_qalib'] ?? 0) <= 0 &&
    (int)($duel['bitdi_meglub'] ?? 0) <= 0
) {

    header(
        "Location: fight.php?duel_id="
        . (int)$duel_id
        . "&lis=1"
    );

    exit;
}



    /*
     * Mən hələ zərbə vurmamışam,
     * rəqib zərbə vurub.
     *
     * Bu halda da mənim səhifəm
     * normal zərbə seçim səhifəsi olaraq qalır.
     *
     * Yəni burada redirect etmirik.
     */


    /*
     * Hər iki tərəf zərbə vurubsa,
     * normal fight səhifəsi davam edir.
     */
}


if (isset($_GET['lis']) && (int)$_GET['lis'] === 1) {

    $stmt_wait = $pdo->prepare("
        SELECT
            oyuncu1_id,
            oyuncu2_id,
            oyuncu1_hucum,
            oyuncu2_hucum,
            qalib_id,
            bitdi_qalib,
            bitdi_meglub
        FROM duel
        WHERE id = :duel_id
        LIMIT 1
    ");

    $stmt_wait->execute([
        ':duel_id' => $duel_id
    ]);

    $duel_wait = $stmt_wait->fetch(PDO::FETCH_ASSOC);



    if (!$duel_wait) {
        exit('Duel tapÄ±lmadÄ±.');
    }

    /* =====================================================
   VAXT SƏBƏBİ İLƏ DUEL BİTİBSƏ
===================================================== */

if (
    (int)$duel_wait['bitdi_qalib'] > 0 &&
    (int)$duel_wait['bitdi_meglub'] > 0
) {

    /*
     * Nəticə artıq DB-də yazılıb.
     * İlkin duel səhifəsinə qayıtma.
     * Normal fight.php nəticə hissəsinə keç.
     */
    
    header(
        "Location: fight.php?duel_id="
        . (int)$duel_id
    );

    exit;
}

    /* =====================================================
       DUEL BÄ°TÄ°BSÆ
    ===================================================== */

    if (
        $duel_wait['qalib_id'] !== null &&
        (int)$duel_wait['qalib_id'] > 0
    ) {
        header(
            "Location: fight.php?duel_id="
            . (int)$duel_id
        );
        exit;
    }

    /* =====================================================
       MÆNÄ°M VÆ RÆQÄ°BÄ°N ZÆRBÆSÄ°
    ===================================================== */

    if ($my_id === (int)$duel_wait['oyuncu1_id']) {

        $menim_hucum_yoxla = $duel_wait['oyuncu1_hucum'];
        $reqib_hucum_yoxla = $duel_wait['oyuncu2_hucum'];

    } else {

        $menim_hucum_yoxla = $duel_wait['oyuncu2_hucum'];
        $reqib_hucum_yoxla = $duel_wait['oyuncu1_hucum'];
    }

    /* =====================================================
       HÆR Ä°KÄ° TÆRÆF ZÆRBÆ VURUBSA
       HESABLANMIÅ NÆTÄ°CÆYÆ GET
    ===================================================== */

    if (
        $menim_hucum_yoxla !== null &&
        $reqib_hucum_yoxla !== null
    ) {
        header(
            "Location: fight.php?duel_id="
            . (int)$duel_id
        );
        exit;
    }

    /* =====================================================
       MÆN ZÆRBÆ VURMUÅAM, RÆQÄ°B HÆLÆ VURMAYIB
    ===================================================== */

    if (
        $menim_hucum_yoxla !== null &&
        $reqib_hucum_yoxla === null
    ) {
        ?>


<div class="center">


<b>Rəqibi gözləyin...</b>

<br/>

Rəqib
<b>
    <span id="gozlemeTimer">
        <?php echo (int)$qalan_vaxt; ?>
    </span>
</b>
- saniyə ərzində zərbə atmasa məğlub olacaq.

<br/>

<div class="menu">

    <li>
        <a href="fight.php?duel_id=<?php echo (int)$duel_id; ?>&amp;lis=1">
            Yenilə
        </a>
    </li>

    <hr/>

</div>


</div>

<script>
(function () {

    var qalan = <?php echo (int)$qalan_vaxt; ?>;
    var timer = document.getElementById('gozlemeTimer');

    function geriSay() {

        if (qalan <= 0) {

            timer.innerHTML = '0';

            /*
             * 0 olduqda lis=1 istifadə ETMİRİK.
             * Normal fight.php request-i gedir.
             * PHP timeout-u yoxlayır və duel bitibsə
             * nəticə səhifəsini göstərir.
             */
            window.location.href =
                'fight.php?duel_id=<?php echo (int)$duel_id; ?>';

            return;
        }

        timer.innerHTML = qalan;
        qalan--;

        setTimeout(geriSay, 1000);
    }

    geriSay();

})();
</script>
        <?php
        exit;
    }

    /* =====================================================
       ÆGÆR BURA GÆLÄ°BSÆ, MÆNÄ°M ZÆRBÆM YOXDUR
       NORMAL DUEL SÆHÄ°FÆSÄ°NÆ QAYIT
    ===================================================== */

    header(
        "Location: fight.php?duel_id="
        . (int)$duel_id
    );

    exit;
}

?>







<?php if (isset($_GET['ok']) && $_GET['ok'] == 1): ?>

<?php

/* =========================================================
   OK SÆHÄ°FÆSÄ°NDÆ DUEL VÆZÄ°YYÆTÄ°NÄ° YOXLAYIRIQ
========================================================= */

$stmt_ok = $pdo->prepare("
    SELECT
    oyuncu1_id, 
    oyuncu2_id, 
    oyuncu1_hucum, 
    oyuncu2_hucum, 
    qalib_id, 
    bitdi_qalib, 
    bitdi_meglub 
    FROM duel
    WHERE id = :duel_id
    LIMIT 1
");

$stmt_ok->execute([
    ':duel_id' => $duel_id
]);

$duel_ok = $stmt_ok->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   DUEL TAPILMAYIBSA
========================================================= */

if (!$duel_ok) {
    exit('Duel tapÄ±lmadÄ±.');
}




/* =========================================================
   HÆR Ä°KÄ° TÆRÆFÄ°N ZÆRBÆSÄ°NÄ° GÃ–TÃœRÃœRÃœK
========================================================= */

$ok_oyuncu1_hucum = $duel_ok['oyuncu1_hucum'];
$ok_oyuncu2_hucum = $duel_ok['oyuncu2_hucum'];


/* =========================================================
   HÆR Ä°KÄ° TÆRÆF ZÆRBÆ VURUBSA
========================================================= */

if (
    $ok_oyuncu1_hucum !== null &&
    $ok_oyuncu2_hucum !== null
) {

    /*
     * Ä°ki tÉ™rÉ™f dÉ™ zÉ™rbÉ™ vurub.
     * Ä°lkin duel sÉ™hifÉ™sinÉ™ keÃ§.
     */

    header(
        "Location: fight.php?duel_id="
        . (int)$duel_id
    );

    exit;
}


/* =========================================================
   BÄ°R TÆRÆF HÆLÆ ZÆRBÆ VURMAYIB
========================================================= */

/*
 * Burada heÃ§ nÉ™ etmirik.
 *
 * AÅŸaÄŸÄ±dakÄ± [OK] dÃ¼ymÉ™si istifadÉ™Ã§ini
 * lis=1 gÃ¶zlÉ™mÉ™ sÉ™hifÉ™sinÉ™ gÃ¶ndÉ™rÉ™cÉ™k.
 */

?>


<!--
     RÆQÄ°B HÆLÆ ZÆRBÆ VURMAYIB
     Ona gÃ¶rÉ™ OK-dan sonra gÃ¶zlÉ™mÉ™ sÉ™hifÉ™sinÉ™ gedirik.
-->

<div class="center">

    <a
        href="fight.php?duel_id=<?php echo (int)$duel_id; ?>&amp;lis=1"
        style="
            color:#850E0E;
            text-decoration:none;
            display:block;
            padding:6px;
            margin-top:5px;
            margin-bottom:5px;
            font-weight:bold;
            text-align:center;
        "
    >[OK]</a>

</div>


<?php endif; ?>




<!-- =========================================================
     DUEL
========================================================= -->
<?php if (!isset($_GET['ok']) && !isset($_GET['lis'])): ?>

<?php 
/* =========================================================
   HEÇ-HEÇƏ NƏTİCƏSİ
   Yalnız həqiqətən hər iki tərəf 0 can deyilsə yox,
   xüsusi hec_hece=1 nəticəsində göstərilir.
========================================================= */

if (
    isset($_GET['hec_hece']) &&
    (int)$_GET['hec_hece'] === 1 &&
    (int)$duel['qalib_id'] === 0 &&
    (int)$duel['oyuncu1_can'] > 0 &&
    (int)$duel['oyuncu2_can'] > 0
) {
?>
<br>

<div class="center">

    <div class="block_line">
        <span class="green">
            Döyüş heç - heçe oldu,her biriniz 3 deq erzinde zerbe atmadınız!
        </span>
    </div>

    <br>


</div>



<u>Əldə etdiniz:</u>

<br>

<b>Tecrübe:</b> 0

<br>

<b>Rang:</b> 0

<br><br>

<a href="log_izle.php?go=izle&amp;duel_id=<?php echo (int)$duel_id; ?>">
    Oyunun Gedişatını izle
</a>


<br>

<a href="online.php">
    Online döyüşçülər
</a>

|

<a href="menu.php">
    Ana sehife
</a>

<br>

<?php
    exit;
}
?>
<?php

/* =========================================================
   CANA GÖRƏ QALİB / MƏĞLUB
========================================================= */

$oyuncu1_can = (int)$duel['oyuncu1_can'];
$oyuncu2_can = (int)$duel['oyuncu2_can'];

$duel_bitib = false;
$qalib_id = 0;
/* =========================================================
   CAN 0 - HEÇ-HEÇƏ
   Hər iki tərəfin canı eyni anda 0 olduqda
========================================================= */

$can0_hechece = (
    $oyuncu1_can <= 0 &&
    $oyuncu2_can <= 0
);

/* OYUNCU 1 ÖLDÜ */
if ($oyuncu1_can <= 0 && $oyuncu2_can > 0) {

    $duel_bitib = true;
    $qalib_id = (int)$duel['oyuncu2_id'];

}


/* OYUNCU 2 ÖLDÜ */
elseif ($oyuncu2_can <= 0 && $oyuncu1_can > 0) {

    $duel_bitib = true;
    $qalib_id = (int)$duel['oyuncu1_id'];

}


/* HƏR İKİSİ 0-DIR */
elseif ($oyuncu1_can <= 0 && $oyuncu2_can <= 0) {

    $duel_bitib = true;
    $qalib_id = 0;

}

?>

<?php
/* =========================================================
   VAXT SƏBƏBİ İLƏ DUEL BİTİB?
========================================================= */

$bitdi_qalib = (int)($duel['bitdi_qalib'] ?? 0);
$bitdi_meglub = (int)($duel['bitdi_meglub'] ?? 0);
?>


<?php if ($bitdi_qalib > 0 || $bitdi_meglub > 0): ?>

    <?php if ($bitdi_qalib === $my_id): ?>

        <br>

        <div class="center">

            <div class="block_line">
                <span class="green">
                    Siz Qalib geldiz, Reqib 3 deq erzinde zerbe atmayaraq meglub oldu!
                </span>
            </div>

        </div>

        <br>

        Elde etdiniz:<br>
        Tecrübe: +1 <br>
        <?php
/* =====================================================
   RANK MÜKAFATI
===================================================== */

$qalib_movqe = null;
$reqib_movqe = null;

if ($my_id === (int)$duel['oyuncu1_id']) {
    $qalib_movqe = $player1['movqe'] ?? null;
    $reqib_movqe = $player2['movqe'] ?? null;
} else {
    $qalib_movqe = $player2['movqe'] ?? null;
    $reqib_movqe = $player1['movqe'] ?? null;
}

$verilecek_rank = (
    $qalib_movqe === $reqib_movqe
        ? 1
        : 4
);
?>
        Rank: +<?php echo (int)$verilecek_rank; ?> <br>

        <br>

         <a href="log_izle.php?go=izle&amp;duel_id=<?php echo (int)$duel_id; ?>">
        Oyunun Gedişatını izle
    </a>

        <br>

        <a href="online.php?">
            Online döyüşçülər
        </a>

        |

        <a href="menu.php?">
            Ana sehife
        </a>

        <br>


    <?php elseif ($bitdi_meglub === $my_id): ?>

        <br>

        <div class="center">

            <div class="block_line">
                <span class="red">
                    Siz Meglub Oldunuz, Reqibe 3 deq erzinde zerbe atmadınız!
                </span>
            </div>

        </div>

        <br>

        Elde etdiniz:<br>
        Tecrübe: 0<br>
        Rang: 0 <br>

        <br>

         <a href="log_izle.php?go=izle&amp;duel_id=<?php echo (int)$duel_id; ?>">
        Oyunun Gedişatını izle
    </a>

        <br>

        <a href="online.php?">
            Online döyüşçülər
        </a>

        |

        <a href="menu.php?">
            Ana sehife
        </a>

        <br>

    <?php endif; ?>

<?php elseif ($duel_bitib): ?>

<?php
/* =========================================================
   CANLARIN HƏR İKİSİ 0 OLUBSA
   HEÇ-HEÇƏ NƏTİCƏSİ
========================================================= */

if ($can0_hechece) {

    /* =====================================================
       SON RAUND MƏLUMATLARI
    ===================================================== */

    if ((int)$duel['oyuncu1_id'] === $my_id) {

        $menim_zarar_hec  = (int)$duel['son_oyuncu1_zarar'];
        $reqib_zarar_hec  = (int)$duel['son_oyuncu2_zarar'];

        $menim_kritik_hec = (int)$duel['son_oyuncu1_kritik'];
        $reqib_kritik_hec = (int)$duel['son_oyuncu2_kritik'];

        $menim_xeta_hec   = (int)$duel['son_oyuncu1_xeta'];
        $reqib_xeta_hec   = (int)$duel['son_oyuncu2_xeta'];

        $menim_hucum_hec  = $duel['son_oyuncu1_hucum'];
        $reqib_hucum_hec  = $duel['son_oyuncu2_hucum'];

    } else {

        $menim_zarar_hec  = (int)$duel['son_oyuncu2_zarar'];
        $reqib_zarar_hec  = (int)$duel['son_oyuncu1_zarar'];

        $menim_kritik_hec = (int)$duel['son_oyuncu2_kritik'];
        $reqib_kritik_hec = (int)$duel['son_oyuncu1_kritik'];

        $menim_xeta_hec   = (int)$duel['son_oyuncu2_xeta'];
        $reqib_xeta_hec   = (int)$duel['son_oyuncu1_xeta'];

        $menim_hucum_hec  = $duel['son_oyuncu2_hucum'];
        $reqib_hucum_hec  = $duel['son_oyuncu1_hucum'];
    }


    /* =====================================================
       HÜCUM ADLARI
    ===================================================== */

    $hucum_adlari_hec = [
        0 => 'başa',
        1 => 'sinəyə',
        2 => 'gövdəyə',
        3 => 'ayağa'
    ];


    $menim_hucum_metn_hec = isset(
        $hucum_adlari_hec[(int)$menim_hucum_hec]
    )
        ? $hucum_adlari_hec[(int)$menim_hucum_hec]
        : 'naməlum yerə';


    $reqib_hucum_metn_hec = isset(
        $hucum_adlari_hec[(int)$reqib_hucum_hec]
    )
        ? $hucum_adlari_hec[(int)$reqib_hucum_hec]
        : 'naməlum yerə';


    /* =====================================================
       HEÇ-HEÇƏ BAŞLIĞI
    ===================================================== */
?>

<br>

<div class="center">

    <div class="block_line">

        <span class="green">
            Döyüş heç-heçə oldu!
        </span>

    </div>

</div>

<br>


<!-- =====================================================
     RƏQİBİN ZƏRBƏSİ
===================================================== -->

<div class="battle_log">

<?php if ($reqib_xeta_hec): ?>

    Rəqib vurdu

    <b>
        <?php echo $reqib_hucum_metn_hec; ?>
    </b>

    <b style="color:seagreen;">siz zərbədən yayındınız.</b>

<?php elseif ($reqib_zarar_hec > 0): ?>

    Rəqib

    <b>

        <?php if ($reqib_kritik_hec): ?>

            <font style="color:#FF0000">
                <?php echo $reqib_zarar_hec; ?> (krit)
            </font>

        <?php else: ?>

            <?php echo $reqib_zarar_hec; ?>

        <?php endif; ?>

    </b>

    zərbə vurdu

    <b>
        <?php echo $reqib_hucum_metn_hec; ?>
    </b>

<?php else: ?>

    Rəqib vurdu

    <b>
        <?php echo $reqib_hucum_metn_hec; ?>
    </b>

    siz zərbəni dəf etdiniz.

<?php endif; ?>


<br>


<!-- =====================================================
     SİZİN CANINIZ
===================================================== -->
<div style=" display:flex; align-items:center; gap:5px; white-space:nowrap; ">
<span>Sizin canınız:</span>
<?php

$menim_can_hec = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$duel['oyuncu1_can']
    : (int)$duel['oyuncu2_can'];

$menim_can_max_hec = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$player1_yekun_can
    : (int)$player2_yekun_can;

if ($menim_can_max_hec <= 0) {
    $menim_can_max_hec = 1;
}

if ($menim_can_hec < 0) {
    $menim_can_hec = 0;
}

if ($menim_can_hec > $menim_can_max_hec) {
    $menim_can_hec = $menim_can_max_hec;
}

?>


<div style="
    width:130px;
    height:14px;
    background:#39a852;
    border:1px solid #111;
    margin:5px 0;
    position:relative;
    overflow:hidden;
">

    <div style="
        position:absolute;
        left:0;
        top:0;
        width:<?php echo ($menim_can_hec / $menim_can_max_hec) * 100; ?>%;
        height:100%;
        background:#39a852;
    "></div>

    <div style="
        position:absolute;
        right:0;
        top:0;
        width:<?php echo 100 - (($menim_can_hec / $menim_can_max_hec) * 100); ?>%;
        height:100%;
        background:#d90000;
    "></div>

    <div style="
        position:absolute;
        left:0;
        top:0;
        width:100%;
        height:100%;
        text-align:center;
        color:#fff;
        font-size:11px;
        line-height:14px;
        font-weight:bold;
    ">
        <?php echo $menim_can_hec; ?>
        /
        <?php echo $menim_can_max_hec; ?>
    </div>

</div>

</div>
</div>

<br>



<!-- =====================================================
     SİZİN ZƏRBƏNİZ
===================================================== -->

<div class="battle_log">

<?php if ($menim_xeta_hec): ?>

    Siz

    <b>
        <?php echo $menim_hucum_metn_hec; ?>
    </b>

    rəqib zərbədən yayındı.


<?php elseif ($menim_zarar_hec > 0): ?>

    Siz

    <b>
        <?php if ($menim_kritik_hec): ?>
            <span style="color:#FF0000;">
                <?php echo $menim_zarar_hec; ?>
                (krit)
            </span>
        <?php else: ?>
            <?php echo $menim_zarar_hec; ?>
        <?php endif; ?>
    </b>

    zərbə vurdunuz

    <b>
        <?php echo $menim_hucum_metn_hec; ?>
    </b>

<?php else: ?>

    Siz vurdunuz

    <b>
        <?php echo $menim_hucum_metn_hec; ?>
    </b>

    rəqib zərbəni dəf etdi.

<?php endif; ?>





<br>


<!-- =====================================================
     RƏQİBİN CANI
===================================================== -->

<div style=" display:flex; align-items:center; gap:5px; white-space:nowrap; ">
<span>Reqibin canı:</span>

<?php

$reqib_can_hec = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$duel['oyuncu2_can']
    : (int)$duel['oyuncu1_can'];

$reqib_can_max_hec = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$player2_yekun_can
    : (int)$player1_yekun_can;

if ($reqib_can_max_hec <= 0) {
    $reqib_can_max_hec = 1;
}

if ($reqib_can_hec < 0) {
    $reqib_can_hec = 0;
}

if ($reqib_can_hec > $reqib_can_max_hec) {
    $reqib_can_hec = $reqib_can_max_hec;
}

?>

<div style="
    width:130px;
    height:14px;
    background:#39a852;
    border:1px solid #111;
    margin:5px 0;
    position:relative;
    overflow:hidden;
">

    <div style="
        position:absolute;
        left:0;
        top:0;
        width:<?php echo ($reqib_can_hec / $reqib_can_max_hec) * 100; ?>%;
        height:100%;
        background:#39a852;
    "></div>

    <div style="
        position:absolute;
        right:0;
        top:0;
        width:<?php echo 100 - (($reqib_can_hec / $reqib_can_max_hec) * 100); ?>%;
        height:100%;
        background:#d90000;
    "></div>

    <div style="
        position:absolute;
        left:0;
        top:0;
        width:100%;
        height:100%;
        text-align:center;
        color:#fff;
        font-size:11px;
        line-height:14px;
        font-weight:bold;
    ">
        <?php echo $reqib_can_hec; ?>
        /
        <?php echo $reqib_can_max_hec; ?>
    </div>

</div>

</div>
</div>
<br>


<!-- =====================================================
     MÜKAFATLAR
===================================================== -->

Elde etdiniz:

<br>

Tecrübe: 0

<br>

<?php
$rank_miqdari = 1;

if ((int)$player1['movqe'] !== (int)$player2['movqe']) {
    $rank_miqdari = 4;
}
?>

Rang: <?php echo $rank_miqdari; ?>

<br><br>


<a href="log_izle.php?go=izle&amp;duel_id=<?php echo (int)$duel_id; ?>">
    Oyunun Gedişatını izle
</a>

<br>

<a href="online.php?">
    Online döyüşçülər
</a>

|

<a href="menu.php?">
    Ana sehife
</a>

<br>


<?php

    exit;
}

?>
  

<?php
/* =========================================================
   SON RAUND MƏLUMATLARI
========================================================= */

if ((int)$duel['oyuncu1_id'] === $my_id) {

    $menim_zarar_final  = (int)$duel['son_oyuncu1_zarar'];
    $reqib_zarar_final  = (int)$duel['son_oyuncu2_zarar'];

    $menim_kritik_final = (int)$duel['son_oyuncu1_kritik'];
    $reqib_kritik_final = (int)$duel['son_oyuncu2_kritik'];

    $menim_xeta_final   = (int)$duel['son_oyuncu1_xeta'];
    $reqib_xeta_final   = (int)$duel['son_oyuncu2_xeta'];

    $menim_hucum_final  = $duel['son_oyuncu1_hucum'];
    $reqib_hucum_final  = $duel['son_oyuncu2_hucum'];

} else {

    $menim_zarar_final  = (int)$duel['son_oyuncu2_zarar'];
    $reqib_zarar_final  = (int)$duel['son_oyuncu1_zarar'];

    $menim_kritik_final = (int)$duel['son_oyuncu2_kritik'];
    $reqib_kritik_final = (int)$duel['son_oyuncu1_kritik'];

    $menim_xeta_final   = (int)$duel['son_oyuncu2_xeta'];
    $reqib_xeta_final   = (int)$duel['son_oyuncu1_xeta'];

    $menim_hucum_final  = $duel['son_oyuncu2_hucum'];
    $reqib_hucum_final  = $duel['son_oyuncu1_hucum'];
}

$hucum_adlari_final = [
    0 => 'başa',
    1 => 'sinəyə',
    2 => 'gövdəyə',
    3 => 'ayağa'
];

$menim_hucum_metn_final = isset($hucum_adlari_final[(int)$menim_hucum_final])
    ? $hucum_adlari_final[(int)$menim_hucum_final]
    : 'naməlum yerə';

$reqib_hucum_metn_final = isset($hucum_adlari_final[(int)$reqib_hucum_final])
    ? $hucum_adlari_final[(int)$reqib_hucum_final]
    : 'naməlum yerə';

$reqib_id = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$duel['oyuncu2_id']
    : (int)$duel['oyuncu1_id'];

$menim_can_final = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$duel['oyuncu1_can']
    : (int)$duel['oyuncu2_can'];

$reqib_can_final = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$duel['oyuncu2_can']
    : (int)$duel['oyuncu1_can'];
?>
<br>






    <?php if ($qalib_id === $my_id): ?>
<div class="center">
        <div class="block_line">
            <span class="green">
                Siz Qalib Gəldiniz!
            </span>
        </div>

    <?php elseif ($qalib_id > 0): ?>
<div class="center">
        <div class="block_line">
            <span class="red">
                Siz Məğlub oldunuz!
            </span>
        </div>

    <?php else: ?>

        <div class="block_line">
            <span class="green">
                Duel heç-heçə başa çatdı!
            </span>
        </div>

    <?php endif; ?>


</div>

<br>


<div class="battle_log">
<?php if ($duel['son_hucum_eden'] !== null): ?>

<?php if ($reqib_xeta_final): ?>

    Rəqib vurdu
    <b><?php echo $reqib_hucum_metn_final; ?></b>
    <b style="color:seagreen;">siz zərbədən yayındınız.</b>

<?php elseif ($reqib_zarar_final > 0): ?>

    Rəqib

    <b>
        <?php if ($reqib_kritik_final): ?>

            <font style="color:#FF0000">
                <?php echo $reqib_zarar_final; ?> (krit)
            </font>

        <?php else: ?>

            <?php echo $reqib_zarar_final; ?>

        <?php endif; ?>
    </b>

    zərbə vurdu
    <b><?php echo $reqib_hucum_metn_final; ?></b>

<?php else: ?>
    Rəqib vurdu
    <b><?php echo $reqib_hucum_metn_final; ?></b>
    siz zərbəni dəf etdiniz.

<?php endif; ?>
<?php endif; ?>


<?php
$menim_can_max = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$player1_yekun_can
    : (int)$player2_yekun_can;

$menim_can_final = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$duel['oyuncu1_can']
    : (int)$duel['oyuncu2_can'];

if ($menim_can_max <= 0) {
    $menim_can_max = 1;
}

if ($menim_can_final < 0) {
    $menim_can_final = 0;
}

if ($menim_can_final > $menim_can_max) {
    $menim_can_final = $menim_can_max;
}

$menim_can_faiz = ($menim_can_final / $menim_can_max) * 100;
$menim_itirilmis_faiz = 100 - $menim_can_faiz;
?>



<div style="
    display:flex;
    align-items:center;
    justify-content:flex-start;
    gap:4px;
    margin-top:4px;
    white-space:nowrap;
">

    <span>Sizin canınız:</span>



    <div style="
        width:130px;
        height:14px;
        background:#39a852;
        border:1px solid #111;
        position:relative;
        overflow:hidden;
        flex-shrink:0;
    ">

        <!-- QALAN CAN -->
        <div style="
            position:absolute;
            left:0;
            top:0;
            width:<?php echo $menim_can_faiz; ?>%;
            height:100%;
            background:#39a852;
        "></div>

        <!-- ITIRILMIS CAN -->
        <div style="
            position:absolute;
            right:0;
            top:0;
            width:<?php echo $menim_itirilmis_faiz; ?>%;
            height:100%;
            background:#d90000;
        "></div>

        <!-- CAN RƏQƏMİ -->
        <div style="
            position:absolute;
            left:0;
            top:0;
            width:100%;
            height:100%;
            text-align:center;
            color:#fff;
            font-size:11px;
            line-height:14px;
            font-weight:bold;
        ">
            <?php echo $menim_can_final; ?> / <?php echo $menim_can_max; ?>
        </div>

    </div>
</div>

</div>

<br>

<div class="battle_log">

<?php if ($menim_xeta_final): ?>

    Siz vurdunuz
    <b style="color:green;"><?php echo $menim_hucum_metn_final; ?></b>
    rəqib zərbədən yayındı.

<?php elseif ($menim_zarar_final > 0): ?>

    Siz

    <b>
        <?php if ($menim_kritik_final): ?>

            <font style="color:#FF0000">
                <?php echo $menim_zarar_final; ?> (krit)
            </font>

        <?php else: ?>

            <?php echo $menim_zarar_final; ?>

        <?php endif; ?>
    </b>

    zərbə vurdunuz
    <b><?php echo $menim_hucum_metn_final; ?></b>

<?php else: ?>

    Siz vurdunuz
    <b><?php echo $menim_hucum_metn_final; ?></b>
    rəqib zərbəni dəf etdi.

<?php endif; ?>



<?php
$reqib_can_max = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$player2_yekun_can
    : (int)$player1_yekun_can;

$reqib_can_final = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$duel['oyuncu2_can']
    : (int)$duel['oyuncu1_can'];

if ($reqib_can_max <= 0) {
    $reqib_can_max = 1;
}

if ($reqib_can_final < 0) {
    $reqib_can_final = 0;
}

if ($reqib_can_final > $reqib_can_max) {
    $reqib_can_final = $reqib_can_max;
}

$reqib_can_faiz = ($reqib_can_final / $reqib_can_max) * 100;
$reqib_itirilmis_faiz = 100 - $reqib_can_faiz;
?>

<div style="
    display:flex;
    align-items:center;
    justify-content:flex-start;
    gap:4px;
    margin-top:4px;
    white-space:nowrap;
">

    <span>Reqibin canı:</span>


    <div style="
        width:130px;
        height:14px;
        background:#39a852;
        border:1px solid #111;
        position:relative;
        overflow:hidden;
        flex-shrink:0;
    ">

        <!-- QALAN CAN -->
        <div style="
            position:absolute;
            left:0;
            top:0;
            width:<?php echo $reqib_can_faiz; ?>%;
            height:100%;
            background:#39a852;
        "></div>

        <!-- ITIRILMIS CAN -->
        <div style="
            position:absolute;
            right:0;
            top:0;
            width:<?php echo $reqib_itirilmis_faiz; ?>%;
            height:100%;
            background:#d90000;
        "></div>

        <!-- CAN RƏQƏMİ -->
        <div style="
            position:absolute;
            left:0;
            top:0;
            width:100%;
            height:100%;
            text-align:center;
            color:#fff;
            font-size:11px;
            line-height:14px;
            font-weight:bold;
        ">
            <?php echo $reqib_can_final; ?> / <?php echo $reqib_can_max; ?>
        </div>

    </div>
</div>
</div>


<br>

<u>Əldə etdiniz:</u>

<br>

<b>Tecrübe:</b> 0

<br>
<?php
$qalib_movqe = null;
$reqib_movqe = null;
$qalib_seviyye = 0;
$reqib_seviyye = 0;
$verilecek_rank = 0;

if ($qalib_id !== null && (int)$qalib_id !== 0) {

    if ((int)$qalib_id === (int)$player1_id) {

        $qalib_movqe = $player1['movqe'] ?? null;
        $reqib_movqe = $player2['movqe'] ?? null;

        $qalib_seviyye = (int)$player1['oyuncunun_seviyyesi'];
        $reqib_seviyye = (int)$player2['oyuncunun_seviyyesi'];

    } elseif ((int)$qalib_id === (int)$player2_id) {

        $qalib_movqe = $player2['movqe'] ?? null;
        $reqib_movqe = $player1['movqe'] ?? null;

        $qalib_seviyye = (int)$player2['oyuncunun_seviyyesi'];
        $reqib_seviyye = (int)$player1['oyuncunun_seviyyesi'];
    }

    /* Əvvəl yüksək səviyyəli rəqibə qalib gəlmə yoxlanılır */
    if ($reqib_seviyye > $qalib_seviyye) {

        $verilecek_rank = 5;

    } elseif ((int)$qalib_movqe === (int)$reqib_movqe) {

        $verilecek_rank = 1;

    } else {

        $verilecek_rank = 4;
    }
}
?>
<b>Rang:</b>
<?php echo ($qalib_id === $my_id) ? (int)$verilecek_rank : 0; ?>

<br><br>


<a href="log_izle.php?go=izle&amp;duel_id=<?php echo (int)$duel_id; ?>">
    Oyunun Gedişatını izle
</a>

<br>

<a href="online.php?">
    Online döyüşçülər
</a>

|

<a href="menu.php?">
    Ana sehife
</a>

<br>


<?php else: ?>

<?php
$son_hucum_eden = isset($duel['son_hucum_eden'])
    ? (int)$duel['son_hucum_eden']
    : 0;
?>

<div class="info">







<br/><br/>

<?php

$menim_hucum = null;
$reqib_hucum = null;

if ($my_id === (int)$duel['oyuncu1_id']) {

    $menim_hucum = $duel['oyuncu1_hucum'];
    $reqib_hucum = $duel['oyuncu2_hucum'];

} else {

    $menim_hucum = $duel['oyuncu2_hucum'];
    $reqib_hucum = $duel['oyuncu1_hucum'];
}

$hucum_adlari = [
    0 => 'baÅŸa',
    1 => 'sinÉ™yÉ™',
    2 => 'gÃ¶vdÉ™yÉ™',
    3 => 'ayaÄŸa'
];

$reqib_hucum_metn = isset($hucum_adlari[(int)$reqib_hucum])
    ? $hucum_adlari[(int)$reqib_hucum]
    : 'namÉ™lum yerÉ™';

$son_zarar = (int)$duel['son_zarar'];

?>

<?php

/* =========================================================
   SON RAUND NÆTÄ°CÆSÄ°
========================================================= */

$son_oyuncu1_zarar = (int)$duel['son_oyuncu1_zarar'];
$son_oyuncu2_zarar = (int)$duel['son_oyuncu2_zarar'];

$son_oyuncu1_kritik = (int)$duel['son_oyuncu1_kritik'];
$son_oyuncu2_kritik = (int)$duel['son_oyuncu2_kritik'];

$son_oyuncu1_xeta = (int)$duel['son_oyuncu1_xeta'];
$son_oyuncu2_xeta = (int)$duel['son_oyuncu2_xeta'];
$son_mudafie = (int)$duel['son_mudafie'];

/*
 * Son zÉ™rbÉ™ni vuran ÅŸÉ™xsin hÃ¼cum yerini tapÄ±rÄ±q.
 */
$son_hucum_yeri = null;

if ($son_hucum_eden === (int)$duel['oyuncu1_id']) {

    $son_hucum_yeri = $duel['oyuncu1_hucum'];

} elseif ($son_hucum_eden === (int)$duel['oyuncu2_id']) {

    $son_hucum_yeri = $duel['oyuncu2_hucum'];
}
$son_hucum_eden = isset($duel['son_hucum_eden'])
    ? (int)$duel['son_hucum_eden']
    : 0;


/* =========================================================
   DUEL VAXTI
   İlk zərbə: 300 saniyə
   Sonrakı raundlar: 180 saniyə
========================================================= */

$novbe_baslama = !empty($duel['novbe_baslama_tarixi'])
    ? strtotime($duel['novbe_baslama_tarixi'])
    : time();

/*
 * Əgər heç bir tərəf hələ zərbə vurmayıbsa,
 * ilk raund üçün 300 saniyə.
 *
 * Əgər əvvəlki raund artıq tamamlanıbsa,
 * 180 saniyə.
 */

$novbe_baslama = !empty($duel['novbe_baslama_tarixi'])
    ? strtotime($duel['novbe_baslama_tarixi'])
    : time();

/*
 * İlk raund:
 * 300 saniyə.
 *
 * İlk raund tamamlandıqdan sonra:
 * 180 saniyə.
 */
if ((int)$duel['raund'] === 0) {
    $vaxt_limit = 300;
} else {
   $vaxt_limit = ((int)$duel['raund'] === 0)
    ? $ilk_vaxt
    : $sonraki_vaxt;
}

$kecen_vaxt = time() - $novbe_baslama;
$qalan_vaxt = $vaxt_limit - $kecen_vaxt;

/* =========================================================
   VAXT BİTDİ — ZƏRBƏ VURMAYAN MƏĞLUBDUR
========================================================= */

if ($qalan_vaxt <= 0) {

    $oyuncu1_hucum_var = (
        $duel['oyuncu1_hucum'] !== null
    );

    $oyuncu2_hucum_var = (
        $duel['oyuncu2_hucum'] !== null
    );


  /* =====================================================
   HƏR İKİ TƏRƏF ZƏRBƏ VURMAYIB
   HEÇ-HEÇƏ
===================================================== */

if (
    !$oyuncu1_hucum_var &&
    !$oyuncu2_hucum_var
) {

    $stmt_hec_hece = $pdo->prepare("
        UPDATE duel
        SET
            qalib_id = 0,
            hec_hece = 1,
            bitdi = 1,
            bitdi_qalib = 0,
            bitdi_meglub = 0,
            novbe_id = NULL
        WHERE id = :duel_id
          AND bitdi = 0
          AND (qalib_id IS NULL OR qalib_id = 0)
    ");

    $stmt_hec_hece->execute([
        ':duel_id' => (int)$duel_id
    ]);

    header(
        "Location: fight.php?duel_id="
        . (int)$duel_id
        . "&hec_hece=1"
    );

    exit;
}


    /* =====================================================
       YALNIZ OYUNCU 1 ZƏRBƏ VURUB
       OYUNCU 2 VURMAYIB
    ===================================================== */

    if (
        $oyuncu1_hucum_var &&
        !$oyuncu2_hucum_var
    ) {

        $qalib_timeout = (int)$duel['oyuncu1_id'];
        $meglub_timeout = (int)$duel['oyuncu2_id'];

        $stmt_vaxt = $pdo->prepare("
            UPDATE duel
            SET
                bitdi_qalib = :bitdi_qalib,
                bitdi_meglub = :bitdi_meglub
            WHERE id = :duel_id
              AND (bitdi_qalib IS NULL OR bitdi_qalib = 0)
              AND (bitdi_meglub IS NULL OR bitdi_meglub = 0)
        ");

        $stmt_vaxt->execute([
            ':bitdi_qalib'  => $qalib_timeout,
            ':bitdi_meglub' => $meglub_timeout,
            ':duel_id'      => (int)$duel_id
        ]);

        /*
         * DB-də nəticəni dərhal dəyişənə yazırıq.
         * Beləliklə aşağıdakı nəticə hissəsi
         * düzgün şəkildə işləyir.
         */
        $duel['bitdi_qalib'] = $qalib_timeout;
        $duel['bitdi_meglub'] = $meglub_timeout;

        /*
         * Daha aşağıdakı timeout kodlarına düşməsin.
         */
        $qalan_vaxt = 1;
    }


    /* =====================================================
       YALNIZ OYUNCU 2 ZƏRBƏ VURUB
       OYUNCU 1 VURMAYIB
    ===================================================== */

    elseif (
        $oyuncu2_hucum_var &&
        !$oyuncu1_hucum_var
    ) {

        $qalib_timeout = (int)$duel['oyuncu2_id'];
        $meglub_timeout = (int)$duel['oyuncu1_id'];

        $stmt_vaxt = $pdo->prepare("
            UPDATE duel
            SET
                bitdi_qalib = :bitdi_qalib,
                bitdi_meglub = :bitdi_meglub
            WHERE id = :duel_id
              AND (bitdi_qalib IS NULL OR bitdi_qalib = 0)
              AND (bitdi_meglub IS NULL OR bitdi_meglub = 0)
        ");

        $stmt_vaxt->execute([
            ':bitdi_qalib'  => $qalib_timeout,
            ':bitdi_meglub' => $meglub_timeout,
            ':duel_id'      => (int)$duel_id
        ]);

        /*
         * Nəticəni dərhal yadda saxla.
         */
        $duel['bitdi_qalib'] = $qalib_timeout;
        $duel['bitdi_meglub'] = $meglub_timeout;

        /*
         * Aşağıdakı timeout məntiqinə yenidən düşməsin.
         */
        $qalan_vaxt = 1;
    }


    /* =====================================================
       HƏR İKİ TƏRƏF ZƏRBƏ VURUB
       TIMEOUT NƏTİCƏSİ YARATMIRIQ
    ===================================================== */

    else {

        $bitdi_qalib = (int)($duel['bitdi_qalib'] ?? 0);
        $bitdi_meglub = (int)($duel['bitdi_meglub'] ?? 0);
    }
}


?>
<?php
/* HÃ¼cum adlarÄ± */

$hucum_adlari = [
    0 => 'BaÅŸdan',
    1 => 'SinÉ™dÉ™n',
    2 => 'GÃ¶vdÉ™dÉ™n',
    3 => 'Ayaqdan'
];


/* Son zÉ™rbÉ™nin yeri */

$son_hucum_metn = isset($hucum_adlari[(int)$son_hucum_yeri])
    ? $hucum_adlari[(int)$son_hucum_yeri]
    : 'namÉ™lum yerdÉ™n';


/*
 * MÉ™n hÉ™min raundda zÉ™rbÉ™ vuran tÉ™rÉ™fÉ™m?
 */
$men_vurdum = ($son_hucum_eden === $my_id);


/*
 * QarÅŸÄ± tÉ™rÉ™fin kim olduÄŸunu mÃ¼É™yyÉ™nlÉ™ÅŸdiririk.
 */
$reqib_id = ($my_id === (int)$duel['oyuncu1_id'])
    ? (int)$duel['oyuncu2_id']
    : (int)$duel['oyuncu1_id'];


/* =========================================================
   MÆNÄ°M ZÆRBÆM
========================================================= */




/* =========================================================
   SON RAUND NÆTÄ°CÆLÆRÄ°
========================================================= */

/* MÉ™nim vÉ™ rÉ™qibin mÉ™lumatlarÄ±nÄ± mÃ¼É™yyÉ™n edirik */

if ((int)$duel['oyuncu1_id'] === $my_id) {

    /* MÉ™n oyunÃ§u 1-É™m */

    $menim_hucum = $duel['son_oyuncu1_hucum'];
    $reqib_hucum = $duel['son_oyuncu2_hucum'];

    $menim_zarar = (int)$duel['son_oyuncu1_zarar'];
    $reqib_zarar = (int)$duel['son_oyuncu2_zarar'];

    $menim_kritik = (int)$duel['son_oyuncu1_kritik'];
    $reqib_kritik = (int)$duel['son_oyuncu2_kritik'];

    $menim_xeta = (int)$duel['son_oyuncu1_xeta'];
    $reqib_xeta = (int)$duel['son_oyuncu2_xeta'];

} else {

    /* MÉ™n oyunÃ§u 2-yÉ™m */

    $menim_hucum = $duel['son_oyuncu2_hucum'];
    $reqib_hucum = $duel['son_oyuncu1_hucum'];

    $menim_zarar = (int)$duel['son_oyuncu2_zarar'];
    $reqib_zarar = (int)$duel['son_oyuncu1_zarar'];

    $menim_kritik = (int)$duel['son_oyuncu2_kritik'];
    $reqib_kritik = (int)$duel['son_oyuncu1_kritik'];

    $menim_xeta = (int)$duel['son_oyuncu2_xeta'];
    $reqib_xeta = (int)$duel['son_oyuncu1_xeta'];
}


/* =========================================================
   HÃœCUM YERLÆRÄ°
========================================================= */

$hucum_adlari = [
    0 => 'başa',
    1 => 'sinəyə',
    2 => 'gövdəyə',
    3 => 'ayağa'
];


/* MÉ™nim hÃ¼cum yerim */

$menim_hucum_metn = isset($hucum_adlari[(int)$menim_hucum])
    ? $hucum_adlari[(int)$menim_hucum]
    : 'namelum yere';


/* RÉ™qibin hÃ¼cum yeri */

$reqib_hucum_metn = isset($hucum_adlari[(int)$reqib_hucum])
    ? $hucum_adlari[(int)$reqib_hucum]
    : 'namelum yere';

?>


<!-- =========================================================
     MƏNİM NƏTİCƏM
========================================================= -->

<?php if ($menim_hucum !== null): ?>

    <?php if ($menim_xeta): ?>

        Siz vurdunuz
        <b><?php echo $menim_hucum_metn; ?></b>

        <b style="color:seagreen;"> Reqib zerbeden yayındı.</b>

    <?php elseif ($menim_zarar > 0): ?>

        Siz

<b>
    <?php if ($menim_kritik): ?>
        <font style="color:#FF0000">
            <?php echo $menim_zarar; ?>
            (krit)
        </font>
    <?php else: ?>
        <?php echo $menim_zarar; ?>
    <?php endif; ?>
</b>

        zerbe vurdunuz.

        <b>
            <?php echo $menim_hucum_metn; ?>
        </b>

    <?php else: ?>

        Siz vurdunuz
        <b><?php echo $menim_hucum_metn; ?></b>

        reqib zerbeni def etdi.

    <?php endif; ?>

    <br>
    <hr>

<?php endif; ?>


<?php if ($reqib_hucum !== null): ?>

    <?php if ($reqib_xeta): ?>

        Reqib vurdu
        <b><?php echo $reqib_hucum_metn; ?></b>

        <b style="color:seagreen;">siz zerbeden yayındınız.</b>

    <?php elseif ($reqib_zarar > 0): ?>

        <?php if ($reqib_kritik): ?>

            Reqib

            <b style="color:red;">
                <?php echo $reqib_zarar; ?> (krit)
            </b>

            zerbe vurdu

            <b>
                <?php echo $reqib_hucum_metn; ?>
            </b>.

        <?php else: ?>

            Reqib 
             <b><?php echo $reqib_zarar; ?></b>
            zerbe vurdu.
            <b><?php echo $reqib_hucum_metn; ?></b>

          

        <?php endif; ?>

    <?php else: ?>

        Reqib vurdu
        <b><?php echo $reqib_hucum_metn; ?></b>

        siz zerbeni def etdiniz.

    <?php endif; ?>

<?php endif; ?>



<!-- =========================================================
     180 SANÄ°YÆ XÆBÆRDARLIÄI
========================================================= -->

<?php
/*
 * Əgər mən artıq zərbə vurmuşamsa,
 * ilkin duel səhifəsində taymer göstərilmir.
 */
$men_zerbe_vurub = (
    $my_id === (int)$duel['oyuncu1_id']
        ? $duel['oyuncu1_hucum']
        : $duel['oyuncu2_hucum']
);

if ($men_zerbe_vurub === null):
?>

<div style="text-align:left; margin:8px 0;">


        
        <span id="duelTimer">
    <small><?php echo (int)$qalan_vaxt; ?> saniyə ərzində zərbə atmasaz məğlub olacaqsız.</small>
</span>
        


</div>

<script>
(function () {

    var qalan = <?php echo (int)$qalan_vaxt; ?>;
    var timer = document.getElementById('duelTimer');

    function geriSay() {

        if (qalan <= 0) {
    timer.innerHTML = '<small>0 saniyə ərzində zərbə atmasaz məğlub olacaqsız.</small>';

            /*
             * Vaxt bitdi.
             * PHP yenidən işləyəcək və
             * heç-heçə / məğlubiyyət timeout-unu yoxlayacaq.
             */
            window.location.replace(
                'fight.php?duel_id=<?php echo (int)$duel_id; ?>'
            );

            return;
        }

       timer.innerHTML = '<small>' + qalan + ' saniyə ərzində zərbə atmasaz məğlub olacaqsız.</small>';
        qalan--;

        setTimeout(geriSay, 1000);
    }

    geriSay();

})();
</script>

<?php endif; ?>



<?php
/* =========================================================
   BAXAN İSTİFADƏÇİ BİRİNCİ, RƏQİB İKİNCİ
========================================================= */

if ((int)$my_id === (int)$player1_id) {

    // BAXAN İSTİFADƏÇİ = PLAYER 1
    $ust_id = $player1_id;
    $ust_login = $player1_login;
    $ust_level = $player1_level;
    $ust_can = (int)$duel['oyuncu1_can'];
    $ust_can_max = (int)$player1_yekun_can;

    // RƏQİB = PLAYER 2
    $alt_id = $player2_id;
    $alt_login = $player2_login;
    $alt_level = $player2_level;
    $alt_can = (int)$duel['oyuncu2_can'];
    $alt_can_max = (int)$player2_yekun_can;

} else {

    // BAXAN İSTİFADƏÇİ = PLAYER 2
    $ust_id = $player2_id;
    $ust_login = $player2_login;
    $ust_level = $player2_level;
    $ust_can = (int)$duel['oyuncu2_can'];
    $ust_can_max = (int)$player2_yekun_can;

    // RƏQİB = PLAYER 1
    $alt_id = $player1_id;
    $alt_login = $player1_login;
    $alt_level = $player1_level;
    $alt_can = (int)$duel['oyuncu1_can'];
    $alt_can_max = (int)$player1_yekun_can;
}


/* =========================================================
   BAXAN İSTİFADƏÇİ
========================================================= */

if ($ust_can_max <= 0) {
    $ust_can_max = 1;
}

if ($ust_can < 0) {
    $ust_can = 0;
}

if ($ust_can > $ust_can_max) {
    $ust_can = $ust_can_max;
}

$ust_faiz = ($ust_can / $ust_can_max) * 100;
$ust_itirilmis_faiz = 100 - $ust_faiz;
?>

<!-- =========================================================
     BAXAN İSTİFADƏÇİ
========================================================= -->

<div class="battle_log">


<b>

    <a href="infoforce.php?uid=<?php echo (int)$ust_id; ?>">

        <?php
        echo htmlspecialchars(
            $ust_login,
            ENT_QUOTES,
            'UTF-8'
        );
        ?>

    </a>

    [<?php echo (int)$ust_level; ?>]

</b>

<br>

<div style="
    width:130px;
    height:14px;
    background:#39a852;
    border:1px solid #111;
    margin:5px 0;
    position:relative;
    overflow:hidden;
">

    <!-- QALAN CAN -->
    <div style="
        position:absolute;
        left:0;
        top:0;
        width:<?php echo $ust_faiz; ?>%;
        height:100%;
        background:#39a852;
    "></div>

    <!-- GEDƏN CAN -->
    <div style="
        position:absolute;
        right:0;
        top:0;
        width:<?php echo $ust_itirilmis_faiz; ?>%;
        height:100%;
        background:#d90000;
    "></div>

    <!-- CAN RƏQƏMİ -->
    <div style="
        position:absolute;
        left:0;
        top:0;
        width:100%;
        height:100%;
        text-align:center;
        color:#fff;
        font-size:11px;
        line-height:14px;
        font-weight:bold;
    ">

        <?php echo $ust_can; ?> / <?php echo $ust_can_max; ?>

    </div>

</div>


</div>

<!-- =========================================================
     VS
========================================================= -->

<b>VS</b>

<br>

<?php
/* =========================================================
   RƏQİB
========================================================= */

if ($alt_can_max <= 0) {
    $alt_can_max = 1;
}

if ($alt_can < 0) {
    $alt_can = 0;
}

if ($alt_can > $alt_can_max) {
    $alt_can = $alt_can_max;
}

$alt_faiz = ($alt_can / $alt_can_max) * 100;
$alt_itirilmis_faiz = 100 - $alt_faiz;
?>

<!-- =========================================================
     RƏQİB
========================================================= -->

<div class="battle_log">


<b>

    <a href="infoforce.php?uid=<?php echo (int)$alt_id; ?>">

        <?php
        echo htmlspecialchars(
            $alt_login,
            ENT_QUOTES,
            'UTF-8'
        );
        ?>

    </a>

    [<?php echo (int)$alt_level; ?>]

</b>

<br>

<div style="
    width:130px;
    height:14px;
    background:#39a852;
    border:1px solid #111;
    margin:5px 0;
    position:relative;
    overflow:hidden;
">

    <!-- QALAN CAN -->
    <div style="
        position:absolute;
        left:0;
        top:0;
        width:<?php echo $alt_faiz; ?>%;
        height:100%;
        background:#39a852;
    "></div>

    <!-- GEDƏN CAN -->
    <div style="
        position:absolute;
        right:0;
        top:0;
        width:<?php echo $alt_itirilmis_faiz; ?>%;
        height:100%;
        background:#d90000;
    "></div>

    <!-- CAN RƏQƏMİ -->
    <div style="
        position:absolute;
        left:0;
        top:0;
        width:100%;
        height:100%;
        text-align:center;
        color:#fff;
        font-size:11px;
        line-height:14px;
        font-weight:bold;
    ">

        <?php echo $alt_can; ?> / <?php echo $alt_can_max; ?>

    </div>

</div>







</div>






</div>









<span class="dark-brown">
    Raund:<?php echo ((int)$duel['raund'] + 1); ?>
</span>



<!-- =========================================================
     HÃœCUM / MÃœDAFÄ°Æ FORMU
========================================================= -->

<form
    method="post"
    action="fight.php?go=vurdum&amp;duel_id=<?php echo (int)$duel_id; ?>"
>


<b>Hucum:</b>

<br>


<select name="hucum">

    <option value="0">Başdan</option>

    <option value="1">Sinədən</option>

    <option value="2">Gövdədən</option>

    <option value="3">Ayağdan</option>

</select>


<br>


<b>Müdafiə:</b>

<br>


<select name="mudafie">

    <option value="0">Baş və sinə</option>

    <option value="1">Sinə və Gövdə</option>

    <option value="2">Gövdə və Ayağ</option>

    <option value="3">Ayağ və Baş</option>

</select>


<br>


<input
    type="hidden"
    name="action"
    value="save"
>


<input
    type="submit"
    class="button"
    value="Zərbə vur"
>


<br>


<br>


<small>

    <i>
       Qalib gəldiniz halda 0 təcrubə və Rang qazanacağsınız.
    </i>

</small>


<br>


</form>

<?php endif; ?>
</div>

</div>

<?php endif; ?>
</body>

</html>
