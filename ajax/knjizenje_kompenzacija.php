<?php

if (isset($_POST['id_proknjizi'])) {

    $id_kompenzacije = $_POST['id_proknjizi'];
    $datum_knjizenja = $_POST['datum_knjizenja'];

    //POZIVANJE PROCEDURE ZA KNJIZENJE
    $upit_knjizenje = "knjizenje_kompenzacije('$id_kompenzacije', '$datum_knjizenja')";


    //IZVRSAVANJE UPITA
    $rezultat = pg_query($amso_konekcija, $upit_knjizenje);

    //DEO KODA ZA TESTIRANJE.ODKOMENTARISATI ZA SLANJE PORUKE O USPESNOM KNJIZENJU
    //$rezultat = true;

    //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA
    if (!$rezultat) {

        $niz_podaci['obavestenje'] = 'Greska pri izvrsavanju upita';
        echo json_encode($niz_podaci);
        die();
    } 

    else {
        
        $niz_podaci['obavestenje'] = 'Knjizenje uspesno';
    }

    echo json_encode($niz_podaci);
}