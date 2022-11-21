# Feedback
Feedback brukes for å hente tilbakemeldinger fra UKM systemer.

## Strukturen
Feedback har disse klassene:
* Feedbacks - abstract classe som representerer en generel feedback og som har en liste med FeedbackResponse
* FeedbackDelta - er utvidelse av Feedback som passer bare påmeldingsystemet (Delta)
* FeedbackArrangor - er utvidelse av Feedback som passer bare arrangørssystemet (er ikke implementert)
* FeedbackResponse - representerer et svar som ble sendt i en feedback. Med andre ord kan en tilbakemelding (Feedback) ha mange svar (FeedbackResponse)
* Feedbacks - utivder klassen Collection og brukes for å hente og organisere Feedback
* Write - brukes for å skrive (lagre, updatere) Feedback og FeedbackResponse

## Klass diagram
![klassdiagram](documentation/klassdiagram.png?raw=true)

## Database
Feedback bruker 3 tabeller i databasen SS3:
* feedback - representerer Feedback
* feedback_response - representerer FeedbackResponse
* rel_innslag_feedback - relasjonen mellom et Innslag og en Feedback. F.eks. en tilbakemelding er gitt på et innslag
