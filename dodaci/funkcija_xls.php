<?php
/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 600);

date_default_timezone_set('Europe/Belgrade');

if (PHP_SAPI == 'cli')
	die('This example should only be run from a Web Browser');

/** Include PHPExcel */
require_once dirname(__FILE__) . '/Classes/PHPExcel.php';

// Ukoliko je uklju?en 'magic_quotes', onda poskidaj vi?ak karaktere iz dolaznih promenljivih 
if (get_magic_quotes_gpc()) 
{
	function undoMagicQuotes($array, $topLevel=true) 
	{
		$newArray = array();
		foreach($array as $key => $value) {
			if (!$topLevel) {
				$key = stripslashes($key);
			}
			if (is_array($value)) {
				$newArray[$key] = undoMagicQuotes($value, false);
			}
			else {
				$newArray[$key] = stripslashes($value);
			}
		}
		return $newArray;
	}
	$_GET = undoMagicQuotes($_GET);
	$_POST = undoMagicQuotes($_POST);
	$_COOKIE = undoMagicQuotes($_COOKIE);
	$_REQUEST = undoMagicQuotes($_REQUEST);
}

// Prihvati promenljive
$sql_za_xls = $_POST['sql_za_xls'];
$upit_potrazuje = $_POST['upit_potrazuje'];
$baza_za_xls = $_POST['baza_za_xls'];
$kolone_za_xls = json_decode($_POST['kolone_za_xls'],true);
$kolone_za_xls_duznik = json_decode($_POST['kolone_za_xls_duznik'],true);

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("AMS Osiguranje a.d.o.")
							 ->setLastModifiedBy("AMS Osiguranje a.d.o.")
							 ->setTitle("XLS")
							 ->setSubject(utf8_encode("XLS tabela"))
							 ->setDescription("Opis")
							 ->setKeywords("XLs")
							 ->setCategory("Test");

// Podesavanje fonta
$objPHPExcel->getDefaultStyle()->getFont()->setName('Times')->setSize(12)->setBold(false);


// $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory;
// $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;
$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;

PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

//PROMENA IMENA PRVOM STYLESHEETU
$objPHPExcel->getActiveSheet()->setTitle('AMSO');

//KREIRANJE NOVOG STYLESHEETA
$objWorkSheet = $objPHPExcel->createSheet(1);
$objWorkSheet->setTitle("PARTNER");

// Formatiranje

// Pode?avanje ?tampe

// Upit za dobijanje rezultata i konekcija
$conn = pg_connect("host=localhost dbname=$baza_za_xls user=zoranp");
if (!$conn) {
	echo "<br><br>Do?lo je do gre?ke prilikom konektovanja.\n";
	exit;
}
if(isset($_POST['sql_za_xls_temporary']))
{
    $sql_za_xls_temporary = $_POST['sql_za_xls_temporary'];
    $sql_za_xls_temporary1 = $_POST['sql_za_xls_temporary1'];

    if($sql_za_xls_temporary)
    {
        $rezultat_temporary= pg_query( $conn, $sql_za_xls_temporary );
        //$result = pg_query ( $conn, $sql_za_xls_temporary1 );
        //$podaci_sql=pg_fetch_all($result);
    }

}

$sql_encoding = "SET client_encoding TO 'UTF-8'";
$rezultat_encoding 	= pg_query($conn, $sql_encoding);

$rezultat = pg_query($conn, $sql_za_xls);
$niz_xls = pg_fetch_all($rezultat);
$broj_kolona = pg_num_fields($rezultat);

$rezultat_potrazuje = pg_query($conn, $upit_potrazuje);
$niz_xls2 = pg_fetch_all($rezultat_potrazuje);
$broj_kolona2 = pg_num_fields($rezultat);

//AKO NISU SETOVANA IMENA KOLONA ZA PRVI SHEET
if (!isset($kolone_za_xls))
{
	$kolone_za_xls = array();
	for ($i = 0; $i < $broj_kolona; $i++) 
	{
		$kolone_za_xls[pg_field_name($rezultat, $i)] = pg_field_name($rezultat, $i);
	}
}

//AKO SU SETOVANA IMENA KOLONA ZA PRVI SHEET
if ($kolone_za_xls)
{
	$kolone_za_xls_tipovi = array();
	for ($i = 0; $i < $broj_kolona; $i++)
	{
		$kolone_za_xls_tipovi[pg_field_name($rezultat, $i)] = pg_field_type($rezultat, $i);
	}
}


