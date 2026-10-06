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

/* =========================================================
   QRUP DÖYÜŞ DATABASE
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
/* =========================================================
   QRUP ÜZVLƏRİ ÜÇÜN BOŞ DƏYƏR
========================================================= */

$qrup_uzvleri = [];
/* =========================================================
   DÖYÜŞ OTAĞI
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'qrup' &&
    isset($_GET['id'])
) {

    $qrup_id = (int)$_GET['id'];

    if ($qrup_id <= 0) {
        exit('Yanlış qrup.');
    }

    /* QRUP MƏLUMATLARI */

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
        exit('Qrup tapılmadı.');
    }


    /* =====================================================
       QRUP ÜZVLƏRİ
    ===================================================== */

   $stmt_uzvler = $qrupPdo->prepare("
    SELECT
        id,
        user_id,
        terefi,
        giris_sirasi,
        gelme_novu,
        devet_eden_id,
        daxil_olma_vaxti,
        status
    FROM qrup_uzvleri
    WHERE qrup_id = :qrup_id
      AND status = 1
    ORDER BY giris_sirasi ASC
");

$stmt_uzvler->execute([
    ':qrup_id' => $qrup_id
]);

$qrup_uzvleri = $stmt_uzvler->fetchAll(PDO::FETCH_ASSOC);
}
/* =====================================================
   QRUP ÜZVLƏRİNİN ƏSAS USER MƏLUMATLARINI GƏTİR
===================================================== */
foreach ($qrup_uzvleri as &$uzv) {

    $stmt_user_info = $pdo->prepare("
        SELECT login, oyuncunun_seviyyesi,movqe
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_user_info->execute([
        ':id' => (int)$uzv['user_id']
    ]);

    $user_info = $stmt_user_info->fetch(PDO::FETCH_ASSOC);

    if ($user_info) {

        $uzv['login'] = $user_info['login'];

        $uzv['oyuncunun_seviyyesi'] =
            (int)$user_info['oyuncunun_seviyyesi'];
               $uzv['movqe'] =
        (int)$user_info['movqe'];

    } else {

        $uzv['login'] = 'Naməlum';

        $uzv['oyuncunun_seviyyesi'] = 0;
    }
}

unset($uzv);


/* =========================================================
   YENİ QRUP YARAT
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_GET['go']) &&
    $_GET['go'] === 'ok' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save'
) {

    /* -------------------------
       FORM MƏLUMATLARI
    ------------------------- */

    $otaq_adi = trim($_POST['alish_min'] ?? '');

    $oyuncu_tutumu = (int)($_POST['bb'] ?? 0);

    $minimum_merhele = $_POST['nn'] ?? 'nn';

    $maksimum_merhele = $_POST['mm'] ?? '0';

    $qizil = (int)($_POST['aa'] ?? 0);

    $doyus_novu = (int)($_POST['cc'] ?? 0);


    /* -------------------------
       OTAQ ADI
    ------------------------- */

    if ($otaq_adi === '') {
        exit('Otağın adını yazın.');
    }

    if (mb_strlen($otaq_adi, 'UTF-8') > 15) {
        exit('Otağın adı maksimum 15 simvol ola bilər.');
    }


    /* -------------------------
       OYUNÇU TUTUMU
    ------------------------- */

    $icaze_verilen_tutumlar = [2, 6, 8, 10, 12];

    if (!in_array($oyuncu_tutumu, $icaze_verilen_tutumlar, true)) {
        exit('Yanlış oyunçu tutumu.');
    }


    /* -------------------------
       MINIMUM MƏRHƏLƏ
    ------------------------- */

    if ($minimum_merhele === 'nn') {
        $minimum_merhele = null;
    } else {
        $minimum_merhele = (int)$minimum_merhele;

        if ($minimum_merhele < 3 || $minimum_merhele > 13) {
            exit('Yanlış minimum mərhələ.');
        }
    }


    /* -------------------------
       MAKSİMUM MƏRHƏLƏ
    ------------------------- */

    if ($maksimum_merhele === '0') {
        $maksimum_merhele = null;
    } else {
        $maksimum_merhele = (int)$maksimum_merhele;

        if ($maksimum_merhele < 3 || $maksimum_merhele > 13) {
            exit('Yanlış maksimum mərhələ.');
        }
    }


    /* -------------------------
       MƏRHƏLƏLƏRİ YOXLAYIRIQ
    ------------------------- */

    if (
        $minimum_merhele !== null &&
        $maksimum_merhele !== null &&
        $minimum_merhele > $maksimum_merhele
    ) {
        exit('Minimum mərhələ maksimum mərhələdən böyük ola bilməz.');
    }


    /* -------------------------
       QIZIL
    ------------------------- */

    $icaze_verilen_qizil = [0, 20, 50, 100];

    if (!in_array($qizil, $icaze_verilen_qizil, true)) {
        exit('Yanlış qızıl məbləği.');
    }


    /* -------------------------
       DÖYÜŞ NÖVÜ
    ------------------------- */

    if (!in_array($doyus_novu, [0, 1], true)) {
        exit('Yanlış döyüş növü.');
    }


    /* =====================================================
       HƏLƏLİK QRUPUN TƏRƏFİ
       
       0 = İnsan
       1 = Vampir
       
       Burada hələ formda tərəf seçimi yoxdur.
       Ona görə müvəqqəti olaraq İnsan edirik.
    ===================================================== */

    /* =====================================================
   QRUPUN TƏRƏFİ USER MOVQE-DƏN GƏLİR
===================================================== */

$stmt_movqe = $pdo->prepare("
    SELECT movqe
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt_movqe->execute([
    ':id' => $my_id
]);

$movqe_user = (int)$stmt_movqe->fetchColumn();

if (!in_array($movqe_user, [1, 2], true)) {
    exit('Qrup yaratmaq üçün İnsan və ya Vampir olmalısınız.');
}

$terefi = $movqe_user;


    /* =====================================================
       QRUPU SQL-Ə YAZ
    ===================================================== */

    $stmt_qrup = $qrupPdo->prepare("
        INSERT INTO qruplar (
            yaradan_id,
            otaq_adi,
            oyuncu_tutumu,
            minimum_merhele,
            maksimum_merhele,
            qizil,
            doyus_novu,
            terefi,
            reqib_qrup_id,
            status
        ) VALUES (
            :yaradan_id,
            :otaq_adi,
            :oyuncu_tutumu,
            :minimum_merhele,
            :maksimum_merhele,
            :qizil,
            :doyus_novu,
            :terefi,
            NULL,
            1
        )
    ");

    $stmt_qrup->execute([
        ':yaradan_id'      => $my_id,
        ':otaq_adi'        => $otaq_adi,
        ':oyuncu_tutumu'   => $oyuncu_tutumu,
        ':minimum_merhele' => $minimum_merhele,
        ':maksimum_merhele'=> $maksimum_merhele,
        ':qizil'           => $qizil,
        ':doyus_novu'      => $doyus_novu,
        ':terefi'          => $terefi
    ]);


    $qrup_id = (int)$qrupPdo->lastInsertId();


/* =====================================================
   QRUPU YARADAN OYUNÇUNU 1-Cİ ÜZV KİMİ ƏLAVƏ ET
===================================================== */

$stmt_uzv = $qrupPdo->prepare("
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
        1,
        0,
        NULL,
        NOW(),
        1
    )
");

$stmt_uzv->execute([
    ':qrup_id' => $qrup_id,
    ':user_id' => $my_id,
    ':terefi'  => $terefi
]);


/* =====================================================
   DÖYÜŞ OTAĞINA KEÇ
===================================================== */

header("Location: qrup_yarad.php?go=qrup&id=" . $qrup_id);
exit;
}


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

/* =========================================================
   DÖYÜŞÇÜ ÇAĞIR SƏHİFƏSİ
========================================================= */

$cagir_qrup = null;

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'cagir' &&
    isset($_GET['id'])
) {

    $cagir_qrup_id = (int)$_GET['id'];

    if ($cagir_qrup_id <= 0) {
        exit('Yanlış qrup.');
    }

    /* QRUPU GƏTİR */

    $stmt_cagir_qrup = $qrupPdo->prepare("
        SELECT *
        FROM qruplar
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_cagir_qrup->execute([
        ':id' => $cagir_qrup_id
    ]);

    $cagir_qrup = $stmt_cagir_qrup->fetch(PDO::FETCH_ASSOC);

    if (!$cagir_qrup) {
        exit('Qrup tapılmadı.');
    }

    /*
     * Burada yalnız online oyunçular göstəriləcək.
     * Özümüz göstərilməyəcəyik.
     */

}

/* =========================================================
   QRUP DƏVƏTİNİ QƏBUL ET
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'devet_qebul' &&
    isset($_GET['devet_id'])
) {

    $devet_id = (int)$_GET['devet_id'];

    if ($devet_id <= 0) {
        exit('Yanlış dəvət.');
    }

    /* =====================================================
       DƏVƏTİ GƏTİR
    ===================================================== */

    $stmt_devet_qebul = $qrupPdo->prepare("
        SELECT
            id,
            qrup_id,
            gonderen_id,
            alan_id,
            status
        FROM qrup_devetleri
        WHERE id = :id
          AND alan_id = :alan_id
          AND status = 0
        LIMIT 1
    ");

    $stmt_devet_qebul->execute([
        ':id'      => $devet_id,
        ':alan_id' => $my_id
    ]);

    $devet = $stmt_devet_qebul->fetch(PDO::FETCH_ASSOC);

    if (!$devet) {
        exit('Bu dəvət artıq qəbul edilib və ya mövcud deyil.');
    }

    $qrup_id = (int)$devet['qrup_id'];

    /* =====================================================
       QRUPU GƏTİR
    ===================================================== */

    $stmt_qrup_qebul = $qrupPdo->prepare("
        SELECT
            id,
            oyuncu_tutumu,
            terefi,
            status
        FROM qruplar
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_qrup_qebul->execute([
        ':id' => $qrup_id
    ]);

    $qrup_qebul = $stmt_qrup_qebul->fetch(PDO::FETCH_ASSOC);

    if (!$qrup_qebul) {
        exit('Qrup tapılmadı.');
    }

    /* =====================================================
       OYUNÇU ARTİQ QRUPDADIR?
    ===================================================== */

    $stmt_artiq_qrup = $qrupPdo->prepare("
        SELECT id
        FROM qrup_uzvleri
        WHERE qrup_id = :qrup_id
          AND user_id = :user_id
          AND status = 1
        LIMIT 1
    ");

    $stmt_artiq_qrup->execute([
        ':qrup_id' => $qrup_id,
        ':user_id' => $my_id
    ]);

    if ($stmt_artiq_qrup->fetch()) {

        /* Dəvəti artıq keçərsiz et */
        $stmt_devet_sil = $qrupPdo->prepare("
            UPDATE qrup_devetleri
            SET status = 1,
                cavab_vaxti = NOW()
            WHERE id = :id
            LIMIT 1
        ");

        $stmt_devet_sil->execute([
            ':id' => $devet_id
        ]);

        header(
            "Location: qrup_yarad.php?go=qrup&id=" . $qrup_id
        );
        exit;
    }

    /* =====================================================
       QRUPUN TUTUMUNU YOXLAYIRIQ
    ===================================================== */

    $stmt_uzv_sayi = $qrupPdo->prepare("
        SELECT COUNT(*)
        FROM qrup_uzvleri
        WHERE qrup_id = :qrup_id
          AND status = 1
    ");

    $stmt_uzv_sayi->execute([
        ':qrup_id' => $qrup_id
    ]);

    $uzv_sayi = (int)$stmt_uzv_sayi->fetchColumn();

    $oyuncu_tutumu = (int)$qrup_qebul['oyuncu_tutumu'];

    if ($uzv_sayi >= $oyuncu_tutumu) {
        exit('Qrup artıq doludur.');
    }

    /* =====================================================
       OYUNÇUNU QRUPA ƏLAVƏ ET
    ===================================================== */

    $giris_sirasi = $uzv_sayi + 1;

    $stmt_uzv_elave = $qrupPdo->prepare("
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
            1,
            :devet_eden_id,
            NOW(),
            1
        )
    ");

$stmt_uzv_elave->execute([
    ':qrup_id'        => $qrup_id,
    ':user_id'        => $my_id,
    ':terefi'         => ((int)$qrup_qebul['terefi'] === 1 ? 2 : 1),
    ':giris_sirasi'   => $giris_sirasi,
    ':devet_eden_id'  => (int)$devet['gonderen_id']
]);


    /* =====================================================
       DƏVƏTİ QƏBUL EDİLMİŞ KİMİ İŞARƏLƏ
    ===================================================== */

    $stmt_devet_qebul_update = $qrupPdo->prepare("
        UPDATE qrup_devetleri
        SET status = 1,
            cavab_vaxti = NOW()
        WHERE id = :id
          AND alan_id = :alan_id
          AND status = 0
        LIMIT 1
    ");

    $stmt_devet_qebul_update->execute([
        ':id'      => $devet_id,
        ':alan_id' => $my_id
    ]);

    /* =====================================================
       QRUPA KEÇ
    ===================================================== */

    header(
        "Location: qrup_yarad.php?go=qrup&id=" . $qrup_id
    );
    exit;
}

/* =========================================================
   DÖYÜŞÇÜYƏ QRUP DƏVƏTİ GÖNDƏR
========================================================= */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'cagir_ok' &&
    isset($_GET['uid']) &&
    isset($_GET['lis'])
) {

    $alan_id = (int)$_GET['uid'];
    $qrup_id = (int)$_GET['lis'];

    if ($alan_id <= 0 || $qrup_id <= 0) {
        exit('Yanlış məlumat.');
    }

    /* QRUPU YOXLAYIRIQ */

    $stmt_qrup_devet = $qrupPdo->prepare("
        SELECT *
        FROM qruplar
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_qrup_devet->execute([
        ':id' => $qrup_id
    ]);

    $devet_qrup = $stmt_qrup_devet->fetch(PDO::FETCH_ASSOC);

    if (!$devet_qrup) {
        exit('Qrup tapılmadı.');
    }

    /* YALNIZ QRUPU YARADAN DƏVƏT GÖNDƏRƏ BİLSİN */

    if ((int)$devet_qrup['yaradan_id'] !== $my_id) {
        exit('Bu qrupdan oyunçu dəvət etmək icazəniz yoxdur.');
    }

    /* ÖZÜNÜ DƏVƏT ETMƏ */

    if ($alan_id === $my_id) {
        exit('Özünüzü dəvət edə bilməzsiniz.');
    }

    /* EYNİ OYUNÇU QRUPDA VARSA */

    $stmt_var = $qrupPdo->prepare("
        SELECT id
        FROM qrup_uzvleri
        WHERE qrup_id = :qrup_id
          AND user_id = :user_id
          AND status = 1
        LIMIT 1
    ");

    $stmt_var->execute([
        ':qrup_id' => $qrup_id,
        ':user_id' => $alan_id
    ]);

    if ($stmt_var->fetch()) {
        exit('Bu oyunçu artıq qrupdadır.');
    }

    /* GÖNDƏRİLMİŞ GÖZLƏYƏN DƏVƏT VARSA */

    $stmt_yoxla = $qrupPdo->prepare("
        SELECT id
        FROM qrup_devetleri
        WHERE qrup_id = :qrup_id
          AND gonderen_id = :gonderen_id
          AND alan_id = :alan_id
          AND status = 0
        LIMIT 1
    ");

    $stmt_yoxla->execute([
        ':qrup_id'    => $qrup_id,
        ':gonderen_id'=> $my_id,
        ':alan_id'    => $alan_id
    ]);

    if ($stmt_yoxla->fetch()) {
        exit('Bu oyunçuya artıq dəvət göndərilib.');
    }

    /* =====================================================
       DƏVƏTİ SQL-Ə YAZ
    ===================================================== */

    $stmt_devet = $qrupPdo->prepare("
        INSERT INTO qrup_devetleri (
            qrup_id,
            gonderen_id,
            alan_id,
            status,
            yaradildi,
            cavab_vaxti
        )
        VALUES (
            :qrup_id,
            :gonderen_id,
            :alan_id,
            0,
            NOW(),
            NULL
        )
    ");

    $stmt_devet->execute([
        ':qrup_id'     => $qrup_id,
        ':gonderen_id' => $my_id,
        ':alan_id'     => $alan_id
    ]);

/* =====================================================
   DƏVƏT GÖNDƏRİLDİ
   QRUPUN ƏSAS SƏHİFƏSİNƏ QAYIT
===================================================== */

header("Location: qrup_yarad.php?go=qrup&id=" . $qrup_id . "&devet=ok");
exit;
}
/* =====================================================
   DÖYÜŞ BAŞLANMAZDAN ƏVVƏL RƏQİB YOXLAMASI
===================================================== */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'doyus_yoxla' &&
    isset($_GET['id'])
) {

    $yoxla_qrup_id = (int)$_GET['id'];

    if ($yoxla_qrup_id <= 0) {
        exit('Yanlış qrup.');
    }

    /* QRUPU GƏTİR */

    $stmt_yoxla_qrup = $qrupPdo->prepare("
        SELECT
            id,
            terefi,
            yaradildi,
            status
        FROM qruplar
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_yoxla_qrup->execute([
        ':id' => $yoxla_qrup_id
    ]);

    $yoxla_qrup = $stmt_yoxla_qrup->fetch(PDO::FETCH_ASSOC);

    if (!$yoxla_qrup) {
        exit('Qrup tapılmadı.');
    }



    /* =================================================
       SAYĞAC ÜÇÜN 178 SANİYƏ
    ================================================= */

    $doyus_muddeti = 10;

    $yaradildi_vaxti = strtotime($yoxla_qrup['yaradildi']);

    if ($yaradildi_vaxti === false) {
        exit('Qrupun yaradılma vaxtı düzgün deyil.');
    }

    $bitme_vaxti = $yaradildi_vaxti + $doyus_muddeti;

    /* Vaxt hələ bitməyibsə geri qaytar */

    if (time() < $bitme_vaxti) {

        header(
            "Location: qrup_yarad.php?go=qrup&id=" .
            $yoxla_qrup_id
        );

        exit;
    }

    /* =================================================
       QRUPUN AKTİV ÜZVLƏRİ
    ================================================= */

    $stmt_yoxla_uzvler = $qrupPdo->prepare("
        SELECT
            user_id,
            terefi
        FROM qrup_uzvleri
        WHERE qrup_id = :qrup_id
          AND status = 1
    ");

    $stmt_yoxla_uzvler->execute([
        ':qrup_id' => $yoxla_qrup_id
    ]);

    $yoxla_uzvler = $stmt_yoxla_uzvler->fetchAll(PDO::FETCH_ASSOC);

    /* =================================================
       RƏQİB VARMI?
    ================================================= */
$reqib_var = false;

foreach ($yoxla_uzvler as $uzv) {

    if ((int)$uzv['terefi'] !== (int)$yoxla_qrup['terefi']) {
        $reqib_var = true;
        break;
    }
}


    /* =================================================
       RƏQİB VARSA
    ================================================= */

if ($reqib_var) {

    header(
        "Location: qrup_komek.php?lis=" .
        $yoxla_qrup_id
    );

    exit;
}

/* =================================================
   RƏQİB YOXDUR — QRUPU AKTİV QRUPLARDAN SİL
================================================= */

$stmt_uzv_legv = $qrupPdo->prepare("
    UPDATE qrup_uzvleri
    SET status = 0
    WHERE qrup_id = :qrup_id
      AND status = 1
");

$stmt_uzv_legv->execute([
    ':qrup_id' => $yoxla_qrup_id
]);

/* QRUPUN ÖZÜNÜ DƏ AKTİVDƏN ÇIXAR */

$stmt_qrup_legv = $qrupPdo->prepare("
    UPDATE qruplar
    SET status = 0
    WHERE id = :qrup_id
      AND status = 1
");

$stmt_qrup_legv->execute([
    ':qrup_id' => $yoxla_qrup_id
]);

/* LƏĞV EKRANINA KEÇ */

header(
    "Location: qrup_yarad.php?go=legv&id=" . $yoxla_qrup_id
);
exit;
}

/* =====================================================
   QRUP DƏVƏTİNDƏN İMTİNA ET
===================================================== */

if (
    isset($_GET['go']) &&
    $_GET['go'] === 'devet_imtina' &&
    isset($_GET['devet_id'])
) {

    $devet_id = (int)$_GET['devet_id'];

    if ($devet_id <= 0) {
        exit('Yanlış dəvət.');
    }

    /* DƏVƏTİ YOXLAYIRIQ */
    $stmt_imtina = $qrupPdo->prepare("
        SELECT
            id,
            qrup_id,
            gonderen_id,
            alan_id,
            status
        FROM qrup_devetleri
        WHERE id = :id
          AND alan_id = :alan_id
          AND status = 0
        LIMIT 1
    ");

    $stmt_imtina->execute([
        ':id'      => $devet_id,
        ':alan_id' => $my_id
    ]);

    $imtina_devet = $stmt_imtina->fetch(PDO::FETCH_ASSOC);

    if (!$imtina_devet) {
        exit('Dəvət tapılmadı və ya artıq cavablandırılıb.');
    }



    /* =================================================
       DƏVƏTİ İMTİNA EDİLDİ KİMİ QEYD EDİRİK
       status:
       0 = gözləyir
       2 = imtina
    ================================================= */

    $stmt_imtina_update = $qrupPdo->prepare("
        UPDATE qrup_devetleri
        SET
            status = 2,
            cavab_vaxti = NOW()
        WHERE id = :id
          AND alan_id = :alan_id
          AND status = 0
        LIMIT 1
    ");

    $stmt_imtina_update->execute([
        ':id'      => $devet_id,
        ':alan_id' => $my_id
    ]);

    /* =================================================
       QRUPUN ÇAĞIR SƏHİFƏSİNƏ QAYIT
    ================================================= */

header("Location: menu.php");
exit;
}


?>

<!DOCTYPE html>
<html>
<head>
<meta name="robots" content="ALL" /> 
<meta name="keywords" content="klan.az ,azgame , azgame.biz , online oyun ,qrup doyusleri,klan doyusleri,qorxulu qalalar, " /> 
<meta name="description" content="Azerbaycanda ilk Mobil Online oyunu. Первый мобильный онлайн-игры" /> 

<link rel="stylesheet" href="http://klanaz.com/klan/css.css" type="text/css"/>

 <meta content="text/html; charset=utf-8" http-equiv="content-type" />

<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=3.0;"><title>qrup doyuslari</title>
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

<?php if (isset($_GET['go']) && $_GET['go'] === 'legv'): ?>
       
            Qarşı tərəfdə oyunçu olmadığına görə döyüş ləğv edildi
        

        <br/>


<?php elseif (isset($_GET['go']) && $_GET['go'] === 'cagir' && isset($cagir_qrup)): ?>

<?php
/* =====================================================
   BU QRUP ÜÇÜN GÖNDƏRİLMİŞ DƏVƏTLƏR
===================================================== */

$devet_edilenler = [];

$stmt_devetler = $qrupPdo->prepare("
    SELECT alan_id
    FROM qrup_devetleri
    WHERE qrup_id = :qrup_id
      AND gonderen_id = :gonderen_id
      AND status = 0
");

$stmt_devetler->execute([
    ':qrup_id'     => (int)$cagir_qrup['id'],
    ':gonderen_id' => $my_id
]);

foreach ($stmt_devetler->fetchAll(PDO::FETCH_COLUMN) as $alan_id) {
    $devet_edilenler[(int)$alan_id] = true;
}
/* =====================================================
   ARTİQ QRUPDA OLAN OYUNÇULAR
===================================================== */

$qrupda_olanlar = [];

$stmt_qrupda_olanlar = $qrupPdo->prepare("
    SELECT user_id
    FROM qrup_uzvleri
    WHERE qrup_id = :qrup_id
      AND status = 1
");

$stmt_qrupda_olanlar->execute([
    ':qrup_id' => (int)$cagir_qrup['id']
]);

foreach ($stmt_qrupda_olanlar->fetchAll(PDO::FETCH_COLUMN) as $user_id) {
    $qrupda_olanlar[(int)$user_id] = true;
}

?>

    <?php
    $online_cagir_sayi = 0;

    foreach ($users as $oyuncu):

    $oyuncu_id = (int)$oyuncu['id'];

    /* Özümüzü siyahıda göstərmirik */
    if ($oyuncu_id === $my_id) {
        continue;
    }

    /* ARTİQ QRUPDADIRSA GÖSTƏRMƏ */
    if (isset($qrupda_olanlar[$oyuncu_id])) {
        continue;
    }

    $online_cagir_sayi++;

        /* Mövqeyə görə rəng */
        if ((int)$oyuncu['movqe'] === 2) {
            $renk = '#0F7100';
        } elseif ((int)$oyuncu['movqe'] === 1) {
            $renk = 'red';
        } elseif ((int)$oyuncu['movqe'] === 3) {
            $renk = 'rgb(0, 0, 255)';
        } else {
            $renk = 'white';
        }
    ?>

    <?php
$oyuncu_id = (int)$oyuncu['id'];

$artiq_devet_edilib = isset($devet_edilenler[$oyuncu_id]);
?>

<?php if ($artiq_devet_edilib): ?>

    <span>(Go)</span>

<?php else: ?>

    <a href="qrup_yarad.php?go=cagir_ok&amp;uid=<?php echo $oyuncu_id; ?>&amp;lis=<?php echo (int)$cagir_qrup['id']; ?>">
        (Go)
    </a>

<?php endif; ?>

|

<a href="infoforce.php?uid=<?php echo $oyuncu_id; ?>">
    <font color="<?php echo $renk; ?>">

        <?php if ($artiq_devet_edilib): ?>
            <s>
        <?php endif; ?>

        <?php echo htmlspecialchars($oyuncu['login']); ?>
        [<?php echo (int)$oyuncu['oyuncunun_seviyyesi']; ?>]

        <?php if ($artiq_devet_edilib): ?>
            </s>
        <?php endif; ?>

    </font>
</a>


        <hr/>

    <?php endforeach; ?>


    <?php if ($online_cagir_sayi === 0): ?>

        <i>Hazırda online oyunçu yoxdur.</i>

        <br/><br/>

    <?php endif; ?>


    <div class='line'></div>

    <input
        type="button"
        class="button"
        value="Geri"
        onclick="goGeri()"
    >

<?php elseif (isset($_GET['go']) && $_GET['go'] === 'qrup' && isset($qrup)): ?>

    <?php
$doyus_muddeti = 10;

$yaradildi_vaxti = strtotime($qrup['yaradildi']);

if ($yaradildi_vaxti === false) {
    $yaradildi_vaxti = time();
}

$qalan_san = max(
    0,
    ($yaradildi_vaxti + $doyus_muddeti) - time()
);
?>

    <?php if (isset($_GET['devet']) && $_GET['devet'] === 'ok'): ?>

    <div class="success">
        <img src="muxtelif/okey.png" alt="">
        Çagiriş gönderildi<br>
    </div>


<?php endif; ?>


    <div id="doyus_saygac">
    Döyüşün başlanmasına
    <b id="qalan_saniye"><?php echo (int)$qalan_san; ?> san</b>
    qalıb
</div>

<script>
var qalan = <?php echo (int)$qalan_san; ?>;

var saygac = setInterval(function () {

 if (qalan <= 0) {
    clearInterval(saygac);

    document.getElementById('qalan_saniye').innerHTML = '0 san';

    window.location.href =
        'qrup_yarad.php?go=doyus_yoxla&id=<?php echo (int)$qrup['id']; ?>';

    return;
}


    qalan--;

    document.getElementById('qalan_saniye').innerHTML =
        qalan + ' san';

}, 1000);
</script>
    <br/>

    <hr/>

    <a href="qrup_yarad.php?go=qrup&id=<?php echo (int)$qrup['id']; ?>">
        Səhifəni Yenilə
    </a>

    <br/>

    <div class="center">
        <div class="block_line">
            <b>Döyüş Məlumatları</b>
        </div>
    </div>

    <br/>

    <b>Qrupun adı:</b>
    <?php echo htmlspecialchars($qrup['otaq_adi']); ?>
    <br/>

    <b>Döyüşçü tutumu:</b>
    <?php echo (int)$qrup['oyuncu_tutumu']; ?>
    <br/>

    <b>Döyüş növü:</b>

    <?php
    if ((int)$qrup['doyus_novu'] === 0) {
        echo 'Vampir VS İnsan';
    } else {
        echo 'Qatışıq';
    }
    ?>

    <br/>

    <b>Qoyulan qızıl:</b>
    <?php echo (int)$qrup['qizil']; ?>

    <br/>

    <b>Qızıl fondu:</b>
    0

    <br/>

    <b>Minimum mərhələ:</b>

    <?php
    if ($qrup['minimum_merhele'] === null) {
        echo 'Limitsiz';
    } else {
        echo (int)$qrup['minimum_merhele'];
    }
    ?>

    <br/>

    <b>Maksimum mərhələ:</b>

    <?php
    if ($qrup['maksimum_merhele'] === null) {
        echo 'Limitsiz';
    } else {
        echo (int)$qrup['maksimum_merhele'];
    }
    ?>

    <br/>

    <b>Qrupu Yaradan:</b>
    <?php echo (int)$qrup['yaradan_id']; ?>

    <br/>



  <?php
$sizin_qrup = [];
$reqib_qrup = [];

$qrup_terefi = (int)$qrup['terefi'];

foreach ($qrup_uzvleri as $uzv) {

    $uzv_terefi = (int)$uzv['terefi'];

    if ($uzv_terefi === $qrup_terefi) {
        $sizin_qrup[] = $uzv;
    } else {
        $reqib_qrup[] = $uzv;
    }
}
?>

<div class="center">
    <div class="block_line">
        <b>Sizin Qrup</b>
    </div>
</div>

<br/>

<?php if (!empty($sizin_qrup)): ?>

    <?php foreach ($sizin_qrup as $uzv): ?>

        <?php
        if ((int)$uzv['movqe'] === 2) {
            $renk = '#0F7100';
        } elseif ((int)$uzv['movqe'] === 1) {
            $renk = 'red';
        } elseif ((int)$uzv['movqe'] === 3) {
            $renk = 'rgb(0, 0, 255)';
        } else {
            $renk = 'white';
        }
        ?>

        <a href="infoforce.php?uid=<?php echo (int)$uzv['user_id']; ?>">
            <u>
                <font color="<?php echo $renk; ?>">
                    <?php echo htmlspecialchars($uzv['login']); ?>
                    [<?php echo (int)$uzv['oyuncunun_seviyyesi']; ?>]<br>

                </font>
            </u>
        </a>



    <?php endforeach; ?>

<?php else: ?>

    <i>Sizin qrupda döyüşçü yoxdur.</i>

<?php endif; ?>




<div class="center">
    <div class="block_line">
        <b>Rəqib qrup</b>
    </div>
</div>

<br/>

<?php if (!empty($reqib_qrup)): ?>

    <?php foreach ($reqib_qrup as $uzv): ?>

        <?php
        if ((int)$uzv['movqe'] === 2) {
            $renk = '#0F7100';
        } elseif ((int)$uzv['movqe'] === 1) {
            $renk = 'red';
        } elseif ((int)$uzv['movqe'] === 3) {
            $renk = 'rgb(0, 0, 255)';
        } else {
            $renk = 'white';
        }
        ?>

        <a href="infoforce.php?uid=<?php echo (int)$uzv['user_id']; ?>">
            <u>
                <font color="<?php echo $renk; ?>">
                    <?php echo htmlspecialchars($uzv['login']); ?>
                    [<?php echo (int)$uzv['oyuncunun_seviyyesi']; ?>]
                    <br>
                </font>
            </u>
        </a>


    <?php endforeach; ?>

<?php else: ?>

    <i>Rəqib qrupda döyüşçü yoxdur.</i>

<?php endif; ?>


<hr/>
<br/>

<?php
/* =====================================================
   DƏVƏTLƏ GƏLƏN OYUNÇU VARMI?
   
   gelme_novu:
   0 = normal daxil olub
   1 = dəvətlə gəlib
===================================================== */

$devetle_gelen_var = false;

foreach ($qrup_uzvleri as $uzv) {

    if (
        (int)$uzv['status'] === 1 &&
        (int)$uzv['gelme_novu'] === 1
    ) {
        $devetle_gelen_var = true;
        break;
    }
}
?>

<?php if (!$devetle_gelen_var): ?>

    <a href="qrup_yarad.php?go=cagir&id=<?php echo (int)$qrup['id']; ?>">
        Döyüşçü çağır
    </a>

    <br/><br/>

<?php endif; ?>


    <form method="post"
          action="qrup_yarad.php?go=yaz&id=<?php echo (int)$qrup['id']; ?>">

        <input name="message" value="" maxlength="120"/>

        <br/>

        <input type="submit" value="Gönder"/>
<hr>
    </form>

<?php else: ?>

<form method="post" action="qrup_yarad.php?go=ok">

    <b>Otaqın Adı</b><br/>

    <input
        type="text"
        size="12"
        name="alish_min"
        maxlength="15"
        value=""
    />

    <br/>

    <b>Oyunçu Tutumu</b><br/>

    <select name="bb">
        <option value="2">2</option>
        <option value="6">6</option>
        <option value="8">8</option>
        <option value="10">10</option>
        <option value="12">12</option>
    </select>

    <br/><br/>

    <b>Minimum Merhele:</b><br/>

    <select name="nn">
        <option value="nn">Limitsiz</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
        <option value="6">6</option>
        <option value="7">7</option>
        <option value="8">8</option>
        <option value="9">9</option>
        <option value="10">10</option>
        <option value="11">11</option>
        <option value="12">12</option>
        <option value="13">13</option>
    </select>

    <br/><br/>

    <b>Maxsimum Merhele:</b><br/>

    <select name="mm">
        <option value="0">Limitsiz</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
        <option value="6">6</option>
        <option value="7">7</option>
        <option value="8">8</option>
        <option value="9">9</option>
        <option value="10">10</option>
        <option value="11">11</option>
        <option value="12">12</option>
        <option value="13">13</option>
    </select>

    <br/><br/>

    <b>Qızıl:</b><br/>

    <select name="aa">
        <option value="0">Qızılsız</option>
        <option value="20">20 Qızıl</option>
        <option value="50">50 Qızıl</option>
        <option value="100">100 Qızıl</option>
    </select>

    <br/><br/>

    <b>Döyüş növü:</b><br/>

    <select name="cc">
        <option value="0">Vampir VS İnsan</option>
        <option value="1">Qatışıq</option>
    </select>

    <br/><br/>

    <input type="hidden" name="action" value="save"/>

    <input
        type="submit"
        class="button"
        value="Ok"
    />

    <br/>

    <div class="line"></div>

    <input
        type="button"
        class="button"
        value="Geri"
        onclick="goGeri()"
    />

</form>

<?php endif; ?>

<?php if (!(isset($_GET['go']) && $_GET['go'] === 'legv')): ?>
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

                    <img
                        src="muxtelif/saat.ico"
                        width="20"
                        height="20"
                        title="Vaxt"
                        alt="Vaxt"
                    >

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

<?php endif; ?>
</body>
</html>