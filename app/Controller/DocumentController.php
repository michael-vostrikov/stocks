<?php
namespace App\Controller;

use App\Model\LogicException;
use App\Service\DocumentService;
use yii\web\HttpException;

class DocumentController extends \yii\web\Controller
{
    /** @var DocumentService */
    public $documentService;

    public function init()
    {
        parent::init();

        $this->documentService = new DocumentService();
    }

    public function runAction($id, $params = [])
    {
        return $this->documentService->runInTransaction(function () use ($id, $params) {
            try {
                return parent::runAction($id, $params);
            } catch (LogicException $ex) {
                throw new HttpException(400, $ex->getMessage());
            } catch (\yii\db\Exception $ex) {
                throw new HttpException(400, $ex->getMessage());
            }
        });
    }

    public function actionClose($id, $type)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $this->documentService->closeDoc((int)$id, (int)$type);

        return ['success' => true];
    }

    public function actionCloseByProcedure($id, $type)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $this->documentService->closeDocByProcedure((int)$id, (int)$type);

        return ['success' => true];
    }
}
