<?
namespace app\helpers;

use app\helpers\NodeHelper;

class GraphHelper {

    public $nodes = []; // тут у нас будут все вершины

    public function __construct(array $nodes, array $relations){
        foreach($nodes as $node){
            $temporaryNode = new NodeHelper($node);

            foreach ($relations as $relation) {
                if((int)$relation['from'] == (int)$node){
                    $temporaryNode->addConnect($relation['to'], $relation['price']);
                } 
            }

            $this->nodes[$node] = $temporaryNode;
        }

    }
 
    public function getNode($id) {
        $nodes = $this->getNodes();
        return $nodes[$id];
    }
 
    public function getNodes() {
        return $this->nodes;
    }
}