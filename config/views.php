<?php
    function createViews($con) {
        $con->query("CREATE OR REPLACE VIEW v_products AS
            SELECT p.id_product, p.name, p.description, p.artist, p.price,
                   p.stock, p.image, p.date,
                   c.id_category, c.name AS category_name,
                   g.id_genre, g.name AS genre_name
            FROM products p
            INNER JOIN categories c ON p.id_category = c.id_category
            LEFT JOIN genres g ON p.id_genre = g.id_genre");

        $con->query("CREATE OR REPLACE VIEW v_orders AS
            SELECT o.id_order, o.total, o.status, o.date, o.paid_date,
                   u.id_user, u.name AS client_name, u.email,
                   a.street_address, a.cp,
                   ci.name AS city_name
            FROM orders o
            INNER JOIN users u ON o.id_user = u.id_user
            INNER JOIN addresses a ON o.id_address = a.id_address
            INNER JOIN cities ci ON a.id_city = ci.id_city");

        $con->query("CREATE OR REPLACE VIEW v_order_detail AS
            SELECT od.id_detail, od.id_order, od.quantity, od.unit_price,
                   p.name AS product_name, p.artist, p.image
            FROM order_detail od
            INNER JOIN products p ON od.id_product = p.id_product");
    }
?>
