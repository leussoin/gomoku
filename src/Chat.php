<?php

namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
Use MyApp\Gomoku as Gomoku;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $gomoku;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->gomoku = new Gomoku();
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {

        // je reçoit la couleur sélectionnée uniquement lors du choix de la couleur en JS de maniére à savoir qui est blanc et qui est noir puisque le choix se fait sur le front
        if ($msg === "blanc" || $msg === "noir") {
            $this->gomoku->beginGame($msg, $from->resourceId);
            if ($msg === "noir") {
                //  todo: calculer proprement le tableau pour obtenir les 19 intersections sur le front et placer le milieu
                // le premier coup étant toujours le noir, je place donc le premier coup au "milieu"
                $this->gomoku->move($from->resourceId, "6-6");
            }


            //sinon je reçoit les coordonnées du coup joué et l'ID du joueur (identifié par son ID ressource)
        } else {
            $this->gomoku->move($from->resourceId, $msg);
            // je récupére la liste des coups du joueur
            $moves = $this->gomoku->shotsPlayed($from->resourceId);

            if (isset($moves)) {

                $this->gomoku->checkWin($moves);
                // envoyer un message à tous:  c'est fini $couleur à gagné !
                // arréter la partie

            }
        }

        // je récupére l'ID ressource pour différiencer les joueurs
        $msg .= "|" . strval($from->resourceId);

        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n", $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');


        foreach ($this->clients as $client) {
            if ($from !== $client) {
                //todo: sérialization du tableau des suggestions (emplacement adjacent a ceux de l'adversaire) et envoi au JS pour suggerer les emplacements disponibles
                $suggestions = $this->gomoku->suggestion($client->resourceId);
                var_dump($suggestions);
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }

        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        $this->gomoku->disconnected($conn->resourceId);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }


}