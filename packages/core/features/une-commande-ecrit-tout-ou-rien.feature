# language: fr
Fonctionnalité: Une commande écrit tout ou rien
  En tant que membre d'un compte
  Je veux que ce qu'un geste écrit, et ce qui en découle dans les autres contextes, soit gardé d'un seul tenant
  Afin qu'un geste refusé ou en échec ne laisse jamais un état à moitié écrit

  Règle: un use case dont une réaction échoue n'écrit rien
    Les réactions des autres contextes rejoignent la commande qui a publié : le fondateur et son profil, écrits
    par les réactions à l'ouverture du compte, sont abandonnés avec le compte.

    Scénario: Une réaction refusée refuse le geste, qui n'écrit rien
      Étant donné une réaction à l'ouverture d'un compte qui refuse : "Refused by the reaction"
      Quand j'ouvre le compte de mon organisation :
        | raison sociale | SOLEAN IT |
      Alors c'est refusé : "Refused by the reaction"
      Et rien n'est écrit

    Scénario: Une réaction en échec fait échouer le geste, qui n'écrit rien
      Étant donné une réaction à l'ouverture d'un compte qui échoue
      Quand j'ouvre le compte de mon organisation, en vain :
        | raison sociale | SOLEAN IT |
      Alors rien n'est écrit

