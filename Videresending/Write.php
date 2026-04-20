<?php

namespace UKMNorge\Videresending;

use Exception;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;

class Write
{
    /**
     * Opprett rad, eller returner eksisterende aktiv / reaktiver deaktivert.
     * Unik nøkkel: arrangement_fra, arrangement_til, b_id, p_id, t_id (inkl. NULL der det er tillatt).
     */
    public static function create(
        int $season,
        string $innslag_type,
        int $arrangement_fra,
        int $arrangement_til,
        ?int $p_id = null,
        ?int $b_id = null,
        bool $godkjent = false,
        ?string $beskrivelse = null,
        ?int $t_id = null
    ): VideresendingNominasjon {
        $aktiv = VideresendingNominasjon::finnVedNokkel($arrangement_fra, $arrangement_til, $b_id, $p_id, $t_id, true);
        if ($aktiv !== null) {
            return $aktiv;
        }

        $inaktiv = VideresendingNominasjon::finnVedNokkel($arrangement_fra, $arrangement_til, $b_id, $p_id, $t_id, false);
        if ($inaktiv !== null && !$inaktiv->getActive()) {
            $inaktiv->setActive(true);
            self::save($inaktiv);
            return $inaktiv;
        }

        $status = VideresendingNominasjon::STATUS_HOS_MOTTAKER;
        VideresendingNominasjon::krevGyldigStatus($status);
        $sql = new Insert(VideresendingNominasjon::TABLE);
        $sql->add('p_id', $p_id);
        $sql->add('b_id', $b_id);
        $sql->add('t_id', $t_id);
        $sql->add('season', $season);
        $sql->add('innslag_type', $innslag_type);
        $sql->add('arrangement_fra', $arrangement_fra);
        $sql->add('arrangement_til', $arrangement_til);
        $sql->add('godkjent', $godkjent ? 1 : 0);
        $sql->add('beskrivelse', $beskrivelse);
        $sql->add('status', $status);
        $sql->add('active', 1);

        $id = $sql->run();
        if (!$id) {
            throw new Exception('Kunne ikke opprette videresending_nominasjon');
        }

        return new VideresendingNominasjon((int) $id);
    }

    /**
     * Oppdater rad ut fra gjeldende felt på objektet.
     */
    public static function save(VideresendingNominasjon $nominasjon): bool
    {
        $sql = new Update(
            VideresendingNominasjon::TABLE,
            ['id' => $nominasjon->getId()]
        );
        $sql->add('p_id', $nominasjon->getPId());
        $sql->add('b_id', $nominasjon->getBId());
        $sql->add('t_id', $nominasjon->getTId());
        $sql->add('season', $nominasjon->getSeason());
        $sql->add('innslag_type', $nominasjon->getInnslagType());
        $sql->add('arrangement_fra', $nominasjon->getArrangementFra()->getId());
        $sql->add('arrangement_til', $nominasjon->getArrangementTil()->getId());
        $sql->add('godkjent', $nominasjon->getGodkjent() ? 1 : 0);
        $sql->add('beskrivelse', $nominasjon->getBeskrivelse());
        $sql->add('status', $nominasjon->getStatus());
        $sql->add('active', $nominasjon->getActive() ? 1 : 0);
        $sql->add('sporsmaal', $nominasjon->getSporsmal());
        $sql->add('svar', $nominasjon->getSvar());

        $res = $sql->run();
        if ($res === false) {
            throw new Exception('Kunne ikke lagre videresending_nominasjon id ' . $nominasjon->getId());
        }

        return true;
    }

    /**
     * Myk sletting: raden beholdes, men vises ikke i lister (active = 0).
     */
    public static function deactivate(VideresendingNominasjon $nominasjon): bool
    {
        $nominasjon->setActive(false);
        return self::save($nominasjon);
    }

    public static function delete(VideresendingNominasjon $nominasjon): bool
    {
        $delete = new Delete(
            VideresendingNominasjon::TABLE,
            ['id' => $nominasjon->getId()]
        );
        $res = $delete->run();
        if (!$res || $res < 1) {
            throw new Exception('Kunne ikke slette videresending_nominasjon id ' . $nominasjon->getId());
        }
        return true;
    }
}
