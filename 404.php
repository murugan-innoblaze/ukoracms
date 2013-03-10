<?php
//handle visitor
$this->Visitor = new Visitor($this->db);
$this->User = new User($this->db);

//do redirect
findNewLocation();

//save page not found
addToIntelligenceStack('page not found', $_SERVER['REQUEST_URI']);

?>
<!DOCTYPE html>
<html lang="en-us"> 
	<head> 
		<?php require RELATIVE_ASSETS_PATH . '/elements/head.element.php'; ?>
	</head>
	<body>
		<?php require RELATIVE_ASSETS_PATH . '/elements/topbar.element.php'; ?>
		<div class="container">
			<div class="content">
				<h1>Page not found</h1>
				<h2>Oops! This page could not be found.</h2>
				<p>We have send a message to the site admin. If you've received this message in error we'll work hard to have it resolved soon! Now please either go back to the previous page or <a href="/" title="<?=SITE_NAME?>">click here</a> to go our homepage.</p>
				<p style="font-style: italic;">Thank you for your interest in <?=SITE_NAME?>!</p>
			</div><!-- end .content -->
			<?php require RELATIVE_ASSETS_PATH . '/elements/footer.element.php'; ?>			
		</div><!-- end .container -->
	</body>
</html>
<?php

//upload page
pageUnload(false, true);

?>