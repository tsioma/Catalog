<?php
/* кодировка файла utf8 */

require_once(USES_FOLDER . '/bin/Credit/class_Credit_model.php');
require_once(USES_FOLDER . '/bin/Reviews/class_Reviews_model.php');
require_once(USES_FOLDER . '/bin/CatalogOrders/class_CatalogOrders_model.php');
error_reporting(E_ALL ^ E_NOTICE);

class Catalog {
	private static $instance;
	public static $curent_id;
	private static $inner_url;
	private static $min_price;
	private static $max_price;
			
	private static $block_id;
	private static $block_url;
	public static  $blocks_in_list;
	private static $page;
	private static $sort_type;
	private static $order;
	private static $limit;

	public static  $gender;//Определяет текущий каталог 0 - общий 1 - женская 2 - мужская
	public static  $gender_form;//Определяет текущий каталог для значений в фильтре 0 - общий 1 - женская 2 - мужская
	private static $Mname = 'Catalog'; /* current module name */

	private static $action;
	
	private static $day;
	private static $month;
	private static $year;
	
	private static $choice;

	public static $blocks_in_list_view=array(
		20 => '20',
		40 => '40',
		60 => '60',
		80 => '80',
		'all' => 'Все'
	);
	
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
		
		
		if (file_exists(USES_FOLDER."/bin/".self::$Mname."/class_".self::$Mname."_model.php")) {
			require_once USES_FOLDER."/bin/".self::$Mname."/class_".self::$Mname."_model.php";
			$obj = call_user_func(self::$Mname."_model::singleton");
			$obj = call_user_func(self::$Mname."_model::init");
			
		}
		
		
		Page::assign('preview', Page::$preview);
		//Menu::REindexTree(Catalog,1,0);
		self::$inner_url = (isset($_REQUEST['inner_url']) ? $_REQUEST['inner_url'] : NULL);
		if (isset($_REQUEST['quickview'])){
			echo (self::getBlock((int)self::block_id_url($_REQUEST['block_url'])));
			die;
		}
		
		self::$blocks_in_list = (isset($_COOKIE['blocks_in_list']) ? $_COOKIE['blocks_in_list'] : 20);
		self::getInfo();
		Page::assign('inner_url',self::$inner_url);
		
		
		self::$curent_id = (int)Menu::getIdToFullUrl(self::$Mname,self::$inner_url, NULL, NULL, Page::$preview);
		
		
				
					self::$sort_type = (isset($_REQUEST['price_order']) ? $_REQUEST['price_order'] : NULL);
					Page::assign('price_order',self::$sort_type);
					
					switch (self::$sort_type) {
						case 'up':
							self::$order=" cb.`cb_price2` ASC "; 
							break;
						case 'down':
							self::$order=" cb.`cb_price2` DESC "; 
							break;	
						case 'pop':
							self::$order=" shop_popularity.`raiting` DESC ";
							break;
						default:
						//Сортировчка нах
							$sql='SELECT `cd_sort_type`,`cd_sort_style` 
											 FROM `catalog_index` 
											 WHERE `ci_id`="'.self::$curent_id.'"';
							$res = Page::$DB->query($sql);				 
							$num = $res->num_rows();
								if ($num > 0){
									$re = $res->fetch_assoc();
									$cd_sort_style=$re['cd_sort_style'];
									
									if ($re['cd_sort_type']=='2') {
										
										$ar=(array)explode(',',$re['cd_sort_style']);
										foreach ($ar as $a) {
												if ((int)$a==1) {$ord[]='`sales` DESC';
												}
												if ((int)$a==2) {$ord[]='`new` DESC';
												}
												if ((int)$a==3) {$ord[]='`hit` DESC';
												}
												if ((int)$a==4) {$ord[]='`cb_id` DESC';
												}
												
										}
										self::$order=(implode (',',$ord));
									}else{
										self::$order=" cb.`cb_price2` DESC "; 
									}
								
								}else{
									self::$order=" cb.`cb_price2` DESC "; 
								}
						//Сортировчке конец
						}
		
		Catalog_model::getLatelyItems(); 
		
		
		self::$block_url = (isset($_REQUEST['block_url']) ? $_REQUEST['block_url'] : NULL);		
		if (isset(self::$block_url)&&!empty(self::$block_url)){
			self::$block_id = self::block_id_url(self::$block_url);
			}else{
			self::$block_id = NULL;
		}
		
		
		if (self::$inner_url!='' && self::$curent_id==0) {
			Main::error();
		} 
		
		$section_id=(int)Menu::getIdToFullUrl(self::$Mname,self::$inner_url, NULL, NULL, Page::$preview);
		self::$gender=(int)Menu::getNodeAncestorId(self::$Mname,$section_id);
		if (self::$gender==false){
				self::$gender=(int)Menu::getNodeAncestorId(self::$Mname,self::$curent_id);
		}
		if  (self::$gender==3){
				self::$gender=1; //Шубы сейчас на верхнем уровне, но тоже женская коллекция
		}
		
		
		if (Page::isPDA() && $_REQUEST['lately_view']=='1'){
			Page::assign('lately_view', true);
		}
		
		/*self::$year  = ((isset($_REQUEST['news_year']) && !empty($_REQUEST['news_year'])) ? (int)$_REQUEST['news_year'] : NULL);
		self::$month = ((isset($_REQUEST['news_month']) && !empty($_REQUEST['news_month'])) ? (int)$_REQUEST['news_month'] : NULL);
		self::$day   = ((isset($_REQUEST['news_day']) && !empty($_REQUEST['news_day'])) ? (int)$_REQUEST['news_day'] : NULL);
		
		
		if (strlen(self::$month)==1) {$m = '0'.self::$month;} else {$m = self::$month;}  
		if (strlen(self::$day)==1) {$d = '0'.self::$day;} else {$d = self::$day;}  
		if (self::$year.'.'.$m.'.'.$d !='..') {  
			Page::assign('date_filter',self::$year.'.'.$m.'.'.$d);
		}*/

		self::$page = ((isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? (int)$_REQUEST['page'] : 1);
		

		if (isset($_REQUEST['catalog_action'])) { 
		
			self::$action=$_REQUEST['catalog_action'];
		} else {
			self::$action = 'fetchContent';
		}
		if (!empty(self::$action)) {
			if (method_exists(self::$Mname, self::$action)) {
				$obj = call_user_func(self::$Mname.'::'.self::$action);
			} else {
				Main::error();
			}
		}
	}
	
	
	public static function model ()	{
		echo '
			<!DOCTYPE html>
				<html>
					<head>
						<meta charset="UTF-8" />
							<title>Барс</title>
					</head>
						<body>
							<iframe src="/public/themes/default/templates/player.html?path=http://www.shubbing-shop.ru/public/content/catalog/3d/'.$_REQUEST["model"].'"  width="695" height="573" webkitAllowFullScreen mozAllowFullScreen allowFullScreen></iframe>
						</body>
				</html>';
	}
	
	public static function block_id_url ($block_url){
		$sql = "SELECT `cb_id`
		FROM `catalog_blocks` AS `cb`
			
		WHERE
			`cb`.`cb_url` = '" . Page::$DB->escape($block_url) . "'

		LIMIT 1;";
		
		$res = Page::$DB->query($sql);
		if ($res->num_rows > 0){
			
			return $res->get_one(0, 0);
		}
		return false;
	}
	
	public static function getGoodsPriceByPrice($block_id, $price){
		$sql = "SELECT
			`sv`.`sv_val4` AS `price`
		FROM `catalog_blocks` AS `cb`
			INNER  JOIN `sprav_values` AS `sv` ON (`cb`.`cb_ext_id` = `sv`.`sv_val1`)
			LEFT JOIN `sprav_values` AS `sv1` ON (`sv`.`sv_val2` = `sv1`.`sv_val1`)
		WHERE
			`cb`.`cb_id` = " . (int)$block_id . "
			AND `sv`.`sv_val4` = '".Page::$DB->escape($price)."'
			AND `cb`.`block_show` = '1'
			AND `sv`.`sp_name` = 'sizes'
			AND `sv1`.`sp_name` = 'stores'
		LIMIT 1;";
		$res = Page::$DB->query($sql);
		if ($res->num_rows > 0){
			return $res->get_one(0, 0);
		}
		return false;
	}

