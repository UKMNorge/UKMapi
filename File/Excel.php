<?php
namespace UKMNorge\File;

use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once('UKM/Autoloader.php');

class Excel extends OfficeDok {
    var $row = [];
    var $sheet_ids = [];
    var $sheet_names = [];
    var $phpSpreadsheet;

    public function __construct( String $file_name )
    {
        parent::__construct($file_name);

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
	    $writer->save( $this->getPath() . $filename );
	    return $this->getUrl() . $filename;
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
}
if( defined('DOWNLOAD_PATH_EXCEL') ) {
    Excel::setPath( DOWNLOAD_PATH_EXCEL );
}
if( defined('DOWNLOAD_URL_EXCEL') ) {
    Excel::setUrl( DOWNLOAD_URL_EXCEL );
}