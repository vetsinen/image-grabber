<?php


namespace Serpstat;


class Set
{
    public $set=[];
    public function add($el)
    {
        if (!in_array($el,$this->set)){$this->set[]=$el;}
    }
    public function clear(){$this->set=[];}
}