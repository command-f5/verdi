<?php
  //фильтр по готовой продукции. Возможно, опираться на tid неразумно. Но пока сойдёт.
  $tid = $content['field_photos']['#object']->field_product_kind['und'][0]['tid'];
  $tid_array = array(
    419,
    420,
    421
  );
?>

<article<?php print $attributes; ?>>
  <?php print $user_picture; ?>
  <?php print render($title_prefix); ?>
  <?php if (!$page && $title): ?>
  <header>
    <h2<?php print $title_attributes; ?>><a href="<?php print $node_url ?>" title="<?php print $title ?>"><?php print $title ?></a></h2>
  </header>
  <?php endif; ?>
  <?php print render($title_suffix); ?>
  <?php if ($display_submitted): ?>
  <footer class="submitted"><?php print $date; ?> -- <?php print $name; ?></footer>
  <?php endif; ?>
  <div class="container-24 grid-14 prefix-1 clearfix">
    <?php print render($content['product:field_images']); ?>
  </div>
  <div class="container-24 grid-8 prefix-1">
    <div<?php print $content_attributes; ?>>
      <?php
        // We hide the comments and links now so that we can render them later.
        hide($content['comments']);
        hide($content['links']);

        //print render($content['field_photos']);
		$image = field_get_items('node', $node, 'field_photos');
		?>
		<div class="nomaximg">
		<?php
		$image_out = field_view_value('node', $node, 'field_photos', $image[0], array(
					  'type' => 'image',
					  'settings' => array(
							'image_style' => 'w1280',
							'image_link' => false,
						  )
						)
					);
        echo render( $image_out );
		?>
		</div>
		<?php
        if (in_array($tid, $tid_array)):
      ?>
        <div class="product_price">
          <?php print render($content['product:commerce_price']); ?>
        </div>
      <?php else: ?>
	  
	<?php if(!empty($content['field_product'])){ // если есть описаньки ?>
	
      <table class="product_table sl_prod_table">
        <tr>
          <td class="product_cell_1">
            <?php //print render($content['flag_bookmarks']); ?>
            <a class="like" onClick="jQuery('#ya_share').show();  ">
              <div id="ya_share" />
            </a>
            <?php print render($content['field_product']); ?>
            <div class="product_table_separator" />
          </td>
          <td class="product_cell_2">
            <?php print render($content['product:title_field']); ?>
            <?php print render($content['product:field_fabric_composition']); ?>
            <div class="product_table_separator" />
          </td>
          <td class="product_cell_3">
            <?php print render($content['product:field_operating_parameters']); ?>
            <?php print render($content['product:field_roll_width']); ?>
            <?php print render($content['product:commerce_price']); ?>
          </td>
        </tr>
      </table>
	  
	<?php } else { // если есть описаньки, если нет: ?>
	<div style="height:11px"></div>
	<?php } ?>
	
      <?php endif; ?>
    </div>
  </div>
  <div class="container-24 grid-24 clearfix">
    <?php if (!empty($content['links'])): ?>
    <nav class="links node-links clearfix"><?php print render($content['links']); ?></nav>
    <?php endif; ?>
    <?php
    print render($content['comments']);
    ?>
  </div>
</article>
<script type="text/javascript" src="//yandex.st/share/share.js" charset="utf-8"></script>
<script type="text/javascript">
  ya_init();
  // создаем блок
  function ya_init() {
    setTimeout(function(){
      if (Ya) {
        var YaShareInstance = new Ya.share({
          element: 'ya_share',
          link: window.location.origin+'<?php print $node_url ?>',
          title: '<?php print $title; ?>'
        });
      }
      else {
        ya_init();
      }
    }, 500);
  }
</script>
