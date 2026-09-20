# Contrôle des entrées et sorties — Chez Traoré

Objectif : savoir chaque jour, **en quantité et en argent**, ce qui est entré (achats),
ce qui est sorti (ventes) et ce qui a disparu (pertes, oublis de saisie, vols).

Le cas du pain arabe le montre : 2 000 pains facturés par le boulanger, 1 300 kebabs vendus.
700 pains à 4 MRU = **2 800 MRU perdus sur un mois**, sans qu'on sache où.

---

## 1. La règle d'or : un achat ne s'enregistre qu'une seule fois, et il touche le stock

Avant, l'argent dépensé et le stock vivaient chacun de leur côté. Désormais :

> **Menu Achats** → une saisie = la marchandise entre en stock **et** la dépense est enregistrée.

Sans cette règle, aucune comparaison achat / vente n'est possible. Si un achat est noté
uniquement comme « dépense », il devient invisible pour le contrôle matière.

---

## 2. Paramétrer les articles clés (à faire une fois)

Menu **Stock → Nouvel article**. Pour chaque article, renseigner le conditionnement
d'achat, ce qui évite toute erreur de calcul à la saisie.

| Article | Unité | Conditionnement | Unités | Prix d'achat | Coût unitaire |
|---|---|---|---|---|---|
| Pain arabe | **paquet** (10 pains) | — (acheté au paquet) | — | 40 MRU | **40 MRU / paquet** |
| Pain tacos | pièce | Carton (6 sachets × 18) | 108 | prix du jour | prix ÷ 108 |
| Poulet | pièce | Carton de 10 | 10 | prix du jour | prix ÷ 10 |

Raccourcis de paramétrage (à lancer une fois, relançables sans risque) :

- `php artisan restaurant:pain-arabe` → fournisseur **Lassana Camara**, article *Pain arabe*
  suivi **en paquets** (1 paquet = 10 pains, 40 MRU) et **pris à crédit par défaut**,
  recette **0,1 paquet (= 1 pain)** pour
  *Kebab poulet*, *Kebab viande*, *Kebab spécial* et *Chawarma*. Au comptage du soir on saisit
  des **paquets** : `10,5` = 10 paquets et 5 pains ;
- `php artisan restaurant:viande-hachee` → fournisseur **Fabe Cissé**, article *Viande hachée* (kg)
  au **prix convenu de 220 MRU le kilo**, quantité habituelle **2 kg par jour** (440 MRU), et les
  recettes de départ : *Kebab viande* et *Kebab spécial* 0,12 kg, *Tacos viande* 0,15 kg,
  *Pizza viande* 0,10 kg, *Hamburger* 0,12 kg. Ces grammages sont des **estimations** :
  pèse cinq portions réelles et corrige-les, soit dans Produits → Recette, soit en relançant
  la commande avec `--kebab=0.15 --tacos=0.18`. Si le prix du kilo change :
  `--prix-kg=460`, ou `--kg-par-jour=3` si la quantité change.

Un produit peut utiliser les deux : les commandes ne s'effacent pas l'une l'autre.
Après avoir créé le *Kebab spécial* dans le menu Produits, relance les deux commandes
pour qu'il reçoive sa recette.

Raccourci : la commande `php artisan restaurant:articles-cles` crée ces trois articles
avec leur conditionnement, déjà cochés « article clé ». Elle ne touche jamais aux
quantités en stock et peut être relancée sans risque.

Cocher **« Article clé — à compter chaque soir »** pour ces trois-là, plus les boissons
et tout ce qui part vite ou coûte cher. Cinq à huit articles suffisent : un comptage court
se fait vraiment tous les soirs ; un comptage de quarante articles ne se fait jamais.

---

## 2 bis. Les fournisseurs à abonnement (boulanger, viande hachée)

### L'article porte son fournisseur

Chaque article de stock connaît **son fournisseur habituel** et **son mode d'achat habituel**
(fiche article : *Stock → Gérer*). Dès qu'on choisit cet article dans le menu **Achats**,
le fournisseur et le règlement se remplissent tout seuls. C'est ce lien qui garantit qu'une
entrée de pain arabe devient **toujours** une dette chez Lassana Camara, et jamais une
sortie de caisse oubliée.

