<?php

class SM_ShortCodes {

    function __construct() {
        $this->smAddActions();
    }

    function smAddActions() {
        add_shortcode('sm_continue', array($this, 'continue_button'));
        add_shortcode('sm_verify_phone', array($this, 'verify_phone_number'));
        add_shortcode('sm_display_article_rate', array($this, 'sm_display_article_rate'));
    }

    public function sm_display_article_rate($atts) {
        $currency = "";
        $key = get_option('sm_domain_key', '');
        $sm_currency = get_option('sm_currency', '');

        if (isset($atts['currency'])) {
            $currency = $atts['currency'];
        } else if (isset($_SESSION['sm_usercurrency'])) {
            $currency = $_SESSION['sm_usercurrency'];
        } else if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $cu_email = wp_get_current_user()->data->user_email;
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
            //print_r($content);
            $err = curl_error($curl);
            //print_r($err);
            curl_close($curl);
            if (!$err) {
                $content = json_decode($content);
                if ($content->success) {
                    if (!session_id()) {
                        session_start();
                    }
                    $_SESSION['cl_userid'] = $current_user;
                    $_SESSION['sm_userid'] = $content->user_id;
                    $currency = $_SESSION['sm_usercurrency'] = $content->currency;
                    $_SESSION['sm_token'] = $content->sm_token;
                }
            }
        } 
        else if (!empty($sm_currency)) {
             $currency = $sm_currency;
        } 
        if (empty($currency))
        {
            $currency = "CAD";
        }
        
        $curl = curl_init();
        $curlopt_array = array(
            CURLOPT_URL => SM_URL . 'api/visit/articleRate',
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => array(
                'key' => $key,
                'currency' => $currency
            )
        );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt_array($curl, $curlopt_array);

        $content = curl_exec($curl);

        //print_r($content);
        $err = curl_error($curl);
        curl_close($curl);
        if (!$err) {
            $content = json_decode($content);

            if (isset($content->success) && $content->success) {
                return $content->article_rate;
            }
        }
    }

    public function continue_button($atts) {
        global $page_status;
        if (!is_user_logged_in()) {

            $text = "Continue";
            if (isset($atts['text']))
                $text = $atts['text'];

            $msg_text = "not logged in";
            if (isset($atts['msg_text']))
                $msg_text = $atts['msg_text'];

            $url = admin_url('admin-ajax.php?action=logged_in_check');
            return "<button type='button' id='not_logged_in' onclick=\"fetchdata('$url','$msg_text');\" >$text</button>";
        }

        if ($page_status == "not_registered") {

            $text = "Continue with slushmoney";
            if (isset($atts['text']))
                $text = $atts['text'];

            return "<form method='post' action='' ><input type='hidden' name='continue_auto_register' value='1' >  <button type='submit' name='btn_continue_submit' id='btn_continue_submit'   >$text</button></form>";
        }

        if ($page_status == "archived") {

            $text = "Continue - Unarchive";
            if (isset($atts['text']))
                $text = $atts['text'];

            return "<form method='post' action='' ><input type='hidden' name='continue_unarchive' value='1' >  <button type='submit' name='btn_continue_submit' id='btn_continue_submit'  >$text</button></form>";
        }

        return "Page Status" . $page_status;
    }

    public function verify_phone_number($atts) {

        $text = "Click here to add your phone number";
        if (isset($atts['text']))
            $text = $atts['text'];

        global $wp;
        $url = base64_encode(home_url(add_query_arg(array(), $wp->request)));


        return '<a  href="' . SM_URL . "user/verifyPhone/" . $_SESSION['sm_token'] . "/" . $url . '" ><button type="button">' . $text . '</button></a>';
    }

} 

new SM_ShortCodes();

?>