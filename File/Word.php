<?php

namespace UKMNorge\File;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

require_once('UKM/Autoloader.php');

class Word extends OfficeDok
{

    const DEFAULT_FONT_SIZE = 12;
    const DEFAULT_FONT_SIZE_INCREMENT = 4;

    static $paperWidth = 210; // mm
    static $paperHeight = 297; // mm
    static $documentWidth; // mm, defined by setMargins
    static $documentHeight; // mm, defined by setMargins

    /**
     * @var PhpWord
     */
    var $phpWord;
    var $sections;
    var $current_section;

    public function __construct(String $file_name)#, String $rapport_navn)
    {
        parent::__construct($file_name);

        $this->phpWord = new PhpWord();
        
        $this->phpWord->getDocInfo()
        ->setCreator('UKM Norge')
        ->setCompany('UKM Norges arrangørsystem')
        #->setTitle('UKM-rapport :: ' . $rapport_navn)
        ->setLastModifiedBy('UKM Norges arrangørsystem')
        ->setCreated(time())
        ->setModified(time());
        #$this->setRetning('portrett');
        $this->setDefaultLanguage();
        $this->setMargins();
        $this->defineHeaderStyles();
        $this->addSection();
    }

    /**
     * Returner millimeter i twips
     *
     * @param Int $millimetres
     * @return Float $twips
     */
    public static function mmToTwips(Int $millimetres)
    {
        return $millimetres * 56.692913385827;
    }

    /**
     * Returner points i twips
     *
     * @param Int $points
     * @return Float $twips
     */
    public static function ptToTwips(Int $points ) {
        return $points * 20;
    }

    public static function pcToTwips(Int $percent) {

    }

    /**
     * Sett norsk som standard-språk
     *
     * Language ID: 1044 Norwegian
     * @see https://technet.microsoft.com/en-us/library/cc287874(v=office.12).aspx
     * 
     * @return void
     */
    public function setDefaultLanguage() {
        $this->phpWord->getSettings()->setThemeFontLang(
            new \PhpOffice\PhpWord\Style\Language(
                'nb-NO'
            )
        );
    }

    /**
     * Opprett en ny section (f.eks annerledes topp-/bunn-tekst, orientation osv)
     *
     * @param String orientation. Default = doc default
     * @return Int Section ID
     */
    public function addSection($orientation = null)
    {
        if (is_null($this->sections)) {
            $this->sections = [];
        }
        // Use doc orientation if not otherwise specified
        if (is_null($orientation)) {
            $orientation = $this->orientation;
        }
        $this->sections[] = $this->phpWord->addSection(['orientation' => $orientation]);

        $this->current_section = sizeof($this->sections)-1;
        return $this->current_section;
    }

    /**
     * Hent gitt section (default==current_section)
     *
     * @param Int $id
     * @return void
     */
    public function getSection(Int $id = null)
    {
        // Opprett første section hvis ingen er opprettet enda
        if (is_null($this->sections)) {
            $this->addSection();
        }

        // Hvis ikke gitt ID, bruk current section
        if (is_null($id)) {
            $id = $this->current_section;
        }

        return $this->sections[$id];
    }

    /**
     * Set margins på arket
     *
     * @param Int millimeter $top
     * @param Int millimeter $left
     * @param Int millimeter $bottom
     * @param Int millimeter $right
     * @return void
     */
    public function setMargins(Int $top = 19, Int $left = 19, Int $bottom = 19, Int $right = 19)
    {
        // getStyle() => PhpOffice\PhpWord\Style\Section
        $this->getSection()->getStyle()
            ->setMarginTop(static::mmToTwips($top))
            ->setMarginRight(static::mmToTwips($right))
            ->setMarginBottom(static::mmToTwips($bottom))
            ->setMarginLeft(static::mmToTwips($left));

        static::$documentWidth = static::$paperWidth - $left - $right;
        static::$documentHeight = static::$paperHeight - $top - $bottom;
    }

    /**
     * Write the document to file
     *
     * @return String download url
     */
    public function writeToFile() {
        $filename = $this->name .'.docx';
        $writer = IOFactory::createWriter($this->phpWord, 'Word2007');
	    $writer->save( $this->getPath() . $filename );
        return $this->getUrl() . $filename;
    }

    /**
     * Sett inn sideskift
     *
     * @return void
     */
    public function addPageBreak() {
        $this->getSection()->addPageBreak();
    }

    public function h1( String $tekst ) {
        return $this->overskrift( $tekst, 1);
    }
    public function h2( String $tekst ) {
        return $this->overskrift( $tekst, 2);
    }
    public function h3( String $tekst) {
        return $this->overskrift( $tekst, 3);
    }
    public function h4( String $tekst ) {
        return $this->overskrift( $tekst, 4);
    }
    
    public function overskrift( String $tekst, Int $storrelse ) {
        $this->getSection()->addTitle( $tekst, $storrelse );
    }

    public function tekst( String $tekst ) {
        $this->getSection()->addText($tekst);
    }

    public function defineHeaderStyles() {
        $max = 4;
        for( $i=0; $i<$max+1; $i++ ) {
            $fontSize = static::DEFAULT_FONT_SIZE + (static::DEFAULT_FONT_SIZE_INCREMENT * ($max - $i));

            $this->phpWord->addTitleStyle(
                $i,
                [
                    'size' => ($fontSize),
                    'bold' => true
                ],
                [
                    'spaceAfter' => static::ptToTwips($fontSize*0.5),
                    'spaceBefore' => static::ptToTwips($fontSize*1.5)
                ]
            );
        }
    }

}

if( defined('DOWNLOAD_PATH_WORD') ) {
    Word::setPath( DOWNLOAD_PATH_WORD );
}
if( defined('DOWNLOAD_URL_WORD') ) {
    Word::setUrl( DOWNLOAD_URL_WORD );
}