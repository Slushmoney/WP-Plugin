<?php
/*
  Plugin Name: Slushmoney
  Description: Works with slushmoney.net for paywall functions and donations.
  Author: Farhat Aziz
  Version: 1.0
 */

class SM_Setting {

    public function __construct() {

       // add_action('plugins_loaded', function() {
            if (!defined('SM_URL')) {
                define('SM_URL', 'https://demo.slushmoney.net/app/');
                //define('SM_URL', 'http://app.slushmoney.local/');
            }
            if (!defined('SM_SYNCHRONIZED')) {
                define('SM_SYNCHRONIZED', 0);
            }

       // });
        add_action('wp_loaded', function() {
            if (!session_id()) {
                session_start();
            }
        });
        //DEFINE MENUS
        add_action("admin_menu", function () {
            add_menu_page(
                    "Slushmoney Settings", "Slushmoney Demo", "manage_options", "slush_money", array($this, 'smGeneralSettingsContents'), plugins_url('images/logo.png', __FILE__), 6);

            add_submenu_page("slush_money", "General Settings", "General Settings", 'manage_options', "slush_money", array($this, 'smGeneralSettingsContents'));
            add_submenu_page("slush_money", "Messages", "Messages", 'manage_options', "sm_messages", array($this, 'smMessagesSettingsContents'));
            add_submenu_page("slush_money", "Shortcodes", "Shortcodes", 'manage_options', "sm_shortcodes", array($this, 'smShortcodesContents'));
        });



    }

    //THE ABOVE THREE SUB MENUS OPEN FOLLOWING THREE PAGES
    //1- GENERAL SETTING TAB
    public function smGeneralSettingsContents() {

         // ADD STYLING
        wp_register_style('sm_setting', plugins_url('css/settings.css', __FILE__));
        wp_enqueue_style('sm_setting');
        add_action('admin_print_styles', 'sm_setting');
         wp_enqueue_script('sm_general_js',plugins_url('js/sm_general.js', __FILE__),
    array(), false, true);
         add_action('admin_print_scripts', 'sm_general_js');
        if (isset($_POST['updated']) && $_POST['updated'] === 'true') {
            $this->handleGeneralSettingsForm();
        }
        ?>

        <div class="tab">
  <a class="tablinks active" href="?page=slush_money" >General Settings</a>
  <a class="tablinks"  href="?page=sm_messages">Messages</a>
  <a class="tablinks"  href="?page=sm_shortcodes">Shortcodes</a>
</div>

        <div class="tabcontent" >

            <h2>General Settings</h2>
            <form method="POST">
                <input type="hidden" name="updated" value="true" />
                <?php wp_nonce_field('sm_update', 'sm_form'); ?>
                <table class="form1-table">
                    <tbody>

                        <tr>
                            <th><label for="sm_domain_key">Site Key : </label></th>
                            <td><input style="width:280px;" name="sm_domain_key" id="sm_domain_key" type="text" value="<?php echo get_option('sm_domain_key'); ?>" class="regular-text" /></td>
                        </tr>

                        <tr>
                            <th><label for="sm_currency">Default Currency : </label></th>
                            <td><select class="regular-text"  name="sm_currency" id="sm_currency" style="width:100px;"  >
                                    <option <?php if (empty(get_option('sm_currency'))) echo 'selected'; ?> value=""></option>
                                    <option <?php if (get_option('sm_currency') == "CAD") echo 'selected'; ?> value="CAD">CAD$</option>
                                    <option <?php if (get_option('sm_currency') == "USD") echo 'selected'; ?> value="USD">USD$</option>
                                    <option <?php if (get_option('sm_currency') == "EUR") echo 'selected'; ?> value="EUR">EUR€</option>
                                    <option <?php if (get_option('sm_currency') == "GBP") echo 'selected'; ?> value="GBP">GBP£</option>
                                    <option <?php if (get_option('sm_currency') == "AUD") echo 'selected'; ?> value="AUD">AUD$</option>

                                </select>
                            </td>
                        </tr>
                          <tr>
                            <th>&nbsp;</th>
                            <td></td>
                        </tr>

                        <tr>
                            <th>&nbsp;</th>
                            <td><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings"></td>
                        </tr>

<tr>
                            <th>&nbsp;</th>
                            <td></td>
                        </tr>



                    </tbody>
                </table>

            </form>
        </div> <?php
    }

