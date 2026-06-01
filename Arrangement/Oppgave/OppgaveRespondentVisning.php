<?php

namespace UKMNorge\Arrangement\Oppgave;

use Exception;
use UKMNorge\Allergener\Allergener;
use UKMNorge\Arrangement\Skjema\DeltaRespondent;
use UKMNorge\Arrangement\Skjema\Skjema;
use UKMNorge\Arrangement\Skjema\Svar;
use UKMNorge\Arrangement\Skjema\SvarSett;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Personer\Person;
use UKMNorge\Samtykkeskjema\SamtykkeSkjema;
use UKMNorge\Samtykkeskjema\SkjemaSuper;

/**
 * Serialiserer oppgaveliste + svar for én respondent (admin, kun visning).
 */
class OppgaveRespondentVisning {
    public static function forRespondent(Oppgave $oppgave, DeltaRespondent $respondent): array {
        $deltaUserId = (int) $respondent->getId();
        $mobil = $respondent->getMobil();
        $personId = $oppgave->getBestPersonIdForRespondent($deltaUserId, $mobil);
        $is18 = self::isDeltaUser18Plus($deltaUserId, $personId);

        $kjede = [];
        foreach ($oppgave->getSkjemaKjede() as $ledd) {
            $skjema = $ledd->getSkjema();
            $besvart = $skjema->isAnswered($deltaUserId, $personId);
            $foresattGodkjent = $skjema->isForesattGodkjent($deltaUserId, $personId);
            $indicator = self::indicatorForSkjema($besvart, $foresattGodkjent, $is18);

            $kjede[] = [
                'ledd_id'           => $ledd->getId(),
                'skjema_id'         => $ledd->getSkjemaId(),
                'skjema_type'       => $ledd->getSkjemaType(),
                'skjema_type_label' => $ledd->getSkjemaTypeLabel(),
                'skjema_navn'       => $skjema->getNavn(),
                'besvart'           => $besvart,
                'foresatt_godkjent' => $foresattGodkjent,
                'venter_foresatt'   => $besvart && !$is18 && !$foresattGodkjent,
                'indicator'         => $indicator,
                'detalj'            => self::serializeDetalj($ledd->getSkjemaType(), $skjema, $deltaUserId, $personId),
            ];
        }

        return [
            'oppgave' => [
                'id'          => $oppgave->getId(),
                'name'        => $oppgave->getName(),
                'description' => $oppgave->getDescription(),
            ],
            'respondent' => [
                'id'            => $deltaUserId,
                'delta_user_id' => $deltaUserId,
                'navn'          => $respondent->getNavn(),
                'etternavn'     => $respondent->getEtternavn(),
                'mobil'          => $mobil,
                'foresatt_navn'  => $respondent->getForesattNavn(),
                'foresatt_mobil' => $respondent->getForesattMobil(),
                'navn_fullt'     => $respondent->getNavnFullt(),
                'is_18'          => $is18,
            ],
            'person_id' => $personId,
            'kjede'     => $kjede,
        ];
    }

    /**
     * Alle spørsmål i oppgavens spørreskjema-kjede (for spørsmålsvelger i admin).
     *
     * @return array<int, array{skjema_id: int, skjema_navn: string, sporsmal_id: int, tittel: string, label: string}>
     */
    public static function sporsmalListeForOppgave(Oppgave $oppgave): array {
        $liste = [];
        foreach ($oppgave->getSkjemaKjede() as $ledd) {
            if ($ledd->getSkjemaType() !== OppgaveSkjema::SKJEMA_VIDERESENDING) {
                continue;
            }
            $skjema = $ledd->getSkjema();
            if (!($skjema instanceof Skjema)) {
                continue;
            }
            $skjemaNavn = $skjema->getNavn();
            foreach ($skjema->getSporsmal()->getAll() as $sporsmalObj) {
                $tittel = $sporsmalObj->getTittel();
                $liste[] = [
                    'skjema_id'   => $ledd->getSkjemaId(),
                    'skjema_navn' => $skjemaNavn,
                    'sporsmal_id' => $sporsmalObj->getId(),
                    'tittel'      => $tittel,
                    'label'       => $skjemaNavn . ': ' . $tittel,
                ];
            }
        }
        return $liste;
    }

