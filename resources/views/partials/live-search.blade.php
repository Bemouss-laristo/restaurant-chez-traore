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
        return Object.assign({
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
        }, extra);
    }
</script>
