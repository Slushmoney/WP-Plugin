<?php

class SM_PayableLink {

    //private $payable_links = array();

    function __construct() {
        $this->smAddActions();
    }

    function smAddActions() {

        if (SM_SYNCHRONIZED == 1) {
            add_action('load-post.php', array($this, 'smPostEditLoad'));
        }
        add_action('add_meta_boxes', array($this, 'smAddPayableLinkMetabox'));
        add_action('save_post', array($this, 'smSavePayableLinkCheckbox'), 1, 2);
        add_filter('manage_posts_columns', array($this, 'smPayableLinkColumnHeader'));
        add_action('manage_posts_custom_column', array($this, 'smPayableLinkColumnContents'), 10, 2);
        add_filter('manage_pages_columns', array($this, 'smPayableLinkColumnHeader'));
        add_action('manage_pages_custom_column', array($this, 'smPayableLinkColumnContents'), 10, 2);
        add_filter('bulk_actions-edit-post', array($this, 'smPayableLinkBulkAction'));
        add_filter('bulk_actions-edit-page', array($this, 'smPayableLinkBulkAction'));
        add_filter('handle_bulk_actions-edit-post', array($this, 'smPayableLinkBulkActionHandler'), 10, 3);
        add_filter('handle_bulk_actions-edit-page', array($this, 'smPayableLinkBulkActionHandler'), 10, 3);
    }

    // CALLED WHEN PAGE IS LOADED ON ADMIN   
    public  function smPostEditLoad() {

        if (!empty($_GET['post'])) {
            // Get the post object
            $post = get_post($_GET['post']);
            $is_payable_wp = (int) get_post_meta($post->ID, 'is_payable', true);
            $link = get_permalink($post->ID); //link
            $url = SM_URL . 'api/getPayableLink';
            $curl = curl_init();
            $curlopt_array = array(
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => array(
                    'request' => 'is_payable',
                    'link' => $link)
            );
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt_array($curl, $curlopt_array);
            $content = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if (!$err) {
                $content = json_decode($content);
                $is_payable_sm = (int) $content->is_payable;
            }
            if ($is_payable_sm != $is_payable_wp) {
                update_post_meta($post->ID, 'is_payable', $is_payable_sm);
            }
        }
    }

    //ADD METABOX TO EACH POST/PAGE FOR PAYABLE LINK CHECKBOX

    function smAddPayableLinkMetabox() {
        add_meta_box('payable_link', 'PAYABLE LINKS', array($this, 'smAddPayableLinkCheckbox'), array('page', 'post'), 'side', 'high');
    }

    //ADD CHECKBOX TO EACH POST/PAGE INSIDE METABOX TO SHOW IF ITS PAYABLE LINK
    function smAddPayableLinkCheckbox($post) {

        global $post;
        $isPayable = get_post_meta($post->ID, 'is_payable', true);
        ?>
        <input type="checkbox" name="is_payable" value="1" <?php echo (($isPayable == 1) ? 'checked="checked"' : ''); ?>/> YES
        <?php
    }

    //SAVE IS_PAYABLE CHECKBOX VALUE INSIDE POST/PAGE. 
    function smSavePayableLinkCheckbox($post_id, $post) {
        global $post;

        if (isset($post->post_status) && ('auto-draft' == $post->post_status || 'draft' == $post->post_status)) {
            return true;
        }

        $isPayable = get_post_meta($post_id, 'is_payable', true);
        $is_payable =0;
        if (isset($_POST['is_payable']) && $_POST['is_payable'] ==1) {
            $is_payable =1;
        }
       
            if ($isPayable == $is_payable) {
                return true;
            }
            if (SM_SYNCHRONIZED == 1) {
                $url = SM_URL . "api/payableLink";
               // $domain = get_site_url();
                $key = get_option('sm_domain_key', '');
                $link = get_permalink($post_id); //link
                $curl = curl_init();

                if (isset($_POST['is_payable']) && 1 == $_POST['is_payable']) {


                    $curlopt_array = array(
                        CURLOPT_URL => $url,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => array(
                            'key' => $key,
                            'link' => $link)
                    );
                } else {


                    $curlopt_array = array(
                        CURLOPT_URL => $url,
                        CURLOPT_CUSTOMREQUEST => 'DELETE',
                        CURLOPT_POSTFIELDS => json_encode(array(
                            'key' => $key,
                            'link' => $link))
                    );
                }
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt_array($curl, $curlopt_array);

                $content = curl_exec($curl);
                // print_r($content);
                // exit();
                $err = curl_error($curl);
                curl_close($curl);
            }
            if (SM_SYNCHRONIZED != 1 || !$err ) {
                update_post_meta($post_id, 'is_payable', $_POST['is_payable']);
                return true;
            } else {
                return false;
            }
           
        return true;
    }

