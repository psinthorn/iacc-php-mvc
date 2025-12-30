<?php 

namespace App\Controllers;

class Notfound
{
    public function index()
    {
        require("./notfound_or_error.php");
    }
}