$broj_redova_svih = count($niz_xls);

//DOK IMA REDOVA VRACENIH IZ BAZE(GENERISI DUGOVNU STRANU)
for ($j = -1; $j < $broj_redova_svih; $j++) 
{
	
	if ($j==-1) 
	{
		$i = 0;

		//UNESI NASLOV ZA SVAKU KOLONU PRVOG SHEET-A
		foreach ($kolone_za_xls as $key => $value) 
		{
            $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow($i, $j+2, $value);
            $objPHPExcel->getActiveSheet()->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($i))->setAutoSize(true);
			$i++;
        }

	}
	else
	{
		$i = 0;

		//FORMATIRAJ VREDNOSTI ZA CELIJE PRVOG SHEET-A
		foreach ($kolone_za_xls as $key => $value)
		{

            $vrednost_za_celiju = $niz_xls[$j][$key];
        
            if ($kolone_za_xls_tipovi[$key] == 'date')
            {
                $kolona_i_red = PHPExcel_Cell::stringFromColumnIndex($i).($j+2);
                                $objPHPExcel->getActiveSheet()
                                ->getStyle($kolona_i_red)
                                ->getNumberFormat()
                                ->setFormatCode(
                                    PHPExcel_Style_NumberFormat::FORMAT_DATE_DOT
                                );
                $d2 = '=VALUE(DATEVALUE("'.$vrednost_za_celiju.'"))';
                $vrednost_za_celiju = $d2;
            }
            /*
			else if ($kolone_za_xls_tipovi[$key] == 'numeric')
            {
                $kolona_i_red = PHPExcel_Cell::stringFromColumnIndex($i).($j+2);
                $objPHPExcel->getActiveSheet()
                ->getStyle($kolona_i_red)
                ->getNumberFormat()
                ->setFormatCode(
                    PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2
                );
            }
            */
            else if (in_array($kolone_za_xls_tipovi[$key], array('numeric', 'float4', 'float8')))
            {
				$objPHPExcel->setActiveSheetIndex(0);   
				$kolona_i_red = PHPExcel_Cell::stringFromColumnIndex($i).($j+2);
				$objPHPExcel->getActiveSheet()
				->getStyle($kolona_i_red)
				->getNumberFormat()
				->setFormatCode(
					PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
				);
            }
            else if (in_array($kolone_za_xls_tipovi[$key], array('int4', 'int8', 'int2')))
            {   
				$objPHPExcel->setActiveSheetIndex(0);   
				$kolona_i_red = PHPExcel_Cell::stringFromColumnIndex($i).($j+2);
				$objPHPExcel->getActiveSheet()
				->getStyle($kolona_i_red)
				->getNumberFormat()
				->setFormatCode(
					PHPExcel_Style_NumberFormat::FORMAT_NUMBER
				);
            }
            else
            {
				$objPHPExcel->setActiveSheetIndex(0);   
				$kolona_i_red = PHPExcel_Cell::stringFromColumnIndex($i).($j+2);
				$objPHPExcel->getActiveSheet()
				->getStyle($kolona_i_red)
				->getNumberFormat()
				->setFormatCode(
					PHPExcel_Style_NumberFormat::FORMAT_TEXT
				);
            }
	
            //UPISI VREDNOSTI U CELIJE PRVOG SHEET-A
            $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValueByColumnAndRow($i, $j+2, $vrednost_za_celiju);
			$i++;
		}
		
		//PORAVNAJ SADRZAJ CELIJA
		$objPHPExcel->getActiveSheet()->getStyle('A1:'.PHPExcel_Cell::stringFromColumnIndex(count($kolone_za_xls)+1)."1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle('A1:'.PHPExcel_Cell::stringFromColumnIndex(count($kolone_za_xls)+1)."1")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $objPHPExcel->getActiveSheet()->getRowDimension($j+2)->setRowHeight(20);
		$objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);
		/*
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(false);
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
		$objPHPExcel->getActiveSheet()->getRowDimension('5')->setRowHeight(40);
		*/
		//KREIRAJ BORDERE
		$objPHPExcel->getActiveSheet()->getStyle('A1:'.PHPExcel_Cell::stringFromColumnIndex(count($kolone_za_xls)-1).(count($niz_xls)+1))->applyFromArray(
				array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)))
		);

		//OBOJ CELIJE
		$objPHPExcel->getActiveSheet()->getStyle('A1:'.PHPExcel_Cell::stringFromColumnIndex(count($kolone_za_xls)-1)."1")->applyFromArray(
				array('fill' 	=> array(
						'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
						'color'		=> array('argb' => 'FFCCFFCC')
						)
				)
		);
	}
}