	public static function credit(){
		if (!isset($_GET['catalog_goods_price']) || !self::$block_id || false === ($price = self::getGoodsPriceByPrice(self::$block_id, hexdec($_GET['catalog_goods_price'])))){
			Main::error();
		}
		
		
		/* --- --- --- */
		if (!array_key_exists(Page::$AllSettings['Main']['default_credit_term'][1]['s_val'], Credit_model::$cprog_periods)){
			Credit_model::$cprog_periods[Page::$AllSettings['Main']['default_credit_term'][1]['s_val']] = array();
			ksort(Credit_model::$cprog_periods);
		}
		if (!array_key_exists(Page::$AllSettings['Main']['default_credit_of_down_payment'][1]['s_val'], Credit_model::$cprog_initial_fees)){
			Credit_model::$cprog_initial_fees[Page::$AllSettings['Main']['default_credit_of_down_payment'][1]['s_val']] = array();
			ksort(Credit_model::$cprog_initial_fees);
		}

		Credit_model::$cprog_periods[Page::$AllSettings['Main']['default_credit_term'][1]['s_val']]['default'] = true;
		Credit_model::$cprog_initial_fees[Page::$AllSettings['Main']['default_credit_of_down_payment'][1]['s_val']]['default'] = true;
		/* /--- --- --- */

		Menu::setTemplates('kroshka');
		Page::assign('kroshka', Menu::fetchTopDown(self::$Mname,self::$curent_id, 1, Page::$preview, self::$block_id));

		$sql = "
		SELECT cb.`ci_id`,cb.`cb_id`, cb.`cb_price1`, cb.`cb_price2`, cb.`cb_dlina`, cb.`block_order`, cb.`block_show`, cb.`sp_model`, cb.`cb_sex`, cb.`sp_hood`, cb.`cb_status`, cbc.`cbc_header`, cbc.`cbc_anonce`, cbc.`cbc_text`,
		sv.`sv_val2` as color, sv1.`sv_val2` as raw , sv2.`sv_val2` as decor , sv3.`sv_val2` as country , sv4.`sv_val2` as decor , sv5.`sv_val2` as composition, sv6.`sv_val2` 
		FROM `catalog_blocks` AS cb
			INNER JOIN `catalog_blocks_cont` AS `cbc` ON (`cbc`.`cb_id` = `cb`.`cb_id`)
			LEFT JOIN `sprav_values` AS `sv` ON (`cb`.`sp_color` = `sv`.`sv_val1`)
			LEFT JOIN `sprav_values` AS `sv1` ON (`cb`.`sp_raw` = `sv1`.`sv_val1`)
			LEFT JOIN `sprav_values` AS `sv2` ON (`cb`.`sp_type` = `sv2`.`sv_val1`)
			LEFT JOIN `sprav_values` AS `sv3` ON (`cb`.`sp_country` = `sv3`.`sv_val1`)
			LEFT JOIN `sprav_values` AS `sv4` ON (`cb`.`sp_decor` = `sv4`.`sv_val1`)
			LEFT JOIN `sprav_values` AS `sv5` ON (`cb`.`sp_sostav` = `sv5`.`sv_val1`)
			LEFT JOIN `sprav_values` AS `sv6` ON (`cb`.`sp_sprav_sostav` = `sv6`.`sv_val1`)
		WHERE cbc.`lang_id` = '".Page::$lang."' 
					".((!Page::$preview) ? "AND cb.`block_show` = '1'" :"")."  
					AND cb.`cb_id` = '".self::$block_id."' 
					AND sv.`sp_name`='color'
					AND sv1.`sp_name`='raw'
					AND sv2.`sp_name`='decor'
					AND sv3.`sp_name`='countries'
					AND sv4.`sp_name`='decor'
					AND sv5.`sp_name`='composition'
					
					
				
		LIMIT 1;";
		$res = Page::$DB->query($sql);
		$num = $res->num_rows();
		if ($num > 0) {
			$arr = $res->fetch_assoc();
			
			$arr['cbc_text'] = Parser::parseTable(stripslashes($arr['cbc_text']), (int)$arr['cbc_ptbl_id']);
		
			$sql = "
				SELECT * FROM `catalog_photos` 
				WHERE `cb_id` = '".self::$block_id."' 
							AND `photo_show` = '1' 
				ORDER BY `photo_order` ASC
			";
			$qw=Page::$DB->query($sql);
			
			if ($qw->num_rows()>0) {
				while ($re = $qw->fetch_assoc()) {
					$arr['photos'][] = $re; 
				}
			} 
				
			
			$sql1 = "
		
			SELECT sv1.`sv_id`, sv1.`sv_val1` as s_id, sv1.`sv_val2` as stores_name, sv.`sv_val3` as sizes, sv.`sv_val4` as price2, sv.`sv_val5` as price1	
			FROM `catalog_blocks` AS cb
				INNER JOIN `catalog_blocks_cont` AS `cbc` ON (`cbc`.`cb_id` = `cb`.`cb_id`)
				LEFT JOIN `sprav_values` AS `sv` ON (`cb`.`cb_ext_id` = `sv`.`sv_val1`)
				LEFT JOIN `sprav_values` AS `sv1` ON (`sv`.`sv_val2` = `sv1`.`sv_val1`)
							WHERE cbc.`lang_id` = '".Page::$lang."' 
							".((!Page::$preview) ? "AND cb.`block_show` = '1'" :"")."  
							AND cb.`cb_id` = '".self::$block_id."' 
							AND sv.`sp_name`='sizes'
							AND sv1.`sp_name`='stores'
							ORDER BY `stores_name`
						;";
			$qw1 = Page::$DB->query($sql1);
			$num = $res->num_rows();
			
			if ($num > 0) {
				while ($re1 = $qw1->fetch_assoc()) {
					
					$arr['size_stores'][$re1['price1']][$re1['price2']][$re1['sizes']][] = $re1; 
					
				}
			
			}
			if(!empty($arr['size_stores'])){
					ksort($arr['size_stores']);
						foreach ($arr['size_stores'] as $arr_small){
							ksort($arr_small);
						}
					}
			
			
			
			$arr['url'] = Menu::getNodeFullUrl(self::$Mname,$arr['ci_id'], Page::$lang);

			Page::assign('meta_tags', $meta_tags);
			Page::assign('block', $arr);
			Page::assign('credit_goods_price', $price);
		}

		Page::assign('cprog_periods', Credit_model::$cprog_periods);
		Page::assign('cprog_initial_fees', Credit_model::$cprog_initial_fees);
		if (Page::isPDA()){
			$cprog_initial_fee=$price*(int)Page::$AllSettings['Main']['default_credit_of_down_payment'][1]['s_val']/100;
			$cprog_initial_fee_percent=Page::$AllSettings['Main']['default_credit_of_down_payment'][1]['s_val'];
			$cprog_period=Page::$AllSettings['Main']['default_credit_term'][1]['s_val'];
			$result = Credit_model::singleton()->getAvailablePrograms($price, $cprog_initial_fee, $cprog_initial_fee_percent, $cprog_period);
			$result[0]['fee_percent']=$cprog_initial_fee_percent;
			Page::assign('credit_best_price', $result[0]);
			Page::assign('full_url', $_SERVER['REQUEST_URI']);
			}
		Page::$tpl->caching = false;
		echo Page::fetch(CTF . '/addons/catalog_goods_credit.tpl');
	}

	public static function getContent(){

		if (empty(self::$block_id)){
			
			// Вставка меню и т. п.
			/* top menu */
			Menu::setTemplates('top_menu');
			Page::assign('top_menu', Menu::fetchOneLevel('Main', 2, NULL));
			
			/* kroshka */
			Menu::setTemplates('kroshka');
			Page::assign('kroshka', Menu::fetchTopDown(self::$Mname,self::$curent_id, 1, Page::$preview, self::$block_id,self::$curent_id));
			/* /kroshka */

			/*left menu */
					Menu::setTemplates(array('left_tree'));
			
			if (Menu::fetchOneLevel(self::$Mname, self::$curent_id, NULL, Page::$preview) != NULL){
					
					Page::assign('left_menu', Menu::fetchOneLevel(self::$Mname, self::$curent_id, self::$curent_id,  Page::$preview));
				}else{
					Page::assign('left_menu', Menu::fetchOneLevel(self::$Mname, (Menu::getAncestorId(self::$Mname, 1, self::$curent_id)), self::$curent_id,  Page::$preview));
				}
			
			/* /left menu */
			
			if (Page::$AllSettings['Main']['tags_active'][1]['s_val']  == 1) {
				Page::assign('tagnav', Tags::fetchTagNavigation(self::$Mname,self::$curent_id));
			} 
			
			/* inserts */
			Page::assign('feedback_email', Page::$AllSettings['Main']['admin_mail'][1]['s_val']);
			Page::assign('default_title', Page::$AllSettings['Main']['title_def'][1]['s_val']);
			Page::assign('default_keywords', Page::$AllSettings['Main']['keywords_def'][1]['s_val']);
			Page::assign('default_description', Page::$AllSettings['Main']['description_def'][1]['s_val']);
			/* /inserts */
			
			self::filter_form(self::$curent_id);//значения фильтра
			$cont = self::getList();
		} else {
			
			$cont = self::getBlock();
			
		}

		return $cont;
	}

	public static function filter_val(){//аякс функция обновления значений фильтра
		if ((isset($_POST) && $_POST['ajax']==true)){
			$filter=array();
			$values=array();
					if ($_POST['fl_type']=='type'){
						$id = implode (',',$_POST['id']);
						$filter['price_min']=self::getPrice($id,'min',true);
						$filter['price_max']=self::getPrice($id,'max',true);
					}
					if ($_POST['fl_type']=='sostav'&&!empty($_POST['id'])){
						foreach ($_POST['id']as $val){
							$values[]=str_pad($val,9,'0',STR_PAD_LEFT);
						}
						$id = implode (',',$values);
						
						$filter['sostav']=self::getSostav($id,true);
						
						
							if ($filter['sostav']!=false){
								foreach ($filter['sostav'] as $key=>$sostav){
											$cont=$cont.'<div class="asideFilter-string"><span class="customCheckbox"><label for="sostav'.$key.'">        
											<input class="customCheckbox-native type_sostav" ' . ((isset($_POST["checked"]) && (in_array($key, (array)$_POST["checked"]))) ? ' checked="checked" ' : '') . ' type="checkbox" id="sostav'.$key.'" name="sostav['.$key.']" value="'.$key.'" >
											<span class="customCheckbox-custom"></span><span class="customCheckbox-text">'.$sostav.'</span></label></span></div>';
									   }
							}else{
								$cont='empty';
							}
						
					echo $cont;
				}
			}
		return false;
	}
	