Le menu **Fournisseurs** affiche, pour chacun, les articles qui lui sont rattachés.

### La prise du jour : la saisie de dix secondes

Quand un article a un **prix convenu** avec son fournisseur (40 MRU le paquet de pain,
220 MRU le kilo de viande), le menu **Achats** affiche en haut un bloc **« Prise du jour »** :

> Pain arabe — 40 MRU le paquet — Lassana Camara — [ **30** ] → *Enregistrer*

On saisit **uniquement la quantité prise**. Le montant se calcule tout seul, entre en stock,
et vient s'ajouter à ce qu'on devra au fournisseur. On peut saisir plusieurs fois par jour
(deux passages du livreur) : les prises s'additionnent, et la page rappelle ce qui a déjà
été pris aujourd'hui.

Le prix convenu se règle dans la fiche article (*Stock → Gérer*). Si le boulanger change
son prix, on le change là, une fois.

**Ajouter un fournisseur à la prise du jour** : sur la même page, le bloc
« Ajouter un article à la prise du jour » suffit. On choisit l'article, on tape le nom du
nouveau fournisseur (ou on prend un existant), le prix convenu, et c'est fait — pas besoin
de passer par deux écrans. C'est ainsi qu'on ajoute le poulet, le pain tacos, le gaz :
plus il y a d'articles suivis comme ça, plus le contrôle est précis.

**Corriger une erreur de saisie** : dans « Derniers achats », chaque ligne a
*Corriger la quantité* et *Supprimer*. Une correction refait le calcul complet —
stock, dépense et dette du fournisseur bougent ensemble. Une suppression efface
la saisie comme si elle n'avait jamais eu lieu, y compris dans le contrôle matière.
C'est important : corriger à la main dans un seul des trois endroits créerait un
écart qu'on chercherait pendant des heures en fin de mois.

### Cas 1 — payé à la fin : le pain arabe (Lassana Camara)

Mode d'achat habituel : **À crédit**. Prix convenu : **40 MRU le paquet**.

Chaque jour, une seule saisie : le nombre de paquets pris. Puis, à tout moment,
**Fournisseurs → Lassana Camara** donne trois choses :

- **Total pris ce mois** : la quantité totale (par exemple 95 paquets) et le montant.
  C'est exactement ce qu'il doit facturer ;
- **Détail jour par jour** : chaque prise, sa date, sa quantité, son prix unitaire.
  C'est ce tableau qu'on lit à côté de sa facture, ligne par ligne ;
- **À payer (total dû)** : ce qu'on lui doit aujourd'hui.

Un écart avec sa facture a toujours une cause : soit une prise n'a pas été saisie
(cherche le jour manquant dans le détail), soit il facture plus qu'il n'a livré.
Sans ce tableau, on ne peut que le croire sur parole — c'est ainsi qu'on a payé
2 000 pains pour 1 300 kebabs.

Le règlement s'enregistre depuis le relevé : l'argent sort, la dette tombe à zéro.
Ne jamais ressaisir la facture comme une dépense : la marchandise a déjà été comptée à
chaque prise, ce serait la compter deux fois.

### Cas 2 — payé d'avance : la viande hachée (Fabe Cissé)

2 kg par jour à **220 MRU le kilo**, soit **440 MRU par jour**, réglés en une fois au
début du mois.

1. **Début du mois** : *Fournisseurs → Fabe Cissé → Payer*. Le montant du mois est déjà
   proposé (440 × nombre de jours). L'argent sort une seule fois ; le solde passe en
   **avance**, affiché en vert.
2. **Chaque jour** : menu **Achats**, bloc **Prise du jour**. La quantité habituelle
   (2 kg) est déjà remplie : il n'y a qu'à valider. Les 440 MRU sont imputés à Fabe Cissé,
   donc l'avance baisse d'autant.
3. **Fin du mois** : l'avance doit être proche de zéro. Si elle est encore grosse, des
   livraisons n'ont pas été saisies (ou n'ont pas été livrées — à réclamer). Si le solde
   est repassé en **dette**, c'est qu'on a pris plus que le mois payé.

