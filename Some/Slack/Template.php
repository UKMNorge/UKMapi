<?php

namespace UKMNorge\Some\Slack;

use UKMNorge\Slack\Block\Composition\Markdown;
use UKMNorge\Slack\Block\Section;
use UKMNorge\Slack\Payload\PayloadInterface;
use UKMNorge\Some\Forslag\Ide;

class Template
{

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
                    "*Ønsket dato* \n\n" . $ide->getPubliseringsdato()
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
