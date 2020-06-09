<?php
/**
 * Checkout Form fields definifions.
 */
if ( ! defined( 'MOSKITO_MAX_PERSON_IN_ORDER' ) ) {
    define( 'MOSKITO_MAX_PERSON_IN_ORDER', 4 );
}
/**
 * Add Common fields for each persons:
 * FirstName,
 * LastName,
 * Date Of Birth,
 * Passport Number,
 * Passport Valid Date.
 */
add_action( 'woocommerce_after_checkout_billing_form', 'moskito_checkout_persons_fields' );
function moskito_checkout_persons_fields( $fields ) {

    /*$field_name = 'billing_persons_count';
    woocommerce_form_field( $field_name, [
        'type' => 'select',
        'required'  => $required,
        'class' => ['form-row-wide'],
        'label' => __('Select additional persons count', 'adventure-tours-child'),
        'options' => [
            0 => __('no', 'adventure-tours-child'),
            1 => '1 ' . __('more person', 'adventure-tours-child'),
            2 => '2 ' . __('more person', 'adventure-tours-child'),
            3 => '3 ' . __('more person', 'adventure-tours-child'),
        ],
        'default' => 0,
    ], $fields->get_value( $field_name ));*/

    $priority = 111;
    for($i = 0; $i < (MOSKITO_MAX_PERSON_IN_ORDER-1); $i++) {
        $suffix = '_' . $i;

        echo "<div id='moskito-person-$i' class='moskito-person'>";

        $field_name = 'billing_first_name' . $suffix;
        woocommerce_form_field( $field_name, [
            'priority' => ($priority + $i),
            'type' => 'text',
            'required'  => true,
            'validate' => false,
            'class' => ['form-row-first'],
            'label' => __('First name', 'adventure-tours-child'),
        ], $fields->get_value( $field_name ));

        $field_name = 'billing_last_name' . $suffix;
        woocommerce_form_field( $field_name, [
            'priority' => ($priority + $i + 1),
            'type' => 'text',
            'required'  => true,
            'validate' => false,
            'class' => ['form-row-last'],
            'label' => __('Last name', 'adventure-tours-child'),
        ], $fields->get_value( $field_name ));

        $field_name = 'billing_date_of_birth' . $suffix;
        woocommerce_form_field( $field_name, [
            'priority' => ($priority + $i + 2),
            'type' => 'date',
            'required'  => true,
            'validate' => false,
            'class' => ['form-row-wide'],
            'label' => __('Date of birth', 'adventure-tours-child'),
        ], $fields->get_value( $field_name ));

        $field_name = 'billing_passport_number' . $suffix;
        woocommerce_form_field( $field_name, [
            'priority' => ($priority + $i + 3),
            'type' => 'text',
            'validate' => false,
            'class' => ['form-row-first'],
            'label' => __('Passport number', 'adventure-tours-child'),
        ], $fields->get_value( $field_name ));

        $field_name = 'billing_passport_valid_until' . $suffix;
        woocommerce_form_field( $field_name, [
            'priority' => ($priority + $i + 4),
            'type' => 'date',
            'validate' => false,
            'class' => ['form-row-last'],
            'label' => __('Passport valid until', 'adventure-tours-child'),
        ], $fields->get_value( $field_name ));

//        $field_name = 'billing_remarks' . $suffix;
//        woocommerce_form_field( $field_name, [
//            'priority' => ($priority + $i + 5),
//            'type' => 'text',
//            'class' => ['form-row-wide'],
//            'label' => __('Remarks e.g. seat in aircraft, vegetarian etc', 'adventure-tours-child'),
//        ], $fields->get_value( $field_name ));

        $priority += MOSKITO_MAX_PERSON_IN_ORDER + 1;
        echo "<div class='clearfix'></div>";

        echo "</div>";
    }

}
add_filter( 'woocommerce_checkout_fields', 'moskito_checkout_other_fields', 10, 1 );
/**
 * Add other fields as Emergency call needed. Change order of fields.
 */
