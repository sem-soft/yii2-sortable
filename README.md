
# yii2-sortable
Full sortable pack for managing entities ordering by specific attribute and grouping filter
## Usage
Next, an example of using the entire Sortable package
### Tables migration
1. Table with simple one filed order column 'sort'
    ```php
        $this->createTable('{{%banner}}', [
            'id' => $this->primaryKey(),
            'is_active' => $this->boolean()->notNull()->defaultValue(1)->comment('Активность записи'),
            'sort' => $this->sort()->decimal(65, 13)->notNull()->unique()->comment('Порядок'),
            'url' => $this->string()->comment('Ссылка для перехода'),
             ...
            'created_at' => $this->integer()->comment('Дата создания'),
             ...
        ]);
    ```
2. Table with complex sorting group fields 'sort' and 'document_group_id'
    ```php
        $this->createTable('{{%document}}', [
            'id' => $this->primaryKey(),
            ...
            'document_group_id' => $this->integer()->comment('Категория документа'),
            ...
            'sort' => $this->decimal(65,13)->notNull()->comment('Порядок'),
            ...
            'url' => $this->string()->comment('Ссылка'),
            'published_at' => $this->integer()->notNull()->comment('Дата публикации'),
            ...
        ]);
        $this->createIndex('idx-document-document_group_id-sort', '{{%document}}', ['document_group_id', 'sort'], true);
    ```
### Models configuration
Include simple rule for 'sort' field and special behavior setting.
1. Model with simple ordering by 'sort' field
	```php
	<?php
	...
	namespace common\modules\banner\models;  
	... 
	use Yii;
	use sem\sortable\behaviors\SortAttributeBehavior;  
	...
	class Banner extends ActiveRecord  
	{
		...
	    public function rules()
	    {
	        return [
		        ...
	            [['sort'], 'number'],
	            ...
	        ];
	    }
	    ...
	    public function behaviors()
	    {
	        return [
		        ...
	            SortAttributeBehavior::class,
				...
	        ];
	    }
	}
	```
2. Model with complex grouping ordering by two fields: 'document_group_id' and 'sort'
	```php
	<?php
	...
	namespace common\modules\document\models;  
	... 
	use Yii;
	use sem\sortable\behaviors\SortAttributeBehavior;  
	...
	class Document extends ActiveRecord  
	{
		...
	    public function rules()
	    {
	        return [
		        ...
	            [['sort'], 'number'],
	            ...
	        ];
	    }
	    ...
	    public function behaviors()
	    {
	        return [
		        ...
	            [
	                'class' => SortAttributeBehavior::class,
	                'filter' => function (Query $query, Document $model) {
	                    if ($model->document_group_id) {
	                        $query->andWhere([
	                            'document_group_id' => $model->document_group_id
	                        ]);
	                    }
	                },
	            ],
				...
	        ];
	    }
	    ...
	}
	```
### Controller special actions configuration
Their is several special actions for reordering entities. Configuration of this actions may be done in controller ```actions()``` method. Don't forgot about RBAC and filtering permissions for this actions

1. Step based actions. These actions allow move entity upstair and downstair by one step only. The example below shows the configuration of two actions with a complex grouping anonymous sort function. 
	```php
	<?php
	...
	namespace common\modules\document\controllers;
	...
	use sem\sortable\enums\MoveDirection;
	use Yii;
	...
	class DocumentController extends Controller
	{
		...
		public function actions()
	    {
	        $sortFilter = function (Query $query, Document $model) {
	            if ($model->document_group_id) {
	                $query->andWhere([
	                    'document_group_id' => $model->document_group_id
	                ]);
	            }
	        };

	        return [
		        ...
		        'up' => [
	                'class' => 'sem\sortable\actions\StepMoveAction',
	                'modelClass' => Document::class,
	                'direction' => MoveDirection::DOWN,
	                'filter' => $sortFilter
	            ],
	            'down' => [
	                'class' => 'sem\sortable\actions\StepMoveAction',
	                'modelClass' => Document::class,
	                'direction' => MoveDirection::UP,
	                'filter' => $sortFilter
	            ],
	            ...
	        ];
	    }
	}
	```
2. Drag and drop action. This action allow move entity in randomly place in list. Example below.
```php
<?php
...
namespace common\modules\privates\bsnner\controllers;  
...
use sem\sortable\actions\DragDropMoveAction;  
use Yii;  
...
class SliderController extends Controller  
{
	...
    public function actions()
    {
        return [
			...
            'swap' => [
                'class' => DragDropMoveAction::class,
                'modelClass' => Banner::class,
            ],
			...
        ];
    }
}
```

### Widgets
All widgets adapted to work with Pjax tech.
1. GridView widget with Step-based ordering buttons.
	```php
        <?php \yii\widgets\Pjax::begin();?>
        <?= \yii\grid\GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
	            ...
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{up} {down} {update} {delete}',
                    'buttons' => [
                        'up' => function ($url, Document $model) {
                            return Html::a('<span class="fa fa-arrow-up"></span>', ['up', 'id' => $model->id], [
                                'data' => [
                                    'pjax' => 1,
                                    'method' => 'post',
                                ],
                            ]);
                        },
                        'down' => function ($url, Document $model) {
                            return Html::a('<span class="fa fa-arrow-down"></span>', ['down', 'id' => $model->id], [
                                'data' => [
                                    'pjax' => 1,
                                    'method' => 'post'
                                ],
                            ]);
                        }
                    ],
                ],
            ],
        ]); ?>
        <?php \yii\widgets\Pjax::end();?>
	```
	When Primary key of sortable entity is composite the ActionColumn buttons url configuration look like the following:
    ```
        ...
        'up' => function ($url, Document $model) {
            return Html::a('<span class="fa fa-arrow-up"></span>',
                array_merge(
                        ['up'],
                        $model->getPrimaryKey(true)
                ),
                [
                    'data' => [
                        'pjax' => 1,
                        'method' => 'post'
                    ]
                ]

            );
        },
        ...
    ```

2. SortableGridView widget. Allows Drag and Drop selected entity over his table list. Also support Pjax. Widget detects Pjax sorround automaticly. Now, this widget supports only GET-method. In the future, POST-method support will be added.
	```php
	        <?php \yii\widgets\Pjax::begin();?>
	        <?= \sem\sortable\widgets\gridview\SortableGridView::widget([
	            'dataProvider' => $dataProvider,
	            'sortActionRoute' => ['swap'],
	            'columns' => [
					...
	                [
	                    'class' => 'yii\grid\ActionColumn',
	                    'template' => '{update} {delete}',
	                ],
	                ...
	            ],
	        ]); ?>
	        <?php \yii\widgets\Pjax::end();?>
	```
	