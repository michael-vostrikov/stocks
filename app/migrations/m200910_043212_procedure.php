<?php

use yii\db\Migration;

/**
 * Class m200910_043212_procedure
 */
class m200910_043212_procedure extends Migration
{
    public function up()
    {
        $sql = "
            CREATE FUNCTION raise_error(message VARCHAR(200))
            RETURNS INT DETERMINISTIC
            BEGIN
                DECLARE `error` CONDITION FOR SQLSTATE '45000';
                SIGNAL `error` SET MESSAGE_TEXT = message;
                RETURN 0;
            END
        ";
        $this->execute($sql);

        $sql = "
            CREATE PROCEDURE close_doc(p_doc_id INT, type TINYINT)
            -- type - 1 закрытие, -1 открытие
            BEGIN
                -- Нельзя закрывать ЗПС и План производства
                SET @msg := (SELECT raise_error('Документы данного типа нельзя закрывать если есть позиции с не нулевыми количествами.')
                FROM documents d
                INNER JOIN document_positions dp ON d.id = dp.document_id
                WHERE d.document_type_id IN (6, 8) -- ЗПС и План производства
                AND IFNULL(dp.cnt, 0) <> 0
                AND d.id = p_doc_id)
                ;
                
                SET @msg := (SELECT raise_error('Документ уже находится в этом статусе.')
                FROM documents d
                WHERE d.status = type
                AND d.id = p_doc_id)
                ;
                
                -- Изменим текущие остатки
                INSERT current_stock (material_id, organization_id_address, organization_id_ur, cnt, reserve)
                SELECT dp.material_id, d.organization_id_address, d.organization_id_ur
                , IFNULL(dp.cnt, 0) * -1 * t.credit_type * type cnt
                , IFNULL(dp.cnt, 0) * -1 * IF(t.credit_type = 1, 1, 0) * type reserve
                FROM documents d
                INNER JOIN document_positions dp ON d.id = dp.document_id
                INNER JOIN document_types t ON d.document_type_id = t.id
                WHERE d.id = p_doc_id
                AND t.credit_type IN (1, -1)
                ON DUPLICATE KEY UPDATE current_stock.cnt = current_stock.cnt + IFNULL(dp.cnt, 0) * -1 * t.credit_type * type
                , current_stock.reserve = current_stock.reserve + IFNULL(dp.cnt, 0) * -1 * IF(t.credit_type = 1, 1, 0) * type
                ;
                
                DELETE current_stock
                FROM documents d
                INNER JOIN document_positions dp ON d.id = dp.document_id
                INNER JOIN current_stock ON current_stock.material_id = dp.material_id AND current_stock.organization_id_address = d.organization_id_address AND current_stock.organization_id_ur = d.organization_id_ur
                WHERE current_stock.cnt = 0
                AND current_stock.reserve = 0
                AND d.id = p_doc_id;
                
                UPDATE documents SET status = type WHERE id = p_doc_id;
            END
        ";

        $this->execute($sql);
    }

    public function down()
    {
        $sql = "DROP PROCEDURE close_doc";
        $this->execute($sql);

        $sql = "DROP FUNCTION raise_error";
        $this->execute($sql);
    }
}
