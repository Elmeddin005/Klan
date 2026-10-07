<?php

/*
=========================================================
 QRUP_KOMEK.PHP
=========================================================
*/

ob_start();

session_start();

require_once "config.php";
require_once "user_data.php";
require_once "guc_parametrləri.php";


/* =========================================================
   PARAMETRLƏR
========================================================= */

$go  = $_GET['go'] ?? '';
$lis = isset($_GET['lis']) ? (int)$_GET['lis'] : 0;


/* =========================================================
   LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

    if ($go === 'yoxla_doyus') {

        ob_clean();

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'ok'       => 0,
            'doyus'    => 0,
            'bildiris' => 0,
            'error'    => 'not_logged_in'
        ]);

        exit;
    }

    header("Location: index.php");
    exit;
}

$my_id = (int)$_SESSION['user_id'];


/* =========================================================
   QRUP DATABASE
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
   AJAX - DÖYÜŞ YOXLAMA
   BU BLOK YALNIZ 1 DƏFƏ VAR
========================================================= */

if ($go === 'yoxla_doyus') {

    /*
     * config.php / user_data.php / guc_parametrləri.php
     * hər hansı çıxış veribsə təmizləyirik.
     *
     * JSON-un qarşısında:
     * <script>
     * <html>
     * və s. qalmayacaq.
     */

    ob_clean();

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header(
        'Cache-Control: no-cache, no-store, must-revalidate, max-age=0'
    );

    header('Pragma: no-cache');

    header('Expires: 0');


    $ajax_lis =
        isset($_GET['lis'])
            ? (int)$_GET['lis']
            : 0;


    if ($ajax_lis <= 0) {

        echo json_encode([
            'ok'       => 0,
            'doyus'    => 0,
            'bildiris' => 0,
            'error'    => 'invalid_group'
        ]);

        exit;
    }


    $stmt_ajax = $pdo_qrup->prepare("
        SELECT
            doyuse_qosuldu,
            doyus_bildirisi
        FROM qrup_uzvleri
        WHERE qrup_id = :qrup_id
          AND user_id = :user_id
          AND status = 1
        LIMIT 1
    ");


    $stmt_ajax->execute([

        ':qrup_id' => $ajax_lis,

        ':user_id' => $my_id

    ]);


    $ajax = $stmt_ajax->fetch();


    if (!$ajax) {

        echo json_encode([
            'ok'       => 1,
            'doyus'    => 0,
            'bildiris' => 0
        ]);

        exit;
    }


    echo json_encode([

        'ok' =>
            1,

        'doyus' =>
            (int)$ajax['doyuse_qosuldu'],

        'bildiris' =>
            (int)$ajax['doyus_bildirisi']

    ]);

    exit;
}


/* =========================================================
   QRUP ID YOXLAMA
========================================================= */

if ($lis <= 0) {

    exit('Qrup məlumatı tapılmadı.');

}


/* =========================================================
   USER
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        qızıl,
        brılyant,
        enerjı,
        oyuncunun_seviyyesi
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $my_id
]);

$user = $stmt->fetch();


if (!$user) {

    exit('İstifadəçi tapılmadı.');

}


/* =========================================================
   ONLINE
========================================================= */

$stmt_online = $pdo->prepare("
    UPDATE users
    SET online_oyuncu_vaxti = :vaxt
    WHERE id = :id
");

$stmt_online->execute([

    ':vaxt' => time(),

    ':id' => $my_id

]);


/* =========================================================
   OXUNMAMIŞ MƏKTUBLAR
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

$unread_count =
    (int)$stmt_unread->fetchColumn();


/* =========================================================
   QRUPU GƏTİR
========================================================= */

$stmt_qrup = $pdo_qrup->prepare("
    SELECT
        id,
        yaradan_id,
        otaq_adi,
        oyuncu_tutumu,
        minimum_merhele,
        maksimum_merhele,
        qizil,
        doyus_novu,
        terefi,
        reqib_qrup_id,
        status,
        yaradildi
    FROM qruplar
    WHERE id = :qrup_id
    LIMIT 1
");

$stmt_qrup->execute([
    ':qrup_id' => $lis
]);

$qrup = $stmt_qrup->fetch();

if (!$qrup && isset($_GET['timeout_yoxla'])) {
    
}
if (!$qrup) {

    exit('Qrup tapılmadı.');

}


$qrup_id =
    (int)$qrup['id'];

/* MƏNİM TƏRƏFİM */
$stmt_menim_teref = $pdo_qrup->prepare("
    SELECT terefi
    FROM qrup_uzvleri
    WHERE qrup_id = ?
      AND user_id = ?
      AND status = 1
    LIMIT 1
");

$stmt_menim_teref->execute([
    $qrup_id,
    $my_id
]);

$menim_terefim =
    (int)$stmt_menim_teref->fetchColumn();
/* =========================================================
   QRUP MESAJLARI
========================================================= */

$stmt_mesajlar = $pdo_qrup->prepare("
    SELECT
        qm.id,
        qm.user_id,
        qm.mesaj,
        qm.yaradildi,
        u.login,
        u.movqe
    FROM qrup_mesajlar qm
    INNER JOIN klannn.users u
        ON u.id = qm.user_id
    WHERE qm.qrup_id = :qrup_id
    ORDER BY qm.id DESC
");

$stmt_mesajlar->execute([
    ':qrup_id' => $qrup_id
]);

$qrup_mesajlar =
    $stmt_mesajlar->fetchAll();

$qrup_mesaj_sayi =
    count($qrup_mesajlar);


/* =========================================================
   MESAJ YAZ
========================================================= */

if (
    $go === 'yaz' &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $message =
        trim($_POST['message'] ?? '');


    if ($message !== '') {

        $stmt_yaz = $pdo_qrup->prepare("
            INSERT INTO qrup_mesajlar
            (
                qrup_id,
                user_id,
                mesaj,
                yaradildi
            )
            VALUES
            (
                :qrup_id,
                :user_id,
                :mesaj,
                NOW()
            )
        ");

        $stmt_yaz->execute([

            ':qrup_id' =>
                $qrup_id,

            ':user_id' =>
                $my_id,

            ':mesaj' =>
                $message

        ]);

    }


    header(
        "Location: qrup_komek.php?go=mesajlar&lis=" .
        $qrup_id
    );

    exit;
}

/* =========================================================
   59 SANİYƏ TIMEOUT — OYUNÇUNU DÖYÜŞDƏN ÇIXAR
   VƏ NÖVBƏTİ OYUNÇUNU DÖYÜŞƏ QOŞ
========================================================= */
if (
    !isset($_GET['timeout_yoxla']) ||
    (int)$_GET['timeout_yoxla'] !== 1
) {
$stmt_timeout_doyus = $pdo_qrup->prepare("
    SELECT
        id,
        oyuncu1_id,
        oyuncu2_id,
        oyuncu1_hucum,
        oyuncu2_hucum,
        novbe_baslama_tarixi
    FROM qrup_doyusleri
    WHERE qrup_id = ?
      AND bitdi = 0
      AND novbe_baslama_tarixi IS NOT NULL
    ORDER BY id DESC
    LIMIT 1
");

$stmt_timeout_doyus->execute([
    $qrup_id
]);

$timeout_doyus =
    $stmt_timeout_doyus->fetch(PDO::FETCH_ASSOC);


if ($timeout_doyus) {

    $timeout_baslama =
        strtotime(
            $timeout_doyus['novbe_baslama_tarixi']
        );


    if (
        $timeout_baslama !== false &&
        (time() - $timeout_baslama) >= 10
    ) {

        /*
         * Timeout olan oyunçular.
         */
        $timeout_oyuncular = [];


        if (
            $timeout_doyus['oyuncu1_hucum'] === null
        ) {

            $timeout_oyuncular[] =
                (int)$timeout_doyus['oyuncu1_id'];
        }


        if (
            $timeout_doyus['oyuncu2_hucum'] === null
        ) {

            $timeout_oyuncular[] =
                (int)$timeout_doyus['oyuncu2_id'];
        }


        foreach (
            $timeout_oyuncular
            as $timeout_user_id
        ) {

            if ($timeout_user_id <= 0) {
                continue;
            }


            /*
             * Timeout olanı döyüşdən çıxar.
             */
$stmt_timeout_cixar =
    $pdo_qrup->prepare("
        UPDATE qrup_uzvleri
        SET
            doyuse_qosuldu = 0,
            doyus_bildirisi = 2
        WHERE qrup_id = ?
          AND user_id = ?
          AND status = 1
        LIMIT 1
    ");


            $stmt_timeout_cixar->execute([
                $qrup_id,
                $timeout_user_id
            ]);
echo '<!-- TIMEOUT UPDATE USER=' . $timeout_user_id . ' ROWS=' . $stmt_timeout_cixar->rowCount() . ' -->';


            /*
             * Timeout olan tərəfi tap.
             */
            $stmt_timeout_teref =
                $pdo_qrup->prepare("
                    SELECT terefi
                    FROM qrup_uzvleri
                    WHERE qrup_id = ?
                      AND user_id = ?
                      AND status = 1
                    LIMIT 1
                ");

            $stmt_timeout_teref->execute([
                $qrup_id,
                $timeout_user_id
            ]);

            $timeout_uzv =
                $stmt_timeout_teref->fetch(PDO::FETCH_ASSOC);


            if (!$timeout_uzv) {
                continue;
            }


            $timeout_teref =
                (int)$timeout_uzv['terefi'];


            /*
             * Həmin tərəfdən növbəti uyğun oyunçunu tap.
             *
             * Döyüşdə olmayan,
             * canı olan,
             * statusu aktiv olan
             * və timeout ilə çıxarılmayan oyunçu.
             */
            $stmt_novbeti =
                $pdo_qrup->prepare("
                    SELECT
                        id,
                        user_id
                    FROM qrup_uzvleri
                    WHERE qrup_id = ?
                      AND terefi = ?
                      AND status = 1
                      AND doyuse_qosuldu = 0
                      AND user_id <> ?
                      AND (
                          son_can IS NULL
                          OR son_can > 0
                      )
                    ORDER BY
                        giris_sirasi ASC,
                        id ASC
                    LIMIT 1
                ");

            $stmt_novbeti->execute([
                $qrup_id,
                $timeout_teref,
                $timeout_user_id
            ]);

            $novbeti =
                $stmt_novbeti->fetch(PDO::FETCH_ASSOC);


            if ($novbeti) {

                /*
                 * Növbəti oyunçunu döyüşə qoş.
                 */
                $stmt_qos =
                    $pdo_qrup->prepare("
                        UPDATE qrup_uzvleri
                        SET
                            doyuse_qosuldu = 1,
                            doyus_bildirisi = 1,
                            son_doyuse_qosulma_vaxti = NOW()
                        WHERE id = ?
                          AND status = 1
                          AND doyuse_qosuldu = 0
                        LIMIT 1
                    ");

                $stmt_qos->execute([
                    (int)$novbeti['id']
                ]);
            }
        }
    }
}
}


/* =========================================================
   BİZİM QRUP ÜZVLƏRİ
========================================================= */

$stmt_bizim = $pdo_qrup->prepare("
    SELECT

        qu.id AS uzv_id,

        qu.user_id,

        qu.terefi,

        qu.giris_sirasi,

        qu.gelme_novu,

        qu.devet_eden_id,

        qu.daxil_olma_vaxti,

        qu.status,

        qu.doyuse_qosuldu,

        qu.doyus_bildirisi,
       qu.son_can,
u.login,


        u.movqe,

        u.oyuncunun_seviyyesi,

        u.vip,

        op.can,
qd.oyuncu1_id,
qd.oyuncu2_id,
qd.oyuncu1_can,
qd.oyuncu2_can,
qd.bitdi,
qd.novbe_baslama_tarixi,
qd.oyuncu1_hucum,
qd.oyuncu2_hucum


    FROM qrup_uzvleri qu

    INNER JOIN klannn.users u
        ON u.id = qu.user_id

    LEFT JOIN klannn.oyuncu_parametrleri op
        ON op.user_id = u.id
       LEFT JOIN qrup_doyusleri qd
    ON qd.id = (
        SELECT MAX(qd2.id)
        FROM qrup_doyusleri qd2
        WHERE qd2.qrup_id = qu.qrup_id
          AND (
              qd2.oyuncu1_id = qu.user_id
              OR qd2.oyuncu2_id = qu.user_id
          )
    )

    WHERE qu.qrup_id = :qrup_id

      AND qu.status = 1

      AND qu.terefi = :teref

    ORDER BY
        qu.giris_sirasi ASC,
        qu.id ASC
");

$stmt_bizim->execute([

    ':qrup_id' =>
        $qrup_id,

    ':teref' =>
        $menim_terefim

]);

$bizim_uzvler =
    $stmt_bizim->fetchAll();


/* =========================================================
   RƏQİB QRUP ÜZVLƏRİ
========================================================= */

$stmt_reqib = $pdo_qrup->prepare("
    SELECT

        qu.id AS uzv_id,

        qu.user_id,

        qu.terefi,

        qu.giris_sirasi,

        qu.gelme_novu,

        qu.devet_eden_id,

        qu.daxil_olma_vaxti,

        qu.status,

        qu.doyuse_qosuldu,

        qu.doyus_bildirisi,
        qu.son_can,

        u.login,

        u.movqe,

        u.oyuncunun_seviyyesi,

        u.vip,

        op.can,
qd.oyuncu1_id,
qd.oyuncu2_id,
qd.oyuncu1_can,
qd.oyuncu2_can,
qd.bitdi,
qd.novbe_baslama_tarixi,
qd.oyuncu1_hucum,
qd.oyuncu2_hucum


    FROM qrup_uzvleri qu

    INNER JOIN klannn.users u
        ON u.id = qu.user_id

    LEFT JOIN klannn.oyuncu_parametrleri op
        ON op.user_id = u.id

     LEFT JOIN qrup_doyusleri qd
    ON qd.id = (
        SELECT MAX(qd2.id)
        FROM qrup_doyusleri qd2
        WHERE qd2.qrup_id = qu.qrup_id
          AND (
              qd2.oyuncu1_id = qu.user_id
              OR qd2.oyuncu2_id = qu.user_id
          )
    )

    WHERE qu.qrup_id = :qrup_id

      AND qu.status = 1

      AND qu.terefi != :teref

    ORDER BY
        qu.giris_sirasi ASC,
        qu.id ASC
");

$stmt_reqib->execute([

    ':qrup_id' =>
        $qrup_id,

    ':teref' =>
        $menim_terefim

]);

$reqib_uzvler =
    $stmt_reqib->fetchAll();


/* =========================================================
   RƏHBƏRLƏR
========================================================= */

$bizim_rehber_id =
    (int)$qrup['yaradan_id'];

$reqib_rehber_id = 0;


if (!empty($reqib_uzvler)) {

    $reqib_rehber_id =
        (int)$reqib_uzvler[0]['user_id'];

}
/* =========================================================
   TIMEOUT YOXLAMA — 59 SANİYƏ
========================================================= */

if (
    isset($_GET['timeout_yoxla']) &&
    (int)$_GET['timeout_yoxla'] === 1
) {

    header('Content-Type: application/json; charset=utf-8');
    ob_clean();

    try {

        /*
         * =====================================================
         * 1. DÖYÜŞDƏ HAZIRDA OLAN OYUNÇULARI TAP
         *
         * TIMEOUT ARTİQ QROUP_DOYUSLERI-DƏN ASILI DEYİL.
         * 59 SANİYƏ:
         * son_doyuse_qosulma_vaxti + 59
         * =====================================================
         */

        $stmt_timeout = $pdo_qrup->prepare("
            SELECT
                id AS uzv_id,
                user_id,
                terefi,
                doyuse_qosuldu,
                doyus_bildirisi,
                son_can,
                son_doyuse_qosulma_vaxti,
                giris_sirasi
            FROM qrup_uzvleri
            WHERE qrup_id = ?
              AND status = 1
              AND doyuse_qosuldu = 1
              AND son_doyuse_qosulma_vaxti IS NOT NULL
            ORDER BY giris_sirasi ASC, id ASC
        ");

        $stmt_timeout->execute([
            $qrup_id
        ]);

        $timeout_rows = $stmt_timeout->fetchAll(PDO::FETCH_ASSOC);

        $timeout_edenler = [];


        /*
         * =====================================================
         * 2. HƏR AKTİV OYUNÇUNUN 59 SANİYƏSİNİ YOXLAYIRIQ
         * =====================================================
         */

       foreach ($timeout_rows as $tr) {

    /*
     * =====================================================
     * 59 SANİYƏNİN BAŞLANĞICINI MÜƏYYƏN EDİRİK
     *
     * Əvvəl aktiv raundun vaxtına baxırıq.
     * Aktiv raund varsa:
     *     qrup_doyusleri.novbe_baslama_tarixi
     *
     * Aktiv raund yoxdursa:
     *     son_doyuse_qosulma_vaxti
     * =====================================================
     */

    $baslama = false;

    $stmt_timeout_vaxt = $pdo_qrup->prepare("
        SELECT
            novbe_baslama_tarixi
        FROM qrup_doyusleri
        WHERE qrup_id = ?
          AND bitdi = 0
          AND novbe_baslama_tarixi IS NOT NULL
          AND (
              oyuncu1_id = ?
              OR oyuncu2_id = ?
          )
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt_timeout_vaxt->execute([
        $qrup_id,
        (int)$tr['user_id'],
        (int)$tr['user_id']
    ]);

    $timeout_vaxt_row =
        $stmt_timeout_vaxt->fetch(PDO::FETCH_ASSOC);

    if (
        $timeout_vaxt_row &&
        !empty($timeout_vaxt_row['novbe_baslama_tarixi'])
    ) {
        $baslama = strtotime(
            $timeout_vaxt_row['novbe_baslama_tarixi']
        );
    }

    /*
     * Aktiv raund yoxdursa,
     * oyunçunun döyüşə qoşulma vaxtından hesabla.
     */
    if (
        $baslama === false &&
        !empty($tr['son_doyuse_qosulma_vaxti'])
    ) {
        $baslama = strtotime(
            $tr['son_doyuse_qosulma_vaxti']
        );
    }

    if ($baslama === false) {
        continue;
    }

    /*
     * Hələ 59 saniyə tamam olmayıb.
     */
    if (
        (time() - $baslama) < 10
    ) {
        continue;
    }

            /*
             * =================================================
             * 3. BU OYUNÇU BU NÖVBƏDƏ ZƏRBƏ VURUBMU?
             *
             * ƏGƏR HƏLƏ QROUP_DOYUSLERI YARANMAYIBSA:
             *     zərbə yoxdur.
             *
             * ƏGƏR SƏTR YARANIBSA:
             *     yalnız bu oyunçunun hucum sahəsinə baxırıq.
             *
             * Köhnə döyüş sətrinin yeni növbəyə təsir etməməsi
             * üçün novbe_baslama_tarixi oyunçunun qoşulma
             * vaxtından əvvəl olmamalıdır.
             * =================================================
             */
 /*
 * =================================================
 * AKTİV DÖYÜŞ SƏTRİNİ TAP
 *
 * İlk döyüşdə qrup_doyusleri hələ yoxdursa:
 * zərbə vurulmayıb kimi qəbul edilir.
 * =================================================
 */

$hucum = null;

$stmt_hucum = $pdo_qrup->prepare("
    SELECT
        oyuncu1_id,
        oyuncu2_id,
        oyuncu1_hucum,
        oyuncu2_hucum,
        oyuncu1_can,
        oyuncu2_can,
        novbe_baslama_tarixi
    FROM qrup_doyusleri
    WHERE qrup_id = ?
      AND bitdi = 0
      AND novbe_baslama_tarixi IS NOT NULL
      AND novbe_baslama_tarixi >= ?
      AND (
          oyuncu1_id = ?
          OR oyuncu2_id = ?
      )
    ORDER BY id DESC
    LIMIT 1
");

$stmt_hucum->execute([
    $qrup_id,
    date('Y-m-d H:i:s', $baslama),
    (int)$tr['user_id'],
    (int)$tr['user_id']
]);

$hucum = $stmt_hucum->fetch(PDO::FETCH_ASSOC);
            /*
             * =================================================
             * 4. BU OYUNÇU ZƏRBƏ VURUBMU?
             * =================================================
             */

            $hucum_edib = false;

            if ($hucum) {

                if (
                    (int)$hucum['oyuncu1_id'] ===
                    (int)$tr['user_id']
                ) {

                    if (
                        $hucum['oyuncu1_hucum'] !== null
                    ) {
                        $hucum_edib = true;
                    }

                } elseif (
                    (int)$hucum['oyuncu2_id'] ===
                    (int)$tr['user_id']
                ) {

                    if (
                        $hucum['oyuncu2_hucum'] !== null
                    ) {
                        $hucum_edib = true;
                    }
                }
            }


            /*
             * =================================================
             * 5. ZƏRBƏ VURUBSA TIMEOUT DEYİL
             * =================================================
             */

            if ($hucum_edib) {
                continue;
            }

/*
 * =================================================
 * TIMEOUT NƏTİCƏSİ ÜÇÜN RƏQİBİ TAP
 * =================================================
 */

$timeout_teref = (int)$tr['terefi'];
$timeout_user_id = (int)$tr['user_id'];

$reqib_teref =
    ($timeout_teref === 1) ? 2 : 1;

$stmt_reqib_aktiv = $pdo_qrup->prepare("
    SELECT
        user_id,
        terefi,
        doyuse_qosuldu,
        son_can,
        son_doyuse_qosulma_vaxti
    FROM qrup_uzvleri
    WHERE qrup_id = ?
      AND terefi = ?
      AND status = 1
      AND doyuse_qosuldu = 1
    ORDER BY giris_sirasi ASC, id ASC
    LIMIT 1
");

$stmt_reqib_aktiv->execute([
    $qrup_id,
    $reqib_teref
]);

$reqib_aktiv = $stmt_reqib_aktiv->fetch(PDO::FETCH_ASSOC);

$reqib_hucum_edib = false;
$reqib_user_id = 0;

if ($reqib_aktiv) {

    $reqib_user_id =
        (int)$reqib_aktiv['user_id'];

    /*
     * Rəqibin bu raundda hücum edib-etmədiyini tap.
     */
    if ($hucum) {

        if (
            (int)$hucum['oyuncu1_id'] ===
            $reqib_user_id
        ) {

            $reqib_hucum_edib =
                ($hucum['oyuncu1_hucum'] !== null);

        } elseif (
            (int)$hucum['oyuncu2_id'] ===
            $reqib_user_id
        ) {

            $reqib_hucum_edib =
                ($hucum['oyuncu2_hucum'] !== null);
        }
    }
}
/*
 * =================================================
 * TIMEOUT OLAN TƏRƏFDƏ NÖVBƏTİ OYUNÇU VAR?
 * =================================================
 */

$stmt_timeout_novbeti = $pdo_qrup->prepare("
    SELECT
        user_id
    FROM qrup_uzvleri
    WHERE qrup_id = ?
      AND terefi = ?
      AND status = 1
      AND user_id != ?
      AND (
          son_can IS NULL
          OR son_can > 0
      )
    ORDER BY
        giris_sirasi ASC,
        id ASC
    LIMIT 1
");

$stmt_timeout_novbeti->execute([
    $qrup_id,
    $timeout_teref,
    $timeout_user_id
]);

$timeout_teref_novbeti =
    $stmt_timeout_novbeti->fetch(PDO::FETCH_ASSOC);

$timeout_sonuncudur =
    !$timeout_teref_novbeti;
    /*
 * =================================================
 * RƏQİB TƏRƏFİN AKTİV OYUNÇUSU SONUNCUDUR?
 * =================================================
 */

$reqib_sonuncudur = false;

if ($reqib_aktiv) {

    $stmt_reqib_novbeti = $pdo_qrup->prepare("
        SELECT
            user_id
        FROM qrup_uzvleri
        WHERE qrup_id = ?
          AND terefi = ?
          AND status = 1
          AND user_id != ?
          AND (
              son_can IS NULL
              OR son_can > 0
          )
        ORDER BY
            giris_sirasi ASC,
            id ASC
        LIMIT 1
    ");

    $stmt_reqib_novbeti->execute([
        $qrup_id,
        $reqib_teref,
        $reqib_user_id
    ]);

    $reqib_novbeti =
        $stmt_reqib_novbeti->fetch(PDO::FETCH_ASSOC);

    $reqib_sonuncudur =
        !$reqib_novbeti;
}

if (
    $hucum_edib xor $reqib_hucum_edib
) {

    if ($hucum_edib) {
        $qalib_id = $tr['user_id'];
        $meglub_id = $reqib_user_id;
    } else {
        $qalib_id = $reqib_user_id;
        $meglub_id = $tr['user_id'];
    }

    $stmt_timeout_qalib = $pdo_qrup->prepare("
        INSERT INTO qrup_doyusleri
        (
            qrup_id,
            oyuncu1_id,
            oyuncu2_id,
            qalib_id,
            bitdi,
            bitdi_qalib,
            bitdi_meglub,
            hec_hece,
            novbe_baslama_tarixi
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            1,
            ?,
            ?,
            0,
            NOW()
        )
    ");

    $stmt_timeout_qalib->execute([
        $qrup_id,
        $tr['user_id'],
        $reqib_user_id,
        $qalib_id,
        $qalib_id,
        $meglub_id
    ]);

$stmt_meglub = $pdo_qrup->prepare("
    UPDATE qrup_uzvleri
    SET
        doyuse_qosuldu = 0,
        doyus_bildirisi = CASE
            WHEN user_id = ? THEN 2
            WHEN user_id = ? THEN 0
            ELSE doyus_bildirisi
        END
    WHERE qrup_id = ?
      AND user_id IN (?, ?)
      AND status = 1
");

$stmt_meglub->execute([
    $meglub_id,
    $qalib_id,
    $qrup_id,
    $meglub_id,
    $qalib_id
]);
}
/*
 * =================================================
 * TIMEOUT NƏTİCƏSİ
 * =================================================
 */

if ($timeout_sonuncudur && $reqib_sonuncudur) {

    /*
     * Rəqib də sonuncudur və hücum etməyib:
     * HEÇ-HEÇƏ
     */
    if (
        $reqib_sonuncudur &&
        !$reqib_hucum_edib &&
        !$hucum_edib
    ) {
              $timeout_son_can = (int)($tr['son_can'] ?? 0);
$reqib_son_can   = (int)($reqib_aktiv['son_can'] ?? 0);

$hec_hece_canlar = [
    $timeout_user_id => $timeout_son_can,
    $reqib_user_id   => $reqib_son_can
];

/*
 * Bu iki oyunçu üçün artıq nəticə yaradılıbsa,
 * yenidən INSERT etmə.
 */
$stmt_hec_hece_yoxla = $pdo_qrup->prepare("
    SELECT id
    FROM qrup_doyusleri
    WHERE qrup_id = ?
      AND (
          (oyuncu1_id = ? AND oyuncu2_id = ?)
          OR
          (oyuncu1_id = ? AND oyuncu2_id = ?)
      )
      AND bitdi = 1
    LIMIT 1
");

$stmt_hec_hece_yoxla->execute([
    $qrup_id,
    $timeout_user_id,
    $reqib_user_id,
    $reqib_user_id,
    $timeout_user_id
]);

$hec_hece_artiq_var =
    $stmt_hec_hece_yoxla->fetchColumn();

if ($hec_hece_artiq_var) {
    continue;
}
        $stmt_hec_hece = $pdo_qrup->prepare("
            INSERT INTO qrup_doyusleri
            (
                qrup_id,
                oyuncu1_id,
                oyuncu2_id,
                oyuncu1_can,
                oyuncu2_can,
                qalib_id,
                bitdi,
                bitdi_qalib,
                bitdi_meglub,
                hec_hece,
                novbe_baslama_tarixi
            )
          VALUES
(
    ?,
    ?,
    ?,
    ?,
    ?,
    NULL,
    1,
    NULL,
    NULL,
    1,
    NOW()
)
        ");

       $stmt_hec_hece->execute([
    $qrup_id,
    $timeout_user_id,
    $reqib_user_id,
    $hec_hece_canlar[$timeout_user_id],
    $hec_hece_canlar[$reqib_user_id]
]);

  $stmt_cixar = $pdo_qrup->prepare("
    UPDATE qrup_uzvleri
    SET
        doyuse_qosuldu = 0,
        doyus_bildirisi = 2
    WHERE qrup_id = ?
      AND user_id IN (?, ?)
      AND status = 1
      AND doyuse_qosuldu = 1
");

$stmt_cixar->execute([
    $qrup_id,
    $timeout_user_id,
    $reqib_user_id
]);

$timeout_edenler[] = $timeout_user_id;
$timeout_edenler[] = $reqib_user_id;

break;
  }

    /*
     * Rəqib hücum edib, timeout olan sonuncudur:
     * RƏQİB QALİBDİR.
     */
    if (
        $reqib_hucum_edib
    ) {

        $stmt_qalib = $pdo_qrup->prepare("
            INSERT INTO qrup_doyusleri
            (
                qrup_id,
                oyuncu1_id,
                oyuncu2_id,
                oyuncu1_can,
                oyuncu2_can,
                qalib_id,
                bitdi,
                bitdi_qalib,
                bitdi_meglub,
                hec_hece,
                novbe_baslama_tarixi
            )
                       VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                NULL,
                1,
                NULL,
                NULL,
                0,
                NOW()
            )
        ");

        $stmt_qalib->execute([
            $qrup_id,
            $reqib_user_id,
            $timeout_user_id,
            $reqib_user_id,
            $reqib_user_id,
            $timeout_user_id
        ]);
        /* TIMEOUT QALİB/MƏĞLUB NƏTİCƏSİNDƏ SON CANLARI SAXLA */

$timeout_son_can = null;
$reqib_son_can = null;

/*
 * ƏVVƏLCƏ AKTİV DÖYÜŞDƏN CANI GÖTÜR
 */
if ($hucum) {

    if (
        (int)$hucum['oyuncu1_id'] ===
        $timeout_user_id
    ) {

        $timeout_son_can =
            (int)$hucum['oyuncu1_can'];

        $reqib_son_can =
            (int)$hucum['oyuncu2_can'];

    } else {

        $timeout_son_can =
            (int)$hucum['oyuncu2_can'];

        $reqib_son_can =
            (int)$hucum['oyuncu1_can'];
    }
}

/*
 * DÖYÜŞ SƏTRİ YOXDURSA
 * qrup_uzvleri.son_can-DAN GÖTÜR
 */
if ($timeout_son_can === null) {

    if ($tr['son_can'] !== null) {
        $timeout_son_can =
            (int)$tr['son_can'];
    }
}

if ($reqib_son_can === null) {

    if (
        $reqib_aktiv &&
        $reqib_aktiv['son_can'] !== null
    ) {
        $reqib_son_can =
            (int)$reqib_aktiv['son_can'];
    }
}

$stmt_son_can = $pdo_qrup->prepare("
    UPDATE qrup_uzvleri
    SET son_can = CASE
        WHEN user_id = ? THEN ?
        WHEN user_id = ? THEN ?
        ELSE son_can
    END
    WHERE qrup_id = ?
      AND user_id IN (?, ?)
    LIMIT 2
");

$stmt_son_can->execute([
    $timeout_user_id,
    $timeout_son_can,
    $reqib_user_id,
    $reqib_son_can,
    $qrup_id,
    $timeout_user_id,
    $reqib_user_id
]);

       $stmt_cixar = $pdo_qrup->prepare("
    UPDATE qrup_uzvleri
    SET
        doyuse_qosuldu = 0,
        doyus_bildirisi = 2
    WHERE qrup_id = ?
      AND user_id IN (?, ?)
      AND status = 1
");

$stmt_cixar->execute([
    $qrup_id,
    $timeout_user_id,
    $reqib_user_id
]);
        $timeout_edenler[] =
            $timeout_user_id;

        continue;
    }
}
            /*
             * =================================================
             * 6. 59 SANİYƏDƏ ZƏRBƏ VURMAYAN OYUNÇUNU ÇIXAR
             * =================================================
             */

            $stmt_cixar = $pdo_qrup->prepare("
                UPDATE qrup_uzvleri
                SET
                    doyuse_qosuldu = 0,
                    doyus_bildirisi = 2
                WHERE id = ?
                  AND qrup_id = ?
                  AND status = 1
                  AND doyuse_qosuldu = 1
            ");

            $stmt_cixar->execute([
                (int)$tr['uzv_id'],
                $qrup_id
            ]);


            /*
             * =================================================
             * 7. EYNİ TƏRƏFDƏN NÖVBƏTİ OYUNÇUNU TAP
             * =================================================
             */

            $stmt_novbeti = $pdo_qrup->prepare("
                SELECT
                    id,
                    user_id
                FROM qrup_uzvleri
                WHERE qrup_id = ?
                  AND terefi = ?
                  AND status = 1
                  AND doyuse_qosuldu = 0
                  AND user_id != ?
                  AND (
                      son_can IS NULL
                      OR son_can > 0
                  )
                ORDER BY
                    giris_sirasi ASC,
                    id ASC
                LIMIT 1
            ");

            $stmt_novbeti->execute([
                $qrup_id,
                (int)$tr['terefi'],
                (int)$tr['user_id']
            ]);

            $novbeti = $stmt_novbeti->fetch(PDO::FETCH_ASSOC);


            /*
             * =================================================
             * 8. NÖVBƏTİ OYUNÇU VARSA DÖYÜŞƏ QOŞ
             * =================================================
             */

            if ($novbeti) {

                $stmt_qos = $pdo_qrup->prepare("
                    UPDATE qrup_uzvleri
                    SET
                        doyuse_qosuldu = 1,
                        doyus_bildirisi = 1,
                        son_doyuse_qosulma_vaxti = NOW()
                    WHERE id = ?
                      AND qrup_id = ?
                      AND status = 1
                      AND doyuse_qosuldu = 0
                ");

                $stmt_qos->execute([
                    (int)$novbeti['id'],
                    $qrup_id
                ]);
            }


            /*
             * =================================================
             * 9. TIMEOUT OLAN OYUNÇUNUN ID-SİNİ QEYD ET
             * =================================================
             */

            $timeout_edenler[] =
                (int)$tr['user_id'];
        }


        /*
         * =====================================================
         * 10. NƏTİCƏ
         * =====================================================
         */

        echo json_encode([
            'ok' => 1,
            'timeout' => $timeout_edenler,
            'reload' => (
                count($timeout_edenler) > 0
                    ? 1
                    : 0
            )
        ]);

    } catch (Throwable $e) {

        echo json_encode([
            'ok' => 0,
            'timeout' => [],
            'reload' => 0
        ]);
    }

    exit;
}


/* =========================================================
   GO = KECID
   RƏHBƏR OYUNÇUNU DÖYÜŞƏ QOŞUR
========================================================= */

if (
    $go === 'kecid' &&
    isset($_GET['uid'])
) {

    $go_user_id =
        (int)$_GET['uid'];


    if ($go_user_id <= 0) {

        exit('Yanlış oyunçu.');

    }


    /* -----------------------------------------------------
       OYUNÇUNU TAP
    ----------------------------------------------------- */

    $stmt_go = $pdo_qrup->prepare("
        SELECT
            id,
            user_id,
            terefi,
            doyuse_qosuldu,
            doyus_bildirisi
        FROM qrup_uzvleri
        WHERE qrup_id = :qrup_id
          AND user_id = :user_id
          AND status = 1
        LIMIT 1
    ");

    $stmt_go->execute([

        ':qrup_id' =>
            $qrup_id,

        ':user_id' =>
            $go_user_id

    ]);

    $go_uzv =
        $stmt_go->fetch();


    if (!$go_uzv) {

        exit('Bu oyunçu qrupda deyil.');

    }


    /* -----------------------------------------------------
       RƏHBƏR YOXLAMASI
    ----------------------------------------------------- */

    if (
        $my_id !== $bizim_rehber_id &&
        $my_id !== $reqib_rehber_id
    ) {

        exit(
            'Bu əməliyyata icazəniz yoxdur.'
        );

    }
 /* -----------------------------------------------------
   50 SANİYƏLİK DÖYÜŞÇÜ DƏYİŞMƏ MƏHDUDİYYƏTİ
----------------------------------------------------- */

$stmt_son_doyuscu = $pdo_qrup->prepare("
    SELECT
        TIMESTAMPDIFF(
            SECOND,
            son_doyuse_qosulma_vaxti,
            NOW()
        ) AS kechen_saniye
    FROM qrup_uzvleri
    WHERE qrup_id = :qrup_id
      AND terefi = :teref
      AND status = 1
      AND son_doyuse_qosulma_vaxti IS NOT NULL
    ORDER BY
        son_doyuse_qosulma_vaxti DESC,
        id DESC
    LIMIT 1
");

$stmt_son_doyuscu->execute([

    ':qrup_id' => $qrup_id,

    ':teref' => (int)$go_uzv['terefi']

]);

$son_doyuscu = $stmt_son_doyuscu->fetch();


if ($son_doyuscu) {

    $kechen_saniye =
        (int)$son_doyuscu['kechen_saniye'];


    if ($kechen_saniye < 50) {

        $qalan_saniye =
            50 - $kechen_saniye;


        $_SESSION['qrup_doyus_error'] =
            'Siz Oyunçunu ' .
            $qalan_saniye .
            ' saniyə sonra dəyişə bilərsiz.';


        header(
            "Location: qrup_komek.php?lis=" .
            $qrup_id .
            "&t=" .
            time()
        );

        exit;

    }

}



    $secilen_teref =
        (int)$go_uzv['terefi'];


    /* -----------------------------------------------------
       HƏMİN TƏRƏFDƏKİ ƏVVƏLKİ DÖYÜŞÇİNİ ÇIXAR
    ----------------------------------------------------- */

    $stmt_evvelki = $pdo_qrup->prepare("
        UPDATE qrup_uzvleri

        SET
            doyuse_qosuldu = 0,
            doyus_bildirisi = 0

        WHERE qrup_id = :qrup_id

          AND terefi = :teref

          AND status = 1

          AND doyuse_qosuldu = 1
    ");

    $stmt_evvelki->execute([

        ':qrup_id' =>
            $qrup_id,

        ':teref' =>
            $secilen_teref

    ]);


    /* -----------------------------------------------------
       YENİ OYUNÇUNU DÖYÜŞƏ QOŞ
    ----------------------------------------------------- */

$stmt_go_1 = $pdo_qrup->prepare("
    UPDATE qrup_uzvleri
    SET
        doyuse_qosuldu = 1,
        doyus_bildirisi = 1,
        son_doyuse_qosulma_vaxti = NOW()

    WHERE id = :uzv_id

      AND qrup_id = :qrup_id

      AND user_id = :user_id

      AND status = 1

    LIMIT 1
");


    $stmt_go_1->execute([

        ':uzv_id' =>
            (int)$go_uzv['id'],

        ':qrup_id' =>
            $qrup_id,

        ':user_id' =>
            $go_user_id

    ]);
    unset($_SESSION['qrup_doyus_error']);
$_SESSION['qrup_doyus_success'] = 1;
header(
    "Location: qrup_komek.php?lis=" .
    $qrup_id .
    "&t=" .
    time()
);
exit;



    /* -----------------------------------------------------
       STATUS YOXLAMASI
    ----------------------------------------------------- */

    $stmt_yoxla = $pdo_qrup->prepare("
        SELECT
            doyuse_qosuldu,
            doyus_bildirisi
        FROM qrup_uzvleri

        WHERE id = :id

          AND qrup_id = :qrup_id

          AND user_id = :user_id

          AND status = 1

        LIMIT 1
    ");

    $stmt_yoxla->execute([

        ':id' =>
            (int)$go_uzv['id'],

        ':qrup_id' =>
            $qrup_id,

        ':user_id' =>
            $go_user_id

    ]);

    $yeni_status =
        $stmt_yoxla->fetch();


    if (
        !$yeni_status ||
        (int)$yeni_status['doyuse_qosuldu'] !== 1 ||
        (int)$yeni_status['doyus_bildirisi'] !== 1
    ) {

        exit(
            'Oyunçu döyüşə göndərilə bilmədi.'
        );

    }


    header(
        "Location: qrup_komek.php?lis=" .
        $qrup_id .
        "&t=" .
        time()
    );

    exit;
}


/* =========================================================
   AVTOMATİK BİZİM DÖYÜŞÇÜ
========================================================= */

$stmt_bizim_doyus =
    $pdo_qrup->prepare("
        SELECT id
        FROM qrup_uzvleri
        WHERE qrup_id = :qrup_id
          AND terefi = :teref
          AND status = 1
          AND doyuse_qosuldu = 1
        LIMIT 1
    ");

$stmt_bizim_doyus->execute([

    ':qrup_id' =>
        $qrup_id,

    ':teref' =>
        $menim_terefim

]);

$bizim_doyusde_olan =
    $stmt_bizim_doyus->fetch();


if (
    !$bizim_doyusde_olan &&
    $bizim_rehber_id > 0
) {

    $stmt_bizim_ilk =
        $pdo_qrup->prepare("
            UPDATE qrup_uzvleri

               SET
    doyuse_qosuldu = 1,
    doyus_bildirisi = CASE
        WHEN doyus_bildirisi = 2 THEN 2
        ELSE 0
    END,
    son_doyuse_qosulma_vaxti = CASE
        WHEN doyus_bildirisi = 2
            THEN son_doyuse_qosulma_vaxti
        ELSE NOW()
    END

            WHERE qrup_id = :qrup_id

              AND user_id = :user_id

              AND status = 1

            LIMIT 1
        ");

    $stmt_bizim_ilk->execute([

        ':qrup_id' =>
            $qrup_id,

        ':user_id' =>
            $bizim_rehber_id

    ]);

}


/* =========================================================
   AVTOMATİK RƏQİB DÖYÜŞÇÜ
========================================================= */

$stmt_reqib_doyus =
    $pdo_qrup->prepare("
        SELECT id
        FROM qrup_uzvleri
        WHERE qrup_id = :qrup_id
          AND terefi != :teref
          AND status = 1
          AND doyuse_qosuldu = 1
        LIMIT 1
    ");

$stmt_reqib_doyus->execute([

    ':qrup_id' =>
        $qrup_id,

    ':teref' =>
        $menim_terefim

]);

$reqib_doyusde_olan =
    $stmt_reqib_doyus->fetch();


if (!$reqib_doyusde_olan) {

    $stmt_ilk_reqib =
        $pdo_qrup->prepare("
            SELECT id
            FROM qrup_uzvleri
            WHERE qrup_id = :qrup_id
              AND terefi != :teref
              AND status = 1
            ORDER BY
                giris_sirasi ASC,
                id ASC
            LIMIT 1
        ");

    $stmt_ilk_reqib->execute([

        ':qrup_id' =>
            $qrup_id,

        ':teref' =>
            $menim_terefim

    ]);

    $ilk_reqib =
        $stmt_ilk_reqib->fetch();


    if ($ilk_reqib) {

        $stmt_reqib_ilk =
            $pdo_qrup->prepare("
                UPDATE qrup_uzvleri

               SET
    doyuse_qosuldu = 1,
    doyus_bildirisi = CASE
        WHEN doyus_bildirisi = 2 THEN 2
        ELSE 0
    END,
    son_doyuse_qosulma_vaxti = CASE
        WHEN doyus_bildirisi = 2
            THEN son_doyuse_qosulma_vaxti
        ELSE NOW()
    END

                WHERE id = :uzv_id

                  AND qrup_id = :qrup_id

                  AND status = 1

                LIMIT 1
            ");

        $stmt_reqib_ilk->execute([

            ':uzv_id' =>
                (int)$ilk_reqib['id'],

            ':qrup_id' =>
                $qrup_id

        ]);

    }

}


/* =========================================================
   GO = IZLE
========================================================= */

$izle_status = 0;
$izle_bildiris = 0;


if ($go === 'izle') {

    $stmt_izle =
        $pdo_qrup->prepare("
            SELECT
                doyuse_qosuldu,
                doyus_bildirisi
            FROM qrup_uzvleri
            WHERE qrup_id = :qrup_id
              AND user_id = :user_id
              AND status = 1
            LIMIT 1
        ");

    $stmt_izle->execute([

        ':qrup_id' =>
            $qrup_id,

        ':user_id' =>
            $my_id

    ]);

    $izle_data =
        $stmt_izle->fetch();


    if ($izle_data) {

        $izle_status =
            (int)$izle_data['doyuse_qosuldu'];

        $izle_bildiris =
            (int)$izle_data['doyus_bildirisi'];

    }

}


/* =========================================================
   MƏNİM STATUSUM
========================================================= */

$stmt_menim =
    $pdo_qrup->prepare("
        SELECT
            doyuse_qosuldu,
            doyus_bildirisi,
            son_doyuse_qosulma_vaxti
        FROM qrup_uzvleri
        WHERE qrup_id = :qrup_id
          AND user_id = :user_id
          AND status = 1
        LIMIT 1
    ");

$stmt_menim->execute([

    ':qrup_id' =>
        $qrup_id,

    ':user_id' =>
        $my_id

]);

$menim_status =
    $stmt_menim->fetch();

/* =========================================================
   QRUP DÖYÜŞÜNÜN SON NƏTİCƏSİ
========================================================= */

$qrup_netice = null;

$stmt_netice = $pdo_qrup->prepare("
    SELECT
        qd.bitdi,
        qd.qalib_id,
        qd.bitdi_qalib,
        qd.bitdi_meglub,
        qd.hec_hece,
        qd.novbe_baslama_tarixi,
        qd.oyuncu1_id,
        qd.oyuncu2_id
    FROM qrup_doyusleri qd
    LEFT JOIN qrup_uzvleri qu
        ON qu.qrup_id = qd.qrup_id
       AND qu.user_id = qd.qalib_id
       AND qu.status = 1
    WHERE qd.qrup_id = :qrup_id
    ORDER BY qd.id DESC
    LIMIT 1
");

$stmt_netice->execute([
    ':qrup_id' => $qrup_id
]);

$qrup_netice = $stmt_netice->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   ZƏRBƏ ÜÇÜN QALAN VAXT
   TIMEOUT OLAN OYUNÇU HƏMİŞƏ 0-DA QALIR
========================================================= */

$zerbe_qalan_saniye = 10;

$baslama_vaxti = null;

/*
 * Əvvəlcə mənim timeout olub-olmadığımı yoxlayırıq.
 */
if (
    $menim_status &&
    isset($menim_status['doyus_bildirisi']) &&
    (int)$menim_status['doyus_bildirisi'] === 2
) {

    /*
     * TIMEOUT OLAN OYUNÇU:
     * 0 saniyədə qalır.
     */
    $zerbe_qalan_saniye = 0;

} else {

    /*
     * Aktiv döyüş varsa,
     * raundun başlanğıc vaxtını götürürük.
     */
    $stmt_aktiv_vaxt = $pdo_qrup->prepare("
        SELECT
            novbe_baslama_tarixi
        FROM qrup_doyusleri
        WHERE qrup_id = :qrup_id
          AND bitdi = 0
          AND novbe_baslama_tarixi IS NOT NULL
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt_aktiv_vaxt->execute([
        ':qrup_id' => $qrup_id
    ]);

    $aktiv_doyus_vaxt =
        $stmt_aktiv_vaxt->fetch(PDO::FETCH_ASSOC);

    if (
        $aktiv_doyus_vaxt &&
        !empty($aktiv_doyus_vaxt['novbe_baslama_tarixi'])
    ) {

        $baslama_vaxti =
            $aktiv_doyus_vaxt['novbe_baslama_tarixi'];

    } elseif (
        $menim_status &&
        !empty($menim_status['son_doyuse_qosulma_vaxti'])
    ) {

        $baslama_vaxti =
            $menim_status['son_doyuse_qosulma_vaxti'];
    }

    /*
     * Normal aktiv oyunçu üçün 59 saniyə hesabla.
     */
    if ($baslama_vaxti !== null) {

        $baslama = strtotime($baslama_vaxti);

        if ($baslama !== false) {

            $zerbe_qalan_saniye =
                10 - (time() - $baslama);
        }
    }

    $zerbe_qalan_saniye =
        max(
            0,
            min(
                10,
                (int)$zerbe_qalan_saniye
            )
        );
}
/* =========================================================
   MƏNİM NƏTİCƏM
========================================================= */

$menim_qrup_neticesi = null;

if (
    $qrup_netice &&
    (int)$qrup_netice['bitdi'] === 1
) {

    if (
    isset($qrup_netice['hec_hece']) &&
    (int)$qrup_netice['hec_hece'] === 1
) {

    $menim_qrup_neticesi = 'hec_hece';

} elseif (
    isset($qrup_netice['qalib_id'])
) {

    if (
        (int)$qrup_netice['qalib_id'] ===
        (int)$my_id
    ) {
        $menim_qrup_neticesi = 'qalib';

    } else {
        $menim_qrup_neticesi = 'meglub';
    }
}
}


/* =========================================================
   HTML
========================================================= */

?>

<!DOCTYPE html>

<html>

<head>

<meta name="robots" content="ALL">

<meta
name="keywords"
content="klan.az, azgame, azgame.biz, online oyun, qrup doyusleri, klan doyusleri"
>

<meta
name="description"
content="Azerbaycanda ilk Mobil Online oyunu."
>

<link
rel="stylesheet"
href="http://klanaz.com/klan/css.css"
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

<title>Qrup döyüşləri</title>


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


<!-- =====================================================
     HEADER
===================================================== -->

<div id="header">

    <a href="menu.php">

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


            <?php if ($unread_count > 0): ?>

                <a href="arxiv.php?go=goster">

                    <img
                    src="img/mektub.gif"
                    title="Məktub"
                    alt=""
                    >

                </a>

                (<?php echo $unread_count; ?>)

            <?php endif; ?>


            <?php if (isset($dostluq_sayi) && $dostluq_sayi > 0): ?>

                <a href="dostlar.php">

                    <img
                    src="muxtelif/dost_pilus.png"
                    title="Dost"
                    alt=""
                    >

                </a>

                (<?php echo $dostluq_sayi; ?>)

            <?php endif; ?>

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
     EXPERIENCE
===================================================== -->

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


<!-- =====================================================
     IZLE
===================================================== -->

<?php if ($go === 'izle'): ?>

    <?php if ($izle_status === 1): ?>

        <?php if ($izle_bildiris === 1): ?>

            <div class="success">

                <img
                src="muxtelif/okey.png"
                alt=""
                >

                Siz Döyüşə Buraxılırsız

                <br>

            </div>


            <div class="menu">

                <li>

                    <a
                    href="fight_hucum.php?uid=<?php echo $my_id; ?>&lis=<?php echo $qrup_id; ?>"
                    >

                        Döyüşə daxil ol...

                    </a>

                </li>

            </div>


            <?php

            /*
             * Bildiriş artıq göstərildi.
             *
             * Ona görə bildirisi 0 edirik.
             *
             * Növbəti refresh zamanı:
             *
             * doyus_bildirisi = 0
             *
             * olacaq və həm bildiriş,
             * həm də "Döyüşə daxil ol..." görünməyəcək.
             */

            $stmt_bildiris_sil = $pdo_qrup->prepare("
                UPDATE qrup_uzvleri

                SET
                    doyus_bildirisi = 0

                WHERE qrup_id = :qrup_id

                  AND user_id = :user_id

                  AND status = 1

                  AND doyuse_qosuldu = 1

                  AND doyus_bildirisi = 1

                LIMIT 1
            ");

            $stmt_bildiris_sil->execute([
                ':qrup_id' => $qrup_id,
                ':user_id' => $my_id
            ]);

            ?>

        <?php endif; ?>


    <?php else: ?>

        <div class="error">

            Siz hazırda döyüşə qoşulmamısınız.

        </div>

    <?php endif; ?>

<?php endif; ?>

<?php if ($go !== 'izle'): ?>
<?php if ($menim_qrup_neticesi === null): ?>
<!-- =====================================================
     ƏSAS STATUS
===================================================== -->

<?php if (!empty($_SESSION['qrup_doyus_error'])): ?>

    <div class="error">

        <img
        src="muxtelif/eror.png"
        alt=""
        >

        <?php
        echo htmlspecialchars(
            $_SESSION['qrup_doyus_error'],
            ENT_QUOTES,
            'UTF-8'
        );
        ?>

        <br>

    </div>

    <?php
    unset($_SESSION['qrup_doyus_error']);
    ?>

<?php endif; ?>


<?php if (
    $menim_status &&
    (int)$menim_status['doyuse_qosuldu'] === 1
): ?>

   <small>

    Zərbə atmaq üçün qalan vaxt:
    <span id="zerbe-qalan-vaxt"><?php echo $zerbe_qalan_saniye; ?></span>
    saniyə.

    <a
        href="fight_hucum.php?uid=<?php echo $my_id; ?>&lis=<?php echo $qrup_id; ?>"
    >
        Döyüşə daxil olun
    </a>

</small>

<script>
(function () {

    var saygac = document.getElementById('zerbe-qalan-vaxt');

    if (!saygac) {
        return;
    }

    var qalan = parseInt(saygac.textContent, 10);

    if (isNaN(qalan)) {
        return;
    }

    var interval = setInterval(function () {

        if (qalan <= 0) {
            qalan = 0;
            saygac.textContent = qalan;
            clearInterval(interval);
            return;
        }

        qalan--;

        saygac.textContent = qalan;

    }, 1000);

})();
</script>

<?php else: ?>


    <?php if (!empty($_SESSION['qrup_doyus_success'])): ?>

        <div class="success">

            <img
            src="muxtelif/okey.png"
            alt=""
            >

            Qeyd etdiyiniz şəxs döyüşə göndərildi

            <br>

        </div>

        <?php
        unset($_SESSION['qrup_doyus_success']);
        ?>

    <?php endif; ?>


    <small>

        Sizin qrupun növbəsidir
        (43 san)

    </small>


<?php endif; ?>


<br>
<br>
<br>
<?php endif; ?>
<?php if ($menim_qrup_neticesi === 'hec_hece'): ?>

<div class="error">
    <b>HEÇ-HEÇƏ!</b><br/>
    Hər iki tərəf zərbə vurmadı.
</div>

<?php endif; ?>
Yəni sıra belə olacaq:

<?php if ($menim_qrup_neticesi === 'hec_hece'): ?>

<div class="error">
    <b>HEÇ-HEÇƏ!</b><br/>
    Hər iki tərəf zərbə vurmadı.
</div>

<?php endif; ?>

<?php if ($menim_qrup_neticesi === 'qalib'): ?>
<!-- =====================================================
     DÖYÜŞ NƏTİCƏSİ — QALİB
===================================================== -->


<div class="list2">

    <b>Siz Qalib Gəldiz</b><br/>

</div>

<div class="list1">

    <p align="left">

        <u>Eldə etdiniz:</u><br/>

        <b>Qızıl:</b> 0<br/>

        <b>Təcrübə:</b> +4<br/>

        <b>Rang:</b> 4<br/>

    </p>


    <div class="menu">

        <br/>

        <li>
            <a href="online.php">
                Online Döyüşçülər
            </a>
        </li>

        <li>
            <a href="menu.php">
                Ana səhifə
            </a>
        </li>

        <div class="line"></div>

    </div>

</div>

<br/>
<br/>
<?php elseif ($menim_qrup_neticesi === 'meglub'): ?>
<!-- =====================================================
     DÖYÜŞ NƏTİCƏSİ — MƏĞLUB
===================================================== -->


<div class="list2">

    <b>Siz Məğlub Oldunuz</b><br/>

</div>

<div class="list1">

    <p align="left">

        <u>Eldə etdiniz:</u><br/>

        <b>Təcrübə:</b> 0<br/>

        <b>Rang:</b> 0<br/>

    </p>


    <div class="menu">

        <br/>

        <li>
            <a href="online.php">
                Online Döyüşçülər
            </a>
        </li>

        <li>
            <a href="menu.php">
                Ana səhifə
            </a>
        </li>

        <div class="line"></div>

    </div>

</div>

<br/>
<br/>
<?php elseif ($menim_qrup_neticesi === 'hec_hece'): ?>
<!-- =====================================================
     DÖYÜŞ NƏTİCƏSİ — HEÇ-HEÇƏ
===================================================== -->


<div class="list2">

    <b>Döyüş heç-heçə oldu.</b><br/>

</div>

<div class="list1">

    <p align="left">

        <u>Eldə etdiniz:</u><br/>

        <b>Qızıl:</b> 0<br/>

        <b>Təcrübə:</b> +3<br/>

        <b>Rang:</b> 0<br/>

    </p>


    <div class="menu">

        <br/>

        <li>
            <a href="online.php">
                Online Döyüşçülər
            </a>
        </li>

        <li>
            <a href="menu.php">
                Ana səhifə
            </a>
        </li>

        <div class="line"></div>

    </div>

</div>

<br/>
<br/>
<?php endif; ?>
<!-- =====================================================
     BİZİM QRUP
===================================================== -->

<div class="center">

    <div class="block_line">

        Sizin qrupun üzvləri

    </div>

</div>


<br>


<div class="battle_log">


<?php if (!empty($bizim_uzvler)): ?>


    <?php

    $bizim_uzvler_goster =
        array_reverse($bizim_uzvler);

    ?>


    <?php foreach (
        $bizim_uzvler_goster
        as $uzv
    ): ?>


        <?php

$user_id =
    (int)$uzv['user_id'];


/* =================================================
   OYUNÇUNUN CANINI YALNIZ ÖZ MƏLUMATINDAN GÖTÜR
================================================= */

$maks_can =
    (int)$ilkin_can +
    (int)$uzv['can'];



/* ƏVVƏLKİ DÖYÜŞDƏN QALAN CAN VARSA */
if ($uzv['son_can'] !== null) {

    $can =
        (int)$uzv['son_can'];


/* HAZIRDA 1-Cİ SLOTDA DÖYÜŞÜRSƏ */
} elseif (
    !empty($uzv['oyuncu1_id']) &&
    (int)$uzv['oyuncu1_id'] === $user_id &&
    $uzv['oyuncu1_can'] !== null
) {

    $can =
        (int)$uzv['oyuncu1_can'];


/* HAZIRDA 2-Cİ SLOTDA DÖYÜŞÜRSƏ */
} elseif (
    !empty($uzv['oyuncu2_id']) &&
    (int)$uzv['oyuncu2_id'] === $user_id &&
    $uzv['oyuncu2_can'] !== null
) {

    $can =
        (int)$uzv['oyuncu2_can'];


/* HEÇ DÖYÜŞMƏYİB / REZERVDƏDİR */
} else {

    $can =
        (int)$ilkin_can +
        (int)$uzv['can'];
}


/* CAN 0-DAN AŞAĞI OLA BİLMƏZ */
if ($can < 0) {

    $can = 0;
}


        if ((int)$uzv['movqe'] === 2) {

            $renk = '#0F7100';

        } elseif ((int)$uzv['movqe'] === 1) {

            $renk = 'red';

        } elseif ((int)$uzv['movqe'] === 3) {

            $renk = 'rgb(0,0,255)';

        } else {

            $renk = 'white';

        }

        ?>


        <span style="margin-left:25px;">


            <?php if (
                (int)$uzv['doyuse_qosuldu'] === 1
            ): ?>


                <img
                src="muxtelif/doyush.png"
                alt=""
                >


           <?php elseif (
    $my_id === $bizim_rehber_id &&
    $can > 0
): ?>


                <a
                href="qrup_komek.php?go=kecid&uid=<?php echo $user_id; ?>&lis=<?php echo $qrup_id; ?>"
                >

                    (Go)

                </a>


            <?php endif; ?>


           <?php
$timeout_xetti = false;
echo '<!-- BILDIRIS=' . (int)($uzv['doyus_bildirisi'] ?? -1) . ' -->';
if (
    isset($uzv['doyus_bildirisi']) &&
    (int)$uzv['doyus_bildirisi'] === 2
) {
    $timeout_xetti = true;
}


if (
    isset($uzv['bitdi']) &&
    (int)$uzv['bitdi'] === 0 &&
    !empty($uzv['novbe_baslama_tarixi'])
) {

    $timeout_baslama = strtotime(
        $uzv['novbe_baslama_tarixi']
    );

    if (
        $timeout_baslama !== false &&
        (time() - $timeout_baslama) >= 10
    ) {

        if (
            (int)$uzv['user_id'] === (int)$uzv['oyuncu1_id'] &&
            $uzv['oyuncu1_hucum'] === null
        ) {
            $timeout_xetti = true;
        }

        if (
            (int)$uzv['user_id'] === (int)$uzv['oyuncu2_id'] &&
            $uzv['oyuncu2_hucum'] === null
        ) {
            $timeout_xetti = true;
        }
    }
}
?>

<a
href="infoforce.php?uid=<?php echo $user_id; ?>"
>


    <u<?php echo (
        $can <= 0 || $timeout_xetti
            ? ' style="text-decoration:line-through;"'
            : ''
    ); ?>>

        <font
        color="<?php echo $renk; ?>"
        >

                        <?php

                        echo htmlspecialchars(
                            $uzv['login'],
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        ?>

                        [<?php
                        echo (int)$uzv['oyuncunun_seviyyesi'];
                        ?>]

                    </font>

                </u>

            </a>


            (<?php echo $can; ?><b>/</b><?php echo $maks_can; ?>)

            <br>

        </span>


    <?php endforeach; ?>


<?php else: ?>


    <i>

        Sizin qrupda döyüşçü yoxdur.

    </i>


<?php endif; ?>


</div>


<br>


<!-- =====================================================
     RƏQİB QRUP
===================================================== -->

<div class="center">

    <div class="block_line">

        Rəqib qrupun üzvləri

    </div>

</div>


<br>


<div class="battle_log">


<?php if (!empty($reqib_uzvler)): ?>


    <?php

    $reqib_uzvler_goster =
        array_reverse($reqib_uzvler);

    ?>


    <?php foreach (
        $reqib_uzvler_goster
        as $uzv
    ): ?>


        <?php

  $user_id =
    (int)$uzv['user_id'];


/* =================================================
   OYUNÇUNUN CANINI YALNIZ ÖZ MƏLUMATINDAN GÖTÜR
================================================= */

$maks_can =
    (int)$ilkin_can +
    (int)$uzv['can'];


/* ƏVVƏLKİ DÖYÜŞDƏN QALAN CAN VARSA */
if ($uzv['son_can'] !== null) {

    $can =
        (int)$uzv['son_can'];


/* HAZIRDA 1-Cİ SLOTDA DÖYÜŞÜRSƏ */
} elseif (
    !empty($uzv['oyuncu1_id']) &&
    (int)$uzv['oyuncu1_id'] === $user_id
) {

    $can =
        (int)$uzv['oyuncu1_can'];


/* HAZIRDA 2-Cİ SLOTDA DÖYÜŞÜRSƏ */
} elseif (
    !empty($uzv['oyuncu2_id']) &&
    (int)$uzv['oyuncu2_id'] === $user_id
) {

    $can =
        (int)$uzv['oyuncu2_can'];


/* HEÇ DÖYÜŞMƏYİB / REZERVDƏDİR */
} else {

    $can =
        (int)$ilkin_can +
        (int)$uzv['can'];
}


/* CAN 0-DAN AŞAĞI OLA BİLMƏZ */
if ($can < 0) {

    $can = 0;
}
        if ((int)$uzv['movqe'] === 2) {

            $renk = '#0F7100';

        } elseif ((int)$uzv['movqe'] === 1) {

            $renk = 'red';

        } elseif ((int)$uzv['movqe'] === 3) {

            $renk = 'rgb(0,0,255)';

        } else {

            $renk = 'white';

        }

        ?>


        <span style="margin-left:25px;">


            <?php if (
                (int)$uzv['doyuse_qosuldu'] === 1
            ): ?>


                <img
                src="muxtelif/doyush.png"
                alt=""
                >


          <?php elseif (
    $my_id === $reqib_rehber_id &&
    $can > 0
): ?>


                <a
                href="qrup_komek.php?go=kecid&uid=<?php echo $user_id; ?>&lis=<?php echo $qrup_id; ?>"
                >

                    (Go)

                </a>


            <?php endif; ?>


            <?php
$timeout_xetti = false;
echo '<!-- BILDIRIS=' . (int)($uzv['doyus_bildirisi'] ?? -1) . ' -->';
if (
    isset($uzv['doyus_bildirisi']) &&
    (int)$uzv['doyus_bildirisi'] === 2
) {
    $timeout_xetti = true;
}


if (
    isset($uzv['bitdi']) &&
    (int)$uzv['bitdi'] === 0 &&
    !empty($uzv['novbe_baslama_tarixi'])
) {

    $timeout_baslama = strtotime(
        $uzv['novbe_baslama_tarixi']
    );

    if (
        $timeout_baslama !== false &&
        (time() - $timeout_baslama) >= 10
    ) {

        if (
            (int)$uzv['user_id'] === (int)$uzv['oyuncu1_id'] &&
            $uzv['oyuncu1_hucum'] === null
        ) {
            $timeout_xetti = true;
        }

        if (
            (int)$uzv['user_id'] === (int)$uzv['oyuncu2_id'] &&
            $uzv['oyuncu2_hucum'] === null
        ) {
            $timeout_xetti = true;
        }
    }
}
?>

<a
href="infoforce.php?uid=<?php echo $user_id; ?>"
>

    <u<?php echo (
        $can <= 0 || $timeout_xetti
            ? ' style="text-decoration:line-through;"'
            : ''
    ); ?>>
        <font
        color="<?php echo $renk; ?>"
        >

                        <?php

                        echo htmlspecialchars(
                            $uzv['login'],
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        ?>

                        [<?php
                        echo (int)$uzv['oyuncunun_seviyyesi'];
                        ?>]

                    </font>

                </u>

            </a>


            (<?php echo $can; ?><b>/</b><?php echo $maks_can; ?>)

            <br>

        </span>


    <?php endforeach; ?>


<?php else: ?>


    <i>

        Rəqib qrupda döyüşçü yoxdur.

    </i>


<?php endif; ?>


</div>


<br>


<!-- =====================================================
     MENYU
===================================================== -->

<a
href="qrup_komek.php?lis=<?php echo $qrup_id; ?>"
>

    Yenilə

</a>


|


<?php if (
    $go === 'mesajlar' ||
    $go === 'yaz'
): ?>


    <a
    href="qrup_komek.php?go=gedishat&lis=<?php echo $qrup_id; ?>"
    >

        Oyunun Gedişatı

    </a>


<?php else: ?>


    <a
    href="qrup_komek.php?go=mesajlar&lis=<?php echo $qrup_id; ?>"
    >

        Mesajlar
        (<?php echo $qrup_mesaj_sayi; ?>)

    </a>


<?php endif; ?>


<br>


<!-- =====================================================
     MESAJLAR
===================================================== -->

<?php if (
    $go === 'mesajlar' ||
    $go === 'yaz'
): ?>


    <form
    method="post"
    action="qrup_komek.php?go=yaz&lis=<?php echo $qrup_id; ?>"
    >

        <input
        name="message"
        value=""
        maxlength="120"
        >

        <br>

        <input
        type="submit"
        class="button"
        value="Göndər"
        >

    </form>


    <hr>


    <?php if (!empty($qrup_mesajlar)): ?>


        <?php foreach (
            $qrup_mesajlar
            as $mesaj
        ): ?>


            <?php

            if ((int)$mesaj['movqe'] === 2) {

                $renk = '#0F7100';

            } elseif ((int)$mesaj['movqe'] === 1) {

                $renk = 'red';

            } elseif ((int)$mesaj['movqe'] === 3) {

                $renk = 'rgb(0,0,255)';

            } else {

                $renk = 'white';

            }

            ?>


            <a
            href="infoforce.php?uid=<?php echo (int)$mesaj['user_id']; ?>"
            >

                <u>

                    <font
                    color="<?php echo $renk; ?>"
                    >

                        <?php

                        echo htmlspecialchars(
                            $mesaj['login'],
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        ?>

                    </font>

                </u>

            </a>


            »


            <?php

            echo htmlspecialchars(
                $mesaj['mesaj'],
                ENT_QUOTES,
                'UTF-8'
            );

            ?>


            <br>


        <?php endforeach; ?>


    <?php else: ?>


        <i>

            Hələ heç bir mesaj yoxdur.

        </i>


    <?php endif; ?>


<?php endif; ?>


<!-- =====================================================
     OYUNUN GEDİŞATI
===================================================== -->

<?php if ($go === 'gedishat'): ?>

    <div class="battle_log">

        Oyunun gedişatı burada görünəcək.

    </div>

<?php endif; ?>


<!-- =====================================================
     AJAX DÖYÜŞ YOXLAMA
===================================================== -->

<script>

var doyusYonlendirme = false;


function doyusYoxla() {

    if (doyusYonlendirme) {

        return;

    }


    fetch(
        "qrup_komek.php?go=yoxla_doyus&lis=<?php echo $qrup_id; ?>&t=" + Date.now(),
        {
            method: "GET",
            cache: "no-store",
            credentials: "same-origin"
        }
    )

    .then(function(response) {

        return response.text();

    })

    .then(function(text) {

        /*
         * Əvvəlcə RAW cavabı görürük.
         *
         * Əgər server səhvən HTML qaytarsa,
         * JSON parse xətası vermək əvəzinə
         * console-da cavabı göstərəcək.
         */

        console.log(
            "DÖYÜŞ AJAX RAW:",
            text
        );


        var data;


        try {

            data =
                JSON.parse(text);

        } catch (e) {

            console.error(
                "JSON xətası. Server JSON əvəzinə bunu qaytardı:",
                text
            );

            return;

        }


        console.log(
            "DÖYÜŞ BİLDİRİŞİ:",
            data
        );


        var doyus =
            parseInt(
                data.doyus || 0,
                10
            );


        var bildiris =
            parseInt(
                data.bildiris || 0,
                10
            );


        /*
         * YALNIZ RƏHBƏRİN GO İLƏ
         * SEÇDİYİ İSTİFADƏÇİ:
         *
         * doyuse_qosuldu = 1
         *
         * doyus_bildirisi = 1
         *
         * alacaq.
         */

        if (
            doyus === 1 &&
            bildiris === 1
        ) {

            doyusYonlendirme = true;


            window.location.replace(
                "qrup_komek.php?go=izle&lis=<?php echo $qrup_id; ?>&t=" + Date.now()
            );

        }

    })

    .catch(function(error) {

        console.error(
            "Döyüş yoxlama xətası:",
            error
        );

    });

}


/*
 * SƏHİFƏ AÇILAN KİMİ
 */

doyusYoxla();


/*
 * HƏR 2 SANİYƏDƏN BİR
 */

setInterval(
    doyusYoxla,
    2000
);

</script>
<?php endif; ?>

</div>

<script type="text/javascript">

(function () {

    "use strict";

    var timeoutYoxlanir = false;
var timeoutInterval = null;
    function timeoutYoxla() {

        if (timeoutYoxlanir) {
            return;
        }

        timeoutYoxlanir = true;

        var xhr =
            new XMLHttpRequest();

        var url =
            "qrup_komek.php"
            + "?lis=<?php echo (int)$qrup_id; ?>"
            + "&timeout_yoxla=1"
            + "&t="
            + new Date().getTime();
console.log("TIMEOUT AJAX URL:", url);

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

                timeoutYoxlanir = false;

                if (xhr.status !== 200) {
                    return;
                }

                try {

                    var cavab =
                        JSON.parse(
                            xhr.responseText
                        );

if (
    cavab.ok &&
    cavab.reload === 1
) {

    /*
     * Nəticə artıq serverdə hesablandı.
     * Səhifəni avtomatik yeniləmə.
     *
     * İstifadəçi özü F5 / Refresh edəndə
     * nəticə ekranda görünəcək.
     */
    clearInterval(timeoutInterval);

    timeoutYoxlanir = true;

    return;
}


                } catch (e) {}

            };

        xhr.onerror =
            function () {
                timeoutYoxlanir = false;
            };

        try {
            xhr.send(null);
        } catch (e) {
            timeoutYoxlanir = false;
        }

    }


    timeoutYoxla();

  timeoutInterval = setInterval(
    timeoutYoxla,
    1000
);

})();

</script>

</body>

</html>

<?php

ob_end_flush();

?>
