<?php 

//AKO JE POSLAT AJAX POZIV SA FRONTENDA
if (isset($_POST)) {

    require_once '../dodaci/konekcija_amso.php';

    //POSTAVLJANJE LIMITA ZA BROJ KOMPENZACIJA PO STRANICI
    $kompenzacija_po_stranici = 8;  

    //KREIRANJE PROMENJIVIH ZA BROJ STRANICE I HTML OUTPUT
    $stranica = '';  
    $html = '';  

    //AKO JE KLIKNUTO NA ODREDJENU STRANICU,SETUJ PROMENJIVU NA TU VREDNOST.U SUPROTNOM SETUJ NA 1.
    if (isset($_POST["stranica"])) {  

        $stranica = $_POST["stranica"];

    } else { 

        $stranica = 1;  
    } 

    //SETOVANJE OD KOJEG ZAPISA IZ BAZE POCINJE PRIKAZ NA STRANICI
    $pocni_od = ($stranica - 1) * $kompenzacija_po_stranici;  

    //WITH SET SA SVIM POTREBNIM PODACIMA O PREDLOZIMA KOMPENZACIJE ZAPISIMA IZ LOG TABELE
    $with_set = "WITH kompenzacija_podaci AS(SELECT 
                                        a.akcija,
                                        a.datum_promene_statusa AS datum_kreiranja,
                                        k.kompenzacija_zaglavlje_id,
                                        k.datum_stanja,
                                        k.broj_kompenzacije,
                                        k.sistemski_broj || '/' || k.sistemska_godina AS sistemski_broj,
                                        k.datum_slanja_pdfa,
                                        k.datum_vracanja_pdfa,
                                        k.partner,
                                        p.naziv,
                                        r.ime

                                    FROM kompenzacija_zaglavlje k

                                        INNER JOIN kompenzacija_akcije_log a ON a.kompenzacija_id = k.kompenzacija_zaglavlje_id 	
                                        INNER JOIN partneri p ON p.sifra = k.partner 
                                        INNER JOIN radnik r ON r.radnik = a.radnik
                                        WHERE akcija = 'kreirano'
                                        ),
                                    poslednji_podaci AS(
                                    SELECT DISTINCT ON(kompenzacija_id) a.kompenzacija_id,a.akcija AS POSLEDNJA_AKCIJA,a.datum_promene_statusa,r.ime AS RADNIK_IME FROM kompenzacija_akcije_log a
                                            INNER JOIN radnik r ON r.radnik = a.radnik ORDER BY kompenzacija_id,a.datum_promene_statusa DESC 
                                    )";

    //UPIT ZA INICIJALNI PRIKAZ PODATAKA,KADA NEMA FILTERA
    $upit = $with_set. "SELECT * FROM kompenzacija_podaci";

    //AKO POSTOJI PARAMETAR ZA PRETRAGU PO PIBU
    if($_POST['filteri']['pib'] != '') {

        //KONVERTOVANJE PIBA PARTNERA U STRING
        $pib_partnera = strval($_POST['filteri']['pib']);

        //KREIRANJE NOVOG UPITA SA FILTERIMA I DODAVANJE USLOVA ZA PRETRAGU PO ZADATOM PIBU
        $upit = $with_set. "SELECT * FROM kompenzacija_podaci
                            INNER JOIN poslednji_podaci ON kompenzacija_podaci.kompenzacija_zaglavlje_id = poslednji_podaci.kompenzacija_id
                            WHERE partner = '$pib_partnera'";
    } 

    //AKO POSTOJI FILTER ZA PRETRAGU PO STATUSU KOMPENZACIJE
    if ($_POST['filteri']['status_knjizenja'] != '' && $_POST['filteri']['status_knjizenja'] != '-1') {

        $status_knjizenja = $_POST['filteri']['status_knjizenja'];

        //NASTAVLJANJE UPITA I DODAVANJE USLOVA
        $upit .= " AND poslednja_akcija = '$status_knjizenja'";
    }
    
    //AKO POSTOJI FILTER ZA PRIKAZ KOMPENZACIJA,ZA KOJE JE POSLAT PDF
    if ($_POST['filteri']['status_pdfa'] == 'poslat_pdf' && $_POST['filteri']['status_pdfa'] != '-1') {

        //NASTAVLJANJE UPITA I DODAVANJE USLOVA
        $upit .= " AND datum_slanja_pdfa IS NOT NULL";
    }

    //AKO POSTOJI FILTER ZA PRIKAZ KOMPENZACIJA,ZA KOJE JE VRACEN PDF
    if ($_POST['filteri']['status_pdfa'] == 'vracen_pdf' && $_POST['filteri']['status_pdfa'] != '-1') {

        //NASTAVLJANJE UPITA I DODAVANJE USLOVA
        $upit .= " AND datum_vracanja_pdfa IS NOT NULL";
    }
    
    //ZAVRSETAK UPITA ZA DOBIJANJE PODATAKA O PREDLOZIMA KOMPENZACIJE
    $upit .= " ORDER BY kompenzacija_zaglavlje_id DESC OFFSET $pocni_od LIMIT $kompenzacija_po_stranici";

    //IZVRSAVANJE UPITA
    $rezultat = pg_query($amso_konekcija, $upit);

    //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA I PREKINI IZVRSAVANJE SKRIPTE
    if (!$rezultat) {

        $html = 'Greška pri izvršavanju upita. Pokušajte ponovo.' .$upit. ' ' .pg_last_error($amso_konekcija);
        echo json_encode($html);
        die();
    }

    //AKO UPIT VRATI REZULTATE,GENERISI HTML SA SVIM PREDLOZENIM KOMPENZACIJAMA
    if (pg_num_rows($rezultat) > 0) {

        //DODAVANJE NASLOVA I FORME ZA PRETRAGU U HTML OUTPUT
        $html = '<h3 class="kompenzacija margina_naslov">Pregled svih predloga za kompenzaciju</h3>

                <form id="forma_filteri">
                    <div class="pretraga">

                        <div class="pib_wrapper">
                            <label for="input_pretraga" id="pib_labela">Pretraga po PIB-u</label>
                            <input type="text" id="input_pretraga" placeholder="Unesite pib za pretragu"/>
                        </div>

                        <div class="status_wrapper">
                            <label for="pretraga_kriterijumi" id="status_labela">Pretraga po statusu knjiženja</label>
                        
                            <select class="filtriraj_tabelu" id="pretraga_kriterijumi">
                                <option selected value="-1">Svi statusi knjiženja</option>
                                <option value="proknjizeno">Proknjiženo</option>
                                <option value="odobreno">Odobreno</option>
                                <option value="stornirano">Stornirano</option>
                            </select>
                        </div>

                        <div class="pdf_wrapper">
                            <label for="pretraga_pdf" id="pdf_labela">Pretraga po statusu knjiženja</label>

                            <select class="pdf_filter" id="pretraga_pdf">
                                <option selected value="-1">Svi statusi pdf-a</option>
                                <option value="poslat_pdf">Poslat pdf</option>
                                <option value="vracen_pdf">Vraćen pdf</option>
                            </select>
                        </div>

                        <div class="button_wrapper">
                            <button type="submit" class="btn btn-primary pretrazi">Pretraži</button> 
                        </div>

                        <div class="right_button_wrapper">
                            <button type="button" class="btn btn-success resetuj_filtere">Resetuj filtere</button> 
                        </div>
                    </div>
                </form>';

        //DODAVANJE TABELE U HTML OUTPUT
        $html .= '<table class="table table-bordered" id="tabela_predlozi">
                    <thead>
                        <tr>
                            <th>Redni broj</th>
                            <th>Sistemski broj</th>
                            <th>Broj komp.</th>
                            <th>Datum stanja GK</th>
                            <th>Vrednost</th>
                            <th>Partner</th>
                            <th>Odobrio</th>
                            <th>Proknjižio</th>
                            <th>Stornirao</th>
                            <th>Kreirao radnik</th>
                            <th>Poslat pdf</th>
                            <th>Vraćen pdf</th>
                            <th>Vreme kreiranja</th>
                            <th>Stavke i generisanje</th>
                            <th colspan="4">Akcije</th>
                        </tr>
                    </thead>
                    <tbody>';

        //GENERISANJE REDOVA U TABELI ZA SVAKI RED IZ BAZE
        while ($red = pg_fetch_array($rezultat)) {
            
            $upit_iznos = "SELECT SUM(duguje) AS iznos FROM kompenzacija_stavke WHERE kompenzacija_zaglavlje_id = ".$red['kompenzacija_zaglavlje_id']." ";

            //IZVRSAVANJE UPITA
            $rezultat_iznos = pg_query($amso_konekcija, $upit_iznos);

            //AKO UPIT VRATI REZULTATE,GENERISI HTML SA SVIM PREDLOZENIM KOMPENZACIJAMA
            if (pg_num_rows($rezultat_iznos) > 0) {

                $red_iznos = pg_fetch_array($rezultat_iznos);
            }

            //AKO POSTOJI NAZIV U TABELI PARTNERI,UPISI GA U VARIJABLU.U SUPROTNOM UPISI SIFRU
            if ($red['naziv']) {

                //ENKODIRANJE IMENA PARTNERA IZ BAZE U UTF-8 FORMAT
                $ime_partnera = mb_convert_encoding($red['naziv'], 'UTF-8', 'ISO-8859-2');

            } else {

                $ime_partnera = $red['sifra'];
            }

            //ENKODIRANJE IMENA RADNIKA IZ BAZE U UTF-8 FORMAT
            $ime_radnika = mb_convert_encoding($red['ime'], 'UTF-8', 'ISO-8859-2');

            //KONVERZIJA DATUMA U ZELJENI FORMAT
            $datum = date_create($red['datum_kreiranja']);
            $vreme_kreiranja = date_format($datum, 'Y-m-d H:i');

            //KONVERZIJA VREDNOSTI KOMPENZACIJE U ZELJENI FORMAT
            $vrednost_kompenzacije = number_format($red_iznos["iznos"], 2);

            //AKO JE DATUM SLANJA PDF-A NULL,POSTAVI VREDNOST NA "-".U SUPROTNOM UPISI VREDNOST IZ BAZE
            if (is_null($red['datum_slanja_pdfa'])) {
                
                $dugme_poslat_pdf = '-<br>';
                $dugme_poslat_pdf .= '<button class="btn btn-primary btn-xs poslat_pdf" id="' .$red["kompenzacija_zaglavlje_id"]. '">Odaberi datum</button>';
                //$datum_slanja_pdfa = '-';

            } else {
                
                $dugme_poslat_pdf = '<br>' .$red['datum_slanja_pdfa'];
            }

            //AKO JE DATUM VRACANJA PDF-A NULL,POSTAVI VREDNOST NA "-".U SUPROTNOM UPISI VREDNOST IZ BAZE
            if (is_null($red['datum_vracanja_pdfa'])) {
                
                if (!is_null($red['datum_slanja_pdfa'])) {

                    $dugme_vracen_pdf = '-<br>';
                    $dugme_vracen_pdf .= '<button class="btn btn-success btn-xs vracen_pdf" id="' .$red["kompenzacija_zaglavlje_id"]. '">Odaberi datum</button>';
                }

                else {

                    $dugme_vracen_pdf = '-';
                }
                
            } else {

                $dugme_vracen_pdf = '<br>' .$red['datum_vracanja_pdfa'];
            }

            //UPIT ZA SELEKTOVANJE IZ TABELE KOMPENZACIJA_STAVKE NA DUGOVNOJ STRANI,NA OSNOVU ID-JA KOMPENZACIJE
            $upit = "SELECT konto,brojdok,duguje FROM kompenzacija_stavke WHERE kompenzacija_zaglavlje_id = '".$red["kompenzacija_zaglavlje_id"]."' AND konto LIKE '4%' AND duguje > 0";

            //UPIT ZA SELEKTOVANJE IZ TABELE KOMPENZACIJA_STAVKE NA POTRAZNOJ STRANI,NA OSNOVU ID-JA KOMPENZACIJE
            $upit_potrazuje = "SELECT konto,brojdok,potrazuje FROM kompenzacija_stavke WHERE kompenzacija_zaglavlje_id = '".$red["kompenzacija_zaglavlje_id"]."' AND konto LIKE '2%' AND potrazuje > 0";


            $kolone_za_xls_niz_1 = array('brojdok', 'duguje');
            $kolone_za_xls_niz_2 = array('Broj dokumenta', 'Dugovanje');
            $kolone_za_xls = array();

            $kolone_za_xls_niz_3 = array('brojdok', 'potrazuje');
            $kolone_za_xls_niz_4 = array('Broj dokumenta', 'Potraživanje');
            $kolone_za_xls_duznik = array();

            //KREIRAJ NAZIV ZA KOLONE IZ BAZE KOJE SE PRIKAZUJU U XLS FAJLU
            for ($i = 0; $i < count($kolone_za_xls_niz_1); $i++){

                $kolone_za_xls[$kolone_za_xls_niz_1[$i]] = $kolone_za_xls_niz_2[$i];
                $kolone_za_xls_duznik[$kolone_za_xls_niz_3[$i]] = $kolone_za_xls_niz_4[$i];
            }
        
            //PREBACI IMENA KOLONA SA DUGOVNE STRANE U JSON FORMAT
            $kolone_za_xls = json_encode($kolone_za_xls);
        
            //PREBACI IMENA KOLONA SA POTRAZNE STRANE U JSON FORMAT
            $kolone_za_xls_duznik = json_encode($kolone_za_xls_duznik);
            
            //KREIRANJE FORME SA DETALJIMA KOJI SE PROSLEDJUJU FUNKCIJI ZA GENERISANJE XLS FAJLA
            $dugme = '<form id="forma" action="dodaci/funkcija_xls.php" method="POST">
                        <input type="hidden" id="sql_za_xls" name="sql_za_xls" value="'.$upit.'" />
                        <input type="hidden" id="upit_potrazuje" name="upit_potrazuje" value="'.$upit_potrazuje.'" />
                        <input type="hidden" id="baza_za_xls" name="baza_za_xls" value="amso"/>';
            $dugme .=  "<input type='hidden' id='kolone_za_xls' name='kolone_za_xls' value='$kolone_za_xls'/>";
            $dugme .=  "<input type='hidden' id='kolone_za_xls_duznik' name='kolone_za_xls_duznik' value='$kolone_za_xls_duznik'/>";
            $dugme .=  '<button class="btn btn-warning btn-xs test" type="submit" style="margin-top: 7px; width: 122.467px;">Kreiraj XLS</button>
                    </form>';
                    
            $dugme_pdf = '<form id="forma_pdf" action="ajax/kreiraj_pdf.php" method="POST"  target="_blank">
                            <input type="hidden" name="id_kompenzacije" value="' .$red["kompenzacija_zaglavlje_id"]. '" />
                            <button class="btn btn-primary btn-xs" type="submit">Kreiraj pdf</button>
                        </form>';
            /*
            //KREIRANJE BUTTONA ZA OTVARANJE DATEPICKERA            
            $dugme_poslat_pdf = '<button class="btn btn-primary btn-xs poslat_pdf" id="' .$red["kompenzacija_zaglavlje_id"]. '">Odaberi datum</button>';
            $dugme_vracen_pdf = '<button class="btn btn-success btn-xs vracen_pdf" id="' .$red["kompenzacija_zaglavlje_id"]. '">Odaberi datum</button>';
            */
            /*
            //UPIT ZA DOBIJANJE STATUSA PREDLOGA KOMPENZACIJE,TJ.POSLEDNJE PRIMENJENE AKCIJE NAD NJOM(ODOBRENO,PROKNJIZENO,STORNIRANO)
            $upit_status = "SELECT a.akcija,a.datum_promene_statusa,r.ime FROM kompenzacija_akcije_log a
                            INNER JOIN radnik r ON r.radnik = a.radnik
                            WHERE kompenzacija_id = ".$red['kompenzacija_zaglavlje_id']."  ORDER BY a.datum_promene_statusa DESC LIMIT 1";*/

            $upit_status = $with_set. "SELECT * FROM poslednji_podaci WHERE kompenzacija_id = ".$red['kompenzacija_zaglavlje_id']." ";
                          
            //IZVRSAVANJE UPITA
            $rezultat_status = pg_query($amso_konekcija, $upit_status);

            //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA I PREKINI IZVRSAVANJE SKRIPTE
            if (!$rezultat_status) {

                $html = 'Greška pri izvršavanju upita. Pokušajte ponovo.' .pg_last_error($amso_konekcija);
                echo json_encode($html);
                die();   
            }
            
            //AKO UPIT VRATI REZULTATE
            if (pg_num_rows($rezultat_status) > 0) {

                //DOK IMA REDOVA U TABELI,UPISI IH U NIZ
                while ($red_status = pg_fetch_array($rezultat_status)) {

                    //KONVERZIJA DATUMA U ZELJENI FORMAT
                    $datum_statusa = date_create($red_status['datum_promene_statusa']);
                    $datum_poslednje_akcije = date_format($datum_statusa, 'Y-m-d');

                    //AKO JE POSLEDNJA AKCIJA ZA ODREDJENU KOMPENZACIJU ODOBRENO
                    if ($red_status['poslednja_akcija'] == 'odobreno') {

                        //KONVERZIJA IMENA RADNIKA U UTF-8 FORMAT
                        $red_status['radnik_ime'] = mb_convert_encoding($red_status['radnik_ime'], 'UTF-8', 'ISO-8859-2');

                        //KREIRANJE PODATAKA ZA POLJA U TABELI
                        $radnik_odobrio = $red_status['radnik_ime'] .'<br>'. $datum_poslednje_akcije;
                        $radnik_proknjizio = '-';
                        $radnik_stornirao = '-';
                    }
                    //AKO JE POSLEDNJA AKCIJA ZA ODREDJENU KOMPENZACIJU STORNIRANO
                    elseif ($red_status['poslednja_akcija'] == 'stornirano') {

                        //KONVERZIJA IMENA RADNIKA U UTF-8 FORMAT
                        $red_status['radnik_ime'] = mb_convert_encoding($red_status['radnik_ime'], 'UTF-8', 'ISO-8859-2');
                        
                        //KREIRANJE PODATAKA ZA POLJA U TABELI
                        $radnik_stornirao = $red_status['radnik_ime'] .'<br>'. $datum_poslednje_akcije;
                        $radnik_proknjizio = '-';
                        $radnik_odobrio = '-';
                    }
                    //AKO JE POSLEDNJA AKCIJA ZA ODREDJENU KOMPENZACIJU PROKJNIZENO
                    elseif ($red_status['poslednja_akcija'] == 'proknjizeno') {

                        //KONVERZIJA IMENA RADNIKA U UTF-8 FORMAT
                        $red_status['radnik_ime'] = mb_convert_encoding($red_status['radnik_ime'], 'UTF-8', 'ISO-8859-2');
                        
                        //KREIRANJE PODATAKA ZA POLJA U TABELI
                        $radnik_proknjizio = $red_status['radnik_ime'] .'<br>'. $datum_poslednje_akcije;
                        $radnik_odobrio = '-';
                        $radnik_stornirao = '-';
                    }
                    //AKO JE POSLEDNJA AKCIJA ZA ODREDJENU KOMPENZACIJU KREIRANO I NEMA DRUGIH AKCIJA
                    else {

                        //KREIRANJE PODATAKA ZA POLJA U TABELI
                        $radnik_proknjizio = '-';
                        $radnik_odobrio = '-';
                        $radnik_stornirao = '-';
                    }
                    
                }
            }


            //UPISIVANJE PODATAKA U POLJA TABELE
            $html .= '<tr>';
            $html .= '<td>' .$red["kompenzacija_zaglavlje_id"]. '</td>';
            $html .= '<td>' .$red["sistemski_broj"]. '</td>';
            $html .= '<td width="5%">' .$red["broj_kompenzacije"]. '</td>';
            $html .= '<td>' .$red["datum_stanja"]. '</td>';
            $html .= '<td class="right">' .$vrednost_kompenzacije. '</td>';
            $html .= '<td width="15%">' .$ime_partnera. '</td>';
            $html .= '<td width="6%">'.$radnik_odobrio.'</td>';
            $html .= '<td width="6%">' .$radnik_proknjizio. '</td>';
            $html .= '<td width="6%">' .$radnik_stornirao. '</td>';
            $html .= '<td width="10%">' .$ime_radnika. '</td>';
            $html .= '<td>' 
                        .$dugme_poslat_pdf.
                    '</td>';
            $html .= '<td>' 
                        .$dugme_vracen_pdf.
                    '</td>';
            $html .= '<td>' .$vreme_kreiranja. '</td>';
            $html .= '<td class="posledna_celija">
                        <button class="btn btn-success btn-xs stavke" id="' .$red["kompenzacija_zaglavlje_id"]. '">Stavke</button>
                        '.$dugme_pdf.'
                        '.$dugme.'
                    </td>';

            $html .= '<td>
                        <button class="btn btn-success btn-xs odobri" id="' .$red["kompenzacija_zaglavlje_id"]. '">Odobri</button>
                    </td>';
            $html .= '<td>
                        <button class="btn btn-primary btn-xs proknjizi" id="' .$red["kompenzacija_zaglavlje_id"]. '">Proknjiži</button>
                    </td>';
            $html .= '<td>
                        <button class="btn btn-danger btn-xs storniraj" id="' .$red["kompenzacija_zaglavlje_id"]. '">Storniraj</button>
                    </td>';

            $html .= '</tr>';
        }

        //ZATVARANJE BODY DELA I TABELE
        $html .=  '</tbody>
                </table>';

        //DODAVANJE DIVA SA TEKSTOM
        $html .= '<br/>
        <div class="blue" align="center">
            Odaberite stranicu
        </div>';  


        //OTVARANJE DIVA ZA PAGINACIJU
        $html .= '<br/>
            <div align="center">'; 
    } 

    else {

        $niz_podaci['greska'] = 'Nema podataka za zadate kriterijume pretrage.';
    }



    //UPIT ZA PAGINACIJU,KADA NEMA FILTERA
    $upit_stranice = $with_set. "SELECT * FROM kompenzacija_podaci";

    //AKO POSTOJI PARAMETAR ZA PRETRAGU PO PIBU
    if($_POST['filteri']['pib'] != '') {

        //KONVERTOVANJE PIBA PARTNERA U STRING
        $pib_partnera = strval($_POST['filteri']['pib']);

        //KREIRANJE NOVOG UPITA ZA PAGINACIJU SA FILTERIMA I DODAVANJE USLOVA ZA PRETRAGU PO ZADATOM PIBU
        $upit_stranice = $with_set. "SELECT * FROM kompenzacija_podaci
                                    INNER JOIN poslednji_podaci ON kompenzacija_podaci.kompenzacija_zaglavlje_id = poslednji_podaci.kompenzacija_id
                                    WHERE partner = '$pib_partnera'";
    } 
  
    //AKO POSTOJI FILTER ZA PRETRAGU PO STATUSU KOMPENZACIJE
    if ($_POST['filteri']['status_knjizenja'] != '' && $_POST['filteri']['status_knjizenja'] != '-1') {

        $status_knjizenja = $_POST['filteri']['status_knjizenja'];

        //NASTAVLJANJE UPITA I DODAVANJE USLOVA
        $upit_stranice .= " AND poslednja_akcija = '$status_knjizenja'";
    }
      
      //AKO POSTOJI FILTER ZA PRIKAZ KOMPENZACIJA,ZA KOJE JE POSLAT PDF
      if ($_POST['filteri']['status_pdfa'] == 'poslat_pdf' && $_POST['filteri']['status_pdfa'] != '-1') {
  
          //NASTAVLJANJE UPITA I DODAVANJE USLOVA
          $upit_stranice .= " AND datum_slanja_pdfa IS NOT NULL";
      }
  
      //AKO POSTOJI FILTER ZA PRIKAZ KOMPENZACIJA,ZA KOJE JE VRACEN PDF
      if ($_POST['filteri']['status_pdfa'] == 'vracen_pdf' && $_POST['filteri']['status_pdfa'] != '-1') {
  
          //NASTAVLJANJE UPITA I DODAVANJE USLOVA
          $upit_stranice .= " AND datum_vracanja_pdfa IS NOT NULL";
      }
      
      //ZAVRSETAK UPITA ZA DOBIJANJE PODATAKA O PREDLOZIMA KOMPENZACIJE
      $upit_stranice .= " ORDER BY kompenzacija_zaglavlje_id DESC";


    //IZVRSAVANJE UPITA ZA PAGINACIJU
    $rezultat_stranice = pg_query($amso_konekcija, $upit_stranice); 

    //DOBIJANJE BROJA REDOVA I BROJA STRANICA,NA OSNOVU REZULTATA IZ BAZE
    $broj_redova = pg_num_rows($rezultat_stranice);  
    $broj_stranica = ceil($broj_redova / $kompenzacija_po_stranici);  
            
    //DOK IMA STRANICA,GENERISI PAGINATION LINKOVE ZA NJIH
    for ($i = 1; $i <= $broj_stranica; $i++) {  

        //AKO JE KLIKNUTO NA STRANICU,DODAJ LINKU KLASU ACTIVE(ZBOG CSS-A)
        if ($i == $stranica) {

            $html .= "<span class='pagination_link active' id='" .$i. "'>" .$i. "</span>"; 

        } else {
            
            $html .= "<span class='pagination_link' id='" .$i. "'>" .$i. "</span>";  
        }
    }  

    //ZATVARANJE DIVA ZA PAGINACIJU
    $html .= '</div>';

    $niz_podaci['html'] = $html;

    echo json_encode($niz_podaci);  
}
?>  