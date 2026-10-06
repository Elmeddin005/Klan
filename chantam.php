<?php

session_start();

require_once "config.php";
require_once "user_data.php";

$go = $_GET['go'] ?? '';
$my_id = (int)($_SESSION['user_id'] ?? 0);

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
   FUNKSİYALAR
========================================================= */

function goGeri()
{
    echo "window.history.back();";
}


function esya_reng($reng)
{
    $reng = trim(mb_strtolower((string)$reng, 'UTF-8'));

    $rengler = [
        'sari'    => '#FFD700',
        'sarı'    => '#FFD700',

        'goy'     => '#0088FF',
        'göy'     => '#0088FF',

        'yasil'   => '#00AA00',
        'yaşıl'   => '#00AA00',

        'qirmizi' => '#FF0000',
        'qırmızı' => '#FF0000',

        'qara'    => '#000000',
        'ağ'      => '#FFFFFF',
        'ag'      => '#FFFFFF',
        'beyaz'   => '#FFFFFF'
    ];

    return $rengler[$reng] ?? '#FFFFFF';
}

function oyuncuParametrleriniYenile($pdo, $user_id)
{
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(
    CASE
        WHEN eg.canta_id IS NOT NULL
        THEN eg.guclendirilmis_min_zerbe
        WHEN c.random_min_zerbe > 0
        THEN c.random_min_zerbe
        ELSE e.min_zerbe
    END
), 0) AS min_zerbe,
            COALESCE(SUM(
    CASE
        WHEN eg.canta_id IS NOT NULL
        THEN eg.guclendirilmis_max_zerbe
        WHEN c.random_max_zerbe > 0
        THEN c.random_max_zerbe
        ELSE e.max_zerbe
    END
), 0) AS max_zerbe,
            COALESCE(SUM(
    CASE
        WHEN eg.canta_id IS NOT NULL
        THEN eg.guclendirilmis_can
        WHEN c.random_can > 0
        THEN c.random_can
        ELSE e.can
    END
), 0) AS can,
            COALESCE(SUM(
    CASE
        WHEN eg.canta_id IS NOT NULL
        THEN eg.guclendirilmis_mudafie
        WHEN c.random_mudafie > 0
        THEN c.random_mudafie
        ELSE e.mudafie
    END
), 0) AS mudafie,
           COALESCE(SUM(
    CASE
        WHEN e.reng IN ('goy', 'göy', 'yasil', 'yaşıl')
        THEN c.random_krit
        ELSE e.krit
    END
), 0) AS krit,

COALESCE(SUM(
    CASE
        WHEN e.reng IN ('goy', 'göy', 'yasil', 'yaşıl')
        THEN c.random_krit_faiz
        ELSE 0
    END
), 0) AS krit_faiz,


COALESCE(SUM(
    CASE
        WHEN e.reng IN ('goy', 'göy', 'yasil', 'yaşıl')
        THEN c.random_anti_krit
        ELSE e.anti_krit
    END
), 0) AS anti_krit,
COALESCE(SUM(
    CASE
        WHEN e.reng IN ('goy', 'göy', 'yasil', 'yaşıl')
        THEN c.random_anti_krit_faiz
        ELSE 0
    END
), 0) AS anti_krit_faiz,

COALESCE(SUM(
    CASE
        WHEN e.reng IN ('goy', 'göy', 'yasil', 'yaşıl')
        THEN c.random_uvorot
        ELSE e.uvorot
    END
), 0) AS uvorot,
COALESCE(SUM(
    CASE
        WHEN e.reng IN ('goy', 'göy', 'yasil', 'yaşıl')
        THEN c.random_uvorot_faiz
        ELSE 0
    END
), 0) AS uvorot_faiz,

COALESCE(SUM(
    CASE
        WHEN e.reng IN ('goy', 'göy', 'yasil', 'yaşıl')
        THEN c.random_anti_uvorot
        ELSE e.anti_uvorot
    END
), 0) AS anti_uvorot,
COALESCE(SUM(
    CASE
        WHEN e.reng IN ('goy', 'göy', 'yasil', 'yaşıl')
        THEN c.random_anti_uvorot_faiz
        ELSE 0
    END
), 0) AS anti_uvorot_faiz

        FROM canta c
        INNER JOIN esyalar e
            ON e.id = c.esya_id
            LEFT JOIN esya_guclendirme eg
    ON eg.canta_id = c.id
        WHERE c.user_id = :user_id
          AND c.geyimde = 1
          AND e.reng IN ('sari', 'goy', 'göy', 'yasil', 'yaşıl')
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $param = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$param) {
        $param = [
            'min_zerbe'   => 0,
            'max_zerbe'   => 0,
            'can'         => 0,
            'mudafie'     => 0,
            'krit'        => 0,
            'krit_faiz'   => 0,
            'anti_krit'   => 0,
            'anti_krit_faiz' => 0,
            'uvorot'      => 0,
            'uvorot_faiz'      => 0,
            'anti_uvorot' => 0,
            'anti_uvorot_faiz'      => 0
        ];
    }

    $stmt_update = $pdo->prepare("
        INSERT INTO oyuncu_parametrleri
        (
            user_id,
            min_zerbe,
            max_zerbe,
            can,
            mudafie,
            krit,
            krit_faiz,
            anti_krit,
            anti_krit_faiz,
            uvorot,
            uvorot_faiz,
            anti_uvorot,
            anti_uvorot_faiz
        )
        VALUES
        (
            :user_id,
            :min_zerbe,
            :max_zerbe,
            :can,
            :mudafie,
            :krit,
            :krit_faiz,
            :anti_krit,
            :anti_krit_faiz,
            :uvorot,
            :uvorot_faiz,
            :anti_uvorot,
            :anti_uvorot_faiz
        )
        ON DUPLICATE KEY UPDATE
            min_zerbe   = VALUES(min_zerbe),
            max_zerbe   = VALUES(max_zerbe),
            can         = VALUES(can),
            mudafie     = VALUES(mudafie),
            krit        = VALUES(krit),
            krit_faiz   = VALUES(krit_faiz),
            anti_krit   = VALUES(anti_krit),
            anti_krit_faiz =VALUES(anti_krit_faiz),
            uvorot      = VALUES(uvorot),
            uvorot_faiz = Values (uvorot_faiz),
            anti_uvorot = VALUES(anti_uvorot),
            anti_uvorot_faiz = VALUES(anti_uvorot_faiz) 
    ");

    $stmt_update->execute([
        ':user_id'      => $user_id,
        ':min_zerbe'    => (int)$param['min_zerbe'],
        ':max_zerbe'    => (int)$param['max_zerbe'],
        ':can'          => (int)$param['can'],
        ':mudafie'      => (int)$param['mudafie'],
        ':krit'         => (int)$param['krit'],
        ':krit_faiz'    => (int)$param['krit_faiz'],
        ':anti_krit'    => (int)$param['anti_krit'],
        ':anti_krit_faiz'    => (int)$param['anti_krit_faiz'],
        ':uvorot'       => (int)$param['uvorot'],
        ':uvorot_faiz' => (int)$param['uvorot_faiz'],
        ':anti_uvorot'  => (int)$param['anti_uvorot'],
        ':anti_uvorot_faiz' => (int)$param['anti_uvorot_faiz']
    ]);
}


/* =========================================================
   DƏYİŞƏNLƏR
========================================================= */

$chixart_ok = false;
$chixart_xeta = '';
$chixart_ad = '';
$chixart_tip = '';

$geyin_ok = false;
$geyin_xeta = '';
$geyin_ad = '';
$geyin_tip = '';
$kohne_geyin_ad = '';