    /**
     * Svar på ett spørsmål for én respondent.
     *
     * @return array{sporsmal_id: int, tittel: string, linjer: array<int, array{label: string, value: string}>, foresatt_godkjent: bool|null}
     */
    public static function sporsmalSvarForRespondent(
        Oppgave $oppgave,
        DeltaRespondent $respondent,
        int $skjemaId,
        int $sporsmalId
    ): array {
        $deltaUserId = (int) $respondent->getId();
        $personId = $oppgave->getBestPersonIdForRespondent($deltaUserId, $respondent->getMobil());

        foreach ($oppgave->getSkjemaKjede() as $ledd) {
            if ($ledd->getSkjemaType() !== OppgaveSkjema::SKJEMA_VIDERESENDING || $ledd->getSkjemaId() !== $skjemaId) {
                continue;
            }
            $skjema = $ledd->getSkjema();
            if (!($skjema instanceof Skjema)) {
                throw new Exception('Fant ikke spørreskjema', 404);
            }
            foreach ($skjema->getSporsmal()->getAll() as $sporsmalObj) {
                if ($sporsmalObj->getId() !== $sporsmalId) {
                    continue;
                }
                $svarsett = self::getSvarSett($skjema, $personId);
                $svar = null;
                try {
                    $svar = $svarsett->get($sporsmalId);
                } catch (Exception $e) {
                    // placeholder or missing
                }

                return [
                    'sporsmal_id'         => $sporsmalId,
                    'tittel'              => $sporsmalObj->getTittel(),
                    'linjer'              => self::formatSvarLinjer($sporsmalObj->getType(), $svar),
                    'foresatt_godkjent'   => $svar !== null ? $svar->isForesattGodkjent() : null,
                ];
            }
            throw new Exception('Fant ikke spørsmålet i skjemaet', 404);
        }
        throw new Exception('Fant ikke skjemaet i oppgaven', 404);
    }

    private static function indicatorForSkjema(bool $besvart, bool $foresattGodkjent, bool $is18): string {
        if (!$besvart) {
            return 'danger';
        }
        if ($is18 || $foresattGodkjent) {
            return 'success';
        }
        return 'warning';
    }

