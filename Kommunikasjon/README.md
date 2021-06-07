Kommunikasjon
=============


## SMS
For å sende SMS, kan du bruke følgende kode.

```php

    use UKMNorge\Kommunikasjon\SMS;
    use UKMNorge\Kommunikasjon\Mottaker;

    $engangskode = 'A8X';
    $mobilnummer = '+4799999999';
    $melding = 'Hei! Din engangskode er '. $engangskode;
    SMS::setSystemId('UKMid', 0);
    $sms = new SMS('UKMNorge');
    $result = $sms->setMelding( $melding )->setMottaker( Mottaker::fraMobil( $mobilnummer ) )->send();
```

De ulike systemene er per i dag:

- `wordpress` (da kan du bruke `get_current_user()` for å hente id)
- `UKMdelta`
- `UKMid`
- `UKMsjekk`
- `IllegalPrefix`
- `samtykke`, `samtykke-barn`, `samtykke-takk` brukes alle av [fotoreservasjon-systemet](https://github.com/UKMNorge/UKMsamtykke)

De fleste systemene krever en numerisk ID, mens noen tillater at ID er null.
