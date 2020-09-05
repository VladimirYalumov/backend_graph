<?
namespace app\helpers;

// Собственно говоря алгоритм Дейкстры
class Dijkstra {

    private $startingNode;
    private $endingNode;
    private $graph;
    private $paths = [];
    private $solution = false;

    public function __construct($graph) {
        $this->graph = $graph;
    }

    public function getDistance() {
        return $this->getEndingNode()->getPotential();
    }

    public function getEndingNode() {
        return $this->endingNode;
    }

    public function getLiteralShortestPath() {
        $path = $this->solve();
        $literal = [];
        foreach ( $path as $k => $p ) {
            $literal[$k] = $p->getId();
        }

        return $literal;
    }

    public function getShortestPath() {
        $path = [];
        $node = $this->getEndingNode();
        while ( $node->getId() != $this->getStartingNode()->getId() ) {
            $path[] = $node;
            $node = $node->getPotentialFrom();
        }
        $path[] = $this->getStartingNode();
        return array_reverse($path);
    }

    public function getStartingNode() {
        return $this->startingNode;
    }

    public function setEndingNode($node) {
        $this->endingNode = $node;
    }

    public function setStartingNode($node) {

        $this->paths[] = array($node);
        $this->startingNode = $node;
    }

    public function solve() {

        $this->calculatePotentials($this->getStartingNode());
        $this->solution = $this->getShortestPath();
        return $this->solution;
    }

    protected function calculatePotentials($node) {

        $connections = $node->getConnections();
        $sorted = array_flip($connections);
        krsort($sorted);
        foreach ( $connections as $id => $distance ) {
            $v = $this->getGraph()->getNode($id);
            $v->setPotential($node->getPotential() + $distance, $node);
            foreach ( $this->getPaths() as $path ) {
                $count = count($path);
                if ($path[$count - 1]->getId() === $node->getId()) {
                    $this->paths[] = array_merge($path, array($v));
                }
            }
        }

        $node->markPassed();
 
        foreach ( $sorted as $id ) {
            $node = $this->getGraph()->getNode($id);
            if (! $node->isPassed()) {
                $this->calculatePotentials($node);
            }
        }
    }

    protected function getGraph() {
        return $this->graph;
    }

    protected function getPaths() {
        return $this->paths;
    }

    protected function isSolved() {
        return ( bool ) $this->solution;
    }
}