function moskito_checkout_other_fields( $fields ) {

    // Main fields additions
    $fields['billing']['billing_date_of_birth'] = [
        'priority' => 22,
        'type' => 'date',
        'required'  => true,
        'class' => ['form-row-wide'],
        'label' => __('Date of birth', 'adventure-tours-child'),
    ];

    $fields['billing']['billing_passport_number'] = [
        'priority' => 23,
        'type' => 'text',
        'class' => ['form-row-first'],
        'label' => __('Passport number', 'adventure-tours-child'),
    ];

    $fields['billing']['billing_passport_valid_until'] = [
        'priority' => 24,
        'type' => 'date',
        'class' => ['form-row-last'],
        'label' => __('Passport valid until', 'adventure-tours-child'),
    ];

    unset( $fields['billing']['billing_company'] );

    $fields['billing']['billing_country']['priority'] = 95;

    $fields['billing']['billing_phone']['label'] = __('Telefon 1', 'adventure-tours-child');
    $fields['billing']['billing_phone']['class'] = ['form-row-first'];
    $fields['billing']['billing_phone']['required'] = true;
    $fields['billing']['billing_phone_2'] = [
        'priority' => 105,
        'type' => 'tel',
        'class' => ['form-row-last'],
        'label' => __('Telefon 2', 'adventure-tours-child'),
    ];
    $fields['billing']['billing_email']['required'] = true;

    // Other fields

    $fields['billing']['billing_persons_count'] = [
        'priority' => 110,
        'type' => 'select',
        'options' => [
            0 => __('no', 'adventure-tours-child'),
            1 => '1 ' . __('more person', 'adventure-tours-child'),
            2 => '2 ' . __('more person', 'adventure-tours-child'),
            3 => '3 ' . __('more person', 'adventure-tours-child'),
        ],
        'default' => 0,
        'class' => ['form-row-wide'],
        'label' => __('Select additional persons count', 'adventure-tours-child'),
    ];

    $fields['billing']['billing_additional_files'] = [
        'priority' => 111,
        'type' => 'file',
        'default' => 0,
        'class' => ['form-row-wide'],
        'label' => __('Weitere Dateien zu ihrer Buchung z.B. Kopie Reisepassseite bitte hier einfügen / hochladen', 'adventure-tours-child'),
    ];
    $fields['billing']['billing_room_request'] = [
        'priority' => 130,
        'type' => 'select',
        'options' => [
            0 => __('No matter', 'adventure-tours-child'),
            2 => __('Double room', 'adventure-tours-child'),
            1 => __('Single room', 'adventure-tours-child'),
            3 => __('Half double room', 'adventure-tours-child'),
        ],
        'default' => 0,
        'class' => ['form-row-wide'],
        'label' => __('Room request', 'adventure-tours-child'),
    ];

    $fields['billing']['billing_prolongation'] = [
        'priority' => 131,
        'type' => 'checkbox',
        'class' => ['form-row-wide'],
        'label' => __('Prolongation', 'adventure-tours-child'),
    ];

    $fields['billing']['billing_preferred_departure_points'] = [
        'priority' => 132,
        'type' => 'text',
        'class' => ['form-row-wide'],
        'label' => __('Preferred departure points ', 'adventure-tours-child'),
    ];

    $fields['billing']['billing_rail_fly_carrier'] = [
        'priority' => 133,
        'type' => 'checkbox',
        'class' => ['form-row-wide'],
        'label' => __('Rail&Fly carrier', 'adventure-tours-child'),
    ];

    $fields['billing']['billing_comment'] = [
        'priority' => 134,
        'type' => 'textarea',
        'class' => ['form-row-wide'],
        'label' => __('Comment', 'adventure-tours-child'),
    ];
    $fields['billing']['additional_phone_number'] = [
        'priority' => 135,
        'type' => 'text',
        'class' => ['form-row-wide'],
        'label' => __('Im Falle eines Notfalls verständigen (Name, Adresse, Telefonnummer)', 'adventure-tours-child'),
        'required' => true,
    ];
    // remove second stroke of address
    unset($fields['billing']['billing_address_2']);

    unset($fields['order']);
    // In case of emergency fields
    /* $fields['order']['order_emergensy_name'] = [
         'type' => 'text',
         'required'  => true,
         'class' => ['form-row-wide'],
         'label' => __('In case of emergency call', 'adventure-tours-child'),
         'placeholder' => _x('Name', 'placeholder', 'adventure-tours-child')
     ];
     $fields['order']['order_emergensy_phone'] = [
         'type' => 'text',
         'required'  => true,
         'class' => ['form-row-wide'],
         'label' => __('Emergency phone', 'adventure-tours-child'),
         'placeholder' => _x('Phone', 'placeholder', 'adventure-tours-child')
     ];
     $fields['order']['order_emergensy_address'] = [
         'type' => 'textarea',
         'class' => ['form-row-wide'],
         'label' => __('Emergency address', 'adventure-tours-child'),
         'placeholder' => _x('Address', 'placeholder', 'adventure-tours-child')
     ];*/

    return $fields;
}
/**
 * Add additional terms checkboxes.
 */
