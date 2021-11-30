<?php

require_once('./EstoreParser.php');

$test = new EstoreParser('http://estoremedia.space/DataIT/');
$test->parse();

$products = $test->getProductsFromCSV();

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<table>
    <thead>
        <tr>
            <th>Nazwa</th>
            <th>Url produktu</th>
            <th>Url zdjęcia</th>
            <th>Cena</th>
            <th>Liczba ocen</th>
            <th>Ilość gwiazdek</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($products as $product){ ?>
        <tr>
            <td><a href="https://sobieraj.dev/es_test/product.php?id=<?=$product[6];?>" target="_blank"><?=$product[0];?></a></td>
            <td><?=$product[1];?></td>
            <td><?=$product[2];?></td>
            <td><?=$product[3];?></td>
            <td><?=$product[4];?></td>
            <td><?=$product[5];?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</body>
</html>