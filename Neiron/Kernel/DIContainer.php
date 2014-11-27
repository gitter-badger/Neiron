<?php
/**
 * PHP 5x framework с открытым иходным кодом
 */
namespace Neiron\Kernel;
use Neiron\Arhitecture\Kernel\DIContainerInterface;
/**
 * Dependicy Inection Контейнер
 * @author KpuTuK
 * @version 1.0.0
 * @package Neiron framework
 * @category Kernel
 * @link
 */
class DIContainer implements DIContainerInterface {
    /**
     * Контейнер
     * @var array
     */
    private $container = array();
    /**
     * Конструктор класса
     * @param array $values 
     */
    public function __construct(array $values = array()) {
        foreach ($values as $offset => $value) {
            $this->offsetSet($offset, $value);
        }
    }
    /**
     * Сохраняет обьект в функцию
     * @param string $name
     * @param mixed $class
     */
    public function setInstance($name, $class) {
        $this->offsetSet($name, function ($values) use ($class) {
            if (is_object($class)) {
                return (new \ReflectionObject($class))->newInstance($values);
            }
            return new $class($values);
        });
    }
    /**
     * Заменяет содержимое контейнера по ключу
     * @param type $name
     * @param type $value
     */
    public function rewind($name, $value) {
        $this->offsetUnset($name);
        $this->offsetSet($name, $value);
    }
    /**
     * Проверяет наличие параметра в контейнере
     * @param string $offset Проверяемый параметр
     * @return bool true параметр найден или false если параметр отсутсвует
     */
    public function offsetExists($offset) {
        return array_key_exists($offset, $this->container);
    }
    /**
     * Сохраняет содержимое в контейнер по ключу
     * @param string $offset Ключ
     * @param mixed $value Сохраняемое содержимое
     * @throws \InvalidArgumentException Исключение выбрасываемое в случае если ключ уже существует в контейнере
     */
    public function offsetSet($offset, $value) {
        if ($this->offsetExists($offset)) {
            throw new \InvalidArgumentException(
                sprintf('Параметр "%s" уже существует!', $offset)
            );
        }
        $this->container[$offset] = $value;
    }
    /**
     * Возвращает содержимое контейнера по ключу
     * @param string $offset Ключ содержимого
     * @return mixed Содержимое
     * @throws \InvalidArgumentException Исключение выбрасываемое в случае отсутствия ключа в контейнере
     */
    public function offsetGet($offset) {
        if ( ! $this->offsetExists($offset)) {
            throw new \InvalidArgumentException(
                sprintf('Параметр "%s" не существует!', $offset)
            );
        }
        return $this->container[$offset];
    }
    /**
     * Удаляет содержимое по ключу в контейнере
     * @param string $offset Ключ содержимого
     * @throws \InvalidArgumentException Исключение выбрасываемое в случае отсутствия ключа в контейнере
     */
    public function offsetUnset($offset) {
        if ( ! $this->offsetExists($offset)) {
            throw new \InvalidArgumentException(
                sprintf('Параметр "%s" не существует!', $offset)
            );
        }
        unset($this->container[$offset]);
    }
}