<?php
/**
 * Файл класса StepQueryBuilderFactory.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\factories;

use sem\sortable\builders\queries\AbstractStepQueryBuilder;
use yii\db\ActiveRecord;

/**
 * Фабрика инициализации нужного объекта-построителя запросов
 * для определения нового значения порядка с изменением на шаг
 */
class StepQueryBuilderFactory
{
    /**
     * Метод возвращает построитель запроса
     *
     * @param ActiveRecord $model
     * @param string $direction
     * @param string $attribute
     * @return AbstractStepQueryBuilder
     */
    public static function getInstance(ActiveRecord $model, $direction, $attribute = 'sort')
    {
        $direction = ucfirst($direction);
        $queryClass = "\sem\sortable\builders\queries\\{$direction}StepQueryBuilder";

        /** @var AbstractStepQueryBuilder $builder */
        $builder = new $queryClass();
        $builder->setModel($model);
        $builder->setAttribute($attribute);

        return $builder;
    }
}