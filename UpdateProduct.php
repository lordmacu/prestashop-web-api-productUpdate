<?php
/*
 *
 * Example of using the PrestShop WebService to create a product with
 * its stock and its image
 *
 */

ini_set('display_errors', '1');
$_POST["codigo_producto"] = preg_replace('/\s+/', '', $_POST["codigo_producto"]);

define('DEBUG', false);
define('_PS_DEBUG_SQL_', true);
define('PS_SHOP_PATH', 'http://www.mctools.co');

define('PS_WS_AUTH_KEY', 'T5FVAVQ2BBN9MDZGTVUHD142WINYAE6R');
require_once ('PSWebServiceLibrary.php');
//Here we take the product schema and add our datas
$webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);

try {
	$opt['resource'] = 'products';
	$opt['filter']['ean13'] = $_POST["codigo_producto"];
	$xml = $webService -> get($opt);
	$x = ($xml -> products[0] -> product -> attributes());

	// $xml = $webService->get(array('url' => PS_SHOP_PATH . '/api/search/?reference=15254'));
	// $xml = $webService->get(array('url' => PS_SHOP_PATH . '/api/search?query=product&language=1')); http://url_to_prestashop/api/resourse/id

	$resources = $xml -> children() -> children();

	if ($resources) {
		$ProductId = (int)$x['id'];
		UpdateProducto($ProductId);
	} else {
		CrearProducto();
	}

} catch (Exception $e) {
	echo 'Excepción capturada: ', $e -> getMessage(), "\n";
}

function format_uri($string, $separator = '-') {
	$accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
	$special_cases = array('&' => 'and', "'" => '');
	$string = mb_strtolower(trim($string), 'UTF-8');
	$string = str_replace(array_keys($special_cases), array_values($special_cases), $string);
	$string = preg_replace($accents_regex, '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
	$string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
	$string = preg_replace("/[$separator]+/u", "$separator", $string);
	return $string;
}

function cambioarNombre($id) {
	$webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);

	try {
		$opt = array('resource' => 'products');
		$opt['id'] = $id;
		$xml = $webService -> get($opt);

		// Here we get the elements from children of customer markup which is children of prestashop root markup
		$resources = $xml -> children() -> children();
	} catch (PrestaShopWebserviceException $e) {
		// Here we are dealing with errors
		$trace = $e -> getTrace();
		if ($trace[0]['args'][0] == 404)
			echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401)
			echo 'Bad auth key';
		else
			echo 'Other error<br />' . $e -> getMessage();
	}

	$idurlimagen = $resources -> id_default_image;
	$url = PS_SHOP_PATH . '/api/images/products/' . $id . '/' . $idurlimagen . '?ps_method=PUT';

	try {
		unset($resources -> id_default_image);
		unset($resources -> position_in_category);
		unset($resources -> manufacturer_name);
		unset($resources -> unity);
		unset($resources -> date_add);
		unset($resources -> date_upd);
		unset($resources -> product_bundle);
		unset($resources -> quantity);
		unset($resources -> associations -> categories -> category);
		$resources -> reference = $_POST["referencia_producto"];
		$resources -> id_supplier = $_POST["id_marca"];
		$resources -> supplier_reference = $_POST['codigo_proveedor'];
		$resources -> price = $_POST['precio_producto'];
		$resources -> ean13 = $_POST['codigo_producto'];
		$resources -> name -> language[0][0] = $_POST["title"];

		$resources -> description -> language[0][0] = '<p><img src="' . str_replace('#', '', $_POST["imag_ft"]) . '" alt="' . $_POST["title"] . '" width="500" height="360" /></p>';
		$resources -> meta_description -> language[0][0] = $_POST["descripcion_producto"];
		$resources -> meta_title -> language[0][0] = $_POST["title"];
		$resources -> meta_keywords -> language[0][0] = $_POST["palabrasClave"];
		$resources -> link_rewrite -> language[0][0] = format_uri($_POST["title"]);

		$resources -> description_short -> language[0][0] = $_POST["descripcion_producto"];

		$resources -> associations -> categories -> addChild('category') -> addChild('id', $_POST['categoria_padre']);
		$resources -> associations -> categories -> addChild('category') -> addChild('id', $_POST['categoria_hijo']);

		$image_path = $_FILES['upload']["tmp_name"];
		echo "Actualizado correctamente";

		if (isset($_FILES['upload'])) {

			$errors = array();
			$file_name = $_FILES['upload']['name'];
			$file_size = $_FILES['upload']['size'];
			$file_tmp = $_FILES['upload']['tmp_name'];
			$file_type = $_FILES['upload']['type'];
			$file_ext = strtolower(end(explode('.', $_FILES['upload']['name'])));
			$expensions = array("jpeg", "jpg", "png");
			if (in_array($file_ext, $expensions) === false) {
				$errors[] = "extension not allowed, please choose a JPEG or PNG file.";
			}
			if ($file_size > 2097152) {
				$errors[] = 'File size must be excately 2 MB';
			}

			if (empty($errors) == true) {

				move_uploaded_file($file_tmp, "img/" . $file_name);

				$image_path = "/home/users/web/b2716/ipg.markandstorecom/request/img/" . $file_name;

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_USERPWD, PS_WS_AUTH_KEY . ':');
				curl_setopt($ch, CURLOPT_POSTFIELDS, array('image' => '@' . $image_path));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($ch);
				curl_close($ch);

				unlink($image_path);

			} else {
				print_r($errors);
			}
		}

		if ($_POST['activo_tienda'] == "TRUE") {
			$resources -> active = 1;
		} else {
			$resources -> active = 0;

		}

		$opt = array('resource' => 'products');
		$opt['putXml'] = $xml -> asXML();
		$opt['id'] = $id;
		$xml = $webService -> edit($opt);
		// if WebService don't throw an exception the action worked well and we don't show the following message
	} catch (PrestaShopWebserviceException $ex) {
		// Here we are dealing with errors
		$trace = $ex -> getTrace();
		echo $ex -> getMessage();
		if ($trace[0]['args'][0] == 404)
			echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401)
			echo 'Bad auth key';
		else
			echo 'Other error<br />' . $ex -> getMessage();
	}

}

