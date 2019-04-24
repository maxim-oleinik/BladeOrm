<?php namespace MODEL_NAMESPACE;

class MODEL_NAME extends \Blade\Orm\Model
{
    /**
     * Настройка преобразования входящих данных. Приведение к типу и другие проверки.
     * Назначение - для приведения типа и подготовки входящих данных из формы.
     * Можно указывать несколько обработчиков или callable.
     *
     * NULL передается как есть в обход трансформеров
     *
     * @var array - Трансформеры
     */
    protected $transformers = [
        'field1'  => 'trim',
        'field2'  => 'lower',
        'field21' => 'upper',
        'field3'  => 'ucfirst',
        'field4'  => 'int',
        'field5'  => 'float',
        'field7'  => 'bool',
        'field8'  => 'array',
        'field9'  => ['trim', 'lower'], // несколько трансформеров
        'field10' => ['SomeClass', 'someMethod'], // callable
        'field11' => ['trim', ['SomeClass', 'someMethod']], // trim + callable

        // Может принимать значения (set) только указанного класса, аналог тайпхинта
        // get() вернет или объект этого класса, или null, если значение пустое
        'field12' => [\Blade\Orm\Value\DateTime::class],
    ];

    /**
    * @return array - Значения по умолчанию для создания нового объекта
    */
    public function defaults(): array
    {
        return [
            'name'       => null,
            'created_at' => new \DateTime,
        ];
    }

    /**
     * Геттеры назначенные на указанные поля. Кидает исключение, если пытается получить свойство мимо геттера
     * Назначение: заставить явно использовать геттер - для важных полей или там где возможна путаница с названиями
     *             или когда геттер содержит определенную логику и его надо ВСЕГДА вызывать
     *
     * @var array - Геттеры, вызываются в toArray()
     */
    protected $forceGetters = [
        'name' => 'getName', /* @see MODEL_NAME::getName() */
    ];


    /**
     * @var array - Сеттеры, вызываются в set(), update()
     */
    protected $forceSetters = [
        'name' => 'setName', /* @see MODEL_NAME::setName() */
        'code' => false,  // Запретить устанавливать значения через публичные методы
    ];


    /**
     * Геттер - пример
     */
    public function getName()
    {
        // Использовать _get_value(), а не get(), который вызовет getName() еще раз
        return $this->_get_value('name');
    }

    // @codingStandardsIgnoreStart
    public function getCode() { return $this->_get_value(''); }
    // @codingStandardsIgnoreEnd


    /**
     * Сеттер - пример
     */
    public function setName($value)
    {
        // Использовать _set_update(), а не set(), который вызовет setName() еще раз
        if ($this->_set_update('name', $value)) {
            // если значение было изменено
        } else {
            // значение не поменялось
        }
    }
}
