<?php
class EstoreParser{

    function __construct($url){
        $this->url = $url;
        $this->csv_path = './products.csv';

        $this->prepareCSV();
    }

    function getContent($url){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:15.0) Gecko/20100101 Firefox/15.0.1');
        curl_setopt($ch, CURLOPT_INTERFACE, '51.77.43.9');
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;

    }

    function parse(){

        $products = $this->getProducts();
        $this->insertProductsListToCSV($products);

    }

    function prepareCSV(){

        if(file_exists($this->csv_path)){
            unlink($this->csv_path);
        }

    }

    function insertProductsListToCSV($products){

        $f = fopen($this->csv_path, 'w');
        if($f === false){
            throw new Exception('Cannot open CSV file '.$this->csv_path);
        }
        foreach($products as $product){
            fputcsv($f, $product);
        }
        fclose($f);
        chmod($this->csv_path, 0777);

    }

    function getProductsFromCSV(){

        $data = [];

        $f = fopen($this->csv_path, 'r');

        if($f === false){
            throw new Exception('Cannot open CSV file '.$this->csv_path);
        }

        while(($row = fgetcsv($f)) !== false){
            $data[] = $row;
        }

        fclose($f);

        return $data;

    }

    function getProducts(){

        $products = array();
        $pages = $this->getPagesList();

        foreach($pages as $page_id){

            try{
                $products_content = $this->getProductsHTML($this->url.'index.php?page='.$page_id);

                foreach($products_content as $productHTML){

                    $rating = $this->getProductRatingFromHTML($productHTML);
                    $product_id = $this->getProductIDFromHTML($productHTML);

                    $products[] = array(
                        'full_name' => $this->getProductFullNameFromHTML($productHTML),
                        'product_card_url' => $this->url.'?index.php?page='.$product_id,
                        'image_url' => $this->getProductImageFromHTML($productHTML),
                        /* Zwyczajne pobranie ceny zgodnie z instrukcją zadania, bez rozbijania na cena/waluta. */
                        'price' => $this->getProductPriceFromHTML($productHTML),
                        'number_of_ratings' => $rating['number_of_ratings'],
                        'stars' => $rating['rating'],
                        'product_id' => $product_id
                    );

                }

            }catch(Exception $e){
                echo 'Error: ',  $e->getMessage(), "\n";
                break;
            }

        }

        return $products;

    }

    function getProductsHTML($url){

        $content = $this->getContent($url);
        preg_match_all('/<div class=\"card h-100\">(.*)<\/small>/ismU', $content, $products_content_array);
        if(isset($products_content_array[0]) && count($products_content_array[0]) > 0){
            return $products_content_array[0];
        }else{
            throw new Exception('Cannot get products card HTML from '.$url);
        }

    }

    function getProductHTML($url){

        $content = $this->getContent($url);
        preg_match_all('/<div class=\"card h-100\">(.*)<\/small>/ismU', $content, $products_content_array);
        if(isset($products_content_array[0]) && count($products_content_array[0]) > 0){
            return $products_content_array[0][0];
        }else{
            throw new Exception('Cannot get product card HTML from '.$url);
        }

    }

    function getProductImageFromHTML($productHTML){

        preg_match_all('/<img class=\"card-img-top\" src=\"(.*)\"/ismU', $productHTML, $product_img_content_array);
        if(isset($product_img_content_array[1][0]) && $product_img_content_array[1][0] != ''){
            return $product_img_content_array[1][0];
        }else{
            throw new Exception('Cannot get image url.');
        }

    }

    function getProductIDFromHTML($productHTML){

        preg_match_all('/<a href=\"product.php\?id=(.*)\">/ismU', $productHTML, $product_id_content_array);
        if(isset($product_id_content_array[1][0]) && $product_id_content_array[1][0] != ''){
            return $product_id_content_array[1][0];
        }else{
            throw new Exception('Cannot get product id.');
        }

    }

    function getProductFullNameFromHTML($productHTML, $from_product_page = false){

        if(!$from_product_page){
            $pattern = '/data-name=\"(.*)\"/ismU';
        }else{
            $pattern = '/<p class=\"card-text\">(.*)<\/p>/ismU';
        }

        preg_match_all($pattern, $productHTML, $product_full_name_content_array);

        if(isset($product_full_name_content_array[1][0]) && $product_full_name_content_array[1][0] != ''){
            /* dodatkowy fix na nazwy z wieloma spacjami */
            return preg_replace('/\s+/', ' ', $product_full_name_content_array[1][0]);
        }else{
            throw new Exception('Cannot get product full name.');
        }

    }

    function getProductPriceFromHTML($productHTML){

        preg_match_all('/<h5>(.*)<\/h5>/ismU', $productHTML, $product_price_content_array);
        if(isset($product_price_content_array[1][0]) && $product_price_content_array[1][0] != ''){
            return $product_price_content_array[1][0];
        }else{
            throw new Exception('Cannot get product price.');
        }

    }

    function getProductAdditionalData($productHTML){

        preg_match_all('/<script type=\"application\/json\">(.*)<\/script>/ismU', $productHTML, $product_json_content_array);
        if(isset($product_json_content_array[1][0]) && $product_json_content_array[1][0] != ''){
            return json_decode($product_json_content_array[1][0], true);
        }else{
            throw new Exception('Cannot get product JSON.');
        }

    }

    function getProductRatingFromHTML($productHTML){

        $number_of_ratings = 0;
        $rating = 0;
        preg_match_all('/<small class=\"text-muted\">(.*)<\/small>/ismU', $productHTML, $product_ratirng_content_array);
        if(isset($product_ratirng_content_array[1][0]) && $product_ratirng_content_array[1][0]){
            preg_match_all('/\((.*)\)/ismU', $product_ratirng_content_array[1][0], $product_ratirng_how_many_content_array);
            if(isset($product_ratirng_how_many_content_array[1][0]) && $product_ratirng_how_many_content_array[1][0] != ''){
                $number_of_ratings = $product_ratirng_how_many_content_array[1][0];
            }else{
                throw new Exception('Cannot get product rating.');
            }
            preg_match_all('/&#9733;/ismU', $product_ratirng_content_array[1][0], $product_rating_rating_content_array);

            if(isset($product_rating_rating_content_array[0]) && $product_rating_rating_content_array[0] != ''){
                $rating = count($product_rating_rating_content_array[0]);
            }else{
                throw new Exception('Cannot get product rating.');
            }

            return array('number_of_ratings' => $number_of_ratings, 'rating' => $rating);

        }else{
            throw new Exception('Cannot get product rating.');
        }

    }


    function getPagesList(){

        $content = $this->getContent($this->url);
        $pages = array();

        /* Wycinanie z całego html-a samej paginacji */
        preg_match_all('/<ul class=\"pagination\">(.*)<\/ul>/ismU', $content, $subpages_content_array);

        if(isset($subpages_content_array[1])){

            /* Wycinanie z wyciętej treści paginacji samych identyfikatorów podstron. */
            preg_match_all('/data-page=\"(.*)\"/ismU', $subpages_content_array[1][0], $subpages_pages_array);

            if(isset($subpages_pages_array[1]) && is_array($subpages_pages_array[1]) && count($subpages_pages_array[1]) > 0){

                foreach($subpages_pages_array[1] as $subpage_number){
                    if(is_numeric($subpage_number)){
                        $pages[] = $subpage_number;
                    }
                }

                /* Identyfikatory podstron w paginacji są powielane poprzez elementy typu "next", wykluczam w ten sposob powielone wpisy w tablicy aby uniknąć ponownego parsowania podstron. */
                $pages = array_unique($pages);

            }

        }

        return $pages;

    }

    function getProductByID($product_id){

        $productHTML = $this->getProductHTML($this->url.'product.php?id='.$product_id);

        $rating = $this->getProductRatingFromHTML($productHTML);

        $product = array(
            'full_name' => $this->getProductFullNameFromHTML($productHTML, true),
            'image_url' => $this->getProductImageFromHTML($productHTML),
            /* Zwyczajne pobranie ceny zgodnie z instrukcją zadania, bez rozbijania na cena/waluta. */
            'price' => $this->getProductPriceFromHTML($productHTML),
            'number_of_ratings' => $rating['number_of_ratings'],
            'stars' => $rating['rating'],
            'additional' => $this->getProductAdditionalData($productHTML)
        );

        return $product;

    }

}

?>