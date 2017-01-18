<?php

	namespace Drupal\bocExtractor\Controller;

	class BocExtractorController {

		private $options;
		
		public static function test() {
			return array(
					'#markup' => t('Hello World!'), 
					'#title' => t('Hello World!')
				);
		}

		public static function boc_form() {
			$form['BocExtractor_description'] = array(
			    '#title' => t('Drupal Boc extractor Settings'), 
			    '#markup' => t('Personnalisez les liens de publications de BOCs au cas la BRVM changeait de site!'),
			);

			$form['BocExtractor_url'] = array(
				'#type' => 'textfield', 
				'#title' => t('Lien page des publications de Bulletins Officiels de la Côte:'), 
				'#default_value' => '',
			);
			    
			$form['BocExtractor_class'] = array(
				'#title' => t('Classe CSS commune aux liens de publications des Bulletins Officiels de la Côte:'),
			    '#type' => 'textfield'
			);

			$form['submit_button'] = array( 
			    '#type' => 'submit',
			    '#value' => t('ENREGISTRER LA CONFIGURATION'),
			);
			  
			return $form;
			// return system_settings_form($form);
		}

		/* fonctions */
		public static function get_boc() {

			ini_set('user_agent', 'NameOfAgent (http://www.brvm.org)');

			//var_dump($this->parametres_wp_boc_options);

			$Options = $this->getOptions();

			$address_bocs = $Options['lien'];

			$btn_class = $Options['classe'];

			//var_dump($btn_class);

			# Create a DOM parser object
			$dom = new DOMDocument();

			$uploaddir = wp_upload_dir();

			// $folder = $uploaddir['path'] . "/bocs/"; //retourne le path complet donné par wp par defaut.	

			$folder = $uploaddir['basedir'] . "/bocs/";

			if($this->robots_allowed($address_bocs, "NameOfAgent")) {

				# Parse the HTML from address.
				# The @ before the method call suppresses any warnings that
				// @$dom->loadHTML($html);
				@$dom->loadHTMLFile($address_bocs);

				$xpath = new DOMXPath($dom);
				
				$nodes = $xpath->query("//a[contains(@class, '" . $btn_class . "')]/@href");

				$nodes_number = $xpath->evaluate("count(//a[contains(@class, $btn_class)]/@href)");

				//var_dump($nodes_number, $nodes->length);
				
				if ($this->urlExist($address_bocs) === true && $nodes->length > 0) {

					foreach ($nodes as $key => $node) {
						# code...
						# var_dump($node->nodeValue);

						$filelink = $node->nodeValue;

						if (!file_exists($folder)) {
							mkdir($folder, 0777, true);
							chown($folder, "root");
							chmod($folder, 0777);
						}

						if (!file_exists($folder . "/" . basename($filelink))) {

							if ( file_put_contents($folder . "/" . basename($filelink) , fopen($filelink, 'r')) ) {
							    var_dump('yes');
							} else {
							    var_dump('no');
							}

						} else {

							//var_dump("Existe!");
							// var_dump($folder . "/" . basename($filelink));
							return;

						}

					}

				} else { 

					$this->error_notice_pages_ressources_not_good();
				}	

			} else {

			 	die('Access denied by robots.txt');
			}

		}

		/* Si l'url existe et ne retourne pas 404 */
		public function urlExist($url) {
		    $file_headers = @get_headers($url);
		    if ( strpos($file_headers[0],'200') === false ) {
		     	return false;
		    } else {
		    	return true;
		    }
		}

		/*fonction indépendantes de wp mais qui fait un traitement important: Robots allowed*/
		public function robots_allowed($url, $useragent=false) {
		    // parse url to retrieve host and path
		    $parsed = parse_url($url);

		    $agents = array(preg_quote('*'));
		    if($useragent) $agents[] = preg_quote($useragent);
		    $agents = implode('|', $agents);

		    // location of robots.txt file
		    $robotstxt = @file("http://{$parsed['host']}/robots.txt");

		    // if there isn't a robots, then we're allowed in
		    if( empty($robotstxt) ) return true;

		    $rules = array();
		    $ruleApplies = false;
		    foreach($robotstxt as $line) {
		        // skip blank lines
		        if(!$line = trim($line)) continue;

		        // following rules only apply if User-agent matches $useragent or '*'
		        if(preg_match('/^\s*User-agent: (.*)/i', $line, $match)) {
		          $ruleApplies = preg_match("/($agents)/i", $match[1]);
		        }

		        if($ruleApplies && preg_match('/^\s*Disallow:(.*)/i', $line, $regs)) {
		          // an empty rule implies full access - no further tests required
		          if(!$regs[1]) return true;
		          // add rules that apply to array for testing
		          $rules[] = preg_quote(trim($regs[1]), '/');
		        }
		    }

		    foreach($rules as $rule) {
		        // check if page is disallowed to us
		        if(preg_match("/^$rule/", $parsed['path'])) return false;
		    }

		    // page is not disallowed
		    return true;
	  	}


		// fonctions wordpress nécessaires

		/*Erreur à afficher; voir includes/class-wp-boc-extractor.php pour l'ajout des actions et hooks (add_action) */
	  	public function  error_notice_pages_ressources_not_good() {
	  		ob_start(); ?>
		    <div class="error notice">
		        <p>
		        <?php
		        	_e( "Il semble que le site de la BRVM ait été mis à jour. Revérifiez dans vos paramètres d'avoir bien précisé la nouvelle url présentant ou listant vos ressources, ainsi que la classe css des ressources à récupérer.", 'wp-boc-extractor' );
		        ?>
		        <br>
		        <a href="options-general.php?page=parametres-wp-boc">Paramètres WP-BOC</a>
		        </p>
		    </div>
	    	<?php
		}

		//settings page
		public function parametres_wp_boc_add_plugin_page() {
			add_menu_page(
				'Paramètres WP-BOC', // page_title
				'Paramètres WP-BOC', // menu_title
				'manage_options', // capability
				'parametres-wp-boc', // menu_slug
				array( $this, 'parametres_wp_boc_create_admin_page' ), // function
				'dashicons-media-document' // icon_url

			);
		}

		public function parametres_wp_boc_create_admin_page() {
			$this->parametres_wp_boc_options = get_option( 'parametres_wp_boc_option_name' );
			?>

			<div class="wrap">
				<h2>Paramètres WP-BOC</h2>
				<p>Personnalisez les liens de publications de BOCs au cas la BRVM changeait de site.</p>
				<?php settings_errors(); ?>

				<form method="post" action="options.php">
					<?php
						settings_fields( 'parametres_wp_boc_option_group' );
						do_settings_sections( 'parametres-wp-boc-admin' );
						submit_button();
					?>
				</form>

				<div class="list-bocs">

					<h1>List of files / Liste des fichiers:</h1>

					<ul>

					<?php

						if(!$_GET['start']) {  
							$start = 0;  
						} else {  
							$start = $_GET['start'];  
						}  

						$exclude_files = array("");  
						$ifiles = Array();  
						$handle = opendir(wp_upload_dir()['basedir'] . '/bocs/');  
						$number_to_display = '9';

						while (false !== ($file = readdir($handle))) {  
						   if ($file != "." && $file != ".." && !in_array($file, $exclude_files)) {    
						       $ifiles[] = $file;  
						   }  
						}  
							
						closedir($handle);  

						$total_files = count($ifiles);  
						$req_pages = ceil($total_files/$number_to_display);  

						// echo "Nombre total = ". $total_files."<br>";  

						for($z=0; $z<$number_to_display; $z++) { 
							if ($start < 0)
								$start = 0;
					        $vf = $z + $start;  
					        $ifiles_display = explode(".", $ifiles[$vf]);  
					      	echo "<li class='item-boc'><a class='btn-boc' href=". $ifiles[$vf]. ">".  $ifiles_display[0] . "</a></li>";  
							echo '<br>';  
						}  

						// echo "<br> Pagination = ". $req_pages. "<br>";  
						echo "<a href=\"?start=0\">Premier</a> |";  
							   
						for( $x=1; $x<$req_pages; $x++ ) { ?>  
							<a href="?page=parametres-wp-boc&start=<? echo ($x-1)*$number_to_display; ?>"><? echo $x; ?></a> |  

							<? } ?>  
							<a href="?page=parametres-wp-boc&start=<? echo $total_files-$number_to_display; ?>">Dernier</a> |

					</ul>
					
				</div>

			</div>

		<?php }


		public function getOptions() {
			// //verification du lien
			if ( isset(get_option( 'parametres_wp_boc_option_name' )['lien_page_des_publications_de_bulletins_officiels_de_la_cote_0']) && (get_option( 'parametres_wp_boc_option_name' )['lien_page_des_publications_de_bulletins_officiels_de_la_cote_0'] <> '') ) {
				$address_bocs = get_option( 'parametres_wp_boc_option_name' )['lien_page_des_publications_de_bulletins_officiels_de_la_cote_0'] ;
			} else {
				$address_bocs = "http://www.brvm.org/fr/bulletins-officiels-de-la-cote";
			}

			//vérification de la classe des liens de téléchargement des BOCs
			if ( isset(get_option( 'parametres_wp_boc_option_name' )['classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1']) && (get_option( 'parametres_wp_boc_option_name' )['classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1'] <> '') ) {
				$btn_class = get_option( 'parametres_wp_boc_option_name' )['classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1'] ;
			} else {
				$btn_class = "btn-download";
			}

			return array('lien' => $address_bocs, 'classe' => $btn_class );
		}


		public function parametres_wp_boc_page_init() {
			register_setting(
				'parametres_wp_boc_option_group', // option_group
				'parametres_wp_boc_option_name', // option_name
				array( $this, 'parametres_wp_boc_sanitize' ) // sanitize_callback
			);

			add_settings_section(
				'parametres_wp_boc_setting_section', // id
				'Settings', // title
				array( $this, 'parametres_wp_boc_section_info' ), // callback
				'parametres-wp-boc-admin' // page
			);

			add_settings_field(
				'lien_page_des_publications_de_bulletins_officiels_de_la_cote_0', // id
				'Lien page des publications de Bulletins Officiels de la Côte', // title
				array( $this, 'lien_page_des_publications_de_bulletins_officiels_de_la_cote_0_callback' ), // callback
				'parametres-wp-boc-admin', // page
				'parametres_wp_boc_setting_section' // section
			);

			add_settings_field(
				'classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1', // id
				'Classe CSS commune aux liens de publications des Bulletins Officiels de la Côte', // title
				array( $this, 'classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1_callback' ), // callback
				'parametres-wp-boc-admin', // page
				'parametres_wp_boc_setting_section' // section
			);
		}

		public function parametres_wp_boc_sanitize($input) {
			$sanitary_values = array();
			if ( isset( $input['lien_page_des_publications_de_bulletins_officiels_de_la_cote_0'] ) ) {
				$sanitary_values['lien_page_des_publications_de_bulletins_officiels_de_la_cote_0'] = sanitize_text_field( $input['lien_page_des_publications_de_bulletins_officiels_de_la_cote_0'] );
			}

			if ( isset( $input['classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1'] ) ) {
				$sanitary_values['classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1'] = sanitize_text_field( $input['classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1'] );
			}

			return $sanitary_values;
		}

		public function parametres_wp_boc_section_info() {
			
		}

		public function lien_page_des_publications_de_bulletins_officiels_de_la_cote_0_callback() {
			printf(
				'<input class="regular-text" type="text" name="parametres_wp_boc_option_name[lien_page_des_publications_de_bulletins_officiels_de_la_cote_0]" id="lien_page_des_publications_de_bulletins_officiels_de_la_cote_0" value="%s">',
				isset( $this->parametres_wp_boc_options['lien_page_des_publications_de_bulletins_officiels_de_la_cote_0'] ) ? esc_attr( $this->parametres_wp_boc_options['lien_page_des_publications_de_bulletins_officiels_de_la_cote_0']) : ''
			);
		}

		public function classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1_callback() {
			printf(
				'<input class="regular-text" type="text" name="parametres_wp_boc_option_name[classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1]" id="classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1" value="%s">',
				isset( $this->parametres_wp_boc_options['classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1'] ) ? esc_attr( $this->parametres_wp_boc_options['classe_css_commune_aux_liens_de_publications_des_bulletins_officiels_de_la_cote_1']) : ''
			);
		}



	}


	function hook_install() {
		var_dump("ok");
		BocExtractorController::get_boc();
	}
