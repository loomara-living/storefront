<?php
/** Loomara customer orders. Native query, ownership, actions and pagination retained. */
defined('ABSPATH') || exit;
if (!is_user_logged_in()) { return; }
$selection = loomara_order_status_selection();
do_action('woocommerce_before_account_orders', $has_orders);
?>
<section class="loomara-orders" aria-labelledby="loomara-orders-title">
  <header class="orders-heading"><p class="eyebrow">Your Loomara collection</p><h2 id="loomara-orders-title">Your orders</h2><p>Follow every piece on its way to your home.</p></header>
  <?php if(isset($_GET['testimonial'])&&$_GET['testimonial']==='submitted'): ?><p role="status">Thank you. Your testimonial is awaiting review before it appears publicly.</p><?php endif; ?>
  <div class="testimonial-layout"><div>
  <form method="get" action="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="order-status-filter">
    <label for="order-status-filter">Order status</label>
    <select id="order-status-filter" name="order_status">
      <option value="all" <?php selected($selection,'all'); ?>>All orders</option>
      <option value="successful" <?php selected($selection,'successful'); ?>>Successful orders</option>
      <?php foreach (wc_get_order_statuses() as $key=>$label) : $value=preg_replace('/^wc-/', '', $key); if(in_array($value,['checkout-draft','draft'],true)){continue;} ?>
        <option value="<?php echo esc_attr($value); ?>" <?php selected($selection,$value); ?>><?php echo esc_html($label); ?></option>
      <?php endforeach; ?>
    </select>
    <?php do_action('loomara_orders_filter_fields'); ?>
    <button class="button" type="submit">Apply filter</button>
  </form>
  <p class="orders-filter-note"><?php echo $selection === 'successful' ? 'Showing paid/accepted orders. Unpaid failed attempts are hidden.' : ($selection==='pending'?'Showing pending payment orders, including unfinished checkout drafts.': 'Showing '.esc_html($selection === 'all' ? 'all orders, excluding unpaid failed attempts' : loomara_customer_order_status_label($selection)).'.'); ?></p>
  <?php if ($has_orders) : ?>
    <div class="order-card-list">
    <?php foreach ($customer_orders->orders as $entry) :
        $order=wc_get_order($entry);
        if (!$order || (int)$order->get_customer_id() !== get_current_user_id() || loomara_customer_order_hidden($order)) { continue; }
        $date=$order->get_date_created();$status=$order->get_status(); ?>
      <article class="customer-order-card">
        <header><div><span class="order-card-label">Order reference</span><h3><a href="<?php echo esc_url($order->get_view_order_url()); ?>"><?php echo esc_html($order->get_order_number()); ?></a></h3><?php if ($date) : ?><time datetime="<?php echo esc_attr($date->date('c')); ?>"><?php echo esc_html(loomara_order_created_datetime($date)); ?></time><?php endif; ?></div><span class="order-status-badge status-<?php echo esc_attr(sanitize_html_class($status)); ?>"><?php echo esc_html(loomara_customer_order_status_label($status)); ?></span></header>
        <ul class="order-card-items">
          <?php foreach ($order->get_items() as $item) : ?>
            <li><span><?php echo esc_html($item->get_name()); ?></span><span class="order-item-qty">× <?php echo absint($item->get_quantity()); ?></span></li>
          <?php endforeach; ?>
        </ul>
        <footer><div><span class="order-card-label">Order total</span><strong><?php echo wp_kses_post($order->get_formatted_order_total()); ?></strong></div><nav aria-label="<?php echo esc_attr('Actions for order '.$order->get_order_number()); ?>">
        <?php foreach (wc_get_account_orders_actions($order) as $key=>$action) : ?>
          <a href="<?php echo esc_url($action['url']); ?>" class="button <?php echo esc_attr(sanitize_html_class($key)); ?>" aria-label="<?php echo esc_attr($action['name'].' order '.$order->get_order_number()); ?>"><?php echo esc_html($action['name']); ?></a>
        <?php endforeach; ?></nav></footer>
        <?php loomara_customer_cancel_form($order); ?>
      </article>
    <?php endforeach; ?>
    </div>
    <?php do_action('woocommerce_before_account_orders_pagination'); ?>
    <?php if ($customer_orders->max_num_pages > 1) : ?>
      <nav class="order-card-pagination" aria-label="Order pages">
      <?php if ($current_page > 1) : ?><a class="button" href="<?php echo esc_url(add_query_arg('order_status',$selection,wc_get_endpoint_url('orders',$current_page-1))); ?>">Previous</a><?php endif; ?>
      <span>Page <?php echo absint($current_page); ?> of <?php echo absint($customer_orders->max_num_pages); ?></span>
      <?php if ($current_page < $customer_orders->max_num_pages) : ?><a class="button" href="<?php echo esc_url(add_query_arg('order_status',$selection,wc_get_endpoint_url('orders',$current_page+1))); ?>">Next</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php else : ?>
    <div class="orders-empty"><span aria-hidden="true">◇</span><h3>No orders in this view yet.</h3><p>Try another status to find payment attempts, or discover something new for your home.</p><a class="button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Explore the collection</a></div>
  <?php endif; ?>
  </div><aside aria-label="Share your Loomara experience"><?php loomara_testimonial_prompt('orders'); ?></aside></div>
</section>
<?php do_action('woocommerce_after_account_orders', $has_orders); ?>
