<?php
/*
Plugin Name: WP create user area
Plugin URI: http://kuaza.com/wordpress-eklentileri/wordpress-profil-icin-yeni-alan-olusturma-eklentisi
Description: Wordpress for user create new profile area (label) and widget..
Author: kuaza
Version: 1.0
Author URI: http://kuaza.com
*/

// gelistirici icindir: hatalari gormek icin (varsa) :)
// error_reporting(E_ALL); ini_set("display_errors", 1);

if ( ! defined( 'ABSPATH' ) ) exit; 

define( 'KA_AREA_VER', '1.0' );
define( 'KA_AREA_URI', plugin_dir_url( __FILE__ ) );
define( 'KA_AREA_DIR', plugin_dir_path( __FILE__ ) );
define( 'KA_AREA_PLUGIN', __FILE__ );
define( 'KA_AREA_DIRNAME', dirname( plugin_basename( __FILE__ ) ) );

add_action( 'plugins_loaded', 'ka_uarea_textdomain' );
function ka_uarea_textdomain() {
	load_plugin_textdomain("ka_area", false, KA_AREA_DIRNAME.'/languages/');
}

// soz konusu kullanici alanini gostermeye yarar.
// temaya ekleyeceksiniz ornek bu sekilde ekleyebilirsiniz: 
/* <?php echo reklam_alanlari_array("5","diger reklam",false); ?> */
// 5 : kullanici alan numarasi
// diger reklam : yazi yada yazar sayfasi degilse burda yazdiginiz kodlar cikar, reklam kodu koyabilirsiniz yada bos birakin.
// false : sadece yazi sayfasinda gorunsun istiyorsaniz (belirttiginiz kullanici alan numarasinin) true yapin, aksi durumda false kalsin yada bos birakin.
function reklam_alanlari_array($reklam_alani="1",$diger_reklam = "",$sadece_single = false){

	// yazi okuma (single) sayfasindaysa
    if (is_singular() || is_single() || is_author()){
	
	// bilesen kismindan secilen reklam alanin icerik bilgilerini aliriz
	$alan_icerik = get_the_author_meta('ka_alan_'.$reklam_alani);
	
	// ve motor :D
	// eger sadece tekil sayfada gosterilsin secilirse, yazar sayfasinda gostermez ancak tekil sayfasinda gosterir.
	return ($sadece_single && is_author() ? $diger_reklam : $alan_icerik);

	}
	
	// eger soz konusu tekil sayfalar yada yazar sayfalari degilse diger belirlenen reklam cikar :)
	return $diger_reklam;
}