    public function handleGeneralSettingsForm() {
        if (!isset($_POST['sm_form']) || !wp_verify_nonce($_POST['sm_form'], 'sm_update')) {
            ?>
            <div class="error">
                <p>Sorry, the data provided was not correct. Please try again.</p>
            </div> <?php
            exit;
        } else {

            $sm_domain_key = sanitize_text_field($_POST['sm_domain_key']);
            $sm_currency = sanitize_text_field($_POST['sm_currency']);

            update_option('sm_domain_key', $sm_domain_key);
            update_option('sm_currency', $sm_currency);
            ?>
            <div class="updated">
                <p>Site settings have been saved</p>
            </div>
            <?php
        }
    }

    //END OF GENERAL SETTINGS
    //2- MESSAGES SETTING TAB
    public function smMessagesSettingsContents() {
         // ADD STYLING
        wp_register_style('sm_setting', plugins_url('css/settings.css', __FILE__));
        wp_enqueue_style('sm_setting');
        add_action('admin_print_styles', 'sm_setting');
         wp_enqueue_script('sm_general_js',plugins_url('js/sm_general.js', __FILE__),
    array(), false, true);
         add_action('admin_print_scripts', 'sm_general_js');
        if (isset($_POST['updated']) && $_POST['updated'] === 'true') {
            $this->handleMessagesSettingsForm();
        }
        ?>


<div class="tab">
  <a class="tablinks " href="?page=slush_money" >General Settings</a>
  <a class="tablinks active"  href="?page=sm_messages">Messages</a>
  <a class="tablinks"  href="?page=sm_shortcodes">Shortcodes</a>
</div>

        <div class="tabcontent" >
            <h2>Messages Settings</h2>
            <form method="POST">
                <input type="hidden" name="updated" value="true" />
                <?php wp_nonce_field('sm_update', 'sm_form'); ?>
                <table class="form-table">
                    <tbody>

                        <tr>
                            <th><label for="sm_block_warning_msg">Warning Message - Article limit near</label></th>
                            <td>
                                <textarea name="sm_block_warning_msg" id="sm_block_warning_msg" style="width:100%; resize:vertical;min-height: 100px;" /><?php echo stripslashes(esc_html(get_option('sm_block_warning_msg'))); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="sm_block_msg">Message - Article Limit reached</label></th>
                            <td>
                                <textarea name="sm_block_msg" id="sm_block_msg" style="width:100%; resize:vertical;min-height: 100px;" /><?php echo stripslashes(esc_html(get_option('sm_block_msg'))); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th>
                                <label for="sm_not_logged_in_msg">Message - User not Logged in</label></th>
                            <td>
                                <textarea name="sm_not_logged_in_msg" id="sm_not_logged_in_msg" style="width:100%; resize:vertical;min-height: 100px;" /><?php echo stripslashes(esc_html(get_option('sm_not_logged_in_msg'))); ?></textarea>

                                <p class="deiscription" id="sm_not_logged_in_msg-description">Default redirected to login.</p>

                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="sm_auto_add">Auto Add Slushmoney User</label>
                            </th>
                            <td>
                                <input name="sm_auto_add" id="sm_auto_add" type="checkbox" value="1" <?php if (get_option('sm_auto_add') == 1) echo "checked"; ?> class="regular-text" />

                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="sm_redirect_directly_to_phone_auth">Redirect directly to phone auth.</label>
                            </th>
                            <td>


                                <input name="sm_redirect_directly_to_phone_auth" id="sm_redirect_directly_to_phone_auth" type="checkbox" value="1" <?php if (get_option('sm_redirect_directly_to_phone_auth') == 1) echo "checked"; ?> class="regular-text" />

                            </td>
                        </tr>
                        <tr>
                            <th><label for="sm_not_registered_msg">Message - User is not registered at Slushmoney</label></th>
                            <td>
                                <textarea name="sm_not_registered_msg" id="sm_not_registered_msg" style="width:100%; resize:vertical;min-height: 100px;" /><?php
                                if (!empty(get_option('sm_not_registered_msg')))
                                    echo stripslashes(esc_html(get_option('sm_not_registered_msg')));
                                ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="sm_in_archive_msg">Message - Site is innactive at Slushmoney</label></th>
                            <td>
                                <textarea name="sm_in_archive_msg" id="sm_in_archive_msg" style="width:100%; resize:vertical;min-height: 100px;" /><?php
                                if (!empty(get_option('sm_in_archive_msg')))
                                    echo stripslashes(esc_html(get_option('sm_in_archive_msg')));
                                ?></textarea>

                            </td>
                        </tr>

                        <tr>
                            <th><label for="sm_not_logged_in_donation">Donation - User not Logged in </label></th>
                            <td>
                                <textarea name="sm_not_logged_in_donation" id="sm_not_logged_in_donation" style="width:100%; resize:vertical;min-height: 100px;" /><?php if (!empty(get_option('sm_not_logged_in_donation'))) echo stripslashes(esc_html(get_option('sm_not_logged_in_donation')));  ?></textarea>

                            </td>
                        </tr>

                        <tr>
                            <th><label for="sm_verify_phone_msg">Verify Phone</label></th>
                            <td>
                                <textarea name="sm_verify_phone_msg" id="sm_verify_phone_msg" style="width:100%; resize:vertical;min-height: 100px;" /><?php
                                if (!empty(get_option('sm_verify_phone_msg')))
                                    echo stripslashes(esc_html(get_option('sm_verify_phone_msg')));
                                else
                                    echo "Your phone number at slushmoney is not verified. Please verify your phone number.";
                                ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th><label for="sm_donation_success_msg">Donation Success Message</label></th>
                            <td>
                                <textarea name="sm_donation_success_msg" id="sm_donation_success_msg" style="width:100%; resize:vertical;min-height: 100px;" /><?php
                                if (!empty(get_option('sm_donation_success_msg')))
                                    echo stripslashes(esc_html(get_option('sm_donation_success_msg')));
                                else
                                    echo "Donation added successfully.";
                                ?></textarea>
                            </td>
                        </tr>


                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings">
                </p>
            </form>
        </div> <?php
    }

