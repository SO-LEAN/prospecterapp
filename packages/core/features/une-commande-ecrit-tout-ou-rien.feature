# language: fr
Fonctionnalité: Une commande écrit tout ou rien
  En tant que membre d'un compte
  Je veux que ce qu'un geste écrit soit gardé d'un seul tenant, et que ce qui en découle ailleurs ne le défasse pas
  Afin qu'un geste refusé ou en échec ne laisse jamais un état à moitié écrit, et qu'un geste réussi le reste

  Les réactions suivent la commande, chacune dans sa propre transaction : son refus ou son échec n'annule pas le
  geste, ni les autres réactions, et l'événement attend en file d'échec d'être relancé. Un geste refusé n'écrit rien,
  et rien n'en découle.

  Scénario: Une réaction refusée n'annule pas le geste
    Étant donné une réaction à l'ouverture d'un compte qui refuse : "Refused by the reaction"
    Quand j'ouvre le compte de mon organisation :
      | raison sociale | SOLEAN IT |
    Alors je vois mon compte :
      | raison sociale de mon organisation | SOLEAN IT |
      | prénom                             |           |
    Et l'ouverture du compte attend en file d'échec : "Refused by the reaction"

  Scénario: Une réaction en échec n'annule pas le geste
    Étant donné une réaction à l'ouverture d'un compte qui échoue
    Quand j'ouvre le compte de mon organisation :
      | raison sociale | SOLEAN IT |
    Alors je vois mon compte :
      | raison sociale de mon organisation | SOLEAN IT |
      | prénom                             |           |
    Et l'ouverture du compte attend en file d'échec : "The reaction failed"

  Scénario: Une réaction en échec n'écrit rien
    Étant donné une réaction à l'ouverture d'un compte qui prospecte "Lumen" puis échoue
    Quand j'ouvre le compte de mon organisation :
      | raison sociale | SOLEAN IT |
    Alors aucune organisation "Lumen" n'est prospectée

  Scénario: Un geste refusé n'a pas de suite
    Quand j'ouvre le compte de mon organisation :
      | forme juridique | SARL |
    Alors c'est refusé : "An account needs the corporate name of its organization"
    Et rien n'est écrit