    private static function isDeltaUser18Plus(int $deltaUserId, int $personId): bool {
        if ($personId > 0) {
            try {
                $person = Person::loadFromId($personId);
                if ($person->getAlder() >= 18) {
                    return true;
                }
            } catch (Exception $e) {
                // fall through
            }
        }

        try {
            $sql = new Query(
                "SELECT birthdate FROM ukm_user WHERE id = '#id'",
                ['id' => $deltaUserId],
                'ukmdelta'
            );
            $res = $sql->run('array');
            if (!empty($res['birthdate'])) {
                $birth = new \DateTime($res['birthdate']);
                $cutoff = (new \DateTime())->modify('-18 years');
                return $birth <= $cutoff;
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    private static function serializeDetalj(string $skjemaType, SkjemaSuper $skjema, int $deltaUserId, int $personId): array {
        if ($skjemaType === OppgaveSkjema::SKJEMA_SAMTYKKE && $skjema instanceof SamtykkeSkjema) {
            return self::serializeSamtykke($skjema, $deltaUserId);
        }
        if ($skjemaType === OppgaveSkjema::SKJEMA_VIDERESENDING && $skjema instanceof Skjema) {
            return self::serializeSporreskjema($skjema, $personId);
        }
        return ['type' => 'ukjent'];
    }

    private static function serializeSamtykke(SamtykkeSkjema $skjema, int $deltaUserId): array {
        $versjoner = [];
        foreach ($skjema->getVersjoner() as $versjon) {
            $versjoner[] = [
                'beskrivelse' => $versjon->getBeskrivelse(),
                'body_text'   => $versjon->getBodyText(),
            ];
        }

        $svarData = null;
        $versjonListe = $skjema->getVersjoner();
        if (count($versjonListe) > 0) {
            $sisteSvar = $versjonListe[0]->getSvarSamtykkeForBruker($deltaUserId);
            if ($sisteSvar !== null) {
                $svarData = [
                    'svar'       => $sisteSvar->getSvar(),
                    'kommentar'  => $sisteSvar->getKommentar(),
                    'created_at' => $sisteSvar->getCreatedAt(),
                    'skjema_type' => $skjema->getType(),
                ];
            }
        }

        return [
            'type'           => 'samtykkeskjema',
            'samtykke_type'  => $skjema->getType(),
            'versjoner'      => $versjoner,
            'svar'           => $svarData,
        ];
    }

    private static function serializeSporreskjema(Skjema $skjema, int $personId): array {
        $svarsett = self::getSvarSett($skjema, $personId);
        $sporsmal = [];

        foreach ($skjema->getSporsmal()->getAll() as $sporsmalObj) {
            $svar = null;
            try {
                $svar = $svarsett->get($sporsmalObj->getId());
            } catch (Exception $e) {
                // placeholder or missing
            }

            $sporsmal[] = [
                'id'                => $sporsmalObj->getId(),
                'type'              => $sporsmalObj->getType(),
                'tittel'            => $sporsmalObj->getTittel(),
                'hjelp'             => $sporsmalObj->getTekst(),
                'linjer'            => self::formatSvarLinjer($sporsmalObj->getType(), $svar),
                'foresatt_godkjent' => $svar !== null ? $svar->isForesattGodkjent() : null,
            ];
        }

        return [
            'type'     => 'sporreskjema',
            'sporsmal' => $sporsmal,
        ];
    }

    private static function getSvarSett(Skjema $skjema, int $personId): SvarSett {
        try {
            $respondent = $skjema->getRespondenter()->get($personId);
            return $respondent->getSvar();
        } catch (Exception $e) {
            if ($e->getCode() == 163003) {
                return SvarSett::getPlaceholder('person', $personId, $skjema->getId());
            }
            throw $e;
        }
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private static function formatSvarLinjer(string $type, ?Svar $svar): array {
        if ($svar === null) {
            return [['label' => '', 'value' => '—']];
        }

        $value = $svar->getValue();

        switch ($type) {
            case 'janei':
                $raw = is_scalar($value) ? (string) $value : '';
                if ($raw === 'true' || $raw === '1') {
                    return [['label' => '', 'value' => 'Ja']];
                }
                if ($raw === 'false' || $raw === '0') {
                    return [['label' => '', 'value' => 'Nei']];
                }
                return [['label' => '', 'value' => $raw !== '' ? $raw : '—']];

            case 'kort_tekst':
            case 'lang_tekst':
                return [['label' => '', 'value' => self::scalarToString($value)]];

            case 'kontakt':
                return [
                    ['label' => 'Navn', 'value' => self::fieldToString($value, 'navn')],
                    ['label' => 'Mobil', 'value' => self::fieldToString($value, 'mobil')],
                    ['label' => 'E-post', 'value' => self::fieldToString($value, 'epost')],
                ];

            case 'kontaktajourfore':
                $linjer = [
                    ['label' => 'Bekrefter brukerdata', 'value' => self::boolLabel(self::fieldToString($value, 'bekrefter_brukerdata'))],
                    ['label' => 'Kontakt navn', 'value' => self::fieldToString($value, 'kontakt_navn')],
                    ['label' => 'Kontakt mobil', 'value' => self::fieldToString($value, 'kontakt_mobil')],
                    ['label' => 'Er 18 år (gjelder deltakere som i utgangspunktet er registrert som 17 år)', 'value' => self::boolLabel(self::fieldToString($value, 'er_18_aar'))],
                    ['label' => 'Foresatt navn', 'value' => self::fieldToString($value, 'fore_navn')],
                    ['label' => 'Foresatt mobil', 'value' => self::fieldToString($value, 'fore_mobil')],
                    ['label' => 'Foresatt e-post', 'value' => self::fieldToString($value, 'fore_epost')],
                ];
                return array_values(array_filter($linjer, fn ($l) => $l['value'] !== '—'));

            case 'filopplasting':
                $filId = self::scalarToString($value);
                if ($filId === '' || $filId === '—') {
                    return [['label' => '', 'value' => 'Ingen fil lastet opp']];
                }
                return [
                    ['label' => 'Fil', 'value' => $filId],
                    ['label' => 'Lenke', 'value' => '/getplaybackfile?id=' . urlencode($filId)],
                ];

            case 'intoleranser':
                return self::formatIntoleranser($value);

            case 'innslagdatabekreftelse':
                return [
                    [
                        'label' => 'Bekreftelse',
                        'value' => self::boolLabel(self::fieldToString($value, 'bekrefter_innslagsdata')),
                    ],
                ];

            default:
                if (is_object($value) || is_array($value)) {
                    return self::formatObjectAsLinjer($value);
                }
                return [['label' => '', 'value' => self::scalarToString($value)]];
        }
    }

    private static function formatIntoleranser($value): array {
        if (!is_object($value) && !is_array($value)) {
            return [['label' => '', 'value' => '—']];
        }
        $obj = (object) (is_array($value) ? $value : (array) $value);
        if (!empty($obj->ingen)) {
            return [['label' => '', 'value' => 'Ingen allergier eller intoleranser']];
        }

        $navnListe = [];
        $alle = array_merge(Allergener::getKulturelle(), Allergener::getStandard());
        $idTilNavn = [];
        foreach ($alle as $allergen) {
            $idTilNavn[$allergen->id] = $allergen->navn;
        }
        $liste = $obj->liste ?? [];
        if (is_array($liste)) {
            foreach ($liste as $id) {
                $navnListe[] = $idTilNavn[$id] ?? ('ID ' . $id);
            }
        }

        $linjer = [];
        if (count($navnListe) > 0) {
            $linjer[] = ['label' => 'Valgte', 'value' => implode(', ', $navnListe)];
        }
        $tekst = isset($obj->tekst) ? trim((string) $obj->tekst) : '';
        if ($tekst !== '') {
            $linjer[] = ['label' => 'Tilleggsinformasjon', 'value' => $tekst];
        }
        if (count($linjer) === 0) {
            $linjer[] = ['label' => '', 'value' => '—'];
        }
        return $linjer;
    }

    private static function fieldToString($value, string $key): string {
        if (is_object($value) && isset($value->$key)) {
            return self::scalarToString($value->$key);
        }
        if (is_array($value) && isset($value[$key])) {
            return self::scalarToString($value[$key]);
        }
        return '—';
    }

    private static function scalarToString($value): string {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Ja' : 'Nei';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
        return $encoded !== false ? $encoded : '—';
    }

    private static function boolLabel(string $raw): string {
        if ($raw === '—' || $raw === '') {
            return '—';
        }
        if (in_array($raw, ['1', 'true', 'Ja'], true)) {
            return 'Ja';
        }
        if (in_array($raw, ['0', 'false', 'Nei'], true)) {
            return 'Nei';
        }
        return $raw;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private static function formatObjectAsLinjer($value): array {
        $obj = (object) (is_array($value) ? $value : (array) $value);
        $linjer = [];
        foreach ((array) $obj as $key => $felt) {
            if ($felt === null || $felt === '') {
                continue;
            }
            $label = ucfirst(str_replace('_', ' ', (string) $key));
            $linjer[] = [
                'label' => $label,
                'value' => self::boolLabel(self::scalarToString($felt)),
            ];
        }
        if (count($linjer) === 0) {
            $linjer[] = ['label' => '', 'value' => '—'];
        }
        return $linjer;
    }
}
