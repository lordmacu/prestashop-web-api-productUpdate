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

function normaliza($cadena) {
	$originales = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ
ßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
	$modificadas = 'aaaaaaaceeeeiiiidnoooooouuuuy
bsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
	$cadena = utf8_decode($cadena);
	$cadena = strtr($cadena, utf8_decode($originales), $modificadas);
	$cadena = strtolower($cadena);
	return utf8_encode($cadena);
}

function getTagId($Tag) {
	$webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);

	$Tag = normaliza($Tag);

	//if tag exists
	$xml = $webService -> get(array('url' => PS_SHOP_PATH . '/api/tags?filter[name]=' . $Tag . '&limit=1'));
	$resources = $xml -> children() -> children();
	if (!empty($resources)) {
		$attributes = $resources -> tag -> attributes();
		return $attributes['id'];
	}

	//if not exists, add it
	$xml = $webService -> get(array('url' => PS_SHOP_PATH . '/api/tags?schema=synopsis'));
	$resources = $xml -> children() -> children();

	unset($resources -> id);
	$resources -> name = $Tag;
	$resources -> id_lang = 1;

	$opt = array('resource' => 'tags', 'postXml' => $xml -> asXML());

	$xml = $webService -> add($opt);
	return $xml -> tag -> id;
}

function cambioarNombre($id) {
	$webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);

	try {
		$opt = array('resource' => 'products');
		$opt['id'] = $id;
		$xml = $webService -> get($opt);

		$resources = $xml -> children() -> children();
	} catch (PrestaShopWebserviceException $e) {
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
		unset($resources -> associations -> categories -> tag);

		$resources -> reference = $_POST["referencia_producto"];
		$resources -> id_manufacturer = $_POST["id_marca"];
		$resources -> supplier_reference = $_POST['codigo_proveedor'];
		$resources -> price = $_POST['precio_producto'];
		$resources -> ean13 = $_POST['codigo_producto'];
		$resources -> name -> language[0][0] = $_POST["title"];
		$resources -> meta_title -> language[0][0] = $_POST["title"];
		$resources -> link_rewrite -> language[0][0] = format_uri($_POST["title"]);

		$resources -> associations -> categories -> addChild('category') -> addChild('id', $_POST['categoria_padre']);
		$resources -> associations -> categories -> addChild('category') -> addChild('id', $_POST['categoria_hijo']);

		$tags = explode(",", $_POST["palabrasClave"]);

		$arraytags = array();
		foreach ($tags as $key => $value) {
			if ($value != "") {
				$palabra = strtolower($value);
				$tagid = getTagId($palabra);
				$arraytags[] = $tagid;
			}
		}

		foreach (array_unique($arraytags) as $ak) {
			$resources -> associations -> tags -> addChild('tag') -> addChild('id', $ak);

		}

		$des = htmlentities($_POST["descripcion_producto"]);

		$resources -> description -> language[0][0] = $des . '<br><p><img src="' . str_replace('#', '', $_POST["imag_ft"]) . '" alt="' . $_POST["title"] . '" width="500" height="360" /></p>';

		$resources -> link_rewrite -> language[0][0] = format_uri($_POST["title"]);

		$limit = 120;
		if (strlen($summary) > $limit)
			$summary = substr(($_POST["descripcion_corta"]), 0, strrpos(substr($_POST["descripcion_corta"], 0, $limit), ' ')) . '...';

		$resources -> meta_description -> language[0][0] = $summary;
		$resources -> description_short -> language[0][0] = $summary;

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
	} catch (PrestaShopWebserviceException $ex) {
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

function remove_accents($string) {
	if (!preg_match('/[\x80-\xff]/', $string))
		return $string;

	$chars = array(
	// Decompositions for Latin-1 Supplement
	chr(195) . chr(128) => 'A', chr(195) . chr(129) => 'A', chr(195) . chr(130) => 'A', chr(195) . chr(131) => 'A', chr(195) . chr(132) => 'A', chr(195) . chr(133) => 'A', chr(195) . chr(135) => 'C', chr(195) . chr(136) => 'E', chr(195) . chr(137) => 'E', chr(195) . chr(138) => 'E', chr(195) . chr(139) => 'E', chr(195) . chr(140) => 'I', chr(195) . chr(141) => 'I', chr(195) . chr(142) => 'I', chr(195) . chr(143) => 'I', chr(195) . chr(145) => 'N', chr(195) . chr(146) => 'O', chr(195) . chr(147) => 'O', chr(195) . chr(148) => 'O', chr(195) . chr(149) => 'O', chr(195) . chr(150) => 'O', chr(195) . chr(153) => 'U', chr(195) . chr(154) => 'U', chr(195) . chr(155) => 'U', chr(195) . chr(156) => 'U', chr(195) . chr(157) => 'Y', chr(195) . chr(159) => 's', chr(195) . chr(160) => 'a', chr(195) . chr(161) => 'a', chr(195) . chr(162) => 'a', chr(195) . chr(163) => 'a', chr(195) . chr(164) => 'a', chr(195) . chr(165) => 'a', chr(195) . chr(167) => 'c', chr(195) . chr(168) => 'e', chr(195) . chr(169) => 'e', chr(195) . chr(170) => 'e', chr(195) . chr(171) => 'e', chr(195) . chr(172) => 'i', chr(195) . chr(173) => 'i', chr(195) . chr(174) => 'i', chr(195) . chr(175) => 'i', chr(195) . chr(177) => 'n', chr(195) . chr(178) => 'o', chr(195) . chr(179) => 'o', chr(195) . chr(180) => 'o', chr(195) . chr(181) => 'o', chr(195) . chr(182) => 'o', chr(195) . chr(182) => 'o', chr(195) . chr(185) => 'u', chr(195) . chr(186) => 'u', chr(195) . chr(187) => 'u', chr(195) . chr(188) => 'u', chr(195) . chr(189) => 'y', chr(195) . chr(191) => 'y',
	// Decompositions for Latin Extended-A
	chr(196) . chr(128) => 'A', chr(196) . chr(129) => 'a', chr(196) . chr(130) => 'A', chr(196) . chr(131) => 'a', chr(196) . chr(132) => 'A', chr(196) . chr(133) => 'a', chr(196) . chr(134) => 'C', chr(196) . chr(135) => 'c', chr(196) . chr(136) => 'C', chr(196) . chr(137) => 'c', chr(196) . chr(138) => 'C', chr(196) . chr(139) => 'c', chr(196) . chr(140) => 'C', chr(196) . chr(141) => 'c', chr(196) . chr(142) => 'D', chr(196) . chr(143) => 'd', chr(196) . chr(144) => 'D', chr(196) . chr(145) => 'd', chr(196) . chr(146) => 'E', chr(196) . chr(147) => 'e', chr(196) . chr(148) => 'E', chr(196) . chr(149) => 'e', chr(196) . chr(150) => 'E', chr(196) . chr(151) => 'e', chr(196) . chr(152) => 'E', chr(196) . chr(153) => 'e', chr(196) . chr(154) => 'E', chr(196) . chr(155) => 'e', chr(196) . chr(156) => 'G', chr(196) . chr(157) => 'g', chr(196) . chr(158) => 'G', chr(196) . chr(159) => 'g', chr(196) . chr(160) => 'G', chr(196) . chr(161) => 'g', chr(196) . chr(162) => 'G', chr(196) . chr(163) => 'g', chr(196) . chr(164) => 'H', chr(196) . chr(165) => 'h', chr(196) . chr(166) => 'H', chr(196) . chr(167) => 'h', chr(196) . chr(168) => 'I', chr(196) . chr(169) => 'i', chr(196) . chr(170) => 'I', chr(196) . chr(171) => 'i', chr(196) . chr(172) => 'I', chr(196) . chr(173) => 'i', chr(196) . chr(174) => 'I', chr(196) . chr(175) => 'i', chr(196) . chr(176) => 'I', chr(196) . chr(177) => 'i', chr(196) . chr(178) => 'IJ', chr(196) . chr(179) => 'ij', chr(196) . chr(180) => 'J', chr(196) . chr(181) => 'j', chr(196) . chr(182) => 'K', chr(196) . chr(183) => 'k', chr(196) . chr(184) => 'k', chr(196) . chr(185) => 'L', chr(196) . chr(186) => 'l', chr(196) . chr(187) => 'L', chr(196) . chr(188) => 'l', chr(196) . chr(189) => 'L', chr(196) . chr(190) => 'l', chr(196) . chr(191) => 'L', chr(197) . chr(128) => 'l', chr(197) . chr(129) => 'L', chr(197) . chr(130) => 'l', chr(197) . chr(131) => 'N', chr(197) . chr(132) => 'n', chr(197) . chr(133) => 'N', chr(197) . chr(134) => 'n', chr(197) . chr(135) => 'N', chr(197) . chr(136) => 'n', chr(197) . chr(137) => 'N', chr(197) . chr(138) => 'n', chr(197) . chr(139) => 'N', chr(197) . chr(140) => 'O', chr(197) . chr(141) => 'o', chr(197) . chr(142) => 'O', chr(197) . chr(143) => 'o', chr(197) . chr(144) => 'O', chr(197) . chr(145) => 'o', chr(197) . chr(146) => 'OE', chr(197) . chr(147) => 'oe', chr(197) . chr(148) => 'R', chr(197) . chr(149) => 'r', chr(197) . chr(150) => 'R', chr(197) . chr(151) => 'r', chr(197) . chr(152) => 'R', chr(197) . chr(153) => 'r', chr(197) . chr(154) => 'S', chr(197) . chr(155) => 's', chr(197) . chr(156) => 'S', chr(197) . chr(157) => 's', chr(197) . chr(158) => 'S', chr(197) . chr(159) => 's', chr(197) . chr(160) => 'S', chr(197) . chr(161) => 's', chr(197) . chr(162) => 'T', chr(197) . chr(163) => 't', chr(197) . chr(164) => 'T', chr(197) . chr(165) => 't', chr(197) . chr(166) => 'T', chr(197) . chr(167) => 't', chr(197) . chr(168) => 'U', chr(197) . chr(169) => 'u', chr(197) . chr(170) => 'U', chr(197) . chr(171) => 'u', chr(197) . chr(172) => 'U', chr(197) . chr(173) => 'u', chr(197) . chr(174) => 'U', chr(197) . chr(175) => 'u', chr(197) . chr(176) => 'U', chr(197) . chr(177) => 'u', chr(197) . chr(178) => 'U', chr(197) . chr(179) => 'u', chr(197) . chr(180) => 'W', chr(197) . chr(181) => 'w', chr(197) . chr(182) => 'Y', chr(197) . chr(183) => 'y', chr(197) . chr(184) => 'Y', chr(197) . chr(185) => 'Z', chr(197) . chr(186) => 'z', chr(197) . chr(187) => 'Z', chr(197) . chr(188) => 'z', chr(197) . chr(189) => 'Z', chr(197) . chr(190) => 'z', chr(197) . chr(191) => 's');

	$string = strtr($string, $chars);

	return $string;
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
	$resources -> id_manufacturer = $_POST["id_marca"];
	$resources -> id_supplier;
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

	$limit = 120;
	if (strlen($summary) > $limit)
		$summary = substr($_POST["descripcion_corta"], 0, strrpos(substr($_POST["descripcion_corta"], 0, $limit), ' ')) . '...';

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
	$resources -> meta_description -> language[0][0] = $summary;
	$resources -> meta_description -> language[0][0]['id'] = 1;
	$resources -> meta_description -> language[0][0]['xlink:href'] = PS_SHOP_PATH . '/api/languages/1';

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
	$resources -> description -> language[0][0] = htmlentities($_POST["descripcion_producto"]) . '<br><p><img src="' . str_replace('#', '', $_POST["imag_ft"]) . '" alt="' . $_POST["title"] . '" width="500" height="360" /></p>';
	$resources -> description -> language[0][0]['id'] = 1;
	$resources -> description -> language[0][0]['xlink:href'] = PS_SHOP_PATH . '/api/languages/1';
	$node = dom_import_simplexml($resources -> description_short -> language[0][0]);
	$no = $node -> ownerDocument;

	$node -> appendChild($no -> createCDATASection("cdata description_short"));
	$resources -> description_short -> language[0][0] = $summary;
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

	$tags = explode(",", $_POST["palabrasClave"]);

	$arraytags = array();
	foreach ($tags as $key => $value) {
		if ($value != "") {
			$palabra = strtolower($value);
			$tagid = getTagId($palabra);
			$arraytags[] = $tagid;
		}
	}

	foreach (array_unique($arraytags) as $ak) {
		$resources -> associations -> tags -> addChild('tag') -> addChild('id', $ak);

	}

	try {
		$opt = array('resource' => 'products');
		$opt['postXml'] = $xml -> asXML();
		$xml_request = $webService -> add($opt);
		echo "Creado correctamente";

	} catch (PrestaShopWebserviceException $ex) {
		echo '<b>Error : ' . $ex -> getMessage() . '</b>';
		$trace = $ex -> getTrace();
		print_r($trace);
	}

	$resources = $xml_request -> children() -> children();

	$stock_available_id = $resources -> associations -> stock_availables -> stock_available[0] -> id;
	$id_created_product = $resources -> id;

	try {
		$opt = array('resource' => 'stock_availables');
		$opt['id'] = $stock_available_id;
		$xml = $webService -> get($opt);
	} catch (PrestaShopWebserviceException $e) {
		$trace = $e -> getTrace();
		if ($trace[0]['args'][0] == 404)
			echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401)
			echo 'Bad auth key';
		else
			echo 'Other error<br />' . $e -> getMessage();
	}

	$resources = $xml -> children() -> children();

	try {
		$opt = array('resource' => 'stock_availables');
		$opt['putXml'] = $xml -> asXML();
		$opt['id'] = $stock_available_id;
		$xml = $webService -> edit($opt);
	} catch (PrestaShopWebserviceException $ex) {
		$trace = $ex -> getTrace();
		if ($trace[0]['args'][0] == 404)
			echo 'Bad ID';
		else if ($trace[0]['args'][0] == 401)
			echo 'Bad auth key';
		else
			echo 'Other error<br />' . $ex -> getMessage();
	}

	$url = PS_SHOP_PATH . '/api/images/products/' . $id_created_product;

	$image_path = $_FILES['upload']["tmp_name"];

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
