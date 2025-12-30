<?php

namespace App\Controllers;

class Home {
    public function index(){
        // Mock data - remove DB query delays
        $sales_today = 0;
        $sales_month = 0;
        $pending_orders = 0;
        $low_stock = 0;
        $recent_receipts = array();
        $pending_pos = array();
        $top_products = array();
        
        // Load view with empty/mock data
        require "./views/home_index.php";
    }
}
