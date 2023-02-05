<?php

class SM_Donation {

    function __construct() {
        $this->smAddActions();
    }

    function smAddActions() {

        add_shortcode('sm_receive_donation', array($this, 'receive_donation'));
        add_action('template_redirect', array($this, 'add_donation_check_login'));
        //this action is executed if user is logged in
        add_action("wp_ajax_logged_in_check", array($this, 'logged_in_check'));
        //this action is executed if user is not logged in
        add_action("wp_ajax_nopriv_logged_in_check", array($this, 'logged_in_check'));
    }

    public function add_donation_check_login() {
        global $strmsg;

        if ((isset($_POST['donate']) && $_POST['donate'] == "DONATE") || (isset($_SESSION['sm_amount_sess']) && $_SESSION['sm_amount_sess'] >0) ) {
            if (!is_user_logged_in()) {
                $sm_not_logged_in_donation = get_option('sm_not_logged_in_donation', '0');
                ?>
                <div id="smModal" class="SmModal ">
                    <div class="SmModal-content">
                        <span title="Close" onclick="smCheckLoggedIn('<?php echo admin_url('admin-ajax.php?action=logged_in_check'); ?>')"  class="smClose" >×</span>
                        <?php echo do_shortcode(stripslashes($sm_not_logged_in_donation)); ?>
                        <div id="not_logged_msg" style="display:none; " ></div>
                    </div>
                </div>

                <?php
                
                   if(isset($_POST["donation"])) {
                      if(session_id() == '' && !headers_sent() )
                        session_start();
                        $_SESSION['sm_amount_sess'] = $_POST["donation"];
                        $_SESSION['sm_donation_title_sess'] = $_POST['donation_title'];
                        $_SESSION['sm_currency_sess'] = $_POST['currency'];
                        if (isset($_POST['recursive']))
                           $_SESSION['sm_recursive_sess'] = $_POST['recursive'];
                        if (isset($_POST['charity_receipt']))
                           $_SESSION['sm_charity_receipt_sess'] = $_POST['charity_receipt'];
        
                    }
                   
                return 1;
            }

            $current_user = wp_get_current_user();

            $url = SM_URL . "api/donation";
            $domain = get_site_url();
            $key = get_option('sm_domain_key', '');
            $user_email = $current_user->user_email;
            $recursive = 0;
            $charity_receipt = 0;
            if (isset($_POST["donation"])) {
                $amount = $_POST["donation"];
                if (isset($_POST['recursive']))
                    $recursive = $_POST['recursive'];
                if (isset($_POST['charity_receipt']))
                    $charity_receipt = $_POST['charity_receipt'];
                $donation_title = $_POST['donation_title'];
                $currency = $_POST['currency'];
            }
            else if(isset($_SESSION['sm_amount_sess'])){
                $amount = $_SESSION["sm_amount_sess"];
                if (isset($_SESSION['sm_recursive_sess']))
                    $recursive = $_SESSION['sm_recursive_sess'];
                if (isset($_SESSION['sm_charity_receipt_sess']))
                    $charity_receipt = $_SESSION['sm_charity_receipt_sess'];
                $donation_title = $_SESSION['sm_donation_title_sess'];
                $currency = $_SESSION['sm_currency_sess'];
                
            }
           

            if (empty($amount) && is_numeric($amount)) {

                $strmsg .= '<div>';
                $strmsg .= '<strong>ERROR</strong>:';
                $strmsg .= $error . '<br/>';
                $strmsg .= '</div>';
            } else {


                $curl = curl_init();
                $curlopt_array = array(
                    CURLOPT_URL => $url,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => array(
                        'domain' => $domain,
                        'key' => $key,
                        'donation' => $amount,
                        'currency' => $currency,
                        'email' => $user_email,
                        'charity_receipt' => $charity_receipt,
                        'recursive' => $recursive,
                        'auto_add' => 1,
                        'title' => $donation_title)
                );
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt_array($curl, $curlopt_array);
               
                $content = curl_exec($curl);
            //    echo $domain . " ".$url." ".$key;
          // print_r($content);
                $err = curl_error($curl);
                curl_close($curl);

                if (!$err) {
                    unset($_SESSION["sm_amount_sess"]);
                    unset($_SESSION["sm_recursive_sess"]);
                    unset($_SESSION["sm_charity_receipt_sess"]);
                    unset($_SESSION["sm_donation_title_sess"]);
                    unset($_SESSION["sm_currency_sess"]);
                    
                    $content = json_decode($content);

                    if (!$content->error) {
                        $msg = get_option('sm_donation_success_msg', '');
                    } else if ($content->msg == "User does not exist in db" ) {
                        global $page_status;
                        $page_status = "not_registered";
                        add_action('wp_footer', $this->sm_add_modal_window_not_registered_donation(), 1000);
                        return 1;
                    } else if ($content->msg == "This user account cannot accept donations") {
                        $msg = $content->msg;
                    } else {
                        $msg = "Tech Error.";
                    }
                } else {
                    $msg = "Tech Error.";
                }
            }
        }
        if (isset($msg) && $msg != "") {
            if (!$content->error) {
                $_SESSION['sm_donation_success'] = $msg;
                $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

                $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                wp_redirect($url, 303);
                exit;
            }
            ?>


            <div id="smModal" class="SmModal ">
                <div class="SmModal-content">
                    <span class="smClose" id="smClose_warning_msg">×</span>
                    <?php echo do_shortcode(stripslashes($msg)); ?>
                </div>
            </div>

            <?php
        }
    }

