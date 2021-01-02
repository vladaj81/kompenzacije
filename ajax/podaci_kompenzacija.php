<?php
//AKO JE PRITISNUTO DUGME PRIKAZI OTVORENE STAVKE NA FRONTENDU
if (isset($_POST['sifra_partnera'])) {

    //UKLJUCIVANJE KONEKCIJE KA AMSO BAZI
    require_once '../dodaci/konekcija_amso.php';

    //KONVERTOVANJE SIFRE PARTNERA U STRING FORMAT
    $sifra_partnera = strval($_POST['sifra_partnera']);

    //UPIS DATUMA U PROMENJIVU
    $datum_pregleda = $_POST['datum'];
    
    //UPIT ZA SELEKTOVANJE PODATAKA O OTVORENIM STAVKAMA PARTNERA
    $upit = "SELECT * FROM get_otvorene_stavke_za_partnera_za_datum('$datum_pregleda', '$sifra_partnera')";

    //IZVRSAVANJE UPITA
    $rezultat = pg_query($amso_konekcija, $upit);

    //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA
    if (!$rezultat) {

        $niz_partneri['greska'] = 'Greška pri izvršavanju upita.';
        echo json_encode($niz_partneri);
        die();
    } 

    //AKO UPIT VRATI REZULTATE
    if (pg_num_rows($rezultat) > 0) {

        //UPISIVANJE DOBIJENIH REDOVA IZ BAZE U NIZ
        while ($red = pg_fetch_array($rezultat)) {

            $naziv_konta = mb_convert_encoding($red['konto_naziv'], 'UTF-8', 'ISO-8859-2');
            
            $niz_partneri[] = array(

                'konto' => $red['konto'], 
                'naziv_konta' => $naziv_konta, 
                'brojdok' => $red['brojdok'], 
                'pib' => $red['pib'], 
                'duguje' => $red['duguje'], 
                'potrazuje' => $red['potrazuje'],
                'iznos' => $red['iznos']
            );
        }
        
    } else {
        //KREIRANJE OBAVESTENJA,AKO NEMA REZULTATA ZA IZVRSEN UPIT
        $niz_partneri['obavestenje'] = 'Za odabranog partnera nema potencijalnih računa za kreiranje predloga kompenzacije.';
    }

    echo json_encode($niz_partneri);
}
