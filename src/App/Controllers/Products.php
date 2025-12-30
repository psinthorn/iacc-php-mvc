<?php

namespace App\Controllers;

use App\Models\Product;

class Products {
    public function index() {
        // เมื่อผู้ใช้เรียกใช้เข้าหน้า product ก็จะเรียกมาที่ product controller นี้โดยมีหน้าที่
        // นำเสนอข้อมูลทั้งหมดของรายการ products ที่มีอยู่โดยจัดเก็บใน Mysql Database ดั้งนั้นสิ่งที่ต้องการใหนการเรียกดูข้อมูลคือ 
        // - ติดต่อ database
        // - Query ข้อมูลออกมาเก็บในตัวแปรที่กำหนด
        // - นำเสนอข้อมูลให้อยู่ในรูปแบบที่อ้าใจง่ายสวยงาม

        // ติดต่อฐานข้อมูลด้วย model และเรียกใช้ function getData() ที่ได้สร้างไว้
        // require "./src/models/product.php";
        $model = new Product;
        $products = $model->getData();
        
        // นำเสนอข้อมูล
        require "./views/products_index.php";
    }

    public function show() {
        require "./views/products_show.php";
    }
}