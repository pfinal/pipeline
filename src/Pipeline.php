<?php

namespace PFinal\Pipeline;

use Pimple\Container;

class Pipeline
{
    protected $container;

    /**
     * 准备通过管道,最终传入$destination处理的对象
     *
     * @var mixed
     */
    protected $passable;

    /**
     * 管子集合
     * @var array
     */
    protected $pipes = array();

    /**
     * 每个管子调用的方法名
     *
     * @var string
     */
    protected $method = 'handle';

    /**
     * 构造方法
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * 将对象放入管道
     *
     * @param  mixed $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * 放置需要通过的管子
     *
     * @param  array|mixed $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * 让 $passable 通过所有管子后传入$destination,最后返回 $destination 的执行结果
     *
     * @param  \Closure $destination
     * @return mixed
     */
    public function then(\Closure $destination)
    {
        $firstSlice = function ($passable) use ($destination) {
            return call_user_func($destination, $passable);
        };

        $pipes = array_reverse($this->pipes);

        return call_user_func(
            array_reduce($pipes, $this->getSlice(), $firstSlice), $this->passable
        );
    }

    protected function getSlice()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {

                //如果是Closure实例,直接调用
                if ($pipe instanceof \Closure) {
                    return call_user_func($pipe, $passable, $stack);
                } else {

                    //解析名称和参数值,并从容器中获取对象
                    list($name, $parameters) = $this->parsePipeString($pipe);
                    return call_user_func_array(array($this->container->make($name), $this->method),
                        array_merge(array($passable, $stack), $parameters));
                }
            };
        };
    }

    /**
     * 解析字符串,得到名称和参数值
     * 例如 "throttle:60,1"
     *
     * @param  string $pipe
     * @return array
     */
    protected function parsePipeString($pipe)
    {
        list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, array());

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return array($name, $parameters);
    }
}
