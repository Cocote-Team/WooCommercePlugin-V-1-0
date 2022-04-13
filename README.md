# Plugin Cocote pour WooCommerce 3.4 à 3.5

Ce module communique avec Cocote.com et génére un flux XML de vos offres produits.

Pour installer ce module:

1) Télécharger https://github.com/Cocote-Team/WooCommercePlugin/raw/master/woo-cocotefeed.zip


2) Transfert et copie des fichiers


Aller sur votre Admin WordPress : Extensions > Ajouter et cliquer sur 'Téléverser une extension'

Faire un Drag'n'Drop du fichier téléchargé woo-cocotefeed.zip.


3) Configurer le Module


Cliquer sur l'élément 'Cocote Feed' au sein de l'item 'WooCommerce' dans le menu de gauche de l'Admin.
                   
Renseigner vos clés (diponibles depuis https://fr.cocote.com/mon-compte/ma-boutique/script-de-suivi ) 

Cliquer sur enregistrer.

Votre url flux est désormais disponible.

Cot Cot Cot!

4) Configurer et activer les crons

Pour faire fonctionner les crons sur WordPress il faut faire les choses suivantes :

- Télécharger le module cronjobs https://github.com/Cocote-Team/WooCommercePlugin/raw/master/wp-control.zip
- Aller sur votre Admin WordPress : Extensions > Ajouter et cliquer sur 'Téléverser une extension'
- Faire un Drag n Drop du fichier téléchargé wp-control.zip.
- Une fois installer, au sein de la liste des extensions cliquer sur le lien "Évènements" de l'extension **WP Control**
- Au sein de la liste des évènements vous devriez pouvoir trouver l'évènement "woo_cocote". Cet évènement qui est lancé chaque jour à 3h00 permet de regénérer le flux XML de vos produits.
