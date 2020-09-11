<?php

namespace App\Console;

use Yii;
use App\Model\Document;
use App\Service\DocumentService;

class DocumentManagerController extends \yii\console\Controller
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
            return parent::runAction($id, $params);
        });
    }

    public function actionClose($documentId, $closingType)
    {
        $this->documentService->closeDoc((int)$documentId, (int)$closingType);
    }

    public function actionCloseByProcedure($documentId, $closingType)
    {
        $this->documentService->closeDocByProcedure((int)$documentId, (int)$closingType);
    }

    // This action emulates SQL queries as when user opens document page during real work
    public function actionEmulateDocumentPage($documentId)
    {
        $documentId = (int)$documentId;
        $document = Document::find()->with(['positions', 'type'])->where(['id' => $documentId])->one();
        echo count($document->positions) . "\n";
    }
}