//AKO NIJE SETOVAN NIZ SA IMENIMA KOLONA ZA DRUGI SHEET
if (!isset($kolone_za_xls_duznik))
{
	$kolone_za_xls_duznik = array();
	for ($i = 0; $i < $broj_kolona2; $i++) 
	{
		$kolone_za_xls_duznik[pg_field_name($rezultat_potrazuje, $i)] = pg_field_name($rezultat_potrazuje, $i);
	}
}

//AKO JE SETOVAN NIZ SA IMENIMA KOLONA ZA DRUGI SHEET
if ($kolone_za_xls_duznik)
{
	$kolone_za_xls_tipovi2 = array();
	for ($i = 0; $i < $broj_kolona2; $i++)
	{
		$kolone_za_xls_tipovi2[pg_field_name($rezultat_potrazuje, $i)] = pg_field_type($rezultat_potrazuje, $i);
	}
}

$broj_redova_svih2 = count($niz_xls2);

//DOK IMA REDOVA VRACENIH IZ BAZE(GENERISI POTRAZNU STRANU)
for ($j = -1; $j < $broj_redova_svih2; $j++) 
{

	if ($j==-1) 
	{
		$i = 0;
		
		//UNESI NASLOV ZA SVAKU KOLONU DRUGOG SHEET-A
        foreach ($kolone_za_xls_duznik as $key => $value) 
		{
            $objPHPExcel->setActiveSheetIndex(1)->setCellValueByColumnAndRow($i, $j+2, $value);
            $objPHPExcel->getActiveSheet()->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($i))->setAutoSize(true);
			$i++;
		}
	}
	else
	{
        $i = 0;

		//FORMATIRAJ VREDNOSTI ZA CELIJE DRUGOG SHEET-A
		foreach ($kolone_za_xls_duznik as $key => $value)
		{
			
			$vrednost_za_celiju = $niz_xls2[$j][$key];
			
			if ($kolone_za_xls_tipovi2[$key] == 'date')
			{
				$kolona_i_red = PHPExcel_Cell::stringFromColumnIndex($i).($j+2);
								$objPHPExcel->getActiveSheet()
								->getStyle($kolona_i_red)
								->getNumberFormat()
								->setFormatCode(
										PHPExcel_Style_NumberFormat::FORMAT_DATE_DOT
								);
				$d2 = '=VALUE(DATEVALUE("'.$vrednost_za_celiju.'"))';
				$vrednost_za_celiju = $d2;
			}
			/*
 			else if ($kolone_za_xls_tipovi[$key] == 'numeric')
 			{
 				$kolona_i_red = PHPExcel_Cell::stringFromColumnIndex($i).($j+2);
 				$objPHPExcel->getActiveSheet()
 				->getStyle($kolona_i_red)
 				->getNumberFormat()
 				->setFormatCode(
					PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2
 				);
			}
			*/
			else if (in_array($kolone_za_xls_tipovi2[$key], array('numeric', 'float4', 'float8')))
			{
				$objPHPExcel->setActiveSheetIndex(1);   
				$kolona_i_red = PHPExcel_Cell::stringFromColumnIndex($i).($j+2);
				$objPHPExcel->getActiveSheet()
				->getStyle($kolona_i_red)
				->getNumberFormat()
				->setFormatCode(
					PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
				);
			}
			else if (in_array($kolone_za_xls_tipovi2[$key], array('int4', 'int8', 'int2')))
			{   
				$objPHPExcel->setActiveSheetIndex(1);   
				$kolona_i_red = PHPExcel_Cell::stringFromColumnIndex($i).($j+2);
				$objPHPExcel->getActiveSheet()
				->getStyle($kolona_i_red)
				->getNumberFormat()
				->setFormatCode(
					PHPExcel_Style_NumberFormat::FORMAT_NUMBER
				);
			}
			else
			{
				$objPHPExcel->setActiveSheetIndex(1);   
				$kolona_i_red = PHPExcel_Cell::stringFromColumnIndex($i).($j+2);
				$objPHPExcel->getActiveSheet()
				->getStyle($kolona_i_red)
				->getNumberFormat()
				->setFormatCode(
					PHPExcel_Style_NumberFormat::FORMAT_TEXT
				);
			}

			//UPISI VREDNOSTI U CELIJE DRUGOG SHEET-A
			$objPHPExcel->setActiveSheetIndex(1)
			->setCellValueByColumnAndRow($i, $j+2, $vrednost_za_celiju);
			$i++;
        }

        //PORAVNAJ SADRZAJ CELIJA
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.PHPExcel_Cell::stringFromColumnIndex(count($kolone_za_xls_duznik)+1)."1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.PHPExcel_Cell::stringFromColumnIndex(count($kolone_za_xls_duznik)+1)."1")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

		$objPHPExcel->getActiveSheet()->getRowDimension($j+2)->setRowHeight(20);
		$objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);

        //KREIRAJ BORDERE
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.PHPExcel_Cell::stringFromColumnIndex(count($kolone_za_xls_duznik)-1).(count($niz_xls2)+1))->applyFromArray(
                array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)))
        );

		//OBOJ CELIJE
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.PHPExcel_Cell::stringFromColumnIndex(count($kolone_za_xls_duznik)-1)."1")->applyFromArray(
                array('fill' 	=> array(
                        'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
                        'color'		=> array('argb' => 'FFCCFFCC')
                        )
                )
        );
	}
}