	public static function getList() {
		/*filters*/
		if (Page::isPDA() && $_REQUEST['search']=='on'){
			Page::assign('catalog_search', true);
		}
		if (Page::isPDA() && isset($_REQUEST['filter'])){
			Page::assign('filter', true);
		}
		
		if ($_GET['filter']=='on'){
			$pst['gender']=trim(Page::$DB->escape($_GET['gender']));
			$pst['min_price']=trim(Page::$DB->escape($_GET['min_price']));
			$pst['max_price']=trim(Page::$DB->escape($_GET['max_price']));
			$pst['types']=$_GET['types'];
			$pst['sostav']=$_GET['sostav'];
			$pst['size']=$_GET['size'];
			$pst['length']=$_GET['length'];
			$pst['stores']=$_GET['stores'];
			
			$pst['status']=$_GET['status'];
			$pst['sales']=$_GET['sales'];
			$pst['hood']=trim(Page::$DB->escape($_GET['hood']));
			$pst['filter']=trim(Page::$DB->escape($_GET['filter']));
			$pst['model'] = (isset($_GET['model']) ? UTF8::strtolower(UTF8::trim($_GET['model'])) : '');
			

			if (isset ($pst)){
				$qstring=http_build_query($pst);
				
				if (isset($pst['types'])){
					if (is_array($pst['types'])){
					$section=$pst['types'];
						$sub_sect = array();
						foreach ($section as $val){
							$sub_sect = array_merge($sub_sect,Menu::getNodeAllChildsIds(self::$Mname, $val));
						}
						$section = implode (",",$sub_sect);
						
					}else{
						$section=self::$curent_id;
						$sub_sect = Menu::getNodeAllChildsIds(self::$Mname, $section);
							($sub_sect!=NULL ? $section=$section.','.implode(',', $sub_sect) : $section);
							
					}
				}else{
					$section=self::$curent_id;
					$sub_sect = Menu::getNodeAllChildsIds(self::$Mname, $section);
					($sub_sect!=NULL ? $section=$section.','.implode(',', $sub_sect) : $section);
				}
				
				if (isset($pst['sostav'])){
					$sostav = implode ("','",$pst['sostav']);
					$arrSqlWhere[]='cb.`sp_sprav_sostav` IN (\''.$sostav.'\') ';
						
				}
				
				if (isset($pst['min_price']) && isset($pst['max_price'])){
					if (($pst['max_price']!=0) && ($pst['min_price']!=0) ){
							if ($pst['min_price']<($pst['max_price'])){
									$arrSqlWhere[]='cb.`cb_price2` <= '.$pst['max_price']. ' AND cb.`cb_price2` >='.$pst['min_price'];
								}else{
									$arrSqlWhere[]='cb.`cb_price2` <= '.$pst['min_price']. ' AND cb.`cb_price2` >='.$pst['max_price'];
								}
					}
				}
				
				if (isset($pst['hood']) && '' != $pst['hood']){
					if ($pst['hood']!=1){
						if ($pst['hood']==2){
							$arrSqlWhere[]="cb.`sp_hood` = '1'";
						}else{
							$arrSqlWhere[]="cb.`sp_hood` = '0'";
						}
					}
				}
				
				if (isset($pst['sales']) && !empty($pst['sales'])){
						foreach ($pst['sales'] as $sales){
							$arrSqlWhereActions[]=" `cb`.`cb_price1` > 0";
						}
						
					}
				
				if (isset($pst['length']) && !empty($pst['length'])){
					$length=implode("','",array_map(array(Page::$DB, 'escape'), $pst['length']));
						$arrSqlWhere[]='cb.`cb_dlina` IN (\''.$length.'\')';
				}
				
				if (isset($pst['status']) && !empty($pst['status'])){
					$status=implode("','",array_map(array(Page::$DB, 'escape'), $pst['status']));
						$arrSqlWhere[]='cb.`cb_status` IN (\''.$status.'\')';
					
				}
				
				
				$ext_q=array();
				if(!empty($pst['stores'])&&!empty($pst['size'])){
					$stores=implode("','",$pst['stores']);
					$sizes=implode("','",$pst['size']);
					$ext_q[0] ="LEFT JOIN  `sprav_values` AS  `sv` ON ( cb.`cb_ext_id` =  `sv`.`sv_val1` ) ";
					$ext_q[1] ="AND `sv`.sp_name =  'sizes' 
								AND  `sv`.`sv_val2` IN ('".$stores."')
								AND `sv`.sp_name =  'sizes' 
								AND  `sv`.`sv_val3` IN ('".$sizes."')";
				
					}elseif(!empty($pst['stores'])){
						$stores=implode("','",$pst['stores']);
						$ext_q[0] = " LEFT JOIN  `sprav_values` AS  `sv` ON ( cb.`cb_ext_id` =  `sv`.`sv_val1` ) ";
						$ext_q[1] = " AND `sv`.sp_name =  'sizes' 
									     AND  `sv`.`sv_val2` IN ('".$stores."') ";
						
						
					}elseif(!empty($pst['size'])){
						$sizes=implode("','",$pst['size']);
						$ext_q[0] = " LEFT JOIN  `sprav_values` AS  `sv` ON ( cb.`cb_ext_id` =  `sv`.`sv_val1` ) ";
						$ext_q[1] = " AND `sv`.sp_name =  'sizes' 
									     AND  `sv`.`sv_val3` IN ('".$sizes."') ";
					
					}else{
						$ext_q = '';
					}
				
				if ($pst['model']){
				
					$arrSqlWhere[] = "LOWER(`cb`.`sp_model`) LIKE '%" . Page::$DB->escape(UTF8::str_replace('%', '\\%', $pst['model'])) . "%'";
					
					Page::assign('model', $pst['model']);
					
				}
			}
		}else{
				$section=self::$curent_id;
				$pst['types'] = array($section=>$section);
				$sub_sect = Menu::getNodeAllChildsIds(self::$Mname, $section);
				($sub_sect!=NULL ? $section=$section.','.implode(',', $sub_sect) : $section);
				
				
			}
		
		
		/*filters end*/
		

		$begin_limit = (int)(self::$blocks_in_list * (self::$page-1));

		$bloks_list = array();
		$bloks_list_pages = array();
		
		if (self::$blocks_in_list!='all'){
					
					self::$limit=" LIMIT $begin_limit, ".self::$blocks_in_list;
				}else{
					self::$limit="";
				}
		if (Page::isPDA()){
				self::$limit=" LIMIT 0, ".self::$blocks_in_list*self::$page;
					
			}
		$meta_tags = Menu::getMeta(self::$Mname, self::$curent_id, self::$block_id, Page::$lang);
		Page::assign('meta_tags', $meta_tags);
		self::getCompareItems();
		
		
		$actions=array();
		$sql = "
			SELECT SQL_CALC_FOUND_ROWS cb.`ci_id`, cb.`cb_id`, cb.`cb_url`, cbc.`cbc_header`, cbc.`cbc_anonce`, cb.`block_show`, cb.`sp_model`, cb.`cb_price1`, cb.`cb_price2`, cb.`cb_status`, shop_popularity.`raiting`,  cb.`cb_undorder`, 
			IF(`cb_status`='Новинка', '1','0') as `new`,
			IF(`cb_status`='Хит', '1','0') as `hit`,
			IF(`cb_price1`<> 0, '1','0') as `sales`,
			(SELECT cp.`photo_preview` FROM `catalog_photos` AS cp WHERE cp.`cb_id` = cb.`cb_id` AND cp.`photo_show` = '1' ORDER BY cp.`photo_order` ASC LIMIT 1) AS img
			FROM `catalog_blocks` AS cb 
			INNER JOIN `catalog_blocks_cont` AS `cbc` ON (`cbc`.`cb_id` = `cb`.`cb_id`)
			LEFT JOIN `shop_popularity` ON (`shop_popularity`.`sp_cb_id` =  `cb`.`cb_id`) "
			.(!(empty($ext_q[0])&&$ext_q[0]!=NULL) ? $ext_q[0]:'').
			"WHERE cbc.`cb_id` = cb.`cb_id` 
						AND cbc.`lang_id` = '".Page::$lang."' 
						".((!Page::$preview) ? "AND cb.`block_show` = '1'" : "")." 
						".(!empty($section) ? "AND cb.`ci_id` in (".$section.")" : '')." 
						".(count($arrSqlWhere) > 0 ? "AND " . implode(' AND ', $arrSqlWhere) : "")."
						
						".(!(empty($ext_q[1])&&$ext_q[1]!=NULL) ? $ext_q[1]:'')."
			GROUP BY cb.`cb_id` 
			ORDER BY ".self::$order."
			".self::$limit.";
		";
	
		$res = Page::$DB->query($sql);
		
		$sql_c = "SELECT FOUND_ROWS();";
		$res_c = Page::$DB->query($sql_c);
		$count_blocks = (int)$res_c->get_one(0, 0);

		$num = $res->num_rows();
		$pages_count = 0;
	
		if ($num > 0){
			
			if (isset($pst['model']) && $pst['model'] && 1 == $num){
				$arr = $res->fetch_assoc();
				$arr['ci_url'] = Menu::getNodeFullUrl(self::$Mname,$arr['ci_id'], Page::$lang);
				Gen::redirect($arr['ci_url'] . $arr['cb_url'] . '.html');
			}

			while ($arr = $res->fetch_assoc()){
				$arr['ci_url'] = Menu::getNodeFullUrl(self::$Mname,$arr['ci_id'], Page::$lang);
				if ($arr['cb_price1']==0) {
						$arr['cb_price1']=$arr['cb_price2'];						
				}
	
				
				
				if (Page::isPDA()){
				$but_wish=Catalog_model::getWishHash($arr['cb_id']);
					if ($but_wish!=false){
						$arr['but_wish']=$but_wish;
					}
				
				}
				$blocks_list[] = $arr;
			
			}
			if (self::$blocks_in_list!='all'){
				$pages_count = ceil($count_blocks / self::$blocks_in_list);
				if ($pages_count > 1){
								if ((self::$page-2) > 3) {
									$blocks_list_pages[1] = 1;
									$blocks_list_pages[2] = 2;
									$blocks_list_pages[3] = 3;
									$blocks_list_pages[4] = '...';
									$start = self::$page-2;
								} else {
									$start = 1;
								}
								if ((self::$page+2)<$pages_count-2) {
									$end = self::$page+2;
									while ($start <=$end){
										$blocks_list_pages[$start] = $start;
										$start ++;
									}
									$blocks_list_pages[$start] = '...';
									$blocks_list_pages[$pages_count-2] = $pages_count-2;
									$blocks_list_pages[$pages_count-1] = $pages_count-1;
									$blocks_list_pages[$pages_count] = $pages_count;
								} else {
									$end = $pages_count;
									while ($start <=$end){
										$blocks_list_pages[$start] = $start;
										$start ++;
									}
								}
							}
			}else{
				self::$blocks_in_list='Все';
			}
		}
		
		
		$sql = "SELECT `cic_anonce`
				FROM  `catalog_index_cont` 	
				WHERE  `ci_id` = '".self::$curent_id."' LIMIT 1";
			
		$res = Page::$DB->query($sql);	
		$num = $res->num_rows();
		
		if ($num > 0){
			while ($arr = $res->fetch_assoc()){
				$cic_anonce= htmlspecialchars_decode($arr['cic_anonce']);
			}
			Page::assign('cic_anonce',$cic_anonce);
		}
		/*Определяем пол коллекции*/
		if (self::$curent_id !=1 && self::$curent_id !=2 && self::$curent_id !=3){
			self::$gender=(int)Menu::getNodeAncestorId(self::$Mname,self::$curent_id);
		}else{
			self::$gender=self::$curent_id;
		}
		
		if  (self::$gender==3){
				self::$gender=1; //Шубы сейчас на верхнем уровне, но тоже женская коллекция
		}
		/*Определяем пол коллекции*/
		
		Page::assign('blocks_in_list_view', self::$blocks_in_list_view);
		Page::assign('blocks_in_list', self::$blocks_in_list);
		Page::assign('blocks_list_pages_count', $pages_count);
		Page::assign('blocks_list_pages', $blocks_list_pages);
		Page::assign('blocks_list', $blocks_list);
		Page::assign('this_blocks_page', self::$page);
		Page::assign('gender', self::$gender);


		Page::assign('list_header', self::fetchHeader());
		Page::assign('qstring', $qstring);
		
		Page::assign('pst', $pst);
		
		Page::$tpl->caching = false;
		
		Page::assign('catalog_products_listpage', true);

		return Page::fetch(CTF.'/addons/catalog_list.tpl');
	}
    
	public static function getLength(){
		$sql = "SELECT DISTINCT (`cb_dlina`)
				FROM  `catalog_blocks` 
				WHERE `block_show` = '1'
				";
				
		$res = Page::$DB->query($sql);	
		$num = $res->num_rows();
		if ($num > 0){
			while ($arr = $res->fetch_assoc()){
					$length[]=$arr['cb_dlina'];
			}
			return $length;
		}
		return false;
	}
	
	public static function getSizes(){
		$sql = "SELECT DISTINCT (`sv_val3`)
				FROM  `sprav_values` 	
				WHERE  `sp_name` =  'sizes' AND `sv_val3` NOT LIKE '%*%'  ORDER BY `sv_val3`";
				
		$res = Page::$DB->query($sql);	
		$num = $res->num_rows();
		if ($num > 0){
			while ($arr = $res->fetch_assoc()){
					$sizes[]=$arr['sv_val3'];
			}
			return $sizes;
		}
		return false;
	}
	
	public static function getStores(){
		$sql = "SELECT `sv_val1`, `sv_val2`
				FROM  `sprav_values` 	
				WHERE  `sp_name` = 'stores' 
				AND `sv_val1`<> '000000004'
				ORDER BY `sv_val2`;
				 ";
				
		$res = Page::$DB->query($sql);	
		$num = $res->num_rows();
		if ($num > 0){
			while ($arr = $res->fetch_assoc()){
					$stores[]= array(
								'id'  =>$arr['sv_val1'],
								'name'=>$arr['sv_val2']);
			}
			return $stores;
		}
		return false;
	}
	
