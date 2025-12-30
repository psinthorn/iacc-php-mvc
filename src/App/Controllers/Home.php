<?php

namespace App\Controllers;

class Home {
    public function index(){
        // Check if user is logged in
        if (!isset($_SESSION['usr_id']) || $_SESSION['usr_id'] === '') {
            header("Location: /iacc/login.php");
            exit;
        }
        
        // Mock data - remove DB query delays
        $sales_today = 0;
        $sales_month = 0;
        $pending_orders = 0;
        $low_stock = 0;
        $recent_receipts = array();
        $pending_pos = array();
        $top_products = array();
        $user_name = isset($_SESSION['usr_name']) ? $_SESSION['usr_name'] : 'User';
        
        // Load view with empty/mock data
        require "./views/home_index.php";
    }
}
