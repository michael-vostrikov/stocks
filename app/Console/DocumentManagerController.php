<?php

namespace App\Console;

use Yii;
use App\Model\Document;
use App\Model\DocumentType;
use App\Model\CurrentStock;
use RuntimeException;

class DocumentManagerController extends \yii\console\Controller
{
    public function actionClose($documentId, $closingType)
    {
        $transaction = Yii::$app->db->beginTransaction();
        $this->closeDoc((int)$documentId, (int)$closingType);
        $transaction->commit();
    }

    // type - 1 закрытие, -1 открытие
    public function closeDoc(int $documentId, int $closingType)
    {
        /** @var Document $document */
        $document = Document::find()->where(['id' => $documentId])->forUpdate()->with(['positions', 'type'])->one();
        if ($document->status === $closingType) {
            throw new RuntimeException('Документ уже находится в этом статусе.');
        }

        if (in_array($document->document_type_id, [DocumentType::ZPS, DocumentType::PLAN_PROIZVODSTVA])) {
            $exists = $document->getPositions()->andWhere(['AND', ['!=', 'cnt', 0], ['IS NOT', 'cnt', null]])->exists();
            if ($exists) {
                throw new RuntimeException('Документы данного типа нельзя закрывать если есть позиции с не нулевыми количествами.');
            }
        }

        $curStocks = $document->getPositionStocks()->forUpdate()->all();
        $newData = [];
        foreach ($document->positions as $position) {
            $newCnt = ($position->cnt ?? 0) * -1 * $closingType * $document->type->credit_type;  // hack with multiplication by business value, be careful!
            $curStock = ($curStocks[$position->material_id] ?? null);

            $newData[] = [
                'organization_id_address' => $document->organization_id_address, 'organization_id_ur' => $document->organization_id_ur, 'material_id' => $position->material_id,

                'cnt' => ($curStock === null ? 0 : $curStock->cnt) + $newCnt,
                'reserve' => ($curStock === null ? 0 : $curStock->reserve) + ($document->type->credit_type === 1 ? $newCnt : 0),
            ];
        }

        CurrentStock::batchReplace($newData);

        $document->getPositionStocks()->andWhere(['cnt' => 0, 'reserve' => 0])->delete();

        $document->status = $closingType;
        $document->save();
    }

    public function actionCloseByProcedure($documentId, $closingType)
    {
        $transaction = Yii::$app->db->beginTransaction();
        Yii::$app->db->createCommand('CALL close_doc(:id, :type)', [':id' => (int)$documentId, ':type' => (int)$closingType])->execute();
        $transaction->commit();
    }

    // This action emulates SQL queries as when user opens document page during real work
    public function actionEmulateDocumentPage($documentId)
    {
        $documentId = (int)$documentId;
        $document = Document::find()->with(['positions', 'type'])->where(['id' => $documentId])->one();
        echo count($document->positions) . "\n";
    }
}
