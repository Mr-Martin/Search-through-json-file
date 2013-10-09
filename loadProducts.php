<?php

//-------------------------------------------------------------
// Gather the keywords
//-------------------------------------------------------------
$query = strtolower($_POST['s']);


//-------------------------------------------------------------
// Check if there are any keywords with quotes around it
//-------------------------------------------------------------
preg_match('/"(.*?)"/', $query, $quoted_string);


//-------------------------------------------------------------
// Check if there are any negative keywords
// If there is, we remove the hyphen and the space. We also
// remove the negative string from $query
//-------------------------------------------------------------
preg_match('/(\s-\w+).*/', $query, $negative_strings);

if($negative_strings) {
  $negative_strings = explode(' ', $negative_strings[0]);
  $negative_strings = array_filter($negative_strings);

  $i = 0;
  foreach($negative_strings as $negative_string) {
    $length = strlen($negative_string);

    //-------------------------------------------------------------
    // Check if the string has more than 1 character i.e more than
    // just a hyphen. If it does -> continue with removing the string
    // from $query
    //-------------------------------------------------------------
    if($length > 1) {
      $trimmed_negative_strings[$i] = substr($negative_string, 1, $length);

      $query = str_replace($negative_string, '', $query);
      $i++;

    } else {
      //-------------------------------------------------------------
      // If the string only contain 1 characted i.e the string contains
      // ONLY a hyphen -> Remove that hyphen from the $query
      //-------------------------------------------------------------
      $query = str_replace($negative_string, '', $query);
    }
  }

}


//-------------------------------------------------------------
// 1. Split the keywords for later use
// 2. Filter the array to get rid of possible blank spaces
//-------------------------------------------------------------
$queries = explode(' ', $query);
$queries = array_filter($queries);


//-------------------------------------------------------------
// 1. Read the products.json file
// 2. Decode it and store the products in $products
//-------------------------------------------------------------
$json_products = file_get_contents("products/products.json");
$products = json_decode($json_products, true);


//-------------------------------------------------------------
// Define $products_found as a empty array
//
// This will later on be used as a holder for all the products
// that we found containing the keywords that the user entered
//-------------------------------------------------------------
$products_found = array();


//-------------------------------------------------------------
// This function checks if it can find products with the
// specified pattern
//-------------------------------------------------------------
function checkWords($pattern, $subject, $product) {
  if(preg_match($pattern, $product[$subject]) > 0) {
    return true;
  }
}


//-------------------------------------------------------------
// This function will search for products containing the keywords
// provided
//-------------------------------------------------------------
function searchProducts($args = array()) {
  extract($args);
  if(!isset($pattern)) {
    $pattern = '/(' . implode('|', $queries) . ')/i';
  }

  if(checkWords($pattern, 'produkt_id', $product) || checkWords($pattern, 'produkt_namn', $product) || checkWords($pattern, 'kategori_namn', $product) > 0) {
    $weight = 0;

    //-------------------------------------------------------------
    // Here we add a weight value to each product found. The weight
    // is used for sorting the array. The more keywords that are
    // found in the product name = higher weight
    //-------------------------------------------------------------
    $splitProductName = explode(' ', $product['produkt_namn']);
    foreach($splitProductName as $key => $name) {
      $name = explode(' ', strtolower($name));
      foreach($queries as $q) {
        if(in_array($q, $name)) {
          $weight++;
        }
      }
    }

    $product['weight'] = $weight;
    return $product;
  }
}


//-------------------------------------------------------------
// Start looping through the products and do some magic
//-------------------------------------------------------------
$i = 0;
foreach ($products as $product) {

  //-------------------------------------------------------------
  // First, check if there are some keywords with quotes around
  // it. If there is, use this quoted word as a pattern to find
  // that product else continue with the regular check
  //-------------------------------------------------------------
  if(isset($quoted_string) && !empty($quoted_string)) {
    $pattern = '/\b' . $quoted_string[1] . '\b/i';

    if(checkWords($pattern, 'produkt_namn', $product)) {
      $count = strlen($product['produkt_namn']) - strlen(str_replace(str_split($query, 2), '', $product['produkt_namn']));
      
      if($count == 0) {
        $count++;
      }
      
      $product['weight'] = $count;
      $products_found[$i] = $product;
    }

  } else if(isset($negative_string) && !empty($negative_string)) {

    //-------------------------------------------------------------
    // Negative keyword check;
    //
    // Here we check the product id, name and category. If we find
    // any product id, name or category containing a negative keyword,
    // we will ignore that product
    //-------------------------------------------------------------
    $pattern = '/';
    foreach ($queries as $word) {
      $pattern .= '(?=.*' . $word . '+)';
    }

    foreach ($trimmed_negative_strings as $trimmed_negative_string) {
      $pattern .= '(?!.*\b'.$trimmed_negative_string.'\b)';
    }
    $pattern .= '.*/i';

    if(checkWords($pattern, 'produkt_namn', $product)) {
      //-------------------------------------------------------------
      // If we found a word that doesn't contain the negative keyword;
      // put it in the products_found array
      //-------------------------------------------------------------
      $args = array(
        'queries' => $queries,
        'product' => $product,
        'pattern' => $pattern,
        'i' => $i
      );

      $products_found[$i] = searchProducts($args);
    }

  } else {

    //-------------------------------------------------------------
    // Regular check;
    //
    // Here we check the product id, name and category. If we find
    // any product id, name or category containing any of the keywords,
    // we will put that in the 'products_found' array
    //-------------------------------------------------------------
    $pattern = '/';
    foreach ($queries as $word) {
      $pattern .= '(?=.*' . $word . ')';
    }
    $pattern .= '/i';

    if(checkWords($pattern, 'produkt_id', $product) || checkWords($pattern, 'produkt_namn', $product) || checkWords($pattern, 'kategori_namn', $product) > 0) {
      $args = array(
        'queries' => $queries,
        'product' => $product,
        'i' => $i
      );

      $products_found[$i] = searchProducts($args);
    }
  }

  $i++;
}


//-------------------------------------------------------------
// SORT THE PRODUCT FOUND ARRAY
//
// This will sort the array on the weight (descending)
//-------------------------------------------------------------
$weight = array();
foreach($products_found as $k => $v) {
  $weight[$k] = $v['weight'];
}

array_multisort($weight, SORT_DESC, $products_found);


//-------------------------------------------------------------
// JSON ENCODE AND RETURN
//
// Last, we json encode the products_found array and echo
// it so we can use the object in AJAX
//-------------------------------------------------------------
echo json_encode($products_found);

?>