//Dorada Nemanja Jovanovic 2020-01-14
if(isset($_POST['sumirani_iznos_broj_redova']))
{
	$broj_redova	= $_POST['sumirani_iznos_broj_redova'];
	$broj_redova	+= 2;
	$smestiD		= 'D'.$broj_redova;
	$broj_redova	-= 1;
	$opseg			= 'D'.$broj_redova;

	$objPHPExcel->getActiveSheet()->setCellValue($smestiD, '=SUM(D2:'.$opseg.')');
	$objPHPExcel->getActiveSheet()->getStyle($smestiD)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

	$broj_redova	= $_POST['sumirani_iznos_broj_redova'];
	$broj_redova	+= 2;
	$smestiF			= 'F'.$broj_redova;
	$broj_redova	-= 1;
	$opseg			= 'F'.$broj_redova;

	$objPHPExcel->getActiveSheet()->setCellValue($smestiF, '=SUM(F2:'.$opseg.')');
	$objPHPExcel->getActiveSheet()->getStyle($smestiF)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

	$broj_redova	= $_POST['sumirani_iznos_broj_redova'];
	$broj_redova	+= 2;
	$smestiO			= 'O'.$broj_redova;
	$broj_redova	-= 1;
	$opseg			= 'O'.$broj_redova;

	$objPHPExcel->getActiveSheet()->setCellValue($smestiO, '=SUM(O2:'.$opseg.')');
	$objPHPExcel->getActiveSheet()->getStyle($smestiO)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

	$broj_redova	= $_POST['sumirani_iznos_broj_redova'];
	$broj_redova	+= 2;
	$smestiP		= 'P'.$broj_redova;
	$broj_redova	-= 1;
	$opseg			= 'P'.$broj_redova;

	$objPHPExcel->getActiveSheet()->setCellValue($smestiP, '=SUM(P2:'.$opseg.')');
	$objPHPExcel->getActiveSheet()->getStyle($smestiP)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}
// kraj

if(isset($_POST['broj_redova']))
{

$broj_redova=$_POST['broj_redova'];
$broj_redova+=2;
$smesti='G'.$broj_redova;
$broj_redova-=1;
$opseg='G'.$broj_redova;


$objPHPExcel->getActiveSheet()
->setCellValue(
		$smesti,
		'=SUM(G2:'.$opseg.')'
);

}
if(isset($_POST['broj_redova_sintetika']))
{
	
	$broj_redova=$_POST['broj_redova_sintetika'];
	$broj_redova+=2;
	
	$smesti='B'.$broj_redova;
	$broj_redova-=1;
	$opseg='B'.$broj_redova;
	
	
	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(B2:'.$opseg.')'
	);	
}

