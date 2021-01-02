<?php

/**********FUNKCIJA ZA GENERISANJE IZJAVE O KOMPENZACIJI U PDF-U*********

-PRVI PARAMETAR SU PODACI PARTNERA DOBIJENI UPITOM IZ TABELA KOMPENZACIJA_ZAGLAVLJE,KOMPENZACIJA_STAVKE I PARTNERI
-DRUGI PARAMETAR SU PODACI O STAVKAMA DOBIJENI UPITOM IZ TABELE KOMPENZACIJA_STAVKE

**********/
function generisi_pdf_za_kompenzacije($podaci_partnera, $rezultat_stavke) 
{
    
    require_once ('../pdf/tcpdf/config/lang/srp.php');
    require_once ('../pdf/tcpdf/tcpdf.php');

    //KREIRANJE PDF DOKUMENTA
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    //PODESAVANJE INFORMACIJA O DOKUMENTU
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('AMS Osiguranje');
    $pdf->SetTitle('IZJAVA O PREBIJANJU - KOMPENZACIJI BR.' .$podaci_partnera['broj_kompenzacije'], 'B');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    //PODESAVANJE DEFAULT MONOSPACE FONTA
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    //PODESAVANJE MARGINA
    $pdf->SetTopMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetLeftMargin(10);
    $pdf->SetRightMargin(10);

    //PODESAVANJE JOS NEKIH PARAMETARA
    $pdf->setPageOrientation('P','',0.0);
    $pdf->SetFontSubsetting(true);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetTextColor(0,0,0);

    //OPCIONA PODESAVANJA JEZIKA
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $pdf->setLanguageArray($l);
    }

    //DODAVANJE STRANICE I PODESAVANJE FONTA
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', '', 10);

    // set auto page breaks
    //$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    //KREIRANJE NASLOVA PDF DOKUMENTA
    $naslov_pdfa = 'IZJAVA O PREBIJANJU - KOMPENZACIJI BR.' .$podaci_partnera['broj_kompenzacije'];

    $pdf->Write(15, $naslov_pdfa, '', 0, 'C', true, 0, false, false, 0);


    //VARIJABLE SA PODACIMA O POVERIOCU
    $naziv_poverioca = 'AMS OSIGURANJE A.D.O';
    $adresa_poverioca = 'RUZVELTOVA 16';
    $mesto_poverioca = 'BEOGRAD';
    $pib_poverioca = 100000563;
    $tekuci_racun_poverioca = '205-113757-63';
    $telefon_poverioca = '011/3084-926';

    //ENKODIRANJE IMENA PARTNERA IZ BAZE U UTF-8 FORMAT
    $ime_partnera = mb_convert_encoding($podaci_partnera['naziv'], 'UTF-8', 'ISO-8859-2');
    
    //VARIJABLE SA PODACIMA O DUZNIKU
    $naziv_duznika = $ime_partnera;
    $podaci_partnera['adresa'] ? $adresa_duznika = $podaci_partnera['adresa'] : $adresa_duznika = '---';
    $podaci_partnera['mesto'] ? $mesto_duznika = $podaci_partnera['mesto'] : $mesto_duznika = '---';
    $podaci_partnera['partner'] ? $pib_duznika = $podaci_partnera['partner'] : $pib_duznika = '---';
    $podaci_partnera['tekracun'] ? $tekuci_racun_duznika = $podaci_partnera['tekracun'] : $tekuci_racun_duznika = '---';
    $podaci_partnera['telefon'] ? $telefon_duznika = $podaci_partnera['telefon'] : $telefon_duznika = '---';

    //ENKODIRANJE ADRESE PARTNERA IZ BAZE U UTF-8 FORMAT
    $adresa_partnera = mb_convert_encoding($adresa_duznika, 'UTF-8', 'ISO-8859-2');

    //KREIRANJE HTML TABELE SA PODACIMA O POVERIOCU I DUZNIKU
    $html .= '<table cellspacing="0" cellpadding="5" border="1">
            <tr>
                <th align="center"><strong>POVERILAC:</strong></th>
                <th align="center"><strong>DUŽNIK:</strong></th>
            </tr>
            <tr>
                <td><strong>Naziv:</strong> '.$naziv_poverioca.' </td>
                <td><strong>Naziv:</strong> '.$ime_partnera.' </td>
            </tr>
            <tr>
                <td><strong>Adresa:</strong> '.$adresa_poverioca.'</td>
                <td><strong>Adresa:</strong> '.$adresa_partnera.'</td>
            </tr>
            <tr>
                <td><strong>Mesto:</strong> '.$mesto_poverioca.' </td>
                <td><strong>Mesto:</strong> '.$mesto_duznika.' </td>
            </tr>
            <tr>
                <td><strong>PIB:</strong> '.$pib_poverioca.' </td>
                <td><strong>PIB:</strong> '.$pib_duznika.' </td>
            </tr>
            <tr>
                <td><strong>Tekući račun:</strong> '.$tekuci_racun_poverioca.' </td>
                <td><strong>Tekući račun:</strong> '.$tekuci_racun_duznika.' </td>
            </tr>
            <tr>
                <td><strong>Telefon:</strong> '.$telefon_poverioca.' </td>
                <td><strong>Telefon:</strong> '.$telefon_duznika.' </td>
            </tr>
        </table>';


    //KREIRANJE TABELE SA TEKSTOM: NA OSNOVU ZAKONA...
    $html .= '<table cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td>Na osnovu Zakona o obligacionim odnosima (Sl. list SRJ, br. 29/73, 39/85, 57/89, 31/93), dajemo izjavu da smo
                    saglasni za prebijanje-kompenzaciju sledećih međusobno dospelih novčanih potraživanja:</td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                </tr>
            </table>';


    //OTVARANJE TABELE SA STAVKAMA KOMPENZACIJE NA POTRAZNOJ STRANI I KREIRANJE ZAGLAVLJA
    $tabela_duguje = '<table cellspacing="0" cellpadding="5" border="1" style="height: 100%;">
                            <tr>
                                <th align="center"><strong>Poverilačke obaveze prema dužniku:</strong></th>
                            </tr>
                            <tr>
                                <td width="36%" align="center">Broj računa ili drugog dokumenta</td>
                                <td width="27%" align="center">Dan dospeća</td>
                                <td width="37%" align="center">Iznos</td>
                            </tr>';

    //OTVARANJE TABELE SA STAVKAMA KOMPENZACIJE NA DUGOVNOJ STRANI I KREIRANJE ZAGLAVLJA
    $tabela_potrazuje = '<table cellspacing="0" cellpadding="5" border="1" style="height: 100%;">
                        <tr>
                            <th align="center"><strong>Dužničke obaveze prema poveriocu:</strong></th>
                        </tr>
                        <tr>
                            <td width="36%" align="center">Broj računa ili drugog dokumenta</td>
                            <td width="27%" align="center">Dan dospeća</td>
                            <td width="37%" align="center">Iznos</td>
                        </tr>';
       
    //INICIJALIZACIJA SUME I BROJACA
    $suma_potrazuje = 0;  
    $suma_duguje = 0;    
    $brojac_potrazuje = 0;
    $brojac_duguje = 0;

    //ZA SVAKU STAVKU KOMPENZACIJE DOBIJENU IZ BAZE
    while ($stavke =  pg_fetch_array($rezultat_stavke)) {

        //AKO JE POTRAZNI IZNOS VECI OD NULE,DODAJ STAVKU U POTRAZNU TABELU I UVECAJ SUMU
        if ($stavke['potrazuje'] > 0) {

            $brojac_potrazuje++;

            $suma_potrazuje += $stavke['potrazuje'];

            $tabela_potrazuje .= '<tr>
                                    <td width="36%" height="35" align="center">' .$stavke['brojdok']. '</td>
                                    <td width="27%" height="35" align="center">---</td>
                                    <td width="37%" height="35" align="right">' .number_format($stavke['potrazuje'], 2). '</td>
                                </tr>';
        }

        //AKO JE DUGOVNI IZNOS VECI OD NULE,DODAJ STAVKU U DUGOVNU TABELU I UVECAJ SUMU
        if ($stavke['duguje'] > 0) {

            $brojac_duguje++;

            $suma_duguje += $stavke['duguje'];

            $tabela_duguje .= '<tr>
                                    <td width="36%" height="35" align="center">' .$stavke['brojdok']. '</td>
                                    <td width="27%" height="35" align="center">---</td>
                                    <td width="37%" height="35" align="right">' .number_format($stavke['duguje'], 2). '</td>
                                </tr>';
        }
  
    }     

    //AKO JE BROJ DUGOVNIH STAVKI VECI OD BROJA POTRAZNIH,IZRACUNAJ RAZLIKU
    if ($brojac_duguje > $brojac_potrazuje) {

        $razlika = $brojac_duguje - $brojac_potrazuje;

        //DODAJ PRAZNE REDOVE U POTRAZNU TABELU,DOK SE NE IZJEDNACI SA DUGOVNOM
        for ($i = 0; $i < $razlika; $i++) {

            $tabela_potrazuje .= '<tr>
                                    <td colspan="3" height="35"></td>
                                </tr>';
        }
    }

    //AKO JE BROJ POTRAZNIH STAVKI VECI OD BROJA DUGOVNIH,IZRACUNAJ RAZLIKU
    if ($brojac_potrazuje > $brojac_duguje) {

        $razlika = $brojac_potrazuje - $brojac_duguje;

        //DODAJ PRAZNE REDOVE U DUGOVNU TABELU,DOK SE NE IZJEDNACI SA POTRAZNOM
        for ($i = 0; $i < $razlika; $i++) {

            $tabela_duguje .= '<tr>
                                    <td colspan="3" height="35"></td>
                                </tr>';
        }
    }
    
    //PRETVARANJE SUME U ZELJENI FORMAT
    $suma_potrazuje = number_format($suma_potrazuje, 2);
    $suma_duguje = number_format($suma_duguje, 2);

    //PRIKAZI SUMU I ZATVORI POTRAZNU TABELU
    $tabela_potrazuje .= '<tr>
                            <td width="63%"><strong>UKUPNO:</strong></td>
                            <td width="37%" align="right"><strong>' .$suma_potrazuje. '</strong></td>
                        </tr>
                    </table>';

    //PRIKAZI SUMU I ZATVORI DUGOVNU TABELU
    $tabela_duguje .= '<tr>
                            <td width="63%"><strong>UKUPNO:</strong></td>
                            <td width="37%" align="right"><strong>' .$suma_duguje. '</strong></td>
                        </tr>
                    </table>';

    //DODAJ TABELE U HTML
    $html .= '<table >
                <tr>
                    <td>' .$tabela_duguje.' </td>
                    <td>' .$tabela_potrazuje. '</td>
                </tr>
            </table>';
    
            //AKO JE BROJ STAVKI SA BILO KOJE STRANE IZMEDJU 7 I 13, PREBACI OSTALI SADRZAJ NA NOVU STRANICU
            if (($brojac_potrazuje > 7 && $brojac_potrazuje < 12) || ($brojac_duguje > 7 && $brojac_potrazuje < 12)) {
                
                //UPIS HTML-A NA PRVU STRANICU
                $pdf->writeHTML($html, true, false, true, false, '');

                //DODAVANJE NOVE STRANICE
                $pdf->AddPage('P', 'A4');

                //KREIRANJE TABELE SA TEKSTOM: RAZLIKU (SALDO) OD... I DODAVANJE U HTML
                $html2 = '<table cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td>'.$brojac_duguje.'</td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Razliku (saldo) od ____________ / ___________ ( _____________________ / ______________________ ) dinara u NAŠU - VAŠU korist platiti u 
                                zakonskom roku, a najkasnije do ______ / ______ godine.</td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Dostavljaju se u 2 primerka, overene i potpisane izjave o kompenzaciji, s tim da se povratnom poštom 1 primerak vrati pošiljaocu 
                                radi sprovođenja odgovarajućih knjiženja</td>
                            </tr>
                        </table>';


                //KREIRANJE TABELE SA MESTIMA ZA PECAT I POTPISE I DODAVANJE U HTML
                $html2 .= '<table cellspacing="0" cellpadding="5" border="0">
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td width="35%" align="center">Odgovorno lice poverioca:</td>
                                <td width="15%" rowspan="3" align="center">M.P</td>
                                <td width="35%" align="center">Odgovorno lice dužnika:</td>
                                <td width="15%" rowspan="3" align="center">M.P</td>
                            </tr>
                            <tr>
                                <td width="35%" align="center">_________________________<br>potpis</td>
                                <td width="35%" align="center">_________________________<br>potpis</td>
                            </tr>
                            <tr>
                                <td width="35%" align="center">Datum: ___________________</td>
                                <td width="35%" align="center">Datum: ___________________</td>
                            </tr>
                        </table>';          

                //UPIS HTML-A NA DRUGU STRANICU
                $pdf->writeHTML($html2, true, false, true, false, '');

            } 

            //AKO BROJ STAVKI SA BILO KOJE STRANE MANJI OD 6,ILI VECI OD 13
            else {

                //KREIRANJE TABELE SA TEKSTOM: RAZLIKU (SALDO) OD... I DODAVANJE U HTML
                $html .= '<table cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Razliku (saldo) od ____________ / ___________ ( _____________________ / ______________________ ) dinara u NAŠU - VAŠU korist platiti u 
                                zakonskom roku, a najkasnije do ______ / ______ godine.</td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Dostavljaju se u 2 primerka, overene i potpisane izjave o kompenzaciji, s tim da se povratnom poštom 1 primerak vrati pošiljaocu 
                                radi sprovođenja odgovarajućih knjiženja</td>
                            </tr>
                        </table>';


                //KREIRANJE TABELE SA MESTIMA ZA PECAT I POTPISE I DODAVANJE U HTML
                $html .= '<table cellspacing="0" cellpadding="5" border="0">
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td width="35%" align="center">Odgovorno lice poverioca:<br><br></td>
                                <td width="15%" rowspan="3" align="center">M.P</td>
                                <td width="35%" align="center">Odgovorno lice dužnika:</td>
                                <td width="15%" rowspan="3" align="center">M.P</td>
                            </tr>
                            <tr>
                                <td width="35%" align="center">_________________________<br>potpis</td>
                                <td width="35%" align="center">_________________________<br>potpis</td>
                            </tr>
                            <tr>
                                <td width="35%" align="center">Datum: ___________________</td>
                                <td width="35%" align="center">Datum: ___________________</td>
                            </tr>
                        </table>';          

                //UPIS HTML-A NA PRVU STRANICU
                $pdf->writeHTML($html, true, false, true, false, '');
            }
    
    //OUTPUT PDF DOKUMENTA
    return $pdf->Output('izjava o kompenzaciji.pdf', 'I');
}

