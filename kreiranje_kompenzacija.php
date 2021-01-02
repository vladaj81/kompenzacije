<?php
/*
session_start();

$root = $_SERVER ["DOCUMENT_ROOT"]; 
require_once "$root/common/no_cache.php";
require_once "$root/privilegije/privilegije.php";
require_once "$root/common/zabrane.php";
$sifra_u_nizu = array('001001055');
$sifra_provera= implode("','",$sifra_u_nizu);
zabrana_istekla_sesija($sifra_provera, $root);

if($_SESSION['radnik'])
{	
    $radnik=$_SESSION['radnik'];
}
else
{
    session_destroy();
    header("Location:/index.html");
    exit;
    
}
*/
$radnik = 151;

require_once('zakljucano.php');

//DOBIJANJE MINIMALNOG DATUMA ZA DATEPICKER(PRVOG SLOBODNOG DATUMA NAKON ZAKLJUCANOG PERIODA)
$min_datum = proveriZDatum('KVART', '1970-01-01');
?>
<html>
<head>
	<title>Kreiranje kompenzacija</title>
	<meta name="naslov" content="Forma_za_kompenzaciju">
    <meta http-equiv="Content-Type" content="text/html; charset=utf8">
    
    <!--UKLJUCIVANJE JQUERY UI CSS-A I CSS-A ZA KOMPENZACIJE-->
    <link rel="stylesheet" type="text/css" href="css/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="css/kompenzacije.css"/>
    
    <!--UKLJUCIVANJE POTREBNIH SKRIPTI-->
    <script type="text/javascript" language="javascript" src="js/jquery.js"></script>
    <script type="text/javascript" language="javascript" src="js/jquery.alphanum.js"></script>
	<script type="text/javascript" language="javascript" src="js/jquery-ui.js"></script>	
	<script type="text/javascript" language="javascript" src="js/jquery.ui.datepicker-sr-SR.js"></script>

    <script>

    //DEFINISANJE GLOBALNIH PROMENLJIVIH
    var datum;
    var sifra_partnera;

    //FUNKCIJA KOJA SADRZI DATEPICKER I AUTOCOMPLETE
    $(document).ready(function() {

        //DISABLE-OVANJE DUGMETA ZA PRIKAZ OTVORENIH STAVKI PARTNERA
        $('#prikazi_stavke').prop('disabled', true);         

        //DODAVANJE TOOLTIP-A ZA PRIKAZ DUGOVNIH I POTRAZNIH KONTA NA HOVER
        $('.tooltip_duguje').tooltip(); 
        $('.tooltip_potrazuje').tooltip();   
        
        //PREVENTIVA POTVRDE AKO KORISNIK PRITISNE ENTER
        $('#datum').on('keypress', function(e) {
            return e.which !== 13;
        });
        
        //DATEPICKER FUNKCIJA
        $('#datum').datepicker({

            showButtonPanel: false,
            minDate: '<?php echo $min_datum; ?>',
            maxDate: '0',
            dateFormat: 'yy-mm-dd'
        });

        //FUNKCIJA NA KLIK DUGMETA SA ID-JEM POSALJI DATUM
        $(document).on('click', '#posalji_datum', function() { 

            //RESETOVANJE LISTE PARTNERA
            $('#lista_partnera').empty();

            //SAKRIVANJE ODREDJENIH DIVOVA
            $('.first').hide();
            $('.second').hide();
            $('.centriraj_dugme').hide();
            $('#prikaz_podataka').hide();

            //AKO DATUM NIJE IZABRAN,OBAVESTI KORISNIKA
            if ($('#datum').val() == '') {

                alert('Niste odabrali datum.');
            }
            else {

                //UPIS ODABRANOG DATUMA U PROMENJIVU
                datum = $('#datum').val();

                //PRIKAZ DIVA SA PRELOADER-OM
                $('.loader_wrapper').show();

                //SLANJE AJAX POZIVA U FAJL ZA DOBIJANJE POTENCIJALNIH PARTNERA ZA KOMPENZACIJU
                $.ajax({

                    url: 'ajax/vrati_partnere.php',
                    method: 'POST',
                    dataType: 'json',

                    data: {datum:datum},

                    success: function(data) {

                        //SAKRIVANJE DIVA SA PRELOADER-OM
                        $('.loader_wrapper').hide();
                        
                        //console.log(data);

                        //POZIVANJE FUNKCIJE ZA GENERISANJE LISTE PARTNERA
                        generisi_listu_partnera(data);

                        if ($('#lista_partnera').val() !== null) {

                            //ENABLE-OVANJE DUGMETA ZA PRIKAZ OTVORENIH STAVKI
                            $("#prikazi_stavke").prop("disabled", false);
                        }  
                    }
                });
            }
        });

        //FUNKCIJA ZA GENERISANJE LISTE PARTNERA
        function generisi_listu_partnera(data) {

        var lista_partnera;

            //ZA SVAKOG PARTNERA IZ VRACENOG NIZA,GENERISI ITEM ZA SELECT LISTU 
            for (var i = 0; i < data.length; i++) {

                lista_partnera += "<option value='" + data[i].sifra+ "'>" + data[i].partner + " " + data[i].sifra +"</option>";
            }

            //DODAVANJE ITEM-A U SELECT LISTU
            $('#lista_partnera').html(lista_partnera);
        }
    });
    </script>

    <script>
    //FUNKCIJA ZA DINAMICKO GENERISANJE HTML-A ZA OBAVEZE I POTRAZIVANJA I PRIKAZ PREDLOGA ZA KOMPENZACIJU. PARAMETAR SU PODACI IZ GLAVNE KNJIGE U BAZI
    function generisiHtml(data) {

        //console.log(data);

        var brojac = 0;
        var brojac2 = 0;

        var duguje = "";
        var potrazuje = "";

        //ITERACIJA KROZ DOBIJENE PODATKE
        for (var i = 0; i < data.length; i++) {

            //AKO JE VREDNOST KOLONE DUGUJE U GLAVNOJ KNJIZI VECA OD NULE,GENERISI RED NA DUGOVNOJ STRANI
            if (data[i].konto[0] == 4 && data[i].iznos > 0 ) {

                var kanal_prodaje;
                
                //AKO JE POLJE U GLAVNOJ KNJIZI PRAZNO,POSTAVI KANAL PRODAJE NA 0. U SUPROTNOM DODELI VREDNOST IZ GLAVNE KNJIGE
                data[i].pib === null ?  kanal_prodaje = null : kanal_prodaje = data[i].pib;

                //DODAVANJE PREDLOGA ZA KOMPENZACIJU U HTML OUTPUT
                duguje += "<div class='rasporedi_inpute'>" +
                                "<div class='poravnaj prvi_item'>" +
                                    "<label class='donja_margina' for='duguje" + brojac + "'>Izaberi: </label>" +
                                    "<input class='cekiraj_duguje' id='duguje" + brojac + "' type='checkbox'>" +   
                                "</div>" +
                                
                                "<div class='poravnaj'>" +
                                    "<label class='donja_margina' for='konto_duguje" + brojac + "'>Konto: </label>" +
                                    "<input class='flex_item obaveze_input tooltip_duguje' id='konto_duguje" + brojac + "' type='text' value='" + data[i].konto + "' readonly title='" + data[i].naziv_konta + "'>" +
                                "</div>" +
                          
                                "<div class='poravnaj'>" +
                                    "<label class='donja_margina' for='broj_duguje" + brojac + "'>Broj dokum: </label>" +
                                    "<input class='flex_item' id='broj_duguje" + brojac + "' type='text' value='" + data[i].brojdok + "' readonly>" +
                                "</div>" +

                                "<div class='poravnaj'>" +
                                    "<label class='donja_margina' for='iznos_duguje" + brojac + "'>Iznos: </label>" +
                                    "<input class='flex_item iznos duguje' id='iznos_duguje" + brojac + "' type='text' value='" + data[i].iznos + "' max='" + data[i].iznos + "' min='1' disabled>" +
                                "</div>" +
                                
                                "<div class='poravnaj'>" +
                                    "<input class='flex_item' id='kanal_duguje" + brojac + "' type='number' value='" + kanal_prodaje + "' hidden>" +
                                "</div>" +
                            "</div>";

                brojac++;

            //AKO JE VREDNOST KOLONE POTRAZUJE U GLAVNOJ KNJIZI VECA OD NULE,GENERISI RED NA POTRAZNOJ STRANI    
            } else if (data[i].konto[0] == 2 && data[i].iznos > 0) {

                var kanal_prodaje;

                //AKO JE POLJE U GLAVNOJ KNJIZI PRAZNO,POSTAVI KANAL PRODAJE NA 0. U SUPROTNOM DODELI VREDNOST IZ GLAVNE KNJIGE
                data[i].pib === null ?  kanal_prodaje = null : kanal_prodaje = data[i].pib;

                //DODAVANJE PREDLOGA ZA KOMPENZACIJU U HTML OUTPUT
                potrazuje += "<div class='rasporedi_inpute'>" +
                                "<div class='poravnaj prvi_item'>" +
                                    "<label class='donja_margina' for='potrazuje" + brojac2 + "'>Izaberi: </label>" +
                                    "<input class='cekiraj_potrazuje' id='potrazuje" + brojac2+ "' type='checkbox'>" +   
                                "</div>" +
                                
                                "<div class='poravnaj'>" +
                                    "<label class='donja_margina' for='konto_potrazuje" + brojac2 + "'>Konto: </label>" +
                                    "<input class='flex_item obaveze_input tooltip_potrazuje' id='konto_potrazuje" + brojac2 + "' type='text' value='" + data[i].konto + "' readonly title='" + data[i].naziv_konta + "'>" +
                                "</div>" +
                        
                                "<div class='poravnaj'>" +
                                    "<label class='donja_margina' for='broj_potrazuje" + brojac2 + "'>Broj dokum: </label>" +
                                    "<input class='flex_item' id='broj_potrazuje" + brojac2 + "' type='text' value='" + data[i].brojdok + "' readonly>" +
                                "</div>" +

                                "<div class='poravnaj'>" +
                                    "<label class='donja_margina' for='iznos_potrazuje" + brojac2 + "'>Iznos: </label>" +
                                    "<input class='flex_item iznos potrazuje' id='iznos_potrazuje" + brojac2 + "' type='text' value='" + data[i].iznos + "' max='" + data[i].iznos + "' min='1' disabled>" +
                                "</div>" +

                                "<div class='poravnaj'>" +
                                    "<input class='flex_item' id='kanal_potrazuje" + brojac2 + "' type='number' value='" + kanal_prodaje + "' hidden>" +
                                "</div>" +
                            "</div>";

                brojac2++;
            } 
        }

        //DODAVANJE DIVA UKUPNO U DIV OBAVEZE
        duguje +=  "<div class='ukupno ukupno_prvi'>" +
                        "<span class='uporedi_duguje'></span>" +
                        "<label class='ukupno_label'>Ukupno:</label>" +
                        "<input class='ukupno_input suma_duguje' type='text' value='0' readonly>" +
                    "</div>";

        //DODAVANJE DIVA UKUPNO U DIV POTRAZIVANJA
        potrazuje +=  "<div class='ukupno ukupno_drugi'>" +
                        "<span class='uporedi_potrazuje'></span>" +
                        "<label class='ukupno_label'>Ukupno:</label>" +
                        "<input class='ukupno_input suma_potrazuje' type='text' value='0' readonly>" +
                    "</div>";

        //UPIS SVIH PREDLOGA ZA KOMPENZACIJU U DIV OBAVEZE
        $('.podaci_duguje').html(duguje);

        //UPIS SVIH PREDLOGA ZA KOMPENZACIJU U DIV POTRAZIVANJE
        $('.podaci_potrazuje').html(potrazuje);

        //console.log(data);
    }

    //DEKLARIZANJE NIZOVA ZA DUGOVNE I POTRAZNE PODATKE
    var niz_duguje = []; 
    var niz_potrazuje = []; 

    //FUNKCIJA ZA UPIS I PRIKAZ PODATAKA O PARTNERU 
    $(document).ready(function() {

        //RESETOVANJE POLJA FORME SA PODACIMA O PARTNERU
        $('#naziv').val("");
        $('#pib').val("");
        $('#adresa').val("");
        $('#grad').val("");
        $('#postanski_broj').val("");
        $('#telefon').val("");

        //FUNKCIJA NA KLIK DUGMETA PRIKAZI STAVKE
        $(document).on('click', '#prikazi_stavke', function() { 

            //SAKRIVANJE ODREDJENIH DIVOVA
            $('.first').hide();
            $('.second').hide();
            $('.centriraj_dugme').hide();
            $('#prikaz_podataka').hide();

            //PROVERA DA LI JE DATUM ODABRAN
            if ($('#datum').val() == '') 
            {  
                alert('Niste odabrali datum');  
            }  
            else if ($('#lista_partnera').val() === null)  
            {  
                alert('Niste odabrali partnera');  
            }  
            else  
            {   
                
                //PRIKAZ DIVA SA PRELOADER-OM
                $('.loader_wrapper').show();
                
                //UPIS SIFRE ODABRANOG PARTNERA U PROMENJIVU
                sifra_partnera = $('#lista_partnera').val();

                //SLANJE AJAX POZIVA ZA DOBIJANJE OSNOVNIH PODATAKA O PARTNERU(NAZIV,PIB,ADRESA,GRAD,POSTANSKI BROJ,TELEFON)
                $.ajax({  

                    url: 'ajax/ucitaj_partnera.php',  
                    method: 'POST', 
                    dataType: 'json', 
                    data: {sifra_partnera:sifra_partnera},

                    success: function(data){  
                        //console.log(data); 

                        sifra_partnera = data[1];

                        //UPIS PODATAKA ZA IZABRANOG PARTNERA.AKO JE POLJE U BAZI PRAZNO,ISPISI ---
                        if (data[0]) $('#naziv').val(data[0]); else $('#naziv').val('---');
                        if (data[1]) $('#pib').val(data[1]); else $('#pib').val('---');
                        if (data[2]) $('#adresa').val(data[2]); else $('#adresa').val('---');
                        if (data[3]) $('#grad').val(data[3]); else $('#grad').val('---');
                        if (data[4]) $('#postanski_broj').val(data[4]); else $('#postanski_broj').val('---');
                        if (data[5]) $('#telefon').val(data[5]); else $('#telefon').val('---');

                        //UPISIVANJE VISINE DIVOVA U PROMENLJIVE
                        $visina_prvog_diva = $(".first").height();
                        $visina_drugog_diva = $(".second").height();
        
                        //USLOV ZA IZJEDNACAVANJE VISINE DIVOVA: OBAVEZE I POTRAZIVANJA
                        if ($visina_prvog_diva > $visina_drugog_diva) {

                            $(".second").height($visina_prvog_diva);

                        } else {

                            $(".first").height($visina_drugog_diva);
                        }
                    }  
                });  
                
                //SLANJE AJAX POZIVA ZA DOBIJANJE PODATAKA O DUGOVNO-POTRAZNOM STANJU ZA IZABRANOG PARTNERA
                $.ajax({  

                    url: 'ajax/podaci_kompenzacija.php',  
                    method: 'POST', 
                    dataType: 'json', 
                    data: {datum:datum, sifra_partnera:sifra_partnera},

                    success: function(data){ 

                        //SAKRIVANJE DIVA SA PRELOADER-OM
                        $('.loader_wrapper').hide();

                        //console.log(data);

                        //AKO DODJE DO GRESKE U IZVRSAVANJU UPITA,OBAVESTI KORISNIKA
                        if (data.greska !== undefined) {

                            //SAKRIVANJE DIVA SA PRELOADER-OM
                            $('.loader_wrapper').hide();
                            
                            //SAKRIVANJE ZELJENIH DIVOVA
                            $('.first').hide();
                            $('.second').hide();
                            $('.centriraj_dugme').hide();
                            $('#prikaz_podataka').hide();
                            
                            alert(data.greska);
                        }

                        //AKO NEMA POTENCIJALNIH RACUNA ZA KOMPENZACIJU ZA ODABRANOG KLIJENTA,OBAVESTI KORISNIKA                    
                        if (data.obavestenje !== undefined) {

                            //SAKRIVANJE DIVA SA PRELOADER-OM
                            $('.loader_wrapper').hide();
                            
                            //SAKRIVANJE ZELJENIH DIVOVA
                            $('.first').hide();
                            $('.second').hide();
                            $('.centriraj_dugme').hide();
                            $('#prikaz_podataka').hide();
                            
                            alert(data.obavestenje);
                        }
                        //AKO JE SVE OK,PRIKAZI PODATKE
                        else {

                            //ONEMOGUCI SCROLLOVANJE STRANICE
                            $('html').css('overflow', 'hidden');

                            //POZIVANJE FUNKCIJE ZA GENERISANJE PREDLOGA ZA KOMPENZACIJU
                            generisiHtml(data);
                            
                            //PRIKAZ ZELJENIH DIVOVA
                            $('.first').show();
                            $('.second').show();
                            $('.centriraj_dugme').show();
                            $('#prikaz_podataka').show();

                            //RESETOVANJE NIZOVA DUGUJE I POTRAZUJE NA PRAZNO STANJE
                            niz_duguje = []; 
                            niz_potrazuje = [];   
                            
                            //console.log(data);
                        }
                    }  
                }); 
            } 
        });  
    });

    
    //FUNKCIJA ZA IZRACUNAVANJE SUME OBAVEZA I POTRAZIVANJA PREMA PARTNERU
    $(document).ready(function() {

        //INICIJALIZACIJA POTREBNIH PROMENLJIVIH
        var suma_duguje = 0;
        var suma_potrazuje = 0; 

        //FUNKCIJA ZA SABIRANJE I ODUZIMANJE CEKIRANIH IZNOSA NA STRANI DUGUJE
        $(document).on('click', '.cekiraj_duguje', function(){  

            //AKO JE SELEKTOVAN ODREDJENI PREDLOG ZA KOMPENZACIJU PUTEM CHECKBOXA
            if ($(this).is(':checked')) 
            { 
                //ENABLE-OVANJE DUGMETA ZA PRIPREMU KOMPENZACIJE
                $('#pripremi').prop('disabled', false);

                //SETOVANJE SUME NA NULU
                suma_duguje = 0;

                //ENABLE-OVANJE INPUTA U CEKIRANOM REDU NA OSNOVU ID-JA
                var id = $(this).attr('id');
                $("#iznos_" + id).prop('disabled', false);

                //UZIMANJE VREDNOSTI IZ ODGOVARAJUCIH POLJA
                var iznos = $("#iznos_" + id).val();
                var konto_duguje = $('#konto_' + id).val();
                var broj_duguje = $('#broj_' + id).val();
                var kanal_duguje = $('#kanal_' + id).val();

                //DODAVANJE PODATAKA IZ CEKIRANOG PREDLOGA ZA KOMPENZACIJU U NIZ(IZNOS,KONTO, BROJ DOKUMENTA, KANAL PRODAJE)
                niz_podaci_duguje = [id, iznos, konto_duguje, broj_duguje, kanal_duguje];
                
                //console.log(niz_podaci_duguje);
                
                //DODAVANJE SVIH NIZOVA SA PREDLOZIMA U NIZ ZA SLANJE
                niz_duguje.push(niz_podaci_duguje);
                //console.log(niz_duguje);

                //ZA SVAKI INPUT NA DUGOVNOJ STRANI
                $(".duguje").each(function() {  

                    //AKO JE INPUT ENABLE-OVAN
                    if (!$(this).prop("disabled")) {

                        var stari_iznos = Math.round($(this).val() * 100.0) / 100.0;

                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                        suma_duguje += Number($(this).val());
                        suma_duguje = Math.round(suma_duguje * 100.0) / 100.0;

                        //KREIRANJE PROMENJIVIH ZA VRACANJE NA STARU VREDNOST,U SLUCAJU POGRESNOG UNOSA
                        var prethodna_vrednost;
                        var novi_iznos;

                        //FUNKCIJA NA IZMENU VREDNOSTI INPUT POLJA
                        $(this).bind('input', function() {

                            //OGRANICAVANJE UNOSA INPUTA SAMO NA BROJEVE
                            $(this).numeric();
                                                        
                            //RESETOVANJE SUME NA NULU
                            suma_duguje = 0;
                            
                            //AKO SE DESI GRESKA PRILIKOM PRVOG UNOSA,VRATI VREDNOST PROMENJIVE NA POCETNU VREDNOST
                            if (prethodna_vrednost === undefined) {

                                prethodna_vrednost = $(this).attr('max');
                            } 
                            
                            var vrednost = $(this).val();
                            
                            //AKO UNETI BROJ NIJE U ISPRAVNOM FORMATU ILI JE PRVA CIFRA NULA,VRATI VREDNOST POLJA NA POSLEDNJU ISPRAVNU VREDNOST
                            if(vrednost.indexOf('0') == 0 || isNaN($(this).val())){

                                $(this).val(prethodna_vrednost);

                                //ZA SVAKI INPUT NA DUGOVNOJ STRANI
                                $(".duguje").each(function() { 

                                    //AKO JE INPUT ENABLE-OVAN
                                    if (!$(this).prop("disabled")) { 

                                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                                        suma_duguje += Number($(this).val());
                                        suma_duguje = Math.round(suma_duguje * 100.0) / 100.0;
                                        
                                    }
                                });
                            }
                                                    
                            //AKO JE UNETI IZNOS VECI OD IZNOSA SA RACUNA,OBAVESTI KORISNIKA I VRATI VREDNOST NA POCETNU
                            else if (Number($(this).val()) > $(this).attr('max')) {

                                alert('Prekoracili ste vrednost racuna');
                                
                                //VRATI VREDNOST POLJA NA POSLEDNJU ISPRAVNU VREDNOST
                                $(this).val(prethodna_vrednost);

                                //ZA SVAKI INPUT NA DUGOVNOJ STRANI
                                $(".duguje").each(function() { 

                                    //AKO JE INPUT ENABLE-OVAN
                                    if (!$(this).prop("disabled")) { 

                                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                                        suma_duguje += Number($(this).val());
                                        suma_duguje = Math.round(suma_duguje * 100.0) / 100.0;
                                        
                                    }
                                });
                            }

                            //AKO JE VREDNOST POLJA 0 ILI PRAZNO,OBAVESTI KORISNIKA O GRESCI I PROMENI VREDNOST NA 1
                            else if ($(this).val() <= 0)  {

                                //POSTAVI VREDNOST POLJA NA 1
                                $(this).val(1);
                                
                                //ZA SVAKI INPUT NA DUGOVNOJ STRANI
                                $(".duguje").each(function() { 

                                    //AKO JE INPUT ENABLE-OVAN
                                    if (!$(this).prop("disabled")) { 

                                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                                        suma_duguje += Number($(this).val());
                                        suma_duguje = Math.round(suma_duguje * 100.0) / 100.0;
                                        
                                    }
                                });

                                //UZIMANJE ID-JA PROMENJENOG INPUTA DA BI SMO AZURIRALI IZNOS U NIZU ZA SLANJE
                                var id = $(this).attr('id');
                                id = 'duguje' + id.substr(12);

                                //UZIMANJE NOVE VREDNOSTI IZ INPUT POLJA
                                novi_iznos = Math.round($(this).val() * 100.0) / 100.0;
                                //alert(novi_iznos);

                                //AKO NOVI IZNOS IMA RAZLICITU VREDNOST OD POCETNE VREDNOSTI POLJA
                                if (Math.round($(this).val() * 100.0) / 100.0 != stari_iznos) {

                                    //PROLAZAK KROZ NIZ ZA SLANJE I PRONALAZENJE ELEMENTA KOJI TREBA DA SE AZURIRA
                                    for (var i = 0; i < niz_duguje.length; i++) {
                                    
                                        //AKO SE BROJ REDA POKLAPA SA ID-JEM,AZURIRA SE IZNOS
                                        if (id === niz_duguje[i][0]) {

                                            niz_duguje[i][1] = novi_iznos;
                                        }
                                        //console.log(niz_duguje[i]);
                                    } 
                                }
                                
                                //UPISIVANJE SUME U POLJE UKUPNO
                                $('.suma_duguje').val(suma_duguje);
                            } 
                            
                            //AKO JE SVE U REDU,NASTAVI DALJE
                            else {

                                //UZIMANJE ID-JA PROMENJENOG INPUTA DA BI SMO AZURIRALI IZNOS U NIZU ZA SLANJE
                                var id = $(this).attr('id');
                                id = 'duguje' + id.substr(12);

                                //UZIMANJE NOVE VREDNOSTI IZ INPUT POLJA
                                novi_iznos = Math.round($(this).val() * 100.0) / 100.0;
                                //alert(novi_iznos);

                                //AKO NOVI IZNOS IMA RAZLICITU VREDNOST OD POCETNE VREDNOSTI POLJA
                                //if (Math.round($(this).val() * 100.0) / 100.0 != stari_iznos) {

                                    //PROLAZAK KROZ NIZ ZA SLANJE I PRONALAZENJE ELEMENTA KOJI TREBA DA SE AZURIRA
                                    for (var i = 0; i < niz_duguje.length; i++) {
                                    
                                        //AKO SE BROJ REDA POKLAPA SA ID-JEM,AZURIRA SE IZNOS
                                        if (id === niz_duguje[i][0]) {

                                            niz_duguje[i][1] = novi_iznos;
                                        }
                                        //console.log(niz_duguje[i]);
                                    } 
                                //}

                                //ZA SVAKI INPUT NA DUGOVNOJ STRANI
                                $(".duguje").each(function() { 

                                    //AKO JE INPUT ENABLE-OVAN
                                    if (!$(this).prop("disabled")) { 

                                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                                        suma_duguje += Number($(this).val());
                                        suma_duguje = Math.round(suma_duguje * 100.0) / 100.0;
                                        
                                    }
                                });
                                
                                //UPISIVANJE SUME U POLJE UKUPNO
                                $('.suma_duguje').val(suma_duguje);
                                //console.log(niz_duguje);
                            }

                            prethodna_vrednost = novi_iznos;
                            //console.log(prethodna_vrednost);
                            
                            //AKO JE DUGOVNA SUMA VECA OD POTRAZNE,OBAVESTI KORISNIKA
                            if (suma_duguje > suma_potrazuje) {
                                var razlika = 'Dugovna suma je veća od potražne za: ' + Math.round((suma_duguje - suma_potrazuje) * 100.0) / 100.0;
                                $('.uporedi_duguje').html(razlika);
                                $('.uporedi_potrazuje').html(razlika);
                            }

                            //AKO JE POTRAZNA SUMA VECA OD DUGOVNE,OBAVESTI KORISNIKA
                            if (suma_potrazuje > suma_duguje) {
                                var razlika = 'Potražna suma je veća od dugovne za: ' + Math.round((suma_potrazuje - suma_duguje) * 100.0) / 100.0;
                                $('.uporedi_duguje').html(razlika);
                                $('.uporedi_potrazuje').html(razlika);
                            }
                            
                            //AKO SU DUGOVNA I POTRAZNA SUMA JEDNAKE OBAVESTI KORISNIKA
                            if (suma_potrazuje == suma_duguje) {
                                var razlika = 'Iznosi su jednaki';
                                $('.uporedi_duguje').html(razlika);
                                $('.uporedi_potrazuje').html(razlika);
                            }
                        });
                    }
                });  
                //console.log(niz_duguje); 
            }
            
            //AKO JE SELEKTOVANI PREDLOG ODCEKIRAN
            else {

                //SETOVANJE SUME NA NULU
                suma_duguje = 0;
                
                //DISABLE-OVANJE INPUT POLJA,NA OSNOVU ID-JA ODCEKIRANOG CHECKBOXA
                var id = $(this).attr('id');
                $("#iznos_" + id).prop('disabled', true);

                //ZA SVAKI INPUT NA DUGOVNOJ STRANI
                $(".duguje").each(function() {  

                    //AKO JE INPUT ENABLE-OVAN
                    if (!$(this).prop("disabled")) {

                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                        suma_duguje += Number($(this).val());
                        suma_duguje = Math.round(suma_duguje * 100.0) / 100.0;

                    } else {
                        
                        //VRACANJE INPUT POLJA NA POCETNU VREDNOST
                        $(this).val($(this).attr('max'));
                    } 
                });

                //alert(suma_duguje);

                //ITERACIJA KROZ NIZ SA PREDLOZIMA ZA SLANJE
                for (var i = 0; i < niz_duguje.length; i++) {

                    //AKO JE ID ODCEKIRANOG CHECKBOXA ISTI KAO ID PREDLOGA,IZBRISI GA IZ NIZA SA PREDLOZIMA ZA SLANJE
                    if (id === niz_duguje[i][0]) {

                        niz_duguje.splice(i, 1);
                    }
                }
                
                //console.log(niz_duguje);
            }

            //UPISIVANJE SUME U POLJE UKUPNO
            $('.suma_duguje').val(suma_duguje);
            
            //AKO JE DUGOVNA SUMA VECA OD POTRAZNE,OBAVESTI KORISNIKA
            if (suma_duguje > suma_potrazuje) {
                var razlika = 'Dugovna suma je veća od potražne za: ' + Math.round((suma_duguje - suma_potrazuje) * 100.0) / 100.0;
                $('.uporedi_duguje').html(razlika);
                $('.uporedi_potrazuje').html(razlika);
            }

            //AKO JE POTRAZNA SUMA VECA OD DUGOVNE,OBAVESTI KORISNIKA
            if (suma_potrazuje > suma_duguje) {
                var razlika = 'Potražna suma je veća od dugovne za: ' + Math.round((suma_potrazuje - suma_duguje) * 100.0) / 100.0;
                $('.uporedi_duguje').html(razlika);
                $('.uporedi_potrazuje').html(razlika);
            }
            
            //AKO SU DUGOVNA I POTRAZNA SUMA JEDNAKE OBAVESTI KORISNIKA
            if (suma_potrazuje == suma_duguje) {
                var razlika = 'Iznosi su jednaki';
                $('.uporedi_duguje').html(razlika);
                $('.uporedi_potrazuje').html(razlika);
            }
        });


        //FUNKCIJA ZA SABIRANJE I ODUZIMANJE CEKIRANIH IZNOSA NA STRANI DUGUJE
        $(document).on('click', '.cekiraj_potrazuje', function(){  

            //AKO JE SELEKTOVAN ODREDJENI PREDLOG ZA KOMPENZACIJU PUTEM CHECKBOXA
            if ($(this).is(':checked')) 
            { 
                //ENABLE-OVANJE DUGMETA ZA PRIPREMU KOMPENZACIJE
                $('#pripremi').prop('disabled', false);

                //SETOVANJE SUME NA NULU
                suma_potrazuje = 0;

                //ENABLE-OVANJE INPUTA U CEKIRANOM REDU NA OSNOVU ID-JA
                var id = $(this).attr('id');
                $("#iznos_" + id).prop('disabled', false);

                //UZIMANJE VREDNOSTI IZ ODGOVARAJUCIH POLJA
                var iznos = $("#iznos_" + id).val();
                var konto_potrazuje = $('#konto_' + id).val();
                var broj_potrazuje = $('#broj_' + id).val();
                var kanal_potrazuje = $('#kanal_' + id).val();

                //DODAVANJE PODATAKA IZ CEKIRANOG PREDLOGA ZA KOMPENZACIJU U NIZ(IZNOS,KONTO, BROJ DOKUMENTA, KANAL PRODAJE)
                niz_podaci_potrazuje = [id, iznos, konto_potrazuje, broj_potrazuje, kanal_potrazuje];
                //console.log(niz_podaci_duguje);
                
                //DODAVANJE SVIH NIZOVA SA PREDLOZIMA U NIZ ZA SLANJE
                niz_potrazuje.push(niz_podaci_potrazuje);
                //console.log(niz_duguje);

                //ZA SVAKI INPUT NA DUGOVNOJ STRANI
                $(".potrazuje").each(function() {  

                    //AKO JE INPUT ENABLE-OVAN
                    if (!$(this).prop("disabled")) {

                        var stari_iznos = Math.round($(this).val() * 100.0) / 100.0;

                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                        suma_potrazuje += Number($(this).val());
                        suma_potrazuje = Math.round(suma_potrazuje * 100.0) / 100.0;

                        //KREIRANJE PROMENJIVIH ZA VRACANJE NA STARU VREDNOST,U SLUCAJU POGRESNOG UNOSA
                        var prethodna_vrednost;
                        var novi_iznos;

                        //FUNKCIJA NA IZMENU VREDNOSTI INPUT POLJA
                        $(this).bind('input', function() {

                            //OGRANICAVANJE UNOSA INPUTA SAMO NA BROJEVE
                            $(this).numeric();
                            
                            //RESETOVANJE SUME NA NULU
                            suma_potrazuje = 0;

                            //AKO SE DESI GRESKA PRILIKOM PRVOG UNOSA,VRATI VREDNOST PROMENJIVE NA POCETNU VREDNOST
                            if (prethodna_vrednost === undefined) {

                                prethodna_vrednost = $(this).attr('max');
                            } 

                            var vrednost = $(this).val();

                            //AKO UNETI BROJ NIJE U ISPRAVNOM FORMATU ILI JE PRVA CIFRA NULA,VRATI VREDNOST POLJA NA POSLEDNJU ISPRAVNU VREDNOST
                            if(vrednost.indexOf('0') == 0 || isNaN($(this).val())){
                              
                                $(this).val(prethodna_vrednost);

                                //ZA SVAKI INPUT NA POTRAZNOJ STRANI
                                $(".potrazuje").each(function() { 

                                    //AKO JE INPUT ENABLE-OVAN
                                    if (!$(this).prop("disabled")) { 

                                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                                        suma_potrazuje += Number($(this).val());
                                        suma_potrazuje = Math.round(suma_potrazuje * 100.0) / 100.0;
                                    }
                                });
                            }

                            //AKO JE UNETI IZNOS VECI OD IZNOSA SA RACUNA,OBAVESTI KORISNIKA I VRATI VREDNOST NA POCETNU
                            else if (Number($(this).val()) > $(this).attr('max')) {

                                alert('Prekoracili ste vrednost racuna');
                                
                                //VRATI VREDNOST POLJA NA POSLEDNJU ISPRAVNU VREDNOST
                                $(this).val(prethodna_vrednost);

                                //ZA SVAKI INPUT NA POTRAZNOJ STRANI
                                $(".potrazuje").each(function() { 

                                    //AKO JE INPUT ENABLE-OVAN
                                    if (!$(this).prop("disabled")) { 

                                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                                        suma_potrazuje += Number($(this).val());
                                        suma_potrazuje = Math.round(suma_potrazuje * 100.0) / 100.0;
                                    }
                                });
                            }

                            //AKO JE VREDNOST POLJA 0 ILI PRAZNO,OBAVESTI KORISNIKA O GRESCI I PROMENI VREDNOST NA 1
                            else if ($(this).val() <= 0)  {

                                //alert('Vrednost polja ne sme biti prazna ili nula.');

                                //POSTAVI VREDNOST POLJA NA 1
                                $(this).val(1);

                                 //ZA SVAKI INPUT NA DUGOVNOJ STRANI
                                 $(".potrazuje").each(function() { 

                                    //AKO JE INPUT ENABLE-OVAN
                                    if (!$(this).prop("disabled")) { 

                                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                                        suma_potrazuje += Number($(this).val());
                                        suma_potrazuje = Math.round(suma_potrazuje * 100.0) / 100.0;
                                        
                                    }
                                });

                                //UZIMANJE ID-JA PROMENJENOG INPUTA DA BI SMO AZURIRALI IZNOS U NIZU ZA SLANJE
                                var id = $(this).attr('id');
                                id = 'potrazuje' + id.substr(15);

                                //UZIMANJE NOVE VREDNOSTI IZ INPUT POLJA
                                novi_iznos = Math.round($(this).val() * 100.0) / 100.0;
                                //alert(novi_iznos);

                                //AKO NOVI IZNOS IMA RAZLICITU VREDNOST OD POCETNE VREDNOSTI POLJA
                                if (Math.round($(this).val() * 100.0) / 100.0 != stari_iznos) {

                                    //PROLAZAK KROZ NIZ ZA SLANJE I PRONALAZENJE ELEMENTA KOJI TREBA DA SE AZURIRA
                                    for (var i = 0; i < niz_potrazuje.length; i++) {
                                    
                                        //AKO SE BROJ REDA POKLAPA SA ID-JEM,AZURIRA SE IZNOS
                                        if (id === niz_potrazuje[i][0]) {

                                            niz_potrazuje[i][1] = novi_iznos;
                                        }
                                        //console.log(niz_duguje[i]);
                                    } 
                                }
                                //console.log(niz_potrazuje);

                                //UPISIVANJE SUME U POLJE UKUPNO
                                $('.suma_potrazuje').val(suma_potrazuje);
                            } 

                            //AKO JE SVE U REDU,NASTAVI DALJE
                            else {

                                //UZIMANJE ID-JA PROMENJENOG INPUTA DA BI SMO AZURIRALI IZNOS U NIZU ZA SLANJE
                                var id = $(this).attr('id');
                                id = 'potrazuje' + id.substr(15);
                              
                                //UZIMANJE NOVE VREDNOSTI IZ INPUT POLJA
                                novi_iznos = Math.round($(this).val() * 100.0) / 100.0;
                                //novi_iznos = $(this).val();
                                //alert(novi_iznos);

                                //AKO NOVI IZNOS IMA RAZLICITU VREDNOST OD POCETNE VREDNOSTI POLJA
                               // if (Math.round($(this).val() * 100.0) / 100.0 != stari_iznos) {

                                    //PROLAZAK KROZ NIZ ZA SLANJE I PRONALAZENJE ELEMENTA KOJI TREBA DA SE AZURIRA
                                    for (var i = 0; i < niz_potrazuje.length; i++) {
                                    
                                        //AKO SE BROJ REDA POKLAPA SA ID-JEM,AZURIRA SE IZNOS
                                        if (id === niz_potrazuje[i][0]) {

                                            niz_potrazuje[i][1] = novi_iznos;
                                        }
                                        //console.log(niz_duguje[i]);
                                    } 
                                //}

                                //ZA SVAKI INPUT NA POTRAZNOJ STRANI
                                $(".potrazuje").each(function() { 

                                    //AKO JE INPUT ENABLE-OVAN
                                    if (!$(this).prop("disabled")) { 

                                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                                        suma_potrazuje += Number($(this).val());
                                        suma_potrazuje = Math.round(suma_potrazuje * 100.0) / 100.0;
                                    }
                                });
                                
                                //UPISIVANJE SUME U POLJE UKUPNO
                                $('.suma_potrazuje').val(suma_potrazuje);
                                //console.log(niz_potrazuje);
                            }

                            prethodna_vrednost = novi_iznos;
                            //console.log(prethodna_vrednost);
                            
                            //AKO JE DUGOVNA SUMA VECA OD POTRAZNE,OBAVESTI KORISNIKA
                            if (suma_duguje > suma_potrazuje) {
                                var razlika = 'Dugovna suma je veća od potražne za: ' + Math.round((suma_duguje - suma_potrazuje) * 100.0) / 100.0;
                                $('.uporedi_duguje').html(razlika);
                                $('.uporedi_potrazuje').html(razlika);
                            }

                            //AKO JE POTRAZNA SUMA VECA OD DUGOVNE,OBAVESTI KORISNIKA
                            if (suma_potrazuje > suma_duguje) {
                                var razlika = 'Potražna suma je veća od dugovne za: ' + Math.round((suma_potrazuje - suma_duguje) * 100.0) / 100.0;
                                $('.uporedi_duguje').html(razlika);
                                $('.uporedi_potrazuje').html(razlika);
                            }
                            
                            //AKO SU DUGOVNA I POTRAZNA SUMA JEDNAKE OBAVESTI KORISNIKA
                            if (suma_potrazuje == suma_duguje) {
                                var razlika = 'Iznosi su jednaki';
                                $('.uporedi_duguje').html(razlika);
                                $('.uporedi_potrazuje').html(razlika);
                            }
                        });
                    }
                });  
                //console.log(niz_potrazuje); 
            }

            //AKO JE SELEKTOVANI PREDLOG ODCEKIRAN
            else {

                //SETOVANJE SUME NA NULU
                suma_potrazuje = 0;
                
                //DISABLE-OVANJE INPUT POLJA,NA OSNOVU ID-JA ODCEKIRANOG CHECKBOXA
                var id = $(this).attr('id');
                $("#iznos_" + id).prop('disabled', true);

                //ZA SVAKI INPUT NA DUGOVNOJ STRANI
                $(".potrazuje").each(function() {  

                    //AKO JE INPUT ENABLE-OVAN
                    if (!$(this).prop("disabled")) {

                        //DODAJ IZNOS IZ POLJA U SUMU I ZAOKRUZI NA 2 DECIMALE
                        suma_potrazuje += Number($(this).val());
                        suma_potrazuje = Math.round(suma_potrazuje * 100.0) / 100.0;
                    
                    } else {
                        
                        //VRACANJE INPUT POLJA NA POCETNU VREDNOST
                        $(this).val($(this).attr('max'));
                    } 
                });

                //alert(suma_duguje);

                //ITERACIJA KROZ NIZ SA PREDLOZIMA ZA SLANJE
                for (var i = 0; i < niz_potrazuje.length; i++) {

                    //AKO JE ID ODCEKIRANOG CHECKBOXA ISTI KAO ID PREDLOGA,IZBRISI GA IZ NIZA SA PREDLOZIMA ZA SLANJE
                    if (id === niz_potrazuje[i][0]) {

                        niz_potrazuje.splice(i, 1);
                    }
                }
                
                //console.log(niz_potrazuje);
            }

            //UPISIVANJE SUME U POLJE UKUPNO
            $('.suma_potrazuje').val(suma_potrazuje);

            
            //AKO JE DUGOVNA SUMA VECA OD POTRAZNE,OBAVESTI KORISNIKA
            if (suma_duguje > suma_potrazuje) {
                var razlika = 'Dugovna suma je veća od potražne za: ' + Math.round((suma_duguje - suma_potrazuje) * 100.0) / 100.0;
                $('.uporedi_duguje').html(razlika);
                $('.uporedi_potrazuje').html(razlika);
            }

            //AKO JE POTRAZNA SUMA VECA OD DUGOVNE,OBAVESTI KORISNIKA
            if (suma_potrazuje > suma_duguje) {
                var razlika = 'Potražna suma je veća od dugovne za: ' + Math.round((suma_potrazuje - suma_duguje) * 100.0) / 100.0;
                $('.uporedi_duguje').html(razlika);
                $('.uporedi_potrazuje').html(razlika);
            }
            
            //AKO SU DUGOVNA I POTRAZNA SUMA JEDNAKE OBAVESTI KORISNIKA
            if (suma_potrazuje == suma_duguje) {
                var razlika = 'Iznosi su jednaki';
                $('.uporedi_duguje').html(razlika);
                $('.uporedi_potrazuje').html(razlika);
            }
        });

        //FUNKCIJA NA CLICK DUGMETA PRIPREMI KOMPENZACIJU
        $('#pripremi').click(function() {

            //AKO JE SUMA NA DUGOVNOJ STRANI VECA,OBAVESTI KORISNIKA KOLIKA JE RAZLIKA
            if (suma_duguje > suma_potrazuje) {

                var razlika = suma_duguje - suma_potrazuje;

                alert('Umanjite iznos na dugovnoj strani za: ' + Math.round((suma_duguje - suma_potrazuje) * 100.0) / 100.0 + ' da izjednačite iznose.');
                return false;
            } 

            //AKO JE SUMA NA POTRAZNOJ STRANI VECA,OBAVESTI KORISNIKA KOLIKA JE RAZLIKA
            else if (suma_potrazuje > suma_duguje) {

                var razlika = suma_potrazuje - suma_duguje;

                alert('Umanjite iznos na potražnoj strani za: ' + Math.round((suma_potrazuje - suma_duguje) * 100.0) / 100.0 + ' da izjednačite iznose.');
                return false;
            }

            //AKO NIJE ODABRANA NIJEDNA STAVKA NA DUGOVNOJ I POTRAZNOJ STRANI
            else if (suma_potrazuje === 0 || suma_duguje === 0) {

                alert('Niste odabrali nijednu stavku.');
                return false;
            }

            //AKO SU IZNOSI NA OBE STRANE JEDNAKI
            else {

                //AKO JE POLJE SA BROJEM KOMPENZACIJE PRAZNO,OBAVESTI KORISNIKA.U SUPROTNOM UPISI VREDNOST U PROMENJIVU
                if ($('#broj_kompenzacije').val() == '') {
                    
                    alert('Unesite broj kompenzacije,ako želite da nastavite.');
                } 

                else {

                    var broj_kompenzacije = $('#broj_kompenzacije').val();

                     //UPISIVANJE IMENA RADNIKA IZ PHP SESIJE
                    var radnik = '<?php echo $radnik; ?>';

                    $.ajax({

                        url: 'ajax/pripremi_kompenzaciju.php',
                        method: 'POST',
                        dataType: 'json',

                        data: {datum:datum, radnik:radnik, niz_duguje:niz_duguje, niz_potrazuje:niz_potrazuje, sifra_partnera:sifra_partnera, broj_kompenzacije: broj_kompenzacije},

                        success: function(data) {

                            //RESETOVANJE POSLATIH NIZOVA NA PRAZNO STANJE
                            niz_duguje = []; 
                            niz_potrazuje = [];  

                            //RESETOVANJE DUGOVNE I POTRAZNE SUME
                            suma_duguje = 0;
                            suma_potrazuje = 0;

                            //RESETOVANJE POLJA SA BROJEM KOMPENZACIJE
                            $('#broj_kompenzacije').val('');

                            //RESETOVANJE POLJA SA SUMOM NA NULU
                            $('.suma_duguje').val('0');
                            $('.suma_potrazuje').val('0');

                            //SAKRIVANJE DIVOVA: PODACI O PARTNERU,DUGUJE,POTRAZUJE,PRIPREMI KOMPENZACIJU
                            $('.first').hide();
                            $('.second').hide();
                            $('#prikaz_podataka').hide();
                            $('.centriraj_dugme').hide();

                            //ODCEKIRANJE SVIH CHECKBOX-OVA
                            $('.cekiraj_duguje').prop('checked', false);
                            $('.cekiraj_potrazuje').prop('checked', false);

                            //DISABLE SVIH INPUT POLJA NA DUGOVNOJ STRANI
                            $(".duguje").each(function() {  

                                $(this).prop('disabled', true);
                            });

                            //DISABLE SVIH INPUT POLJA NA POTRAZNOJ STRANI
                            $(".potrazuje").each(function() {  

                                $(this).prop('disabled', true);
                            });

                            //DISABLE-OVANJE DUGMETA PRIPREMI KOMPENZACIJU
                            $('#pripremi').prop('disabled', true);

                            //console.log(data);
                            alert(data);
                        }
                    });
                }
            }
        });

    });
    </script>
