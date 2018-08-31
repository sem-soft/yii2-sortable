<?php
/**
 * Файл класса UpStepQueryBuilder.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\builders\queries;

use yii\db\Expression;
use yii\db\Query;

/**
 * Построитель запросов для вычисления нового значения для сортировки сущности со смещением на один шаг вверх.
 *
 * Если элемент C нужно поместить между A и B, то его значение вычисляется по следующей пормуле:
 * C = A + (B - A) / 2.
 * В эту формулу добавляется специфика циклического перемещения.
 * 
 * Циклический ресорт записи на одну позицию ВВЕРХ.
 * Перемещает запись с текущим порядком :currentSort на одну позицию вверх.
 * Если запись первая, то она становится последней
 * ```
 *  SELECT
 *      IFNULL(
 *          a + (IFNULL(b,0) - a)/2,
 *          (SELECT MAX(sort) + 1 FROM credit_cash)
 *      ) as c
 *  FROM
 *   (SELECT
 *      (SELECT
 *          sort
 *      FROM
 *          credit_cash
 *      WHERE
 *          sort < :currentSort
 *      ORDER BY sort DESC LIMIT 1) a,
 *
 *      (SELECT
 *          sort
 *      FROM
 *          credit_cash
 *      WHERE
 *          sort < (SELECT
 *          sort
 *      FROM
 *          credit_cash t
 *      WHERE
 *          sort < :currentSort
 *      ORDER BY sort DESC LIMIT 1)
 *      ORDER BY sort DESC LIMIT 1) b
 *    ) t
 * ```
 */
class UpStepQueryBuilder extends AbstractStepQueryBuilder
{

    const COMPARE_OPERATOR = '<';

    const ORDER_DIRECTION = SORT_DESC;

    /**
     * Возвращает объект запроса для нахождения нового значения сортировки для указанной модели
     * @return Query
     */
    public function getQuery()
    {
        return $this->getCQuery();
    }

    /**
     * @inheritdoc
     */
    protected function getCompareOperator()
    {
        return self::COMPARE_OPERATOR;
    }

    /**
     * @inheritdoc
     */
    protected function getOrderDirection()
    {
        return self::ORDER_DIRECTION;
    }

    /**
     * Возвращать основную SQL-формулу для расчета нового значения порядка
     * @return string
     */
    protected function getCFormula()
    {
        $cNullReplacementQuery = (new Query())
            ->select(new Expression("MAX([[{$this->attribute}]]) + 1"))
            ->from($this->getTableName());

        $this->applyFilter($cNullReplacementQuery, $this->model);

        return "IFNULL([[a]] + (IFNULL([[b]],0) - a) / 2, ({$cNullReplacementQuery->createCommand()->getRawSql()}))";
    }
}