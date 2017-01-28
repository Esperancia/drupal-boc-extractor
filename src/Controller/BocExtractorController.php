<?php

	namespace Drupal\bocExtractor\Controller;
	
	use Drupal\Core\Controller\ControllerBase;
	use Drupal\Component\Utility\Html;
	use Drupal\Core\StreamWrapper\PublicStream;

	class BocExtractorController extends ControllerBase {

		public function DisplayBocExtractorSettingsForm() {
			$output = array();
			$form_class = '\Drupal\bocExtractor\Form\BocExtractorSettingsForm';
			$build['form'] = \Drupal::formBuilder()->getForm($form_class);
			$build['#title'] = t('Boc Extractor Settings');
			$build['#markup'] = $this->BOCsList();

			return $build;
		}

		/* fonctions */
		public function get_boc() {

			ini_set('user_agent', 'NameOfAgent (http://www.brvm.org)');

			$Options = $this->getOptions();

			//var_dump($Options);

			$address_bocs = $Options['lien'];

			$btn_class = $Options['classe'];

			//$folder = \Drupal::service('site.path') . '/files/bocs';
			//$folder = realpath(PublicStream::basePath()). "/bocs/";
			$folder = DRUPAL_ROOT . "/" . PublicStream::basePath() . "/bocs/";

			# Create a DOM parser object
			$dom = new \DOMDocument();

			if($this->robots_allowed($address_bocs, "NameOfAgent")) {

				# Parse the HTML from address.
				# The @ before the method call suppresses any warnings that
				@$dom->loadHTMLFile($address_bocs);

				$xpath = new \DOMXPath($dom);
				
				$nodes = $xpath->query("//a[contains(@class, '" . $btn_class . "')]/@href");

				$nodes_number = $xpath->evaluate("count(//a[contains(@class, $btn_class)]/@href)");

				//var_dump($nodes_number, $nodes->length);
				
				if ($this->urlExist($address_bocs) === true && $nodes->length > 0) {

					foreach ($nodes as $key => $node) {
						# code...
						# var_dump($node->nodeValue);

						$filelink = $node->nodeValue;

						if (!file_exists($folder)) {

							file_prepare_directory($folder, FILE_CREATE_DIRECTORY);

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


		public function getOptions() {
		
			$url = \Drupal::config('bocExtractor.settings')->get('BocExtractor_url');
			$class = \Drupal::config('bocExtractor.settings')->get('BocExtractor_class');

			// //verification du lien
			if ( isset( $url ) && ( $url <> '' ) ) {
				$address_bocs = $url ;
			} else {
				$address_bocs = "http://www.brvm.org/fr/bulletins-officiels-de-la-cot";
			}

			//vérification de la classe des liens de téléchargement des BOCs
			if ( isset( $class ) && ( $class <> '' ) ) {
				$btn_class = $class ;
			} else {
				$btn_class = "btn-download";
			}

			return array('lien' => $address_bocs, 'classe' => $btn_class );
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

		/*fonction indépendante qui fait un traitement important: Robots allowed*/
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


		/*Erreur à afficher;  */
	  	public function  error_notice_pages_ressources_not_good() {
	  		ob_start();

        	$message = "Il semble que le site de la BRVM ait été mis à jour. Revérifiez dans vos paramètres d'avoir bien précisé la nouvelle url présentant ou listant vos ressources, ainsi que la classe css des ressources à récupérer." ;
        	
        	\Drupal::logger('bocExtractor')->error(t($message));

        	drupal_set_message(t($message), 'error', TRUE);

		}

		public function BOCsList() {
			
			?>

			<div class="wrap">

				<div class="list-bocs">

					<h1>List of files / Liste des fichiers:</h1>

					<ul>

					<?php

						if(!isset($_GET['start'])) {  
							$start = 0;  
						} else {  
							$start = $_GET['start'];  
						}  

						$exclude_files = array("");  
						$ifiles = Array();  
						$handle = opendir(DRUPAL_ROOT . "/" . PublicStream::basePath() . "/bocs/");  
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

	}
