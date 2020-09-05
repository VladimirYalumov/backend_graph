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
        try{
        } catch (Exception $e) {
            return $this->asJson(['success' => false, 'msg' => $e->errorInfo[2]]); 
        }
        return $this->asJson(['success' => true]);
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

        $node = Node::find()->select(['id'])->where(['name' => $get['name']])->one();

        try {           
            $this->addRelation((int)$get['from_id'], $node['id'], (int)$get['price']); 
        } catch (Exception $e) {
            $node->delete();
            return $this->asJson(['success' => false, 'msg' => $e->errorInfo[2]]); 
        } 
        
        return $this->asJson(['success' => true]);      
    }

    public function actionDeleteNode(){

        $request = Yii::$app->request;

        Yii::$app->response->format = Response::FORMAT_JSON;

        try{
            if (!$this->deleteNode($request->getBodyParam('id')))
            return $this->asJson(['success' => false, 'msg' => 'Такой вершины не существует']);
        } catch (Exception $e) {
            return $this->asJson(['success' => false, 'msg' => $e->errorInfo[2]]); 
        }
        return $this->asJson(['success' => true]);
    }

    public function actionAddRelation(){
        $request = Yii::$app->request;
        $get = $request->get();

        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $this->addRelation((int)$get['from'], (int)$get['to'], (int)$get['price']);
        } catch (Exception $e) {
            return $this->asJson(['success' => false, 'msg' => $e->errorInfo[2]]); 
        }

        return $this->asJson(['success' => true]);
    }

    public function actionGetGraph(){

        $temporaryGraph = $this->getGraph();
        $graph = [];
        $nodes = $temporaryGraph->nodes;

        foreach($nodes as $node){
            $graph[$node->getId()] = $node->getConnections();
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
        
        $graphForDijkstraAlgorithm->setStartingNode($start_node);
        $graphForDijkstraAlgorithm->setEndingNode($end_node);

        Yii::$app->response->format = Response::FORMAT_JSON;

        try{
            $shortestWay = $graphForDijkstraAlgorithm->getLiteralShortestPath();
        } catch (Exception $e) {
            return $this->asJson(['success' => false, 'msg' => $e->errorInfo[2]]); 
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
