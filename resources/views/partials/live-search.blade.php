{{--
    Recherche instantanée, faite dans le navigateur.

    Pourquoi pas côté serveur : chaque lettre tapée voudrait dire un aller-retour
    réseau. Sur une connexion moyenne, la liste arrive toujours en retard d'une ou
    deux lettres — c'est exactement ce qui donne l'impression qu'il faut « taper le
    mot entier puis cliquer ». Ici, les listes tiennent en entier dans la page :
    le filtre est donc immédiat, sans réseau.

    Les accents sont ignorés (« hachee » trouve « hachée ») et chaque mot tapé doit
    être présent, dans n'importe quel ordre (« pain ara » trouve « Pain arabe »).
--}}
<script>
    function liveSearch(extra = {}) {
        const base = {
            search: '',

            normalize(value) {
                return (value ?? '')
                    .toString()
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[̀-ͯ]/g, '');
            },

            /** La ligne correspond-elle à ce qui est tapé ? */
            match(haystack) {
                const needle = this.normalize(this.search).trim();

                if (needle === '') {
                    return true;
                }

                const text = this.normalize(haystack);

                return needle.split(/\s+/).every((word) => text.includes(word));
            },

            /** Nombre de lignes affichées, pour le message « aucun résultat ». */
            get visibleCount() {
                return (this.rows ?? []).filter((row) => this.match(row)).length;
            },
        };

        // Surtout pas Object.assign : il EXÉCUTE les getters de `extra` (« shown »)
        // au moment de la copie, hors du composant, où `this.match` n'existe pas.
        // Le calcul plantait, tout le composant tombait et les lignes restaient
        // cachées — la liste du stock et des produits paraissait vide.
        // On copie donc les propriétés telles quelles, getters compris.
        return Object.defineProperties(base, Object.getOwnPropertyDescriptors(extra));
    }
</script>
