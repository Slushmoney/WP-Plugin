<?php

class SM_Socket {

    private $page_link;
    private $is_payable_link;

    function __construct() {
        $this->smAddActions();
    }

    function smAddActions() {

        add_action('wp_enqueue_scripts', function() {
            wp_register_style('sm_modal_css', plugins_url('css/sm_modal.css', __FILE__));
            wp_enqueue_style('sm_modal_css');
            wp_enqueue_script('script', plugins_url('js/sm_general.js', __FILE__), array(), 1.1, true);
        });

        add_action('wp_logout', function() {
            unset($_SESSION['cl_userid'], $_SESSION['sm_userid'], $_SESSION['sm_token'], $_SESSION['sm_usercurrency'], $_SESSION['sm_min_duration']);
        });
        add_action('template_redirect', array($this, 'smSocketLogin'));
    }

    public function smSocketLogin() {

        global $wp;
        global $post;
        if (!$post)
            return;
        $this->page_link = home_url(add_query_arg(array(), $wp->request));
        $this->is_payable_link = get_post_meta($post->ID, 'is_payable', true);

        if (SM_SYNCHRONIZED == 1) {
            $url = SM_URL . 'api/getPayableLink';
            $curl = curl_init();
            $curlopt_array = array(
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => array(
                    'request' => 'is_payable',
                    'link' =>  $this->page_link)
            );
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt_array($curl, $curlopt_array);

            $content = curl_exec($curl);
             
            $err = curl_error($curl);
            curl_close($curl);
            if (!$err) {
               
                $content = json_decode($content);
                
                $this->is_payable_link = $content->is_payable;
            }
        }
        if ($this->is_payable_link || (isset($_POST['continue_auto_register']) && $_POST['continue_auto_register'] == "1")) {
            if (!is_user_logged_in()) {
                add_filter('body_class', function( $classes ) {
                    return array_merge($classes, array('SmModalBody'));
                });
                add_action('wp_footer', array($this, 'smModalNotLoggedInMsg'), 1000);
            } else {
                add_action('wp_head', array($this, 'smSocketAdd'));
            }
        }
    }

    public function smSocketAdd() {
        global $post;
        $sm_login_model =0;
        if(isset($_SESSION["sm_login_model"]) && $_SESSION["sm_login_model"] == 1){
            $sm_login_model =1;
            unset($_SESSION["sm_login_model"]);
        }
        $key = get_option('sm_domain_key', '');
        $auto_unarchive = 0;
        $auto_add = get_option('sm_auto_add', '0');
        if($auto_add == 1 && $sm_login_model==1){
           $auto_add = 1;
           $auto_unarchive =1;
        } 
        else if( $sm_login_model==1){
           $auto_add = 0;
           $auto_unarchive =1;
        }
       else {
            $auto_add = 0;
       }
        
       
        if (isset($_POST['continue_auto_register']) && $_POST['continue_auto_register'] == "1") {
            $auto_add = 1;      
            $auto_unarchive =1;
        }
        
        if (isset($_POST['continue_unarchive']) && $_POST['continue_unarchive'] == "1") {
            $auto_unarchive = 1;
           
        }


        $current_user = get_current_user_id();
        if ($current_user) {
            $cu_email = wp_get_current_user()->data->user_email;
            // print_r($_SESSION);
            if (empty($_SESSION['sm_token']) || empty($_SESSION['cl_userid']) || !empty($_SESSION['cl_userid']) && $_SESSION['cl_userid'] != $current_user) {

                $curl = curl_init();
                $curlopt_array = array(
                    CURLOPT_URL => SM_URL . 'api/visit/login',
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => array(
                        'email' => $cu_email,
                        'auto_add' => $auto_add,
                        'key' => $key)
                );
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt_array($curl, $curlopt_array);
                $content = curl_exec($curl);
             //   print_r($content);
                
                $err = curl_error($curl);
                curl_close($curl);
                if (!$err) {
                    $content = json_decode($content);
                    if ($content->success) {
                        if (!session_id()) {
                            session_start();
                        }
                        $_SESSION['cl_userid'] = $current_user;
                        $_SESSION['sm_userid'] = $content->user_id;
                        $_SESSION['sm_usercurrency'] = $content->currency;
                        $_SESSION['sm_token'] = $content->sm_token;
                    } else {
                        global $page_status;
                        $page_status = "not_registered";
                        add_filter('body_class', function( $classes ) {
                            return array_merge($classes, array('SmModalBody'));
                        });
                        add_action('wp_footer', array($this, 'smModalNotRegisteredMsg'), 1000);
                        return 1;
                    }
                }
            }
          //  echo 'bbb'.$auto_unarchive;
            $curl = curl_init();
            $curlopt_array = array(
                CURLOPT_URL => SM_URL . 'api/visit',
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => array(
                    'link' => $this->page_link,
                    'key' => $key,
                    'user_id' => $_SESSION['sm_userid'],
                    'auto_unarchive' => $auto_unarchive,
                    'title' => get_the_title()),
            );
                                                    
            //   print_r($curlopt_array);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt_array($curl, $curlopt_array);
            $content = curl_exec($curl);
            
         //   print_r($content);
            $err = curl_error($curl);
            curl_close($curl); //echo $content; //die;
            if (!$err) {
                $content = json_decode($content);
                $_SESSION['sm_min_duration'] = $content->sm_min_duration;
                if(!empty( $content->currency))
                   $_SESSION['sm_usercurrency'] = $content->currency;

                if ($content->payable) {
                    $payable = true;
                    $socket_id = $content->socket_id;
                } else {
                    $payable = false;
                }
              
                if ($content->status == 'phone err') {
                    $sm_redirect_directly_to_phone_auth = get_option('sm_redirect_directly_to_phone_auth', 0);
                    if (($sm_redirect_directly_to_phone_auth != 0 && $sm_login_model==1) || (isset($_POST['continue_auto_register']) && $_POST['continue_auto_register'] == "1") ) {
                        $location = SM_URL . "user/verifyPhone/" . $_SESSION['sm_token']."/".base64_encode($this->page_link);
                        echo("<script>location.href = '" . $location . "'</script>");
                        exit;
                    } else {
                        add_action('wp_footer', array($this, 'smModalVerifyPhoneMsg'), 1000);
                    }
                    return 1;
                } 
                else if ($content->status == 'archived') {
                    global $page_status;
                    $page_status = "archived";
                    add_action('wp_footer', array($this, 'smModalArchiveMsg'), 1000);
                    return 1;
                } 
                else if ($content->status == 'warning') {
                    $sm_block_warning_msg = get_option('sm_block_warning_msg', '');
                    if (!isset($sm_block_warning_msg) || $sm_block_warning_msg == "")
                        $sm_block_warning_msg = 'Please, do the payment or you will be blocked';
                    ?>
                    <div id="smModal" class="SmClosableModal ">
                        <div class="SmClosableModal-content">
                            <span class="smClosableClose" id="smClose_warning_msg">×</span>
                    <?php echo do_shortcode(stripslashes($sm_block_warning_msg)); ?>
                        </div>
                    </div>

                    <?php
                }
                else if ($content->status == 'not registered') {
                    global $page_status;
                    $page_status = "not_registered";
                    add_action('wp_footer', array($this, 'smModalNotRegisteredMsg'), 1000);
                    unset($_SESSION['sm_token']);
                    return 1;
                } 
                else if ($content->status == 'error') {
                    add_action('wp_footer', array($this, 'smModalBlockMsg'), 1000);
                    
                }
            }
        }

        if ($current_user && $payable == true && isset($socket_id) && strlen($socket_id) > 0) {
            
            ?>
            <!-- socket -->
            <script type="text/javascript">

                window.onbeforeunload = function () {
                    var xhr = new XMLHttpRequest();
                    xhr.open('DELETE', '<?php echo SM_URL . 'api/visit'; ?>');
                    xhr.setRequestHeader("Content-Type", "application/json");
                    xhr.onload = function () {
                        console.log(xhr.responseText);
                    }
                    var data = {"socket_id": "<?php echo $socket_id; ?>"};
                    xhr.send(JSON.stringify(data));
 console.log("delete request sent");
                    return undefined;
                }
              setTimeout(function () {
                    window.onbeforeunload = null;
                }, 1000 *<?php echo $_SESSION['sm_min_duration']; ?>)
            </script>

            <?php
        }
    }