	public static function getStatus(){
		$sql = "SELECT DISTINCT(`cb_status`)
				FROM  `catalog_blocks`
				WHERE `cb_status`<> ''
				ORDER BY `cb_status`";
				
		$res = Page::$DB->query($sql);	
		$num = $res->num_rows();
		$i=0;
		if ($num > 0){
			while ($arr = $res->fetch_assoc()){
					$status[$i]=$arr['cb_status'];
			$i++;
			}
			return $status;
		}
		return false;
	}
	
	
	public static function getPrice($cat=NULL,$minmax=NULL,$ajax=false){
		
		if ($ajax==false){
			if (is_array($cat)){
				$sub_sect = array();
				foreach ($cat as $val){
					$sub_sect = array_merge($sub_sect,Menu::getNodeAllChildsIds(self::$Mname, $val));
				}
				$cat = implode (",",$sub_sect);
				
			}else{
			
				$sub_sect = Menu::getNodeAllChildsIds(self::$Mname, $cat);
					($sub_sect!=NULL ? $cat=$cat.','.implode(',', $sub_sect) : $cat);
					
			}
		}
		$sql = "
			SELECT cb.`ci_id`, cb.`cb_id`, cb.`cb_price1`, cb.`cb_price2`  
			FROM `catalog_blocks` AS cb, `catalog_blocks_cont` AS cbc 
			WHERE cbc.`cb_id` = cb.`cb_id` 
						AND cbc.`lang_id` = '".Page::$lang."' 
						".((!Page::$preview) ? "AND cb.`block_show` = '1'" : "")." 
						".((!empty($cat) && (int)$cat > 0) ? "AND cb.`ci_id` in (".$cat.")" : '')." 
			ORDER BY cb.`block_order` DESC;
			";
		
		$res = Page::$DB->query($sql);	
		$num = $res->num_rows();
		$all_price=array();
		if ($num > 0){
			while ($arr = $res->fetch_assoc()){
				if ($arr['cb_price1']==0) {
						$arr['cb_price1']=$arr['cb_price2'];
					}
				$all_price[]=$arr['cb_price2'];
			}
		}
		if(!empty($all_price) && $all_price!=NULL){
				$price['min']=min($all_price);
				$price['max']=max($all_price);
				
				if ($minmax=='min'){
						return $price['min'];
					}elseif ($minmax=='max'){
						return $price['max'];
					}else{
						return $price;
					}
		}
		
	}
	public static function getSostav($cat=NULL,$ajax=false){
		if (Page::isPDA()){
				$sql="SELECT `sv_val1`,`sv_val2`,`sv_val3`
					  FROM `sprav_values`
					  WHERE `sp_name` = 'sprav_sostav' 
					  AND `sv_val1` in (".$cat.");";
				$res = Page::$DB->query($sql);	
				$num = $res->num_rows();
				$sprav_sostav=array();
				if ($num > 0){
					while ($arr = $res->fetch_assoc()){
							$sprav_sostav[$arr['sv_val2']]=$arr['sv_val3'];
					}
				}
			
			
			return $sprav_sostav;
		}
		
		if ($ajax==true&&$cat!=NULL){
				$sql="SELECT `sv_val1`,`sv_val2`,`sv_val3`
					  FROM `sprav_values`
					  WHERE `sp_name` = 'sprav_sostav' 
					  AND `sv_val1` in (".$cat.");";
						
				$res = Page::$DB->query($sql);	
				$num = $res->num_rows();
				$sprav_sostav=array();
				if ($num > 0){
					while ($arr = $res->fetch_assoc()){
							$sprav_sostav[$arr['sv_val2']]=$arr['sv_val3'];
					}
				}
		
		return $sprav_sostav;
		}
		return false;
	}
	
	
	static function fetchHeader(){
		if (empty(self::$curent_id)&&empty(self::$year)) {
			$header = CATALOG_HEADER;
		}
		if (isset(self::$curent_id) && !empty(self::$curent_id)){
			if (isset($_GET['types'])&& !empty($_GET['types'])){
				$count_types=count($_GET['types']);
				if ($count_types == 1) {
					self::$curent_id=implode(',',$_GET['types']);
				}
			}
			$header = Menu::getNodePageName(self::$Mname,self::$curent_id,Page::$lang).' ';
		}
		return $header;
	}
	
