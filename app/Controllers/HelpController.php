<?php
namespace App\Controllers;

class HelpController extends BaseController
{
    public function index(): void
    {
        $this->render('help/index');
    }
}