    public function smModalBlockMsg() {
        $sm_block_msg = get_option('sm_block_msg', '');
        if (!isset($sm_block_msg) || $sm_block_msg == "")
            $sm_block_msg = 'You could not access this page';
        ?>
        <div id="smModal" class="SmModal ">
            <div class="SmModal-content">
                <p><?php echo do_shortcode(stripslashes($sm_block_msg)); ?> </p>
            </div>
        </div>

        <?php
    }

    public function smModalVerifyPhoneMsg() {

        $sm_verify_phone_msg = get_option('sm_verify_phone_msg', '');
        if (!isset($sm_verify_phone_msg) || $sm_verify_phone_msg == "")
            $sm_verify_phone_msg = 'Your number is not verified, please verify it first';
        ?>
        <div id="smModal" class="SmModal ">
            <a href="#close" title="Close" class="close">X</a>
            <div class="SmModal-content">

                <?php echo do_shortcode(stripslashes($sm_verify_phone_msg)); ?>
            </div>
        </div>
        <?php
    }

    public function smModalArchiveMsg() {

        $sm_in_archive_msg = get_option('sm_in_archive_msg', '');
        ?>
        <div id="smModal" class="SmModal ">
            <div class="SmModal-content">
                <?php echo do_shortcode(stripslashes($sm_in_archive_msg)); ?>
            </div>
        </div>

        <?php
    }

    public function smModalNotRegisteredMsg() {

        $sm_not_registered_msg = get_option('sm_not_registered_msg', '');
        ?>
        <div id="smModal" class="SmModal ">
            <div class="SmModal-content">

                <?php echo do_shortcode(stripslashes($sm_not_registered_msg)); ?>

            </div>
        </div>

        <?php
    }

    public function smModalNotLoggedInMsg() {

        $sm_not_logged_in_msg = get_option('sm_not_logged_in_msg', '');
        if (!isset($sm_not_logged_in_msg) || $sm_not_logged_in_msg == "") {
            auth_redirect();
            exit();
        }
        // Set session here
        $_SESSION['sm_login_model'] = 1;
        $url = admin_url('admin-ajax.php?action=logged_in_check');
        ?>
        <div id="smModal" class="SmModal ">
            <div class="SmModal-content">
                <span title="Close" onclick="fetchdata('<?php echo $url; ?>')"  class="smClose" >×</span>
                <?php echo do_shortcode(stripslashes($sm_not_logged_in_msg)); ?>
                <div id="not_logged_msg" style="display:none; " ></div>
            </div>
        </div>

        <?php
    }

}

new SM_Socket();
?>
