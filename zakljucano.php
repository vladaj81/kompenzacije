<?php
/*
© Copyright 2015, 2016 SPL 61 d.o.o. Sva prava zadržana.
Verzija: 0.07
Status: RFC

Datum poslednje izmene: 25.01.2016

*/
/*
if (version_compare(phpversion(), '5.4.0', '<')) {
	if(session_id() == '') {
	session_start();
	}
}
else {
	if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}
}
if (isset($_SESSION['radnik']) && $_SESSION['radnik']) {
	$radnik = $_SESSION['radnik'];
	date_default_timezone_set ('Europe/Belgrade');
}
else {
	die('Na žalost, niste ulogovani.');
}
*/
function german2ISO ($dan) {
// Ako je datum u German formatu (DD.MM.YYYY) pretvori ga u ISO (YYYY-MM-DD), inače vrati ono što si dobio
	return preg_replace('/(^\d{2,2})\.(\d{2,2})\.(\d{4,4})/', '${3}' . '-' . '${2}' . '-' . '${1}', $dan);
}

function iso2German ($dan) {
// Ako je datum u ISO formatu (YYYY-MM-DD) pretvori ga u German (DD.MM.YYYY), inače vrati ono što si dobio
	return preg_replace('/(^\d{4,4})\-(\d{2,2})\-(\d{2,2})/', '${3}' . '-' . '${2}' . '-' . '${1}', $dan);
}

/* Funkcija prima tri parametra:
 * $vrsta - vrsta obrasca
 * $dan - dan na koji želim da proknjižim promenu, prihvatljivi su i ISO i German format (funkcija interno radi u German formatu)
 * $conn (opciono) - konekcija na SQL server, ako je prazno funkcija pravi i zatvara sopstvenu konekciju
 * Funkcija vraća datum na koji treba izvršiti knjiženje, u ISO formatu (YYYY-MM-DD)
 * AKO FUNCIJA VRATI PRAZAN STRING (''), to znači da se radi o pokušaju KNJIŽENJA U BUDUĆNOST!!! Što se, naravno, ne sme dozvoliti.
 * AKO FUNCIJA VRATI NULU (0), to znači da se radi o obrascu koji ne postoji!!! Ispravan odgovor bi bio da mu se vrati dostavljeni datum
 *  (što znači nema ograničenja), ali bismo tako otvorili mogućnost brljanja (npr. kreiran je novi obrasca a nismo ga dodali u
 *   tabelu vrste_obrazaca...).
 * U svakom slučaju, ako nam nije bitan razlog, uslov !$proveriZDatum (...) je dovoljan da prekinemo dalju obradu. A ako
 *  nas interesuje razlog, pogledati prethodna dva reda.
 * Obavezno je da kolona "oznaka" ne sadrži naša slova (ŠĐČĆŽ) zbog različitih enkodinga skriptova!!!
 */
function proveriZDatum ($vrsta, $dan, $conn = false) {
	$zatvori = $conn ? 0 : 1;
	$conn = $zatvori ? pg_connect('host=localhost dbname=amso user=zoranp') : $conn;
	
	$conn = pg_connect('host=localhost dbname=amso user=zoranp');
	$upit = "SELECT DISTINCT oznaka FROM parametri.vrste_obrazaca ORDER BY oznaka";
	$result = pg_query($conn, $upit);
	while ($arr = pg_fetch_row($result)) {
		$oznake[] = $arr[0];
	}
// Ako $vrsta obrasca ne postoji, vrati nulu što predstavlja grešku (i 0 i '' "nisu")
	if (!in_array($vrsta, $oznake)) {
		return 0;
	}
// Ako je datum u formatu YYYY-MM-DD pretvori ga u format DD.MM.YYYY
	$dan = iso2German($dan);
	$upit = "SELECT CASE WHEN extract(year FROM min(dana)) = extract(year FROM to_date('$dan', 'DD.MM.YYYY')) THEN max(dana) ELSE min(dana) END AS dana FROM ((SELECT extract(year FROM dana_od) AS godina, dana_od AS dana, dana_od, least((extract(year FROM dana_od) || '-12-31')::date, current_date) AS dana_do FROM parametri.vrste_obrazaca where oznaka = '$vrsta' AND dana_do isnull AND extract(year FROM dana_od) >= extract(year FROM to_date('$dan', 'DD.MM.YYYY')) ORDER BY dana_od LIMIT 1) UNION ALL (SELECT extract(year FROM dana_od) AS godina, greatest(dana_od, to_date('$dan', 'DD.MM.YYYY')) AS dana, dana_od, least((extract(year FROM dana_od) || '-12-31')::date, current_date) AS dana_do FROM parametri.vrste_obrazaca where oznaka = '$vrsta' AND dana_do isnull AND to_date('$dan', 'DD.MM.YYYY') BETWEEN dana_od AND least((extract(year FROM dana_od) || '-12-31')::date, current_date))) AS foo";
	$result = pg_query($conn, $upit);
	$arr = pg_fetch_assoc($result);
	if ($zatvori) {
		pg_close($conn);
	}
	return german2ISO ($arr['dana']);
}

/* Primeri pozivanja funkcije:

* Treba datum u ISO formatu,
$datum = proveriZDatum ($vrsta, $dan, $conn);
if (!$datum) {
	die('Ne može se knjižiti na datum u budućnosti.');
}
$glavna = 'g' . substr($datum, 0, 4);
...

* Treba datum u formatu German
$datum = iso2German(proveriZDatum ($vrsta, $dan));
if (!$datum) {
	die('Ne može se knjižiti na datum u budućnosti.');
}
$glavna = 'g' . substr($datum, -4);
...

*/
?>