add_action( 'woocommerce_checkout_order_review', 'moskitoAddAfterReviewNewField', 21 );
function moskitoAddAfterReviewNewField() {
    echo '<p><b>Hinweis:</b> Der hier aufgeführte Betrag ist nur der Reisegrundpreis und beinhaltet keine Extras wie Einzelzimmerzuschlag, Rail&Fly, Verlängerung etc. Diese Zusätze werden in Ihrer Rechnung berücksichtigt, die Sie im Anschluss mit Ihren Reiseunterlagen erhalten.</p>';
    echo '<div class="clearfix">';
    woocommerce_form_field('how_know_about_us', [
        'priority' => ($priority + $i),
        'type' => 'text',
        'required'  => false,
        'validate' => false,
        'class' => ['form-row-first'],
        'label' => __('Wie sind sie auf uns aufmerksam geworden?', 'adventure-tours-child'),
    ]);
    echo '</div>';
}
add_action( 'woocommerce_gzd_review_order_before_submit', 'moskito_checkout_additional_terms', 10 );
function moskito_checkout_additional_terms() {
    foreach ( WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $partner_id = get_post_meta( $product_id, '_tour_partner', true );
        $partner = get_the_title($partner_id[0]);
        if (!empty($partners)) {
            if (in_array($partner, $partners)) continue;
        }
        if ($partner == "Moskito Adventures") {
            $formblatt_text = 'Ich und auch alle hier angemeldeten Teilnehmer haben das {formblatt_link}Formblatt{/formblatt_link} nach EU-Pauschalreiserichtlinie dieser Tour erhalten, gelesen und zustimmend zur Kenntnis genommen.';
            $f_link=get_option('_formblatt_link','');
            $formblatt_text=str_replace(array('{formblatt_link}','{/formblatt_link}'),array('<a href="'.esc_attr($f_link).'" target="_blank">','</a>'),$formblatt_text);
        } else {
            $formblatt_text = 'Ich und auch alle hier angemeldeten Teilnehmer haben das {formblatt_link}Formblatt{/formblatt_link} nach EU-Pauschalreiserichtlinie und die {term_link}Allgemeinen Geschäfts- und Reisebedingungen{/term_link} von dem Reiseveranstalter dieser Tour erhalten, gelesen und zustimmend zur Kenntnis genommen.';
            $f_link=get_post_meta($product_id,'_links_formblatt',true);
            $term_link=get_post_meta($product_id,'_links_terms',true);
            $formblatt_text=str_replace(array('{formblatt_link}','{/formblatt_link}'),array('<a href="'.esc_attr($f_link).'" target="_blank">','</a>'),$formblatt_text);
            $formblatt_text=str_replace(array('{term_link}','{/term_link}'),array('<a href="'.esc_attr($term_link).'" target="_blank">','</a>'),$formblatt_text);
        }
        $partners[] = $partner;
        $out .= '<p>'.
            '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">'.
                '<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="terms_received_form-'.$i.'" id="terms-received_form-'.$i.'">'.
                '<span class="woocommerce-terms-and-conditions-checkbox-text">'.
                    $formblatt_text.
                '</span>&nbsp;'.
                '<span class="required">*</span>'.
            '</label>'.
            '<input type="hidden" name="terms_$term_field" value="1" >'.
        '</p>';
    }
    $out.='<script type="text/javascript">
		window.addEventListener("load",function(){
			var legal=document.querySelector(".wc-gzd-checkbox-placeholder.wc-gzd-checkbox-placeholder-legal");
			if(legal){
				var subm=document.querySelector(".wc-gzd-order-submit");
				subm.parentNode.insertBefore(legal,subm);
			}
		});
	</script>';
    echo $out;
}
/**
 * Validate additional terms checkboxes.
 */
