<?php
/**
 * Файл класса SortAttributeBehavior.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * Поведение автоматически проставляет очередное значение порядка при добавлении новой сущности.
 * Присоединяется к AR-моделе.
 *
 * Пример конфигурации поведения:
 * ```php
 *  public function behaviors()
 *  {
 *      return [
 *          ...
 *          [
 *              'class' => \sem\sortable\behaviors\SortableBehavior::class,
 *              'attribute' => 'order',
 *              'filter' => function (Query $query, Document $model) {
 *                  if ($model->document_group_id) {
 *                      $query->andWhere([
 *                          'document_group_id' => $model->document_group_id
 *                      ]);
 *                  }
 *              },
 *          ],
 *          ...
 *      ];
 *  }
 * ```
 * @property ActiveRecord $owner
 */
class SortAttributeBehavior extends Behavior
{

    /**
     * @var string Наименование поля со значением порядка
     */
    public $attribute = 'sort';

    /**
     * @var string|array|\Closure Фильтр для групповой сортировки
     */
    public $filter = null;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
        ];
    }

    /**
     * Если значение атрибута порядка не задано,
     * то оно устанавливается автоматически, с учетом групповой фильтрации, если она задана
     */
    public function beforeInsert()
    {
        if (!$this->owner->{$this->attribute}) {
            $query = (new Query())->from($this->owner->tableName());

            if ($this->filter) {
                if ($this->filter instanceof \Closure) {
                    call_user_func($this->filter, $query, $this->owner);
                } elseif (is_array($this->filter)) {
                    $query->andWhere($this->filter);
                } else {
                    $query->andWhere([
                        $this->filter => $this->owner->{$this->filter}
                    ]);
                }

            }

            $max = (int)$query->max($this->attribute);
            $this->owner->{$this->attribute} = ++$max;
        }
    }
}