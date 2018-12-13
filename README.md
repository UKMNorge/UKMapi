UKMapi
======

## Exceptions:
API V2 skal kaste kodede exceptions. For exceptions benyttes følgende struktur og tabell for å genere sekssifret unik error-kode
`[ les|skriv {1|5} ] [ objekt {xx} ] [ action {yyy} ]`

prefix | objekt | les | skriv
------------ | ------------- | ------------ | -------------
 x | SQL | - | 901yyy 
1 | Mønstring | 101yyy | 501yyy
2 | Kommune | 102yyy | 502yyy
3 | Fylke | 103yyy | 503yyy
4 | InnslagCollection | 104yyy | 504yyy
5 | Innslag | 105yyy | 505yyy
6 | PersonCollection | 106yyy | 506yyy
7 | Person | 107yyy | 507yyy
8 | TitlerCollection | 108yyy | 508yyy
9 | Tittel | 109yyy | 509yyy
10 | _collection | 110yyy | 511yyy
11 | Kontaktperson | 111yyy | 511yyy