</head>

<body>

    <div id="pretraga">
        <div id="container">

            <div id="okolog">
                <img src="images/icg/tb2_l.gif" alt="left-icon" class="levo" />
                <img src="images/icg/tb2_r.gif" alt="right-icon" class="desno" />
                <span id="natpis">Evidencija kompenzacija</span>
            </div>

            <!--DIV SA FORMAMA POCETAK-->
            <div id="content">
                <div id="trazi_podatke">
                    <!--POLJE SA DATEPICKEROM ZA IZBOR DATUMA-->
                    <label id="datum_label" for="datum">Datum:</label>
                    <input id="datum" autocomplete="off"/>
                    
                    <!--DUGME ZA POTVRDU SLANJA DATUMA-->
                    <button type="button" id="posalji_datum">Potvrdi unos</button>

                    <!--PADAJUCA LISTA ZA IZBOR PARTNERA-->
                    <label id="partneri_label" for="lista_partnera">Izaberi partnera:</label>
                    <select id="lista_partnera">

                    </select>

                    <!--DUGME ZA PRIKAZ OTVORENIH STAVKI I PODATAKA O PARTNERU-->
                    <button type="button" id="prikazi_stavke" disabled>Prikaži otvorene stavke</button>

                    <!--NOSECI DIV ZA PRELOADER I TEKST PRE UCITAVANJA PODATAKA POCETAK-->
                    <div class="loader_wrapper">
                        <span class="loader_span">Podaci se učitavaju</span>
                        <div class="loader"></div>
                    </div> 
                    <!--NOSECI DIV ZA PRELOADER I TEKST PRE UCITAVANJA PODATAKA KRAJ-->

                </div>
                <br/>

                <!--NOSECI DIV ZA DIVOVE PODACI O PARTNERU I WRAPPER-A OBAVEZE I POTRAZIVANJA POCETAK-->
                <div id="prikaz_podataka">
                    <h3 class="naslov">PODACI O PARTNERU</h3>
                    
                    <!--DIV SA PODACIMA O PARTNERU POCETAK-->
                    <div class="wrapper">
                        <div class="wrapper-inner">							
                            <label class="partner_label" for="naziv">Naziv partnera: </label>
                            <input class="podaci_partnera duze_polje" id="naziv" type="text"readonly>
                            
                            <label class="partner_label" for="pib">PIB/JMBG: </label>
                            <input class="podaci_partnera" id="pib" type="text" readonly>
                            
                            <label class="partner_label" for="adresa">Adresa: </label>
                            <input class="podaci_partnera duze_polje" id="adresa" type="text" readonly>
                        </div>
                    
                        <br>
                    
                        <div class="wrapper-inner">							
                            <label class="partner_label" for="grad">Grad: </label>
                            <input class="podaci_partnera duze_polje" id="grad" type="text" readonly>
                            
                            <label class="partner_label" for="postanski_broj">Poštanski broj: </label>
                            <input class="podaci_partnera" id="postanski_broj" type="text" readonly>
                            
                            <label class="partner_label" for="telefon">Telefon: </label>
                            <input class="podaci_partnera duze_polje" id="telefon" type="text" readonly>
                        </div>
                    </div>
                    <!--DIV SA PODACIMA O PARTNERU KRAJ-->
                    
                    <!--NOSECI DIV ZA DIVOVE OBAVEZE I POTRAZIVANJA POCETAK-->
                    <div class="wrapper duguje_potrazuje">

                        <!--DIV OBAVEZE KA PARTNERU POCETAK-->
                        <div class="wrapper-inner left first">
                               
                            <div class="fiksiraj_naslov">
                                <h3 class='naslov'>OBAVEZE KA PARTNERU</h3>
                            </div>
                            <div class="podaci_duguje">

                            </div>
                        </div>
                        <!--DIV OBAVEZE KA PARTNERU KRAJ-->
   
                        <!--DIV POTRAZIVANJA OD PARTNERA POCETAK-->
                        <div class="wrapper-inner left second">	

                            <div class="fiksiraj_naslov">
                                <h3 class='naslov'>POTRAŽIVANJA OD PARTNERA</h3>
                            </div>
                            <div class="podaci_potrazuje">

                            </div>

                        </div>
                        <!--DIV POTRAZIVANJA OD PARTNERA KRAJ-->

                    </div>
                    <!--NOSECI DIV ZA DIVOVE OBAVEZE I POTRAZIVANJA KRAJ-->
                </div>
                <!--NOSECI DIV ZA DIVOVE PODACI O PARTNERU I WRAPPER-A OBAVEZE I POTRAZIVANJA KRAJ-->

                <!--DIV SA DUGMETOM PRIPREMI KOMPENZACIJU POCETAK-->
                <div class="centriraj_dugme">
                    <label id="labela_broj" for="broj_kompenzacije">Broj kompenzacije</label>
                    <input type="text" id="broj_kompenzacije" placeholder="Unesite broj kompenzacije">
                    <button type="button" id="pripremi">Pripremi kompenzaciju</button>
                </div>
                <!--DIV SA DUGMETOM PRIPREMI KOMPENZACIJU KRAJ-->
        
            </div>
            <!--DIV SA FORMAMA KRAJ-->

            <div id="okolod" class="noprint">
                <img class="levo" alt="" src="images/icg/tb1_leftr.gif">
                <img class="desno" alt="" src="images/icg/tb1_r.gif">
            </div>

        </div>
    </div>

</body>
</html>
