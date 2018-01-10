<form name="frm" method="get" action="<?php echo MNP_Manager::wishlist_popup_url();?>">
	<input type="hidden" name="SHOP_ID" value="<?php echo MNP_Manager::merchant_id(); ?>">
	<?php foreach( $wishlistItemIds as $wishlistItemId ) : ?>
		<input type="hidden" name="ITEM_ID" value="<?php echo $wishlistItemId;?>">
	<?php endforeach; ?>
</form>
