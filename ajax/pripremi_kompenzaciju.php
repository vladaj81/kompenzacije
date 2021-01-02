<?php

//AKO JE PRITISNUTO DUGME PRIPREMI KOMPENZACIJU NA FRONTENDU
if (isset($_POST['datum'])) {

    //UKLJUCIVANJE KONEKCIJE KA AMSO BAZI
    require_once '../dodaci/konekcija_amso.php';

    //UPIT ZA DOBIJANJE POSLEDNJEG SISTEMSKOG BROJA KOMPENZACIJE IZ TABELE KOMPENZACIJA ZAGLAVLJE
    $upit_sistemski_broj = "SELECT max(sistemski_broj) FROM kompenzacija_zaglavlje";

    //IZVRSAVANJE UPITA
    $rezultat_sis_broj = pg_query($amso_konekcija, $upit_sistemski_broj);

    //KREIRAJ PORUKU AKO JE DOSLO DO GRESKE PRI IZVRSAVANJU UPITA
    if (!$rezultat_sis_broj) {

        $niz_obavestenje['obavestenje'] = 'Greška pri izvršavanju upita'.pg_last_error($amso_konekcija);
    }

    //AKO UPIT VRATI REZULTATE
    if (pg_num_rows($rezultat_sis_broj) > 0) {

        $red_sis_broj = pg_fetch_array($rezultat_sis_broj); 

        //KREIRANJE SISTEMSKOG BROJA ZA UNOS,UVECAVANJEM POSLEDNJEG SISTEMSKOG BROJA ZA 1
        $sistemski_broj = $red_sis_broj['max'] + 1;
    }
    
    //UPISIVANJE POSLATIH PODATAKA IZ FORME ZA PREDLOG KOMPENZACIJE U VARIJABLE
    $datum_stanja = $_POST['datum'];
    $partner = $_POST['sifra_partnera'];
    $radnik = $_POST['radnik'];
    $broj_kompenzacije = $_POST['broj_kompenzacije'];

    $niz_duguje = $_POST['niz_duguje'];
    $niz_potrazuje = $_POST['niz_potrazuje'];

    //DOBIJANJE TRENUTNE GODINE I KONVERTOVANJE U INT FORMAT
    $sistemska_godina = intval(date("Y"));
    

    //UPIT ZA UNOS U TABELU KOMPENZACIJA ZAGLAVLJE.UPIT TAKODJE VRACA ID POSLEDNJEG UNETOG REDA
    $upit_zaglavlje = "INSERT INTO kompenzacija_zaglavlje(datum_stanja, partner, broj_kompenzacije, sistemski_broj, sistemska_godina, datum_slanja_pdfa, datum_vracanja_pdfa) VALUES('$datum_stanja', '$partner', '$broj_kompenzacije', '$sistemski_broj', '$sistemska_godina', null, null) RETURNING kompenzacija_zaglavlje_id";
    
    //IZVRSAVANJE UPITA
    $rezultat = pg_query($amso_konekcija, $upit_zaglavlje);

    //KREIRAJ PORUKU AKO JE DOSLO DO GRESKE PRI IZVRSAVANJU UPITA
    if (!$rezultat) {

        $niz_obavestenje['obavestenje'] = 'Greška pri izvršavanju upita'.pg_last_error($amso_konekcija);
    }

    //AKO UPIT VRATI REZULTATE
    if (pg_num_rows($rezultat) > 0) {

        $red = pg_fetch_array($rezultat); 

        //DOBIJANJE ID-JA POSLEDNJEG UNETOG REDA IZ TABELE KOMPENZACIJA_ZAGLAVLJE
        $poslednji_id = $red['kompenzacija_zaglavlje_id'];
    }

    //UPIT ZA KREIRANJE LOGA U TABELI KOMPENZACIJA_AKCIJE_LOG ZA NOVOUNETU KOMPENZACIJU
    $upit_akcije_log = "INSERT INTO kompenzacija_akcije_log(kompenzacija_id, akcija, radnik, datum_promene_statusa) VALUES('$poslednji_id', 'kreirano', '$radnik', 'now()')";

    //IZVRSAVANJE UPITA
    $rezultat_log = pg_query($amso_konekcija, $upit_akcije_log);

    //KREIRAJ PORUKU AKO JE DOSLO DO GRESKE PRI IZVRSAVANJU UPITA
    if (!$rezultat_log) {

        $niz_obavestenje['greska_log'] = 'Greška pri izvršavanju upita'.pg_last_error($amso_konekcija);
    }

    //ZA SVAKU PREDLOZENU STAVKU SA DUGOVNE STRANE
    foreach ($niz_duguje as $predlog_duguje) {

        //UPIS VREDNOSTI IZ POLJA U PROMENLJIVE
        $kompenzacija_zaglavlje_id = $poslednji_id;
        $konto = $predlog_duguje[2];
        $broj_dok = $predlog_duguje[3];
        $duguje = $predlog_duguje[1];
        $potrazuje = 0;
       
        if ($predlog_duguje[4] != null) {

            $kanal_prodaje = (int)$predlog_duguje[4];
        }
        else {
            $kanal_prodaje = 'null';
        }
        
        //UPIT ZA UNOS U TABELU KOMPENZACIJA STAVKE
        $upit_stavke_duguje = "INSERT INTO kompenzacija_stavke(kompenzacija_zaglavlje_id, konto, brojdok, duguje, potrazuje, kanal_prodaje) VALUES('$kompenzacija_zaglavlje_id', '$konto', '$broj_dok', '$duguje', '$potrazuje', $kanal_prodaje)";

        //IZVRSAVANJE UPITA
        $rezultat_duguje = pg_query($amso_konekcija, $upit_stavke_duguje);

        //KREIRAJ PORUKU AKO JE DOSLO DO GRESKE PRI IZVRSAVANJU UPITA
        if (!$rezultat_duguje) {

            $niz_obavestenje['greska_duguje'] = 'Greška pri izvršavanju upita'.pg_last_error($amso_konekcija);
        }
    }
    
    //ZA SVAKU PREDLOZENU STAVKU SA POTRAZNE STRANE
    foreach ($niz_potrazuje as $predlog_potrazuje) {

        //UPIS VREDNOSTI IZ POLJA U PROMENLJIVE
        $kompenzacija_zaglavlje_id = $poslednji_id;
        $konto = $predlog_potrazuje[2];
        $broj_dok = $predlog_potrazuje[3];
        $potrazuje = $predlog_potrazuje[1];
        $duguje = 0;

        if ($predlog_potrazuje[4] != 0) {

            $kanal_prodaje = (int)$predlog_potrazuje[4];
        }
        else {
            $kanal_prodaje = 'null';
        }

        //UPIT ZA UNOS U TABELU KOMPENZACIJA STAVKE
        $upit_stavke_potrazuje = "INSERT INTO kompenzacija_stavke(kompenzacija_zaglavlje_id, konto, brojdok, duguje, potrazuje, kanal_prodaje) VALUES('$kompenzacija_zaglavlje_id', '$konto', '$broj_dok', '$duguje', '$potrazuje', $kanal_prodaje)";

        //IZVRSAVANJE UPITA
        $rezultat_potrazuje = pg_query($amso_konekcija, $upit_stavke_potrazuje);

        //KREIRAJ PORUKU AKO JE DOSLO DO GRESKE PRI IZVRSAVANJU UPITA
        if (!$rezultat_potrazuje) {

            $niz_obavestenje['greska_potrazuje'] = 'Greška pri izvršavanju upita'.pg_last_error($amso_konekcija);
        }
    }

    //AKO POSTOJI OBAVESTENJE O GRESKAMA U NIZU OBAVESTENJE,POSALJI GA NA FRONTEND.U SUPROTNOM POSALJI OBAVESTENJE O USPESNOM UNOSU PREDLOGA U BAZU
    if (!empty($niz_obavestenje)) {

        echo json_encode($niz_obavestenje);

    } else {

        echo json_encode('Predlog kompenzacije i stavke uspešno sačuvani u bazi.');
    }
}

