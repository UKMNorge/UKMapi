<?php
namespace UKMNorge\File;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once('UKM/Autoloader.php');
require_once('UKM/vendor/autoload.php');

class Excel extends OfficeDok {
    const COLORS = [
        '00004c',
        'ff128b',
        'fff056',
        '00ff89',
        '235da9',
        '72f379',
        'f29d73'
    ];
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
        $sheet = $this->phpSpreadsheet->setActiveSheetIndex( array_search($id, $this->sheet_ids) );
        if( isset( static::COLORS[ array_search($id, $this->sheet_ids) ] ) ) {
            $sheet->getTabColor()->setRGB( static::COLORS[ array_search($id, $this->sheet_ids) ] );
        }
    }

    /**
     * @inheritdoc 
     */
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
    public function celle( String|null $kolonne, String|null $data, Int $rad=null) {
        $kolonne = $kolonne != null ? $kolonne : '';
        $data = $data != null ? $data : '';
        if( $rad == null ) {
            $rad = $this->getRad();
        }
        $this->phpSpreadsheet->getActiveSheet()
            ->getCell($kolonne.$rad)
            ->setValue($data);
        return $kolonne;
    }

    /**
     * Angi fet skrift for en celle
     *
     * @param String A1-style refereanse
     * @return self
     */
    public function fet( String $cell_ref ) {
        $this->phpSpreadsheet
            ->getActiveSheet()
            ->getStyle($cell_ref)
            ->applyFromArray(
                [
                    'font' => [
                        'bold' => true,
                    ]
                ]
            );
        return $this;
    }

    /**
     * Hent aktiv rad
     *
     * @return Int
     */
    public function getRad() {
        return $this->row[ $this->phpSpreadsheet->getActiveSheetIndex() ];
    }

    /**
     * Lagre excel-fil og returner URL for nedlasting
     *
     * @return String
     */
    public function writeToFile() {
        $filename = OfficeDok::sanitizeFilename($this->name .'.xlsx');
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

    /**
     * Åpen eksisterende excel-fil
     *
     * @param String $filename
     * @return Spreadsheet
     */
    public static function readFile( String $filename ) {
        return IOFactory::load($filename);
    }
}
if( defined('DOWNLOAD_PATH_EXCEL') ) {
    Excel::setPath( DOWNLOAD_PATH_EXCEL );
}
if( defined('DOWNLOAD_URL_EXCEL') ) {
    Excel::setUrl( DOWNLOAD_URL_EXCEL );
}