    // ADD NEW COLUMN HEADER ON PAGE/POST LISTING PAGES
    function smPayableLinkColumnHeader($defaults) {


        $defaults['is_payable'] = 'Is Payable';
        return $defaults;
    }

    // SHOW VALUE OF IS_PAYABLE ON PAGE/POST LISTING PAGES
    function smPayableLinkColumnContents($column_name, $post_id) {


        if ($column_name == 'is_payable') {

            $is_payable = get_post_meta($post_id, 'is_payable', true);
            if (SM_SYNCHRONIZED == 1) {
                $link = get_permalink($post_id); //link

                $url = SM_URL . 'api/getPayableLink';
                $curl = curl_init();
                $curlopt_array = array(
                    CURLOPT_URL => $url,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => array(
                        'request' => 'is_payable',
                        'link' => $link)
                );
                
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt_array($curl, $curlopt_array);
                $content = curl_exec($curl);
                // print_r($content);
                // exit();
                $err = curl_error($curl);
                curl_close($curl);
                if (!$err) {
                    $content = json_decode($content);
                    $is_payable_sm = (int) $content->is_payable;
                }
                if ($is_payable_sm != $is_payable) {
                    update_post_meta($post_id, 'is_payable', $is_payable_sm);
                    $is_payable = $is_payable_sm;
                }
            }

            if ($is_payable == 1)
                echo "Yes";
            else
                echo "No";
        }
    }

    // ADD BULK MENU DROPDOWN MENU FOR PAYALBLE LINK
    function smPayableLinkBulkAction($actions) {

        $actions['sm_make_payable'] = 'Make payable';
        $actions['sm_make_non_payable'] = 'Make non-payable';

        return $actions;
    }

    // SAVE BULK ACTION FOR PAYABLE LINK
    function smPayableLinkBulkActionHandler($redirect, $doaction, $object_ids) {


        // let's remove query args first
        $redirect = remove_query_arg(array('sm_make_payable_done', 'sm_make_non_payable_done'), $redirect);



        // do something for "Make Draft" bulk action
        if ($doaction == 'sm_make_payable') {


            $links = array();
            foreach ($object_ids as $post_id) {
                //update_post_meta($post_id, 'is_payable', 1);
                $links[] = get_permalink($post_id); //link
            }
            if (SM_SYNCHRONIZED == 1) {
                $url = SM_URL . "api/payableLink";
               // $domain = get_site_url();
                $key = get_option('sm_domain_key', '');
                //$link = get_permalink($post->ID); //link

                $curl = curl_init();
                $curlopt_array = array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => array(
                        'key' => $key,
                        'links' => urlencode(serialize($links)))
                );
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt_array($curl, $curlopt_array);
                $content = curl_exec($curl);
              //  print_r($content);
              //   exit();
                $err = curl_error($curl);
                curl_close($curl);
            }


            if ( SM_SYNCHRONIZED != 1 || !$err ) {
                //update_post_meta($post->ID, 'is_payable', $_POST['is_payable']);
                foreach ($object_ids as $post_id) {
                    update_post_meta($post_id, 'is_payable', 1);
                }
            }


            // do not forget to add query args to URL because we will show notices later
            $redirect = add_query_arg(
                    'sm_make_payable_done', // just a parameter for URL (we will use $_GET['misha_make_draft_done'] )
                    count($object_ids), // parameter value - how much posts have been affected
                    $redirect);
        }

        // do something 
        if ($doaction == 'sm_make_non_payable') {
            $links = array();
            foreach ($object_ids as $post_id) {
                //update_post_meta($post_id, 'is_payable', 1);
                $links[] = get_permalink($post_id); //link
            }
            if (SM_SYNCHRONIZED == 1) {
               // $domain = get_site_url();
                $key = get_option('sm_domain_key', '');
                //$link = get_permalink($post->ID); //link
                $url = SM_URL . 'api/payableLink';
                $curl = curl_init();
                $curlopt_array = array(
                    CURLOPT_URL => $url,
                    CURLOPT_CUSTOMREQUEST => 'DELETE',
                    CURLOPT_RETURNTRANSFER => TRUE,
                    CURLOPT_POSTFIELDS => json_encode(array(
                        'key' => $key,
                        'links' => urlencode(serialize($links))))
                );
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt_array($curl, $curlopt_array);
                $content = curl_exec($curl);
                 //print_r($content);
                 //exit();
                $err = curl_error($curl);
                curl_close($curl);
            }

            if (SM_SYNCHRONIZED != 1 || !$err ) {
                //update_post_meta($post->ID, 'is_payable', $_POST['is_payable']);
                foreach ($object_ids as $post_id) {
                    update_post_meta($post_id, 'is_payable', 0);
                }
            }
            $redirect = add_query_arg('sm_make_non_payable_done', count($object_ids), $redirect);
        }

        return $redirect;
    }

}

new SM_PayableLink();

//////////////////////Header Code for Paywal/////////////////////
  