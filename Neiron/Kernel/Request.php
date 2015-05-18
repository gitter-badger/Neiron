<?php
/**
 * PHP 5x framework с открытым иходным кодом
 */
namespace Neiron\Kernel;

use Neiron\API\Kernel\RequestInterface;
use Neiron\API\Kernel\DIContainerInterface;
use Neiron\API\Kernel\Request\ControllerResolverInterface;
use Neiron\Kernel\Helpers\ParameterManager;
use Neiron\Kernel\Request\GlobalsManager;
use Neiron\Kernel\Request\CookieManager;

/**
 * Обработчик запросов к серверу
 * @author KpuTuK
 * @version 1.0.0
 * @package Neiron framework
 * @category Kernel
 * @link
 */
class Request implements RequestInterface
{
    /**
     * @var \Neiron\API\Kernel\Request\ControllerResolverInterface
     */
    private $resolver;
    /**
     * Обработчик-альтернатива суперглобальной переменной $GLOBALS
     * @var \Neiron\Kernel\Request\ParameterManager
     */
    public $globals;
    /**
     * Обработчик-альтернатива глобальной переменной $_SERVER
     * @var \Neiron\Kernel\Request\ParameterManager
     */
    public $server;
    /**
     * Обработчик-альтернатива глобальной переменной $_GET
     * @var \Neiron\Kernel\Request\ParameterManager
     */
    public $query;
    /**
     * Обработчик-альтернатива глобальной переменной $_POST
     * @var \Neiron\Kernel\Request\ParameterManager
     */
    public $post;
    /**
     * Обработчик-альтернатива глобальной переменной $_FILES
     * @var \Neiron\Kernel\Request\ParameterManager
     */
    public $files;
    /**
     * Обьект класса для работы с cookie
     * @var \Neiron\Kernel\Request\CookieManager
     */
    public $cookie;
    /**
     * Обьект Dependency injection контейнера
     * @var \Neiron\API\Kernel\DIContainerInteface
     */
    public $headers;
    private $container;
    /**
     * Конструктор класса
     * @param \Neiron\API\Kernel\DIContainerInterface $container
     */
    public function __construct(
        DIContainerInterface $container, 
        ControllerResolverInterface $resolver
    ) {
        $this->container = $container;
        $this->initalizeGlobals();
        $this->resolver = $resolver;
    }
    /**
     * Заполняет глобальные переменные
     */
    private function initalizeGlobals()
    {
        $this->globals = new GlobalsManager($GLOBALS);
        $this->server = new ParameterManager($this->globals['_SERVER']);
        $this->query = new ParameterManager($this->globals['_GET']);
        $this->post = new ParameterManager($this->globals['_POST']);
        $this->files = new ParameterManager($this->globals['_FILES']);
        $this->cookie = new CookieManager($this->globals['_COOKIE']);
        $this->headers = new HeaderManager($this->server->getAll());
    }
    /**
     * Создает и обрабатывает запрос к серверу
     * @todo разобраться с cookies
     * @param string  $uri URI запроса
     * @param mixed  $method Метод запроса
     * @param array  $server Массив данных для переменной $_SERVER
     * @param array  $query Массив данных для переменной $_GET
     * @param array  $post Массив данных для переменной $_POST
     * @param array  $files Массив данных для переменной $_FILES
     * @return \Neiron\API\Kernel\Request\ControllerResolverInterface
     */
    public function create(
        $uri, 
        $method = RequestInterface::METH_GET,
        array $server = array(),
        array $query = array(), 
        array $post = array(),  
        array $files = array(),
        array $cookies = array()
    ){
        $this->server->merge($server);
        $this->query->merge($query);
        $this->post->merge($post);
        $this->files->merge($files);
        $this->cookie->merge($cookies);
        return $this->resolver->resolve(
            $this->container['routing']->match(
                $this->decodeDetectUri($uri),
                $this->server['REQUEST_METHOD'] = $method
            ), 
            $this->container
        );
    }
    /**
     * @author  Zend Framework (1.10dev - 2010-01-24)
     * @license new BSD license (http://framework.zend.com/license/new-bsd).
     * @copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
     * 
     * Возвращает или определяет и декодирует строку uri
     * @param mixed $uri URI строка
     * @return string  Декодированная строка
     * 
     */
    private function decodeDetectUri($uri = null)
    {
        if ($uri === null) {
            $requestUri = '';
            if (isset($this->headers['X_ORIGINAL_URL'])) {
                // IIS with Microsoft Rewrite Module
                $requestUri = $this->headers['X_ORIGINAL_URL'];
                unset(
                    $this->headers['X_ORIGINAL_URL'],
                    $this->headers['HTTP_X_ORIGINAL_URL'],
                    $this->headers['UNENCODED_URL'],
                    $this->headers['IIS_WasUrlRewritten']
                );
            } elseif (isset($this->headers['X_REWRITE_URL'])) {
                // IIS with ISAPI_Rewrite
                $requestUri = $this->headers['X_REWRITE_URL'];
                unset($this->headers['X_REWRITE_URL']);
            } elseif (
                ($this->server['IIS_WasUrlRewritten'] === '1') && 
                ($this->server['UNENCODED_URL'] !== '')
            ) {
                // IIS7 with URL Rewrite: make sure we get the unencoded URL (double slash problem)
                $requestUri = $this->server['UNENCODED_URL'];
                unset($this->server['UNENCODED_URL'], $this->server['IIS_WasUrlRewritten']);
            } elseif (isset($this->server['REQUEST_URI'])) {
                $requestUri = explode('?', $this->server['REQUEST_URI'])[0];
            } elseif ($this->server->has('ORIG_PATH_INFO')) {
                // IIS 5.0, PHP as CGI
                $requestUri = $this->server['ORIG_PATH_INFO'];
                unset($this->server['ORIG_PATH_INFO']);
            }
        }
        $this->setUri(rawurldecode(rtrim($uri, '/')));
        return $this->getUri();
    }
    /**
     * Сохраняет адрес страницы, которая привела браузер пользователя на эту страницу
     * @param string $refer Адрес страницы
     */
    public function setReferer($refer)
    {
        $this->server['HTTP_REFERER'] = $refer;
    }
    /**
     * Возвращает (если есть) адрес страницы, которая привела браузер пользователя на эту страницу
     * @return mixed Адрес страницы или null в случае его отсутствия
     */
    public function getReferer()
    {
        return $this->server['HTTP_REFERER'];
    }
    /**
     * Сохраняет URI запроса
     * @param string $uri URI запроса
     */
    public function setUri($uri)
    {
        $this->server['REQUEST_URI'] = $uri;
    }
    /**
     * Возвращает URI запроса
     * @return string URI запроса
     */
    public function getUri()
    {
        return $this->server['REQUEST_URI'];
    }
    public function isAjax()
    {
        return 'XMLHttpRequest' === $this->headers['X-Requested-With'];
    }
}