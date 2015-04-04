<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

// redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled (or the session has not started)
  if ($session_started == false) {
    if ( !isset($_GET['cookie_test']) ) {
      $all_get = tep_get_all_get_params();

      tep_redirect(tep_href_link('login.php', $all_get . (empty($all_get) ? '' : '&') . 'cookie_test=1', 'SSL'));
    }

    tep_redirect(tep_href_link('cookie_usage.php'));
  }

// login content module must return $login_customer_id as an integer after successful customer authentication
  $login_customer_id = false;

  $page_content = $oscTemplate->getContent('login');

  if ( is_int($login_customer_id) && ($login_customer_id > 0) ) {
    if (SESSION_RECREATE == 'True') {
      tep_session_recreate();
    }

    $Qcheck = $OSCOM_Db->prepare('select c.customers_firstname, c.customers_default_address_id, ab.entry_country_id, ab.entry_zone_id from :table_customers c left join :table_address_book ab on (c.customers_id = ab.customers_id and c.customers_default_address_id = ab.address_book_id) where c.customers_id = :login_customer_id');
    $Qcheck->bindInt(':login_customer_id', $login_customer_id);
    $Qcheck->execute();

    $_SESSION['customer_id'] = $login_customer_id;
    $_SESSION['customer_default_address_id'] = $customer_info['customers_default_address_id'];
    $_SESSION['customer_first_name'] = $customer_info['customers_firstname'];
    $_SESSION['customer_country_id'] = $customer_info['entry_country_id'];
    $_SESSION['customer_zone_id'] = $customer_info['entry_zone_id'];

    $customer_default_address_id = $Qcheck->value('customers_default_address_id');
    tep_session_register('customer_default_address_id');

    $customer_first_name = $Qcheck->value('customers_firstname');
    tep_session_register('customer_first_name');

    $customer_country_id = $Qcheck->value('entry_country_id');
    tep_session_register('customer_country_id');

    $customer_zone_id = $Qcheck->value('entry_zone_id');
    tep_session_register('customer_zone_id');

    $Qupdate = $OSCOM_Db->prepare('update :table_customers_info set customers_info_date_of_last_logon = now(), customers_info_number_of_logons = customers_info_number_of_logons+1, password_reset_key = null, password_reset_date = null where customers_info_id = :customers_info_id');
    $Qupdate->bindInt(':customers_info_id', $customer_id);
    $Qupdate->execute();

// reset session token
    $_SESSION['sessiontoken'] = md5(tep_rand() . tep_rand() . tep_rand() . tep_rand());

// restore cart contents
    $_SESSION['cart']->restore_contents();

    if (sizeof($_SESSION['navigation']->snapshot) > 0) {
      $origin_href = tep_href_link($_SESSION['navigation']->snapshot['page'], tep_array_to_string($_SESSION['navigation']->snapshot['get'], array(session_name())), $_SESSION['navigation']->snapshot['mode']);
      $_SESSION['navigation']->clear_snapshot();
      tep_redirect($origin_href);
    }

    tep_redirect(tep_href_link('index.php'));
  }

  require(DIR_WS_LANGUAGES . $_SESSION['language'] . '/login.php');

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link('login.php', '', 'SSL'));

  require('includes/template_top.php');
?>

<div class="page-header">
  <h1><?php echo HEADING_TITLE; ?></h1>
</div>

<?php
  if ($messageStack->size('login') > 0) {
    echo $messageStack->output('login');
  }
?>

<div id="loginModules">
  <div class="row">
    <?php echo $page_content; ?>
  </div>
</div>

<?php
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