    public function checkValidHtml($string) {
        if (empty($string))
            return true;

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        if ($doc->loadHTML($string) === false){
            libxml_clear_errors();
            return false;
        }
        else
            {
            libxml_clear_errors();
            return true;

        }


    }


    public function handleMessagesSettingsForm() {
        if (!isset($_POST['sm_form']) || !wp_verify_nonce($_POST['sm_form'], 'sm_update')) {
            ?>
            <div class="error">
                <p>Sorry, your data provided was not correct. Please try again.</p>
            </div> <?php
            exit;
        } else {

            if (isset($_POST['sm_auto_add']) && !empty($_POST['sm_auto_add']))
                $sm_auto_add = $_POST['sm_auto_add'];
            else
                $sm_auto_add = 0;
            if (isset($_POST['sm_redirect_directly_to_phone_auth']) && !empty($_POST['sm_redirect_directly_to_phone_auth']))
                $sm_redirect_directly_to_phone_auth = $_POST['sm_redirect_directly_to_phone_auth'];
            else
                $sm_redirect_directly_to_phone_auth = 0;

            $sm_block_warning_msg = $_POST['sm_block_warning_msg'];
            $sm_block_msg = $_POST['sm_block_msg'];
            $sm_not_logged_in_msg = $_POST['sm_not_logged_in_msg'];
            $sm_not_registered_msg = $_POST['sm_not_registered_msg'];
            $sm_in_archive_msg = $_POST['sm_in_archive_msg'];
            $sm_not_logged_in_donation = $_POST['sm_not_logged_in_donation'];
            $sm_verify_phone_msg = $_POST['sm_verify_phone_msg'];
            $sm_donation_success_msg = $_POST['sm_donation_success_msg'];
            $validHtmlFlag = "";

            if (!$this->checkValidHtml($sm_block_warning_msg)) {
                $validHtmlFlag = "Invalid html formatting in 'Warning Message for Block'. , Please fix it and save again.";
            } else if (!$this->checkValidHtml($sm_block_msg)) {
                $validHtmlFlag = "Invalid html formatting in 'Message for Block'. , Please fix it and save again.";
            } else if (!$this->checkValidHtml($sm_not_logged_in_msg)) {
                $validHtmlFlag = "Invalid html formatting in 'User not Logged in Message'. , Please fix it and save again.";
            } else if (!$this->checkValidHtml($sm_not_registered_msg)) {
                $validHtmlFlag = "Invalid html formatting in 'User Not Registered at Slushmoney'. , Please fix it and save again.";
            } else if (!$this->checkValidHtml($sm_in_archive_msg)) {
                $validHtmlFlag = "Invalid html formatting in 'User in Slushmoney Archive'. , Please fix it and save again.";
            } else if (!$this->checkValidHtml($sm_not_logged_in_donation)) {
                $validHtmlFlag = "Invalid html formatting in 'Donation - User not Logged in'. , Please fix it and save again.";
            } else if (!$this->checkValidHtml($sm_verify_phone_msg)) {
                $validHtmlFlag = "Invalid html formatting in 'Verify Phone'. , Please fix it and save again.";
            } else if (!$this->checkValidHtml($sm_verify_phone_msg)) {
                $validHtmlFlag = "Invalid html formatting in 'Donation Success Message'. , Please fix it and save again.";
            }
            if (!empty($validHtmlFlag)) {
                ?>
                <div class="updated">
                    <p><?php echo $validHtmlFlag; print_r($sm_block_warning_parse); ?></p>
                </div> <?php
            } else {

                update_option('sm_block_warning_msg', $sm_block_warning_msg);
                update_option('sm_block_msg', $sm_block_msg);
                update_option('sm_not_logged_in_msg', $sm_not_logged_in_msg);
                update_option('sm_auto_add', $sm_auto_add);
                update_option('sm_redirect_directly_to_phone_auth', $sm_redirect_directly_to_phone_auth);
                update_option('sm_not_registered_msg', $sm_not_registered_msg);
                update_option('sm_in_archive_msg', $sm_in_archive_msg);
                update_option('sm_not_logged_in_donation', $sm_not_logged_in_donation);
                update_option('sm_verify_phone_msg', $sm_verify_phone_msg);
                update_option('sm_donation_success_msg', $sm_donation_success_msg);
                ?>
                <div class="updated">
                    <p>Messages setting has been saved!</p>
                </div> <?php
            }
        }
    }

