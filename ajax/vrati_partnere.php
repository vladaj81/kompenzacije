<?php

//AKO JE IZVRSEN AUTOCOMPLETE NA FRONTENDU I PROSLEDJEN POJAM ZA PRETRAGU
if (isset($_POST['datum'])) {

    //UKLJUCIVANJE KONEKCIJE KA AMSO BAZI
    require_once '../dodaci/konekcija_amso.php';

    $datum = $_POST['datum'];

    //POZIVANJE PROCEDURE ZA DOBIJANJE LISTE PARTNERA SA OTVORENIM STAVKAMA,NA ZELJENI DATUM;
    $upit = "SELECT * FROM get_lista_partnera_otvorene_stavke_za_datum('$datum')";

    //IZVRSAVANJE UPITA
    $rezultat = pg_query($amso_konekcija, $upit);

    //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA
    if (!$rezultat) {

        $niz_partneri['obavestenje'] = 'Greška pri izvršavanju upita'.pg_last_error($amso_konekcija);
        echo json_encode($niz_partneri);
        die();
    } 

    //AKO UPIT VRATI REZULTATE
    if (pg_num_rows($rezultat) > 0) {

        //UPISIVANJE DOBIJENIH REDOVA IZ BAZE U NIZ
        while ($red = pg_fetch_array($rezultat)) {
            
            //KONVERTOVANJE PODATAKA IZ BAZE U UT8 FORMAT
            $niz_partneri[] = array(

                'partner' => mb_convert_encoding($red['partner'], 'UTF-8', 'ISO-8859-2'),
                'sifra' => $red['sifra']
            );
        }

    } else {
        //KREIRANJE OBAVESTENJA,AKO NEMA REZULTATA ZA IZVRSEN UPIT
        $niz_partneri['obavestenje'] = 'Nema partnera sa takvim imenom ili pibom';
    }

    echo json_encode($niz_partneri);
}