$sat_ok = false;
$sat_xeta = '';
$sat_qiymet = 0;
$sat_brilyant = 0;
$sat_almaz = false;

$info_esya = null;
/* =========================================================
   ƏŞYA ÇANTASI TUTUMU
========================================================= */

$canta_tutumu = 34;
$canta_bitme_vaxti = 0;

if ($my_id > 0) {

    $stmt_canta = $pdo->prepare("
        SELECT tutum, bitme_vaxti
        FROM istifadeci_cantalari
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $stmt_canta->execute([
        ':user_id' => $my_id
    ]);

    $canta_melumat = $stmt_canta->fetch(PDO::FETCH_ASSOC);

    if ($canta_melumat) {

        $canta_bitme_vaxti = (int)$canta_melumat['bitme_vaxti'];

        if ($canta_bitme_vaxti > time()) {

            $canta_tutumu = (int)$canta_melumat['tutum'];

        } else {

            // Müddət bitibsə, standart çantaya qayıdır
            $canta_tutumu = 34;

        }
    }
}

/* =========================================================
   AUKSİON DƏYİŞƏNLƏRİ
========================================================= */

$auksion_ok = false;
$auksion_xeta = '';
$auksion_esya = null;
$auksion_form_esya = null;

$auksion_min = 0;
$auksion_max = 0;
$auksion_muddet = 3;


/* =========================================================
   ƏŞYA ÇIXART
========================================================= */

if ($go === 'chixart') {

    $idi = (int)($_GET['idi'] ?? 0);

    if ($idi > 0 && $my_id > 0) {

        try {

            $stmt_ad = $pdo->prepare("
                SELECT
                    c.id,
                    e.ad,
                    e.tip
                FROM canta c
                INNER JOIN esyalar e ON e.id = c.esya_id
                WHERE c.id = :id
                  AND c.user_id = :user_id
                  AND c.say > 0
                  AND c.geyimde = 1
                LIMIT 1
            ");

            $stmt_ad->execute([
                ':id' => $idi,
                ':user_id' => $my_id
            ]);

            $chixart_esya = $stmt_ad->fetch(PDO::FETCH_ASSOC);

            if (!$chixart_esya) {
                throw new Exception('Geyinilmiş əşya tapılmadı.');
            }

            $chixart_ad  = $chixart_esya['ad'];
            $chixart_tip = $chixart_esya['tip'];

            $stmt_chixart = $pdo->prepare("
               UPDATE canta
    SET geyimde = 0,
        son_cixarma_vaxti = :vaxt,
        son_daxil_olma_vaxti = :vaxt
                WHERE id = :id
                  AND user_id = :user_id
                  AND say > 0
                  AND geyimde = 1
                LIMIT 1
            ");

            $stmt_chixart->execute([
                ':id' => $idi,
                ':user_id' => $my_id,
                ':vaxt' => time()
            ]);

            if ($stmt_chixart->rowCount() > 0) {

                oyuncuParametrleriniYenile($pdo, $my_id);

                $chixart_ok = true;

            } else {
                throw new Exception('Əşya çantaya qoyulmadı.');
            }

        } catch (Exception $e) {

            $chixart_xeta = $e->getMessage();
        }

    } else {
        $chixart_xeta = 'Əməliyyat düzgün deyil.';
    }
}

/* =========================================================
   GEYİN
========================================================= */

if ($go === 'geyin') {

    $idi = (int)($_GET['idi'] ?? 0);

    if ($idi > 0 && $my_id > 0) {

        try {

            $pdo->beginTransaction();

            $stmt_geyin = $pdo->prepare("
                SELECT
                    c.id,
                    c.user_id,
                    c.esya_id,
                    c.say,
                    c.geyimde,
                    e.tip,
                    e.ad
                FROM canta c
                INNER JOIN esyalar e ON e.id = c.esya_id
                WHERE c.id = :id
                  AND c.user_id = :user_id
                  AND c.say > 0
                LIMIT 1
            ");

            $stmt_geyin->execute([
                ':id' => $idi,
                ':user_id' => $my_id
            ]);

            $geyin_esya = $stmt_geyin->fetch(PDO::FETCH_ASSOC);

            if (!$geyin_esya) {
                throw new Exception('Əşya tapılmadı.');
            }

            $tip = $geyin_esya['tip'];
            $geyin_ad = $geyin_esya['ad'];
            $geyin_tip = $tip;


            if ($tip === 'balta' || $tip === 'qilinc') {

    $stmt_kohne = $pdo->prepare("
        SELECT
            c.id,
            e.ad
        FROM canta c
        INNER JOIN esyalar e ON e.id = c.esya_id
        WHERE c.user_id = :user_id
          AND e.tip IN ('balta', 'qilinc')
          AND c.geyimde = 1
          AND c.say > 0
        LIMIT 1
    ");

    $stmt_kohne->execute([
        ':user_id' => $my_id
    ]);

} else {

    $stmt_kohne = $pdo->prepare("
        SELECT
            c.id,
            e.ad
        FROM canta c
        INNER JOIN esyalar e ON e.id = c.esya_id
        WHERE c.user_id = :user_id
          AND e.tip = :tip
          AND c.geyimde = 1
          AND c.say > 0
        LIMIT 1
    ");

    $stmt_kohne->execute([
        ':user_id' => $my_id,
        ':tip' => $tip
    ]);
}

$kohne_esya = $stmt_kohne->fetch(PDO::FETCH_ASSOC);

$kohne_esya_id = 0;

if ($kohne_esya) {

    $kohne_esya_id = (int)$kohne_esya['id'];

    $kohne_geyin_ad = $kohne_esya['ad'];
}

/* =========================================================
   KÖHNƏ GEYİMLİ ƏŞYANI ÇIXAR
========================================================= */

$kohne_esya_id = 0;

if ($tip === 'balta' || $tip === 'qilinc') {

    $stmt_kohne = $pdo->prepare("
        SELECT c.id, e.ad
        FROM canta c
        INNER JOIN esyalar e ON e.id = c.esya_id
        WHERE c.user_id = :user_id
          AND e.tip IN ('balta', 'qilinc')
          AND c.geyimde = 1
          AND c.say > 0
        LIMIT 1
    ");

    $stmt_kohne->execute([
        ':user_id' => $my_id
    ]);

} else {

    $stmt_kohne = $pdo->prepare("
        SELECT c.id, e.ad
        FROM canta c
        INNER JOIN esyalar e ON e.id = c.esya_id
        WHERE c.user_id = :user_id
          AND e.tip = :tip
          AND c.geyimde = 1
          AND c.say > 0
        LIMIT 1
    ");

    $stmt_kohne->execute([
        ':user_id' => $my_id,
        ':tip' => $tip
    ]);
}

$kohne_esya = $stmt_kohne->fetch(PDO::FETCH_ASSOC);

if ($kohne_esya) {

    $kohne_esya_id = (int)$kohne_esya['id'];

    $stmt_cixar = $pdo->prepare("
       UPDATE canta
SET geyimde = 0,
    son_cixarma_vaxti = :vaxt,
    son_daxil_olma_vaxti = :vaxt
        WHERE id = :id
          AND user_id = :user_id
          AND geyimde = 1
          AND say > 0
        LIMIT 1
    ");

    $stmt_cixar->execute([
        ':id' => $kohne_esya_id,
        ':user_id' => $my_id,
        ':vaxt' => time()
    ]);
}


            $stmt_geyindir = $pdo->prepare("
                UPDATE canta
                SET geyimde = 1
                WHERE id = :id
                  AND user_id = :user_id
                  AND say > 0
                LIMIT 1
            ");

            $stmt_geyindir->execute([
                ':id' => $idi,
                ':user_id' => $my_id
            ]);

            if ($stmt_geyindir->rowCount() < 1) {
                throw new Exception('Əşya geyinmədi.');
            }

            $pdo->commit();

            oyuncuParametrleriniYenile($pdo, $my_id);

            $geyin_ok = true;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $geyin_xeta = $e->getMessage();
        }

    } else {
        $geyin_xeta = 'Əməliyyat düzgün deyil.';
    }
}


/* =========================================================
   NORMAL SATIŞ
========================================================= */

if (
    $go === 'sat' &&
    isset($_GET['satildi']) &&
    $_GET['satildi'] === 'ok'
) {

    $idi = (int)($_GET['idi'] ?? 0);

    if ($idi > 0 && $my_id > 0) {

        try {

            $pdo->beginTransaction();

            $stmt_sat_esya = $pdo->prepare("
                SELECT
                    c.id,
                    c.esya_id,
                    c.say,
                    c.geyimde,
                    e.ad,
                    e.tip,
                    e.qiymet
                FROM canta c
                INNER JOIN esyalar e ON e.id = c.esya_id
                WHERE c.id = :id
                  AND c.user_id = :user_id
                  AND c.say > 0
                  AND c.geyimde = 0
                LIMIT 1
            ");

            $stmt_sat_esya->execute([
                ':id' => $idi,
                ':user_id' => $my_id
            ]);

            $sat_esya = $stmt_sat_esya->fetch(PDO::FETCH_ASSOC);

            if (!$sat_esya) {
                throw new Exception('Bu növ əşya tapılmadı.');
            }

            if ($sat_esya['tip'] === 'almaz') {

                $sat_almaz = true;
                $sat_brilyant = (int)$sat_esya['qiymet'];

            } else {

                $sat_qiymet = (int)floor(
                    ((int)$sat_esya['qiymet']) / 2
                );
            }


            $stmt_sil = $pdo->prepare("
                DELETE FROM canta
                WHERE id = :id
                  AND user_id = :user_id
                  AND say > 0
                  AND geyimde = 0
                LIMIT 1
            ");

            $stmt_sil->execute([
                ':id' => $idi,
                ':user_id' => $my_id
            ]);

            if ($stmt_sil->rowCount() < 1) {
                throw new Exception('Əşya satıla bilmədi.');
            }


            if ($sat_almaz) {

                $stmt_pul = $pdo->prepare("
                    UPDATE users
                    SET `brılyant` = `brılyant` + :qiymet
                    WHERE id = :user_id
                    LIMIT 1
                ");

                $stmt_pul->execute([
                    ':qiymet' => $sat_brilyant,
                    ':user_id' => $my_id
                ]);

            } else {

                $stmt_pul = $pdo->prepare("
                    UPDATE users
                    SET `qızıl` = `qızıl` + :qiymet
                    WHERE id = :user_id
                    LIMIT 1
                ");

                $stmt_pul->execute([
                    ':qiymet' => $sat_qiymet,
                    ':user_id' => $my_id
                ]);
            }

            $pdo->commit();

            $sat_ok = true;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $sat_xeta = $e->getMessage();
        }

    } else {
        $sat_xeta = 'Əməliyyat düzgün deyil.';
    }
}


/* =========================================================
   AUKSİON FORMU
   GET:
   chantam.php?go=auksion&idi=1152029

   POST:
   chantam.php?go=auksion&idi=1152029
========================================================= */

if ($go === 'auksion') {

    $auksion_id = (int)($_GET['idi'] ?? 0);

    /*
     * Əvvəlcə əşyanı tapırıq.
     */
    if ($auksion_id > 0 && $my_id > 0) {

        $stmt_auksion_form = $pdo->prepare("
            SELECT
                c.id,
                c.user_id,
                c.esya_id,
                c.say,
                c.geyimde,
                e.ad,
                e.seviyye,
                e.reng,
                e.img,
                e.tip,
                e.qiymet
            FROM canta c
            INNER JOIN esyalar e ON e.id = c.esya_id
            WHERE c.id = :id
              AND c.user_id = :user_id
              AND c.say > 0
              AND c.geyimde = 0
            LIMIT 1
        ");

        $stmt_auksion_form->execute([
            ':id' => $auksion_id,
            ':user_id' => $my_id
        ]);

        $auksion_form_esya = $stmt_auksion_form->fetch(PDO::FETCH_ASSOC);


        if (!$auksion_form_esya) {

            $auksion_xeta =
                'Auksiona çıxarılacaq əşya tapılmadı.';

        } else {

            /*
             * =================================================
             * SATIŞA ÇIXART FORMUNU GÖNDƏRİB
             * =================================================
             */

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                $auksion_min = isset($_POST['alish_min'])
                    ? (int)$_POST['alish_min']
                    : 0;

                $auksion_max = isset($_POST['alish_max'])
                    ? (int)$_POST['alish_max']
                    : 0;

                $auksion_muddet = isset($_POST['aa'])
                    ? (int)$_POST['aa']
                    : 0;


                /*
                 * Mənfi qiymətlərə icazə vermirik.
                 */
                if ($auksion_min < 0) {
                    $auksion_min = 0;
                }

                if ($auksion_max < 0) {
                    $auksion_max = 0;
                }


                /*
                 * Max qiymət Min-dən aşağı ola bilməz.
                 */
                if ($auksion_max < $auksion_min) {

                    $auksion_xeta =
                        'Maksimum qiymət minimum qiymətdən aşağı ola bilməz.';

                } else {

                    /*
                     * Müddətlər:
                     *
                     * 0 = 3 saat
                     * 1 = 6 saat
                     * 2 = 12 saat
                     * 3 = 1 gün
                     * 4 = 2 gün
                     */
                    $muddet_saat = [
                        0 => 3,
                        1 => 6,
                        2 => 12,
                        3 => 24,
                        4 => 48
                    ];

                    if (!isset($muddet_saat[$auksion_muddet])) {

                        $auksion_xeta =
                            'Auksion müddəti düzgün deyil.';

                    } else {

                        try {

                            $pdo->beginTransaction();


                            /*
                             * Əşyanı yenidən yoxlayırıq.
                             * Bu vacibdir ki, istifadəçi eyni linki
                             * iki dəfə işlədə bilməsin.
                             */
                            $stmt_yoxla = $pdo->prepare("
                                SELECT
                                    c.id,
                                    c.user_id,
                                    c.esya_id,
                                    c.say,
                                    c.geyimde
                                FROM canta c
                                WHERE c.id = :id
                                  AND c.user_id = :user_id
                                  AND c.say > 0
                                  AND c.geyimde = 0
                                LIMIT 1
                            ");

                            $stmt_yoxla->execute([
                                ':id' => $auksion_id,
                                ':user_id' => $my_id
                            ]);

                            $auksion_yoxla =
                                $stmt_yoxla->fetch(PDO::FETCH_ASSOC);


                            if (!$auksion_yoxla) {

                                throw new Exception(
                                    'Əşya artıq çantada yoxdur.'
                                );
                            }


                            /*
 * Hazırkı vaxt
 */
$tarix = time();


/*
 * Bitmə vaxtı
 */
$bitme_vaxti = time() + (
    $muddet_saat[$auksion_muddet]
    * 60
    * 60
);


/*
 * AUKSİONA YAZ
 */
$stmt_insert = $pdo->prepare("
    INSERT INTO auksion
    (
        user_id,
        esya_id,
        say,
        qiymet_min,
        qiymet_max,
        tarix,
        bitme_vaxti,
        aktiv
    )
    VALUES
    (
        :user_id,
        :esya_id,
        :say,
        :qiymet_min,
        :qiymet_max,
        :tarix,
        :bitme_vaxti,
        1
    )
");

$stmt_insert->execute([
    ':user_id'     => $my_id,
    ':esya_id'     => (int)$auksion_yoxla['esya_id'],
    ':say'         => (int)$auksion_yoxla['say'],
    ':qiymet_min'  => $auksion_min,
    ':qiymet_max'  => $auksion_max,
    ':tarix'       => $tarix,
    ':bitme_vaxti' => $bitme_vaxti
]);


/*
 * ƏŞYANI ÇANTADAN SİL
 *
 * Əşya artıq auksion cədvəlinə yazılıb.
 * Ona görə çantadan silinir.
 */
$stmt_sil_auksion = $pdo->prepare("
    DELETE FROM canta
    WHERE id = :id
      AND user_id = :user_id
      AND say > 0
      AND geyimde = 0
    LIMIT 1
");

$stmt_sil_auksion->execute([
    ':id'      => $auksion_id,
    ':user_id' => $my_id
]);


if ($stmt_sil_auksion->rowCount() < 1) {

    throw new Exception(
        'Əşya çantadan çıxarıla bilmədi.'
    );
}


$pdo->commit();

$auksion_ok = true;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $auksion_xeta = $e->getMessage();
}

} // if isset($muddet_saat...)
} // if $auksion_max >= $auksion_min
} // if REQUEST_METHOD === POST
} // if $auksion_form_esya
} // if $auksion_id > 0 && $my_id > 0
} // if $go === 'auksion'


/* =========================================================
   ƏŞYA INFO
========================================================= */

if ($go === 'info') {

    $rid = (int)($_GET['rid'] ?? 0);

    if ($rid > 0) {

        $stmt_info = $pdo->prepare("
            SELECT
                c.id AS canta_id,
                c.esya_id,

                e.id AS esya_id,
                e.ad,
                e.seviyye,
                e.reng,
                e.img,
                e.tip,
                e.sekil,
                e.qiymet,
                e.davamliliq,

                c.random_min_zerbe,
                c.random_max_zerbe,
                c.random_can,
                c.random_max_can,
                c.random_mudafie,
                c.random_max_mudafie,
                c.random_krit,
                c.random_anti_krit,
                c.random_uvorot,
                c.random_anti_uvorot,

                c.random_krit_faiz,
                c.random_anti_krit_faiz,
                c.random_uvorot_faiz,
                c.random_anti_uvorot_faiz,

                e.min_zerbe,
                e.max_zerbe,
                e.can,
                e.mudafie,
                e.krit,
                e.anti_krit,
                e.uvorot,
                e.anti_uvorot

            FROM canta c

            INNER JOIN esyalar e
                ON e.id = c.esya_id

          WHERE c.say > 0
  AND c.id = :rid
  AND c.user_id = :user_id

            LIMIT 1
        ");

        $stmt_info->execute([
            ':rid' => $rid,
            ':user_id' => $my_id
        ]);

        $info_esya = $stmt_info->fetch(PDO::FETCH_ASSOC);
    }
}


/* =========================================================
   SƏHİFƏLƏMƏ
========================================================= */

$start = (int)($_GET['s'] ?? 0);

if ($start < 0) {
    $start = 0;
}


/* =========================================================
   ÜMUMİ ƏŞYA SAYI
========================================================= */

$stmt_count = $pdo->prepare("
    SELECT COALESCE(SUM(c.say), 0)
    FROM canta c
    INNER JOIN esyalar e ON e.id = c.esya_id
    WHERE c.user_id = :user_id
      AND c.say > 0
      AND c.geyimde = 0
      AND e.tip != 'mecun'
");

$stmt_count->execute([
    ':user_id' => $my_id
]);

$esya_sayi =
    (int)$stmt_count->fetchColumn();


/* =========================================================
   MECUN SAYI
========================================================= */

$stmt_mecun_count = $pdo->prepare("
    SELECT COALESCE(SUM(c.say), 0)
    FROM canta c
    INNER JOIN esyalar e ON e.id = c.esya_id
    WHERE c.user_id = :user_id
      AND c.say > 0
      AND e.tip = 'mecun'
");

$stmt_mecun_count->execute([
    ':user_id' => $my_id
]);

$mecun_sayi =
    (int)$stmt_mecun_count->fetchColumn();


/* =========================================================
   ƏŞYALAR
========================================================= */

$esyalar = [];

if ($go === 'eshya') {

    $stmt_esyalar = $pdo->prepare("
        SELECT
            ue.id,
            ue.user_id,
            ue.esya_id,
            ue.say,
            ue.geyimde,
            e.ad,
            e.seviyye,
            e.reng,
            e.img,
            e.tip,
            e.sekil
        FROM canta ue
        INNER JOIN esyalar e ON e.id = ue.esya_id
        WHERE ue.user_id = :user_id
          AND ue.say > 0
          AND ue.geyimde = 0
          AND e.tip != 'mecun'
     ORDER BY ue.son_daxil_olma_vaxti DESC,
         ue.son_cixarma_vaxti DESC,
         ue.id DESC
        LIMIT 10 OFFSET :start
    ");

    $stmt_esyalar->bindValue(
        ':user_id',
        $my_id,
        PDO::PARAM_INT
    );

    $stmt_esyalar->bindValue(
        ':start',
        $start,
        PDO::PARAM_INT
    );

    $stmt_esyalar->execute();

    $esyalar =
        $stmt_esyalar->fetchAll(PDO::FETCH_ASSOC);
}


/* =========================================================
   MECUNLAR
========================================================= */

$mecunlar = [];

if ($go === 'mecun') {

    $stmt_mecunlar = $pdo->prepare("
        SELECT
            ue.id,
            ue.user_id,
            ue.esya_id,
            ue.say,
            ue.geyimde,
            e.ad,
            e.seviyye,
            e.reng,
            e.img,
            e.tip,
            e.sekil
        FROM canta ue
        INNER JOIN esyalar e ON e.id = ue.esya_id
        WHERE ue.user_id = :user_id
          AND ue.say > 0
          AND e.tip = 'mecun'
        ORDER BY ue.id DESC
        LIMIT 10 OFFSET :start
    ");

    $stmt_mecunlar->bindValue(
        ':user_id',
        $my_id,
        PDO::PARAM_INT
    );

    $stmt_mecunlar->bindValue(
        ':start',
        $start,
        PDO::PARAM_INT
    );

    $stmt_mecunlar->execute();

    $mecunlar =
        $stmt_mecunlar->fetchAll(PDO::FETCH_ASSOC);
}


/* =========================================================
   NÖVBƏTİ / GERİ
========================================================= */

$novbeti_var = false;
$geri_var = false;

if ($go === 'eshya') {

    if (count($esyalar) == 10) {
        $novbeti_var = true;
    }

    if ($start > 0) {
        $geri_var = true;
    }
}

?>

<!DOCTYPE html>
<html>

<head>

<meta name="robots" content="ALL">

<meta
    name="keywords"
    content="klan.az, azgame, online oyun, qrup doyusleri, klan doyusleri"
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
    content="width=device-width, initial-scale=1.0, maximum-scale=3.0"
>

<title>Chantam</title>

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

<img src="img/logo.png" alt="">

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

<div style="background:none repeat scroll 0 0 #888686;height:1px;"></div>


<!-- =====================================================
     EXP
===================================================== -->

<div class="fl b exp_count">

<div style="margin-top:-2px;">

<span style="color:#ff3333">

<b><?php echo $progress; ?>%</b>

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


<div style="background:none repeat scroll 0 0 #888686;height:1px;"></div>


<div class="info">


<?php
/* =====================================================
   AUKSİON NƏTİCƏSİ
===================================================== */

if ($go === 'auksion' && $auksion_ok):
?>

<br>

<div class="info">

<div class="list1">

<b>Əməliyyat yerinə yetirildi</b>

<br><br>

Siz əşyanı auksiona çıxartdınız.

<br><br>

<hr>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<br>

<div class="menu">

<br>

<li>
<a href="auksion.php?">
<img src="muxtelif/auction.png" alt="">
Auksion
</a>
</li>

<li>
<a href="auksion.php?go=menim">
<img src="muxtelif/auction.png" alt="">
Mənim Auksionum
</a>
</li>

<li>
<a href="chantam.php?go=satish&amp;satiw=auksion">
<img src="muxtelif/sandiq.png" alt="">
Satış Yeri
</a>
</li>

</div>

</div>

</div>


<?php
/* =====================================================
   AUKSİON XƏTASI
===================================================== */

elseif ($go === 'auksion' && $auksion_xeta !== '' && !$auksion_form_esya):
?>

<br>

<div class="error">

<img src="muxtelif/error.png" alt="">

<span style="color:#DA1515;">

<?php
echo htmlspecialchars(
    $auksion_xeta,
    ENT_QUOTES,
    'UTF-8'
);
?>

</span>

</div>

<br>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<div class="line"></div>


<?php
/* =====================================================
   AUKSİON FORMU
===================================================== */

elseif ($go === 'auksion' && $auksion_form_esya):
?>

<br>

<div class="info">

<div class="list1">


<?php if ($auksion_xeta !== ''): ?>

<div class="error">

<img src="muxtelif/error.png" alt="">

<span style="color:#DA1515;">

<?php
echo htmlspecialchars(
    $auksion_xeta,
    ENT_QUOTES,
    'UTF-8'
);
?>

</span>

</div>

<br>

<?php endif; ?>


<?php if (!empty($auksion_form_esya['img'])): ?>

<img
    src="<?php
    echo htmlspecialchars(
        $auksion_form_esya['img'],
        ENT_QUOTES,
        'UTF-8'
    );
    ?>"
    alt="foto"
/>

<?php endif; ?>


&#187;

<?php

$auksion_reng = esya_reng($auksion_form_esya['reng']);

if ($auksion_form_esya['tip'] === 'almaz') {

    $almaz_ad = mb_strtolower(
        trim((string)$auksion_form_esya['ad']),
        'UTF-8'
    );

    if (
        strpos($almaz_ad, 'ag almaz') !== false ||
        strpos($almaz_ad, 'ağ almaz') !== false
    ) {
        $auksion_reng = '#FFFFFF';

    } elseif (
        strpos($almaz_ad, 'qara almaz') !== false
    ) {
        $auksion_reng = '#000000';

    } elseif (
        strpos($almaz_ad, 'yasil almaz') !== false ||
        strpos($almaz_ad, 'yaşıl almaz') !== false
    ) {
        $auksion_reng = '#000000';

    } elseif (
        strpos($almaz_ad, 'goy almaz') !== false ||
        strpos($almaz_ad, 'göy almaz') !== false
    ) {
        $auksion_reng = '#0088FF';

    } elseif (
        strpos($almaz_ad, 'sari almaz') !== false ||
        strpos($almaz_ad, 'sarı almaz') !== false
    ) {
        $auksion_reng = '#FFD700';

    } elseif (
        strpos($almaz_ad, 'qirmizi almaz') !== false ||
        strpos($almaz_ad, 'qırmızı almaz') !== false
    ) {
        $auksion_reng = '#FF0000';
    }
}

?>

<font
    color="<?php
    echo htmlspecialchars(
        $auksion_reng,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>"
>

<?php
echo htmlspecialchars(
    $auksion_form_esya['ad'],
    ENT_QUOTES,
    'UTF-8'
);
?>

</font>

<br><br>


<form
    method="post"
    action="chantam.php?go=auksion&amp;idi=<?php
    echo (int)$auksion_form_esya['id'];
    ?>"
>


<b>Ne qeder qızıla?</b>

<br>

Min.

<input
    type="text"
    size="8"
    name="alish_min"
    maxlength="7"
    value="<?php
    echo (int)$auksion_min;
    ?>"
>

<br>

Max.

<input
    type="text"
    size="8"
    name="alish_max"
    maxlength="7"
    value="<?php
    echo (int)$auksion_max;
    ?>"
>

<br><br>


<b>Müddət:</b>

<br>

<select name="aa">

<option
    value="0"
    <?php
    echo $auksion_muddet === 0
        ? 'selected'
        : '';
    ?>
>
3 saat
</option>

<option
    value="1"
    <?php
    echo $auksion_muddet === 1
        ? 'selected'
        : '';
    ?>
>
6 saat
</option>

<option
    value="2"
    <?php
    echo $auksion_muddet === 2
        ? 'selected'
        : '';
    ?>
>
12 saat
</option>

<option
    value="3"
    <?php
    echo $auksion_muddet === 3
        ? 'selected'
        : '';
    ?>
>
1 gün
</option>

<option
    value="4"
    <?php
    echo $auksion_muddet === 4
        ? 'selected'
        : '';
    ?>
>
2 gün
</option>

</select>

<br><br>


<input
    type="hidden"
    name="action"
    value="save"
>


<input
    type="submit"
    class="button_big"
    value="Satışa çıxart"
>


</form>


<br>

<hr>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>


<div class="menu">

<br>

<li>

<a href="auksion.php?">

<img
    src="muxtelif/auction.png"
    alt=""
>

Auksion

</a>

</li>


<li>

<a href="auksion.php?go=menim">

<img
    src="muxtelif/auction.png"
    alt=""
>

Mənim Auksionum

</a>

</li>


<li>

<a href="chantam.php?go=satish&amp;satiw=auksion">

<img
    src="muxtelif/sandiq.png"
    alt=""
>

Satış Yeri

</a>

</li>

</div>


</div>

</div>


<?php
/* =====================================================
   SATIŞ NƏTİCƏSİ
===================================================== */

elseif (
    $go === 'sat' &&
    isset($_GET['satildi']) &&
    $_GET['satildi'] === 'ok'
):
?>

<?php if ($sat_ok): ?>

<div class="info">

Bu əşyanı

<b>

<?php

echo $sat_almaz
    ? $sat_brilyant
    : $sat_qiymet;

?>

</b>

<?php

echo $sat_almaz
    ? 'brilliant'
    : 'qızıla';

?>

satdınız.

<br>

<div class="line"></div>

</div>

<?php else: ?>

<div class="info">

<?php

echo htmlspecialchars(
    $sat_xeta,
    ENT_QUOTES,
    'UTF-8'
);

?>

<br>

<div class="line"></div>

</div>

<?php endif; ?>


<div class="menu">

<li>
<a href="chantam.php?">
Eşya çantası
</a>
</li>

<li>
<a href="infoforce.php?uid=<?php echo $my_id; ?>">
Mənim döyüşçüm
</a>
</li>

</div>


<?php
/* =====================================================
   ÇIXART NƏTİCƏSİ
===================================================== */

elseif ($go === 'chixart'):
?>

<br>

<?php if ($chixart_ok): ?>

<div class="success">

<img src="muxtelif/okey.png" alt="">

<span style="color:#259C00;">

<?php

if ($chixart_tip === 'almaz') {

    echo 'Almaz';

} elseif ($chixart_tip === 'mecun') {

    echo 'Mecun';

} else {

    echo htmlspecialchars(
        $chixart_ad,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

çantaya qoyuldu.

</span>

<br>

</div>

<br>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="window.location.href='chantam.php?go=eshya';"
>

<div class="line"></div>

<br>

<?php else: ?>

<div class="error">

<img src="muxtelif/error.png" alt="">

<span style="color:#DA1515;">

<?php

echo htmlspecialchars(
    $chixart_xeta,
    ENT_QUOTES,
    'UTF-8'
);

?>

</span>

</div>

<br>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="window.location.href='chantam.php?go=eshya';"
>

<div class="line"></div>

<br>

<?php endif; ?>


<div class="menu">

<li>
<a href="chantam.php?">
Eşya çantası
</a>
</li>

<li>
<a href="infoforce.php?uid=<?php echo $my_id; ?>">
Mənim döyüşçüm
</a>
</li>

</div>


<?php
/* =====================================================
   GEYİN NƏTİCƏSİ
===================================================== */

elseif ($go === 'geyin'):
?>

<br>

<?php if ($geyin_ok): ?>

<div class="success">

<img src="muxtelif/okey.png" alt="">

<span style="color:#259C00;">

Bu əşyanı geyindiniz:

<b>

<?php

echo htmlspecialchars(
    $geyin_ad,
    ENT_QUOTES,
    'UTF-8'
);

?>

</b>

</span>

<br>

</div>


<?php if ($kohne_geyin_ad !== ''): ?>

<br>

<div class="error">

<img src="muxtelif/error.png" alt="">

<span style="color:#DA1515;">

Üstünüzdəki

<b>

<?php

echo htmlspecialchars(
    $kohne_geyin_ad,
    ENT_QUOTES,
    'UTF-8'
);

?>

</b>

çantaya göndərildi.

</span>

</div>

<?php endif; ?>


<br>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="window.location.href='chantam.php?go=eshya';"
>

<div class="line"></div>

<?php else: ?>

<div class="error">

<img src="muxtelif/error.png" alt="">

<span style="color:#DA1515;">

<?php

echo htmlspecialchars(
    $geyin_xeta,
    ENT_QUOTES,
    'UTF-8'
);

?>

</span>

</div>

<br>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="window.location.href='chantam.php?go=eshya';"
>

<div class="line"></div>

<?php endif; ?>


<div class="menu">

<br>

<li>

<a href="chantam.php?">

<img
    src="muxtelif/sandiq.png"
    alt=""
>

Eşya çantası

</a>

</li>

<li>

<a href="infoforce.php?uid=<?php echo $my_id; ?>">

<img
    src="muxtelif/doyuscu1.png"
    alt=""
>

Mənim döyüşçüm

</a>

</li>

</div>


<?php
/* =====================================================
   SATIŞ TƏSDİQİ
===================================================== */

elseif (
    $go === 'sat' &&
    !isset($_GET['satildi'])
):
?>

<?php

$sat_id =
    (int)($_GET['idi'] ?? 0);

$sat_goster_qiymet = 0;
$sat_goster_almaz = false;

if ($sat_id > 0 && $my_id > 0) {

    $stmt_goster_sat = $pdo->prepare("
        SELECT
            e.tip,
            e.qiymet
        FROM canta c
        INNER JOIN esyalar e ON e.id = c.esya_id
        WHERE c.id = :id
          AND c.user_id = :user_id
          AND c.say > 0
          AND c.geyimde = 0
        LIMIT 1
    ");

    $stmt_goster_sat->execute([
        ':id' => $sat_id,
        ':user_id' => $my_id
    ]);

    $goster_sat =
        $stmt_goster_sat->fetch(PDO::FETCH_ASSOC);

    if ($goster_sat) {

        if ($goster_sat['tip'] === 'almaz') {

            $sat_goster_almaz = true;

            $sat_goster_qiymet =
                (int)$goster_sat['qiymet'];

        } else {

            $sat_goster_qiymet =
                (int)floor(
                    ((int)$goster_sat['qiymet']) / 2
                );
        }
    }
}

?>

Dükan bunu sizdən yarı qiymətə alır
(<b><?php echo $sat_goster_qiymet; ?></b>

<?php

echo $sat_goster_almaz
    ? 'brilliant'
    : 'qızıla';

?>

),
siz həqiqətən satmaq istəyirsiniz?

<br>

<a
    href="chantam.php?go=sat&amp;satildi=ok&amp;idi=<?php
    echo $sat_id;
    ?>"
>
He
</a>

/

<a href="chantam.php?">
Yox
</a>

<br>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

<div class="line"></div>

<br>

<div class="menu">

<li>
<a href="chantam.php?">
Eşya çantası
</a>
</li>

<li>
<a href="infoforce.php?uid=<?php echo $my_id; ?>">
Mənim döyüşçüm
</a>
</li>

</div>


<?php
/* =====================================================
   INFO
===================================================== */

elseif ($go === 'info'):

if (!empty($info_esya)):

$info_reng =
    esya_reng($info_esya['reng']);

?>
<?php

$krit = (int)$info_esya['krit'];
$anti_krit = (int)$info_esya['anti_krit'];
$uvorot = (int)$info_esya['uvorot'];
$anti_uvorot = (int)$info_esya['anti_uvorot'];
$krit_faiz = (int)$info_esya['random_krit_faiz'];
$anti_krit_faiz = (int)$info_esya['random_anti_krit_faiz'];
$uvorot_faiz = (int)$info_esya['random_uvorot_faiz'];
$anti_uvorot_faiz = (int)$info_esya['random_anti_uvorot_faiz'];



/*
 * Yalnız GÖY və YAŞIL əşyalar random olacaq.
 * Random qiymət canta cədvəlində saxlanılan
 * random_* qiymətindən götürülür.
 *
 * Sarı və digər rənglər dəyişmir.
 */

$info_reng_ad = mb_strtolower(
    trim((string)$info_esya['reng']),
    'UTF-8'
);

if (
    $info_reng_ad === 'goy' ||
    $info_reng_ad === 'göy' ||
    $info_reng_ad === 'yasil' ||
    $info_reng_ad === 'yaşıl'
) {

    $krit = (int)$info_esya['random_krit'];

    $anti_krit = (int)$info_esya['random_anti_krit'];

    $uvorot = (int)$info_esya['random_uvorot'];

    $anti_uvorot = (int)$info_esya['random_anti_uvorot'];
}

?>
<div class="content">

<table
    border="0"
    cellpadding="2"
    cellspacing="0"
>

<tr>

<td>

<?php if (!empty($info_esya['img'])): ?>

<img
    src="<?php
    echo htmlspecialchars(
        $info_esya['img'],
        ENT_QUOTES,
        'UTF-8'
    );
    ?>"
    alt="foto"
>

<?php endif; ?>

</td>

<td>

<b>Ad:</b>

<b>
<?php
$guclendirme_sayi = 0;

$stmt_guclendirme = $pdo->prepare("
   SELECT
    guclendirme_sayi,
    guclendirme_faizi,
    guclendirilmis_min_zerbe,
    guclendirilmis_max_zerbe,
    guclendirilmis_can,
    guclendirilmis_mudafie
    FROM esya_guclendirme
    WHERE canta_id = :canta_id
    LIMIT 1
");
$stmt_guclendirme->execute([
    ':canta_id' => $info_esya['canta_id']
]);

$guclendirme = $stmt_guclendirme->fetch(PDO::FETCH_ASSOC);

if ($guclendirme) {
    $guclendirme_sayi = (int)$guclendirme['guclendirme_sayi'];
}


?>
<?php

echo htmlspecialchars(
    $info_esya['ad'],
    ENT_QUOTES,
    'UTF-8'
);



?>

</b>

<br>

<b>İd nömresi:</b>

[<?php echo (int)$info_esya['canta_id']; ?>]

<br>

<b>Merhele:</b>

<?php echo (int)$info_esya['seviyye']; ?>

<br>

</td>

</tr>

</table>

</div>


<p>

<b>Tapıb:</b> Rehber

<br>

<b>Mexsusdur:</b> ***YALQUZAQ***

<br><br>

<?php if ($guclendirme_sayi > 0): ?>

<b>Güclendirilib:</b>
+<?php echo (int)$guclendirme_sayi; ?>
(<?php echo (int)($guclendirme['guclendirme_faizi'] ?? 0); ?>%)

<br>

<?php endif; ?>


</p>


<div class="line"></div>

<br>
<div class="center">

<div class="block_line">

Eşyanın göstəriciləri

</div>

</div>

<br>

<p>

<?php if (
    $info_esya['tip'] === 'qilinc' ||
    $info_esya['tip'] === 'balta' ||
    $info_esya['tip'] === 'uzuk' ||
    $info_esya['tip'] === 'gurz'
): ?>


<b>Mini.zerbe:</b>

<?php
if ($guclendirme && (int)$guclendirme['guclendirme_sayi'] > 0) {
    echo (int)$guclendirme['guclendirilmis_min_zerbe'];
} elseif ((int)$info_esya['random_min_zerbe'] > 0) {
    echo (int)$info_esya['random_min_zerbe'];
} else {
    echo (int)$info_esya['min_zerbe'];
}
?>

<br>

<b>Maks.zerbe:</b>

<?php
if ($guclendirme && (int)$guclendirme['guclendirme_sayi'] > 0) {
    echo (int)$guclendirme['guclendirilmis_max_zerbe'];
} elseif ((int)$info_esya['random_max_zerbe'] > 0) {
    echo (int)$info_esya['random_max_zerbe'];
} else {
    echo (int)$info_esya['max_zerbe'];
}
?>

<br>

<?php endif; ?>


<b>Can:</b>

+<?php
if ($guclendirme && (int)$guclendirme['guclendirme_sayi'] > 0) {
    echo (int)$guclendirme['guclendirilmis_can'];
} elseif (
    (int)$info_esya['random_can'] > 0 ||
    (int)$info_esya['random_max_can'] > 0
) {
    echo (int)$info_esya['random_can'];
} else {
    echo (int)$info_esya['can'];
}
?>

<br>


<b>Müdafie:</b>

+<?php
if ($guclendirme && (int)$guclendirme['guclendirme_sayi'] > 0) {
    echo (int)$guclendirme['guclendirilmis_mudafie'];
} elseif (
    (int)$info_esya['random_mudafie'] > 0 ||
    (int)$info_esya['random_max_mudafie'] > 0
) {
    echo (int)$info_esya['random_mudafie'];
} else {
    echo (int)$info_esya['mudafie'];
}
?>

<br>

<br>


<b>Krit:</b>

+<?php
if (
    $info_esya['reng'] === 'goy' ||
    $info_esya['reng'] === 'göy' ||
    $info_esya['reng'] === 'yasil' ||
    $info_esya['reng'] === 'yaşıl'
) {
    echo (int)$info_esya['random_krit'];
} else {
    echo (int)$info_esya['krit'];
}
?>

| (<?php echo $krit_faiz; ?>%)


<br>

<b>Anti Krit:</b>

+<?php
if (
    $info_esya['reng'] === 'goy' ||
    $info_esya['reng'] === 'göy' ||
    $info_esya['reng'] === 'yasil' ||
    $info_esya['reng'] === 'yaşıl'
) {
    echo (int)$info_esya['random_anti_krit'];
} else {
    echo (int)$info_esya['anti_krit'];
}
?>

| (<?php echo $anti_krit_faiz; ?>%)


<br>

<b>Uvorot:</b>

+<?php
if (
    $info_esya['reng'] === 'goy' ||
    $info_esya['reng'] === 'göy' ||
    $info_esya['reng'] === 'yasil' ||
    $info_esya['reng'] === 'yaşıl'
) {
    echo (int)$info_esya['random_uvorot'];
} else {
    echo (int)$info_esya['uvorot'];
}
?>

| (<?php echo $uvorot_faiz; ?>%)



<br>

<b>Anti Uvorot:</b>

+<?php
if (
    $info_esya['reng'] === 'goy' ||
    $info_esya['reng'] === 'göy' ||
    $info_esya['reng'] === 'yasil' ||
    $info_esya['reng'] === 'yaşıl'
) {
    echo (int)$info_esya['random_anti_uvorot'];
} else {
    echo (int)$info_esya['anti_uvorot'];
}
?>

| (<?php echo $anti_uvorot_faiz; ?>%)



</p>

<div class="line"></div>

<p>

<b>Qiymet:</b>

<?php echo (int)$info_esya['qiymet']; ?>

Qızıl

<br>

<b>Temirde Olmayıb:</b>

(0 / 2)

<br>

<b>Davamlılıq:</b>

<?php echo (int)$info_esya['davamliliq']; ?>

(100%)

<br>

<?php if ((int)$info_esya['davamliliq'] > 0): ?>

<img
    src="img/davamliliq.png"
    alt=""
    style="margin:1px;"
>

<br>

<?php endif; ?>

</p>

<br>

<div class="menu">

<li>

<a href="eshyalar.php">

<img
    src="muxtelif/dukan.png"
    alt=""
>

Mərkəzi Dükan

</a>

</li>

</div>


<?php else: ?>

<div class="content">

Əşya tapılmadı.

</div>

<br>

<div class="menu">

<li>

<a href="chantam.php?go=eshya">

Eşyalar

</a>

</li>

</div>

<?php endif; ?>


<?php
/* =====================================================
   ƏŞYALAR
===================================================== */

elseif ($go === 'eshya'):
?>

<div class="battle_log">

<div class="content">

Eşyaların sayı:
(<?php echo $esya_sayi; ?>)

|

Qutunun tutumu:
(<?php echo (int)$canta_tutumu; ?>)

</div>

</div>

<br>

<?php if (empty($esyalar)): ?>

<div class="battle_log">

<div class="content">

Çantada əşya yoxdur.

</div>

</div>

<?php else: ?>

<?php foreach ($esyalar as $esya): ?>

<?php

$reng = esya_reng($esya['reng']);

if (in_array((int)$esya['esya_id'], [404, 405, 406], true)) {
    $reng = '#FFD700';
}

if ($esya['tip'] === 'almaz') {

    $almaz_ad = mb_strtolower(
        trim((string)$esya['ad']),
        'UTF-8'
    );

    if (
        strpos($almaz_ad, 'ag almaz') !== false ||
        strpos($almaz_ad, 'ağ almaz') !== false
    ) {

        $reng = '#000000';

    } elseif (
        strpos($almaz_ad, 'qara almaz') !== false
    ) {

        $reng = '#000000';

    } elseif (
        strpos($almaz_ad, 'yasil almaz') !== false ||
        strpos($almaz_ad, 'yaşıl almaz') !== false
    ) {

        $reng = '#000000';

    } elseif (
        strpos($almaz_ad, 'goy almaz') !== false ||
        strpos($almaz_ad, 'göy almaz') !== false
    ) {

        $reng = '#0088FF';

    } elseif (
        strpos($almaz_ad, 'sari almaz') !== false ||
        strpos($almaz_ad, 'sarı almaz') !== false
    ) {

        $reng = '#FFD700';

    } elseif (
        strpos($almaz_ad, 'qirmizi almaz') !== false ||
        strpos($almaz_ad, 'qırmızı almaz') !== false
    ) {

        $reng = '#FF0000';
    }
}
?>

<div class="battle_log">

<div class="content">

<table
    border="0"
    cellpadding="2"
    cellspacing="0"
>

<tr>

<td>

<?php if (!empty($esya['img'])): ?>

<img
    src="<?php
    echo htmlspecialchars(
        $esya['img'],
        ENT_QUOTES,
        'UTF-8'
    );
    ?>"
    alt="foto"
>

<?php endif; ?>

</td>

<td>

<u>

<a
    href="<?php
    if (in_array((int)$esya['esya_id'], [404, 405, 406], true)) {
        echo 'eshyalar.php?go=m_info&rid=' . (int)$esya['esya_id'];
    } elseif ($esya['tip'] === 'almaz') {
        echo 'eshyalar.php?go=c_info&rid=' . (int)$esya['esya_id'];
     } else {
    echo 'chantam.php?go=info&rid=' . (int)$esya['id'];
}

    ?>"
>

<span
    style="color: <?php echo htmlspecialchars($reng, ENT_QUOTES, 'UTF-8'); ?> !important;"
>
<?php
echo htmlspecialchars(
    $esya['ad'],
    ENT_QUOTES,
    'UTF-8'
);

$stmt_canta_guclendirme = $pdo->prepare("
    SELECT guclendirme_sayi, guclendirme_faizi
    FROM esya_guclendirme
    WHERE canta_id = :canta_id
    LIMIT 1
");

$stmt_canta_guclendirme->execute([
    ':canta_id' => (int)$esya['id']
]);

$canta_guclendirme = $stmt_canta_guclendirme->fetch(PDO::FETCH_ASSOC);

if (
    $canta_guclendirme &&
    (int)$canta_guclendirme['guclendirme_sayi'] > 0
) {
$canta_guclendirme_faiz =
    (int)$canta_guclendirme['guclendirme_faizi'];

    echo ' <b style="color:#000000;">(+' .
        $canta_guclendirme_faiz .
        '%)</b>';
}
?>
</span>

</a>

</u>

<br>

<?php if (
    $esya['tip'] !== 'almaz' &&
    $esya['tip'] !== 'mecun' &&
    !in_array((int)$esya['esya_id'], [404, 405, 406], true)
): ?>

<a
    href="chantam.php?go=geyin&amp;idi=<?php
    echo (int)$esya['id'];
    ?>"
>
Geyin
</a>

|

<?php endif; ?>

<a
    href="chantam.php?go=sat&amp;idi=<?php
    echo (int)$esya['id'];
    ?>"
>
Sat
</a>

<br>

<a
    href="chantam.php?go=auksion&amp;idi=<?php
    echo (int)$esya['id'];
    ?>"
>
Auksiona çıxart
</a>

<br>

</td>

</tr>

</table>

</div>

</div>

<?php endforeach; ?>

<?php endif; ?>


<?php if ($novbeti_var): ?>

<div class="menu">

<br>

<li>

<a
    href="chantam.php?go=eshya&amp;s=<?php
    echo $start + 10;
    ?>"
>

<img
    src="img/go_next.png"
    alt=""
>

Növbəti

</a>

</li>

<?php if ($geri_var): ?>

<li>

<a
    href="chantam.php?go=eshya&amp;s=<?php
    echo $start - 10;
    ?>"
>

<img
    src="img/go_back.png"
    alt=""
>

Geri

</a>

</li>

<?php endif; ?>

<div class="line"></div>

</div>

<?php elseif ($geri_var): ?>

<div class="menu">

<br>

<li>

<a
    href="chantam.php?go=eshya&amp;s=<?php
    echo $start - 10;
    ?>"
>

<img
    src="img/go_back.png"
    alt=""
>

Geri

</a>

</li>

<div class="line"></div>

</div>

<?php endif; ?>


<br>

<div class="menu">

<li>

<a href="chantam.php?">

Eşya çantası

</a>

</li>

<li>

<a href="infoforce.php?uid=<?php echo $my_id; ?>">

Mənim döyüşçüm

</a>

</li>

</div>


<?php
/* =====================================================
   MECUNLAR
===================================================== */

elseif ($go === 'mecun'):
?>

<div class="battle_log">

<div class="content">

Mecunların sayı:
(<?php echo $mecun_sayi; ?>)

|

Qutunun tutumu:
(<?php echo (int)$canta_tutumu; ?>)

</div>

</div>

<br>

<?php if (empty($mecunlar)): ?>

<div class="battle_log">

<div class="content">

Çantada meçun yoxdur.

</div>

</div>

<?php else: ?>

<?php foreach ($mecunlar as $mecun): ?>

<?php 

$reng = esya_reng($mecun['reng']);

if (in_array((int)$mecun['esya_id'], [404, 405, 406], true)) {
    $reng = '#FFD700';
}

if (
    in_array(
        (int)$mecun['esya_id'],
        [377, 378, 379, 389, 390, 391, 392, 393, 394, 395, 396, 397, 398, 399, 400, 401, 402, 403],
        true
    )
) {
    $reng = '#850E0E';
}

?>
<div class="battle_log">

<div class="content">

<table
    border="0"
    cellpadding="2"
    cellspacing="0"
>

<tr>

<td>

<?php if (!empty($mecun['img'])): ?>

<img
    src="<?php
    echo htmlspecialchars(
        $mecun['img'],
        ENT_QUOTES,
        'UTF-8'
    );
    ?>"
    alt="foto"
>

<?php endif; ?>

</td>

<td>

<u>

<a
    href="eshyalar.php?go=m_info&amp;rid=<?php
    echo (int)$mecun['esya_id'];
    ?>"
>

<font
    color="<?php
    echo htmlspecialchars(
        $reng,
        ENT_QUOTES,
        'UTF-8'
    );
    ?>"
>

<?php

echo htmlspecialchars(
    $mecun['ad'],
    ENT_QUOTES,
    'UTF-8'
);

?>

</font>

</a>

</u>

<br>

<a
    href="chantam.php?go=sat&amp;idi=<?php
    echo (int)$mecun['id'];
    ?>"
>
Sat
</a>

<br>

<a
    href="chantam.php?go=auksion&amp;idi=<?php
    echo (int)$mecun['id'];
    ?>"
>
Auksiona çıxart
</a>

<br>

</td>

</tr>

</table>

</div>

</div>

<?php endforeach; ?>

<?php endif; ?>


<br>

<div class="menu">

<li>

<a href="chantam.php?">

Eşya çantası

</a>

</li>

<li>

<a href="infoforce.php?uid=<?php echo $my_id; ?>">

Mənim döyüşçüm

</a>

</li>

</div>


<?php
/* =====================================================
   NORMAL ÇANTA
===================================================== */

else:
?>

<div class="center">

<div class="block_line">

Eşya çantası

</div>

</div>

<br>

<div class="standart2">

<a href="chantam.php?go=eshya">

Eşyalar

</a>

(<?php echo $esya_sayi; ?>)

<br>

<a href="chantam.php?go=mecun">

Mecunlar

</a>

(<?php echo $mecun_sayi; ?>)

<br>

<div class="line"></div>

</div>


<div class="menu">

<br>

<li>

<a href="infoforce.php?uid=<?php echo $my_id; ?>">

Mənim döyüşçüm

</a>

</li>

</div>

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

[<b><a href="menu.php?">Menu</a></b>]

[<b><a href="axtar.php?">Axtarış</a></b>]

[<a href="forum/mozu2.php?">Forum</a>]

[<a href="shexsi_sehife.php?">Qurğular</a>]

<br><br>

<img src="muxtelif/saat.ico" width="20"height="20" title="Vaxt"alt="Vaxt">

<?php echo date("H:i", time()); ?>

<br>

<a href="index.php?">

Çıxış
(<?php echo htmlspecialchars($user_login); ?>)

</a>

<br><br>

<a href="menu.php?dil=tr">

Türkce:

<img
    alt="türkce"
    src="http://macera.az/klan/muxtelif/tr.gif"
    title="Türkce"
/>

</a>

<br>

Sciript name: Qanlı efsane(modern version)

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


