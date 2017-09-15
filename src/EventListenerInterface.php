<?php namespace BladeOrm;


interface EventListenerInterface
{
    public function process(Model $model);
}
