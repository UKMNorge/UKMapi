UKMapi
======

## Exceptions:
API V2 skal kaste kodede exceptions. For exceptions benyttes følgende struktur og tabell for å genere sekssifret unik error-kode
`[ les|skriv {1|5} ] [ objekt {xx} ] [ action {yyy} ]`

prefix | objekt | les | skriv
------------ | ------------- | ------------ | -------------
 x | SQL | - | 901yyy 
1 | Mønstring | 101yyy | 501yyy
1 | Arrangement/Arrangement | 101yyy | 501yyy
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
12 | Context | 112yyy | -
13 | Samtykke | 113yyy | -
14 | Bilde | 114yyy | 514yyy
15 | Film | 115yyy | 515yyy
16 | Post | 116yyy | 516yyy
17 | Sensitivt | 117yyy | -
18 | SensitivtRequester | 118yyy | - 
19 | Intoleranse | 119yyy | -
20 | Hendelse/forestilling | 120yyy | 520yyy
21 | HendelseCollection | 121yyy | 521yyy
50 | Arrangement/Arrangementer | 150yyy | -
51 | Arrangement/Skjema/Skjema | 151yyy | 551yyy
52 | Arrangement/Skjema/Sporsmal | 152yyy | -
53 | Arrangement/Skjema/SvarSett | 153yyy | -
54 | Arrangement/Skjema/Svar | 154yyy | -  
55 | Arrangement/Videresending/Videresending | 155yyy | - 
56 | Arrangement/Videresending/Videresender | 156yyy | - 
57 | Arrangement/Videresending/Mottaker | 157yyy | - 
58 | Arrangement/Videresending/Avsender | 158yyy | - 
59 | Arrangement/Eier | 159yyy | - 
61 | Nettverk/Administrator | 161yyy | 561yyy
62 | Nettverk/Omrade | 162yyy | 562yyy
71 | Wordpress/User | 171yyy | 571yyy
72 | Wordpress/Blog | 172yyy | 572yyy
81 | Slack | 181yyy | 581yyy