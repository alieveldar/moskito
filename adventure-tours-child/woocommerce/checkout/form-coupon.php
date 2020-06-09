<?php
/**
 * Checkout coupon form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-coupon.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.4
 */

defined( 'ABSPATH' ) || exit;

if ( ! wc_coupons_enabled() ) { // @codingStandardsIgnoreLine.
    return;
}

?>
<div style="text-align: justify;">
    <p>Wir freuen uns, dass Sie Ihre passende Reise gefunden haben. Bitte nutzen Sie für Ihre verbindliche Anmeldung dieses Buchungsformular. Wir weißen Sie darauf hin, dass Ihre Anmeldung erst zustande kommt, nachdem wir Ihnen diese nochmals bestätigt haben. Übrigens steht Ihnen unser Anmeldeformular unter folgendem Link auch zum Download bereit, sollten Sie sich lieber in Papierform anmelden wollen: Anmeldeformular.pdf</p>
    <p>Die hier als 1. Reisender eingetragene Person erklärt sich damit einverstanden, für die Verpflichtungen aller in dieser Reiseanmeldung aufgeführten Personen einzustehen.</p>
    <p>Ein halbes Doppelzimmer können wir nicht garantieren. Zunächst stellen wir den EZ-Zuschlag in Rechnung. Wenn sich bis 4 Wochen vor Abreise ein geeigneter Zimmerpartner findet, so erstatten wir diesen Betrag zurück.</p>
    <p>Bei Reisen, welche die Buchung von Flügen beinhalten, benötigen wir mit dieser Anmeldung korrekte Angaben zum Vor- und Nachnamen aller Reiseteilnehmer. Bitte stellen Sie sicher, dass diese der Schreibweise in der maschinenlesbaren Zeile des auf der Reise mitgeführten Ausweisdokumentes entsprechen. Eventuelle Gebühren für eine Umbuchung aufgrund fehlerhafter Angaben müssen wir Ihnen berechnen. Der beste Weg ist es, uns mit Ihrer Buchung eine Kopie, einen Scan oder ein Foto Ihrer Reisepassseite per Email zuzusenden.</p>
    <br>
</div>

<div class="woocommerce-form-coupon-toggle">
    <?php wc_print_notice( apply_filters( 'woocommerce_checkout_coupon_message', esc_html__( 'Have a coupon?', 'woocommerce' ) . ' <a href="#" class="showcoupon">' . esc_html__( 'Click here to enter your code', 'woocommerce' ) . '</a>' ), 'notice' ); ?>
</div>

<form class="checkout_coupon woocommerce-form-coupon" method="post" style="display:none">

    <p><?php esc_html_e( 'If you have a coupon code, please apply it below.', 'woocommerce' ); ?></p>

    <p class="form-row form-row-first">
        <input type="text" name="coupon_code" class="input-text" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" id="coupon_code" value="" />
    </p>

    <p class="form-row form-row-last">
        <button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
    </p>

    <div class="clear"></div>
</form>
