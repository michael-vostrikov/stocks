<?php
namespace App\Service;

use App\Model\CurrentStock;
use App\Model\Document;
use App\Model\DocumentType;
use App\Model\LogicException;

class DocumentService extends BaseService
{
    // closingType - 1 закрытие, -1 открытие
    public function closeDoc(Document $document, int $closingType)
    {
        if ($document->status === $closingType) {
            throw new LogicException('Документ уже находится в этом статусе.');
        }

        if (in_array($document->document_type_id, [DocumentType::ZPS, DocumentType::PLAN_PROIZVODSTVA])) {
            $notEmptyPositions = array_filter($document->positions, function ($position) {
                return $position->cnt !== 0 && $position->cnt !== null;
            });
            if (count($notEmptyPositions) > 0) {
                throw new LogicException('Документы данного типа нельзя закрывать если есть позиции с не нулевыми количествами.');
            }
        }

        $curStocks = $document->getPositionStocks()->all();
        $newData = [];
        foreach ($document->positions as $position) {
            $newCnt = ($position->cnt ?? 0) * -1 * $closingType * $document->type->credit_type;  // hack with multiplication by business value, be careful!
            $curStock = ($curStocks[$position->material_id] ?? null);

            $newData[] = [
                'organization_id_address' => $document->organization_id_address,
                'organization_id_ur' => $document->organization_id_ur,
                'material_id' => $position->material_id,

                'cnt' => ($curStock === null ? 0 : $curStock->cnt) + $newCnt,
                'reserve' => ($curStock === null ? 0 : $curStock->reserve) + ($document->type->credit_type === 1 ? $newCnt : 0),
            ];
        }
        CurrentStock::batchReplace($newData);

        $document->getPositionStocks()->andWhere(['cnt' => 0, 'reserve' => 0])->delete();

        $document->status = $closingType;
        $document->save();
    }

    public function closeDocByProcedure(int $documentId, int $closingType)
    {
        \Yii::$app->db->createCommand('CALL close_doc(:id, :type)', [':id' => $documentId, ':type' => $closingType])->execute();
    }
}
