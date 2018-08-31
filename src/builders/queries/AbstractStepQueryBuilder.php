<?php
/**
 * Файл класса AbstractStepQueryBuilder.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\builders\queries;

use yii\db\Query;

/**
 * Абстрактный построитель запроса для вычисления нового значения порядка сущности со смещением на один шаг
 */
abstract class AbstractStepQueryBuilder extends AbstractQueryBuilder
{

    /**
     * Формирование запроса для нахождения A-операнда формулы
     * @return Query
     */
    protected function getAQuery()
    {
        $a = (new Query())
            ->select($this->attribute)
            ->from($this->getTableName())
            ->andWhere([
                $this->getCompareOperator(),
                $this->attribute,
                $this->model->{$this->attribute}
            ])
            ->orderBy([
                $this->attribute => $this->getOrderDirection()
            ])
            ->limit(1);

        $this->applyFilter($a, $this->model);

        return $a;
    }

    /**
     * Формирование запроса для нахождения B-операнда формулы
     * @return Query
     */
    protected function getBQuery()
    {
        $b = (new Query())
            ->select($this->attribute)
            ->from($this->getTableName())
            ->andWhere([
                $this->getCompareOperator(),
                $this->attribute,
                $this->getAQuery()
            ])
            ->orderBy([
                $this->attribute => $this->getOrderDirection()
            ])
            ->limit(1);

        $this->applyFilter($b, $this->model);

        return $b;
    }

    /**
     * Формирование запроса для нахождения нового значения сортировки (C-операнда)
     * @return Query
     */
    protected function getCQuery()
    {
        $t = (new Query())
            ->select([
                '[[a]]' => $this->getAQuery(),
                '[[b]]' => $this->getBQuery()
            ]);

        $c = (new Query())
            ->select([
                '[[c]]' => $this->getCFormula()
            ])->from([
                't' => $t
            ]);

        return $c;
    }

    /**
     * Возвращает оператор сравнения, участвующий в поисковых запросах для данного типа перемещения сущности
     * @return string
     */
    abstract protected function getCompareOperator();

    /**
     * Возвращает направление сортировки, участвующее в поисковых запросах для данного типа перемещения сущности
     * @return integer
     */
    abstract protected function getOrderDirection();

    /**
     * Возвращать основную SQL-формулу для расчета нового значения порядка
     * @return string
     */
    abstract protected function getCFormula();
}