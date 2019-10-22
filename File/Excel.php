<?php
namespace UKMNorge\File;

use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once('UKM/Autoloader.php');

if( defined('DOWNLOAD_PATH_EXCEL') ) {
    Excel::setPath( DOWNLOAD_PATH_EXCEL );
}
if( defined('DOWNLOAD_URL_EXCEL') ) {
    Excel::setUrl( DOWNLOAD_URL_EXCEL );
}

class Excel {

    static $path;
    static $url;
    var $name;
    var $orientation = 'portrait';
    var $row = [];
    var $sheet_ids = [];
    var $sheet_names = [];
    var $phpSpreadsheet;

    public function __construct( $file_name )
    {

        if( !is_dir( static::$path ) ) {
            throw new Exception(
                'Kan ikke opprette excel-dokument, da systemet mangler mappen det skal lagres i. '.
                'Kontakt <a href="mailto:support@ukm.no?subject=UKMAPI%2FFile%2FExcel feil satt opp">support@ukm.no</a>',
                401002
            );
        }
        $this->name = $file_name;

        $this->phpSpreadsheet = new Spreadsheet( $file_name, $this->orientation);
        $this->phpSpreadsheet->getProperties()
            ->setCreator("UKM arrangørsystem")
            ->setLastModifiedBy("UKM arrangørsystem")
            ->setTitle($file_name)
            ->setSubject($file_name)
            ->setKeywords('UKM');
        
        Settings::setLocale('no');

        ## Sett standard-stil
        $this->phpSpreadsheet->getDefaultStyle()->getFont()->setName('Calibri');
        $this->phpSpreadsheet->getDefaultStyle()->getFont()->setSize(12);

        # Start alltid med første ark
        $this->phpSpreadsheet->setActiveSheetIndex(0);

        $this->row[0] = 0;
    }

    /**
     * Sett dokumentets retning
     * landskap eller portrett (default)
     * 
     * @param String $orientation
     * @return  self
     */ 
    public function setRetning($retning)
    {
        if( !in_array($retning, ['portrett','landskap'] ) ) {
            throw new Exception(
                'Excel-dokumenter støtter kun portrett eller landskap',
                401001
            );
        }
        $this->orientation = $retning == 'portrett' ? 'portrait' : 'landscape';

        return $this;
    }

    /**
     * Sett aktivt ark
     * Oppretter arket hvis det ikke finnes fra før
     *
     * @param String $id
     * @param String $navn
     * @return void
     */
    public function setArk( String $id, String $navn=null ) {
        if( !in_array( $id, $this->sheet_ids ) ) {
            $this->sheet_ids[] = $id;
            $this->sheet_names[] = $navn;
            
            if( sizeof( $this->sheet_ids ) == 1 ) {
                $sheet = $this->phpSpreadsheet->getActiveSheet();
            } else {
                $sheet = $this->phpSpreadsheet->createSheet();
            }
            if( $navn !== null ) {
                $sheet->setTitle( $navn );
            }
        }
        $this->phpSpreadsheet->setActiveSheetIndex( array_search($id, $this->sheet_ids) );
    }
    public function ark( String $id ) {
        $this->setArk( $id );
    }

    /**
     * Opprett en ny rad
     *
     * @param Int $rad_nummer
     * @return self
     */
    public function rad(Int $rad_nummer=null) {
        if( $rad_nummer !== null ) {
            $this->row[ $this->phpSpreadsheet->getActiveSheet() ] = $rad_nummer;
        } else {
            $this->row[ $this->phpSpreadsheet->getActiveSheetIndex() ]++;
        }
        return $this;
    }

    /**
     * Skriv data til en celle
     *
     * @param String $kolonne
     * @param String $data
     * @param Int $rad=null
     * @return String $kolonne
     */
    public function celle( String $kolonne, String $data, Int $rad=null) {
        if( $rad == null ) {
            $rad = $this->getRad();
        }
        $this->phpSpreadsheet->getActiveSheet()
            ->getCell($kolonne.$rad)
            ->setValue($data);
        return $kolonne;
    }

    /**
     * Hent aktiv rad
     *
     * @return void
     */
    public function getRad() {
        return $this->row[ $this->phpSpreadsheet->getActiveSheetIndex() ];
    }

    /**
     * Lagre excel-fil og returner URL for nedlasting
     *
     * @return void
     */
    public function writeToFile() {
        $filename = $this->name .'.xlsx';
	    $this->phpSpreadsheet->setActiveSheetIndex(0);
	    $writer = new Xlsx( $this->phpSpreadsheet );
	    $writer->save( $this->_getPath() . $filename );
	    return $this->_getUrl() . $filename;
    }

    /**
     * Hent mappen det skal skrives til
     *
     * @return void
     */
    private function _getPath() {
        return rtrim( static::$path, '/') .'/'. date('Y') .'/';
    }
    /**
     * Hent URL-base
     *
     * @return void
     */
    private function _getUrl() {
        return rtrim( static::$url, '/') .'/'. date('Y') .'/';
    }

    /**
     * Integer til alfabet-konvertering
     * Konverterer heltall til bokstav-representasjon
     * 
     * @param Int $nummer
     * @return String $bokstav
     */
    public static function i2a(Int $nummer) {
		return ($nummer-->26?chr(($nummer/26+25)%26+ord('A')):'').chr($nummer%26+ord('A'));
    }
    
    /**
     * Angi hvor på serveren filene skal lagres
     *
     * @param String $path
     * @return void
     */
    public static function setPath( String $path ) {
        static::$path = $path;
    }

    /**
     * Angi URL for nedlasting av filer
     *
     * @param String $url
     * @return void
     */
    public static function setUrl(String $url ) {
        static::$url = $url;
    }
}