<?php
    use whatwhat\troubleshoot\Wwexception;
    set_exception_handler('error_handling');

    function error_handling($exception){
        $handler = new Wwexception($exception);
    }