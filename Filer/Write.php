<?php

namespace UKMNorge\Filer;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;

class Write
{
    /**
     * Opprett en ny playback-fil (ukm_playback_file)
     *
     * @throws Exception
     */
    public static function opprett(
        string $name,
        string $filnavn,
        ?Arrangement $arrangement = null,
        ?string $beskrivelse = null,
        ?int $svar_id = null,
        ?int $samtykkeskjema_version_id = null,
        ?int $samtykkeskjema_id = null
    ) : PlaybackFile {
        $sql = new Insert(PlaybackFile::TABLE);
        $sql->add('pbf_name', $name);
        $sql->add('pbf_file', $filnavn);
        
        if ($arrangement != null) {
        $sql->add('pl_id', $arrangement->getId());
        }
        if ($beskrivelse !== null) {
            $sql->add('pbf_description', $beskrivelse);
        }
        if ($svar_id !== null) {
            $sql->add('svar_id', $svar_id);
        }
        if ($samtykkeskjema_version_id !== null) {
            $sql->add('samtykkeskjema_version_id', $samtykkeskjema_version_id);
        }
        if ($samtykkeskjema_id !== null) {
            $sql->add('samtykkeskjema_id', $samtykkeskjema_id);
        }

        try {
            $res = $sql->run();
        } catch (Exception $e) {
            if ($e->getCode() == 901001) {
                throw new Exception('Kunne ikke opprette playback-fil.');
            }
            throw $e;
        }

        if (!$res) {
            throw new Exception('Kunne ikke opprette playback-fil', 533002);
        }

        return PlaybackFile::getById((int) $res);
    }

    /**
     * Lagre endringer i playback-fil
     *
     * @throws Exception
     */
    public static function lagre(PlaybackFile $playbackFile) : bool
    {
        $database_playbackFile = PlaybackFile::getById($playbackFile->getId());

        $sql = new Update(
            PlaybackFile::TABLE,
            [
                'pbf_id' => $playbackFile->getId()
            ]
        );

        $fields = [
            'pl_id' => 'getArrangementId',
            'pbf_name' => 'getNavn',
            'pbf_description' => 'getBeskrivelse',
            'pbf_file' => 'getFil',
            'svar_id' => 'getSvarId',
            'samtykkeskjema_version_id' => 'getSamtykkeskjemaVersionId',
            'samtykkeskjema_id' => 'getSamtykkeskjemaId',
        ];

        foreach ($fields as $felt => $funksjon) {
            if ($playbackFile->$funksjon() != $database_playbackFile->$funksjon()) {
                $sql->add($felt, $playbackFile->$funksjon());
            }
        }

        if (!$sql->hasChanges()) {
            return true;
        }

        $res = $sql->run();
        if (!$res || $res == -1) {
            throw new Exception('Kunne ikke lagre playback-fil', 533001);
        }

        return true;
    }

    /**
     * Slett playback-fil
     *
     * @throws Exception
     */
    public static function slett(Arrangement $arrangement, PlaybackFile $playbackFile) : bool
    {
        $sql = new Delete(
            PlaybackFile::TABLE,
            [
                'pbf_id' => $playbackFile->getId(),
                'pl_id' => $arrangement->getId(),
            ]
        );

        if ($sql->run()) {
            return true;
        }

        throw new Exception('Kunne ikke slette playback-fil', 533003);
    }
}