if(isset($_POST['broj_redova_sintetika']))
{

	$broj_redova=$_POST['broj_redova_sintetika'];
	$broj_redova+=2;

	$smesti='C'.$broj_redova;
	$broj_redova-=1;
	$opseg='C'.$broj_redova;


	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(C2:'.$opseg.')'
	);
}

if(isset($_POST['broj_redova_sintetika']))
{

	$broj_redova=$_POST['broj_redova_sintetika'];
	$broj_redova+=2;

	$smesti='D'.$broj_redova;
	$broj_redova-=1;
	$opseg='D'.$broj_redova;


	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(D2:'.$opseg.')'
	);
}

if(isset($_POST['broj_redova_sintetika']))
{

	$broj_redova=$_POST['broj_redova_sintetika'];
	$broj_redova+=2;

	$smesti='E'.$broj_redova;
	$broj_redova-=1;
	$opseg='E'.$broj_redova;


	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(E2:'.$opseg.')'
	);
}

if(isset($_POST['broj_redova_sintetika']))
{

	$broj_redova=$_POST['broj_redova_sintetika'];
	$broj_redova+=2;

	$smesti='F'.$broj_redova;
	$broj_redova-=1;
	$opseg='F'.$broj_redova;


	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(F2:'.$opseg.')'
	);
}
if(isset($_POST['broj_redova_sintetika']))
{

	$broj_redova=$_POST['broj_redova_sintetika'];
	$broj_redova+=2;

	$smesti='A'.$broj_redova;
	$broj_redova-=1;
	$opseg='A'.$broj_redova;


	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'Ukupno rezultat'
	);
}

	

if(isset($_POST['broj_redova_resoiguranje']))
{

	$broj_redova=$_POST['broj_redova_resoiguranje'];
	$broj_redova+=2;
	$smesti='L'.$broj_redova;
	$broj_redova-=1;
	$opseg='L'.$broj_redova;


	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(L2:'.$opseg.')'
	);

}
if(isset($_POST['broj_redova_saosiguranje']))
{

	$broj_redova=$_POST['broj_redova_saosiguranje'];
	$broj_redova+=2;
	$smesti='J'.$broj_redova;
	$broj_redova-=1;
	$opseg='J'.$broj_redova;


	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(J2:'.$opseg.')'
	);
	
	$broj_redova=$_POST['broj_redova_saosiguranje'];
	$broj_redova+=2;
	$smesti='M'.$broj_redova;
	$broj_redova-=1;
	$opseg='M'.$broj_redova;
	
	
	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(M2:'.$opseg.')'
	);
	
	$broj_redova=$_POST['broj_redova_saosiguranje'];
	$broj_redova+=2;
	$smesti='N'.$broj_redova;
	$broj_redova-=1;
	$opseg='N'.$broj_redova;
	
	
	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(N2:'.$opseg.')'
	);
	
	
	$broj_redova=$_POST['broj_redova_saosiguranje'];
	$broj_redova+=2;
	$smesti='I'.$broj_redova;
	$broj_redova-=1;
	$opseg='I'.$broj_redova;
	
	
	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'UKUPNO'
	);
	

}