function UpdateProducto($id) {

	//getIdStockAvailableAndSet($id);
	cambioarNombre($id);
}

function getIdStockAvailableAndSet($ProductId) {
	$webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
	$opt['resource'] = 'products';
	$opt['id'] = $ProductId;
	$xml = $webService -> get($opt);
	foreach ($xml->product->associations->stock_availables->stock_available as $item) {
		//echo "ID: ".$item->id."<br>";
		//echo "Id Attribute: ".$item->id_product_attribute."<br>";
		set_product_quantity($ProductId, $item -> id, $item -> id_product_attribute);
	}
}

function set_product_quantity($ProductId, $StokId, $AttributeId) {
	$webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
	$xml = $webService -> get(array('url' => PS_SHOP_PATH . '/api/stock_availables?schema=blank'));
	$resources = $xml -> children() -> children();
	$resources -> id = $StokId;
	$resources -> id_product = $ProductId;
	$resources -> quantity = $_POST["cantidad_producto"];
	$resources -> id_shop = 1;
	$resources -> out_of_stock = 1;
	$resources -> depends_on_stock = 0;
	$resources -> id_product_attribute = $AttributeId;
	try {
		$opt = array('resource' => 'stock_availables');
		$opt['putXml'] = $xml -> asXML();
		$opt['id'] = $StokId;
		$xml = $webService -> edit($opt);

	} catch (PrestaShopWebserviceException $ex) {
		echo "<b>Error al setear la cantidad  ->Error : </b>" . $ex -> getMessage() . '<br>';
	}
}

