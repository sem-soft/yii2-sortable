<?php
/**
 * Файл класса MoveDirection.php
 *
 * @author Samsonov Vladimir <vs@chulakov.ru>
 */

namespace sem\sortable\enums;

/**
 * Энумератор возможных направмлений перемещения сущности в списке
 */
class MoveDirection
{
    /**
     * Смена позиции сортировки - Вверх
     */
    const UP = 'up';
    /**
     * Смена позиции сортировки - Вниз
     */
    const DOWN = 'down';

    /**
     * @var array Расшифровка статусов
     */
    public static $labels = [
        self::UP => 'Вверх',
        self::DOWN => 'Вниз',
    ];

    /**
     * @var string Дефолтное именование
     */
    public static $defaultLabel = '-';

    /**
     * Получение расшифровки категории
     *
     * @param string $status
     * @return string
     */
    public static function getLabel($status)
    {
        return isset(self::$labels[$status])
            ? self::$labels[$status]
            : self::$defaultLabel;
    }

    /**
     * Разрешенные категории для быстрой валидации
     *
     * @return array
     */
    public static function getRanges()
    {
        return array_keys(static::$labels);
    }
}
