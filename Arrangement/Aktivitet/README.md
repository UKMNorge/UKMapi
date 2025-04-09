# AktivitetTidspunkt

Denne klassen representerer et tidspunkt for en aktivitet i UKM-systemet. Den lar deg håndtere påmelding og verifisering av deltakere til en bestemt aktivitet.

## Metoder for påmelding

### `registrerDeltakerPaamelding(int $tidspunktId, string $mobil): string`

Denne statiske metoden registrerer en deltaker til et gitt tidspunkt, og returnerer en kode som skal brukes for SMS-verifisering.

#### Parametere
- `tidspunktId` *(int)*: ID-en til tidspunktet det skal meldes på til.
- `mobil` *(string)*: Mobilnummeret til deltakeren.

#### Retur
- *(string)* En verifiseringskode (typisk en 6-sifret kode) som skal sendes til deltakeren via SMS.

#### Unntak
Metoden kaster `Exception` i følgende tilfeller:
- Tidspunktet har ikke påmelding aktivert.
- Deltakeren er allerede påmeldt.
- Det er ikke plass til flere deltakere.
- Deltakeren er ikke intern, men tidspunktet er begrenset til interne deltakere.
- Det oppstår feil under registrering.

#### Eksempel
```php
try {
    $smsKode = AktivitetTidspunkt::registrerDeltakerPaamelding(42, '12345678');
    echo "Verifiseringskode sendt: " . $smsKode;
} catch (Exception $e) {
    echo "Feil: " . $e->getMessage();
}
```

---
## Metoder for verifisering

### `verifyDeltakerPaamelding(int $tidspunktId, string $mobil, string $smsCode): bool`

Denne statiske metoden verifiserer en deltaker til et gitt tidspunkt, og returnerer true hvis deltakeren er verifisert.

#### Parametere
- `tidspunktId` *(int)*: ID-en til tidspunktet det skal meldes på til.
- `mobil` *(string)*: Mobilnummeret til deltakeren.
- `smsCode` *(string)*: Kode som har blitt sendt via og som deltakeren gir

#### Retur
- *(bool)* true hvis deltakeren gir riktig SMS kode (og andre steg er fullført)

#### Unntak
Metoden kaster `Exception` i følgende tilfeller:
- Klarte ikke å hente deltakelsen.
- SMS-koden er feil.
  
- Eller andre unntak som kommer fra andre steder

#### Eksempel
```php
try {
    $verifisering = AktivitetTidspunkt::verifyDeltakerPaamelding(221, '46516256', 'AWQKT3');
    echo "Verifisering: " . $verifisering;
} catch (Exception $e) {
    echo "Feil: " . $e->getMessage();
}
```