function CrearProducto() {
	$webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
	$xml = $webService -> get(array('url' => PS_SHOP_PATH . '/api/products?schema=blank'));
	$resources = $xml -> children() -> children();
	$resources -> id;
	$resources -> id_manufacturer;
	$resources -> id_supplier = $_POST["id_marca"];
	$resources -> id_category_default = 10;
	$resources -> new;
	$resources -> cache_default_attribute;
	$resources -> id_default_image = 1;
	$resources -> id_default_combination;
	$resources -> id_tax_rules_group;
	$resources -> position_in_category;
	$resources -> type = "virtual";
	$resources -> id_shop_default;
	$resources -> reference = $_POST["referencia_producto"];
	$resources -> supplier_reference = $_POST['codigo_proveedor'];
	$resources -> location;
	$resources -> width;
	$resources -> height;
	$resources -> depth;
	$resources -> weight;
	$resources -> quantity_discount;
	$resources -> ean13 = $_POST['codigo_producto'];
	$resources -> upc;
	$resources -> cache_is_pack;
	$resources -> cache_has_attachments;
	$resources -> is_virtual;
	$resources -> on_sale;
	$resources -> online_only;
	$resources -> ecotax;
	$resources -> minimal_quantity;
	$resources -> price = $_POST['precio_producto'];
	$resources -> wholesale_price;
	$resources -> unity;
	$resources -> unit_price_ratio;
	$resources -> additional_shipping_cost;
	$resources -> customizable;
	$resources -> text_fields;
	$resources -> uploadable_files;
	if ($_POST['activo_tienda'] == "TRUE") {
		$resources -> active = 1;
	} else {
		$resources -> active = 0;
	}

	$resources -> redirect_type;
	$resources -> id_product_redirected;
	$resources -> available_for_order = 1;
	$resources -> available_date;
	$resources -> condition;
	$resources -> show_price = 1;
	$resources -> indexed;
	$resources -> visibility;
	$resources -> advanced_stock_management;
	$resources -> date_add;
	$resources -> date_upd;
	$node = dom_import_simplexml($resources -> meta_description -> language[0][0]);
	$no = $node -> ownerDocument;
	$node -> appendChild($no -> createCDATASection("cdata meta description"));
	$resources -> meta_description -> language[0][0] = $_POST["descripcion_producto"];
	$resources -> meta_description -> language[0][0]['id'] = 1;
	$resources -> meta_description -> language[0][0]['xlink:href'] = PS_SHOP_PATH . '/api/languages/1';
	$node = dom_import_simplexml($resources -> meta_keywords -> language[0][0]);
	$no = $node -> ownerDocument;
	$node -> appendChild($no -> createCDATASection("cdata meta keywords"));
	$resources -> meta_keywords -> language[0][0] = $_POST["palabrasClave"];
	$resources -> meta_keywords -> language[0][0]['id'] = 1;
	$resources -> meta_keywords -> language[0][0]['xlink:href'] = PS_SHOP_PATH . '/api/languages/1';
	$node = dom_import_simplexml($resources -> meta_title -> language[0][0]);
	$no = $node -> ownerDocument;
	$node -> appendChild($no -> createCDATASection("cdata meta title"));
	$resources -> meta_title -> language[0][0] = $_POST["title"];
	$resources -> meta_title -> language[0][0]['id'] = 1;
	$resources -> meta_title -> language[0][0]['xlink:href'] = PS_SHOP_PATH . '/api/languages/1';
	$node = dom_import_simplexml($resources -> link_rewrite -> language[0][0]);
	$no = $node -> ownerDocument;
	$node -> appendChild($no -> createCDATASection("cdata link_rewrite"));
	$resources -> link_rewrite -> language[0][0] = format_uri($_POST["title"]);
	$resources -> link_rewrite -> language[0][0]['id'] = 1;
	$resources -> link_rewrite -> language[0][0]['xlink:href'] = PS_SHOP_PATH . '/api/languages/1';
	$node = dom_import_simplexml($resources -> name -> language[0][0]);
	$no = $node -> ownerDocument;
	$node -> appendChild($no -> createCDATASection("cdata name"));
	$resources -> name -> language[0][0] = $_POST["title"];
	$resources -> name -> language[0][0]['id'] = 1;
	$resources -> name -> language[0][0]['xlink:href'] = PS_SHOP_PATH . '/api/languages/1';
	$node = dom_import_simplexml($resources -> description -> language[0][0]);
	$no = $node -> ownerDocument;
	$node -> appendChild($no -> createCDATASection("cdata description"));
	$resources -> description -> language[0][0] = '<p><img src="' . str_replace('#', '', $_POST["imag_ft"]) . '" alt="' . $_POST["title"] . '" width="500" height="360" /></p>';
	$resources -> description -> language[0][0]['id'] = 1;
	$resources -> description -> language[0][0]['xlink:href'] = PS_SHOP_PATH . '/api/languages/1';
	$node = dom_import_simplexml($resources -> description_short -> language[0][0]);
	$no = $node -> ownerDocument;
	$node -> appendChild($no -> createCDATASection("cdata description_short"));
	$resources -> description_short -> language[0][0] = $_POST["descripcion_producto"];
	$resources -> description_short -> language[0][0]['id'] = 1;
	$resources -> description_short -> language[0][0]['xlink:href'] = PS_SHOP_PATH . '/api/languages/1';
	$node = dom_import_simplexml($resources -> available_now -> language[0][0]);
	$no = $node -> ownerDocument;
	$node -> appendChild($no -> createCDATASection("cdata In stock"));
	$resources -> available_now -> language[0][0] = "In stock";
	$resources -> available_now -> language[0][0]['id'] = 1;
	$resources -> available_now -> language[0][0]['xlink:href'] = PS_SHOP_PATH . '/api/languages/1';
	$node = dom_import_simplexml($resources -> available_later -> language[0][0]);
	$no = $node -> ownerDocument;
	$node -> appendChild($no -> createCDATASection("cdata available_later"));
	$resources -> available_later -> language[0][0] = "available_later";
	$resources -> available_later -> language[0][0]['id'] = 1;
	$resources -> available_later -> language[0][0]['xlink:href'] = PS_SHOP_PATH . '/api/languages/1';
	$resources -> associations -> categories -> addChild('category') -> addChild('id', $_POST['categoria_padre']);
	$resources -> associations -> categories -> addChild('category') -> addChild('id', $_POST['categoria_hijo']);
	//Here we call to add a new product
	try {
		$opt = array('resource' => 'products');
		$opt['postXml'] = $xml -> asXML();
		$xml_request = $webService -> add($opt);
	} catch (PrestaShopWebserviceException $ex) {
		echo '<b>Error : ' . $ex -> getMessage() . '</b>';
		$trace = $ex -> getTrace();
		print_r($trace);
	}
	echo "Creado correctamente";

	$resources = $xml_request -> children() -> children();

	$stock_available_id = $resources -> associations -> stock_availables -> stock_available[0] -> id;
	$id_created_product = $resources -> id;

	try {
		$opt = array('resource' => 'stock_availables');
		$opt['id'] = $stock_available_id;
		$xml = $webService -> get($opt);
	} catch (PrestaShopWebserviceException $e) {
		// Here we are dealing with errors
		$trace = $e -> getTrace();
		if ($trace[0]['args'][0] == 404)
			echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401)
			echo 'Bad auth key';
		else
			echo 'Other error<br />' . $e -> getMessage();
	}

	$resources = $xml -> children() -> children();

	//There we put our stock
	//$resources -> quantity = $_POST["cantidad_producto"];
	/*
	 There we call to save our stock quantity.
	 */
	try {
		$opt = array('resource' => 'stock_availables');
		$opt['putXml'] = $xml -> asXML();
		$opt['id'] = $stock_available_id;
		$xml = $webService -> edit($opt);
		// if WebService don't throw an exception the action worked well and we don't show the following message
	} catch (PrestaShopWebserviceException $ex) {
		// Here we are dealing with errors
		$trace = $ex -> getTrace();
		if ($trace[0]['args'][0] == 404)
			echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401)
			echo 'Bad auth key';
		else
			echo 'Other error<br />' . $ex -> getMessage();
	}
	/*
	 Here we add an image a created product
	 */
	$url = PS_SHOP_PATH . '/api/images/products/' . $id_created_product;

	// $url = "http://mctools.co/api/images/products/$id_created_product";

	/**.
	 * Uncomment the following line in order to update an existing image
	 */
	//$url = 'http://myprestashop.com/api/images/products/1/2?ps_method=PUT';
	$image_path = $_FILES['upload']["tmp_name"];
	//  $image_path="/home/users/web/b2716/ipg.markandstorecom/hijo.jpg";

	if (isset($_FILES['upload'])) {
		$errors = array();
		$file_name = $_FILES['upload']['name'];
		$file_size = $_FILES['upload']['size'];
		$file_tmp = $_FILES['upload']['tmp_name'];
		$file_type = $_FILES['upload']['type'];
		$file_ext = strtolower(end(explode('.', $_FILES['upload']['name'])));

		$expensions = array("jpeg", "jpg", "png");

		if (in_array($file_ext, $expensions) === false) {
			$errors[] = "extension not allowed, please choose a JPEG or PNG file.";
		}

		if ($file_size > 2097152) {
			$errors[] = 'File size must be excately 2 MB';
		}

		if (empty($errors) == true) {
			move_uploaded_file($file_tmp, "img/" . $file_name);
			echo "Success";

			$image_path = "/home/users/web/b2716/ipg.markandstorecom/request/img/" . $file_name;
			echo $image_path;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_USERPWD, PS_WS_AUTH_KEY . ':');
			curl_setopt($ch, CURLOPT_POSTFIELDS, array('image' => '@' . $image_path . ';type=image/jpg'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($ch);
			curl_close($ch);

			unlink($image_path);

		} else {
			print_r($errors);
		}
	}

}

function getCurlFile($filename) {
	if (class_exists('CURLFile')) {
		return new CURLFile(substr($filename, 1));
	}
	return $filename;
}