    //END OF MESSAGES SETTINGS
    //3- SHORTCODE TAB ////+4N?0}sgiYk;
    public function smShortcodesContents() {
         // ADD STYLING
        wp_register_style('sm_setting', plugins_url('css/settings.css', __FILE__));
        wp_enqueue_style('sm_setting');
        add_action('admin_print_styles', 'sm_setting');
         wp_enqueue_script('sm_general_js',plugins_url('js/sm_general.js', __FILE__),
    array(), false, true);
         add_action('admin_print_scripts', 'sm_general_js');
        ?>

   <div class="tab">
  <a class="tablinks" href="?page=slush_money" >General Settings</a>
  <a class="tablinks"  href="?page=sm_messages">Messages</a>
  <a class="tablinks active"  href="?page=sm_shortcodes">Shortcodes</a>
</div>

        <div class="tabcontent"  >
            <h2>Shortcodes</h2>
            <form method="POST">
                <input type="hidden" name="updated" value="true" />
                <?php wp_nonce_field('sm_update', 'sm_form'); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th style="width:320px;"><label>Continue with Slushmoney button shortcode:</label></th>
                            <td>[sm_continue]</td>
                        </tr>
                        <tr>
                            <th colspan="2"><label>Parameters</label><br/> 1- text : Display text on button, default is "Continue"<br/>
                                2- msg_text : Message shown if user is not logged in  <br><br>
								(  Example : [sm_continue text="Continue"]  ) </th><br>
								
							                          </tr>
                        <tr>
                            <th><label>Donation shortcode</label></th>
                            <td>[sm_receive_donation]</td>
                        </tr>
                        <tr>
                            <th colspan="2"><label>Parameters</label><br/> 1- donation : Default donation amount to be displayed in textbox.
                                <br/> 2- recursive : Add checkbox to allow users to donate recursively
                                <br/> 3- charity_receipt : Add checkbox to allow users to have receipt of charity
                                <br/> 4- title : Title of page
                            </th>
                        </tr>
                        <tr>
                            <th><label>Verify Phone Button shortcode</label></th>
                            <td>[sm_verify_phone]</td>
                        </tr>
                        <tr>
                            <th colspan="2"><label>Parameters</label><br/> 1- text : Display text on button, default is "Click here to add your phone number"<br/>
                            </th>
                        </tr>
                        <tr>
                            <th><label>Display article rate per visit</label></th>
                            <td>[sm_display_article_rate]</td>
                        </tr>
                        <tr>
                            <th colspan="2"><label>Parameters</label><br/> 1- currency : Display rate in provided currency passed as parameter. Default rates are displayed in user currency. User is not logged in, rate are shown in default currency.<br/>
                            </th>
                        </tr>
                    </tbody>
                </table>

            </form>
        </div> <?php
    }

    //END OF SHORTCODE
}

new SM_Setting();




require_once plugin_dir_path(__FILE__) . 'sm-payableLink.php';
require_once plugin_dir_path(__FILE__) . 'sm-header-paywall.php';
require_once plugin_dir_path(__FILE__) . 'sm-donation-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'sm-shortcodes.php';
