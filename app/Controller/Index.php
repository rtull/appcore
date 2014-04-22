<?php
class Controller_Index {
    public function __call($name, $args) {
        echo Core::getTemplate($name);
    }

    public function index() {
        echo Core::getTemplate('index');
    }
}
