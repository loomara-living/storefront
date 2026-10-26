<?php
/** Reference sizing only: never replaces measured product specifications. */
defined('ABSPATH') || exit;
function loomara_product_size_guide() {
    global $product;
    if (!$product || !is_a($product, 'WC_Product')) { return; }
    $id = $product->get_id();
    $cushion = has_term('cushion-covers', 'product_cat', $id);
    $bedding = has_term(['bedsheets', 'comforters', 'combo'], 'product_cat', $id);
    if (!$cushion && !$bedding) { return; }
    ?>
    <details class="loomara-size-guide">
      <summary>Size guide <span aria-hidden="true">↗</span></summary>
      <div class="loomara-size-guide-panel">
        <?php if ($cushion) : ?>
          <h4>Cushion cover size</h4><p>All Loomara cushion covers: <strong>16 × 16 inches (40.6 × 40.6 cm)</strong>. This is the size of each cover, regardless of pack quantity.</p>
        <?php else : ?>
          <h4>Bedding size reference</h4>
          <p>Approximate reference dimensions—not confirmed Loomara product measurements. Indian sizing varies by brand; Double and Queen labels may overlap. Check the exact product dimensions before ordering.</p>
          <div class="loomara-size-table" role="region" aria-label="Bedding reference sizes" tabindex="0">
            <table><caption>Illustrative flat bedsheet and comforter sizes · width × length</caption><thead><tr><th scope="col">Size</th><th scope="col">Flat bedsheet</th><th scope="col">Comforter</th></tr></thead><tbody>
            <?php foreach ([
                ['Single', '60 × 90 in', '152 × 229 cm', '60 × 90 in', '152 × 229 cm'],
                ['Double', '90 × 100 in', '229 × 254 cm', '90 × 100 in', '229 × 254 cm'],
                ['Queen', '90 × 108 in', '229 × 274 cm', '90 × 100 in', '229 × 254 cm'],
                ['King', '108 × 108 in', '274 × 274 cm', '100 × 108 in', '254 × 274 cm'],
            ] as $row) : ?>
              <tr><th scope="row"><?php echo esc_html($row[0]); ?></th><td><?php echo esc_html($row[1]); ?><small><?php echo esc_html($row[2]); ?></small></td><td><?php echo esc_html($row[3]); ?><small><?php echo esc_html($row[4]); ?></small></td></tr>
            <?php endforeach; ?>
            </tbody></table>
          </div>
          <p>Measure your mattress width, length and depth. Flat sheets need extra fabric for the sides and tuck-in; comforter coverage depends on your preferred overhang. This chart is not a fitted-sheet guide. Combo components may have different measurements.</p>
          <a href="<?php echo esc_url(home_url('/contact/')); ?>">Ask us to confirm this product’s measurements</a>
        <?php endif; ?>
      </div>
    </details>
    <?php
}
add_action('woocommerce_after_variations_table', 'loomara_product_size_guide', 10);
add_action('woocommerce_before_add_to_cart_button', function () {
    global $product;
    if ($product && !$product->is_type('variable') && !$product->is_type('grouped')) { loomara_product_size_guide(); }
}, 5);
