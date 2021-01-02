<?php

//AKO JE KLIKNUTO DUGME ODOBRI KOMPENZACIJU NA FRONTENDU
if (isset($_POST['id_odobri'])) {

    //UKLJUCIVANJE KONEKCIJE KA AMSO BAZI
    require_once '../dodaci/konekcija_amso.php';

    //UPIS ID-JA KOMPENZACIJE I RADNIKA U PROMENJIVE
    $kompenzacija_id = $_POST['id_odobri'];
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

        $niz_slanje['obavestenje'] = 'Dokument je već proknjižen.';
        echo json_encode($niz_slanje);
        die();
    }

    //AKO JE DOKUMENT VEC STORNIRAN,OBAVESTI KORISNIKA
    elseif ($status_kompenzacije == 'stornirano') {

        $niz_slanje['obavestenje'] = 'Dokument je storniran. Nije dozvoljena željena radnja.';
        echo json_encode($niz_slanje);
        die();
    }

    //AKO JE DOKUMENT VEC ODOBREN,OBAVESTI KORISNIKA
    elseif ($status_kompenzacije == 'odobreno') {

        $niz_slanje['obavestenje'] = 'Dokument je već odobren.';
        echo json_encode($niz_slanje);
        die();
    }

    else {
        //UPIT ZA UNOS STATUSA ODOBRENO U TABELU KOMPENZACIJA_AKCIJE_LOG ZA ZELJENU KOMPENZACIJU 
        $upit = "INSERT INTO kompenzacija_akcije_log(kompenzacija_id, akcija, radnik, datum_promene_statusa) VALUES ('$kompenzacija_id', 'odobreno', '$radnik', 'now()')";

        //IZVRSAVANJE UPITA
        $rezultat = pg_query($amso_konekcija, $upit);
        
        //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA I PREKINI IZVRSAVANJE SKRIPTE
        if (!$rezultat) {

            $niz_slanje['obavestenje'] = 'Greška pri izvršavanju upita. Pokušajte ponovo.';
            echo json_encode($niz_slanje);
            die();
        }
        
        //AKO SE UPIT IZVRSI USPESNO
        else {

            //UPIS OBAVESTENJA O USPESNOM AZURIRANJU DOKUMENTA
            $niz_slanje['odobreno'] = 'Uspešno ste promenili status dokumenta u odobren.';
        }
            echo json_encode($niz_slanje);
    }
}