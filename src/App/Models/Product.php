<?php 

namespace App\Models;
use PDO;
use PDOException;

class Product 
{
    public function getData()
    {
        // ติดต่อฐานข้อมูลและสร้าง function ขึ้นมาใช่งานให้ตรงกับความต้อง
        // สร้าง dsn โดยมีข้อกำหนดในการติดต่อฐานข้อมูลดังนี้
        // database:host=localhst;dbname=product;charset=utf8;port=3306;

        try {
            $conn = new PDO("mysql:host=db_mysql;dbname=f2xiacc", "root", "root");
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "Connected successfully";
            
            // $stmt = $conn->query("SELECT * FROM product");
            // return $stmt->fetchAll(PDO::FETCH_ASSOC);

          } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
          }

          $stmt = $conn->query("SELECT * FROM product");
          
          return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

