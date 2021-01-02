<?php

//PODESAVANJE VREMENSKE ZONE
date_default_timezone_set('UTC');

//AKO JE PROSLEDJEN DATUM SLANJA PDF-A SA DATEPICKERA NA FRONTENDU
if (isset($_POST['datum_slanja'])) {

    //UKLJUCIVANJE KONEKCIJE KA AMSO BAZI
    require_once '../dodaci/konekcija_amso.php';

    //UPIT ZA DOBIJANJE VREMENA KREIRANJA PREDLOGA KOMPENZACIJE
    $upit_vreme = "SELECT datum_promene_statusa FROM kompenzacija_akcije_log 
                    WHERE kompenzacija_id = ".$_POST['id_poslat_pdf']." 
                    AND akcija = 'kreirano'
                    ORDER BY datum_promene_statusa LIMIT 1";

        //IZVRSAVANJE UPITA
        $rezultat_vreme = pg_query($amso_konekcija, $upit_vreme);

        //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA
        if (!$rezultat_vreme) {

            $niz_slanje['status'] = 'Greška pri izvršavanju upita. Pokušajte ponovo.';
            echo json_encode($niz_slanje);
            die();
        } 
    
        //AKO UPIT VRATI REZULTATE
        if (pg_num_rows($rezultat_vreme) > 0) {
            
            //UPISIVANJE DOBIJENOG REDA IZ BAZE U NIZ
            $red_vreme = pg_fetch_array($rezultat_vreme);

            //CUVANJE DATUMA U DATE FORMATU ZBOG UPDATE-A U BAZI
            $datum_za_unos = $_POST['datum_slanja'];

            //FORMATIRANJE DATUMA KREIRANJA IZ BAZE U FORMAT Y-M-D.(IZBACIVANJE VREMENA)
            $datum_iz_baze = date_create($red_vreme['datum_promene_statusa']);
            $datum_iz_baze = date_format($datum_iz_baze, "Y-m-d");

            //KONVERZIJA DATUMA KREIRANJA IZ BAZE U INT FORMAT,ZBOG UPOREDJIVANJA SA DATUMOM SLANJA
            $datum_kreiranja = new DateTime($datum_iz_baze);
			$datum_kreiranja = $datum_kreiranja->getTimestamp();
            
            //KONVERZIJA DATUMA SLANJA U INT FORMAT,ZBOG UPOREDJIVANJA SA DATUMOM KREIRANJA IZ BAZE
            $datum_slanja = new DateTime($_POST['datum_slanja']);
            $datum_slanja = $datum_slanja->getTimestamp();
        }

        //AKO JE ODABRANI DATUM SLANJA MANJI OD DATUMA KREIRANJA,OBAVESTI KORISNIKA
        if ($datum_slanja < $datum_kreiranja) {

            $niz_slanje['status'] = 'Datum slanja mora biti veći od datuma kreiranja. Ponovite unos';
            echo json_encode($niz_slanje);
            die();
        }
        
        //AKO JE SVE OK,UNESI DATUM SLANJA PDF-A U BAZU
        else {

            //UPIT ZA AZURIRANJE DATUMA SLANJA PDF-A ZA ODGOVARAJUCU KOMPENZACIJU
            $upit = "UPDATE kompenzacija_zaglavlje SET datum_slanja_pdfa = CAST('$datum_za_unos' AS DATE) WHERE kompenzacija_zaglavlje_id = ".$_POST['id_poslat_pdf']." ";

            //IZVRSAVANJE UPITA
            $rezultat = pg_query($amso_konekcija, $upit);

            //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA
            if (!$rezultat) {

                $niz_slanje['status'] = 'Greška pri izvršavanju upita. Pokušajte ponovo.';
                echo json_encode($niz_slanje);
                die();
            }

            //AKO JE SVE OK,OBAVESTI KORISNIKA I OSVEZI STRANICU
            else {
                $niz_slanje['uneseno'] = 'Uspešno ste uneli datum slanja pdf-a u bazu.';                   
      
            }
            
            echo json_encode($niz_slanje);
        }
}