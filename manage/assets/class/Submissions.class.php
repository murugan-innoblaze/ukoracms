<?php

class Submissions extends Form {

	/*************************************************************/
	/*********************** BUILD CLASS *************************/
	/*************************************************************/
	function __construct($db, $table_name = null, $parameters = array(), $sticky_fields = array()){
		
		//run form constructor
		parent::__construct($db, $table_name, $parameters, $sticky_fields);
		
		//export submissions
		if(have($_GET['export-submissions'])){ header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="' . prepareStringForUrl($_GET['export-submissions']) . '-' . date('Y-m-d') . '.csv"'); header('Content-Transfer-Encoding: binary'); echo self::exportSubmissions($_GET['export-submissions']); exit(0); }
		
	}

	/*************************************************************/
	/*********************** EXPORT SUBMISSIONS UI ***************/
	/*************************************************************/	
	function exportSubmissions($submission_name = null){
		if(!have($submission_name)){ return null; }
		$return = '\'submission name\',\'date\',\'time\',\'amount paid\''; $submission_row = array(); $result = @mysql_query(" SELECT * FROM dzpro_submissions WHERE dzpro_submission_name = '" . mysql_real_escape_string($submission_name) . "' "); if(mysql_num_rows($result)){ while($row = mysql_fetch_assoc($result)){ $submission_row = $row; } mysql_free_result($result); } $field_columns = array(); $result = @mysql_query(" SELECT DISTINCT dzpro_submission_value_name FROM dzpro_submission_values LEFT JOIN dzpro_submissions USING ( dzpro_submission_id ) WHERE dzpro_submission_name = '" . mysql_real_escape_string($submission_name) . "' ") or die(mysql_error()); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ if(!in_array($row['dzpro_submission_value_name'], $field_columns)){ $field_columns[] = $row['dzpro_submission_value_name']; } } mysql_free_result($result); } if(have($field_columns)){ foreach($field_columns as $column_name){ $return .= ',\'' . prepareTag($column_name) . '\''; } $return .= "\n"; } $submission_values_array = array(); $result = @mysql_query(" SELECT * FROM dzpro_submission_values LEFT JOIN dzpro_submissions USING ( dzpro_submission_id ) WHERE dzpro_submission_name = '" . mysql_real_escape_string($submission_name) . "' GROUP BY dzpro_submission_value_id "); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ foreach($row as $rkey => $rvalue){ $submission_values_array[$row['dzpro_submission_id']]['array'][$rkey] = $rvalue; } $submission_values_array[$row['dzpro_submission_id']][$row['dzpro_submission_value_name']]['value'] = $row['dzpro_submission_value_value']; } mysql_free_result($result); if(have($submission_values_array)){ foreach($submission_values_array as $submission_id => $submission_array){ if(have($submission_array['array'])){ $return .= '\'' . $submission_array['array']['dzpro_submission_name'] . '\',\'' . convertDate('m-d-Y', $submission_array['array']['dzpro_submission_date_added']) . '\',\'' . convertDate('H:i:s', $submission_array['array']['dzpro_submission_date_added']) . '\',\'' . $submission_array['array']['dzpro_submission_amount'] . '\''; foreach($field_columns as $column_name){ if(have($submission_array[$column_name]['value'])){ $return .= ',\'' . prepareTag($submission_array[$column_name]['value']) . '\''; }else{ $return .= ',\'\''; } } $return .= "\n"; } } } } return $return;
	}
	
	/*************************************************************/
	/*********************** BUILD SUBMISSIONS UI ****************/
	/*************************************************************/	
	function buildSubmissionsUI(){
		?>
			<div class="form_area" method="post">
		<?php $result = @mysql_query(" SELECT DISTINCT *, COUNT(dzpro_submission_name) AS submission_count FROM dzpro_submissions GROUP BY dzpro_submission_name ORDER BY submission_count DESC "); if(mysql_num_rows($result) > 0){ $count = 1; while($row = mysql_fetch_assoc($result)){ ?>
				<div class="input_iframe" style="margin-bottom: 27px;">
					<div class="table_name" style="cursor: default;">
						<table cellpadding="0" cellspacing="0">
							<tbody>
								<tr>
									<td style="width: 170px;">
										<?=$row['dzpro_submission_name']?> (<?=$row['submission_count']?> submissions)
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div style="background-color: rgb(222, 227, 233); padding: 12px;">
						<table cellpadding="0" cellspacing="0" class="submission_button">
							<tbody>
								<tr>
									<td>
										<a href="<?=addToGetString(array('export-submissions'), array($row['dzpro_submission_name']))?>" title="Export <?=$row['dzpro_submission_name']?> Data" target="_blank" class="export_link">
											<img src="/assets/img/manager/download-csv-icon.png" alt="Export <?=$row['dzpro_submission_name']?> Data" /> Export <?=$row['dzpro_submission_name']?> Data
										</a>
									</td>
									<td style="font-size: 12px;">
										(CSV format, export)
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
		<?php $count++; } } ?>
			</div>
		<?php 
	}

}
	
?>