/*
* Profil kismina reklam alanlari icin bolum ekleme
* yararlandigim kaynak : http://www.paulund.co.uk/add-custom-user-profile-fields
*/

	// guncel kullanici bilgilerini aliriz, kullanici alanini sadece adminin yonetebilmesi icin kural koymada kullanacagiz.
		global $current_user;
			//Fix: http://david-coombes.com/wordpress-get-current-user-before-plugins-loaded/
			if(!function_exists('wp_get_current_user'))
				require_once(ABSPATH . "wp-includes/pluggable.php"); 
				
			wp_cookie_constants();
			$current_user = $user = wp_get_current_user();
		
			$user_roles = $current_user->roles;

			$kullanici_level = array_shift($user_roles);
			$kullanici_level = (empty($kullanici_level) ? "ziyaretci" : $kullanici_level);
	// Bitti: guncel kullanici bilgilerini aliriz, kullanici alanini sadece adminin yonetebilmesi icin kural koymada kullanacagiz.

	$kac_tane_alan_gorunecek = get_option("ka_alan_sayisi");
				$kac_tane_alan_gorunecek = (!empty($kac_tane_alan_gorunecek) ? $kac_tane_alan_gorunecek : "5");
					
	// Giris yapilmis ise profil ayar kismini acariz.
	if($kullanici_level != "ziyaretci"){	
		add_action( 'show_user_profile', 'kuaza_kullanici_alanlari' );
		add_action( 'edit_user_profile', 'kuaza_kullanici_alanlari' );
		

		function kuaza_kullanici_alanlari( $user )
		{
		
			global $kac_tane_alan_gorunecek,$kullanici_level;
			
			?>
				<h3><?php echo __("Extra fields","ka_area"); ?></h3>

				<table class="form-table">
				<?php
					$baslik_bilgileri = array();
					$baslik_bilgileri = get_option("ka_alan_basliklar");
					
					$admin_durumu = array();
					$admin_durumu = get_option("ka_admin_durumu");
					
				 for($i="1";$i<=$kac_tane_alan_gorunecek;$i++) {

					$alan_kime_acik = (!empty($admin_durumu[$i]) && $admin_durumu[$i] == "2" ? "2" : "1");
					
					if($alan_kime_acik == "1" || $kullanici_level == "administrator")
					{
					?>

						<tr>
							<th><label for="<?php echo 'ka_alan_'.$i; ?>"><?php echo sprintf(__("%s","ka_area"),$baslik_bilgileri[$i]); ?></label></th>
							<td><textarea name="<?php echo 'ka_alan_'.$i; ?>" class="regular-text" /><?php echo esc_attr(get_the_author_meta( 'ka_alan_'.$i, $user->ID )); ?></textarea></td>
							<td><?php echo sprintf(__("Area: %s","ka_area"),$i); ?></td>
						</tr>

					<?php
					}
				}
				?>
				
				</table>
			<?php
		}
		add_action( 'personal_options_update', 'kuaza_alanlari_kaydet' );
		add_action( 'edit_user_profile_update', 'kuaza_alanlari_kaydet' );

		function kuaza_alanlari_kaydet( $user_id )
		{
		global $kac_tane_alan_gorunecek,$kullanici_level;
					$admin_durumu = array();
					$admin_durumu = get_option("ka_admin_durumu");
					
					$phpjs_durum = array();
					$phpjs_durum = get_option("ka_phpjs_durum");
					
			for($i="1";$i<=$kac_tane_alan_gorunecek;$i++) {

					if($admin_durumu[$i] == "1" || $kullanici_level == "administrator")
					{
						// duzenlemeyi yapan admin ise ellesmeyiz, ona hersey serbest :)
						if($kullanici_level == "administrator"){
						update_user_meta( $user_id,'ka_alan_'.$i,  $_POST['ka_alan_'.$i] );
						}else{
						
							// normal kullanicilara html php js kapali ise gelen istekleri temizleriz..
							if($phpjs_durum[$i] == "2"){
							update_user_meta( $user_id,'ka_alan_'.$i, sanitize_text_field( $_POST['ka_alan_'.$i] ) );
							}else{
							update_user_meta( $user_id,'ka_alan_'.$i,  $_POST['ka_alan_'.$i] );
							}
						}
					
					}
			}
		}
	}
/*
* Bitti: Profil kismina kullanici alanlari icin bolum ekleme
*/