    public function receive_donation($atts) {
        if (isset($_SESSION['sm_donation_success'])) {

            $success_msg = $_SESSION['sm_donation_success'];
            unset($_SESSION['sm_donation_success']);
            wp_register_style('smModalCss', plugins_url('css/sm_modal_closable.css', __FILE__));
            wp_enqueue_style('smModalCss');
            ?>
            <div id="smModal" class="SmModal ">
                <div class="SmModal-content">
                    <span class="smClose" id="smClose_warning_msg">×</span>
                    <?php echo do_shortcode(stripslashes($success_msg)); ?>
                </div>
            </div>

            <?php
        }
        $recursive = $charity_receipt = $donation_title = $donation = "";
        if (isset($atts['title']))
            $donation_title = $atts['title'];
        if (isset($atts['recursive']))
            $recursive = $atts['recursive'];
        if (isset($atts['charity_receipt']))
            $charity_receipt = $atts['charity_receipt'];
        if (isset($_POST['donation']))
            $donation = $_POST['donation'];
        else if (isset($atts['donation']))
            $donation = $atts['donation'];
        $currency = "";
        if (isset($_SESSION['sm_usercurrency'])) {
            $currency = $_SESSION['sm_usercurrency'];
        } else
        if (is_user_logged_in()) {
            $current_user = get_current_user_id();
            $cu_email = wp_get_current_user()->data->user_email;
            $key = get_option('sm_domain_key', '');
            $curl = curl_init();
            $curlopt_array = array(
                CURLOPT_URL => SM_URL . 'api/visit/login',
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => array(
                    'email' => $cu_email,
                    'auto_add' => 0,
                    'key' => $key)
            );
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt_array($curl, $curlopt_array);

            $content = curl_exec($curl);
           // print_r($content);
            $err = curl_error($curl);
            curl_close($curl);
            if (!$err) {
                $content = json_decode($content);
                if (isset($content->success) && $content->success) {
                    if (session_id() == '' && !headers_sent()) {
                        session_start();
                    }
                    $_SESSION['cl_userid'] = $current_user;
                    $_SESSION['sm_userid'] = $content->user_id;
                    $currency = $_SESSION['sm_usercurrency'] = $content->currency;
                    $_SESSION['sm_token'] = $content->sm_token;
                }
            }
        }
        if (empty($currency)) {
            $sm_currency = get_option('sm_currency', '');
            if (isset($atts['currency']))
                $currency = $atts['currency'];
            else if (!empty($sm_currency))
                $currency = $sm_currency;
            else if (empty($currency))
                $currency = "CAD";
        }
        $symbols = array("GBP" => "£", "EUR" => "€", "AUD" => "$", "CAD" => "$", "USD" => "$");
        $str = '
<form action="" method="post" onsubmit="return smValidateNumForm(this)" >
   <input type="hidden" name="currency" value="' . $currency . '" >
    <input type="hidden" name="donation_title"  value="' . $donation_title . '">        
    <div>
     ' . $currency . ' ' . $symbols[$currency]." " . '<input type="text" name="donation" id="donation" value="' . $donation . '" maxlength="6" minlength="1" style="height: 30px;width: 64px;padding: 2px 4px;font-size: 15px;margin-bottom:6px;" /> <input style="width: 100px;height: 30px;margin: auto;padding: 0;" type="submit" name="donate" value="DONATE" />
    </div>
    ';

        if ($recursive == 1)
            $str .= '<div><label for="recursive" style="margin-bottom:0"> <input type="checkbox" name="recursive" value="1" > Monthly</label></div>';

        if ($charity_receipt == 1)
            $str .= '<div><label for="charityinvoice"> <input type="checkbox" name="charity_receipt" value="1" > Request donation receipt</label></div>';

        $str .= '</form>';

        return $str;
    }

    public function logged_in_check() {

        //check if it is a AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            //check if user is logged in or nor
            if (is_user_logged_in()) {
                echo "TRUE";
            } else {
                echo "FALSE";
            }

            die();
        }
        //seems like a anchor link or form request
        else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
            die();
        }
    }

    public function sm_add_modal_window_not_registered_donation() {

        $sm_not_registered_msg = get_option('sm_not_registered_msg', '');
        ?>
        <div id="smModal" class="SmModal ">
            <div class="SmModal-content">

                <?php echo do_shortcode(stripslashes($sm_not_registered_msg)); ?>

            </div>
        </div>

        <?php
    }

}

new SM_Donation();

//Task 'KefeU@MedRecAps.Com: Folder:Inbox Check for new mail.' reported error (0x800CCC0E) : 'Outlook cannot download folder Inbox from the IMAP email server for account KefeU@MedRecAps.Com. Error: Cannot connect to the server. If you continue to receive this message, contact your server administrator or Internet service provider (ISP).'