C'est la même logique dans les deux cas : la marchandise est toujours enregistrée à la
prise, sur le compte du fournisseur. Seul le moment du paiement change.

---

## 3. Écrire les recettes (à faire une fois)

Menu **Produits → Modifier → Recette**. Exemples :

- Kebab = 1 pain arabe + 0,15 kg de poulet
- Tacos = 1 pain tacos + 0,12 kg de poulet
- Poulet braisé = 0,5 poulet

La recette est ce qui permet au système de dire : « 1 300 kebabs vendus = 1 300 pains
consommés ». Sans recette, le stock ne bouge pas à la vente et le contrôle est aveugle.

Vérifier une recette est simple : peser ce qui part réellement dans cinq portions, puis
prendre la moyenne. Une recette fausse de 20 % fausse toute la marge.

---

## 4. La routine quotidienne (10 minutes)

**À la réception de la marchandise (gérant ou admin)**
1. Compter devant le livreur, puis saisir dans **Achats** → **Prise du jour** :
   seulement la quantité. Le prix, le fournisseur et le mode de règlement viennent de
   la fiche article. Pour un achat ponctuel (gaz, emballages), le formulaire complet
   en dessous reste là.
2. Garder le bon de livraison. En fin de mois, comparer la facture du boulanger au total
   des achats saisis : **les deux doivent être identiques**. C'est ce contrôle-là qui
   aurait montré l'écart sur les 2 000 pains.

**À la clôture (caissier)**
3. Menu **Caisse → faire le comptage du soir** : compter les articles clés et saisir
   les quantités réelles, **dans l'unité de l'article** (paquets pour le pain arabe,
   kg pour la viande). L'écart du jour s'affiche immédiatement, valorisé en MRU.
4. Clôturer la caisse et noter les petites dépenses dans l'onglet **Dépenses**.

**Le lendemain matin (gérant)**
5. Ouvrir **Rapports → Contrôle matière** et regarder la colonne « Manquant ».

---

## 5. Lire le contrôle matière

L'équation est simple :

> **Stock de début + Acheté − Vendu (par les recettes) = Stock attendu**
> **Stock attendu − Stock compté = Manquant**

Seuils à retenir :

| Taux de perte | Lecture | Action |
|---|---|---|
| 0 – 2 % | Normal (chutes, pertes de cuisson) | Rien à faire |
| 2 – 5 % | À surveiller | Vérifier les recettes et les repas du personnel |
| > 5 % | Anomalie | Enquêter le jour même |

Causes les plus fréquentes, dans l'ordre :
1. **Ventes non tapées** (servies sans passer en caisse) — la plus courante ;
2. repas du personnel et offerts non déclarés ;
3. recette fausse (portions trop généreuses) ;
4. casse ou péremption non déclarée ;
5. vol.

Pour distinguer « vente non tapée » de « vol » : si le pain manque **et** que l'argent de
la caisse est juste, la marchandise est partie sans passer en caisse. Si le pain manque
**et** que la caisse est en moins, il y a un problème d'argent en plus.

---

## 6. Le rituel du lundi (30 minutes)

1. **Rapports → Contrôle matière**, période = la semaine écoulée.
2. Noter les trois articles au plus fort taux de perte et la valeur en MRU.
3. **Rapports → Mensuel**, tableau « Tous les produits vendus » : classer par marge,
   pas par quantité. Un produit très vendu mais à faible marge occupe la cuisine pour rien.