//AKO JE PROSLEDJEN ID PREDLOGA KOMPENZACIJE SA FRONTENDA
if (isset($_POST['id_kompenzacije'])) {

    //UKLJUCIVANJE KONEKCIJE KA AMSO BAZI
    require_once '../dodaci/konekcija_amso.php';

    //UPIT ZA SELEKTOVANJE PODATAKA O PARTNERU IZ TABELA KOMPENZACIJA_ZAGLAVLJE I PARTNERI NA OSNOVU ID-JA PREDLOGA
    $upit = "SELECT DISTINCT k.kompenzacija_zaglavlje_id,k.partner,k.broj_kompenzacije,p.naziv,p.adresa,p.mesto,p.tekracun,p.telefon FROM kompenzacija_zaglavlje k
            INNER JOIN partneri p ON k.partner = p.sifra
            WHERE k.kompenzacija_zaglavlje_id = ".$_POST['id_kompenzacije']." ";

    //IZVRSAVANJE UPITA
    $rezultat = pg_query($amso_konekcija, $upit);

    //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA I PREKINI IZVRSAVANJE SKRIPTE
    if (!$rezultat) {

        $html = 'Greška pri izvršavanju upita. Pokušajte ponovo.';
        echo json_encode($html);
        die();
    }
    
    //AKO UPIT VRATI REZULTAT
    if (pg_num_rows($rezultat) > 0) {

        //UPIS PODATAKA O PARTNERU U NIZ
        $podaci_partnera = pg_fetch_array($rezultat);

        //UPIT ZA DOBIJANJE DETALJA O STAVKAMA ZA ODABRANU KOMPENZACIJU
        $upit_stavke = "SELECT DISTINCT brojdok,duguje,potrazuje FROM kompenzacija_stavke WHERE kompenzacija_zaglavlje_id = '".$_POST['id_kompenzacije']."' ORDER BY duguje,potrazuje";

        //IZVRSAVANJE UPITA
        $rezultat_stavke = pg_query($amso_konekcija, $upit_stavke);

        //AKO DODJE DO GRESKE PRI IZVRSAVANJU UPITA,OBAVESTI KORISNIKA I PREKINI IZVRSAVANJE SKRIPTE
        if (!$rezultat_stavke) {

            $html = 'Greška pri izvršavanju upita. Pokušajte ponovo.';
            echo json_encode($html);
            die();
        }

        //AKO UPIT VRATI REZULTAT
        if (pg_num_rows($rezultat_stavke) > 0) {

            //POZIVANJE FUNKCIJE ZA GENERISANJE PDF-A I PROSLEDJIVANJE PODATAKA O PARTNERU I STAVKAMA KOMPENZACIJE
            generisi_pdf_za_kompenzacije($podaci_partnera, $rezultat_stavke);

        }
    } 
}