/*
* Admin sayfa yapim bolumu - bu kisimda eklenti ile ilgili bir kac ayar soz konusu :)
*/
// sadece admin sayfasinda bu kisimi calistiririzki bos yere siteyi kasmasin :)
if(is_admin()){
	function ka_admin_sayfa_head(){
	return true;
	}

	function ka_admin_sayfasi() {
		$ka_sayfa = add_users_page('WP User Area', 'WP User Area', 'manage_options', 'ka_area_admin', 'ka_admin_ayar_sayfasi');
	}

	add_action('admin_menu', 'ka_admin_sayfasi');
}

	function ka_admin_ayar_sayfasi(){

			global $kac_tane_alan_gorunecek;
			
				$baslik_durum = get_option("ka_alan_basliklar");
					$phpjs_durum = get_option("ka_phpjs_durum");
						$admin_durumu = get_option("ka_admin_durumu");
		if(isset($_POST) && !empty($_POST)){		
			if(isset($kac_tane_alan_gorunecek)){
				update_option("ka_alan_sayisi",intval($_POST["ka_alan_sayisi"]));
				}else{
					add_option("ka_alan_sayisi",intval($_POST["ka_alan_sayisi"]));
						}
			
			// yeni alan sayisini listeleme icin guncelleriz.
			$kac_tane_alan_gorunecek = intval($_POST["ka_alan_sayisi"]);
			$baslik_topla = array();
			$phpjs_durum_topla = array();
			$admin_durum_topla = array();
			for($i="1";$i<=$kac_tane_alan_gorunecek;$i++) {
				
			$baslik_topla[$i] = (isset($_POST["ka_alan_b_".$i]) ? $_POST["ka_alan_b_".$i] : "");
			$phpjs_durum_topla[$i] = (isset($_POST["ka_alan_pj_".$i]) ? "2" : "1");
			$admin_durum_topla[$i] = (isset($_POST["ka_alan_o_".$i]) ? "2" : "1");
			}
			
			// kod kismina guvenlik uygulariz, adminin sectiklerine tabiki :)
				if(isset($admin_durumu)){
					update_option("ka_admin_durumu",$admin_durum_topla);
					}else{
						add_option("ka_admin_durumu",$admin_durum_topla);
							}	
							
			$admin_durumu = $admin_durum_topla;

			// baslik kisimlarini gunceller yada ekleriz.
				if(isset($baslik_durum)){
					update_option("ka_alan_basliklar",$baslik_topla);
					}else{
						add_option("ka_alan_basliklar",$baslik_topla);
							}				
			$baslik_durum = $baslik_topla;
			
			// kod kismina guvenlik uygulariz, adminin sectiklerine tabiki :)
				if(isset($phpjs_durum)){
					update_option("ka_phpjs_durum",$phpjs_durum_topla);
					}else{
						add_option("ka_phpjs_durum",$phpjs_durum_topla);
							}				
			$phpjs_durum = $phpjs_durum_topla;
		}		

	$kac_tane_alan_gorunecek = (!empty($kac_tane_alan_gorunecek) ? $kac_tane_alan_gorunecek : "0");

	?>

<form method="POST" action="">
<table class="form-table">
<tr valign="top">
	<th><label for="ka_uarea"><?php echo __("How many fields you want to create?","ka_area"); ?></label></th>
	<td><input class="ka_alan_sayisi" id="ka_alan_sayisi" name="ka_alan_sayisi" value="<?php echo $kac_tane_alan_gorunecek; ?>"></td>
</tr>
<?php if($kac_tane_alan_gorunecek > "0"){ ?>
	<?php for($i="1";$i<=$kac_tane_alan_gorunecek;$i++) { ?><tr valign="top">
	<th scope="row"><label for="ka_uarea"><?php echo $i; ?> - <?php echo "ka_alan_".$i; ?></label></th>
	<td>
	
	<input <?php if($admin_durumu[$i] == "2"){ ?> checked<?php } ?> type="checkbox" id="<?php echo "ka_alan_o_".$i; ?>" name="<?php echo "ka_alan_o_".$i; ?>"><?php echo __("Only admin edit (show)","ka_area"); ?><br />
	
	<input <?php if($phpjs_durum[$i] == "2"){ ?> checked<?php } ?> type="checkbox" id="<?php echo "ka_alan_pj_".$i; ?>" name="<?php echo "ka_alan_pj_".$i; ?>"><?php echo __("Clean HTML and JavaScript code","ka_area"); ?>
	<hr />
	</td>
	<td>	<?php echo __("Title for area","ka_area"); ?> <br /><input class="ka_alan_sayisi" id="<?php echo "ka_alan_b_".$i; ?>" name="<?php echo "ka_alan_b_".$i; ?>" value="<?php echo $baslik_durum[$i]; ?>">
	</td>	
</tr><?php } ?>
<?php } ?>

<tr valign="top">
	<th scope="row"></th>
	<td><button type="submit"><?php echo __("Save","ka_area"); ?></button></td>
	<td></td>
</tr>

<tr valign="top">
	<th scope="row"><?php echo __("Manuel add themes","ka_area"); ?></th>
<td><textarea style="width:100%;height:150px;">
echo reklam_alanlari_array("5","example ads or code or text",false);
	
// soz konusu kullanici alanini gostermeye yarar.
// temaya ekleyeceksiniz ornek bu sekilde ekleyebilirsiniz: 
/* <?php echo reklam_alanlari_array("5","diger reklam",false); ?> */
// 5 : kullanici alan numarasi
// diger reklam : yazi yada yazar sayfasi degilse burda yazdiginiz kodlar cikar, reklam kodu koyabilirsiniz yada bos birakin.
// false : sadece yazi sayfasinda gorunsun istiyorsaniz (belirttiginiz kullanici alan numarasinin) true yapin, aksi durumda false kalsin yada bos birakin.
</textarea></td>
	<td></td>
</tr>
</table>	
</form>	
	<?php
	}

/*
* Bitti: Admin sayfa yapim bolumu - bu kisimda eklenti ile ilgili bir kac ayar soz konusu :)
*/


