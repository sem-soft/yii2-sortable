<?php
/**
 * Файл класса DragDropQueryBuilder.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\builders\queries;

use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * Перемещает текущую сущность в указанное место в списке сущностей.
 * Рассчет ведется по следующему принцицу: если элемент C необходимо поместить между двумя элементами A и B,
 * то значение его сортировочного атрибута будет рассчитано по формуле:
 * C.sort = A.sort + (B.sort - A.sort) / 2.
 * В текущую формулу, в зависимости от условий, могут вноситься коррективы:
 * 1. Если последующего элемента А - не существует:
 * 1.1. A - существует, но находится на другой странице. Производится попытка нахождения последующего элемента подзапросом.
 * 1.2. А - не существует и перемещаемый элемент становится первым в списке. Мнимый A рассчитывается по формуле: А.sort = B.sort - 1
 * 2. Если предыдущего элемента B - не существует:
 * 2.1. В - существует, но находится на другой странице. Попытка нахождения последующего элемента подзапросом.
 * 2.2. B - не существует и перемещаемый элемент становится последним в списке. Мнимый B рассчитывается по формуле: B.sort = A.sort + 1
 * 3. Всегда должен быть задан либо А, либо B
 * @todo Реализовать полную поддержку ресорта сущностей в рамках разных групп (или без группы), но в одном общем списке
 */
class DragDropQueryBuilder extends AbstractQueryBuilder
{

    /**
     * @var ActiveRecord|null
     */
    protected $previousModel;

    /**
     * @var ActiveRecord|null
     */
    protected $nextModel;

    /**
     * Устанавливает предыдущую модель в новом порядке
     * @param ActiveRecord|null $model
     */
    public function setPreviousModel(ActiveRecord $model = null)
    {
        $this->previousModel = $model;
    }

    /**
     * Устанавливает следующую за текущей модель в новом порядке
     * @param ActiveRecord|null $model
     */
    public function setNextModel(ActiveRecord $model = null)
    {
        $this->nextModel = $model;
    }

    /**
     * Возвращает объект запроса для нахождения нового значения сортировки для указанной модели
     * @return Query
     */
    public function getQuery()
    {
        return (new Query())
            ->select([
                'c' => "{$this->getA()} + ({$this->getB()} - {$this->getA()}) / 2"
            ]);
    }

    /**
     * Возвращает подготовленное значение операнда А
     * @return string
     */
    protected function getA()
    {
        if (!$this->nextModel) {
            $a = (new Query())
                ->select([
                    'a' => "MAX({$this->attribute})"
                ])
                ->from($this->getTableName())
                ->andWhere([
                    '<',
                    $this->attribute,
                    $this->previousModel->{$this->attribute}
                ]);
            $this->applyFilter($a, $this->model);

            $nullValue = $this->previousModel->{$this->attribute} / 1;

            $a = "IFNULL((" . $a->createCommand()->getRawSql() . "),{$nullValue})";
        } else {
            $a = $this->nextModel->{$this->attribute};
        }

        return $a;
    }

    /**
     * Возвращает подготовленное значение операнда B
     * @return string
     */
    protected function getB()
    {
        if (!$this->previousModel) {
            $b = (new Query())
                ->select([
                    'b' => "MIN({$this->attribute})"
                ])
                ->from($this->getTableName())
                ->andWhere([
                    '>',
                    $this->attribute,
                    $this->nextModel->{$this->attribute}
                ]);

            $this->applyFilter($b, $this->model);

            $nullValue = $this->nextModel->{$this->attribute} + 1;

            $b = "IFNULL((" . $b->createCommand()->getRawSql() . "), {$nullValue})";
        } else {
            $b = $this->previousModel->{$this->attribute};
        }

        return $b;
    }
}