if(isset($_POST['broj_redova_pregled_steta']))
{

	$broj_redova=$_POST['broj_redova_pregled_steta'];
	$broj_redova+=2;
	$smesti='D'.$broj_redova;
	$broj_redova-=1;
	$opseg='D'.$broj_redova;


	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(D2:'.$opseg.')'
	);

	$broj_redova=$_POST['broj_redova_pregled_steta'];
	$broj_redova+=2;
	$smesti='E'.$broj_redova;
	$broj_redova-=1;
	$opseg='E'.$broj_redova;


	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(E2:'.$opseg.')'
	);

	$broj_redova=$_POST['broj_redova_pregled_steta'];
	$broj_redova+=2;
	$smesti='F'.$broj_redova;
	$broj_redova-=1;
	$opseg='F'.$broj_redova;


	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(F2:'.$opseg.')'
	);
	$broj_redova=$_POST['broj_redova_pregled_steta'];
	$broj_redova+=2;
	$smesti='G'.$broj_redova;
	$broj_redova-=1;
	$opseg='G'.$broj_redova;
	
	
	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(G2:'.$opseg.')'
	);
	$broj_redova=$_POST['broj_redova_pregled_steta'];
	$broj_redova+=2;
	$smesti='H'.$broj_redova;
	$broj_redova-=1;
	$opseg='H'.$broj_redova;
	
	
	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(H2:'.$opseg.')'
	);
	
	if($_POST['status']==2 || $_POST['status']==1){
	
		$uuisplaceno = $objPHPExcel->getActiveSheet()->getCell( 'H16' )->getCalculatedValue();
		$uukupno = $objPHPExcel->getActiveSheet()->getCell( 'G16' )->getCalculatedValue();
		
		$uuprosek=$uuisplaceno/$uukupno ;
		$uuprosek=round($uuprosek,2);
		$objPHPExcel->getActiveSheet()->setCellValue('J16',$uuprosek);
		
	 	$uulikvidirano = $objPHPExcel->getActiveSheet()->getCell( 'E16' )->getCalculatedValue();
		
	 	$uuprosek1=$uuisplaceno/$uulikvidirano;
	 	$uuprosek1=round($uuprosek1,2);
	 	$objPHPExcel->getActiveSheet()->setCellValue('I16',$uuprosek1);
	
	}
	else 
	{
		$uuisplaceno = $objPHPExcel->getActiveSheet()->getCell( 'H11' )->getCalculatedValue();
		$uukupno = $objPHPExcel->getActiveSheet()->getCell( 'G11' )->getCalculatedValue();
		
		$uuprosek=$uuisplaceno/$uukupno ;
		$uuprosek=round($uuprosek,2);
		$objPHPExcel->getActiveSheet()->setCellValue('J11',$uuprosek);
		
		$uulikvidirano = $objPHPExcel->getActiveSheet()->getCell( 'E11' )->getCalculatedValue();
		
		$uuprosek1=$uuisplaceno/$uulikvidirano;
		$uuprosek1=round($uuprosek1,2);
		$objPHPExcel->getActiveSheet()->setCellValue('I11',$uuprosek1);
		
	}
	

	$broj_redova=$_POST['broj_redova_pregled_steta'];
	$broj_redova+=2;
	$smesti='K'.$broj_redova;
	$broj_redova-=1;
	$opseg='K'.$broj_redova;
	
	
	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(K2:'.$opseg.')'
	);
	
	$broj_redova=$_POST['broj_redova_pregled_steta'];
	$broj_redova+=2;
	$smesti='L'.$broj_redova;
	$broj_redova-=1;
	$opseg='L'.$broj_redova;
	
	
	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(L2:'.$opseg.')'
	);
	$broj_redova=$_POST['broj_redova_pregled_steta'];
	$broj_redova+=2;
	$smesti='M'.$broj_redova;
	$broj_redova-=1;
	$opseg='M'.$broj_redova;
	
	
	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(M2:'.$opseg.')'
	);
	
	



}



if(isset($_POST['broj_redova_adekvatnost']))
{

	$broj_redova=$_POST['broj_redova_adekvatnost'];
	$broj_redova+=2;
	$smesti='F'.$broj_redova;
	$broj_redova-=1;
	$opseg='F'.$broj_redova;


	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti,
			'=SUM(F2:'.$opseg.')'
	);
	$broj_redova=$_POST['broj_redova_adekvatnost'];
	$broj_redova+=2;
	$smesti1='G'.$broj_redova;
	$broj_redova-=1;
	$opseg1='G'.$broj_redova;
	
	
	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti1,
			'=SUM(G2:'.$opseg1.')'
	);
	$broj_redova=$_POST['broj_redova_adekvatnost'];
	$broj_redova+=2;
	$smesti2='H'.$broj_redova;
	$broj_redova-=1;
	$opseg2='H'.$broj_redova;
	
	
	$objPHPExcel->getActiveSheet()
	->setCellValue(
			$smesti2,
			'=SUM(H2:'.$opseg2.')'
	);
	

}
// Rename worksheet
//$objPHPExcel->getActiveSheet()->setTitle('Strana1');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

// Redirect output to a client's web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="kompenzacija.xls"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
// header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
// header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
// header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
// header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

$objPHPExcel->disconnectWorksheets();
unset($objPHPExcel);

ini_restore('display_errors');
ini_restore('display_startup_errors');
ini_restore('memory_limit');
ini_restore('max_execution_time');

exit;
?>