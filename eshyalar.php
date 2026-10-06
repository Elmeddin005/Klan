<?php

session_start();

require_once "config.php";
require_once "user_data.php";


$go = isset($_GET['go']) ? $_GET['go'] : '';
$tipi = isset($_POST['tipi'])
    ? (int)$_POST['tipi']
    : (isset($_GET['tipi']) ? (int)$_GET['tipi'] : 0);

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

/*
|--------------------------------------------------------------------------
| GO = A2 — ƏŞYA SATIN AL
|--------------------------------------------------------------------------
*/

if ($go == 'a2') {

    $rid = isset($_GET['rid'])
        ? (int)$_GET['rid']
        : 0;

    $my_id = (int)($_SESSION['user_id'] ?? 0);

    $satish_ok = false;
    $xeta = '';

    if ($rid > 0 && $my_id > 0) {

        /* ƏŞYANI TAP */

     $stmt = $pdo->prepare("
    SELECT
        id,
        ad,
        qiymet,
        reng,
        min_zerbe,
        max_zerbe,
        can,
        mudafie
    FROM esyalar
    WHERE id = :id
    LIMIT 1
");

        $stmt->execute([
            ':id' => $rid
        ]);

        $al_esya = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($al_esya) {

            $qiymet = (int)$al_esya['qiymet'];

            /* İSTİFADƏÇİNİN QIZILINI TAP */

            $stmt_user = $pdo->prepare("
                SELECT `qızıl`
                FROM users
                WHERE id = :user_id
                LIMIT 1
            ");

            $stmt_user->execute([
                ':user_id' => $my_id
            ]);

            $qizil = (int)$stmt_user->fetchColumn();

            /* QIZIL KİFAYƏTDİR? */

            if ($qizil >= $qiymet) {

                try {

                    $pdo->beginTransaction();

                    /* QIZILI ÇIX */

                    $stmt_update = $pdo->prepare("
                        UPDATE users
                        SET `qızıl` = `qızıl` - :qiymet
                        WHERE id = :user_id
                          AND `qızıl` >= :qiymet
                    ");

                    $stmt_update->execute([
                        ':qiymet' => $qiymet,
                        ':user_id' => $my_id
                    ]);

                    if ($stmt_update->rowCount() !== 1) {

                        throw new Exception(
                            'Qızıl çıxılmadı.'
                        );
                    }

              /* HƏR ALIŞDA ÇANTAYA YENİ SƏTİR ƏLAVƏ ET */

$stmt_insert = $pdo->prepare("
    INSERT INTO canta
        (user_id, esya_id, say, geyimde, son_daxil_olma_vaxti)
    VALUES
        (:user_id, :esya_id, 1, 0, :vaxt)
");

$stmt_insert->execute([
    ':user_id' => $my_id,
    ':esya_id' => $rid,
    ':vaxt'    => time()
]);

                    $pdo->commit();

                    $satish_ok = true;

                } catch (Exception $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $satish_ok = false;

                    $xeta = 'Əməliyyat zamanı xəta baş verdi.';
                }

            } else {

                $xeta = 'Kifayət qədər qızılınız yoxdur.';
            }

        } else {

            $xeta = 'Əşya tapılmadı.';
        }

    } else {

        $xeta = 'Əməliyyat düzgün deyil.';
    }
}


/* 
|-------------------------------------------------------------------------- 
| GO = B — MƏRHƏLƏ ƏŞYALARI 
|-------------------------------------------------------------------------- 
*/ 

if ($go == 'b') { 

    $seviyye = isset($_POST['tipi']) 
        ? (int)$_POST['tipi'] 
        : 1; 

    if ($seviyye < 1 || $seviyye > 14) { 
        $seviyye = 1; 
    } 

    $stmt = $pdo->prepare(" 
        SELECT 
            id, 
            ad, 
            seviyye, 
            reng, 
            img, 
            tip, 
            sekil, 
            qiymet 
        FROM esyalar 
        WHERE seviyye = :seviyye 
          AND reng = 'sari'
          AND tip NOT IN ('mecun', 'almaz')
          AND id NOT IN (377, 378, 379, 404, 405, 406)
        ORDER BY id ASC 
    "); 

    $stmt->execute([ 
        ':seviyye' => $seviyye 
    ]); 

    $esyalar = $stmt->fetchAll(PDO::FETCH_ASSOC); 
}
 /*
 |--------------------------------------------------------------------------
 | GO = C — DÖYÜŞDƏ İSTİFADƏ OLUNAN MƏCUNLAR
 |--------------------------------------------------------------------------
 */

if ($go == 'c') {

    $tipi = isset($_POST['tipi'])
        ? (int)$_POST['tipi']
        : (isset($_GET['tipi']) ? (int)$_GET['tipi'] : 0);

    $mecun_basliq = '';
    $mecunlar = [];

    if ($tipi == 100) {

        $mecun_basliq = 'Can Mecunları';

        $stmt_mecun = $pdo->prepare("
            SELECT
                id,
                ad,
                qiymet,
                img,
                tip,
                can
            FROM esyalar
            WHERE id IN (377, 378, 379)
              AND tip = 'mecun'
            ORDER BY id ASC
        ");

        $stmt_mecun->execute();

        $mecunlar = $stmt_mecun->fetchAll(PDO::FETCH_ASSOC);
    }
}
if ($tipi == 101) {

    $mecun_basliq = 'Zərbə mecunları';
    $mecunlar = [];

}

if ($tipi == 102) {

    $mecun_basliq = 'Müdafiə mecunları';
    $mecunlar = [];

}

if ($tipi == 103) {

    $mecun_basliq = 'Təcrübə mecunları';
    $mecunlar = [];

}
if ($tipi == 200) {

    $mecun_basliq = 'Can Məcunları';

    $stmt_mecun = $pdo->prepare("
        SELECT
            id,
            ad,
            qiymet,
            img,
            tip,
            can
        FROM esyalar
        WHERE id IN (389, 390, 391, 392, 393)
          AND tip = 'mecun'
        ORDER BY id ASC
    ");

    $stmt_mecun->execute();

    $mecunlar = $stmt_mecun->fetchAll(PDO::FETCH_ASSOC);
}
if ($tipi == 201) {

    $mecun_basliq = 'Zerbe Mecunları';

    $stmt_mecun = $pdo->prepare("
        SELECT
            id,
            ad,
            qiymet,
            img,
            tip,
            can
        FROM esyalar
        WHERE id IN (394, 395, 396, 397, 398)
          AND tip = 'mecun'
        ORDER BY id ASC
    ");

    $stmt_mecun->execute();

    $mecunlar = $stmt_mecun->fetchAll(PDO::FETCH_ASSOC);
}
if ($tipi == 202) {

    $mecun_basliq = 'Müdafie Mecunları';

    $stmt_mecun = $pdo->prepare("
        SELECT
            id,
            ad,
            qiymet,
            img,
            tip,
            can
        FROM esyalar
        WHERE id IN (399, 400, 401, 402, 403)
          AND tip = 'mecun'
        ORDER BY id ASC
    ");

    $stmt_mecun->execute();

    $mecunlar = $stmt_mecun->fetchAll(PDO::FETCH_ASSOC);
}
/*
/*
/*
|--------------------------------------------------------------------------
| GO = D — DİGƏR ƏŞYALAR
|--------------------------------------------------------------------------
*/

if ($go == 'd') {

    $tipi = isset($_POST['tipi'])
        ? (int)$_POST['tipi']
        : 0;
if ($tipi == 50) {

    $stmt_cekicler = $pdo->prepare("
        SELECT
            id,
            esya_id,
            ad,
            img
        FROM temir_cekicleri
        WHERE aktiv = 1
        ORDER BY id ASC
    ");

    $stmt_cekicler->execute();

    $cekicler = $stmt_cekicler->fetchAll(PDO::FETCH_ASSOC);
}
    /*
    |--------------------------------------------------------------------------
    | ALMAZ DAŞLARI
    |--------------------------------------------------------------------------
    */

    if ($tipi == 51) {

        $stmt_almaz = $pdo->prepare("
            SELECT
                id,
                ad,
                qiymet
            FROM esyalar
            WHERE id IN (368, 369, 370)
            ORDER BY
                CASE id
                    WHEN 368 THEN 1
                    WHEN 369 THEN 2
                    WHEN 370 THEN 3
                    ELSE 5
                END
        ");

        $stmt_almaz->execute();

        $almazlar = $stmt_almaz->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | ƏŞYA ÇANTALARI
    |--------------------------------------------------------------------------
    */

    if ($tipi == 53) {

        $stmt_cantalar = $pdo->prepare("
            SELECT
                id,
                ad,
                qiymet,
                tutum,
                img
            FROM esya_cantalari
            WHERE aktiv = 1
            ORDER BY id ASC
        ");

        $stmt_cantalar->execute();

        $esya_cantalari = $stmt_cantalar->fetchAll(PDO::FETCH_ASSOC);
    }
}
/*
|--------------------------------------------------------------------------
| GO = SUMKA_AL — ƏŞYA ÇANTASI SATIN AL
|--------------------------------------------------------------------------
*/

if ($go == 'sumka_al') {

    $tipi = isset($_GET['tipi'])
        ? (int)$_GET['tipi']
        : 0;

    $my_id = (int)($_SESSION['user_id'] ?? 0);

    $sumka_mesaj = '';
    $sumka_xeta = '';

    /*
    |--------------------------------------------------------------------------
    | ÇANTA MƏLUMATLARI
    |--------------------------------------------------------------------------
    */

    $canta_melumatlari = [
        1 => [
            'tutum'   => 34,
            'qiymet'  => 50000,
            'valyuta' => 'qizil',
            'gun'     => 30
        ],
        2 => [
            'tutum'   => 50,
            'qiymet'  => 100000,
            'valyuta' => 'qizil',
            'gun'     => 30
        ],
        3 => [
            'tutum'   => 150,
            'qiymet'  => 100,
            'valyuta' => 'brilyant',
            'gun'     => 30
        ],
        4 => [
            'tutum'   => 200,
            'qiymet'  => 150,
            'valyuta' => 'brilyant',
            'gun'     => 30
        ]
    ];

    /*
    |--------------------------------------------------------------------------
    | YOXLAMA
    |--------------------------------------------------------------------------
    */

    if ($my_id <= 0) {

        $sumka_xeta = 'Əməliyyat düzgün deyil.';

    } elseif (!isset($canta_melumatlari[$tipi])) {

        $sumka_xeta = 'Əməliyyat düzgün deyil.';

    } else {

        $yeni_tutum = $canta_melumatlari[$tipi]['tutum'];
        $qiymet = $canta_melumatlari[$tipi]['qiymet'];
        $valyuta = $canta_melumatlari[$tipi]['valyuta'];

        /*
        |--------------------------------------------------------------------------
        | İSTİFADƏÇİNİN İNDİKİ ÇANTASINI TAP
        |--------------------------------------------------------------------------
        */

        $stmt_canta = $pdo->prepare("
            SELECT
                tutum,
                bitme_vaxti
            FROM istifadeci_cantalari
            WHERE user_id = :user_id
            LIMIT 1
        ");

        $stmt_canta->execute([
            ':user_id' => $my_id
        ]);

        $canta = $stmt_canta->fetch(PDO::FETCH_ASSOC);

        $indiki_tutum = 34;

        if ($canta) {

            $bitme_vaxti = (int)$canta['bitme_vaxti'];

            if ($bitme_vaxti > time()) {
                $indiki_tutum = (int)$canta['tutum'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | EYNİ VƏ YA AŞAĞI ÇANTANI ALMAQ OLMAZ
        |--------------------------------------------------------------------------
        */

       if ($canta && $bitme_vaxti > time() && $yeni_tutum <= $indiki_tutum) {

    if ($yeni_tutum == $indiki_tutum) {

        $sumka_xeta =
            'Siz artıq bu çantanı almısız.';

    } else {

        $sumka_xeta =
            'Siz artıq bu çantanı almısız, ve yaxud çantanızın tutumu '
            . $yeni_tutum . '-den çoxdur.';

    }

} else {

            /*
            |--------------------------------------------------------------------------
            | PULU / BRİLYANTI YOXLAYIRIQ
            |--------------------------------------------------------------------------
            */

            if ($valyuta == 'qizil') {

                $stmt_user = $pdo->prepare("
                    SELECT `qızıl`
                    FROM users
                    WHERE id = :user_id
                    LIMIT 1
                ");

                $stmt_user->execute([
                    ':user_id' => $my_id
                ]);

                $balans = (int)$stmt_user->fetchColumn();

            } else {

                $stmt_user = $pdo->prepare("
                    SELECT `brılyant`
                    FROM users
                    WHERE id = :user_id
                    LIMIT 1
                ");

                $stmt_user->execute([
                    ':user_id' => $my_id
                ]);

                $balans = (int)$stmt_user->fetchColumn();
            }

            /*
            |--------------------------------------------------------------------------
            | BALANS KİFAYƏTDİR?
            |--------------------------------------------------------------------------
            */

            if ($balans < $qiymet) {

                if ($valyuta == 'qizil') {

                    $sumka_xeta =
                        'Kifayət qədər qızılınız yoxdur.';

                } else {

                    $sumka_xeta =
                        'Kifayət qədər Brilliantınız yoxdur.';
                }

            } else {

                /*
                |--------------------------------------------------------------------------
                | ALIŞ ƏMƏLİYYATI
                |--------------------------------------------------------------------------
                */

                try {

                    $pdo->beginTransaction();

                    /*
                    |------------------------------------------------------------------
                    | QIZIL ÇIX
                    |------------------------------------------------------------------
                    */

                    if ($valyuta == 'qizil') {

                        $stmt_update = $pdo->prepare("
                            UPDATE users
                            SET `qızıl` = `qızıl` - :qiymet
                            WHERE id = :user_id
                              AND `qızıl` >= :qiymet
                        ");

                    } else {

                        /*
                        |------------------------------------------------------------------
                        | BRİLYANT ÇIX
                        |------------------------------------------------------------------
                        */

                        $stmt_update = $pdo->prepare("
                            UPDATE users
                            SET `brılyant` = `brılyant` - :qiymet
                            WHERE id = :user_id
                              AND `brılyant` >= :qiymet
                        ");
                    }

                    $stmt_update->execute([
                        ':qiymet' => $qiymet,
                        ':user_id' => $my_id
                    ]);

                    if ($stmt_update->rowCount() !== 1) {

                        throw new Exception(
                            'Balansdan məbləğ çıxılmadı.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 30 GÜNLÜK MÜDDƏT
                    |--------------------------------------------------------------------------
                    */

                    $yeni_bitme_vaxti =
                        time() + (30 * 24 * 60 * 60);

                    /*
                    |--------------------------------------------------------------------------
                    | ÇANTANI YAZ / YENİLƏ
                    |--------------------------------------------------------------------------
                    */

                    if ($canta) {

                        $stmt_update_canta = $pdo->prepare("
                            UPDATE istifadeci_cantalari
                            SET
                                tutum = :tutum,
                                bitme_vaxti = :bitme_vaxti
                            WHERE user_id = :user_id
                        ");

                        $stmt_update_canta->execute([
                            ':tutum'       => $yeni_tutum,
                            ':bitme_vaxti' => $yeni_bitme_vaxti,
                            ':user_id'     => $my_id
                        ]);

                    } else {

                        $stmt_insert_canta = $pdo->prepare("
                            INSERT INTO istifadeci_cantalari
                                (user_id, tutum, bitme_vaxti)
                            VALUES
                                (:user_id, :tutum, :bitme_vaxti)
                        ");

                        $stmt_insert_canta->execute([
                            ':user_id'     => $my_id,
                            ':tutum'       => $yeni_tutum,
                            ':bitme_vaxti' => $yeni_bitme_vaxti
                        ]);
                    }

                    $pdo->commit();

                    /*
                    |--------------------------------------------------------------------------
                    | UĞURLU MESAJ
                    |--------------------------------------------------------------------------
                    */

        $sumka_mesaj =
    'Tebrikler! Siz 30 günlük '
    . $yeni_tutum
    . ' eşya tutan çanta aldınız.';

                } catch (Exception $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $sumka_xeta =
                        'Əməliyyat zamanı xəta baş verdi.';
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| GO = AL — MƏCUN SATIN AL
|--------------------------------------------------------------------------
*/

if ($go == 'al') {

    $rid = isset($_GET['rid'])
        ? (int)$_GET['rid']
        : 0;

    $tipi = isset($_GET['tipi'])
        ? (int)$_GET['tipi']
        : 0;

    $my_id = (int)($_SESSION['user_id'] ?? 0);

    $mecun_satish_ok = false;
    $mecun_xeta = '';

if (
    $rid > 0 &&
    $my_id > 0 &&
    in_array($tipi, [100, 200, 201, 202], true)
) {
        /* MƏCUNU TAP */

        $stmt = $pdo->prepare("
            SELECT
                id,
                ad,
                qiymet,
                tip
            FROM esyalar
            WHERE id = :id
              AND tip = 'mecun'
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $rid
        ]);

        $mecun = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($mecun) {

            $qiymet = (int)$mecun['qiymet'];

            /* İSTİFADƏÇİNİN BRİLYANTINI TAP */

            $stmt_user = $pdo->prepare("
                SELECT `brılyant`
                FROM users
                WHERE id = :user_id
                LIMIT 1
            ");

            $stmt_user->execute([
                ':user_id' => $my_id
            ]);

            $brilyant = (int)$stmt_user->fetchColumn();

            /* BRİLYANT KİFAYƏTDİR? */

            if ($brilyant >= $qiymet) {

                try {

                    $pdo->beginTransaction();

                    /* BRİLYANTI ÇIX */

                    $stmt_update = $pdo->prepare("
                        UPDATE users
                        SET `brılyant` = `brılyant` - :qiymet
                        WHERE id = :user_id
                          AND `brılyant` >= :qiymet
                    ");

                    $stmt_update->execute([
                        ':qiymet'  => $qiymet,
                        ':user_id' => $my_id
                    ]);

                    if ($stmt_update->rowCount() !== 1) {

                        throw new Exception(
                            'Brilliant çıxılmadı.'
                        );
                    }

                    /* MƏCUNU ÇANTAYA ƏLAVƏ ET */

                    $stmt_insert = $pdo->prepare("
                        INSERT INTO canta
                            (user_id, esya_id, say, geyimde)
                        VALUES
                            (:user_id, :esya_id, 1, 0)
                    ");

                    $stmt_insert->execute([
                        ':user_id' => $my_id,
                        ':esya_id' => $rid
                    ]);

                    $pdo->commit();

                    $mecun_satish_ok = true;

                } catch (Exception $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $mecun_satish_ok = false;

                    $mecun_xeta =
                        'Əməliyyat zamanı xəta baş verdi.';
                }

            } else {

                $mecun_xeta =
                    'Kifayət qədər Brilliantınız yoxdur.';
            }

        } else {

            $mecun_xeta =
                'Mecun tapılmadı.';
        }

    } else {

        $mecun_xeta =
            'Əməliyyat düzgün deyil.';
    }
}
/*
|--------------------------------------------------------------------------
| GO = OOL — ALMAZ SATIN AL
|--------------------------------------------------------------------------
*/

if ($go == 'ool') {

    $rid = isset($_GET['rid'])
        ? (int)$_GET['rid']
        : 0;

    $tipi = isset($_GET['tipi'])
        ? (int)$_GET['tipi']
        : 0;

    $my_id = (int)($_SESSION['user_id'] ?? 0);

    $almaz_satish_ok = false;
    $almaz_xeta = '';
    /* =========================================================
   TƏMİR ÇƏKİCİ SATIŞI
========================================================= */

$cekic_satish_ok = false;
$cekic_xeta = '';

if ($rid > 0 && $my_id > 0 && $tipi == 50) {

    $stmt_cekic = $pdo->prepare("
        SELECT
            id,
            ad,
            qiymet
        FROM esyalar
        WHERE id = :id
          AND id IN (
              SELECT esya_id
              FROM temir_cekicleri
              WHERE aktiv = 1
          )
        LIMIT 1
    ");

    $stmt_cekic->execute([
        ':id' => $rid
    ]);

    $cekic = $stmt_cekic->fetch(PDO::FETCH_ASSOC);

    if ($cekic) {

        $qiymet = (int)$cekic['qiymet'];

        /* 404 Ağ və 405 Göy çəkic Qızıl ilə */
        if ($rid == 404 || $rid == 405) {

            $stmt_user = $pdo->prepare("
                SELECT qızıl
                FROM users
                WHERE id = :id
                LIMIT 1
            ");

            $stmt_user->execute([
                ':id' => $my_id
            ]);

            $coin = (int)$stmt_user->fetchColumn();

            if ($coin >= $qiymet) {

                $pdo->beginTransaction();

                try {

                    $stmt_update = $pdo->prepare("
                        UPDATE users
                        SET qızıl = qızıl - :qiymet
                        WHERE id = :id
                    ");

                    $stmt_update->execute([
                        ':qiymet' => $qiymet,
                        ':id' => $my_id
                    ]);
                    if ($stmt_update->rowCount() !== 1) {
    throw new Exception('Qızıl çıxılmadı.');
}

                    $stmt_canta = $pdo->prepare("
                        INSERT INTO canta
                        (user_id, esya_id, say, geyimde, son_daxil_olma_vaxti)
                        VALUES
                        (:user_id, :esya_id, 1, 0, :vaxt)
                    ");

                    $stmt_canta->execute([
                        ':user_id' => $my_id,
                        ':esya_id' => $rid,
                        ':vaxt' => time()
                    ]);

                    $pdo->commit();

                    $cekic_satish_ok = true;

                } catch (Exception $e) {

                    $pdo->rollBack();
                    $cekic_xeta = 'Çəkic alınarkən xəta baş verdi.';

                }

            } else {

                $cekic_xeta = 'Qızılınız kifayət etmir.';

            }

        }

        /* 406 Sarı çəkic Brilliant ilə */
        elseif ($rid == 406) {

            $stmt_user = $pdo->prepare("
                SELECT `brılyant`
                FROM users
                WHERE id = :id
                LIMIT 1
            ");

            $stmt_user->execute([
                ':id' => $my_id
            ]);

            $brilyant = (int)$stmt_user->fetchColumn();

            if ($brilyant >= $qiymet) {

                $pdo->beginTransaction();

                try {

                    $stmt_update = $pdo->prepare("
                        UPDATE users
                        SET `brılyant` = `brılyant` - :qiymet
                        WHERE id = :id
                    ");

                    $stmt_update->execute([
                        ':qiymet' => $qiymet,
                        ':id' => $my_id
                    ]);

                    $stmt_canta = $pdo->prepare("
                        INSERT INTO canta
                        (user_id, esya_id, say, geyimde, son_daxil_olma_vaxti)
                        VALUES
                        (:user_id, :esya_id, 1, 0, :vaxt)
                    ");

                    $stmt_canta->execute([
                        ':user_id' => $my_id,
                        ':esya_id' => $rid,
                        ':vaxt' => time()
                    ]);

                    $pdo->commit();

                    $cekic_satish_ok = true;

                } catch (Exception $e) {

                    $pdo->rollBack();
                    $cekic_xeta = 'Çəkic alınarkən xəta baş verdi.';

                }

            } else {

                $cekic_xeta = 'Brilliantınız kifayət etmir.';

            }
        }

    } else {

        $cekic_xeta = 'Çəkic tapılmadı.';

    }
}

    if ($rid > 0 && $my_id > 0 && $tipi == 51) {

        /* ALMAZI TAP */

        $stmt = $pdo->prepare("
            SELECT
                id,
                ad,
                qiymet,
                tip
            FROM esyalar
            WHERE id = :id
              AND tip = 'almaz'
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $rid
        ]);

        $almaz = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($almaz) {

            $qiymet = (int)$almaz['qiymet'];

            /* İSTİFADƏÇİNİN BRİLYANTINI TAP */

            $stmt_user = $pdo->prepare("
                SELECT `brılyant`
                FROM users
                WHERE id = :user_id
                LIMIT 1
            ");

            $stmt_user->execute([
                ':user_id' => $my_id
            ]);

            $brilyant = (int)$stmt_user->fetchColumn();

            /* BRİLYANT KİFAYƏTDİR? */

            if ($brilyant >= $qiymet) {

                try {

                    $pdo->beginTransaction();

                    /* BRİLYANTI ÇIX */

                    $stmt_update = $pdo->prepare("
                        UPDATE users
                        SET `brılyant` = `brılyant` - :qiymet
                        WHERE id = :user_id
                          AND `brılyant` >= :qiymet
                    ");

                    $stmt_update->execute([
                        ':qiymet' => $qiymet,
                        ':user_id' => $my_id
                    ]);

                    if ($stmt_update->rowCount() !== 1) {

                        throw new Exception(
                            'Brilliant çıxılmadı.'
                        );
                    }

           /* ALMAZ ÇANTAYA ƏLAVƏ OLUNUR */
/* ALINDIĞI ANIN VAXTI YAZILIR */

$stmt_insert = $pdo->prepare("
    INSERT INTO canta
        (
            user_id,
            esya_id,
            say,
            geyimde,
            son_daxil_olma_vaxti
        )
    VALUES
        (
            :user_id,
            :esya_id,
            1,
            0,
            :vaxt
        )
");

$stmt_insert->execute([
    ':user_id' => $my_id,
    ':esya_id' => $rid,
    ':vaxt'    => time()
]);

                    $pdo->commit();

                    $almaz_satish_ok = true;

                } catch (Exception $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $almaz_satish_ok = false;

                    $almaz_xeta =
                        'Əməliyyat zamanı xəta baş verdi.';
                }

            } else {

                $almaz_xeta =
                    'Kifayət qədər Brilliantınız yoxdur.';
            }

        } else {

            $almaz_xeta = 'Almaz daşı tapılmadı.';
        }

    } else {

        $almaz_xeta = 'Əməliyyat düzgün deyil.';
    }
}

/*
|--------------------------------------------------------------------------
| GO = INFO
|--------------------------------------------------------------------------
*/

if ($go == 'mod_info') {

    $rid = isset($_GET['rid'])
        ? (int)$_GET['rid']
        : 0;

    $stmt = $pdo->prepare("
        SELECT
            id,
            ad,
            seviyye,
            reng,
            img,
            tip,
            sekil,
            min_zerbe,
            max_zerbe,
            can,
            mudafie,
            krit,
            anti_krit,
            uvorot,
            anti_uvorot,
            davamliliq,
            qiymet
        FROM esyalar
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $rid
    ]);

    $esya = $stmt->fetch(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| GO = M_INFO — MƏCUN MƏLUMATI
|--------------------------------------------------------------------------
*/

if ($go == 'm_info') {

    $rid = isset($_GET['rid'])
        ? (int)$_GET['rid']
        : 0;

    $stmt = $pdo->prepare("
        SELECT
            id,
            ad,
            img,
            tip,
            qiymet,
            mexsusdur
        FROM esyalar
        WHERE id = :id
         AND (
    tip = 'mecun'
    OR id IN (
        SELECT esya_id
        FROM temir_cekicleri
        WHERE aktiv = 1
    )
)
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $rid
    ]);

    $mecun_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| GO = C_INFO — ALMAZ MƏLUMATI
|--------------------------------------------------------------------------
*/

if ($go == 'c_info') {

    $rid = isset($_GET['rid'])
        ? (int)$_GET['rid']
        : 0;

    $stmt = $pdo->prepare("
        SELECT
            id,
            ad,
            img,
            tip,
            qiymet,
            mexsusdur
        FROM esyalar
        WHERE id = :id
          AND tip = 'almaz'
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $rid
    ]);

    $almaz_info = $stmt->fetch(PDO::FETCH_ASSOC);
}



/*
|--------------------------------------------------------------------------
| GO = DUKAN_INFO
|--------------------------------------------------------------------------
*/

if ($go == 'dukan_info') {

    $rid = isset($_GET['rid'])
        ? (int)$_GET['rid']
        : 0;

    $stmt = $pdo->prepare("
        SELECT
            id,
            ad,
            seviyye,
            reng,
            img,
            tip,
            sekil,
            aktiv,
            min_zerbe,
            max_zerbe,
            can,
            mudafie,
            krit,
            anti_krit,
            uvorot,
            anti_uvorot,
            davamliliq,
            qiymet,
            mexsusdur
        FROM esyalar
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $rid
    ]);

    $esya = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="az">

<head>

<meta charset="utf-8">

<meta name="robots" content="ALL">

<meta
    name="keywords"
    content="klan.az, azgame, online oyun, klan döyüşləri"
>

<meta
    name="description"
    content="Azerbaycanda ilk Mobil Online oyunu."
>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0, maximum-scale=3.0"
>

<title>Dukan | Algi satgi</title>

<link rel="stylesheet" href="css.css">

<script>

function goGeri() {
    window.history.back();
}

</script>

</head>

<body>

<div class="main" style="word-wrap:break-word;">

<!-- HEADER -->

<div id="header">

    <a href="menu.php?">

        <img
            src="img/logo.png"
            alt="Logo"
        >

    </a>

    <div class="icons"></div>

    <div class="main_foot">

        <div class="grey">

  <img src="img/coin.png" title="Qızıl" alt=""/> <?php echo (int)$user['qızıl']; ?>

<img src="img/brill.png" title="Brilliant" alt=""/> <?php echo (int)$user['brılyant']; ?>

<img src="img/energy.png" title="Enerji" alt=""/> <?php echo (int)$user['enerjı']; ?>
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

<div
    style="background:none repeat scroll 0 0 #888686;height:1px;"
></div>


<!-- EXP -->

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
                width: <?php echo $progress; ?>%;
                height: 10px;
            "
        >

            <div class="exp_line"></div>

            <div class="exp_point"></div>

        </div>

    </div>

</div>

<div
    style="background:none repeat scroll 0 0 #888686;height:1px;"
></div>


<!-- MƏZMUN -->

<div class="info">



<?php if ($go == 'sumka_al'): ?>

    <?php if (!empty($sumka_mesaj)): ?>

        <b style="color:#000;">
            <?php echo htmlspecialchars($sumka_mesaj); ?>
        </b>

    <?php else: ?>

        <b>SEHV!</b>
        <?php echo htmlspecialchars($sumka_xeta); ?>

        <br>

        <input type="button" class="button" value="Geri" onclick="goGeri()">

    <?php endif; ?>

    <br>

    <div class="menu">
        <br>
        <li>
            <a href="eshyalar.php?">
                <img src="muxtelif/dukan.png" alt=" ">
                Merkez dukan
            </a>
        </li>
    </div>

<?php elseif ($go == 'a2'): ?>

<!-- SATIN ALMA NƏTİCƏSİ -->

<br>

<?php if ($satish_ok): ?>

    <div class="success">

        <img
            src="muxtelif/okey.png"
            alt=""
        >

        <span style="color:#259C00;">
            Emeliyyat yerine yetirildi. Eşya çantaya gönderildi
        </span>

    </div>

    <br>

    <div class="menu">

        <br>

        <li>

            <a href="chantam.php?">

                <img
                    src="muxtelif/sandiq.png"
                    alt=" "
                >

                Eşya çantası

            </a>

        </li>

        <li>

            <a href="eshyalar.php?">

                <img
                    src="muxtelif/dukan.png"
                    alt=" "
                >

                Merkez dükan

            </a>

        </li>

    </div>


<?php else: ?>


    <div class="battle_log">

        <div class="content">

            <?php echo htmlspecialchars($xeta); ?>

        </div>

    </div>

    <br>

    <div class="menu">

        <li>

            <a href="eshyalar.php?">

                <img
                    src="muxtelif/dukan.png"
                    alt=" "
                >

                Merkez dükan

            </a>

        </li>

    </div>


<?php endif; ?>

<?php elseif ($go == 'm_info' && !empty($mecun_info)): ?>

<div class="content">

    <table border="0" cellpadding="2" cellspacing="0">

        <tr>

            <td>

                <img
                    src="<?php echo htmlspecialchars($mecun_info['img']); ?>"
                    alt="foto"
                >

            </td>

            <td>

                <b>Ad:</b>
                <?php echo htmlspecialchars($mecun_info['ad']); ?>

                <br>

              <b>Tip:</b>
<?php
if (
    (int)$mecun_info['id'] == 404 ||
    (int)$mecun_info['id'] == 405 ||
    (int)$mecun_info['id'] == 406
) {
    echo 'Çəkic';
} else {
    echo 'Mecun';
}
?>
                <br>

                <b>Qiymet:</b>
<?php echo (int)$mecun_info['qiymet']; ?>
<?php echo ((int)$mecun_info['id'] == 404 || (int)$mecun_info['id'] == 405 || (int)$mecun_info['id'] == 406) ? ' Qızıl' : ' Brilliant'; ?>

                <br>

            </td>

        </tr>

    </table>

</div>

<p>

    <b>Haqqında:</b>

    <u>
        <?php
        if ((int)$mecun_info['id'] == 377) {
            echo 'Döyüş vaxtı istifadə olunur, azalan canı maksimum canın 20%-i qədər artırır';
        } elseif ((int)$mecun_info['id'] == 378) {
            echo 'Döyüş vaxtı istifadə olunur, azalan canı maksimum canın 40%-i qədər artırır';
        } elseif ((int)$mecun_info['id'] == 379) {
            echo 'Döyüş vaxtı istifadə olunur, azalan canı maksimum canın 60%-i qədər artırır';
        }
        elseif ((int)$mecun_info['id'] == 389) {
    echo 'İstifade olunduqda 3 saat erzinde <b>Caninizi 5%</b> artirir';
} elseif ((int)$mecun_info['id'] == 390) {
    echo 'İstifade olunduqda 3 saat erzinde <b>Caninizi 10%</b> artirir';
} elseif ((int)$mecun_info['id'] == 391) {
    echo 'İstifade olunduqda 3 saat erzinde <b>Caninizi 20%</b> artirir';
} elseif ((int)$mecun_info['id'] == 392) {
    echo 'İstifade olunduqda 3 saat erzinde <b>Caninizi 30%</b> artirir';
} elseif ((int)$mecun_info['id'] == 393) {
    echo 'İstifade olunduqda 3 saat erzinde <b>Caninizi 40%</b> artirir';

} elseif ((int)$mecun_info['id'] == 394) {
    echo 'İstifade olunduqda 3 saat erzinde <b>Zerbenizi 5%</b> artirir';
} elseif ((int)$mecun_info['id'] == 395) {
    echo 'İstifade olunduqda 3 saat erzinde <b>Zerbenizi 10%</b> artirir';
} elseif ((int)$mecun_info['id'] == 396) {
    echo 'İstifade olunduqda 3 saat erzinde <b>Zerbenizi 20%</b> artirir';
} elseif ((int)$mecun_info['id'] == 397) {
    echo 'İstifade olunduqda 3 saat erzinde <b>Zerbenizi 30%</b> artirir';
} elseif ((int)$mecun_info['id'] == 398) {
    echo 'İstifade olunduqda 3 saat erzinde <b>Zerbenizi 40%</b> artirir';
}
elseif ((int)$mecun_info['id'] == 399) {
    echo 'Istifade olunduqda 3 saat erzinde <b>Mudafienizi 5%</b> artirir';
} elseif ((int)$mecun_info['id'] == 400) {
    echo 'Istifade olunduqda 3 saat erzinde <b>Mudafienizi 10%</b> artirir';
} elseif ((int)$mecun_info['id'] == 401) {
    echo 'Istifade olunduqda 3 saat erzinde <b>Mudafienizi 20%</b> artirir';
} elseif ((int)$mecun_info['id'] == 402) {
    echo 'Istifade olunduqda 3 saat erzinde <b>Mudafienizi 30%</b> artirir';
} elseif ((int)$mecun_info['id'] == 403) {
    echo 'Istifade olunduqda 3 saat erzinde <b>Mudafienizi 40%</b> artirir';
}
if ((int)$mecun_info['id'] == 404) {
    echo 'Eshyalarin temir olunmasinda istifade olunur. Istifade edildikde davamliliqi 50% artirir.';
} elseif ((int)$mecun_info['id'] == 405) {
    echo 'Eshyalarin temir olunmasinda istifade olunur. Istifade edildikde davamliliqi 75% artirir.';
} elseif ((int)$mecun_info['id'] == 406) {
    echo 'Eshyalarin temir olunmasinda istifade olunur. Istifade edildikde davamliliqi 100% artirir.';
} 
        ?>
    </u>

    <br>

    <b>Mexsusdur:</b> 
Force



    <br>

   <?php if (
    (int)$mecun_info['id'] == 404 ||
    (int)$mecun_info['id'] == 405 ||
    (int)$mecun_info['id'] == 406
): ?>

<a href="eshyalar.php?go=ool&rid=<?php echo (int)$mecun_info['id']; ?>&tipi=50">
    [Satın al]
</a>

<?php else: ?>

<a href="eshyalar.php?go=al&rid=<?php echo (int)$mecun_info['id']; ?>&tipi=100">
    [Satın al]
</a>

<?php endif; ?>

</p>



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

        <a href="eshyalar.php?">

            <img
                src="muxtelif/dukan.png"
                alt=" "
            >

            Merkez dükan

        </a>

    </li>

</div>


<?php elseif ($go == 'dukan_info' && !empty($esya)): ?>


<!-- DUKAN ƏŞYA PARAMETRLƏRİ -->

<div class="content">

    <table border="0" cellpadding="2" cellspacing="0">

        <tr>

            <td>

                <img
                    src="<?php echo htmlspecialchars($esya['img']); ?>"
                    alt="foto"
                >

            </td>

            <td>

                <b>Ad:</b>
                <?php echo htmlspecialchars($esya['ad']); ?>

                <br>

                <b>Merhele:</b>
                <?php echo (int)$esya['seviyye']; ?>

                <br>

                <b>Tip:</b>
                <?php echo htmlspecialchars($esya['tip']); ?>

                <br>

            </td>

        </tr>

    </table>

</div>

<p>

    <b>Qiymet:</b>
    <?php echo (int)$esya['qiymet']; ?> Qızıl

    <br>

    <b>Mexsusdur:</b> Force

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
    in_array(
        strtolower($esya['tip']),
        ['qilinc', 'balta', 'gurz', 'uzuk']
    )
): ?>

    <b>Mini.zerbe:</b>
    <?php echo (int)$esya['min_zerbe']; ?> | 0%

    <br>

    <b>Maks.zerbe:</b>
    <?php echo (int)$esya['max_zerbe']; ?> | 0%

    <br>

<?php endif; ?>

<b>Can:</b>
+<?php echo (int)$esya['can']; ?> | 0%

<br>

<b>Müdafie:</b>
+<?php echo (int)$esya['mudafie']; ?> | 0%

<br><br>

<b>Krit:</b>
+<?php echo (int)$esya['krit']; ?>

<br>

<b>Anti Krit:</b>
+<?php echo (int)$esya['anti_krit']; ?>

<br>

<b>Uvorot:</b>
+<?php echo (int)$esya['uvorot']; ?>

<br>

<b>Anti Uvorot:</b>
+<?php echo (int)$esya['anti_uvorot']; ?>

<br>

<b>Davamlılıq:</b>
<?php echo (int)$esya['davamliliq']; ?>%

<br>

<img
    src="img/davamliliq.png"
    alt=""
    style="margin:1px;"
>

<br>

<input
    type="button"
    class="button"
    value="Geri"
    onclick="goGeri()"
>

</p>

<br>

<div class="menu">

    <br>

    <li>

        <a href="eshyalar.php">

            <img
                src="muxtelif/dukan.png"
                alt=" "
            >

            Merkez dükan

        </a>

    </li>

</div>
<?php elseif ($go == 'al'): ?>

<br>

<?php if ($mecun_satish_ok): ?>

    <div class="success">

        <img
            src="muxtelif/okey.png"
            alt=""
        >

        Bu Mecunu aldınız.
        Mecun çantaya qoyuldu.
        Tebrikler

        <br>

    </div>

    <br>

    <div class="menu">

        <br>

        <li>

            <a href="chantam.php?">

                <img
                    src="muxtelif/sandiq.png"
                    alt=" "
                >

                Eşya çantası

            </a>

        </li>

        <li>

            <a href="eshyalar.php?">

                <img
                    src="muxtelif/dukan.png"
                    alt=" "
                >

                Merkez dükan

            </a>

        </li>

    </div>

<?php else: ?>

    <div class="battle_log">

        <div class="content">

            <?php echo htmlspecialchars($mecun_xeta); ?>

        </div>

    </div>

    <br>

    <div class="menu">

        <li>

            <a href="eshyalar.php?">

                <img
                    src="muxtelif/dukan.png"
                    alt=" "
                >

                Merkez dükan

            </a>

        </li>

    </div>

<?php endif; ?>
<?php elseif ($go == 'ool'): ?>

<br>
<?php if ($cekic_satish_ok): ?>

<div class="info">

<div class="success">
<img src="muxtelif/okey.png" alt="">
Çəkic çantaya göndərildi.<br>
</div>

<br>

<div class="menu">
<br>

<li>
<a href="chantam.php?">
<img src="muxtelif/sandiq.png" alt=" ">
Eşya çantası
</a>
</li>

<li>
<a href="eshyalar.php?">
<img src="muxtelif/dukan.png" alt=" ">
Merkez dukan
</a>
</li>

</div>
</div>

<?php endif; ?>



<?php if ($tipi != 50 && $almaz_satish_ok): ?>

    <div class="success">

        <img
            src="muxtelif/okey.png"
            alt=""
        >

        Almaz daşı çantaya göndərildi.

        <br>

    </div>

    <br>

    <div class="menu">

        <br>

        <li>

            <a href="chantam.php?">

                <img
                    src="muxtelif/sandiq.png"
                    alt=" "
                >

                Eşya çantası

            </a>

        </li>

        <li>

            <a href="eshyalar.php?">

                <img
                    src="muxtelif/dukan.png"
                    alt=" "
                >

                Merkez dükan

            </a>

        </li>

    </div>

<?php elseif ($tipi != 50): ?>

    <div class="battle_log">

        <div class="content">

            <?php echo htmlspecialchars($almaz_xeta); ?>

        </div>

    </div>

    <br>

    <div class="menu">

        <br>

        <li>

            <a href="eshyalar.php?">

                <img
                    src="muxtelif/dukan.png"
                    alt=" "
                >

                Merkez dükan

            </a>

        </li>

    </div>

<?php endif; ?>
<?php elseif ($go == 'c_info' && !empty($almaz_info)): ?>



<div class="content">

    <table border="0" cellpadding="2" cellspacing="0">

        <tr>

            <td>

                <img
                    src="<?php echo htmlspecialchars($almaz_info['img']); ?>"
                    alt="foto"
                >

            </td>

            <td>

                <b>Ad:</b>
                <?php echo htmlspecialchars($almaz_info['ad']); ?>

                <br>

                <b>Tip:</b>
                Daş

                <br>

                <b>Qiymet:</b>
                <?php echo (int)$almaz_info['qiymet']; ?> Brilliant

                <br>

            </td>

        </tr>

    </table>

</div>

<p>

    <b>Haqqında:</b>

    <u>
        <?php
        if ((int)$almaz_info['id'] == 369) {
            echo 'Eşyanın əsas parametrlərini 10% gücləndirmək üçün istifadə olunur';
        } elseif ((int)$almaz_info['id'] == 370) {
            echo 'Eşyanın əsas parametrlərini 10% gücləndirmək üçün istifadə olunur. Nəticələr 100% uğurlu alınır';
        } else {
            echo 'Eşyanın əsas parametrlərini 5% gücləndirmək üçün istifadə olunur';
        }
        ?>
    </u>
    <br>
   <b>Mexsusdur:</b>
Force

<br>

    <a href="eshyalar.php?go=ool&rid=<?php echo (int)$almaz_info['id']; ?>&tipi=51">
        [Satın al]
    </a>

</p>

<br>

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

        <a href="eshyalar.php?">

            <img
                src="muxtelif/dukan.png"
                alt=" "
            >

            Merkez dükan

        </a>

    </li>

</div>
<?php elseif ($go == 'd' && isset($tipi) && $tipi == 50): ?>

<div class="info">

<br>

<div class="center">

    <div class="block_line">

        Təmir çəkiciləri

    </div>

</div>

<div class="line"></div>

<br>

<?php

$sira = 1;

foreach ($cekicler as $cekic):

?>

<?php echo $sira; ?>).

<a href="eshyalar.php?go=m_info&amp;rid=<?php echo (int)$cekic['esya_id']; ?>">

    <?php echo htmlspecialchars(
        $cekic['ad'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>

</a>

<i>

    <?php

    $stmt_qiymet = $pdo->prepare("
        SELECT qiymet
        FROM esyalar
        WHERE id = :id
        LIMIT 1
    ");

    $stmt_qiymet->execute([
        ':id' => (int)$cekic['esya_id']
    ]);

    echo (int)$stmt_qiymet->fetchColumn();

    ?> Qızıl

</i>

<a href="eshyalar.php?go=ool&amp;rid=<?php echo (int)$cekic['esya_id']; ?>&amp;tipi=50">

    [Satın al]

</a>

<br>

<small>

    * * * * * * *

</small>

<br>

<?php

$sira++;

endforeach;

?>

<div class="menu">

<br>

<li>

<a href="eshyalar.php?">

<img
    src="muxtelif/dukan.png"
    alt=" "
>

Merkez dukan

</a>

</li>

</div>

</div>
<?php elseif ($go == 'd' && isset($tipi) && $tipi == 51): ?>


<div class="info">

<br>

<div class="center">

    <div class="block_line">

        Almaz daşları

    </div>

</div>

<div class="line"></div>

<br>

<?php

$sira = 1;

foreach ($almazlar as $almaz):

?>

<?php echo $sira; ?>).

<a
    href="eshyalar.php?go=c_info&amp;rid=<?php echo (int)$almaz['id']; ?>"
>

    <?php echo htmlspecialchars(
        $almaz['ad'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>

</a>

<i>

    <?php echo (int)$almaz['qiymet']; ?> Brilliant

</i>


<a href="eshyalar.php?go=ool&amp;rid=<?php echo (int)$almaz['id']; ?>&amp;tipi=51">[Satın al]

</a>

<br>

<small>

    * * * * * * *

</small>

<br>

<?php

$sira++;

endforeach;

?>


<div class="menu">

<br>

<li>

<a href="eshyalar.php?">

<img
    src="muxtelif/dukan.png"
    alt=" "
>

Merkez dükan

</a>

</li>

</div>

</div>

<?php elseif ($go == 'd' && isset($tipi) && $tipi == 53): ?>

<div class="info">

<br>

<div class="center">

    <div class="block_line">

        Əşya Çantaları

    </div>

</div>

<div class="line"></div>


<b>Siz buradan çantanızın tutumunu artıra bilersiz</b>

<br>

<b>Her birinin müddeti 30 gün teşkil edir</b>

<hr>

1) Çanta tutumu 34:
<i>50000 Qızıl</i>

<a href="eshyalar.php?go=sumka_al&amp;tipi=1">
    [Satın al]
</a>

<br>

2) Çanta tutumu 50:
<i>100000 Qızıl</i>

<a href="eshyalar.php?go=sumka_al&amp;tipi=2">
    [Satın al]
</a>

<br>

3) Çanta tutumu 150:
<i>100 Brilliant</i>

<a href="eshyalar.php?go=sumka_al&amp;tipi=3">
    [Satın al]
</a>

<br>

4) Çanta tutumu 200:
<i>150 Brilliant</i>

<a href="eshyalar.php?go=sumka_al&amp;tipi=4">
    [Satın al]
</a>

<br>

<div class="menu">

<br>

<li>

<a href="eshyalar.php?">

<img
    src="muxtelif/dukan.png"
    alt=" "
>

Merkez dükan

</a>

</li>

</div>

</div>
<?php elseif ($go == 'c' && isset($tipi) && $tipi == 101): ?>

<div class="info">

<br>

<div class="center">
    <div class="block_line">
        Zərbə mecunları
    </div>
</div>

<div class="line"></div>

<br>

<div class="error">
    <img src="muxtelif/eror.png" alt="">
    Bu mecun növü hazırlanır
</div>

<br>

<div class="menu">

<br>

<li>

<a href="eshyalar.php?">

<img
    src="muxtelif/dukan.png"
    alt=" "
>

Merkez dukan

</a>

</li>

</div>

</div>


<?php elseif ($go == 'c' && isset($tipi) && $tipi == 102): ?>

<div class="info">

<br>

<div class="center">
    <div class="block_line">
        Müdafiə mecunları
    </div>
</div>

<div class="line"></div>

<br>

<div class="error">
    <img src="muxtelif/eror.png" alt="">
    Bu mecun növü hazırlanır
</div>

<br>

<div class="menu">

<br>

<li>

<a href="eshyalar.php?">

<img
    src="muxtelif/dukan.png"
    alt=" "
>

Merkez dukan

</a>

</li>

</div>

</div>


<?php elseif ($go == 'c' && isset($tipi) && $tipi == 103): ?>

<div class="info">

<br>

<div class="center">
    <div class="block_line">
        Təcrübə mecunları
    </div>
</div>

<div class="line"></div>

<br>

<div class="error">
    <img src="muxtelif/eror.png" alt="">
    Bu mecun növü hazırlanır
</div>

<br>

<div class="menu">

<br>

<li>

<a href="eshyalar.php?">

<img
    src="muxtelif/dukan.png"
    alt=" "
>

Merkez dukan

</a>

</li>

</div>

</div>
<?php elseif ($go == 'c' && isset($tipi) && $tipi == 100): ?>

<div class="info">

<br>

<div class="center">

    <div class="block_line">

        Can Mecunları

    </div>

</div>

<div class="line"></div>

<br>

<?php

$sira = 1;

foreach ($mecunlar as $mecun):

?>

<?php echo $sira; ?>).

<a href="eshyalar.php?go=m_info&amp;rid=<?php echo (int)$mecun['id']; ?>">

    <?php echo htmlspecialchars(
        $mecun['ad'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>

</a>

<i>

    <?php echo (int)$mecun['qiymet']; ?> Brilliant

</i>

<a href="eshyalar.php?go=al&amp;rid=<?php echo (int)$mecun['id']; ?>&amp;tipi=100">

    [Satın al]

</a>

<br>

<small>

    * * * * * * *

</small>

<br>

<?php

$sira++;

endforeach;

?>

<div class="menu">

<br>

<li>

<a href="eshyalar.php?">

<img
    src="muxtelif/dukan.png"
    alt=" "
>

Merkez dükan

</a>

</li>

</div>

</div>
<?php elseif ($go == 'c' && isset($tipi) && $tipi == 202): ?>

<div class="info">

<br>

<div class="center">

    <div class="block_line">

        Müdafie Mecunları

    </div>

</div>

<div class="line"></div>

<br>

<?php

$sira = 1;

foreach ($mecunlar as $mecun):

?>

<?php echo $sira; ?>).

<a href="eshyalar.php?go=m_info&amp;rid=<?php echo (int)$mecun['id']; ?>">

    <?php echo htmlspecialchars(
        $mecun['ad'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>

</a>

<i>

    <?php echo (int)$mecun['qiymet']; ?> Brilliant

</i>

<a href="eshyalar.php?go=al&amp;rid=<?php echo (int)$mecun['id']; ?>&amp;tipi=202">

    [Satın al]

</a>

<br>

<small>

    * * * * * * *

</small>

<br>

<?php

$sira++;

endforeach;

?>

<div class="menu">

<br>

<li>

<a href="eshyalar.php?">

<img
    src="muxtelif/dukan.png"
    alt=" "
>

Merkez dukan

</a>

</li>

</div>

</div>
<?php elseif ($go == 'c' && isset($tipi) && $tipi == 203): ?>

<div class="info">
<br>
<div class="center">
    <div class="block_line">Təcrübə məcunları</div>
</div>
<div class="line"></div>
<br>

<div class="error">
    <img src="muxtelif/eror.png" alt="">
    Bu mecun növü hazırlanır
</div>

<br>

<div class="menu">
<br>
<li>
    <a href="eshyalar.php">
        <img src="muxtelif/dukan.png" alt=" ">
        Merkez dükan
    </a>
</li>
</div>

</div>
<?php elseif ($go == 'c' && isset($tipi) && $tipi == 201): ?>

<div class="info">

<br>

<div class="center">

    <div class="block_line">

        Zerbe Mecunları

    </div>

</div>

<div class="line"></div>

<br>

<?php

$sira = 1;

foreach ($mecunlar as $mecun):

?>

<?php echo $sira; ?>).

<a href="eshyalar.php?go=m_info&amp;rid=<?php echo (int)$mecun['id']; ?>">

    <?php echo htmlspecialchars(
        $mecun['ad'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>

</a>

<i>

    <?php echo (int)$mecun['qiymet']; ?> Brilliant

</i>

<a href="eshyalar.php?go=al&amp;rid=<?php echo (int)$mecun['id']; ?>&amp;tipi=201">

    [Satın al]

</a>

<br>

<small>

    * * * * * * *

</small>

<br>

<?php

$sira++;

endforeach;

?>

<div class="menu">

<br>

<li>

<a href="eshyalar.php?">

<img
    src="muxtelif/dukan.png"
    alt=" "
>

Merkez dukan

</a>

</li>

</div>

</div>

<?php elseif ($go == 'c' && isset($tipi) && $tipi == 200): ?>

<div class="info">

<br>

<div class="center">

    <div class="block_line">

        Can Mecunları

    </div>

</div>

<div class="line"></div>

<br>

<?php

$sira = 1;

foreach ($mecunlar as $mecun):

?>

<?php echo $sira; ?>).

<a href="eshyalar.php?go=m_info&amp;rid=<?php echo (int)$mecun['id']; ?>">

    <?php echo htmlspecialchars(
        $mecun['ad'],
        ENT_QUOTES,
        'UTF-8'
    ); ?>

</a>

<i>

    <?php echo (int)$mecun['qiymet']; ?> Brilliant

</i>

<a href="eshyalar.php?go=al&amp;rid=<?php echo (int)$mecun['id']; ?>&amp;tipi=200">

    [Satın al]

</a>

<br>

<small>

    * * * * * * *

</small>

<br>

<?php

$sira++;

endforeach;

?>

<div class="menu">

<br>

<li>

<a href="eshyalar.php?">

<img
    src="muxtelif/dukan.png"
    alt=" "
>

Merkez dukan

</a>

</li>

</div>

</div>

<?php elseif ($go == 'b'): ?>


<!-- MƏRHƏLƏ ƏŞYALARI -->

<br>

<div class="center">

    <div class="block_line">

        <?php echo $seviyye; ?> Mərhələnin Əşyaları

    </div>

</div>

<br>

<div class="line"></div>

<br>

<?php

$sira = 1;

if (!empty($esyalar)):

    foreach ($esyalar as $esya):

?>

    <?php echo $sira; ?>).

    <a
        href="eshyalar.php?go=dukan_info&rid=<?php echo (int)$esya['id']; ?>"
    >

        <?php echo htmlspecialchars($esya['ad']); ?>

    </a>

    [<?php echo (int)$esya['seviyye']; ?>]

    <i>
        <?php echo (int)$esya['qiymet']; ?> qızıl
    </i>

    <a
        href="eshyalar.php?go=a2&rid=<?php echo (int)$esya['id']; ?>"
    >

        [Satın al]

    </a>

    <br>

    <hr>

<?php

        $sira++;

    endforeach;

else:

?>

    <div class="center">

        Bu Mərhələ Əşyaları Yaxın Zamanda Oyunda Olacaq.

    </div>

<?php endif; ?>


<div class="menu">

    <br>

    <li>

        <a href="eshyalar.php">

            <img
                src="muxtelif/dukan.png"
                alt=" "
            >

            Merkez dükan

        </a>

    </li>

</div>


<?php else: ?>


<!-- ƏSAS MƏRKƏZ DÜKAN -->

<br>

<div class="center">

    <div class="block_line">

        Merkez Dükan

    </div>

</div>

<br>

<div class="line"></div>

<br>


<!-- MƏRHƏLƏ SEÇİMİ -->

<form
    method="post"
    action="eshyalar.php?go=b"
>

    <b>Eşyalar:</b>

    <br>

    <select name="tipi">

        <?php for ($i = 1; $i <= 14; $i++): ?>

            <option value="<?php echo $i; ?>">

                Merhele <?php echo $i; ?>

            </option>

        <?php endfor; ?>

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
        value="Ok"
    >

</form>

<br>


<!-- DÖYÜŞ MƏCUNLARI -->

<form
    method="post"
    action="eshyalar.php?go=c"
>

    <b>Döyüşde istifade olunan mecunlar:</b>

    <br>

    <select name="tipi">

        <option value="100">Can mecunları</option>
        <option value="101">Zerbe mecunları</option>
        <option value="102">Müdafie mecunları</option>
        <option value="103">Tecrübe mecunları</option>

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
        value="Ok"
    >

</form>

<br>


<!-- VAXTLI MƏCUNLAR -->

<form
    method="post"
    action="eshyalar.php?go=c"
>

    <b>Vaxt ile istifade olunan mecunlar:</b>

    <br>

    <select name="tipi">

        <option value="200">Can mecunları</option>
        <option value="201">Zerbe mecunları</option>
        <option value="202">Müdafie mecunları</option>
        <option value="203">Tecrübe mecunları</option>

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
        value="Ok"
    >

</form>

<br>


<!-- DİGƏR ƏŞYALAR -->

<form
    method="post"
    action="eshyalar.php?go=d"
>

    <b>Digər Eşyalar:</b>

    <br>

    <select name="tipi">

        <option value="50">Temir çekicleri</option>
        <option value="51">Almaz daşları</option>
        <option value="53">Eşya çantaları</option>

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
        value="Ok"
    >

</form>

<br>


<!-- ALT MENYU -->

<div class="menu">

    <li>

        <a href="enerji.php?go=hediyye">

            <img
                src="img/energy.png"
                alt=""
            >

            Enerji al

        </a>

    </li>

    <br>

    <li>

        <a href="hediyye.php?go=hediyye">

            <img
                src="muxtelif/gifts.gif"
                alt=""
            >

            Hediyye ver

        </a>

    </li>

    <br>

    <li>

        <a href="avatars.php?go=hediyye">

            <img
                src="muxtelif/doyuscu1.png"
                alt=""
            >

            Avatar Al

        </a>

    </li>

    <br>

    <li>

        <a href="qalxan.php?go=s">

            <img
                src="muxtelif/zashita.png"
                alt=""
            >

            Qalxan Statusu

        </a>

    </li>

    <br><br>

    <div class="center">

        <div class="block_line">

            <span class="green">

                <b>
                    Brilliant hesabını artır
                </b>

            </span>

        </div>

    </div>

    <br>

    <div class="menu">

        <li>

            <a href="simt.php?">

                Sim kontur / Portmanat kod

            </a>

        </li>

    </div>

</div>

<?php endif; ?>

</div>


<!-- FOOTER -->

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

                    <br><br>

                <a href="index.php?">Çıxış (<?php echo htmlspecialchars($user_login); ?>)</a>

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