4. Décider **une seule action** par semaine (une portion à corriger, un fournisseur à
   discuter, une règle à rappeler à l'équipe) et vérifier le lundi suivant si le taux a baissé.

---

## 6 bis. Le budget et le fonds de roulement

Menu **Rapports → Trésorerie** :

- **Argent entré / sorti / résultat du mois** : ce qui reste réellement ;
- **Position nette** (le chiffre à regarder) = argent disponible − dettes fournisseurs
  − salaires impayés. L'argent disponible vient du menu **Soldes**, où l'on saisit une
  fois par semaine la caisse et les comptes Bankily, Masrivi et Sedad ;
- **Fonds de roulement avec le stock** : le même calcul en ajoutant la valeur du stock.
  Il est marqué « estimation » tant que les comptages ne sont pas réguliers, avec la
  date du dernier comptage. Ne pas décider sur ce chiffre avant deux semaines de
  comptages quotidiens ;
- **Plafonds par catégorie** : fixer un montant par mois (achats, salaires, gaz…).
  La barre passe au rouge dès le dépassement.

Comment fixer les plafonds la première fois : prendre les dépenses réelles du mois
précédent, catégorie par catégorie, et viser 5 % de moins sur les postes où tu sais
qu'il y a du gaspillage. Ne pas tout serrer d'un coup.

Menu **Employés et salaires** : chaque employé, sa fonction, son salaire et le statut
**payé / partiel / non payé** pour le mois. Ce qui reste à payer est automatiquement
déduit du fonds de roulement, pour ne pas croire qu'on est riche le 25 du mois.

---

## 6 quater. L'enveloppe du mois

Menu **Rapports → Enveloppe du mois**. Au début du mois, tu fixes l'argent de travail
(par exemple 200 000 MRU). Ensuite :

- les **sorties** (achats payés, factures fournisseurs, salaires) la vident ;
- les **ventes** la reconstituent ;
- ce qui **dépasse la dotation** est du surplus : tu peux le mettre de côté sans
  fragiliser le mois suivant.

Le vrai signal n'est pas le reste, c'est la **vitesse** : deux barres comparent le
pourcentage dépensé au pourcentage du mois écoulé. Si les dépenses passent devant le
temps de plus de 10 points, l'alerte apparaît ici **et** sur le tableau de bord. La page
donne aussi la dépense moyenne par jour, la projection de fin de mois et le nombre de
jours que le reste peut encore couvrir.

**Mettre de côté** : quand tu sors réellement l'argent (coffre, autre compte), enregistre-le
dans « Mettre de l'argent de côté ». Ce n'est pas une dépense — la richesse ne disparaît
pas — mais l'argent quitte l'exploitation et la caisse si c'est en espèces. La réserve
cumulée s'affiche en face du bénéfice du mois.

Comment fixer la dotation : prendre la moyenne des sorties des deux ou trois derniers
mois et ajouter 10 % de marge. Trop petite, tu es bloqué en fin de mois ; trop grande,
tu immobilises de l'argent qui pourrait dormir en réserve.

---

## 6 ter. Suivre les achats

Menu **Rapports → Achats** : total du jour, de la semaine ou du mois, avec le détail
**par jour, par fournisseur et par article**. La colonne « pris à crédit » montre ce qui
reste à payer. C'est le rapport à ouvrir avant de recevoir une facture.

---

## 7. Les indicateurs à suivre

| Indicateur | Où | Cible |
|---|---|---|
| Coût matière | Contrôle matière : achats ÷ ventes | 30 – 35 % du chiffre d'affaires |
| Taux de perte par article | Contrôle matière | < 5 % |
| Écart de caisse | Caisse (historique) | < 1 % des ventes espèces |
| Marge par produit | Rapport mensuel | Connaître les 3 meilleurs et les 3 pires |
| Facture fournisseur vs achats saisis | Fournisseurs → relevé du mois | Écart nul |
| Avance viande hachée en fin de mois | Fournisseurs → Fabe Cissé | Proche de zéro |
| Jours de prise saisis dans le mois | Fournisseurs → relevé, colonne « Jours de prise » | Autant que de jours ouverts |
| Position nette | Trésorerie | Positive, et en hausse |
| Enveloppe consommée vs mois écoulé | Enveloppe du mois | Dépenses derrière le temps |
| Réserve cumulée | Enveloppe du mois | En hausse chaque mois |
| Relevé des soldes | Soldes | Une fois par semaine, même jour |
| Salaires payés | Employés et salaires | 100 % en fin de mois |

---

## 8. Ce que ça aurait changé sur le mois du pain

Avec cette méthode :

- jour 1 : 200 pains achetés, 120 kebabs vendus, 50 pains comptés au lieu de 80
  → **30 pains manquants (120 MRU)** affichés le soir même ;
- jour 2 : même écart → on sait que le problème est quotidien, pas accidentel ;
- jour 3 : on agit.

Au lieu de découvrir 2 800 MRU de perte un mois plus tard, on la stoppe à 240 MRU.
