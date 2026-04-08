<?php

class filterdata
{
    private $find =  ['&','"',"'",'<','>','&','|'];

    public function string(&$_string) {
        $_string = str_replace($this->find, '', $_string);
    }
    /*public function phonenumber (&$_string){
        $this->find [] = ('-');
        $this->find [] = ('+');
        str_replace($this->find,'',$_string);
    }*/
}