	public static function get_city (){
		if (isset($_POST['area'])&&$_POST['ajax']==true){
			$area=Page::$DB->escape($_POST['area']);
			$city_arr = array();
			$sql = "SELECT `id`, `city`,`price`,`period`
					FROM `consumer_dpd` 
					WHERE `area_id` =".$area;
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
				echo json_encode ($city_arr);
			}
		}
	}
	
	
	
	public static function getBlock($quickView=NULL) {
		
		if (isset($quickView)&& $quickView!=NULL){
			self::$block_id = $quickView;
		}
		Page::assign('promo_icon', Promo::getPromo(5, 5,false));
		if (isset(Page::$Geo_data['city'])&&!empty(Page::$Geo_data['city'])){
				$city_list=Gen::rus_lat(Catalog_model::get_all_city());
				$possibleCity=Gen::fuzzySearch($city_list, Gen::rus_lat(Page::$Geo_data['city']));
				$cityId=($possibleCity != false) ? $possibleCity : 1;
			}else{
				$cityId=1;
		}
		$areaId=Catalog_model::get_area_by_city($cityId);
		$hidden_captcha = md5(microtime().uniqid());
		$_SESSION['hidden_captcha'] = $hidden_captcha;
		Page::assign('hidden_captcha', $hidden_captcha);
		Page::$tpl->caching = false;
		$section_id=(int)Menu::getIdToFullUrl(self::$Mname,self::$inner_url, NULL, NULL, Page::$preview);
		self::$gender=(int)Menu::getNodeAncestorId(self::$Mname,$section_id);
		if (self::$gender==false){
				self::$gender=(int)Menu::getNodeAncestorId(self::$Mname,self::$curent_id);
		}
		if  (self::$gender==3){
				self::$gender=1; //Шубы сейчас на верхнем уровне, но тоже женская коллекция
		}
		
		/*if (isset ($_COOKIE['back_url']) && !empty($_COOKIE['back_url'])){
			$params=array();
			$back_url_tmp=explode (',',$_COOKIE['back_url']);
				;
				foreach ($back_url_tmp as $item){
					$val=(explode (':',$item));
					$params[$val[0]]=$val[1];
				}		
				if (isset($params['catalog_page'])&&$params['catalog_page']<>'undefined'){
					Page::assign('this_blocks_page',$params['catalog_page']);
				}
				if (isset($params['catalog_line'])&&$params['catalog_line']<>'undefined'){
					Page::assign('line_counter',$params['catalog_line']);
				}
				if (isset($params['self_url'])&&$params['self_url']<>'undefined'&&!empty($params['self_url'])){
					Page::assign('back_url',$params['self_url']);
					
				}else{
					Page::assign('gender_url',self::$gender);
					Page::assign('back_url','/catalog/'.$section_id.'/');
				}
				
				if (isset($params['gender'])&&$params['gender']<>'undefined'){
					Page::assign('gender_url',$params['gender']);
				}else{
					
					Page::assign('gender_url',self::$gender);
					Page::assign('back_url','/catalog/'.$section_id.'/');
				}
		}*/
		
		Page::assign('gender',self::$gender);
		Menu::setTemplates('kroshka');
		Page::assign('kroshka', Menu::fetchTopDown(self::$Mname,self::$curent_id, 1, Page::$preview, self::$block_id));
	
		Page::assign('block_id', self::$block_id);
		Page::assign('block_url', $_REQUEST['block_url']);		
		self::getCompareItems();
		
		$but_wish=Catalog_model::getWishHash(self::$block_id);
		if ($but_wish!=false){
			Page::assign('but_wish', $but_wish);
		}

		if (self::$block_id>0) {
			Catalog_model::setLatelyCookie(); 
			Catalog_model::toLately(self::$block_id);
			Catalog_model::view(self::$block_id);
		}

		if (isset($_SERVER['HTTP_REFERER']) && '' != $_SERVER['HTTP_REFERER'] && preg_match('#^(https?://(?:shubbing\\.office\\.sumteh\\.ru|(?:www\\.)?shubbing\.ru))(/akcii/.*)$#ui', $_SERVER['HTTP_REFERER'], $matches)){
			Page::assign('catalog_block_back_url', $matches[2]);
		}

		
		$sql = "
		SELECT cb.`ci_id`,cb.`cb_id`, cb.`cb_price1`, cb.`cb_price2`, cb.`cb_dlina`, cb.`block_order`, cb.`block_show`, cb.`sp_model`, cb.`cb_sex`, cb.`sp_hood`, cb.`cb_status`, cbc.`cbc_header`, cbc.`cbc_anonce`, cbc.`cbc_text`,
		sv.`sv_val2` as color, sv1.`sv_val2` as raw , sv3.`sv_val2` as country , sv4.`sv_val2` as decor , sv6.`sv_val3` as composition, cb.`cb_undorder`,cb.`cb_3dmodel` as 3d
		FROM `catalog_blocks` AS `cb`
			INNER JOIN `catalog_blocks_cont` AS `cbc` ON (`cbc`.`cb_id` = `cb`.`cb_id`)
			LEFT JOIN `sprav_values` AS `sv` ON (`cb`.`sp_color` = `sv`.`sv_val1`)
			LEFT JOIN `sprav_values` AS `sv1` ON (`cb`.`sp_raw` = `sv1`.`sv_val1`)
			LEFT JOIN `sprav_values` AS `sv3` ON (`cb`.`sp_country` = `sv3`.`sv_val1`)
			LEFT JOIN `sprav_values` AS `sv4` ON (`cb`.`sp_decor` = `sv4`.`sv_val1`)
			LEFT JOIN `sprav_values` AS `sv6` ON (`cb`.`sp_sprav_sostav` = `sv6`.`sv_val2`)
						WHERE cb.`cb_id` = '".self::$block_id."' 
						AND cbc.`lang_id` = '".Page::$lang."' 
						AND cb.`cb_id` IS NOT NULL 
						AND sv.`sp_name`='color'
						AND sv1.`sp_name`='raw'
						AND sv3.`sp_name`='countries'
						AND sv4.`sp_name`='decor'
						AND sv6.`sp_name`='sprav_sostav'
			LIMIT 1;";

			$res = Page::$DB->query($sql);
			$num = $res->num_rows();
			if ($num > 0) {
				$arr = $res->fetch_assoc();
				
				$arr['cbc_text'] = Parser::parseTable(stripslashes($arr['cbc_text']), (int)$arr['cbc_ptbl_id']);
				$arr['others_analog'] = self::get_other_as_current($arr['cb_id'],$arr['sp_model']);
				$sql = "
					SELECT * FROM `catalog_photos` 
					WHERE `cb_id` = '".self::$block_id."' 
					AND `photo_show` = '1' 
					ORDER BY `photo_order` ASC
				";
				$qw=Page::$DB->query($sql);
				
				if ($qw->num_rows()>0) {
					while ($re = $qw->fetch_assoc()) {
						$arr['photos'][] = $re; 
					}
				} 
				
				
				
				
				$sql1 = "
				SELECT sv1.`sv_val1` as s_id, sv1.`sv_val2` as stores_name, sv.`sv_val3` as sizes, sv.`sv_val4` as price2, sv.`sv_val5` as price1	
				FROM `catalog_blocks` AS cb
					INNER JOIN `catalog_blocks_cont` AS `cbc` ON (`cbc`.`cb_id` = `cb`.`cb_id`)
					LEFT JOIN `sprav_values` AS `sv` ON (`cb`.`cb_ext_id` = `sv`.`sv_val1`)
					LEFT JOIN `sprav_values` AS `sv1` ON (`sv`.`sv_val2` = `sv1`.`sv_val1`)
								WHERE cbc.`lang_id` = '".Page::$lang."' 
								".((!Page::$preview) ? "AND cb.`block_show` = '1'" :"")."  
								AND cb.`cb_id` = '".self::$block_id."' 
								AND sv.`sp_name`='sizes'
								AND sv1.`sp_name`='stores'
								ORDER BY `stores_name`
							;";
				$res1 = Page::$DB->query($sql1);
				$num1 = $res1->num_rows();
				$arr['credit_min_monthly_payments'] = array();
				if ($num1 > 0) {
					while ($re1 = $res1->fetch_assoc()) {
						$arr['size_stores'][$re1['price1']][$re1['price2']][$re1['sizes']][] = $re1;

						if (!isset($arr['credit_min_monthly_payments'][$re1['price2']])){
							$arr['credit_min_monthly_payments'][$re1['price2']] = Credit_model::singleton()->getMinMonthlyPayment($re1['price2']);
						}
						
					}
				}
				if(!empty($arr['size_stores'])){
					ksort ($arr['size_stores']);
					foreach ($arr['size_stores'] as &$arr1){ 	
						ksort($arr1);
							foreach ($arr['size_stores'] as &$arr_small){
								foreach ($arr_small as &$ar){
									ksort($ar);
								}
								unset($ar);
							}
							unset($arr_small);
						}
					unset($arr1);
					}
				$arr['url'] = Menu::getNodeFullUrl(self::$Mname,$arr['ci_id'], Page::$lang);
				
				Page::assign('areas_list',Catalog_model::get_areas());
				
				$meta[0] = Menu::getMeta('Main', 1, NULL, Page::$lang);
				$meta[1] = Menu::getMeta('Catalog', self::$curent_id, NULL, Page::$lang);
				
				$h1=Menu::getIndexInfo(self::$Mname,self::$curent_id);
				
				$meta_tags['title'] = "Модель ".$arr['sp_model'].' цвет '.$arr['color'].' / '.self::fetchHeader().' - '.Page::$AllSettings['Catalog']['title_goods'][1]['s_val'];
				$meta_tags['keywords'] = "Модель ".$arr['sp_model'].' цвет '.$arr['color'].' '.self::fetchHeader();
				$meta_tags['description'] = "Модель ".$arr['sp_model'].' цвет '.$arr['color'].' '.Page::$AllSettings['Catalog']['description_goods'][1]['s_val'].' - '.$meta[1]['title'];
				
				Page::assign('good_raiting',Catalog_model::getGoodRaiting(self::$block_id));
				Page::assign('full_revies',Catalog_model::getGoodReviews(self::$block_id));
				
				Page::assign('good_full_sizes',Catalog_model::getFullSizes(self::$block_id, $arr_sizes));
				Page::assign('good_full_arr_sizes',$arr_sizes);
				Page::assign('meta_tags', $meta_tags);
				Page::assign('H1',$h1["index_header"]);
				Page::assign('block', $arr);
				Page::assign('geoArea', $areaId);
				Page::assign('geoCity', $cityId);
				//Page::assign('NextPrevUrl', self::GetNextPrevUrl(self::$block_id));
				Page::assign('cart_goods', Catalog_model::getCartItems());

				if (isset($quickView)&& $quickView!=NULL){
					return Page::fetch(CTF.'/addons/catalog_content_small.tpl',$hash );
				}else{
					return Page::fetch(CTF.'/addons/catalog_content.tpl',$hash );
				}
			}else{
				Main::error();
			}
		
	}
	
	public static function get_other_as_current($id = NULL, $model= NULL){
		if (!empty($id) && !empty($model)){
			$result = array();
			$sql = "SELECT cb.`cb_id`, cb.`ci_id`, cb.`cb_url`,cb.`sp_model`, cb.`cb_price1`, cb.`cb_price2`,
					(SELECT cp.`photo_preview` FROM `catalog_photos` AS cp WHERE cp.`cb_id` = cb.`cb_id` AND cp.`photo_show` = '1' ORDER BY cp.`photo_order` ASC LIMIT 1) AS img
					FROM `catalog_blocks` as cb
					WHERE `sp_model` = '".$model."'
					AND cb.`block_show` = '1'
					AND `cb_id` <> '".$id."'
					ORDER BY `block_order` ;";
					$res = Page::$DB->query($sql);
					$num = $res->num_rows();
					if ($num > 0) {
						while ($re = $res->fetch_assoc()) {
							$result[] = $re;
						}
					}
			return $result;
		}
		return false;
	}
	
	public static function get_id_to_url($url = NULL, $module = NULL){
		$sql = "
			SELECT `ci_id` 
			FROM `catalog_index` 
			WHERE `ci_url` = '".Page::$DB->escape($url)."' 
						".(!Page::$preview ? "AND `index_show` = '1'" : "")." 
			LIMIT 1;
		";
		$res = Page::$DB->query($sql);
		$num = $res->num_rows();
		if ($num > 0){
			return (int)$res->get_one(0, 0);
		} else {
			return 0;
		}
	}

	public static function fetchContent(){
		if (!empty(Page::$url)){
			$url = (array)explode('/', UTF8::trim(self::$inner_url, '/'));
			$url = (string)$url[0];
			if (!empty($url)){
				$ci_id = self::get_id_to_url($url);
				;
				if (Menu::getNodeLevel(self::$Mname,$ci_id) > 3){
					Main::error();
				}
			}
		}
		
		$page_content = self::getContent();

		/*отправляем header */
		$modified = Menu::getModified(self::$Mname, self::$curent_id, self::$block_id, Page::$lang);
		$page_hash = md5($page_content);
		Page::sendLastModifiedHeader($modified['this'], $modified['last'], $page_hash);
		/*/отправляем header */	

		Page::assign('cat_cont',$page_content);	
		Page::$tpl->caching = false;
		echo Page::fetch(CTF.'/catalog.tpl');
	}

	
	public static function GetNextPrevUrl($current_id, $section=NULL) {
		if ($section==NULL) {
				$section=self::$curent_id;
		}
		
		$direction=array(
						'prev'=>'<',
						'next'=>'>'
						);
		$result=array(
		'prev'=>'',
		'next'=>''
		);
						
		foreach ($direction as $direct=>$key){
			
			$sql = "
				SELECT cb.`cb_id`,cb.`ci_id`,cb.`cb_url`,ci.`ci_url`
				FROM `catalog_blocks` AS cb
				INNER JOIN `catalog_blocks_cont` AS `cbc` ON (`cbc`.`cb_id` = `cb`.`cb_id`)
				INNER JOIN `catalog_index` AS `ci` ON (`ci`.`ci_id` = `cb`.`ci_id`)
				".(!(empty($ext_q[0])&&$ext_q[0]!=NULL) ? $ext_q[0]:'').				"
					WHERE cbc.`cb_id` = cb.`cb_id` 
					AND cb.`cb_id` ".$key." ".$current_id."
					AND cbc.`lang_id` = '".Page::$lang."' 
					".((!Page::$preview) ? "AND cb.`block_show` = '1'" : "")." 
					".(!empty($section) ? "AND cb.`ci_id` in (".$section.")" : '')." 
					".(count($arrSqlWhere) > 0 ? "AND " . implode(' AND ', $arrSqlWhere) : "")." 
					".(!(empty($ext_q[1])&&$ext_q[1]!=NULL) ? $ext_q[1]:'')."
					ORDER BY  `cb`.`cb_id`".(($direct=='prev') ? "DESC" : "ASC")."
				
				LIMIT 1;
			";
			
				$res = Page::$DB->query($sql);
				$num = $res->num_rows();
					if ($num > 0){
						//$block_id=(int)$res->get_one(0, 0);
						//$section_id=(int)$res->get_one(0, 1);
						$block_url=$res->get_one(0, 2);
						$section_url=$res->get_one(0, 3);
						$result[$direct]='/catalog/'.$section_url.'/'.$block_url.'.html';
					}
		}
		
		return $result;
	}
			

	public static function filter_form($section=NULL,$actions=false) {
		$fl_cont['price_min']=self::getPrice($section,'min');
		$fl_cont['price_max']=self::getPrice($section,'max');
		
		if ($actions == false){$fl_cont['type']=Menu::getSubmenuArrNameId(self::$Mname, self::$gender_form);}
		
		if (Page::isPDA()){
			$id=Menu::getSubmenuArrNameId(self::$Mname, self::$gender_form);;
			foreach ($id as $val){
				if ($val['id'] == self::$curent_id){
					$fl_cont['sostav']=self::getSostav($val['ext_id']);
				}
			}
			
		}
		$fl_cont['compos']='';
		$fl_cont['size']=self::getSizes();
		$fl_cont['length']=self::getLength();
		$fl_cont['hood']=array(1 => 'Не важно',2 => 'Есть', 3 => 'Нет');
		
		$fl_cont['status']=self::getStatus();
		
		$fl_cont['sales']=array(0 => 'Скидка');
		$fl_cont['gender']=self::$gender;
		Page::assign('fl_cont', $fl_cont);
		
		}
	

	public static function getCompareItems(){
		Catalog_model::setSavedCookie();
		Catalog_model::getCompareItems();
		Page::$tpl->caching = false;
		return Page::fetch(CTF.'/addons/shop_compare_small.tpl');
	}
	
	public static function refresh_compare() {
		Catalog_model::setCompareCookie();
		Catalog_model::getCompareItems();
		Page::$tpl->caching = false;
		echo Page::fetch(CTF.'/addons/shop_compare_small.tpl');
	} 
	public static function refresh_cart(){
		$count_goods=Catalog_model::getCartItems(true);
		Page::assign('count_goods',$count_goods);
		Page::$tpl->caching = false;
		echo Page::fetch(CTF.'/addons/shop_cart_small.tpl');
	}
	/**
	 * добавляем в сравнение
	 * @param object $itemid
	 * @return 
	 */
	public static function toCompare() {
		$itemid = (int)$_POST['id'];
			$sql = "SELECT `cb_sex` FROM `catalog_blocks` WHERE `cb_id`=".$itemid." LIMIT 1;";
			$res = Page::$DB->query($sql);
			$num = $res->num_rows();
				if ($num > 0){
					$sex=$res->get_one(0, 0);
					if ($sex=='м') {
						$gender='2';
					}
					if ($sex=='ж')
						$gender='1';
				}
		$hash = Catalog_model::setCompareCookie();
		Catalog_model::compare($itemid);
		if ($itemid>0) {
			$sql = "SELECT `sc_hash` FROM `shop_compare` WHERE `sc_hash` = '".$hash."' AND `sc_sb_id` = '".$itemid."'";
			$qw = Page::$DB->query($sql);
			if ($qw->num_rows()==0) {
				
				$sql = "INSERT INTO `shop_compare` (`sc_hash`,`sc_sb_id`,`sc_gender`) VALUES ('".$hash."', '".$itemid."', '".$gender."')";
			}
			
			Page::$DB->query($sql);
		}
	}
	
	public static function wish() {
		
		$id=$_REQUEST['id'];
		Catalog_model::wish($id);
		echo Catalog_model::getWishHash($id);
	}

	public static function removeFromCompare() {
		$id=$_REQUEST['id'];
		Catalog_model::removeFromCompare($id);
	}
		
	public static function compare() {
	
	if (isset ($_COOKIE['back_url']) && !empty($_COOKIE['back_url'])){
			$params=array();
			$back_url_tmp=explode (',',$_COOKIE['back_url']);
				;
				foreach ($back_url_tmp as $item){
					$val=(explode (':',$item));
					$params[$val[0]]=$val[1];
				}		
				if (isset($params['catalog_page'])&&$params['catalog_page']<>'undefined'){
					Page::assign('this_blocks_page',$params['catalog_page']);
				}
				if (isset($params['catalog_line'])&&$params['catalog_line']<>'undefined'){
					Page::assign('line_counter',$params['catalog_line']);
				}
				
				if (isset($params['self_url'])&&$params['self_url']<>'undefined'&&!empty($params['self_url'])){
					Page::assign('back_url',$params['self_url']);
				}else{
					Page::assign('back_url','/catalog/');
					
				}	
				if (isset($params['gender'])&&$params['gender']<>'undefined'){
					Page::assign('gender',$params['gender']);
				}
		}
		
		Page::assign('session_name', session_name());
		Page::assign('cart_goods', Catalog_model::getCartItems());
		Page::assign('session_id', session_id());

		$hidden_captcha = md5(microtime() . uniqid());
		$_SESSION['hidden_captcha'] = $hidden_captcha;
		Page::assign('hidden_captcha', $hidden_captcha);

		/* top menu */
		Menu::setTemplates('top_menu');
		Page::assign('top_menu', Menu::fetchOneLevel('Main', 2, NULL));


		/* kroshka */
		Menu::setTemplates('kroshka');
		Page::assign('kroshka', Menu::fetchTopDown(self::$Mname,self::$curent_id, 1, Page::$preview));
		/* /kroshka */

		/*left menu */
		Menu::setTemplates(array('tree', 'tree1', 'tree2', 'tree3', 'tree4'));
		Page::assign('left_menu', Menu::fetchTree(self::$Mname, 1, self::$curent_id, 2, Page::$preview));
		/* /left menu */
	
		
		Catalog_model::getFullCompareItems(array(),true);
		$meta[0] = Menu::getMeta('Main', 1, NULL, Page::$lang);
		$meta_tags['title'] = 'Сравнение моделей '.$meta[0]['title'];
		Page::assign('meta_tags', $meta_tags);
		Page::$tpl->caching = false;
		echo Page::fetch(CTF.'/addons/catalog_compare.tpl');
	} 
	
	public static function getInfo() {
			
		$sql ="SELECT `val` FROM `catalog_maintenance` WHERE `id`='2';";
		$res = Page::$DB->query($sql);
		$num = $res->num_rows();
		
		if ($num > 0){
				$datetime = explode(' ',$res->get_one(0, 0));
				$time=$datetime[1];
				$date=strftime('%Y-%m-%d',strtotime($datetime[0]));
				$arr = explode('-',$date);
				$new_date = $arr[2].' '.Page::$site_months_arr2[Page::$lang][(int)$arr[1]].' '.$arr[0]; 
				$catalog_update=$new_date.', '.$time;
		}
		Page::assign('catalog_update',$catalog_update);
	}

	public static function credit_calculate(){
		header('Content-type: text/plain; charset=UTF-8');

		$credit_goods_price = intval($_POST['credit_goods_price']);
		$cprog_initial_fee = intval($_POST['cprog_initial_fee']);
		$cprog_initial_fee_percent = intval($_POST['cprog_initial_fee_percent']);
		$cprog_period = intval($_POST['cprog_period']);
		$result = Credit_model::singleton()->getAvailablePrograms($credit_goods_price, $cprog_initial_fee, $cprog_initial_fee_percent, $cprog_period);
		echo json_encode($result);
		return;
		
	}
	public static function model_sms(){
		header('Content-type: text/plain; charset=UTF-8');
		$cb_id = trim($_POST['cb_id']);
		
		if (!isset($_POST['hcaptcha']) || !isset($_SESSION['hidden_captcha']) || $_POST['hcaptcha'] != $_SESSION['hidden_captcha']){
			echo json_encode(false);
			return;
		}
		unset($_SESSION['hidden_captcha']);
		
		$phone = (isset($_POST['phone']) ? preg_replace('#[^0-9]+#u', '', $_POST['phone']) : '');
		if (10 == UTF8::strlen($phone)){
			if ($cb_id){
				if ($good = Catalog_model::getModelInfo($cb_id)){
					$sms_text = Page::$AllSettings['Main']['sms_compare_before'][1]['s_val'] . "\n";
					$sms_text .= "Модель " . $good['sp_model'] . ": " . number_format($good['cb_price2'], 0, '', ' ') . " руб.\n";
					$sms_text .= Page::$AllSettings['Main']['sms_compare_after'][1]['s_val'] . "\n";
					$result = true;
					try {
						$sms = new SMS();
						if (!$sms->send($phone, UTF8::trim($sms_text), Page::$AllSettings['Main']['sms_send_phone'][1]['s_val'])){
							$result = false;
						}
					} catch (Exception $e){
						$result = false;
					}
					echo json_encode($result);
					return;
				}
			}
		}

		echo json_encode(false);
		return;
	}

	public static function cart(){
		$cart=Catalog_model::getCartItems();
		Page::assign('cart', $cart);
		Page::$tpl->caching = false;
		echo Page::fetch(CTF.'/addons/catalog_cart.tpl');
	}
	
	public static function addToCart() {
		$hash = Catalog_model::setCartCookie();
		$itemid = $_REQUEST['id'];
		$amount = $_REQUEST['amount'];
		$size = $_REQUEST['size'];
		
		if ($itemid>0) {
			if ($amount>0) {
				$sql = "SELECT `sc_hash` FROM `shop_cart` WHERE `sc_hash` = '".$hash."' AND `sc_sb_id` = '".$itemid."'";
				$qw = Page::$DB->query($sql);
				if ($qw->num_rows()==0) {
					$sql = "INSERT INTO `shop_cart` (`sc_hash`,`sc_sb_id`,`sc_amount`,`sc_size`) VALUES ('".$hash."', '".$itemid."', '".$amount."', '".$size."')";
				} else {
					$sql = "UPDATE `shop_cart` SET `sc_amount` = '".$amount."',`size` = '".$size."' WHERE `sc_hash` = '".$hash."' AND `sc_sb_id` = '".$itemid."'";
				}
			} else {
				$sql = "DELETE FROM `shop_cart` WHERE `sc_hash` = '".$hash."' AND `sc_sb_id` = '".$itemid."'";
			}
			Page::$DB->query($sql);
		}
	}
	public static function removeFromCart(){
		$hash = Catalog_model::setCartCookie();
		$itemid = $_REQUEST['id'];
		if ($itemid>0) {
			$sql = "DELETE FROM `shop_cart` WHERE `sc_hash` = '".$hash."' AND `sc_sb_id` = '".$itemid."';";
			Page::$DB->query($sql);
			
		}
	}
	public static function notprepay(){
			
		if (
			!isset($_GET['id'])
			|| empty($_GET['id'])
			|| false == ($id = CatalogOrders_model::prepareOrderID($_GET['id']))
			|| !isset($_SESSION['pay']['order_id'])
			|| $_SESSION['pay']['order_id'] != $id
			|| false == ($order_info = CatalogOrders_model::getOrderInfo($id))
		){
			Gen::redirect('/catalog/cart/');
		}
		
		$co_total_prepayment = CatalogOrders_model::calculatePrepayment($order_info['total']['salePrice']);
		CatalogOrders_model::updOrder($id, array(
			'co_total_salePrice' => $order_info['total']['salePrice'],
			'co_total_prepayment' => $co_total_prepayment
		));
		
		Page::assign('order_info', $order_info);
		Page::assign('num_order', str_pad($id,6,'0',STR_PAD_LEFT));
		echo Page::fetch(CTF . '/addons/catalog_notprepay.tpl');
	}
	
	public static function pay(){
			
		if (
			!isset($_GET['id'])
			|| empty($_GET['id'])
			|| false == ($id = CatalogOrders_model::prepareOrderID($_GET['id']))
			|| !isset($_SESSION['pay']['order_id'])
			|| $_SESSION['pay']['order_id'] != $id
			|| false == ($order_info = CatalogOrders_model::getOrderInfo($id))
		){
			Gen::redirect('/catalog/cart/');
		}
		if (isset($_GET['print'])&&$_GET['print']=='ok'){
		
			$fio=$order_info['co_fio'];
			$price='<strong>'.number_format($order_info['total']['co_del_price'], 0, '</strong>  <strong>', ' ').'</strong> руб. <strong>00</strong> коп.' ;
			$area=CatalogOrders_model::getOrderArea($order_info['co_area']);
			$city=CatalogOrders_model::getOrderCity($order_info['co_city']);
			$adress=$area.' '.$city.
			' ул. '.$order_info['co_street'].(!empty($order_info['co_house'])?', д.'.$order_info['co_house']:'').(!empty($order_info['co_housing'])?', корп.'.$order_info['co_housing']:'').(!empty($order_info['co_room'])?', кв.'.$order_info['co_room'] :'');
			$ord_id=str_pad($id,6,'0',STR_PAD_LEFT);
			Catalog_model::makePayForm($fio,$price,$adress,$ord_id);exit;
		}
		$co_total_prepayment = CatalogOrders_model::calculatePrepayment($order_info['total']['salePrice']);
		CatalogOrders_model::updOrder($id, array(
			'co_total_salePrice' => $order_info['total']['salePrice'],
			'co_total_prepayment' => $co_total_prepayment
		));
		Page::assign('print_link', '?id='.$_GET['id'].'&print=ok');
		Page::assign('num_order', str_pad($id,6,'0',STR_PAD_LEFT));
		echo Page::fetch(CTF . '/addons/catalog_pay.tpl');
	}

	public static function kpay_callback(){
		//CatalogOrders_model::paidOrder();

		if (isset($_REQUEST['ORDER'])){
			if (isset($_REQUEST['TRTYPE']) && 24 == $_REQUEST['TRTYPE']){
				$data = array();
				foreach (array(
					'ORDER', 'AMOUNT', 'CURRENCY',
					'RRN', 'INT_REF', 'TRTYPE',
					'TERMINAL', 'TIMESTAMP', 'NONCE',
					'P_SIGN', 'LANG'
				) as $k){
					if (isset($_REQUEST[$k])){
						$data[$k] = $_REQUEST[$k];
					}
				}

				CatalogOrders_model::updOrder(intval($_REQUEST['ORDER'], 10), array('co_is_paid' => '0', 'co_is_canceled' => '1', 'co_canceled_data' => serialize($data)));
			} else {
				$data = array();
				foreach (array(
					'AMOUNT', 'CURRENCY', 'ORDER',
					'EMAIL', 'CARD', 'EXP',
					'PAYMENT_TO', 'PAYMENT', 'PAYMENT_DATE',
					'RECUR_FREQ', 'RECUR_EXP', 'RECUR_REF',
					'RRN', 'INT_REF', 'APPROVAL',
					'RC', 'MESSAGE', 'CODE',
					'ACTION', 'NOTIFY_URL', 'Payment_Text'
				) as $k){
					if (isset($_REQUEST[$k])){
						$data[$k] = $_REQUEST[$k];
					}
				}

				if ($data['ACTION'] == '0'){
					CatalogOrders_model::paidOrder(intval($_REQUEST['ORDER'], 10), array('co_is_paid' => '1', 'co_paid_data' => serialize($data)));
				}
			}
		}

		//file_put_contents(DOC_ROOT . 'tmp/kpay_callback/' . time() . '.log', serialize($_REQUEST));
	}

	public static function kpay(){
		if (
			!isset($_GET['id'])
			|| empty($_GET['id'])
			|| false == ($id = CatalogOrders_model::prepareOrderID($_GET['id']))
			|| !isset($_SESSION['pay']['order_id'])
			|| $_SESSION['pay']['order_id'] != $id
			|| false == ($order_info = CatalogOrders_model::getOrderInfo($id))
		){
			Gen::redirect('/catalog/cart/');
		}

		$co_total_prepayment = round($order_info['total']['co_del_price']);

		CatalogOrders_model::updOrder($id, array(
			'co_total_salePrice' => $order_info['total']['salePrice'],
			'co_total_prepayment' => $co_total_prepayment
		));

		$pay_data_hidden = array();
		//$pay_data_hidden['CVC2_RC'] = '1';
		$pay_data_hidden['AMOUNT'] = strval($co_total_prepayment);
		$pay_data_hidden['CURRENCY'] = 'RUR';
		$pay_data_hidden['ORDER'] = str_pad($id, 6, '0', STR_PAD_LEFT);
		$pay_data_hidden['DESC'] = 'Predoplata zakaza nomer ' . str_pad($id,6,'0',STR_PAD_LEFT);
		$pay_data_hidden['MERCH_NAME'] = 'SHUBBING.RU';
		$pay_data_hidden['MERCH_URL'] = 'http://www.shubbing.ru/';
		$pay_data_hidden['MERCHANT'] = '465206023581701';
		$pay_data_hidden['TERMINAL'] = '23581701';
		//$pay_data_hidden['EMAIL'] = 'program2@sumteh.ru';
		//$pay_data_hidden['TRTYPE'] = '6';
		$pay_data_hidden['TRTYPE'] = '1';
		$pay_data_hidden['COUNTRY'] = 'RU';
		$pay_data_hidden['MERCH_GMT'] = intval(UTF8::str_replace('0', '', date('O')));
		$pay_data_hidden['TIMESTAMP'] = strftime('%Y%m%d%H%M%S', time() - date('Z'));
		$pay_data_hidden['NONCE'] = UTF8::substr(md5(time()), 16);
		$pay_data_hidden['BACKREF'] = 'http://' . HOST_NAME . '/catalog/cart/';

		$pay_data_hidden['P_SIGN'] = '';
		foreach (array('AMOUNT', 'CURRENCY', 'ORDER', 'DESC', 'MERCH_NAME', 'MERCH_URL', 'MERCHANT', 'TERMINAL', 'EMAIL', 'TRTYPE', 'COUNTRY', 'MERCH_GMT', 'TIMESTAMP', 'NONCE', 'BACKREF') as $k){
			if (isset($pay_data_hidden[$k]) && '' !== $pay_data_hidden[$k]){
				$pay_data_hidden[$k] = strval($pay_data_hidden[$k]);

				$pay_data_hidden['P_SIGN'] .= strlen($pay_data_hidden[$k]) . $pay_data_hidden[$k];
			} else {
				$pay_data_hidden['P_SIGN'] .= '-';
			}
		}
		$pay_data_hidden['P_SIGN'] = hash_hmac('sha1', $pay_data_hidden['P_SIGN'], pack('H*', 'D1FE9C2F7FD480A8D10FA75FEF74D4B0'));

		/*var_dump(hash_hmac('sha1', '410003RUR600002430Predoplata zakaza nomer 00002411SHUBBING.RU23http://www.shubbing.ru/15465206023581701823581701-112RU16142013103108474716faf6e670586c574236http://www.shubbing.ru/catalog/cart/--3-17', pack('H*', 'D1FE9C2F7FD480A8D10FA75FEF74D4B0')));
		var_dump(hash_hmac('sha1', '410003RUR600002430Predoplata zakaza nomer 00002411SHUBBING.RU23http://www.shubbing.ru/15465206023581701823581701-112RU16142013103108474716faf6e670586c574236http://www.shubbing.ru/catalog/cart/--3-', pack('H*', 'D1FE9C2F7FD480A8D10FA75FEF74D4B0')));
		var_dump(hash_hmac('sha1', '410003RUR600002430Predoplata zakaza nomer 00002411SHUBBING.RU23http://www.shubbing.ru/15465206023581701823581701-112RU16142013103108474716faf6e670586c574236http://www.shubbing.ru/catalog/cart/--3', pack('H*', 'D1FE9C2F7FD480A8D10FA75FEF74D4B0')));
		var_dump(hash_hmac('sha1', '410003RUR600002430Predoplata zakaza nomer 00002411SHUBBING.RU23http://www.shubbing.ru/15465206023581701823581701-112RU16142013103108474716faf6e670586c574236http://www.shubbing.ru/catalog/cart/--', pack('H*', 'D1FE9C2F7FD480A8D10FA75FEF74D4B0')));
		var_dump(hash_hmac('sha1', '410003RUR600002430Predoplata zakaza nomer 00002411SHUBBING.RU23http://www.shubbing.ru/15465206023581701823581701-112RU16142013103108474716faf6e670586c574236http://www.shubbing.ru/catalog/cart/-', pack('H*', 'D1FE9C2F7FD480A8D10FA75FEF74D4B0')));
		var_dump(hash_hmac('sha1', '410003RUR600002430Predoplata zakaza nomer 00002411SHUBBING.RU23http://www.shubbing.ru/15465206023581701823581701-112RU16142013103108474716faf6e670586c574236http://www.shubbing.ru/catalog/cart/', pack('H*', 'D1FE9C2F7FD480A8D10FA75FEF74D4B0')));
		die();*/

		/*var_dump(hash_hmac('sha1', '511.483USD677144616IT Books. Qty: 217Books Online Inc.14www.sample.com1512345678901234589999999919pgw@mail.sample.com11--142003010515302116F2B2DD7E603A7ADA33https://www.sample.com/shop/reply', pack('H*', '00112233445566778899AABBCCDDEEFF')));
		var_dump('FACC882CA67E109E409E3974DDEDA8AAB13A5E48');*/

		//var_dump($pay_data_hidden);

		$pay_data = array();
		$pay_err = array();
		if (isset($_POST['pay_data'])){
			$pay_data['CARD'] = htmlspecialchars(UTF8::trim($_POST['pay_data']['CARD']), ENT_QUOTES);
			$pay_data['EXP'] = max(0, intval($_POST['pay_data']['EXP']));
			$pay_data['EXP_YEAR'] = intval($_POST['pay_data']['EXP_YEAR']);
			$pay_data['CVC2'] = htmlspecialchars(UTF8::trim($_POST['pay_data']['CVC2']), ENT_QUOTES);
			$pay_data['NAME'] = htmlspecialchars(UTF8::trim($_POST['pay_data']['NAME']), ENT_QUOTES);

			if ('' == $pay_data['CARD']){
				$pay_err['CARD'] = 'Не введен номер карты';
			}
			if ($pay_data['EXP'] <= 0 || $pay_data['EXP'] > 12){
				$pay_err['EXP'] = 'Неверно введен месяц окончания действия карты';
			}
			if ($pay_data['EXP_YEAR'] < date('y')){
				$pay_err['EXP_YEAR'] = 'Неверно введен год окончания действия карты';
			}
			if ('' == $pay_data['CVC2']){
				$pay_err['CVC2'] = 'Не введен СVV/CVC';
			}
			if ('' == $pay_data['NAME']){
				$pay_err['NAME'] = 'Не заполнено имя держателя';
			}

			if (empty($pay_err)){
				/*if ($curl = curl_init()){
					curl_setopt($curl, CURLOPT_URL, 'https://3ds2.mmbank.ru/cgi-bin/cgi_link');
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
					//curl_setopt($curl, CURLOPT_HEADER, true);
					curl_setopt($curl, CURLOPT_POST, true);
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array_merge($pay_data, $pay_data_hidden)));
					$out = curl_exec($curl);
					echo($out);
					curl_close($curl);
				}
				var_dump(http_build_query(array_merge($pay_data, $pay_data_hidden)));
				return;*/
				/*$context = stream_context_create(array(
					'http' => array(
						'method' => 'POST',
						'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
						'content' => http_build_query($pay_data)
					),
				));
				var_dump(file_get_contents('https://3ds2.mmbank.ru/cgi-bin/cgi_link', false, $context));*/
			}
		}

		Page::assign('order_info', $order_info);
		Page::assign('co_total_prepayment', $co_total_prepayment);
		Page::assign('order_end_session_timestamp', ini_get('session.gc_maxlifetime') - (time() - $_SESSION['pay']['timestamp']));
		Page::assign('pay_data_hidden', $pay_data_hidden);
		Page::assign('pay_data', $pay_data);
		Page::assign('pay_err', $pay_err);

		echo Page::fetch(CTF . '/addons/catalog_pay_card.tpl');
		//unset($_SESSION['pay']);
	}

	
	
	public static function buy(){
		
		$result = array();
		$pst['or-name']=htmlspecialchars(Gen::unescape($_REQUEST['or-name'], ENT_QUOTES));
		$pst['or-fio']=htmlspecialchars(Gen::unescape($_REQUEST['or-fio'], ENT_QUOTES));
		$pst['or-phone']=htmlspecialchars(Gen::unescape($_REQUEST['or-phone'], ENT_QUOTES));
		$pst['or-email']=htmlspecialchars(Gen::unescape($_REQUEST['or-email'], ENT_QUOTES));
		$pst['or-street']=htmlspecialchars(Gen::unescape($_REQUEST['or-street'], ENT_QUOTES));
		$pst['or-house']=htmlspecialchars(Gen::unescape($_REQUEST['or-house'], ENT_QUOTES));
		$pst['or-housing']=htmlspecialchars(Gen::unescape($_REQUEST['or-housing'], ENT_QUOTES));
		$pst['or-comment']=htmlspecialchars(Gen::unescape($_REQUEST['or-comment'], ENT_QUOTES));
		$pst['or-payform']=htmlspecialchars(Gen::unescape($_REQUEST['or-payform'], ENT_QUOTES));
		$pst['or-area']=htmlspecialchars(Gen::unescape($_REQUEST['or-area'], ENT_QUOTES));
		$pst['or-city']=htmlspecialchars(Gen::unescape($_REQUEST['or-city'], ENT_QUOTES));
		$pst['or-room']=htmlspecialchars(Gen::unescape($_REQUEST['or-room'], ENT_QUOTES));
		$pst['or-period']=(int)(Gen::unescape($_REQUEST['or-period'], ENT_QUOTES));
		
		if ((trim($pst['or-name'])=='')||(!preg_match("/^[а-яА-ЯЁё ]+$/iu", $pst['or-name']))) {
			$err['or-name'] = 'Проверьте правильность заполнения';
		} 
		if ((trim($pst['or-fio'])=='')||(!preg_match("/^[а-яА-ЯЁё -]+$/iu", $pst['or-fio']))) {
			$err['or-fio'] = 'Проверьте правильность заполнения';
		} 
		if (trim($pst['or-phone'])=='') {
			$err['or-phone'] = 'Проверьте правильность заполнения';
		} 
		if (trim($pst['or-email'])==''||(!preg_match("/^(\S+)@([a-z0-9-]+)(\.)([a-z]{2,4})(\.?)([a-z]{0,4})+$/iu", $pst['or-email']))) {
			$err['or-email'] = 'Проверьте правильность заполнения';
		} 
		if (trim($pst['or-street'])=='') {
			$err['or-street'] = 'Проверьте правильность заполнения';
		} 
		if ((trim($pst['or-house'])=='')) {
			$err['or-house'] = 'Проверьте правильность заполнения';
		} 
		
		if (trim($pst['or-payform'])=='') {
			$err['or-payform'] = '';
		}
		
		if (trim($pst['or-area'])=='0' || trim($pst['or-city'])=='0') {
			$err['or-area'] = 'Уточните регион доставки';
		}
		if (empty($pst['or-period']) || !isset($pst['or-period'])) {
			$err['or-period'] = 'Уточните период доставки';
		}
		
		$pst['full_name']=$pst['or-name'].' '.$pst['or-fio'];
		if (empty($err)) {
				
				$sql = "INSERT INTO `catalog_orders` (
						`co_fio`,
						`co_email`,
						`co_phone`,
						`co_area`,
						`co_city`,
						`co_street`,
						`co_house`,
						`co_housing`,
						`co_room`,
						`co_consumer`,
						`co_comment`,
						`co_period`,
						`co_payform`,
						`co_datetime`
						)VALUES (
						'".$pst['full_name']."',
						'".$pst['or-email']."',
						'".$pst['or-phone']."',
						'".$pst['or-area']."',
						'".$pst['or-city']."',
						'".$pst['or-street']."',
						'".(!empty($pst['or-house'])?$pst['or-house']:'')."',
						'".(!empty($pst['or-housing'])?$pst['or-housing']:'')."',
						'".$pst['or-room']."',
						'1',
						'".(!empty($pst['or-comment'])?$pst['or-comment']:'')."',
						'".$pst['or-period']."',
						'".$pst['or-payform']."',
						NOW()
						
					)";
				Page::$DB->query($sql);
				$order_id=Page::$DB->insert_id;
				$full_order_price=0;
				$hash=Catalog_model::setCartCookie();
				
				//$addon_tarif = (count($_REQUEST['or-goods'])<2 ? 295 : 130);
				$addon_tarif = 0;
				
				foreach ($_REQUEST['or-goods'] as $id=>$order ){
					$sql = "INSERT INTO `catalog_orders_items` (
						`co_id`,
						`cb_id`,
						`co_type_id`,
						`co_del_price`,
						`goods_model`,
						`salePrice`,
						`size`
						)VALUES (
						'".$order_id."',
						'".$id."',
						'".Page::$DB->escape($order['co_type_id'])."',
						'".Page::$DB->escape(CatalogOrders_model::getDeliveryPrice($order['salePrice'],$pst['or-city'],$addon_tarif,$pst['or-period']))."',
						'".Page::$DB->escape($order['goods_model'])."',
						'".Page::$DB->escape($order['salePrice'])."',
						'".Page::$DB->escape($order['size'])."'
							
					)";	
					Page::$DB->query($sql);
					$sql_upd="
						UPDATE `shop_cart`
						SET `sc_order_id` = ".intval($order_id)."
						WHERE `sc_hash` = '".$hash."'
						AND `sc_sb_id` = ".intval($id)."
						LIMIT 1;
					";
					Page::$DB->query($sql_upd);
					$full_order_price+=$order['salePrice'];
				}
				
				
				if ($pst['or-payform'] == '0'){
					$result['redirect']='https://' . HOST_NAME . '/catalog/kpay/?id=x' . intval($order_id);
				}elseif($pst['or-payform'] == '1') {
					$result['redirect']='/catalog/pay/?id=x' . intval($order_id);
				}elseif($pst['or-payform'] == '2') {
					$result['redirect']='/catalog/notprepay/?id=x' . intval($order_id);
				}
				$_SESSION['pay']=$pst;
				$_SESSION['pay']['order_id']=$order_id;
				$_SESSION['pay']['timestamp']=time();
				
					$fop=CatalogOrders_model::getOrderInfo($order_id);
					
					$sms_text= $pst['full_name'].', '. Page::$AllSettings['Main']['sms_order'][1]['s_val'];
					$sms_text.= " №". str_pad($order_id,6,'0',STR_PAD_LEFT)." принят. ";
					$sms_text.= Page::$AllSettings['Main']['sms_order_after'][1]['s_val'];
					
					$phone = (isset($pst['or-phone']) ? preg_replace('#[^0-9]+#u', '', $pst['or-phone']) : '');
					
					
					$sms = new SMS();
					$sms->send($phone, UTF8::trim($sms_text), Page::$AllSettings['Main']['sms_send_phone'][1]['s_val']);

					
				CatalogOrders_model::OrderOperator($order_id,$pst['full_name'],$pst['or-phone']);
				CatalogOrders_model::SendMailOrder($order_id);
				CatalogOrders_model::deleteCartItems($order_id);
				$result['ok']='confirm';
			}else{
				$result['err']=$err;
				$result['pst']=$pst;
		}
		echo json_encode($result);
	
	}
	public static function compare_sms(){
		header('Content-type: text/plain; charset=UTF-8');

		if (!isset($_POST['hcaptcha']) || !isset($_SESSION['hidden_captcha']) || $_POST['hcaptcha'] != $_SESSION['hidden_captcha']){
			echo json_encode(false);
			return;
		}
		unset($_SESSION['hidden_captcha']);

		$index = ((isset($_POST['index']) && 'man' == $_POST['index']) ? 'man' : 'woman');
		$phone = (isset($_POST['phone']) ? preg_replace('#[^0-9]+#u', '', $_POST['phone']) : '');
		if (10 == UTF8::strlen($phone)){
			$nodedInfo = Menu::getNodeInfo('Catalog', ('man' == $index ? 2 : 1));

			$sql = "SELECT `ci_id` FROM `catalog_index`
			WHERE
				`index_lft` >= " . intval($nodedInfo[0]) . "
				AND `index_rgt` <= " . intval($nodedInfo[1]) . "
				AND `index_show` = '1';";
			$res = Page::$DB->query($sql);
			$ci_ids = array();
			while ($row = $res->fetch_row()){
				$ci_ids[] = intval($row[0]);
			}

			if ($ci_ids){
				if ($goods = Catalog_model::getFullCompareItems($ci_ids)){
					$sms_text = Page::$AllSettings['Main']['sms_compare_before'][1]['s_val'] . "\n";
					foreach ($goods as $good){
						$min_price = 0;
						foreach ($good['size_stores'] as $price => $size_arrays){
							if (0 != $price){
								if (0 == $min_price || $min_price > $price){
									$min_price = $price;
								}
							} else {
								if (0 == $min_price || $min_price > $good['cb_price2']){
									$min_price = $good['cb_price2'];
								}
							}
						}
						$sms_text .= "Модель " . $good['sp_model'] . ": " . number_format($min_price, 0, '', ' ') . " руб.\n";
					}
					$sms_text .= Page::$AllSettings['Main']['sms_compare_after'][1]['s_val'] . "\n";

					$result = true;
					
					try {
						$sms = new SMS();
						if (!$sms->send($phone, UTF8::trim($sms_text), Page::$AllSettings['Main']['sms_send_phone'][1]['s_val'])){
							$result = false;
						}
					} catch (Exception $e){
						$result = false;
					}

					echo json_encode($result);
					return;
				}
			}
		}

		echo json_encode(false);
		return;
	}

	public static function discount_sms(){
		header('Content-type: text/plain; charset=UTF-8');
		
		if (!isset($_POST['hcaptcha']) || !isset($_SESSION['hidden_captcha']) || $_POST['hcaptcha'] != $_SESSION['hidden_captcha']){
			echo json_encode(false);
			return;
		}
		unset($_SESSION['hidden_captcha']);

		$phone = (isset($_POST['phone']) ? preg_replace('#[^0-9]+#u', '', $_POST['phone']) : '');
		if (10 == UTF8::strlen($phone)){
			$result = true;
			try {
				$sms_text = Page::$AllSettings['Main']['sms_discount_text'][1]['s_val'];

				$sms = new SMS();
				if (!$sms->send($phone, UTF8::trim($sms_text), Page::$AllSettings['Main']['sms_send_phone'][1]['s_val'])){
					$result = false;
				}
			} catch (Exception $e){
				$result = false;
			}
		} else {
			$result = false;
		}

		echo json_encode($result);
		return;
	}
	
	public static function make_order(){
		if (isset ($_COOKIE['sel_area'])&&!empty($_COOKIE['sel_area'])){$sel_region['area']=$_COOKIE['sel_area'];}
		if (isset ($_COOKIE['sel_city'])&&!empty($_COOKIE['sel_city'])){$sel_region['city']=$_COOKIE['sel_city'];}
		
		if (isset ($_POST['selected_goods'])){
			Page::assign('areas_list',Catalog_model::get_areas());		
			$sum_order=0;
			foreach ($_POST['selected_goods'] as $items){
				if (isset($items['id'])&&!empty($items['id'])){
					$order[$items['id']]=Catalog_model::getOrderItems($items['id'],$items['item_size']);
					$order[$items['id']]['co_type_id']=Page::$DB->escape($items['co_type_id']);
					$sum_order=$sum_order+$order[$items['id']]['salePrice'];
				}
			}
			$count=count($order);
			Page::assign('sel_region',$sel_region);
			Page::assign('sum_order',$sum_order);
			Page::assign('count',$count);
			Page::assign('count_str',Catalog_model::getTovarTovarov($count));
			Page::assign('order',$order);
			if (isset($_COOKIE['pay_data'])&&!empty($_COOKIE['pay_data'])){
				
				$pst=unserialize($_COOKIE['pay_data']);
				foreach ($pst as $key=>$item){
					$key = str_replace("or-", "", $key);
					$pst_c[$key]=$item;
				}
				
				Page::assign('pst_c',$pst_c);
			}
			echo Page::fetch(CTF.'/addons/catalog_order.tpl');
		}else{
			header('location: /catalog/cart/');
			exit;
		}
	}
	public static function helpfulness(){
		if (isset($_REQUEST['helpfulness']) && $_POST['ajax']==true) {
			$fq_id=(int)Page::$DB->escape($_POST['data_val']['fq_id']);
			$cell=Page::$DB->escape($_POST['data_val']['cell']);
			$result=Catalog_model::helpfulness($fq_id,$cell);
			echo json_encode($result);
		}
	
	}
	public static function review_send(){
		
		if (isset($_REQUEST['review_send']) && $_POST['ajax']==true) {
			$result['ok']=false;
			$pst=($_REQUEST['data_val']);
			
			$pst['or_cb_id'] = htmlspecialchars(Gen::unescape($_REQUEST['data_val']['or_cb_id']), ENT_QUOTES);
			$pst['or_text'] = htmlspecialchars(Gen::unescape($_REQUEST['data_val']['or_text']), ENT_QUOTES);
			$pst['or_val'] = htmlspecialchars(Gen::unescape($_REQUEST['data_val']['or_val']), ENT_QUOTES);
			$pst['or_city'] = htmlspecialchars(Gen::unescape($_REQUEST['data_val']['or_city']), ENT_QUOTES);
			$pst['or_name'] = htmlspecialchars(Gen::unescape($_REQUEST['data_val']['or_name']), ENT_QUOTES);
			
			$err = array();
			$result = array();
			if ((trim($pst['or_text'])=='')||(trim($pst['or_text'])=='<br>')) {
				$err['or_text'] = 'Введите текст сообщения!';
			}
			
			if (trim($pst['or_name'])=='') {
				$err['or_name'] = 'Введите имя!';
			} 
			
			if (trim($pst['or_city'])=='') {
				$err['or_city'] = 'Укажите город!';
			}   
			if (empty($err)) {
				$sql = "
					INSERT INTO `rws_questions` (
						`fq_id`, 
						`fi_id`, 
						`fq_date_add`, 
						`fq_author`, 
						`fq_author_city`, 
						`fq_text`
					) VALUES (
						NULL, 
						'1', 
						NOW(), 
						'".Page::$DB->escape($pst['or_name'])."', 
						'".Page::$DB->escape($pst['or_city'])."', 
						'".Page::$DB->escape($pst['or_text'])."'
						
					);
				"; 
				$qw = Page::$DB->query($sql);
				$sql = "
					INSERT INTO `rws_goods` (
						`fq_id`, 
						`cb_id`, 
						`raiting`
					) VALUES (
						'".Page::$DB->insert_id."', 
						'".Page::$DB->escape($pst['or_cb_id'])."', 
						'".Page::$DB->escape($pst['or_val'])."'
					);
				"; 
				$qw = Page::$DB->query($sql);
				$body= 'Уважаемый администратор, <br />'.strftime('%R %d.%m.%Y', time()).' поступил новый отзыв с сайта<br />
				<b>Имя</b>&nbsp;&nbsp;'.$pst['or_name'].'<br />
				<b>Город</b>&nbsp;&nbsp;'.$pst['or_city'].'<br />
				<b>Текст отзыва</b><br />'.nl2br($pst['or_text']).'<br /><br />
				С уважением, Ваш сайт.
				';
				Mailsend::factory()
				->setFromEmail(Page::$AllSettings['Main']['admin_mail'][1]['s_val'])
				->setFromName(Page::$AllSettings['Main']['admin_name'][1]['s_val'])
				->setSubject('Отзыв с сайта www.shubbing-shop.ru')
				->setBody($body)
				->setTo(Page::$AllSettings['Main']['review_mail'][1]['s_val'])
				->send();
				unset($pst);
				$result['ok']=true;
			} else {
				$result['err']=$err;
				$result['pst']=$pst;
			}
			echo json_encode($result);
		} 
	
	}
	
	
	

	
}
?>