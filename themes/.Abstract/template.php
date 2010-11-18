<?php 

/*

This stripped down theme can be used as a starter or guide for building your own themes.


*/


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
				<?php gpOutput::GetHead(); ?>
</head>
<body>

<table cellpadding="11">
<tr><td colspan="2">
				<?php gpOutput::Get('Extra','Header'); ?>
				<?php /* gpOutput::Get('Menu'); */ ?>
</td></tr>
<tr><td class="side">
				<?php gpOutput::Get('FullMenu'); ?>
				<?php /* gpOutput::Get('SubMenu'); */ ?>
				<?php /* gpOutput::Get('ExpandMenu'); */ ?>
				<?php gpOutput::Get('Extra','Side_Menu'); ?>
				<?php gpOutput::GetAllGadgets(); ?>
</td><td>
				<?php $page->GetContent(); ?>
</td></tr>
</table>			

				<?php gpOutput::Get('Extra','Footer'); ?>
				<?php gpOutput::GetAdminLink(); ?>

</body>
</html>
