<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<?php gpOutput::GetHead(); ?>
</head>
<body>
<div id="globalwrapper">
<div id="highlightwrapper">
<div id="contentwrapper">

	<div id="header">
			<?php gpOutput::Get('Extra','Header'); ?>
		<div id="menu">
			<?php
			$GP_ARRANGE = false;
			gpOutput::Get('Menu');
			?>
		</div>
		<div style="clear:both"></div>
	</div>

	<div id="container">

		<div id="center" class="column">
			<?php $page->GetContent() ?>
		</div>

		<div id="left" class="column">
			<div class="leftnav">
			<?php gpOutput::Get('FullMenu'); ?>
			</div>
		</div>

		<div id="right" class="column">
			<div class="rightnav">
		    <?php gpOutput::Get('Extra','Side_Menu'); ?>
			<?php gpOutput::GetAllGadgets(); ?>
			</div>
		</div>
		<div style="clear:both"></div>
	</div>

	<div id="footer-wrapper">
		<div id="footer">
			<?php gpOutput::Get('Extra','Footer'); ?>
			<?php gpOutput::GetAdminLink(); ?>
		</div>
	</div>

</div>
</div>
</div>
</body>

</html>