add_action( 'woocommerce_after_checkout_validation', 'moskito_checkout_all_terms_checked', 10, 2);
function moskito_checkout_all_terms_checked( $fields, $errors ) {
    $all_terms = true;
    foreach([/*'agb', 'privacy_policy',*/ 'received_form'] as $v) {
        $all_terms = $all_terms && isset($_POST['terms_' . $v]) && $_POST['terms_' . $v];
    }
    if ( !$all_terms ) {
        $errors->add( 'validation', __('Please read and accept all terms and conditions.', 'adventure-tours-child') );
    }
}
/* End Checkout block */


add_action('woocommerce_after_checkout_validation','wcCheckoutSpecialValidation',PHP_INT_MAX,2);

function wcCheckoutSpecialValidation($fields, $errors){
    $debug=array();
    if($fields['billing_persons_count']>0){
        $valid=true;
        for($i=0;$i<intval($fields['billing_persons_count']);++$i){
            $postf='_'.$i;
            if($_POST['billing_first_name'.$postf]=='' || $_POST['billing_last_name'.$postf]=='' || $_POST['billing_date_of_birth'.$postf]=='' || $_POST['billing_passport_number'.$postf]=='' || $_POST['billing_passport_valid_until'.$postf]==''){
                $valid=false;
                break;
            }
        }
        if(!$valid) $errors->add( 'required-field', 'Please fill all required fields!' );
    }
    return $fields;

}

add_action('woocommerce_checkout_create_order', 'addNewOrderMeta', 20, 2);
function addNewOrderMeta($order,$data){
    $fields=array('additional_phone_number','how_know_about_us');
    for($i=0;$i<MOSKITO_MAX_PERSON_IN_ORDER;++$i){
        $postf='_'.$i;
        $fields[]='billing_first_name' . $postf;
        $fields[]='billing_last_name' . $postf;
        $fields[]='billing_date_of_birth' . $postf;
        $fields[]='billing_passport_number' . $postf;
        $fields[]='billing_passport_valid_until' . $postf;
    }
    if(is_uploaded_file($_FILES['billing_additional_files']['tmp_name'])){
        $file = wp_upload_bits(preg_replace('/[^0-9a-zA-Z %s\.]/','',$_FILES['billing_additional_files']['name']),null,@file_get_contents($_FILES['billing_additional_files']['tmp_name']));
        if($file && !$file['error']){
            $order->update_meta_data('_additional_files_path', $file['file']);
            $order->update_meta_data('_additional_files_url', $file['url']);
        }
    }
    foreach($fields as $k) if(isset($_POST[$k])){
        $order->update_meta_data('_'.$k, $_POST[$k]);
    }
}

add_action('admin_menu', 'addShopOrderBox',20);
function addShopOrderBox() {
    add_meta_box('meta-box-order-dop-info', 'Additional information', 'showOrderAdditional', 'shop_order', 'normal', 'default');
}

