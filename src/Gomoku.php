<?php

namespace MyApp;

class Gomoku
{

    protected $pdo;

    public function __construct() {

        $this->pdo = $this->connexion();
    }

    public function connexion() {
        $dsn = 'mysql:dbname=gomoku;host=127.0.0.1';
        $user = 'root';
        $password = '';

        try {
            $pdo = new \PDO($dsn, $user, $password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            echo 'Connexion échouée : ' . $e->getMessage();
        }
    }

    /**
     * Cette fonction permet de sauvegarder le premier coup automatiquement généré
     * @param $color
     * @param $ressourceId
     * @return bool
     */
    public function beginGame($color, $ressourceId) {
        $sql = "INSERT INTO clients (color, ressource_id) VALUES (?,?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$color, $ressourceId]);

    }


    /**
     * Cette fonction permet de sauvegarder en DB chaque coup joué par les joueurs
     * @param $ressourceId
     * @param $coordinates
     */
    public function move($ressourceId, $coordinates) {
        $pdo = $this->connexion();
        $sql = "INSERT INTO moves (fk_ressource_id, coordinates) VALUES (?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ressourceId, $coordinates]);
        //  on peut verifier le retour de la fonction pour catcher des potentielles erreurs (log, avertissement pour l'user)
    }

    /**
     * Cette fonction me permet de récupérer les coups joués par les joueurs
     * @param null $ressourceId
     * @return array
     */
    public function shotsPlayed($ressourceId = null) {
        $pdo = $this->connexion();
        if ($ressourceId) {
            $sth = $pdo->prepare("SELECT coordinates FROM `moves` WHERE `fk_ressource_id` = " . $ressourceId);
        } else {
            $sth = $pdo->prepare("SELECT coordinates FROM moves INNER JOIN clients ON clients.ressource_id = moves.fk_ressource_id where clients.color = 'blanc'");
        }
        $sth->execute();
        $coordinates = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $moves = array();
        foreach ($coordinates as $move) {
            $moves[] = $move['coordinates'];
        }
        return $moves;

    }


    /**
     * Quand un joueur se déconnecte je supprime sa partie en purgeant la DB
     * @param $ressourceId
     */
    public function disconnected($ressourceId) {

        $this->pdo->exec('DELETE FROM `moves` WHERE `fk_ressource_id` = ' . $ressourceId);
        $this->pdo->exec('DELETE FROM `clients` WHERE `ressource_id` = ' . $ressourceId);
        //  on peut verifier le retour de la fonction pour catcher des potentielles erreurs (log, avertissement pour l'user)

    }


    /**
     * @param $array
     * Cette fonction permet de verifier si un des joueurs à gagné
     */
    public function checkWin($array) {

        $win = 1;
        $numFunction = 1;
        $result = false;

        foreach ($array as $move) {

            $arr = explode('-', $move);

            for ($i = 0; $i <= 4; $i++) {

                if ($numFunction === 1) {
                    // condition de victoire horizontale buguée : fonctionne uniquement si on place le dernier coup sur la premiére case du pattern
                    $result = $this->testWin($arr[0], $arr[1] + $i, $array); // 1-2  1-3 1-4 1-5
                }
                // condition de victoire verticale buguée : fonctionne uniquement si on place le dernier coup sur la premiére case du pattern
                if ($numFunction === 2) {
                    $result = $this->testWin($arr[0] + $i, $arr[1], $array);
                }
                // condition de victoire diagonale ascendante buguée : fonctionne uniquement si on place le dernier coup sur la premiére case du pattern
                if ($numFunction === 3) {
                    $result = $this->testWin($arr[0] + $i, $arr[1] + $i, $array);
                }
                // condition de victoire diagonale descendante buguée : renvoie la victoire aprés 4 coups
                if ($numFunction === 4) {
                    $result = $this->testWin($arr[0] + $i, $arr[1] - $i, $array);
                }

                if ($result === true) {
                    $win++;
                } else {
                    $win = 1;
                }

                if ($win === 5) {
                    var_dump("C'est la victoire"); // todo: renvoyer au front un message disant que la personne qui vient de jouer à gagné
                    break;
                }
                $result = false;

            }
            $numFunction++;
        }

    }

    /**
     * Cette fonction sert à verifier l'alignement des coups de chaque joueur
     * @param $ligne
     * @param $colonne
     * @param $array
     * @return bool
     */
    public function testWin($ligne, $colonne, $array) {

        if (in_array($ligne . '-' . ($colonne), $array)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Cette fonction renvoie une liste de coordonnées de coups jouables par chaqu'un des joueurs, seuls les coups sur des positions adacentes
     * et sur des positions non prises sont autorisés
     * @param $ressourceId
     * @return array
     */
    public function suggestion($ressourceId) {

        //je prends tous les points déjà joué par l'adversaire
        $pdo = $this->connexion();
        $sth = $pdo->prepare("SELECT coordinates FROM `moves` WHERE `fk_ressource_id` = " . $ressourceId);
        $sth->execute();
        $opponentCoordinates = $sth->fetchAll(\PDO::FETCH_ASSOC);

        //tous les points déjà joués
        $sth = $pdo->prepare("SELECT coordinates FROM `moves`");
        $sth->execute();
        $occupatedCoordinates = $sth->fetchAll(\PDO::FETCH_ASSOC);


        $availlableCoord = array();
        $realAvaillableCoord = array();

        //je prends tous les coups disponibles (8 points adjacents)
        foreach ($opponentCoordinates as $coord) {

            $aCoord = explode('-', $coord['coordinates']);
            $availlableCoord[] = ($aCoord[0] - 1) . "-" . ($aCoord[0] + 1);
            $availlableCoord[] = ($aCoord[0]) . "-" . ($aCoord[0] + 1);
            $availlableCoord[] = ($aCoord[0] + 1) . "-" . ($aCoord[0] + 1);
            $availlableCoord[] = ($aCoord[0] - 1) . "-" . ($aCoord[0]);
            $availlableCoord[] = ($aCoord[0] + 1) . "-" . ($aCoord[0]);
            $availlableCoord[] = ($aCoord[0] - 1) . "-" . ($aCoord[0] - 1);
            $availlableCoord[] = ($aCoord[0]) . "-" . ($aCoord[0] + 1);
            $availlableCoord[] = ($aCoord[0] + 1) . "-" . ($aCoord[0] - 1);
        }

        $availlableCoord = array_unique($availlableCoord);

        // je compare chaqu'un des emplacement adjacent de mon adversaire avec les emplacements déjà selectionnés pour n'avoir que
        // les position réélement valides
        foreach ($availlableCoord as $coord) {
            if (!in_array($coord, $occupatedCoordinates)) {
                $realAvaillableCoord[] = $coord;
            }
        }

        return $realAvaillableCoord;

    }

}