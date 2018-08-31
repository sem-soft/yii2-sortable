<?php
/**
 * Файл класса AbstractQueryBuilder.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\builders\queries;

use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * Абстрактный построитель запроса для вычисления нового значения порядка текущей сущности
 */
abstract class AbstractQueryBuilder
{
    /**
     * @var ActiveRecord
     */
    protected $model;

    /**
     * @var string Наименование поля со значением порядка
     */
    protected $attribute = 'sort';

    /**
     * Фильтр для групповой сортировки
     * @var string|array|\Closure
     */
    protected $filter;

    /**
     * Устанавливает сортируемую моледб
     * @param ActiveRecord $model
     */
    public function setModel(ActiveRecord $model)
    {
        $this->model = $model;
    }

    /**
     * Устанавливает имя атрибута порядка модели
     * @param string $name
     */
    public function setAttribute($name)
    {
        $this->attribute = $name;
    }

    /**
     * Устанавливает дополнительный групповой фильтр для определения порядка
     *
     * @param string|array|\Closure $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * Применяет заданную групповую фильтрацию определения порядка
     *
     * @param Query $query
     * @param ActiveRecord|null $model
     */
    protected function applyFilter(Query $query, ActiveRecord $model)
    {
        if ($this->filter) {
            if ($this->filter instanceof \Closure) {
                call_user_func($this->filter, $query, $model);
            } elseif (is_array($this->filter)) {
                $query->andWhere($this->filter);
            } else {
                $query->andWhere([
                    $this->filter => $model->{$this->filter}
                ]);
            }
        }
    }

    /**
     * Возвращает наименование таблицы сортируемой модели
     * @return string
     */
    protected function getTableName()
    {
        $model = $this->model;
        return $model::tableName();
    }

    /**
     * Возвращает объект запроса для нахождения нового значения порядка для указанной модели
     * @return Query
     */
    abstract public function getQuery();


}