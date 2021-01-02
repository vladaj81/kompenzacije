<?php

//AKO JE PROSLEDJEN ID PREDLOGA KOMPENZACIJE SA FRONTENDA
if (isset($_POST['id_kompenzacije'])) {

    //UKLJUCIVANJE KONEKCIJE KA AMSO BAZI
    require_once '../dodaci/konekcija_amso.php';

    //UPIT ZA SELEKTOVANJE STAVKI IZ TABELE KOMPENZACIJA_STAVKE NA OSNOVU ID-JA PREDLOGA
    $upit = "SELECT DISTINCT * FROM kompenzacija_stavke WHERE kompenzacija_zaglavlje_id = ".$_POST['id_kompenzacije']." ";

    //IZVRSAVANJE UPITA
    $rezultat = pg_query($amso_konekcija, $upit);

    //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA I PREKINI IZVRSAVANJE SKRIPTE
    if (!$rezultat) {

        echo json_encode('Greška pri izvršavanju upita. Pokušajte ponovo.').pg_last_error($amso_konekcija);
        die();
    }
    
    //UPIT NAD TABELAMA KOMPENZACIJA ZAGLAVLJE I PARTNERI ZA DOBIJANJE PODATAKA U ZAGLAVLJU KOD PREGLEDA STAVKI
    $upit_zaglavlje = "SELECT k.datum_stanja,k.partner,k.broj_kompenzacije,p.naziv,a.akcija FROM kompenzacija_zaglavlje k
                       INNER JOIN partneri p ON k.partner = p.sifra 
                       INNER JOIN kompenzacija_akcije_log a ON a.kompenzacija_id = k.kompenzacija_zaglavlje_id
                       WHERE kompenzacija_zaglavlje_id = ".$_POST['id_kompenzacije']."
                       ORDER BY datum_promene_statusa DESC LIMIT 1";


    //AKO UPIT ZA DOBIJANJE STAVKI VRATI REZULTATE,GENERISI HTML SA STAVKAMA ODABRANOG PREDLOGA
    if (pg_num_rows($rezultat) > 0) {

        //IZVRSI UPIT ZA DOBIJANJE PODATAKA ZA ZAGLAVLJE
        $rezultat_zaglavlje = pg_query($amso_konekcija, $upit_zaglavlje);

        //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA I PREKINI IZVRSAVANJE SKRIPTE    
        if (!$rezultat_zaglavlje) {

            echo json_encode($upit_zaglavlje);
            die();
        }

        //AKO UPIT VRATI REZULTATE
        if (pg_num_rows($rezultat_zaglavlje) > 0) {

            //UPISIVANJE DOBIJENOG REDA IZ BAZE U NIZ
            $red_zaglavlje = pg_fetch_array($rezultat_zaglavlje);

            //KONVERTOVANJE NAZIVA PARTNERA IZ BAZE U UTF8 FORMAT
            $naziv_partnera =  mb_convert_encoding($red_zaglavlje['naziv'], 'UTF-8', 'ISO-8859-2');  
        }

        //PROVERA DA LI JE KOMPENZACIJA PROKNJIZENA I UPISIVANJE STATUSA U PROMENJIVU
        $red_zaglavlje['akcija'] == 'proknjizeno' ? $proknjizeno = 'DA' : $proknjizeno = 'NE';
      
        //INICIJALIZACIJA PROMENJIVIH ZA SUMU
        $suma_duguje = 0;
        $suma_potrazuje = 0;

        //DODAVANJE PODATAKA O PARTNERU I KOMPENZACIJI U HTML OUTPUT
        $html .= '<div class="detalji">
                    <h3 class="kompenzacija no_margin">Kompenzacija broj: ' .$red_zaglavlje["broj_kompenzacije"]. '</h3>
                    <p class="kompenzacija no_margin">Naziv partnera: ' .$naziv_partnera. ' ' .$red_zaglavlje["partner"]. '</p>
                    <button class="btn btn-success kraj">Povratak na listu predloga</button>
                </div>';

        $html .= '<div>
                    <p class="paragraf_stavke"><span class="span_stavke">Datum stanja GK: </span>' .$red_zaglavlje["datum_stanja"]. '</p>
                    <p class="paragraf_stavke"><span class="span_stavke">Proknjiženo: </span>' .$proknjizeno. '</p>
                </div>';

        //DODAVANJE TABELE U HTML OUTPUT
        $html .= '<table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Broj predloga</th>
                            <th>Konto</th>
                            <th>Broj dokumenta</th>
                            <th>Duguje</th>
                            <th>Potražuje</th>
                            <th>Kanal prodaje</th>
                        </tr>
                    </thead>
                    <tbody>';

        //GENERISANJE REDOVA U TABELI ZA SVAKI RED IZ BAZE
        while ($red = pg_fetch_array($rezultat)) {

            $suma_duguje += $red['duguje'];
            $suma_potrazuje += $red['potrazuje'];

            //KONVERZIJA DUGOVNOG I POTRAZNOG IZNOSA U ZELJENI FORMAT
            $red["duguje"] = number_format($red["duguje"], 2);
            $red["potrazuje"] = number_format($red["potrazuje"], 2);
            
            $html .= '<tr>';
            $html .= '<td>' .$red["kompenzacija_zaglavlje_id"]. '</td>';
            $html .= '<td>' .$red["konto"]. '</td>';
            $html .= '<td>' .$red["brojdok"]. '</td>';
            $html .= '<td class="right">' .$red["duguje"]. '</td>';
            $html .= '<td class="right">' .$red["potrazuje"]. '</td>';
            $html .= '<td>' .$red["kanal_prodaje"]. '</td>';
            $html .= '</tr>';
        }

        //KONVERZIJA SUME U ZELJENI FORMAT
        $suma_duguje = number_format($suma_duguje, 2);
        $suma_potrazuje = number_format($suma_potrazuje, 2);

        $html .= '<tr style="background: #b3b3b3;">
                    <td colspan="3" align="center"><strong>UKUPNO</strong></td>
                    <td class="bold right">' .$suma_duguje. '</td>
                    <td class="bold right">' .$suma_potrazuje. '</td>
                    <td></td>
                </tr>';

        //ZATVARANJE BODY DELA I TABELE
        $html .=  '</tbody>
                </table>';
    } 
    
   echo json_encode($html);
}