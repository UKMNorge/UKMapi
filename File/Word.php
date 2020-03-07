<?php

namespace UKMNorge\File;

use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

require_once('UKM/Autoloader.php');
require_once('UKM/vendor/autoload.php');

class Word extends OfficeDok
{

    const DEFAULT_FONT_SIZE = 14;
    const DEFAULT_FONT_SIZE_INCREMENT = 4;
    const HEADER_TYPE_COUNT = 4; // Number of headers available (h1 throug h-HEADER_TYPE_COUNT)

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

    public function __construct(String $file_name) #, String $rapport_navn)
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
    public static function ptToTwips(Int $points)
    {
        return $points * 20;
    }

    /**
     * Returner prosent av bredden i twips
     *
     * @param Int $percent
     * @return Float $twips
     */
    public static function pcToTwips(Int $percent)
    {
        return round(
            static::mmToTwips(
                (static::$documentWidth / 100) * $percent
            ),
            2
        );
    }

    /**
     * Sett norsk som standard-språk
     *
     * Language ID: 1044 Norwegian
     * @see https://technet.microsoft.com/en-us/library/cc287874(v=office.12).aspx
     * 
     * @return void
     */
    public function setDefaultLanguage()
    {
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

        $this->current_section = sizeof($this->sections) - 1;
        $this->setMargins(); // Sett standardmargin på ny section
        
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
    public function writeToFile()
    {
        $filename = OfficeDok::sanitizeFilename($this->name . '.docx');
        $writer = IOFactory::createWriter($this->phpWord, 'Word2007');
        $writer->save($this->getPath() . $filename);
        return $this->getUrl() . $filename;
    }

    /**
     * Sett inn sideskift
     *
     * @return void
     */
    public function sideskift()
    {
        return $this->getSection()->addPageBreak();
    }

    /**
     * Sett inn en tabell
     *
     * @return \PhpOffice\PhpWord\Element\Table
     */
    public function tabell()
    {
        return $this->getSection()->addTable();
    }

    public function celle(Float $width, Row $row, array $style = [])
    {
        $style['valign'] = \PhpOffice\PhpWord\SimpleType\VerticalJc::BOTTOM;
        return $row->addCell(
            $width,
            $style
        );
    }

    /**
     * Sett inn en overskrift (H1)
     *
     * @param String $tekst
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function h1(String $tekst, $target = null)
    {
        return $this->overskrift($tekst, 1, $target);
    }
    /**
     * Sett inn en overskrift (H2)
     *
     * @param String $tekst
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function h2(String $tekst, $target = null)
    {
        return $this->overskrift($tekst, 2, $target);
    }
    /**
     * Sett inn en overskrift (H3)
     *
     * @param String $tekst
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function h3(String $tekst, $target = null)
    {
        return $this->overskrift($tekst, 3, $target);
    }
    /**
     * Sett inn en overskrift (H4)
     *
     * @param String $tekst
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function h4(String $tekst, $target = null)
    {
        return $this->overskrift($tekst, 4, $target);
    }

    /**
     * Sett inn en overskrift (H1)
     *
     * @param String $tekst
     * @param Int størrelse (ref H1,H2,H3,H4)
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function overskrift(String $tekst, Int $storrelse, $target = null)
    {
        return $this->getTarget($target)->addTitle(htmlspecialchars($tekst), $storrelse);
    }

    /**
     * Legg til et ekstra linjeskift
     *
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function avsnittSkift($target = null)
    {
        return $this->getTarget($target)->addTextBreak();
    }
    /**
     * Legg til ekstra linjeskift
     *
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function linjeSkift($target = null)
    {
        return $this->avsnittSkift($target);
    }

    /**
     * Sett inn en tekst
     *
     * @param String $tekst
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @param Array paragraph style
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function tekst(String $tekst, $target = null, array $paragraph_style = null, array $font_style = null)
    {
        if (is_null($font_style)) {
            $font_style = [];
        }

        $font_style = array_merge(
            $font_style,
            [
                'spaceAfter' => static::pcToTwips(
                    static::getParagraphHeight()
                )
            ]
        );

        return $this->getTarget($target)->addText(
            htmlspecialchars($tekst),
            $font_style,
            $paragraph_style
        );
    }

    /**
     * Sett inn en fare-tekst
     *
     * @param String $tekst
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @param Array paragraph style
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function tekstFare(String $tekst, $target = null, array $paragraph_style = null, array $font_style = null)
    {
        if (is_null($font_style)) {
            $font_style = [];
        }

        $font_style = array_merge(
            $font_style,
            [
                'color' => 'dc3545'
            ]
        );

        return $this->getTarget($target)->addText(
            htmlspecialchars($tekst),
            $font_style,
            $paragraph_style
        );
    }
    /**
     * Sett inn en liten tekst
     *
     * @param String $tekst
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @param Array $paragraph_style
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function tekstLiten(String $tekst, $target = null, array $paragraph_style = null, array $font_style = null)
    {
        if (is_null($font_style)) {
            $font_style = [];
        }

        $font_style = array_merge(
            $font_style,
            [
                'size' => static::DEFAULT_FONT_SIZE - static::DEFAULT_FONT_SIZE_INCREMENT
            ]
        );

        return $this->getTarget($target)->addText(
            htmlspecialchars($tekst),
            $font_style,
            $paragraph_style
        );
    }

    /**
     * Sett inn en muted tekst
     *
     * @param String $tekst
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @param Array $paragraph_style
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function tekstMuted(String $tekst, $target = null, array $paragraph_style = null, array $font_style = null)
    {
        if (is_null($font_style)) {
            $font_style = [];
        }

        $font_style = array_merge(
            $font_style,
            [
                'color' => '999999',
                'bold' => true
            ]
        );
        return $this->getTarget($target)->addText(
            htmlspecialchars($tekst),
            $font_style,
            $paragraph_style
        );
    }

    /**
     * Sett inn en fettekst
     *
     * @param String $tekst
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @param Array $paragraph_style
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function tekstFet(String $tekst, $target = null, array $paragraph_style = null, array $font_style = null)
    {
        if (is_null($font_style)) {
            $font_style = [];
        }

        $font_style = array_merge(
            $font_style,
            [
                'bold' => true
            ]
        );
        return $this->getTarget($target)->addText(
            htmlspecialchars($tekst),
            $font_style,
            $paragraph_style
        );
    }

    /**
     * Beregn hvilken target-container som skal benyttes
     *
     * @param \PhpOffice\PhpWord\Element\AbstractElement|null $target
     * @return \PhpOffice\PhpWord\Element\AbstractElement $target
     */
    public function getTarget($target = null)
    {
        if (is_null($target)) {
            return $this->getSection();
        }
        return $target;
    }


    /**
     * Hent høyden for en standard tekst
     *
     * @return Int points
     */
    public static function getParagraphHeight()
    {
        return static::DEFAULT_FONT_SIZE;
    }

    /**
     * Hent høyden for en H0 (største tittel)
     *
     * @return Int points
     */
    public static function getH0Height()
    {
        return static::getHeaderHeight(0);
    }
    /**
     * Hent høyden for overskrift H1
     *
     * @return Int points
     */
    public static function getH1Height()
    {
        return static::getHeaderHeight(1);
    }
    /**
     * Hent høyden for overskrift H2
     *
     * @return Int points
     */
    public static function getH2Height()
    {
        return static::getHeaderHeight(2);
    }
    /**
     * Hent høyden for overskrift H3
     *
     * @return Int points
     */
    public static function getH3Height()
    {
        return static::getHeaderHeight(3);
    }
    /**
     * Hent høyden for overskrift H4
     *
     * @return Int points
     */
    public static function getH4Height()
    {
        return static::getHeaderHeight(4);
    }

    /**
     * Hent høyden for overskrift (H$header)
     *
     * @param Int $storrelse
     * @return Int points
     */
    public static function getHeaderHeight(Int $header = 0)
    {
        return static::DEFAULT_FONT_SIZE + (static::DEFAULT_FONT_SIZE_INCREMENT * (static::HEADER_TYPE_COUNT - $header));
    }

    /**
     * Definer styles for alle headers
     *
     * @return void
     */
    public function defineHeaderStyles()
    {
        for ($i = 0; $i < static::HEADER_TYPE_COUNT + 1; $i++) {
            $heightFunction = 'getH' . $i . 'Height';
            $fontSize = static::$heightFunction();

            $this->phpWord->addTitleStyle(
                $i,
                [
                    'size' => ($fontSize),
                    'bold' => true
                ],
                [
                    'spaceAfter' => static::ptToTwips($fontSize * 0.5),
                    'spaceBefore' => static::ptToTwips($fontSize * 1.5)
                ]
            );
        }
    }
}

if (defined('DOWNLOAD_PATH_WORD')) {
    Word::setPath(DOWNLOAD_PATH_WORD);
}
if (defined('DOWNLOAD_URL_WORD')) {
    Word::setUrl(DOWNLOAD_URL_WORD);
}
