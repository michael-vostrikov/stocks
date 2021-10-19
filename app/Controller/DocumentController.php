<?php
namespace App\Controller;

use App\Model\Document;
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

    public function actionClose($id, $type = null)
    {
        if ($id === 'random') $id = rand(1, 1000);

        /** @var Document $document */
        $document = Document::find()->where(['id' => $id])->forUpdate()->with(['positions', 'type'])->one();
        if (empty($type)) {
            $type = ($document->status === 1 ? -1 : 1);
        }

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $this->documentService->closeDoc($document, (int)$type);

        return ['success' => true];
    }

    public function actionCloseByProcedure($id, $type)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $this->documentService->closeDocByProcedure((int)$id, (int)$type);

        return ['success' => true];
    }
}