/*
* Bilesen olusturalim, kullanici hangi bolume hangi kullanici alanini gostermek istiyorsa secsin :)
*/

// Creating the widget 
class ka_uarea extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
'ka_area_user', 

// Widget name will appear in UI
__('Kuaza User area', 'ka_area'), 

// Widget description
array( 'description' => __( 'Show kuaza new user area..', 'ka_area' ), ) 
);
}

// Creating widget front-end
// This is where the action happens
public function widget( $args, $instance ) {
$title = apply_filters( 'widget_title', $instance['title'] );

// before and after widget arguments are defined by themes
echo $args['before_widget'];
if ( ! empty( $title ) )
echo $args['before_title'] . $title . $args['after_title'];

// This is where you run the code and display the output
//echo __( 'Hello, World!', 'ka_area' );

$ka_alan = !empty($instance['ka_alan']) ? $instance['ka_alan'] : "0";
$diger_reklam = !empty($instance['diger_reklam']) ? $instance['diger_reklam'] : false;

$sadece_single = (!empty($instance['sadece_single']) && $instance['sadece_single'] == "on" ? true : false);

echo reklam_alanlari_array($ka_alan,$diger_reklam,$sadece_single);

echo $args['after_widget'];
}
		
// Widget Backend 
public function form( $instance ) {
global $kac_tane_alan_gorunecek;
$baslik_nedir = get_option("ka_alan_basliklar");
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'New title', 'ka_area' );
}

$diger_reklam = (isset($instance[ 'diger_reklam' ]) ? $instance[ 'diger_reklam' ] : "");
$ka_alan = (isset($instance[ 'ka_alan' ]) ? $instance[ 'ka_alan' ] : "");
$sadece_single = (isset($instance[ 'sadece_single' ]) ? $instance[ 'sadece_single' ] : "");
// Widget admin form

?>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:','ka_area' ); ?></label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>

<p>
<label for="<?php echo $this->get_field_id( 'ka_alan' ); ?>"><?php echo __( 'Select area:','ka_area' ); ?></label> 

<select id="<?php echo $this->get_field_id( 'ka_alan' ); ?>" name="<?php echo $this->get_field_name( 'ka_alan' ); ?>">
	<?php for($i="1";$i<=$kac_tane_alan_gorunecek;$i++) { ?>
	<option value="<?php echo $i; ?>" <?php if($ka_alan == $i){ ?> selected<?php } ?>><?php echo $baslik_nedir[$i]; ?></option>
	<?php } ?>
</select>
</p>

<p>
<label for="<?php echo $this->get_field_id( 'diger_reklam' ); ?>"><?php _e( 'The ad will be shown outside of the single or author page.','ka_area' ); ?></label> 
<textarea class="widefat" id="<?php echo $this->get_field_id( 'diger_reklam' ); ?>" name="<?php echo $this->get_field_name( 'diger_reklam' ); ?>"><?php echo esc_attr( $diger_reklam ); ?></textarea>
</p>

<p>
<label for="<?php echo $this->get_field_id( 'sadece_single' ); ?>"><?php _e( 'Only single (post) page','ka_area' ); ?></label> 
<input type="checkbox" <?php if($sadece_single){ ?>checked <?php } ?>class="widefat" id="<?php echo $this->get_field_id( 'sadece_single' ); ?>" name="<?php echo $this->get_field_name( 'sadece_single' ); ?>">
</p>
<?php 
}
	
// Updating widget replacing old instances with new
public function update( $new_instance, $old_instance ) {
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
$instance['ka_alan'] = ( ! empty( $new_instance['ka_alan'] ) ) ? strip_tags( $new_instance['ka_alan'] ) : '';
$instance['diger_reklam'] = ( ! empty( $new_instance['diger_reklam'] ) ) ? $new_instance['diger_reklam'] : '';
$instance['sadece_single'] = ( ! empty( $new_instance['sadece_single'] ) ) ? $new_instance['sadece_single'] : '';
return $instance;
}
} // Class wpb_widget ends here

// Register and load the widget
function kauarea_widget() {
	register_widget( 'ka_uarea' );
}

add_action( 'widgets_init', 'kauarea_widget' );
/*
* bitti: Bilesen olusturalim, kullanici hangi bolume hangi kullanici alanini gostermek istiyorsa secsin :)
*/