function showOrderAdditional(){
    global $post;
    ob_start();
    ?>
    <div>
        <div style="margin-bottom: 20px;">
            <div style="font-weight:600;"><?=__('Geburtsdatum','adventure-tours-child')?></div>
            <div><?=get_post_meta($post->ID,'_billing_date_of_birth',true)?></div>
        </div>
        <?php
        if (get_post_meta($post->ID,'_billing_passport_number',true)) {
            echo '<div style="margin-bottom: 20px;">
                <div style="font-weight:600;">'.__('Reisepassnummer','adventure-tours-child').'</div>
                <div>'.get_post_meta($post->ID,'_billing_passport_number',true).'</div>
            </div>';
        }
        if (get_post_meta($post->ID,'_billing_passport_valid_until',true)) {
            echo '<div style="margin-bottom: 20px;">
                <div style="font-weight:600;">'.__('Reisepass gültig bis','adventure-tours-child').'</div>
                <div>'.get_post_meta($post->ID,'_billing_passport_valid_until',true).'</div>
            </div>';
        }
        if (get_post_meta($post->ID,'_billing_phone_2',true)) {
            echo '<div style="margin-bottom: 20px;">
                <div style="font-weight:600;">'.__('Telefon 2','adventure-tours-child').'</div>
                <div>'.get_post_meta($post->ID,'_billing_phone_2',true).'</div>
            </div>';
        }
        // checkbox "Verlängerung"
        echo '<div style="margin-bottom: 20px;"><div style="font-weight:600;">'.__('Verlängerung','adventure-tours-child').'</div><div>';
        if (get_post_meta($post->ID,'_billing_prolongation',true) == 1) { echo 'Ya'; } else { echo 'Nein'; }
        echo '</div></div>';
        if (get_post_meta($post->ID,'_billing_preferred_departure_points',true)) {
            echo '<div style="margin-bottom: 20px;">
                <div style="font-weight:600;">'.__('Bevorzugte Abflugsorte','adventure-tours-child').'</div>
                <div>'.get_post_meta($post->ID,'_billing_preferred_departure_points',true).'</div>
            </div>';
        }
        // checkbox "Rail & Fly / Zug zum Flug"
        echo '<div style="margin-bottom: 20px;"><div style="font-weight:600;">'.__('Rail & Fly / Zug zum Flug','adventure-tours-child').'</div><div>';
        if (get_post_meta($post->ID,'_billing_rail_fly_carrier',true) == 1) { echo 'Ya'; } else { echo 'Nein'; }
        echo '</div></div>';
        if (get_post_meta($post->ID,'_billing_comment',true)) {
            echo '<div style="margin-bottom: 20px;">
                <div style="font-weight:600;">'.__('Bemerkungen (z.B. Sitzplatzwunsch im Flugzeug, Vegetarier, Verlängerungswunsch etc.)','adventure-tours-child').'</div>
                <div>'.get_post_meta($post->ID,'_billing_comment',true).'</div>
            </div>';
        }
        if (get_post_meta($post->ID,'_how_know_about_us',true)) {
            echo '<div style="margin-bottom: 20px;">
                <div style="font-weight:600;">'.__('Wie sind sie auf uns aufmerksam geworden?','adventure-tours-child').'</div>
                <div>'.get_post_meta($post->ID,'_how_know_about_us',true).'</div>
            </div>';
        }
        ?>
        <div style="margin-bottom: 20px;">
            <div style="font-weight:600;"><?=__('Zimmeranfrage','adventure-tours-child')?></div>
            <div><?php
                $room_request_number = get_post_meta($post->ID,'_billing_room_request',true);
                if ($room_request_number == 0) {
                    echo 'keine Angabe';
                }
                if ($room_request_number == 1) {
                    echo 'Einzelzimmer';
                }
                if ($room_request_number == 2) {
                    echo 'Doppelzimmer';
                }
                if ($room_request_number == 3) {
                    echo 'halbes Doppelzimmer';
                }
                ?>
            </div>
        </div>
        <div style="margin-bottom: 20px;">
            <div style="font-weight:600;"><?=__('Im Falle eines Notfalls verständigen (Name, Adresse, Telefonnummer)','adventure-tours-child')?></div>
            <div><?=get_post_meta($post->ID,'_additional_phone_number',true)?></div>
        </div>
        <?php if(get_post_meta($post->ID,'_additional_files_url',true)) : ?>
            <div style="margin-bottom: 20px;">
                <div style="font-weight:600;"><?=__('Weitere Dateien zu ihrer Buchung z.B. Kopie Reisepassseite bitte hier einfügen / hochladen','adventure-tours-child')?></div>
                <div><a href="<?=get_post_meta($post->ID,'_additional_files_url',true)?>"><?=__('File','adventure-tours-child')?></a></div>
            </div>
        <?php endif; ?>
        <?php if(intval(get_post_meta($post->ID,'_billing_persons_count',true))>0) : ?>
            <div style="font-weight: 600; margin-bottom: 5px;">
                Möchten noch weitere Personen an der Reise teilnehmen
            </div>
            <?php for($i=0;$i<intval(get_post_meta($post->ID,'_billing_persons_count',true));++$i) : ?>
                <div style="margin-bottom: 20px;">
                    <?php $postf='_'.$i; ?>
                    <ul>
                        <li><?=__('Name','adventure-tours-child')?>:  <?=get_post_meta($post->ID,'_billing_first_name'.$postf,true)?></li>
                        <li><?=__('Second name','adventure-tours-child')?>:  <?=get_post_meta($post->ID,'_billing_last_name'.$postf,true)?></li>
                        <li><?=__('Date of birth','adventure-tours-child')?>:  <?=get_post_meta($post->ID,'_billing_date_of_birth'.$postf,true)?></li>
                        <li><?=__('Passport number','adventure-tours-child')?>:  <?=get_post_meta($post->ID,'_billing_passport_number'.$postf,true)?></li>
                        <li><?=__('Passport valid until','adventure-tours-child')?>:  <?=get_post_meta($post->ID,'_billing_passport_valid_until'.$postf,true)?></li>
                    </ul>
                </div>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
    <?php
    echo ob_get_clean();
}

