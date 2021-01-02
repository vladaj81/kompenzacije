<?php

//AKO JE KLIKNUTO DUGME STORNIRAJ KOMPENZACIJU NA FRONTENDU
if (isset($_POST['id_storniraj'])) {

    //UKLJUCIVANJE KONEKCIJE KA AMSO BAZI
    require_once '../dodaci/konekcija_amso.php';

    //UPIS ID-JA KOMPENZACIJE I RADNIKA U PROMENJIVE
    $kompenzacija_id = $_POST['id_storniraj'];
    $radnik = $_POST['radnik'];

    //UPIT ZA PROVERU POSLEDNJEG STATUSA KOMPENZACIJE SA ODREDJENIM ID-JEM
    $upit_provera = "SELECT akcija FROM kompenzacija_akcije_log WHERE kompenzacija_id = '$kompenzacija_id' 
                    ORDER BY datum_promene_statusa DESC LIMIT 1";

    //IZVRSAVANJE UPITA
    $rezultat_provera = pg_query($amso_konekcija, $upit_provera);

    //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA.
    if (!$rezultat_provera) {

        $niz_slanje['obavestenje'] = 'Greška pri izvršavanju upita. Pokušajte ponovo.';
        echo json_encode($niz_slanje);
        die();
    }
    else {

        //UPISIVANJE DOBIJENOG REDA IZ BAZE U NIZ
        $red_provera = pg_fetch_array($rezultat_provera);

        //UPISIVANJE STATUSA KOMPENZACIJE IZ BAZE U PROMENJIVU
        $status_kompenzacije = $red_provera['akcija'];
    }

    //AKO JE DOKUMENT VEC PROKNJIZEN,OBAVESTI KORISNIKA
    if ($status_kompenzacije == 'proknjizeno') {

        $niz_slanje['obavestenje'] = 'Dokument je proknjižen. Nije dozvoljena željena radnja.';
        echo json_encode($niz_slanje);
        die();
    }

    //AKO JE DOKUMENT STORNIRAN,OBAVESTI KORISNIKA
    elseif ($status_kompenzacije == 'stornirano') {

        $niz_slanje['obavestenje'] = 'Dokument je već storniran.';
        echo json_encode($niz_slanje);
        die();
    }

    else {
        //UPIT ZA UNOS STATUSA STORNIRANO U TABELU KOMPENZACIJA_AKCIJE_LOG ZA ZELJENU KOMPENZACIJU 
        $upit = "INSERT INTO kompenzacija_akcije_log(kompenzacija_id, akcija, radnik, datum_promene_statusa) VALUES ('$kompenzacija_id', 'stornirano', '$radnik', 'now()')";

        //IZVRSAVANJE UPITA
        $rezultat = pg_query($amso_konekcija, $upit);
        
        //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA I PREKINI IZVRSAVANJE SKRIPTE
        if (!$rezultat) {

            $obavestenje = 'Greška pri izvršavanju upita. Pokušajte ponovo.' .pg_last_error($amso_konekcija);
            echo json_encode($obavestenje);
            die();
        }
        
        //AKO SE UPIT IZVRSI USPESNO
        else {

            //UPIS OBAVESTENJA O USPESNOM STORNIRANJU DOKUMENTA
            $niz_slanje['stornirano'] = 'Uspešno ste stornirali dokument.';
        }
            echo json_encode($niz_slanje);
    }
}