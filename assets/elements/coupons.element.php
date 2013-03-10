<div class="bucket_left">
	<h2>Enter Coupon</h2>
	<p>If you have a coupon code, please enter it here.</p>
	<?php if(isset($this->Coupon->coupon_message) and have($this->Coupon->coupon_message)){ ?>
	<div class="problem_mssg" style="padding: 5px 10px; margin-left: -5px; font-size: 12px;"><?=prepareStringHtml($this->Coupon->coupon_message)?></div>
	<div style="height: 10px;"><!-- spacer --></div>
	<?php } ?>
	<form action="<?=$_SERVER['REQUEST_URI']?>" method="post" style="padding: 5px; background-color: #c8eb74; margin-left: -5px;" class="radius_3">
		<table cellpadding="0" cellspacing="0">
			<tbody>
				<tr>
					<td style="vertical-align: middle; padding-right: 7px;">
						<input type="text" name="coupon" class="radius_3 shadow_5_inner" style="width: 130px; padding: 2px 4px; color: #412100; font-family: inherit; border: 1px solid #93ba37; font-size: 14px; background-color: white;" />	
					</td>
					<td style="vertical-align: middle;">
						<input type="submit" value="apply" class="radius_3" style="padding: 2px; color: #412100; font-family: inherit; border: 1px solid #93ba37; font-size: 12px;" />
					</td>
				</tr>
			</tbody>
		</table>	
	</form>
	<?php if(isset($_SESSION['coupon_stack']) and have($_SESSION['coupon_stack'])){ ?>
	<div style="height: 10px;"><!-- spacer --></div>
	<strong>Coupons Applied</strong>
	<p>These coupons will be applied to your order. Coupons are applied to each individual shipment.</p>
	<ul>	
		<?php foreach($_SESSION['coupon_stack'] as $coupon){ ?>
		<li title="<?=prepareTag($coupon['dzpro_coupon_conditions'])?>">
			"<?=prepareStringHtml($coupon['dzpro_coupon_key'])?>" <?=prepareStringHtml($coupon['dzpro_coupon_name'])?>
		</li>		
		<?php } ?>
	</ul>
	<?php } ?>
</div><!-- .bucket_left -->