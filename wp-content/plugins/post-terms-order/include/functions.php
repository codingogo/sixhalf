<?php
        
        function pto_get_object_sort_settings($object_id, $taxonomy)
            {
                //load previous saved data if exists
                $object_custom_sort =   get_post_meta($object_id, '_taxonomy_order_' . $taxonomy, TRUE);
                     
                $defaults   = array (
                                        'order_type'                =>  'manual',
                                        'sort_data'                 =>  array(),
                                        'auto_order_by'             =>  '_default_',
                                        'auto_order'                =>  'ASC',
                                    );
                $object_custom_sort          = wp_parse_args( $object_custom_sort, $defaults );
                
                return $object_custom_sort;
                
            }
        
        
           
        /**
        * Retrieves the terms associated with the given object(s), in the supplied taxonomies.
        *
        * The following information has to do the $args parameter and for what can be
        * contained in the string or array of that parameter, if it exists.
        *
        * The first argument is called, 'orderby' and has the default value of 'name'.
        * The other value that is supported is 'count'.
        *
        * The second argument is called, 'order' and has the default value of 'ASC'.
        * The only other value that will be acceptable is 'DESC'.
        *
        * The final argument supported is called, 'fields' and has the default value of
        * 'all'. There are multiple other options that can be used instead. Supported
        * values are as follows: 'all', 'ids', 'names', and finally
        * 'all_with_object_id'.
        *
        * The fields argument also decides what will be returned. If 'all' or
        * 'all_with_object_id' is chosen or the default kept intact, then all matching
        * terms objects will be returned. If either 'ids' or 'names' is used, then an
        * array of all matching term ids or term names will be returned respectively.
        *
        * @since 2.3.0
        * @uses $wpdb
        *
        * @param int|array $object_ids The ID(s) of the object(s) to retrieve.
        * @param string|array $taxonomies The taxonomies to retrieve terms from.
        * @param array|string $args Change what is returned
        * @return array|WP_Error The requested term data or empty array if no terms found. WP_Error if any of the $taxonomies don't exist.
        */
        function pto_wp_get_object_terms($terms, $object_ids, $taxonomies, $args = array()) 
            {
                global $wpdb;
                
                if(!is_array($terms)    ||  count($terms) < 1)
                    return $terms;
                    
                //no need to continue if there's no orderby request argument
                /*
                if(!isset($args['orderby']) ||  $args['orderby']    !=  'post_term_order')
                    return $terms;
                */
                
                //check for order ignore 
                if(isset($args['ignore_post_term_order']) && $args['ignore_post_term_order']    === TRUE)
                    return $terms;
                
                if(!is_array($object_ids))
                    $object_ids =   explode(",", $object_ids);
                $object_ids = array_map('trim', $object_ids);
                
                if ( !is_array($taxonomies) )
                    $taxonomies = explode(",", $taxonomies);
                $taxonomies = array_map('trim', $taxonomies);

                foreach ( $taxonomies as $key   =>  $taxonomy ) 
                    {
                        $taxonomies[$key]   =   trim($taxonomy, "'");
                    }
                
                //there's no such sorts available for multiple objects at once
                if(count($object_ids) > 1 || count($taxonomies) > 1)
                    return $terms;
                
                $object_id  =   $object_ids[0];
                $taxonomy   =   $taxonomies[0];
                
                $terms_map      =   array();
                $terms_data     =   array();
                
                $_fields        =   isset($args['fields']) ? $args['fields']  : '';
                
                //check for any custom sort
                $object_custom_sort    =   pto_get_object_sort_settings($object_id, $taxonomy, TRUE);
                if($object_custom_sort['order_type'] == 'manual' && count($object_custom_sort['sort_data']) < 1)
                    return $terms;
                    
                //check fro atuomatic to apply the new sort
                if($object_custom_sort['order_type'] == 'automatic')
                    {
                        $orderby    =   $object_custom_sort['auto_order_by'];
                        $order      =   $object_custom_sort['auto_order'];
                        
                        if($orderby ==  '_default_')
                            $orderby    =   'name';
                        
                        $args['orderby']    =   $orderby;
                        $args['order']      =   $order;
                    }
                
                remove_action('wp_get_object_terms', 'pto_wp_get_object_terms', 1000, 4);
                        
                $_args  =   $args;
                unset($_args['fields']);
                $terms_data =   wp_get_object_terms($object_ids, $taxonomies, $_args);
                
                $_terms_data =   array();
                foreach($terms_data as $key  =>  $term)
                    {
                        if(isset($term->term_id))
                            {
                                $_terms_data[$term->term_id] =   $term;
                            }
                    }
                $terms_data    =   $_terms_data;
                unset($_terms_data);
                    
                    
                add_action('wp_get_object_terms', 'pto_wp_get_object_terms', 1000, 4);
                
                //no such functionality for the free version
                if($object_custom_sort['order_type'] == 'automatic')
                    {
                        return $terms;   
                    }
                      
                foreach($taxonomies as $taxonomy)
                    {
                        $terms_keys_replacement =   array();
                        
                        foreach($terms_data as $key  =>  $term)
                            {
                                if($term->taxonomy == $taxonomy)
                                    $terms_keys_replacement[]   =   $key;    
                            }
                        
                        $terms  =   pto_apply_custom_order($object_id, $taxonomy, $terms, $terms_data, $terms_keys_replacement, $_fields);   
                    }

                return $terms;
            }
            
            
        add_action('wp_get_object_terms',   'pto_wp_get_object_terms', 1000, 4);    
        add_action('get_the_terms',         'pto_wp_get_object_terms', 1000, 3);
        
        
        function pto_apply_custom_order($object_id, $taxonomy, $terms, $terms_data, $terms_keys_replacement, $_fields)
            {
                //check for any custom sort
                $object_custom_sort    =   pto_get_object_sort_settings($object_id, $taxonomy, TRUE);

                $_terms =   array();
                
                switch($object_custom_sort['order_type'])
                    {
                        case 'manual'   :
                        
                                            if(count($object_custom_sort['sort_data']) < 1)
                                                return $terms;
                                            
                                            $sort_data  =   $object_custom_sort['sort_data'];
                                            
                                            $terms_map  =   array();
                                            foreach($terms_keys_replacement as  $key    =>  $term_list_key)
                                                {
                                                    $terms_map[]    =   $terms_data[$term_list_key]->term_id;      
                                                }
                                            
                                            //exclude the sorted terms which are not belong anymore to the object
                                            foreach($sort_data as $key =>  $object_sort_term)
                                                {
                                                    if(!in_array($object_sort_term, $terms_map))
                                                        unset($sort_data[$key]);
                                                }
                                            
                                            $sort_data =   array_values($sort_data);
                                            //put all other terms at the end
                                            foreach($terms_map  as  $key    =>  $term_id)
                                                {
                                                    if(!in_array($term_id, $sort_data))
                                                        $sort_data[]   =   $term_id;   
                                                }
                                            
                                           
                                            foreach(pto_sanitize_terms($sort_data, $_fields, $taxonomy)  as $key =>  $data)
                                                {
                                                    $_terms[]   =   $data;      
                                                }
                        
                                            break;
                                            
                        case 'automatic'    :
                                            
                                            
                                            
                                            break;
                        
                    }

                return $_terms;   
                
            }
            
            
        function pto_sanitize_terms($sort_data, $_fields, $taxonomy)
            {
                $_terms  =   array();
                
                foreach ( $sort_data as $key => $term_id ) 
                    {
                        $term_data  =   get_term_by('id', $term_id, $taxonomy);
                        
                        switch($_fields)
                            {

                                case 'ids'      :
                                                    $_terms[] = sanitize_term_field( $_fields, $term_data->term_id, $term_data->term_id, $taxonomy, 'raw' );    
                                                    break;
                                case 'names'    :   
                                                    $_terms[] = sanitize_term_field( $_fields, $term_data->name, $term_data->name, $taxonomy, 'raw' );    
                                                    break;
                                case 'slugs'    :
                                                    $_terms[] = sanitize_term_field( $_fields, $term_data->slug, $term_data->slug, $taxonomy, 'raw' );    
                                                    break;
                                case 'tt_ids'   :
                                                    $_terms[] = sanitize_term_field( 'term_taxonomy_id', $term_data->term_taxonomy_id, $term_data->term_taxonomy_id, $taxonomy, 'raw' );    
                                                    break;
                                default          :
                                                    $_terms[] = sanitize_term( $term_data, $taxonomy, 'raw' );
                                                    break;
                            }                        
                    }
                
                return $_terms;
            }
            
            
        function PTeO_info_box()
            {
                ?>
                    <div id="cpt_info_box">
                         <div id="p_right"> 
                            
                            <div id="p_socialize">
                                <div class="p_s_item s_f">
                                <div id="fb-root"></div>
                                <script>(function(d, s, id) {
                                  var js, fjs = d.getElementsByTagName(s)[0];
                                  if (d.getElementById(id)) return;
                                  js = d.createElement(s); js.id = id;
                                  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.5";
                                  fjs.parentNode.insertBefore(js, fjs);
                                }(document, 'script', 'facebook-jssdk'));</script>
                                
                                <div class="fb-like" data-href="https://www.facebook.com/Nsp-Code-190329887674484/" data-layout="button_count" data-action="like" data-show-faces="true" data-share="false"></div>
                                
                            </div>
                            
                            <div class="p_s_item s_t">
                                <a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.nsp-code.com" data-text="Define custom order for your post types through an easy to use javascript AJAX drag and drop interface. No theme code updates are necessarily, this plugin will take care of query update." data-count="none">Tweet</a><script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
                            </div>
                            
                            <div class="p_s_item s_gp">
                                <!-- Place this tag in your head or just before your close body tag -->
                                <script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>

                                <!-- Place this tag where you want the +1 button to render -->
                                <div class="g-plusone" data-size="small" data-annotation="none" data-href="http://nsp-code.com/"></div>
                            </div>
                                
                                <div class="clear"></div>
                            </div>
                            
                            <div id="donate_form">
                                <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                                    <input type="hidden" name="cmd" value="_donations">
                                    <input type="hidden" name="business" value="electronice_delphi@yahoo.com">
                                    <input type="hidden" name="lc" value="RO">
                                    <input type="hidden" name="item_name" value="Nsp-Code">
                                    <input type="hidden" name="item_number" value="post-terms-order">
                                    <input type="hidden" name="no_note" value="0">
                                    <input type="hidden" name="cn" value="Add special instructions to the seller:">
                                    <input type="hidden" name="no_shipping" value="2">
                                    <input type="hidden" name="cancel_return" value="http://nsp-code.com/donate.php">
                                    <input type="hidden" name="currency_code" value="USD">
                                    <input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted">
                                    <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                                    <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                                </form>

                            </div>
                        </div>
                        
                        <p><?php _e('Did you find this plugin useful? Please support our work with a donation or write an article about this plugin in your blog with a link to our site', 'post-types-order') ?> <a href="http://www.nsp-code.com/" target="_blank"><strong>http://www.nsp-code.com/</strong></a>.</p>
                        <h4><?php _e( "Did you know there is available an advanced version of this plug-in?", 'to' ) ?> <a target="_blank" href="http://www.nsp-code.com/premium-plugins/woocommerce-plugins/advanced-post-terms-order/"><?php _e( "Read more", 'to' ) ?></a></h4>
                        <p><?php _e('Check our', 'cpt') ?> <a target="_blank" href="http://wordpress.org/extend/plugins/taxonomy-terms-order/">Category Order - Taxonomy Terms Order</a> <?php _e('plugin which allow to custom sort categories and custom taxonomies terms', 'cpt') ?> </p>
                        
                        <div class="clear"></div>
                    </div>
                
                <?php   
            }


  
?>