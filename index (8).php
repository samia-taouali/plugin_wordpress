<?php
/*
Plugin Name: Contact Form
Description: Plugin to add a msg contact form to dash my site
Version: 1.0
*/

if (!defined('ABSPATH')) {
  exit;
}

function contact_form_create_table()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'contact_form';
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        first_name varchar(50) NOT NULL,
        last_name varchar(50) NOT NULL,
        email varchar(50) NOT NULL,
        subject varchar(250) NOT NULL,
        message varchar(350) NOT NULL,
        date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
register_activation_hook(__FILE__, 'contact_form_create_table');





function contact_form_delete_table()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'contact_form';
  $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_deactivation_hook(__FILE__, 'contact_form_delete_table');



function shortcode_contact_form() {
  ob_start();
  ?>
  <!-- Code du formulaire -->
  <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
      <label for="Fname">First name:</label>
      <input type="text" name="Fname" required>

      <label for="Lname">Last name:</label>
      <input type="text" name="Lname" required>

      <label for="email">Email:</label>
      <input type="email" name="email" required>

      <label for="subject">Subject:</label>
      <input type="text" name="subject" required>

      <label for="message">Message:</label>
      <textarea name="message" rows="5" required></textarea>

      <?php wp_nonce_field( 'submit_contact_form', '_wpnonce' ); ?>
      <input type="hidden" name="action" value="submit_contact_form">
      <input type="submit" name="submit_contact_form" value="Envoyer">
  </form>
  <?php
  // Récupération du contenu du tampon de sortie
  $content = ob_get_contents();
  // Nettoyage du tampon de sortie
  ob_end_clean();
  // Retourne le contenu du shortcode
  return $content;
}
add_shortcode( 'contact_form', 'shortcode_contact_form' );


function cf_add_menu_page()
{
  add_menu_page('contact-form', 'Messages', 'manage_options', 'cf_responses_page', 'cf_render_responses_page', 'dashicons-email-alt', 1);
}
add_action('admin_menu', 'cf_add_menu_page');

function insert_form() {
  if ( isset( $_POST['submit_contact_form'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'submit_contact_form' ) ) {
      $f_name = sanitize_text_field( $_POST['Fname'] );
      $l_name = sanitize_text_field( $_POST['Lname'] );
      $email = sanitize_email( $_POST['email'] );
      $subject = sanitize_text_field( $_POST['subject'] );
      $msg = wp_kses_post( $_POST['message'] );

      global $wpdb;
      $table_name = $wpdb->prefix . 'contact_form';
      $wpdb->insert(
          $table_name,
          array(
              'first_name' => $f_name,
              'last_name'  => $l_name,
              'email'      => $email,
              'subject'    => $subject,
              'message'    => $msg,
          ),
          array( '%s', '%s', '%s', '%s', '%s' )
      );
      wp_redirect( home_url( '/contact/' ) );
      exit;
  } else {
      wp_die( 'Security check failed' );
  }
}
add_action( 'admin_post_submit_contact_form', 'insert_form' );
add_action( 'admin_post_nopriv_submit_contact_form', 'insert_form' );

function cf_render_responses_page()
{
  if (!current_user_can('manage_options')) {
    return;
  }

  global $wpdb;
  $table = $wpdb->prefix . 'contact_form';
  $result_table = $wpdb->get_results("SELECT * FROM $table");

  echo '<div class="wrap bg-dark">';
  echo '<h1>' . esc_html__('Contact Form Responses', 'contact-form') . '</h1>';
  echo '<table class="wp-list-table widefat fixed striped">';
  echo '<thead>';
  echo '<tr>';
  echo '<th style="width: 2rem;">' . esc_html__('ID', 'contact-form') . '</th>';
  echo '<th>' . esc_html__('First name', 'contact-form') . '</th>';
  echo '<th>' . esc_html__('Last name', 'contact-form') . '</th>';
  echo '<th>' . esc_html__('Email', 'contact-form') . '</th>';
  echo '<th>' . esc_html__('Subject', 'contact-form') . '</th>';
  echo '<th>' . esc_html__('Message' , 'contact-form') . '</th>';
  echo '<th>' . esc_html__('Date', 'contact-form') . '</th>';
  echo '</tr>';
  echo '</thead>';
  echo '<tbody>';
  foreach ($result_table as $row) {
    echo '<tr>';
    echo '<td>' . $row->id . '</td>';
    echo '<td>' . $row->first_name . '</td>';
    echo '<td>' . $row->last_name . '</td>';
    echo '<td>' . $row->email . '</td>';
    echo '<td>' . $row->subject . '</td>';
    echo '<td>' . $row->message . '</td>';
    echo '<td>' . $row->date . '</td>';
    echo '</tr>';
  }
  echo '</tbody>';
  echo '</table>';
  echo '</div>';
}

?>






