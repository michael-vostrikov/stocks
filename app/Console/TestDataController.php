<?php

namespace App\Console;

use Yii;
use App\Model\Document;
use App\Model\DocumentPosition;
use App\Model\DocumentType;
use App\Model\CurrentStock;

class TestDataController extends \yii\console\Controller
{
    public function actionAdd($documentCount = 10000)
    {
        $db = Yii::$app->db;

        $db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        $transaction = $db->beginTransaction();

        $documentTypes = [
            ['id' => 1, 'credit_type' => 1, 'name' => 'Document type 1'],
            ['id' => 2, 'credit_type' => -1, 'name' => 'Document type 2'],
            ['id' => 6, 'credit_type' => 1, 'name' => 'ЗПС'],
            ['id' => 8, 'credit_type' => 1, 'name' => 'План производства'],
        ];
        $db->createCommand()->batchInsert(DocumentType::tableName(), array_keys($documentTypes[0]), $documentTypes)->execute();

        $documents = [];
        for ($n = 0; $n < $documentCount; $n++) {
            $data = [
                'id' => $n + 1,
                'document_type_id' => $documentTypes[rand(0, count($documentTypes) - 1)]['id'],
                'organization_id_address' => rand(1, 100),
                'organization_id_ur' => rand(101, 200),
                'status' => 0,
            ];
            $documents[] = $data;
        }
        $db->createCommand()->batchInsert(Document::tableName(), array_keys($documents[0]), $documents)->execute();

        $position_id = 1;
        foreach ($documents as $n => $documentData) {
            if ($n % 100 === 99) {
                echo ($n + 1) . "\r";
            }

            $positionCount = rand(70, 150);
            $positions = [];
            for ($i = 0; $i < $positionCount; $i++) {
                do {
                    $material_id = rand(1, 1000);
                } while (isset($positions[$material_id]) && count($positions) < 1000);

                $data = [
                    'id' => $position_id,
                    'document_id' => $documentData['id'],
                    'material_id' => $material_id,
                    'cnt' => rand(0, 100),
                ];
                $positions[$material_id] = $data;
                $position_id++;
            }
            $fields = array_keys(reset($positions));
            $db->createCommand()->batchInsert(DocumentPosition::tableName(), $fields, $positions)->execute();
        }
        echo "\n";

        $transaction->commit();
        $db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
    }

    public function actionDelete()
    {
        CurrentStock::deleteAll();
        DocumentPosition::deleteAll();
        Document::deleteAll();
        DocumentType::deleteAll();
    }
}
