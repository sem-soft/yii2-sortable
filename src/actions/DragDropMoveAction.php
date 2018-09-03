<?php
/**
 * Файл класса DragDropMoveAction.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\actions;

use Yii;
use yii\base\Exception;
use sem\sortable\builders\queries\DragDropQueryBuilder;

/**
 * Реализует логику перемещениясущности в произвольное место списка по сортировочному атрибуту этой сущности и возможному фильтру.
 * Пример настройки компонента.
 *
 * ```php
 *  public function actions()
 *  {
 *
 *  return [
 *      ...
 *      'swap' => [
 *          'class' => 'common\modules\control\actions\sorter\DropMoveAction',
 *          'modelClass' => Document::class,
 *          'filter' => function (Query $query, Document $model) {
 *              if ($model->document_group_id) {
 *                  $query->andWhere([
 *                      'document_group_id' => $model->document_group_id
 *                  ]);
 *              }
 *          }
 *      ],
 *      ...
 *  ];
 * }
 * ```
 * @property-read bool isClearAjax
 * @todo Предусмотреть вариант, когда первичные ключи моделей - серриализованы в json в случае с составным ключом
 */
class DragDropMoveAction extends MoveAction
{

    /**
     * @inheritdoc
     */
    public function run()
    {
        $currentKey = Yii::$app->request->get('currentKey', null);
        $previousKey = Yii::$app->request->get('previousKey', null);
        $nextKey =  Yii::$app->request->get('nextKey', null);

        if (!$currentModel = $this->findModel($currentKey)) {
            return $this->errorResponse("Сортируемая модель не найдена или была удалена ранее");
        }

        $previousModel = $this->findModel($previousKey);
        $nextModel = $this->findModel($nextKey);

        if ((!$previousModel) && (!$nextModel)) {
            return $this->errorResponse("Предыдущий или последующий элемент должен существовать");
        }

        try {
            $builder = new DragDropQueryBuilder();
            $builder->setModel($currentModel);
            $builder->setNextModel($nextModel);
            $builder->setPreviousModel($previousModel);
            $builder->setFilter($this->filter);

            $currentModel->updateAttributes([
                $this->attribute => $builder->getQuery()->scalar()
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->successResponse();
    }
}