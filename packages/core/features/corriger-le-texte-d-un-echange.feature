# language: fr
Fonctionnalité: Corriger le texte d'un échange
  En tant que prospecteur, même sans le droit aux données personnelles
  Je veux retoucher le texte d'un échange
  Afin de le rendre conforme au RGPD, en gardant qui l'a corrigé et quand

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Grace Hopper" de "ACME"
    Et "Michel" a noté le 15/10/2026 à 09h05 un appel reçu de "Grace Hopper" à 09h00 : "Grace rappellera lundi"

  Scénario: Sans le droit aux données personnelles, je retouche le texte, la correction est tracée
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Et nous sommes le 16/10/2026 à 10h00
    Quand je corrige le texte de cet échange en "La contactée rappellera lundi"
    Alors je vois l'échange :
      | canal   | Appel                            |
      | sens    | reçu                             |
      | moment  | 15/10/2026 09:00                 |
      | texte   | La contactée rappellera lundi    |
      | noté    | le 15/10/2026 à 09:05 par Michel |
      | modifié | le 16/10/2026 à 10:00 par moi    |

  Scénario: Avec le droit aux données personnelles, je retouche le texte aussi
    Soit je suis connecté en tant que "Michel"
    Quand je corrige le texte de cet échange en "Rappellera lundi"
    Alors je vois l'échange :
      | texte | Rappellera lundi |
