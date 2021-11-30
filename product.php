<?php
require_once('./EstoreParser.php');

$parser = new EstoreParser('http://estoremedia.space/DataIT/');

/* Nie robiłem dodatkowej validacji, brak tego typu instrukcji w zadaniu. */
if(!isset($_GET['id'])){
    die('Brak zdefiniowanego ID produktu.');
}

$product = $parser->getProductByID($_GET['id']);

?>

<h2>Dane produktu</h2>

<b>Cena produktu:</b> <?=$product['price'];?><br>
<b>URL zdjęcia:</b> <?=$product['image_url'];?><br>
<b>Kod produktu:</b> <?=$product['additional']['products']['code'];?><br>
<b>Liczba ocen:</b> <?=$product['number_of_ratings'];?><br>
<b>Ilość gwiazdek:</b> <?=$product['stars'];?><br>

<h2>Warianty</h2>
<?php if(count($product['additional']['products']['variants']) > 0){ ?>

    <ul>
        <?php foreach($product['additional']['products']['variants'] as $variant=>$variant_data){ ?>
            <li>
                <?=$product['full_name'];?> #<?=$variant;?><br>
                <?=$variant_data['price'];?>
                <?php if(isset($variant_data['price_old'])){ ?>
                    <s><?=$variant_data['price_old'];?></s>
                <?php } ?>
            </li>
        <?php } ?>
    </ul>

<?php }else{ ?>
    <p>Brak wariantów.</p>
<?php } ?>
