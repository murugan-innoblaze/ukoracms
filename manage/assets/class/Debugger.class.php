<?php

class Debugger extends Form {

	/*****************************************/
	/************ CONSTRUCT elements ********/
	/*****************************************/
	function __construct($db){
	
		//run form constructor
		parent::__construct($db, 'dzpro_errors', array(), array());
	
		//dont allow delete
		parent::dontAllowDelete();
	
	}
	
	public function showErrorDetails(){
		?>
			<div class="form_area" style="margin-top: 0px;">
				<div style="height: 31px; padding: 13px 29px 0 29px; font-size: 16px; color: black; text-shadow: -1px 1px 1px #ffffff;">
					<strong>Error Details</strong>
				</div>
				<div class="input_row inner_shadow">
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td class="label">
									Error Level:
								</td>
								<td class="regular_text">
									<div class="inner_holder">
										<?=prepareStringHtml($this->selected_row['dzpro_error_level'])?>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<div class="line"><!-- line --></div>
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td class="label">
									Error Message:
								</td>
								<td class="regular_text">
									<div class="inner_holder">
										<?=prepareStringHtml($this->selected_row['dzpro_error_message'])?>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<div class="line"><!-- line --></div>
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td class="label">
									On Page:
								</td>
								<td class="regular_text">
									<div class="inner_holder">
										<?=$this->selected_row['dzpro_error_uri']?>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<div class="line"><!-- line --></div>
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td class="label">
									File Path:
								</td>
								<td class="regular_text">
									<div class="inner_holder">
										<?=$this->selected_row['dzpro_error_path']?> [<?=$this->selected_row['dzpro_error_line']?>]
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<div class="line"><!-- line --></div>
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td class="label">
									Parent Path:
								</td>
								<td class="regular_text">
									<div class="inner_holder">
										<?=$this->selected_row['dzpro_error_parent_path']?> [<?=$this->selected_row['dzpro_error_parent_line']?>]
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<div class="line"><!-- line --></div>
					<table cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td class="label">
									PHP Messages:
								</td>
								<td class="regular_text">
									<div class="inner_holder">
										<?php $details = json_decode($this->selected_row['dzpro_error_details']); foreach($details as $key => $detail){ ?><strong><?=$key?>:</strong> <?=$detail?><br /> <?php } ?>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>	
			</div><!-- .form_area -->
		<?php
	}
	
}

?>