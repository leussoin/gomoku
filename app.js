$(document).ready(function () {

    var colone = 0;
    var ligne = 0;
    var color = '';
    var ressourceId = '';

    // la construction du plateau de jeu + les pions
    for (let i = 0; i < 144; i++) {
        if (i % 13 === 0) {
            ligne++;
            colone = 0;
        } else {
            $("<div><button class='button' data-coordinates = " + ligne + '-' + colone + " ></button></div>").appendTo('.plateau');
        }
        colone++;
    }

    // connexion au websocket
    var conn = new WebSocket('ws://localhost:8080');
    conn.onopen = function (e) {
        console.log("Connection established!");

    };

    // boite de dialogue de la selection du joueur
    swal({
        title: "Choisir une couleur",
        buttons: ["Blanc", "Noir"],
    })
        .then((blackChoosen) => {
            if (blackChoosen) {
                // todo = joueur noir choisi on ne peux plus choisir les noirs
                conn.send("noir");
                // swal("Patience votre adversaire prépare son coup");
                $(".button").attr("disabled", true);
                color = 'noir';
            } else {
                //  todo = joueur blanc chosi on ne peux plus choisir les blancs
                conn.send("blanc");
                color = 'blanc';
            }
            // au moment ou on selectionne les couleurs noir joue au centre
            $('button[data-coordinates="6-6"]').css("background-color", "black");
        });


    // si l'emplacement est rose alors je peux jouer et j'attribue la couleur selon le joueur
    $(".button").click(function () {
        if ($(this).css("background-color") == "rgb(255, 0, 255)") {
            if (color === 'noir') {
                $(this).css("background-color", "black");
            } else {
                $(this).css("background-color", "white");
            }
            // swal("Patience votre adversaire prépare son coup");
            // permet de vérouiller de plateau de jeu le temps que l'autre joueur joue
            $(".button").attr("disabled", true);

            // fonction pour communiquer avec mon serveur
            conn.send($(this).data("coordinates"), color);
        }

    });


    // cette fonction permet de récupérer les messages provenant du websocket
    // la configuration du serveur de websocket est faite de telle maniére à ce que cette fonction corresponde à
    // "a vous de jouer"
    conn.onmessage = function (e) {
        console.log(e.data);
        var coord = e.data.split('|');
        ressourceId = coord[1];
        // déverouille le plateau de jeu pour que le joueur puisse placer un pion
        $(".button").attr("disabled", false);

        // ma logique est: quand je passe dans cette condition c'est que l'autre à joué
        if (color === "blanc") {
            $('button[data-coordinates="' + coord[0] + '"]').css("background-color", "black");
        }
        if (color === "noir") {
            $('button[data-coordinates="' + coord[0] + '"]').css("background-color", "white")
        }

    }

});
