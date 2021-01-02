<?php
//AKO JE PROSLEDJENA SIFRA PARTNERA SA FRONTENDA
if (isset($_POST['sifra_partnera'])) {
    
    //UKLJUCIVANJE KONEKCIJE KA AMSO BAZI
    require_once '../dodaci/konekcija_amso.php';

    //KONVERTOVANJE SIFRE PARTNERA U STRING FORMAT
    $sifra_partnera = strval($_POST['sifra_partnera']);
    
    //UPIT ZA SELEKTOVANJE PODATAKA O PARTNERU
    $upit = "SELECT DISTINCT naziv,sifra,adresa,mesto,posbroj,telefon FROM partneri WHERE sifra = '$sifra_partnera' ";

    //IZVRSAVANJE UPITA
    $rezultat = pg_query($amso_konekcija, $upit);

   
    //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA
    if (!$rezultat) {

        $niz_partneri['obavestenje'] = 'Greška pri izvršavanju upita'.pg_last_error($amso_konekcija);
        echo json_encode( $niz_partneri['obavestenje']);
        die();
    } 

    //AKO UPIT VRATI REZULTATE
    if (pg_num_rows($rezultat) > 0) {
        
        //UPISIVANJE DOBIJENIH REDOVA IZ BAZE U NIZ
        $red = pg_fetch_array($rezultat);
        
        //KONVERTOVANJE PODATAKA IZ BAZE U UTF8 FORMAT
        $naziv = mb_convert_encoding($red['naziv'], 'UTF-8', 'ISO-8859-2'); 
        $adresa = mb_convert_encoding($red['adresa'], 'UTF-8', 'ISO-8859-2'); 
        $mesto = mb_convert_encoding($red['mesto'], 'UTF-8', 'ISO-8859-2'); 

        $niz_podaci [] = $naziv;
        $niz_podaci [] = $red['sifra'];
        $niz_podaci [] = $adresa;
        $niz_podaci [] = $mesto;
        $niz_podaci [] = $red['posbroj'];
        $niz_podaci [] = $red['telefon'];

        echo json_encode($niz_podaci);

    } else {
        //KREIRANJE OBAVESTENJA,AKO NEMA REZULTATA ZA IZVRSEN UPIT
        $niz_partneri['obavestenje'] = 'Nema partnera sa takvim imenom ili pibom';
        echo json_encode($niz_partneri);
    }
}
?>