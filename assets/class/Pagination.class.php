<?php 
class Pagination {

	/***************************************/
	/*********** PAGENATION CONSTRUCTOR ****/
	/***************************************/	
	function __construct($db, $length = null, $pages = null, $key = 'start'){
		
		//database connection
		$this->db = $db;
		
		//the key
		$this->the_key = $key;
		
		//total records
		$this->total = null;
		
		//show this many
		$this->length = $length;
		
		//show this many pages
		$this->pages = $pages;
		
		//start position
		$this->start = (isset($_GET[$this->the_key]) and (int)$_GET[$this->the_key] > 0) ? (int)$_GET[$this->the_key] : 0;
		
		//set sql query
		$this->sql = null;
		
		//the records
		$this->records = array();
		
	}

	/***************************************/
	/*********** SET QUERY *****************/
	/***************************************/
	public function setQuery($query = null){
		$this->sql = $query;
		if(have($query)){ self::modifyQuery(); }
	}

	/***************************************/
	/*********** MODIFY QUERY **************/
	/***************************************/
	protected function modifyQuery(){
		$this->sql = str_ireplace('SELECT' , 'SELECT SQL_CALC_FOUND_ROWS', $this->sql) . ' LIMIT ' . $this->start . ', ' . $this->length;
	}

	/***************************************/
	/*********** RUN THE QUERY *************/
	/***************************************/	
	protected function runQuery(){
		$result = @mysql_query($this->sql) or handleError(1, $this->sql . ' error:' . mysql_error()); if(mysql_num_rows($result) > 0){ $result_count = @mysql_query(" SELECT FOUND_ROWS() "); if($count_row = mysql_fetch_row($result_count)){ $this->total = $count_row[0]; } mysql_free_result($result_count); while($row = mysql_fetch_assoc($result)){ $this->records[] = $row; } mysql_free_result($result); }
	}

	/***************************************/
	/*********** RETURN THE RECORDS ********/
	/***************************************/	
	public function getRecords(){
		if(!have($this->records)){ self::runQuery(); }
		return $this->records;
	}

	/***************************************/
	/*********** PRINT PAGINATION BLOCK ****/
	/***************************************/
	public function printPaginationBlock(){
		?>
			<div class="pagination">
				<div class="left_decal"><!-- decal --></div>
				<table cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
			<?php
			$pages = ($this->start <= ($this->length * $this->pages)) ? 1 : ($this->start / $this->length) - $this->pages + 1;
			$max_pages = $pages + $this->pages;
			$index_tag_page = ( ($this->start + $this->length) == 0) ? addToGetString($this->the_key, 0) : addToGetString($this->the_key, $this->start + $this->length);
			$style_next = ($this->start >= $this->total - $this->length) ? '<td><div class="spacer"><!-- spacer --></div></td>' : '<td><a href="' . $index_tag_page . '" title="Next Page" class="tile_page">&gt;</a></td>';
			$index_tag_page = ( ($this->start - $this->length) == 0) ? addToGetString($this->the_key, 0) : addToGetString($this->the_key, $this->start - $this->length);
			$style_prev = !($this->start == 0) ? '<td><a href="' . $index_tag_page . '" title="Previous Page" class="tile_page">&lt;</a></td>' : '<td><div class="spacer"><!-- spacer --></div></td>';
			echo $style_prev;
			while(($pages - 1) <= $max_pages and ($pages - 1) < ($this->total / $this->length)){
			?>
							<td>
								<?php if($this->start == ( ($pages - 1) * $this->length)){ ?>
								<strong><?=(int)$pages?></strong>
								<?php }else{ ?>
								<a href="<?=addToGetString($this->the_key,($pages - 1) * $this->length)?>" title="Page <?=(int)$pages?>" class="tile_page"><?=(int)$pages?></a>
								<?php } ?>
							</td>
			<?php	
				$pages++;
			} 
			echo $style_next;
			?>
						</tr>
					</tbody>
				</table>
			</div><!-- end pagination -->		
		<?php	
	}
	
}
?>