// Update quantity of tours on checkout page
// It will add Delete button, Quanitity field on the checkout page Your Order Table.
function add_quantity($product_title, $cart_item, $cart_item_key) {
    /* Checkout page check */
    if (is_checkout()) {
        /* Get Cart of the user */
        $cart = WC()->cart->get_cart();
        foreach ($cart as $cart_key => $cart_value) {
            if ($cart_key == $cart_item_key) {
                $product_id = $cart_item['product_id'];
                $_product = $cart_item['data'];

                /* Step 1 : Add delete icon */
                $return_value = sprintf(
                    '<a href="%s" class="remove" title="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
                    esc_url(WC()->cart->get_remove_url($cart_key)),
                    __('Remove this item', 'woocommerce'),
                    esc_attr($product_id),
                    esc_attr($_product->get_sku())
                );

                /* Step 2 : Add product name */
                $return_value .= '&nbsp; <span class = "product_name" >' . $product_title . '</span>';

                /* Step 3 : Add quantity selector */
                if ($_product->is_sold_individually()) {
                    $return_value .= sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_key);
                } else {
                    $return_value .= woocommerce_quantity_input(array(
                        'input_name' => "cart[{$cart_key}][qty]",
                        'input_value' => $cart_item['quantity'],
                        'max_value' => $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(),
                        'min_value' => '1'
                    ), $_product, false);
                }
                return $return_value;
            }
        }
    } else {
        /* It will return the product name on the cart page.
         * As the filter used on checkout and cart are same.*/
        $_product = $cart_item['data'];
        $product_permalink = $_product->is_visible() ? $_product->get_permalink($cart_item) : '';
        if (!$product_permalink) {
            $return_value = $_product->get_title() . '&nbsp;';
        } else {
            $return_value = sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_title());
        }
        return $return_value;
    }
}
add_filter('woocommerce_cart_item_name', 'add_quantity', 10, 3);

/* Add js at the footer */
function add_quanity_js(){
    if ( is_checkout() ) {
        wp_enqueue_script( 'checkout_script', get_stylesheet_directory_uri().'/js/add_quantity.js', '', '', false );
        $localize_script = array(
            'ajax_url' => admin_url( 'admin-ajax.php' )
        );
        wp_localize_script( 'checkout_script', 'add_quantity', $localize_script );
    }
}
add_action( 'wp_footer', 'add_quanity_js', 10 );

function load_ajax() {
    if ( !is_user_logged_in() ){
        add_action( 'wp_ajax_nopriv_update_order_review', 'update_order_review' );
    } else{
        add_action( 'wp_ajax_update_order_review',        'update_order_review' );
    }
}
add_action( 'init', 'load_ajax' );

function update_order_review() {
    $values = array();
    parse_str($_POST['post_data'], $values);
    $cart = $values['cart'];
    $cart_qty = $values['billing_persons_count'];
    foreach ( $cart as $cart_key => $cart_value ){
        WC()->cart->set_quantity( $cart_key, $cart_qty+1, false );
        WC()->cart->calculate_totals();
        woocommerce_cart_totals();
    }
    wp_die();
}
// the end of block "Update quantity of tours on checkout page"