<?php
/* кодировка файла utf8 */

class Catalog_model {
	private static $instance;


	public static function singleton() {
		if (!isset(self::$instance)) {
			$c=__CLASS__;
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function __clone() {
		trigger_error('Clone is not allowed. ', E_USER_ERROR);
	}

	private function __construct() {
		
	}
	
	public static function init() {
		
	}

/* сравнение товаров ---------------------------------------------------------------------------------------------------------------------------------- */
/**
 * удаление товара из сравнения
 * @return 
 */
	public static function removeFromCompare($id) {
		if (isset($id)) {
			$sql = "DELETE FROM `shop_compare` WHERE `sc_hash` = '".Page::$DB->escape($_COOKIE['shop_compare'])."' AND `sc_sb_id` = '".$id."';";
			Page::$DB->query($sql);
		}
	}
/** ставим куку корзины
* @return 
*/
	public static function setCartCookie() {
		if (!isset($_COOKIE['shop_cart'])) {
			$hash = md5(session_id().' '.$_SERVER['HTTP_USER_AGENT'].' '.$_SERVER['REMOTE_ADDR']);
			setcookie('shop_cart', $hash, time()+86400*61*3, '/', NULL, NULL);
			$_COOKIE['shop_cart'] = $hash;
		} else {
			$hash = $_COOKIE['shop_cart'];
		}
		return $hash;
	}
/**
 * ставим куку сравнения
 * @return 
 */
	public static function setCompareCookie() {
		if (!isset($_COOKIE['shop_compare'])) {
			$hash = md5(session_id().' '.$_SERVER['HTTP_USER_AGENT'].' '.$_SERVER['REMOTE_ADDR']);
			setcookie('shop_compare', $hash, time()+864000, '/', NULL, NULL);
			$_COOKIE['shop_compare'] = $hash;
		} else {
			$hash = $_COOKIE['shop_compare'];
		}
		return $hash;
	}

/**
 * ставим куку хочу купить
 * @return 
 */
	public static function setWishCookie() {
		if (!isset($_COOKIE['shop_wish'])) {
			$hash = md5(session_id().' '.$_SERVER['HTTP_USER_AGENT'].' '.$_SERVER['REMOTE_ADDR']);
			setcookie('shop_wish', $hash, time()+864000, '/', NULL, NULL);
			$_COOKIE['shop_wish'] = $hash;
			
		} else {
			$hash = $_COOKIE['shop_wish'];
		}
		return $hash;
	}
/**
 * проверяем хочу купить
 * @return 
 */
	public static function getWishHash($itemId) {
		if (isset($_COOKIE['shop_wish'])) {
			$sql = "
				SELECT * FROM  `shop_wish` 
				WHERE `sw_hash` = '".Page::$DB->escape($_COOKIE['shop_wish'])."'
				AND `sw_sb_id`='".$itemId."'
			";
			$qw = Page::$DB->query($sql);
				if ($qw->num_rows()>0) {
					$sql1="SELECT `wish` FROM `shop_popularity` WHERE `sp_cb_id` = '".$itemId."';";
					$qw1=Page::$DB->query($sql1);
					if ($qw1->num_rows()>0) {
							$row = $qw1->fetch_row();
							$count=(intval($row[0])).' '.self::getPeoplePeoplov(intval($row[0]));
							return $count;
						}else{
							return false;
						}
					
				}else{
					return false;
				}
		}else{
			return false;
		}
	}
	
	public static function wish($itemid) {
		
		$hash=self::setWishCookie();
		if ($itemid>0) {
			$sql = "SELECT `raiting` FROM `shop_popularity` WHERE `sp_cb_id` = '".$itemid."' ";
			$qw = Page::$DB->query($sql);
			$sql1 = "INSERT INTO `shop_wish` (`sw_hash`,`sw_sb_id`) VALUES ('".$hash."', '".$itemid."')";
			Page::$DB->query($sql1);
			if ($qw->num_rows()==0) {
				$sql = "INSERT INTO `shop_popularity` (`wish`,`sp_cb_id`) VALUES ('1', '".$itemid."')";
				Page::$DB->query($sql);
				$sql = "UPDATE `shop_popularity`  
						SET `raiting` = `view`+`compare`+`wish`
						WHERE `sp_cb_id` = ".$itemid.";
						";
				Page::$DB->query($sql);
			}else{
				$sql = "UPDATE `shop_popularity`  
						SET `wish` = `wish`+'1',
						`raiting` = `view`+`compare`+`wish`
						WHERE `sp_cb_id` = ".$itemid.";
						";
				Page::$DB->query($sql);
			}
		}
	}
	
/**
 * удаляем куку compare
 * @return 
 */
	public static function delCompareCookie() {
		if (isset($_COOKIE['shop_compare'])) {
			$sql = "DELETE FROM `shop_compare` WHERE `sc_hash` = '".Page::$DB->escape($_COOKIE['shop_compare'])."'";
			Page::$DB->query($sql);
		}
		
		setcookie('shop_compare', '', time()-864000, '/', NULL, NULL);
		unset($_COOKIE['shop_compare']);
	}
	
/**
 * достаем записи из таблицы сравнения `shop_compare`
 * @return 
 */
	public static function queryCompare() {
		$sql = "
			SELECT * FROM `shop_compare` 
			WHERE `sc_hash` = '".Page::$DB->escape($_COOKIE['shop_compare'])."'
		";
		$qw = Page::$DB->query($sql);
		return $qw;
	}


/**
* Получаем данные о модели
*/
public static function getModelInfo($cb_id) {

	$sql = "
		SELECT cb.`cb_id`, cb.`sp_model`, cb.`cb_price1`, cb.`cb_price2`, cb.`ci_id` FROM `catalog_blocks` AS cb 
		WHERE  cb.`cb_id` = '".$cb_id."' LIMIT 1
	";
	$qw1 = Page::$DB->query($sql);
	
	if ($qw1->num_rows()>0) {
		$re1 = $qw1->fetch_assoc();
		$goods = array(
			'sp_model'=>$re1['sp_model'],
			'cb_price2'=>$re1['cb_price2']
		);
	} 
	return $goods;
	
		
}

/**
 * получаем товары из сравнения
 * @return 
 */
	public static function getCompareItems() {
		$qw = self::queryCompare();
		if ($qw->num_rows()>0) {
			$goods = array();
			$goods['count'] = 0;
			$goods['man'] = 0;
			$goods['woman'] = 0;
			while ($re=$qw->fetch_assoc()) {
				$sql = "
					SELECT cb.`cb_id`, cb.`sp_model`, cb.`cb_price2`, cb.`ci_id` FROM `catalog_blocks` AS cb 
					WHERE  cb.`cb_id` = '".$re['sc_sb_id']."'
				";
				$qw1 = Page::$DB->query($sql);
				if ($qw1->num_rows()>0) {
					$re1 = $qw1->fetch_assoc();
					$goods['items'][$re1['cb_id']] = array(
						'header'=>$re1['sp_model'],
						'amount'=>$re['sc_amount'],
						'price'=>$re1['cb_price2'],
						'url' => Menu::getNodeFullUrl(Catalog,$re1['ci_id'], Page::$lang).$re1['cb_id'].'.html'
					);
					
					$goods['count']+=1;
				} 
			}
			
			Page::assign('compare_goods', $goods);
		} 
	}
	public static function getCartItems($small=false) {
	
		$count_goods=0;
		$hash = self::setCartCookie();
		$sql = "SELECT sc.`sc_sb_id`,sc.`sc_size`,`cb`.`sp_model`,sv.`sv_val4`, cb.`ci_id`,sv.`sv_val5`, cb.`cb_url`, ci.`ci_url`,
				(SELECT cp.`photo_preview` FROM `catalog_photos` AS cp WHERE cp.`cb_id` = cb.`cb_id` AND cp.`photo_show` = '1' ORDER BY cp.`photo_order` ASC LIMIT 1) AS img
				FROM `shop_cart` AS `sc`  
				INNER JOIN `catalog_blocks` AS `cb` ON (cb.`cb_id` = sc.`sc_sb_id` AND  sc.`sc_hash` = '".$hash."')
				INNER JOIN `catalog_index` AS `ci` ON (ci.`ci_id` = cb.`ci_id`)
				INNER JOIN `sprav_values` AS `sv` ON (sc.`sc_size` = sv.`sv_val3` AND sv.`sp_name`='sizes' AND cb.`cb_ext_id`=sv.`sv_val1`)
				WHERE  cb.`block_show` = '1'
				GROUP BY sc.`sc_sb_id`
				ORDER BY sv.`sv_val4` DESC;
				
		";
		
		$cart=array();
		$qw = Page::$DB->query($sql);
				if ($qw->num_rows() >0) {
					$salePrice=0;
					while ($re = $qw->fetch_assoc()) {
							
						$genPrice=intval($re['sv_val4']);
						$re['salePrice']=$genPrice;
						$cart[$re['sc_sb_id']] = $re; 
						$count_goods+=1;
					}
				}
		if ($small==true){
				$good_str=array('str'=>self::getTovarTovarov($count_goods),
								'count'=>$count_goods
								);
				return $good_str;
			}else{
				return $cart;
			}
		}
	public static function getOrderItems($id,$size) {
		if ((isset($id)&&!empty($id))||(isset($size)&&!empty($size))){
		$hash = self::setCartCookie();
		$sql = "SELECT `cb`.`sp_model`,sv.`sv_val4`
				FROM `catalog_blocks` AS `cb` 
				INNER JOIN `sprav_values` AS `sv` ON (sv.`sv_val3`= '".$size."' AND sv.`sp_name`='sizes' AND cb.`cb_ext_id`=sv.`sv_val1`)
				WHERE  cb.`cb_id` = '".$id."' 
				AND cb.`block_show` = '1' 
				
				ORDER BY sv.`sv_val4` DESC;
				
		";
		$order=array();
		$qw = Page::$DB->query($sql);
				if ($qw->num_rows() >0) {
					while ($re = $qw->fetch_assoc()) {
						$order['salePrice']=intval($re['sv_val4']);
						$order['model']=$re['sp_model'];
						$order['size']=$size;
					}
				}
			return $order;
		}else{
			return false;
		}	
	}
	public static function setSavedCookie() {
		if (!isset($_COOKIE['shop_saved'])) {
			$hash = md5(session_id().' '.$_SERVER['HTTP_USER_AGENT'].' '.$_SERVER['REMOTE_ADDR']);
			setcookie('shop_saved', $hash, time()+864000, '/', NULL, NULL);
			$_COOKIE['shop_saved'] = $hash;
		} else {
			$hash = $_COOKIE['shop_saved'];
		}
		return $hash;
	}

	public static function getFullCompareItems(array $ci_ids = array(), $sex_class=false){
		$arr = array();
		$ci_ids = array_map('intval', $ci_ids);
		$qw = self::queryCompare();

		if ($qw->num_rows()>0) {
			$compare = array();
			$compare['man'] = 0;
			$compare['woman'] = 0;
			$result=array(
				'0' => array(), //Женьщин
				'1' => array() //Мужьчин
			);
			while ($re=$qw->fetch_assoc()){
				$sql = "
				SELECT cb.`cb_url`,cb.`ci_id`, `ci`.`ci_url` , cb.`cb_price1`, cb.`cb_price2`, cb.`cb_dlina`, cb.`block_order`, cb.`block_show`, cb.`sp_model`, cb.`cb_sex`, cb.`sp_hood`, cb.`cb_actions`,cb.`cb_status`, cbc.`cbc_header`, cbc.`cbc_anonce`, cbc.`cbc_text`,
				sv.`sv_val2` as color, sv1.`sv_val2` as raw , sv2.`sv_val2` as decor , sv3.`sv_val2` as country , sv4.`sv_val2` as decor , sv5.`sv_val2` as composition, sv6.`sv_val2` 
				FROM `catalog_blocks` AS cb
					INNER JOIN `catalog_blocks_cont` AS `cbc` ON (`cbc`.`cb_id` = `cb`.`cb_id`)
					INNER JOIN `catalog_index` AS `ci` ON (`ci`.`ci_id` = `cb`.`ci_id`)
					LEFT JOIN `sprav_values` AS `sv` ON (`cb`.`sp_color` = `sv`.`sv_val1`)
					LEFT JOIN `sprav_values` AS `sv1` ON (`cb`.`sp_raw` = `sv1`.`sv_val1`)
					LEFT JOIN `sprav_values` AS `sv2` ON (`cb`.`sp_type` = `sv2`.`sv_val1`)
					LEFT JOIN `sprav_values` AS `sv3` ON (`cb`.`sp_country` = `sv3`.`sv_val1`)
					LEFT JOIN `sprav_values` AS `sv4` ON (`cb`.`sp_decor` = `sv4`.`sv_val1`)
					LEFT JOIN `sprav_values` AS `sv5` ON (`cb`.`sp_sostav` = `sv5`.`sv_val1`)
					LEFT JOIN `sprav_values` AS `sv6` ON (`cb`.`sp_sprav_sostav` = `sv6`.`sv_val1`)
								WHERE cbc.`lang_id` = '".Page::$lang."' 
								".((!Page::$preview) ? "AND cb.`block_show` = '1'" :"")."  
								AND cb.`cb_id` = '".$re['sc_sb_id']."' 
								AND sv.`sp_name`='color'
								AND sv1.`sp_name`='raw'
								AND sv2.`sp_name`='decor'
								AND sv3.`sp_name`='countries'
								AND sv4.`sp_name`='decor'
								AND sv5.`sp_name`='composition'
								" . ($ci_ids ? "AND `cb`.`ci_id` IN (" . implode(",", $ci_ids) . ")" : "") . "
					LIMIT 1;";
				
				
				$res = Page::$DB->query($sql);
				$num = $res->num_rows();
				if ($num > 0) {
					$arr[$re['sc_sb_id']] = $res->fetch_assoc();
					$but_wish=self::getWishHash($re['sc_sb_id']);
					if ($but_wish!=false){
						$arr[$re['sc_sb_id']]['wish']=$but_wish;
					}
					$arr[$re['sc_sb_id']]['cbc_text'] = Parser::parseTable(stripslashes($arr[$re['sc_sb_id']]['cbc_text']), (int)$arr['cbc_ptbl_id']);
					($re['sc_gender'] == '2') ? $compare['man'] +=1 :  $compare['woman'] +=1;
					$sql = "
						SELECT `photo_preview` FROM `catalog_photos` 
						WHERE `cb_id` = '".$re['sc_sb_id']."' 
									AND `photo_show` = '1' 
						
						LIMIT 1
					";
					$qw1=Page::$DB->query($sql);
					
					if ($qw1->num_rows()>0) {
						while ($re1 = $qw1->fetch_assoc()) {
							$arr[$re['sc_sb_id']]['photos'] = $re1; 
						}
					} 
						
					
					$sql1 = "
				
					SELECT sv1.`sv_val1` as s_id, sv1.`sv_val2` as `stores_name`, sv.`sv_val3` as sizes, sv.`sv_val4` as price2, sv.`sv_val5` as `price1`
					FROM `catalog_blocks` AS cb
						INNER JOIN `catalog_blocks_cont` AS `cbc` ON (`cbc`.`cb_id` = `cb`.`cb_id`)
						LEFT JOIN `sprav_values` AS `sv` ON (`cb`.`cb_ext_id` = `sv`.`sv_val1`)
						LEFT JOIN `sprav_values` AS `sv1` ON (`sv`.`sv_val2` = `sv1`.`sv_val1`)
									WHERE cbc.`lang_id` = '".Page::$lang."' 
									".((!Page::$preview) ? "AND cb.`block_show` = '1'" :"")."  
									AND cb.`cb_id` = '".$re['sc_sb_id']."' 
									AND sv.`sp_name`='sizes'
									AND sv1.`sp_name`='stores'
									ORDER BY `stores_name`
								;";
					$qw1 = Page::$DB->query($sql1);
					$num = $res->num_rows();
					
					if ($num > 0) {
						while ($re1 = $qw1->fetch_assoc()) {
							$arr[$re['sc_sb_id']]['size_stores'][$re1['price1']][$re1['price2']][$re1['sizes']][] = $re1;

							/* --- --- --- */
							if (!isset($arr[$re['sc_sb_id']]['sizes'])) $arr[$re['sc_sb_id']]['sizes'] = array('storage' => array(), 'shop' => array());

							if (!isset($arr[$re['sc_sb_id']]['sizes']['shop'][$re1['price1']])) $arr[$re['sc_sb_id']]['sizes']['shop'][$re1['price1']] = array();
							if (!isset($arr[$re['sc_sb_id']]['sizes']['shop'][$re1['price1']][$re1['price2']])) $arr[$re['sc_sb_id']]['sizes']['shop'][$re1['price1']][$re1['price2']] = array();
							if (!isset($arr[$re['sc_sb_id']]['sizes']['shop'][$re1['price1']][$re1['price2']][$re1['sizes']])) $arr[$re['sc_sb_id']]['sizes']['shop'][$re1['price1']][$re1['price2']][$re1['sizes']] = array();
							$arr[$re['sc_sb_id']]['sizes']['shop'][$re1['price1']][$re1['price2']][$re1['sizes']][] = $re1;

							if ('000000001' == $re1['s_id'] || '000000002' == $re1['s_id'] || '000000003' == $re1['s_id']){
								if (!isset($arr[$re['sc_sb_id']]['sizes']['storage'][$re1['price1']])) $arr[$re['sc_sb_id']]['sizes']['storage'][$re1['price1']] = array();
								if (!isset($arr[$re['sc_sb_id']]['sizes']['storage'][$re1['price1']][$re1['price2']])) $arr[$re['sc_sb_id']]['sizes']['storage'][$re1['price1']][$re1['price2']] = array();
								if (!isset($arr[$re['sc_sb_id']]['sizes']['storage'][$re1['price1']][$re1['price2']][$re1['sizes']])) $arr[$re['sc_sb_id']]['sizes']['storage'][$re1['price1']][$re1['price2']][$re1['sizes']] = array();
								$arr[$re['sc_sb_id']]['sizes']['storage'][$re1['price1']][$re1['price2']][$re1['sizes']][] = $re1;
							}
							/* /--- --- --- */
						}
					
					}
					if (!empty($arr[$re['sc_sb_id']]['size_stores'])){
						ksort($arr[$re['sc_sb_id']]['size_stores']);
						foreach ($arr[$re['sc_sb_id']]['size_stores'] as $arr_small){
							ksort($arr_small);
						}
					}
					if (isset($arr[$re['sc_sb_id']]['sizes']['shop']) && !empty($arr[$re['sc_sb_id']]['sizes']['shop'])){
						ksort($arr[$re['sc_sb_id']]['sizes']['shop']);
						foreach ($arr[$re['sc_sb_id']]['sizes']['shop'] as &$arr_small){
							array_walk($arr_small, function(&$item, $key){
								ksort($item);
							});
						}
						unset($arr_small);
					}
					if (isset($arr[$re['sc_sb_id']]['sizes']['storage']) && !empty($arr[$re['sc_sb_id']]['sizes']['storage'])){
						ksort($arr[$re['sc_sb_id']]['sizes']['storage']);
						foreach ($arr[$re['sc_sb_id']]['sizes']['storage'] as &$arr_small){
							array_walk($arr_small, function(&$item, $key){
								ksort($item);
							});
						}
						unset($arr_small);
					}
				}
				if (($arr[$re['sc_sb_id']]['cb_sex'])=='ж'){
					$result['0'][$re['sc_sb_id']]=$arr[$re['sc_sb_id']];
				}
				if (($arr[$re['sc_sb_id']]['cb_sex'])=='м'){
					$result['1'][$re['sc_sb_id']]=$arr[$re['sc_sb_id']];
				}
			}
			
			if ($sex_class==true){
				Page::assign('compare', $result);
				Page::assign('compare_count', $compare);
				return $result;
			}else{
				Page::assign('compare', $arr);
				Page::assign('compare_count', $compare);
				

				return $arr;
			}
		}
	}	
	public static function getFullSizes($id, &$arr_sizes = array()){
		
		$sql="SELECT sv.sv_val2, sv.sv_val3, sv.sv_val4, sv.sv_val5, sv.sv_val6, sv.sv_val7, sv.sv_val8, sv.sv_val9, sv.sv_val10
				FROM `catalog_blocks` AS cb
				INNER JOIN `sprav_values` AS `sv` ON (`cb`.`cb_ext_id` = `sv`.`sv_val1`)
				".((!Page::$preview) ? "AND cb.`block_show` = '1'" :"")."  
				AND cb.`cb_id` = '".$id."' 
				AND sv.`sp_name`='model_sizes'
				
				GROUP by sv.`sv_val2`
				ORDER by sv.`sv_val2`;";
			$res = Page::$DB->query($sql);
			$num = $res->num_rows();
			$arr = array();
			$arr_sizes = array();
			if ($num > 0) {
				while ($re = $res->fetch_assoc()) {
					if (!in_array($re['sv_val2'], $arr_sizes)){
						$arr_sizes[] = $re['sv_val2'];
					}
					if ($re['sv_val3']>0){
						if (!isset($arr['Обхват груди'])) $arr['Обхват груди'] = array();
						$arr['Обхват груди'][$re['sv_val2']] = $re['sv_val3'];
					}
					if ($re['sv_val4']>0){
						if (!isset($arr['Обхват талии'])) $arr['Обхват талии'] = array();
						$arr['Обхват талии'][$re['sv_val2']] = $re['sv_val4'];
					}
					if ($re['sv_val5']>0){
						if (!isset($arr['Обхват бедер'])) $arr['Обхват бедер'] = array();
						$arr['Обхват бедер'][$re['sv_val2']] = $re['sv_val5'];
					}
					if ($re['sv_val6']>0){
						if (!isset($arr['Длина изделия'])) $arr['Длина изделия'] = array();
						$arr['Длина изделия'][$re['sv_val2']] = $re['sv_val6'];
					}
					if ($re['sv_val7']>0){
						if (!isset($arr['Длина рукава'])) $arr['Длина рукава'] = array();
						$arr['Длина рукава'][$re['sv_val2']] = $re['sv_val7'];
					}
					if ($re['sv_val8']>0){
						if (!isset($arr['Длина плеча'])) $arr['Длина плеча'] = array();
						$arr['Длина плеча'][$re['sv_val2']] = $re['sv_val8'];
					}

					if ($re['sv_val9']>0){
						if (!isset($arr['Длина спинки до талии'])) $arr['Длина спинки до талии'] = array();
						$arr['Длина спинки до талии'][$re['sv_val2']] = $re['sv_val9'];
					}
					if ($re['sv_val10']>0){
						if (!isset($arr['Обхват изделия по низу'])) $arr['Обхват изделия по низу'] = array();
						$arr['Обхват изделия по низу'][$re['sv_val2']] = $re['sv_val10'];
					}
					
				}
			}
		return $arr;
	}
/**
 * правильно склоняем слово товар: "10 товаров в корзине"
 * @param object $count
 * @return 
 */
	public static function getTovarTovarov($count) {
		$str = '';
		$k = $count % 100;
		$k1 = $count % 10;
		if (((int)$k1==1)&&($k!=11)) {
			$str='товар';
		} elseif ((($k1==2)||($k1==3)||($k1==4))&&($k!=12)&&($k!=13)&&($k!=14)) {
			$str='товара';
		} else {
			$str='товаров';
		}
	return $str;
	}
	
	public static function getPeoplePeoplov($count) {
		$str = '';
		$k = $count % 100;
		$k1 = $count % 10;
		if (((int)$k1==1)&&($k!=11)) {
			$str='человек';
		} elseif ((($k1==2)||($k1==3)||($k1==4))&&($k!=12)&&($k!=13)&&($k!=14)) {
			$str='человека';
		} else {
			$str='человек';
		}
	return $str;
	}
	
	public static function getDays($count) {
		$arr=explode('-',$count);
		$count=$arr[1];
		$str = '';
		$k = $count % 100;
		$k1 = $count % 10;
		if (((int)$k1==1)&&($k!=11)) {
			$str='день';
		} elseif ((($k1==2)||($k1==3)||($k1==4))&&($k!=12)&&($k!=13)&&($k!=14)) {
			$str='дня';
		} else {
			$str='дней';
		}
	return $str;
	}

/* недавно просмотренные товары ---------------------------------------------------------------------------------------------------------------------- */

/**
 * ставим куку lately
 * @return 
 */
	public static function setLatelyCookie() {
		if (!isset($_COOKIE['shop_lately'])) {
			$hash = md5(session_id().' '.$_SERVER['HTTP_USER_AGENT'].' '.$_SERVER['REMOTE_ADDR'].' lately');
			setcookie('shop_lately', $hash, time()+1296000, '/', NULL, NULL);
			$_COOKIE['shop_lately'] = $hash;
		} else {
			$hash = $_COOKIE['shop_lately'];
		}
		return $hash;
	}

/**
 * удаляем куку lately
 * @return 
 */
	public static function delLatelyCookie() {
		if (isset($_COOKIE['shop_lately'])) {
			$sql = "DELETE FROM `shop_lately` WHERE `sc_hash` = '".Page::$DB->escape($_COOKIE['shop_lately'])."'";
			Page::$DB->query($sql);
		}
		
		setcookie('shop_lately', '', time()-1296000, '/', NULL, NULL);
		unset($_COOKIE['shop_lately']);
	}
	
/**
 * достаем записи из недавно просмотренных `shop_lately`
 * @return 
 */
	public static function queryLately() {
		$sql = "
			SELECT * FROM `shop_lately` 
			WHERE `sc_hash` = '".Page::$DB->escape($_COOKIE['shop_lately'])."'
		";
		
		$qw = Page::$DB->query($sql);
		return $qw;
	}
	
	public static function getMostPop() {
	$qw = self::queryLately();
		if ($qw->num_rows()>0) {
			$goods = array();
			while ($re=$qw->fetch_assoc()) {
				$sql = "
					SELECT cb.`ci_id`, cb.`cb_id`
					FROM `catalog_blocks` AS cb
					WHERE cb.`block_show` = '1'
					AND cb.`cb_id` = '".$re['sc_sb_id']."'
					
				";
				
				$qw1 = Page::$DB->query($sql);
				if ($qw1->num_rows()>0) {
						$re1 = $qw1->fetch_assoc();
						$goods[] = $re1['ci_id'];
					} 
			} 
			$goods=array_count_values($goods);
			arsort($goods);
			return $goods;
		}
	}
/**
 *
 * 
 * получаем список недавно просмотренных товаров
 * @return 
 */
	public static function getLatelyItems() {
		$qw = self::queryLately();
		if ($qw->num_rows()>0) {
			$goods = array();
			while ($re=$qw->fetch_assoc()) {
					$sql = "
						SELECT cb.`ci_id`, cb.`cb_id`,cb.`cb_url`,  cb.`block_show`, cb.`sp_model`, cb.`cb_price1`, cb.`cb_price2`,  cb.`cb_status`, cb.`cb_actions`,
							(SELECT cp.`photo_preview` FROM `catalog_photos` AS cp WHERE cp.`cb_id` = cb.`cb_id` AND cp.`photo_show` = '1' ORDER BY cp.`photo_order` ASC LIMIT 1) AS img
							
						FROM `catalog_blocks` AS cb
						WHERE cb.`block_show` = '1'
						AND cb.`cb_id` = '".$re['sc_sb_id']."'
						
					";
				$qw1 = Page::$DB->query($sql);
				
				if ($qw1->num_rows()>0) {
					$re1 = $qw1->fetch_assoc();
					if ($re1['cb_price1']==0) {
						$re1['cb_price1']=$re1['cb_price2'];						
					}
					$goods['items'][$re1['cb_id']] = array(
							'block_id'=>$re1['cb_id'],
							'block_url'=>$re1['cb_url'],
							'header'=>$re1['sp_model'],
							'img'=>$re1['img'],
							'price1'=>$re1['cb_price1'],
							'price2'=>$re1['cb_price2'],
							'status'=>$re1['cb_status'],
							'cb_actions'=>$re1['cb_actions'],
							'url' => Menu::getNodeFullUrl(Catalog,$re1['ci_id'], Page::$lang).$re1['cb_url'].'.html'
						);
					
					} 
			}
			
			$goods['items']=array_reverse($goods['items']);
			Page::assign('lately_goods', $goods);
		} 
	}


/**
 * добавляем в недавно просмотренные
 * @param object $itemid
 * @return 
 */
	public static function toLately($itemid) {
		$hash = self::setLatelyCookie();
 
		if ($itemid>0) {
			$sql = "SELECT `sc_hash` FROM `shop_lately` WHERE `sc_hash` = '".$hash."' AND `sc_sb_id` = '".$itemid."'";
			$qw = Page::$DB->query($sql);
			if ($qw->num_rows()==0) {
				$sql = "INSERT INTO `shop_lately` (`sc_hash`,`sc_sb_id`) VALUES ('".$hash."', '".$itemid."')";
			}
			Page::$DB->query($sql);
		}
	}

/**
 * добавляем|обновляем популярность
 * @param object $itemid
 * @return 
 */
	public static function view($itemid) {
		 
		if ($itemid>0) {
			$sql = "SELECT `raiting` FROM `shop_popularity` WHERE `sp_cb_id` = '".$itemid."' ";
			$qw = Page::$DB->query($sql);
			if ($qw->num_rows()==0) {
				$sql = "INSERT INTO `shop_popularity` (`view`,`sp_cb_id`) VALUES ('1', '".$itemid."')";
				Page::$DB->query($sql);
				$sql = "UPDATE `shop_popularity`  
						SET `raiting` = `view`+`compare`+`wish`
						WHERE `sp_cb_id` = ".$itemid.";
						";
				Page::$DB->query($sql);
			}else{
				$sql = "UPDATE `shop_popularity`  
						SET `view` = `view`+'1',
						`raiting` = `view`+`compare`+`wish`
						WHERE `sp_cb_id` = ".$itemid.";
						";
				Page::$DB->query($sql);
			}
		}
	}
	
	
	public static function compare($itemid) {
		 
		if ($itemid>0) {
			$sql = "SELECT `raiting` FROM `shop_popularity` WHERE `sp_cb_id` = '".$itemid."' ";
			$qw = Page::$DB->query($sql);
			if ($qw->num_rows()==0) {
				$sql = "INSERT INTO `shop_popularity` (`compare`,`sp_cb_id`) VALUES ('1', '".$itemid."')";
				Page::$DB->query($sql);
				$sql = "UPDATE `shop_popularity`  
						SET `raiting` = `view`+`compare`+`wish`
						WHERE `sp_cb_id` = ".$itemid.";
						";
				Page::$DB->query($sql);
			}else{
				$sql = "UPDATE `shop_popularity`  
						SET `compare` = `compare`+'1',
						`raiting` = `view`+`compare`+`wish`
						WHERE `sp_cb_id` = ".$itemid.";
						";
				Page::$DB->query($sql);
			}
		}
	}
	
	
public static function makePayForm($fio,$price,$adress,$id){
	error_reporting(0);
	require_once USES_FOLDER.'/libs/tcpdf/tcpdf.php';
	require_once USES_FOLDER.'/libs/tcpdf/config/tcpdf_config.php';
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
					$pdf->SetPrintHeader(false); 
					$pdf->SetPrintFooter(false);
					$pdf->SetCreator(PDF_CREATOR);
					$pdf->SetAuthor('Магазин Барс');
					$pdf->SetTitle('Квитанция на оплату');
					$pdf->SetHeaderData(false, false, false, false);
					$pdf->setHeaderFont(false);
					$pdf->setFooterFont(false);
					$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
					$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
					$pdf->SetHeaderMargin(0);
					$pdf->SetFooterMargin(0);
					$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
					$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
					if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
						require_once(dirname(__FILE__).'/lang/eng.php');
						$pdf->setLanguageArray($l);
					}
					$pdf->SetFont('dejavusans', '', 7.5);

	$pdf->AddPage();
$cur_year=date('Y');

$html = '
<table width="100%" border="1" cellspacing="0">
	<tr>
		<td width="210" valign="top" height="200" align="center">&nbsp;<strong>Извещение</strong></td>
		<td width="430" valign="top" height="200"><ul>
			<tr><td><li></li>&nbsp;</td></tr>
			<tr><td><li></li><strong>Получатель: </strong><font style="font-size:90%"> ИП Воронцов Александр Константинович</font>&nbsp;&nbsp;&nbsp;</td></tr>
			<tr><td><li></li><strong>ИНН:</strong> 667400863697&nbsp;&nbsp;&nbsp; <strong>ОГРН:</strong> 308667410200110&nbsp;&nbsp;<font style="font-size:12px"> &nbsp;</font>&nbsp;</td></tr>
			<tr><td><li></li><strong>P/сч.:</strong> 40802810438260000115 &nbsp;&nbsp;&nbsp;</td></tr>
			<tr><td><li></li><strong>в:</strong> <font style="font-size:90%">Филиал «Екатеринбургский» ОАО «Альфа-Банк», 620000, г. Екатеринбург,<br/> ул. Сони Морозовой д.190</font><br /></td></tr>
			<tr><td><li></li><strong>БИК:</strong> 046577964&nbsp; <strong>К/сч.:</strong> 30101810100000000964<br /></td></tr>
			<tr><td><li></li><strong>Платеж:</strong> <font style="font-size:90%">Предоплата за заказ № '.$id.' в Интернет-магазине www.shubbing.ru</font><br /></td></tr>
			<tr><td><li></li><strong>Плательщик:</strong>  '.$fio.'<br /></td></tr>
			<tr><td><li></li><strong>Адрес плательщика:</strong> <font style="font-size:90%"> '.$adress.'</font><br /></td></tr>
			<tr><td><li></li><strong>Сумма:</strong> '.$price.' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br /> &nbsp;<br /><br /></td></tr>
			<tr><td>Подпись:________________________        Дата: &quot;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&quot;_________________ ' . $cur_year . ' г. <br /><br /> </td></tr>
			</ul>
		</td>
	</tr>
	
	<tr>
		<td width="210" valign="top" height="200" align="center">&nbsp;<strong>Квитанция</strong></td>
		<td width="430" valign="top" height="200"><ul>
			<tr><td><li></li>&nbsp;</td></tr>
			<tr><td><li></li><strong>Получатель: </strong><font style="font-size:90%"> ИП Воронцов Александр Константинович</font>&nbsp;&nbsp;&nbsp;</td></tr>
			<tr><td><li></li><strong>ИНН:</strong> 667400863697&nbsp;&nbsp;&nbsp; <strong>ОГРН:</strong> 308667410200110&nbsp;&nbsp;<font style="font-size:12px"> &nbsp;</font>&nbsp;</td></tr>
			<tr><td><li></li><strong>P/сч.:</strong> 40802810438260000115 &nbsp;&nbsp;&nbsp;</td></tr>
			<tr><td><li></li><strong>в:</strong> <font style="font-size:90%">Филиал «Екатеринбургский» ОАО «Альфа-Банк», 620000, г. Екатеринбург,<br/> ул. Сони Морозовой д.190</font><br /></td></tr>
			<tr><td><li></li><strong>БИК:</strong> 046577964&nbsp; <strong>К/сч.:</strong> 30101810100000000964<br /></td></tr>
			<tr><td><li></li><strong>Платеж:</strong> <font style="font-size:90%">Предоплата за заказ № '.$id.' в Интернет-магазине www.shubbing.ru</font><br /></td></tr>
			<tr><td><li></li><strong>Плательщик:</strong>  '.$fio.'<br /></td></tr>
			<tr><td><li></li><strong>Адрес плательщика:</strong> <font style="font-size:90%"> '.$adress.'</font><br /></td></tr>
			<tr><td><li></li><strong>Сумма:</strong> '.$price.' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br /> &nbsp;<br /><br /></td></tr>
			<tr><td>Подпись:________________________        Дата: &quot;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&quot;_________________ ' . $cur_year . ' г. <br /><br /> </td></tr>
			</ul>
		</td>
	</tr>
	
</table>

';
	$pdf->writeHTML($html, true, false, true, false, '');
	$pdf->lastPage();
	$pdf->Output('form_bank.pdf', 'I');

	
}
public static function getCategories(){		
		$sql = "SELECT cic.`cic_name`,cic.`ci_id` , ci.`index_parent`
			FROM `catalog_index_cont` AS `cic`
			INNER JOIN `catalog_index` AS `ci` ON (cic.`ci_id` = ci.`ci_id`)
			WHERE ci.`index_show` = '1'
			ORDER BY ci.`index_parent`, cic.`cic_name` ASC
			;";
		
		$res = Page::$DB->query($sql);
		if ($res->num_rows()>0) {
				while ($re = $res->fetch_assoc()) {
					$categories[$re['ci_id']]= array(
					'name'=>$re['cic_name'],
					'id'=>$re['ci_id'],
					'parentId'=>$re['index_parent']
					);
				}
			}
	return $categories;
}

public static function getOffers(){
	$sql = "SELECT cb.`cb_id`,cb.`sp_model`,cb.`cb_price2`,cb.`ci_id`, cbc.`cbc_text`
	FROM `catalog_blocks` AS `cb`
		INNER JOIN `catalog_blocks_cont` AS `cbc` ON (cb.`cb_id` = cbc.`cb_id`)
	WHERE cb.`block_show` = '1';";
	$res = Page::$DB->query($sql);
	if ($res->num_rows()>0){
		while ($re = $res->fetch_assoc()){
			$o = array(
				'id'=>$re['cb_id'],
				'c_id'=>$re['ci_id'],
				'price'=>$re['cb_price2'],
				'name'=>'Модель '.$re['sp_model'],
				'description'=>strip_tags($re['description']),
				'pictures' => array(),
				'url'=>'http://www.shubbing.ru/catalog/'.$re['ci_id'].'/'.$re['cb_id'].'.html'
			);

			$sql_i = "SELECT `photo_preview` FROM `catalog_photos` WHERE `cb_id` = " . intval($re['cb_id']) . " AND `photo_show` = '1' ORDER BY `photo_order` ASC;";
			$res_i = Page::$DB->query($sql_i);
			while ($re_i = $res_i->fetch_assoc()){
				$o['pictures'][] = 'http://www.shubbing.ru/public/content/catalog/big/' . $re_i['photo_preview'];
			}

			$offers[] = $o;
		}
	}
	return $offers;
}

public static function getOfferParam($id){
	$sql = "SELECT
		sv.`sv_val2` as color,
		sv1.`sv_val2` as raw,
		sv3.`sv_val2` as country,
		sv4.`sv_val2` as decor,
		sv5.`sv_val2` as composition,
		cb.`cb_dlina` as dlina,
		`sv6`.`sv_val3` AS `size`
	FROM `catalog_blocks` AS `cb`
                    INNER JOIN `catalog_blocks_cont` AS `cbc` ON (cb.`cb_id` = cbc.`cb_id`)
                    LEFT JOIN `sprav_values` AS `sv` ON (`cb`.`sp_color` = `sv`.`sv_val1`)
                    LEFT JOIN `sprav_values` AS `sv1` ON (`cb`.`sp_raw` = `sv1`.`sv_val1`)
                    
                    LEFT JOIN `sprav_values` AS `sv3` ON (`cb`.`sp_country` = `sv3`.`sv_val1`)
                    LEFT JOIN `sprav_values` AS `sv4` ON (`cb`.`sp_decor` = `sv4`.`sv_val1`)
                    LEFT JOIN `sprav_values` AS `sv5` ON (`cb`.`sp_sostav` = `sv5`.`sv_val1`)

					LEFT JOIN  `sprav_values` AS  `sv6` ON (`cb`.`cb_ext_id` =  `sv6`.`sv_val1` AND `sv6`.sp_name =  'sizes')

                    WHERE 	cb.`cb_id`=".$id."
										AND cb.`block_show` = '1' 
                                        AND sv.`sp_name`='color'
                                        AND sv1.`sp_name`='raw'
                                        
                                        AND sv3.`sp_name`='countries'
                                        AND sv4.`sp_name`='decor'
                                        AND sv5.`sp_name`='composition'
			;";

		$res = Page::$DB->query($sql);
		if ($res->num_rows()>0) {
			$param = array();
			while ($re = $res->fetch_assoc()){
				if (empty($param)){
					$param = array(
						'Цвет'=>$re['color'],
						'Сырье'=>$re['raw'],
						'Страна'=>$re['country'],
						'Состав'=>$re['composition'],
						'Отделка'=>$re['decor'],
						'Длина'=>$re['dlina'],
						'Размер'=>array()
					);
				}

				$param['Размер'][] = $re['size'];
			}
		}
		return $param;
	}
	public static function get_areas() {
		$name_area = array();
		$sql ="SELECT *
			   FROM `consumer_dpd_areas` 
			   ORDER BY `area` ASC";
		$res = Page::$DB->query($sql);
		if ($res->num_rows()>0) {
				while ($re = $res->fetch_assoc()) {
					$name_area[$re['id']]=$re['area'];
				}
			}
		asort($name_area);
		return $name_area;
	}
	
	
	public static function get_area_by_city($id) {
		$name_area = array();
		$sql ="SELECT `cdc`.`id`
			   FROM `consumer_dpd_areas` AS `cdc`
			   INNER JOIN `consumer_dpd` AS `cd` ON (`cd`.`area_id` = `cdc`.`id`)
			   WHERE `cd`.`id` = ".$id.";" ;
			   
		$res = Page::$DB->query($sql);
		if ($res->num_rows()>0) {
				return (int)$res->get_one(0, 0);
			}
		return false;
	}
	
	public static function get_dpd_citys ($area){
		
		$city_arr = array();
		$sql = "SELECT `id`, `city`,`price`,`period`
				FROM `consumer_dpd` 
				WHERE `area_id` =". $area;
				
		$res = Page::$DB->query($sql);
		if ($res->num_rows()>0) {
			while ($re = $res->fetch_assoc()) {
				if ($re['id']==1){
					$re['price']=Page::$AllSettings['Catalog']['ekb_price'][1]['s_val'];
					$re['period']=Page::$AllSettings['Catalog']['ekb_period'][1]['s_val'];
					
				}
					$re['days']=Catalog_model::getDays($re['period']);
					$city_arr[]=$re;
			}
			return $city_arr;
		}
		
	}
	public static function helpfulness($fq_id,$cell=NULL){
		$result=array('yes'=>0,
					 'no'=>0);
		if (isset($cell)&&!empty($cell)) {
			$sql = "UPDATE `rws_questions` 
					SET ".$cell." = `".$cell."` + 1
					WHERE `fq_id` = ".$fq_id."
					LIMIT 1;
					";
			Page::$DB->query($sql);
		}
		$sql = "SELECT `fq_helpfulness_yes` as yes,`fq_helpfulness_no` as no
				FROM `rws_questions` 
				WHERE `fq_id` = ".$fq_id." LIMIT 1;
				";
		$res = Page::$DB->query($sql);
		if ($res->num_rows()>0) {
			while ($re = $res->fetch_assoc()) {
				$result=$re;
			}
		return $result;
	
		}	
	}
	public static function getGoodRaiting ($cb_id){
		$raiting=array();
		$count=0;
		$sql = "SELECT `raiting`.`raiting`
				FROM `rws_questions`  as `reviews`
				LEFT JOIN `rws_goods` as `raiting` ON (`reviews`.`fq_id`= `raiting`.`fq_id`)
				WHERE `fi_id` = 1
				AND `raiting`.`cb_id` = ".(int)$cb_id."
				AND  `reviews`.`fq_is_show` = '1'
				";
		$res = Page::$DB->query($sql);

		if ($res->num_rows()>0) {
			while ($re = $res->fetch_assoc()) {
				$raiting[]=$re['raiting'];
				$count++;
			}
		}
		$raiting = ($count > 0 ? (array_sum($raiting) / count($raiting)*100)/5 : 0);
		return $raiting;
	}
	public static function getShopReviews ($limit=NULL,$sort='ORDER BY `rws`.`date_add` DESC',$main=false){
		$sql = "SELECT SQL_CALC_FOUND_ROWS `rws`.`fq_id`,`rws`.`fi_id`,`rws`.`date_add`,`rws`.`fq_date_add`,`rws`.`fq_author`,`rws`.`fq_author_city`,`rws`.`fq_text`,`ans`.`fa_text`
				FROM `rws_questions`  as `rws`
				LEFT JOIN `rws_answers` as `ans` ON (`rws`.`fq_id`= `ans`.`fq_id`)
				WHERE  `rws`.`fq_is_show` = '1'
				AND `rws`.`fi_id` = '2'
				".(isset($main)&($main==1) ? " AND `fq_show_main` = '1' " : '' )."
				".$sort."
				".(isset($limit)&&!empty($limit) ?  $limit : '' )."
				
			";
		
		$res = Page::$DB->query($sql);
		$sql_c = "SELECT FOUND_ROWS();";
		$res_c = Page::$DB->query($sql_c);
		$count_blocks = (int)$res_c->get_one(0, 0);

		
		
		if ($res->num_rows()>0) {
			while ($re = $res->fetch_assoc()) {
				
				$reviews[$re['fq_id']]=$re;
				$reviews[$re['fq_id']]['count_blocks']=$count_blocks;
				$reviews[$re['fq_id']]['helpfulness']=self::helpfulness($re['fq_id']);
			}
		}
		

		return $reviews;
			
	}
	public static function getGoodReviews ($cb_id){
		$reviews=array();
		
		$sql = "SELECT `rws`.`fq_id`,`rws`.`fi_id`,`rws`.`date_add`,`rws`.`fq_date_add`,`rws`.`fq_author`,`rws`.`fq_author_city`,`rws`.`fq_text`,`ans`.`fa_text`,`rng`.`raiting`,`rng`.`cb_id`
			FROM `rws_questions`  as `rws`
			INNER JOIN `rws_goods` as `rng` ON (`rws`.`fq_id`= `rng`.`fq_id`)
			LEFT JOIN `rws_answers` as `ans` ON (`rws`.`fq_id`= `ans`.`fq_id`)
			WHERE `fi_id` = '1'
			AND `rng`.`cb_id` = '".(int)$cb_id."'
			AND  `rws`.`fq_is_show` = '1'
			ORDER BY `rws`.`fq_date_add` DESC
		";
			
		
		$res = Page::$DB->query($sql);
	
		if ($res->num_rows()>0) {
			while ($re = $res->fetch_assoc()) {
				$reviews[$re['fq_id']]=$re;
				$reviews[$re['fq_id']]['helpfulness']=self::helpfulness($re['fq_id']);
			}
		}
		return $reviews;
	}
	
	public static function get_all_city (){
		$city_arr=array();
		$sql = "SELECT `id`, `city`
					FROM `consumer_dpd` ";
		$res = Page::$DB->query($sql);

		if ($res->num_rows()>0) {
			while ($re = $res->fetch_assoc()) {
					$city_arr[$re['id']]=$re['city'];
			}
			return $city_arr;
		}
	}
	
}
?>