<?php

namespace UKMNorge\Some\Slack;

use UKMNorge\Slack\Block\Composition\Markdown;
use UKMNorge\Slack\Block\Composition\PlainText;
use UKMNorge\Slack\Block\Element\MultiSelect;
use UKMNorge\Slack\Block\Section;
use UKMNorge\Slack\Option;
use UKMNorge\Slack\Payload\PayloadInterface;
use UKMNorge\Some\Forslag\Ide;
use UKMNorge\Some\Kanaler\Kanaler;

class Template
{

    /**
     * Hent standard kanal-selector
     *
     * @param Markdown $tekst
     * @param Array<Option> $initial_options
     * @return Section
     */
    public static function getKanalSelector(Markdown $tekst, Array $initial_options = null) {
        $select = new Section($tekst);
        $kanaler = Kanaler::getAlle();
        $options = [];
        foreach( $kanaler->getAll() as $kanal ) {
            $options[] = new Option(
                MultiSelect::class, new PlainText( $kanal->getNavn() ), $kanal->getId()
            );
        }

        $select->setAccessory(
            new MultiSelect(
                'channels',
                $options,
                new PlainText('Velg kanaler')
            )
        );

        if( !is_null($initial_options)) {
            $select->getAccessory()->setInitialOptions($initial_options);
        }

        return $select;
    }

    /**
     * Forhåndsvisning av ønsket status
     *
     * @param PayloadInterface 
     * @param Ide 
     * @return PayloadInterface
     */
    public static function getStatusSuggestionPreview(PayloadInterface $payload, Ide $ide)
    {
        $summary = new Section(null);
        $summary->getFields()
            ->add(
                new Markdown(
                    "*Hva skal deles* \n\n" . $ide->getHva()
                )
            )
            ->add(
                new Markdown(
                    "*Ønsket dato* \n\n" . $ide->getPubliseringsdato()->format('d.m')
                )
            );

        $text = new Section(
            new Markdown(
                "*Ønsket tekst / beskrivelse* \n\n" . $ide->getBeskrivelse()
            )
        );

        $payload->getBlocks()->add($summary);
        $payload->getBlocks()->add($text);

        return $payload;
    }
}
