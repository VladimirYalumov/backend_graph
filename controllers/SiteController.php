<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\web\Request;
use yii\filters\VerbFilter;
use app\models\Node;
use app\models\Connections;
use app\helpers\GraphHelper;
use app\helpers\Dijkstra;
use yii\base\Exception;

class SiteController extends Controller
{
    public function actionTest(){
        Yii::$app->controller->enableCsrfValidation = false;

        $request = Yii::$app->request;

        return $this->asJson(['success' => false, 'msg' => $request->getBodyParam('from')]);
    }

    public function actionAddNode(){
        $request = Yii::$app->request;
        $get = $request->get();

        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $this->addNode($get['name']);
        } catch (Exception $e) {
            return $this->asJson(['success' => false, 'msg' => $e->errorInfo[2]]); 
        }
        
        return $this->asJson(['success' => true]);      
    }

    public function actionDeleteNode(){

        $raw_data = json_decode(Yii::$app->request->getRawBody());

        Yii::$app->response->format = Response::FORMAT_JSON;

        try{
            if (!$this->deleteNode((int)$raw_data->{'id'}))
            return $this->asJson(['success' => false, 'msg' => 'Такой вершины не существует']);
        } catch (Exception $e) {
            return $this->asJson(['success' => false, 'msg' => $e->errorInfo[2]]); 
        }
        return $this->asJson(['success' => true]);
    }

    public function actionAddRelation(){

        $raw_data = json_decode(Yii::$app->request->getRawBody());

        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $this->addRelation(
                (int)$raw_data->{'from'}, 
                (int)$raw_data->{'to'}, 
                (int)$raw_data->{'price'}
            );
        } catch (Exception $e) {
            return $this->asJson(['success' => false, 'msg' => $e->errorInfo[2]]); 
        }

        return $this->asJson(['success' => true]);
    }

    public function actionGetGraph(){

        $temporaryGraph = $this->getGraph();
        $graph = [];
        $nodes = $temporaryGraph->nodes;

        $i = 0;
        foreach($nodes as $node){
            $graph[$i]['id'] = (int)$node->getId();
            $j = 0;
            foreach ($node->getConnections() as $key => $value) {
                $graph[$i]['connections'][$j]['node'] = $key;
                $graph[$i]['connections'][$j]['price'] = $value;
                $j ++;
            }
            if(!$graph[$i]['connections']){
                $graph[$i]['connections'] = [];
            }
            $i++;
        }

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return $graph;    
    }

    public function actionGetNode(){
        $request = Yii::$app->request;
        $get = $request->get();

        try{
            $node = Node::find()->where(['id' => $get['id']])->one();
        } catch (Exception $e) {
            return $this->asJson(['success' => false, 'msg' => $e->errorInfo[2]]); 
        }

        return $this->asJson(['success' => true, 'node' => $node]); 
    }

    public function actionGetRelationsByFrom(){
        $request = Yii::$app->request;
        $get = $request->get();

        try{
            $node = Connections::find()->where(['id_from' => $get['id_from']])->all();
        } catch (Exception $e) {
            return $this->asJson(['success' => false, 'msg' => $e->errorInfo[2]]); 
        }

        return $this->asJson(['success' => true, 'connections' => $node]); 
    }

    public function actionWay(){

        $request = Yii::$app->request;
        $get = $request->get();

        $from = (int)$get['from'];
        $to = (int)$get['to'];

        $graph = $this->getGraph();

        $graphForDijkstraAlgorithm = new Dijkstra($graph);

        $start_node = $graph->getNode($from);
        $end_node = $graph->getNode($to);
        
        if ((!$start_node) || (!$end_node)){
            return $this->asJson(['success' => false, 'msg' => 'Вы ввели несуществующий Node']);
        } 

        $graphForDijkstraAlgorithm->setStartingNode($start_node);
        $graphForDijkstraAlgorithm->setEndingNode($end_node);

        Yii::$app->response->format = Response::FORMAT_JSON;

        $shortestWay = $graphForDijkstraAlgorithm->getLiteralShortestPath();

        if(!$shortestWay){
            return $this->asJson(['success' => false, 'msg' => 'Из '.$from.' в '.$to.' попасть нельзя']);
        } 

        return $this->asJson(['success' => true, 'way' => $shortestWay]);
    }

    protected function addNode($name){
        $node = new Node();
        $node->name = $name;
        $node->save();        
        return;
    }

    protected function deleteNode($id){
        $node = Node::find()->where(['id' => $id])->one();

        if ($node){
            Connections::deleteAll(['id_from' => $id]);
            Connections::deleteAll(['id_to' => $id]);
            $node->delete();
            return true;
        } else {
            return false;
        }
    }

    protected function addRelation($from, $to, $price){

        $connection = new Connections();

        $connection->id_from = $from;
        $connection->id_to = $to;
        $connection->price = $price;

        $connection->save();

        return;
    }

    protected function getGraph(){

        $nodes = Node::find()->all();
        $connections = Connections::find()->all();

        $nodesArray = [];
        $connectionsArray = [];

        foreach ($nodes as $key => $node) {
           $nodesArray[$key] =  $node->id;
        }

        foreach ($connections as $key => $connection) {
           $connectionsArray[$key]['from'] =  $connection->id_from;
           $connectionsArray[$key]['to'] =  $connection->id_to;
           $connectionsArray[$key]['price'] =  $connection->price;
        }

        $graph = new GraphHelper($nodesArray, $connectionsArray);

        return $graph;
    }
}
