<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Neiron\Kernel;

/**
 * Description of ClassLoader
 *
 * @author KpuTuK
 */
class ClassLoader {
    /**
     * Корневая директория классов
     * @var mixed
     */
    private $rootDir;
    /**
     * Массив классов и пространств имен к ним
     * @var array
     */
    private $pathes = array();
    /**
     * Конструктор класса
     * @param mixed $rootDir Корневая директория классов
     * @param mixed $driver Обьект драйвера кеширования путей
     * @group test
     */
    public function __construct($rootDir = null) {
        $this->rootDir = $rootDir;
    }
    /**
     * Доавляет класс и пространство имен в массив
     * @param string $class
     * @param string $namespace
     */
    public function addPath($class, $namespace) {
        $this->pathes[(string)$class] = (string)$namespace;
    }
    /**
     * Добавляет массив классов и пространств имен к ним
     * @param array $pathes Массив классов и пространств имен к ним
     */
    public function addPathes(array $pathes) {
        $this->pathes = array_merge($this->patches, $pathes);
    }
    /**
     * Преоразует путь классу согласно пространству имен
     * @param string $class Преоразуемый класс
     * @return string Преобразованный класс
     */
    private function preparePatch($class) {
        if (count($this->pathes) !== 0) {
            $class = str_replace(
                array_keys($this->pathes), 
                array_values($this->pathes),
                $class
            );
        }
        return $class;
    }
    /**
     * Возвращает полный путь к классу
     * @param string $class Искомый класс
     * @return string Полный путь к классу
     * @throws \ErrorException Исключение выбрасываемое в случае отсутствия класса
     */
    private function getFilePatch($class) {
        $path = $this->rootDir . $this->preparePatch($class) .'.php';
        $file = str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (file_exists($file)) {
            return $file;
        } else {
            throw new \ErrorException(sprintf('Класс {"%s"} не найден!', $file));
        }
    }
    /**
     * Подключает класс
     * @param string $class Подключаемый класс
     */
    public function loadClass($class) {
        require_once $this->getFilePatch($class);
    }
    /**
     * Регистрирует автозагрузчк классов
     * @param booleran $prepend 
     */
    public function register($prepend = false) {
        spl_autoload_register(array($this, 'loadClass'), false, $prepend);
    }
    /**
     * Удаляет автозагрузчик классов
     */
    public function unregister() {
        spl_autoload_unregister(array($this, 'loadClass'));
    }
}