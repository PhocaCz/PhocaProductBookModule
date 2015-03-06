<?php
/*
 * @package Joomla 1.0.x
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 * @module Phoca - Productbook Module
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
if (!defined('_base_A'))
{
	define( '_base_A' , $mosConfig_absolute_path . '/administrator/components/com_productbook/');
}

global $database, $catid, $Itemid, $id, $gid, $mainframe;

// try to find search component's Itemid	
$query = "SELECT id"
	. "\n FROM #__menu"
	. "\n WHERE type = 'components'"
	. "\n AND published = 1"
	. "\n AND link = 'index.php?option=com_productbook'"
	;
$database->setQuery( $query );
$Itemid_product = $database->loadResult();

if ($Itemid_product)
{
	$Itemid = $Itemid_product;
}

//Get variables from GET
$id	= mosGetParam($_REQUEST,"id", '');
$catid	= mosGetParam($_REQUEST,"catid", ''); 
$func	= mosGetParam($_REQUEST,"func",'');
$option = mosGetParam ($_REQUEST, 'option', '');
$tree 	= array();//this is category tree of categories, from id to root



// If there is no catid but id, we get catid from SQL query (Detail of an product)
if (isset($option) && $option == 'com_productbook' && isset($catid) && $catid =='' && isset($id))
{
	$qdpid = "SELECT catid FROM #__productbook WHERE published=1 AND id=$id";
	$database->setQuery($qdpid);
	$odpid = $database->loadObjectList();
	if ($odpid)	{$catid = $odpid[0]->catid;}
		
}


// If user goes to some category of productbook, javascript menu will be open
if (isset($catid) && $catid > 0 && isset($option) && $option == 'com_productbook')
{
	function showTreeUp($database, $gid, $tree, $id)// Get the root from id to root - all parents to 0
	{
		$qdp = "SELECT parent FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND cid=$id";
		$database->setQuery($qdp);
		$odp = $database->loadObjectList();
		if (!$odp)	{$odpv = 0;}
		else		{$odpv = $odp[0]->parent;}

		$tree[] = array($odpv);
		
		if ($odpv > 0)//No loop
		{
			$tree = showTreeUp($database, $gid, $tree, $odpv);
		}	
		return($tree);
	}	
	$tree = showTreeUp($database, $gid, $tree, $catid);	
}

// Add Javascript to the site
?>
<script type="text/javascript" language="JavaScript">
imageplus = new Image();
imageminus = new Image();
imageplus.src = "images/icon-plus.gif"
imageminus.src = "images/icon-minus.gif"

function openList(GrpId) {
  var objR=document.getElementById(GrpId);
  var objR1=document.getElementById(GrpId+'X');
  if (objR!=null && objR1!=null) {
    if (objR1.style.display!='block') {
      objR.src= imageminus.src;
      objR1.style.display='block';
    } else {
      objR.src= imageplus.src;
      objR1.style.display='none';
    }
  }  
}
</script>
<?php

//Get all categoris from database			
$query_product = "SELECT * FROM #__productbook_catg WHERE published=1 AND access <='$gid' ORDER by ordering ASC";
$database->setQuery($query_product);
$object = $database->loadObjectList();

// Recursive function - this function creates a javascript menu
$output = '';
	
function showLink($Itemid, $output, $tree, $catid, $gid, $database, $object, $parent=0, $tab=0)
{	
	
	$same_level = 1;// the same level of subcategories
	
	$style_cat = 'text-decoration:none;font-family: Arial, sans-serif;font-size:15px;font-weight:bold';
	$style_sub = 'text-decoration:none;font-family: Arial, sans-serif;font-size:15px;font-weight:bold';
	foreach($object as $value)
	{	
		if ($value->parent == $parent)// No loop
		{
			// Set href to all links
			$href = sefRelToAbs('index.php?option=com_productbook&amp;func=viewcategory&amp;catid='.$value->cid.'&amp;Itemid='.$Itemid);
			
			
			//-----------------------------------------
			// ZERO LEVEL - parent=0 (CATEGORIES)
			//-----------------------------------------
			if ($parent == 0)
			{
				// Is there a subcategory (tow show the icons)
				$query_subcategory = "SELECT * FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND parent=".$value->cid." ";
				$database->setQuery($query_subcategory);
				$object_subcategory = $database->loadObjectList();
				
				if (!empty($object_subcategory))// Yes there are subcategories opened, display - icon
				{
					$display_group = 0;//groups are not displayed, show + icon
					foreach ($tree as $key2 => $value2)
					{
						if ($value->cid == $value2[0]) 	{$display_group = 1;}//open it
						//There is a opened group ($display_group = 1) so we must
						// display minus icon, not plus (see row 153)
						if ($value->cid == $catid)		{$display_group = 1;}//open it too
					}
					
					if ($display_group == 1)
					{	
						$output .= "\n".'<div class="group"><img id="C'.$value->cid.'" src="images/icon-minus.gif" onclick="openList(this.id);" alt="" /> <a style="'.$style_cat.'" title="" href="'.$href.'">'.$value->name.'</a></div>' ."\n";
					}
					else
					{
						$output .= "\n".'<div class="group"><img id="C'.$value->cid.'" src="images/icon-plus.gif" onclick="openList(this.id);" alt="" /> <a style="'.$style_cat.'" title="" href="'.$href.'">'.$value->name. '</a></div>' ."\n";
					}
				
				}
				else //No there aren't any subcategoris, don't display icon
				{
					$output .=  "\n".'<div class="group"><img src="images/icon-spacer.gif" width="16" height="16" alt="" /> <a style="'.$style_cat.'" title="" href="'.$href.'">'.$value->name. '</a></div>' ."\n";
				}
				$tab = 25;
				
			}

			
			
	
			//-----------------------------------------
			// FIRST AND OTHER LEVELS (SUBCATEGORIES)
			//-----------------------------------------
			
			if ($parent > 0)// subcategories
			{				
				// Is there a subcategory (tow show end the opened group)
				$query_subcategory = "SELECT parent FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND cid=".$value->cid." ";
				$database->setQuery($query_subcategory);
				$object_subcategory = $database->loadObjectList();
				
				
				// SAME LEVEL -------------------------------
				// IF SAME LEVEL 
				// if the same level create only li, not ul
				$count_subcategory = "SELECT COUNT(cid) FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND parent=".$object_subcategory[0]->parent." ";
				
				$database->setQuery($count_subcategory);
				
				$object_count_subcategory = $database->loadRow();
				// --------------------------------------------
				
				
				$query_subcategory = "SELECT parent FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND cid=".$object_subcategory[0]->parent." ";
				$database->setQuery($query_subcategory);
				$object_subcategory = $database->loadObjectList();
				
				$display_group = 0;//don't display all group (don't open javascript menu)
				foreach ($tree as $key2 => $value2)
				{
					if ($value->parent == $value2[0]) 	{$display_group = 1;}//open it
					//E.g. user click on category 10, category 10 is subcategory of category 5
					//and category 5 is subcategory of category 0 (root)
					//so open javascript menu for this category and all parent categories to root
					if ($value->parent == $catid)		{$display_group = 1;}//open it too
					//E.g. user click on category 1, all subcategories in category 1
					//will be displayed (open javascript menu for category 1)	
				}
					
				
				//-----------------------------------------
				// FIRST LEVEL ONLY (SUBCATEGORIES) = WE MUST BEGINN AND END DIV (block or hide)
				//-----------------------------------------
				if ($object_subcategory[0]->parent==0)//First LEVEL (parent=0), show <ul>
				{
					if ($display_group ==1)//if open javascript menu, set display:block
					{
						
						if ($same_level == 1)//only the first item in the same level BEGINN
						{
					
							$output .= "\n". '<div class="first-level" id="C'.$value->parent.'X" style="display:block;list-style:none;background:transparent;">';
						}
						
						$tabli = $tab - 18;
						$output .= '<div style="padding-left:'.$tab.'px;background: url(images/icon-li.gif) '.$tabli.'px -2px no-repeat;"><a style="'.$style_sub.'" title="" href="'.$href.'">' . $value->name . '</a></div>' ."\n";
						
						if ($same_level == $object_count_subcategory[0])// END
						{
							// LAST END NO SUBCATEGORIES
							// HAS SUBCATEGORIES - DON'T CLOSE IT
							$querySUB = "SELECT cid FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND parent=". $value->cid." ";
							$database->setQuery($querySUB);
							$objectSUB = $database->loadObjectList();
							
							// ORDERING
							$queryORD = "SELECT ordering FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND cid=". $value->cid." LIMIT 1";
							$database->setQuery($queryORD);
							$objectORD = $database->loadObjectList();
							
							// IT IS THE LAST IN THE SAME LEVEL
							$queryLAST = "SELECT cid FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND parent=". $value->parent." AND ordering > ".$objectORD[0]->ordering. " ";
							$database->setQuery($queryLAST);
							$objectLAST = $database->loadObjectList();
							
							
							
							if (empty($objectSUB) && empty($objectLAST)) {
								$output .= '</div>' . "\n";
							}
						}
						
					}
					else// set display to none, because no category is opened or other category is opened
					{
						if ($same_level == 1)//only the first item in the same level BEGINN
						{
							$output .= "\n".'<div class="first-level" id="C'.$value->parent.'X" style="display:none;list-style:none; background:transparent;">';
						}
						
						$tabli = $tab - 18;
						$output .= '<div style="padding-left:'.$tab.'px;background: url(images/icon-li.gif) '.$tabli.'px -2px no-repeat;"><a style="'.$style_sub.'" title="" href="'.$href.'">' . $value->name . '</a></div>' ."\n";
						
						if ($same_level == $object_count_subcategory[0])
						{
							// LAST END NO SUBCATEGORIES
							// HAS SUBCATEGORIES - DON'T CLOSE IT
							$querySUB = "SELECT cid FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND parent=". $value->cid." ";
							$database->setQuery($querySUB);
							$objectSUB = $database->loadObjectList();
							
							// ORDERING
							$queryORD = "SELECT ordering FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND cid=". $value->cid." LIMIT 1";
							$database->setQuery($queryORD);
							$objectORD = $database->loadObjectList();
							
							// IT IS THE LAST IN THE SAME LEVEL
							$queryLAST = "SELECT cid FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND parent=". $value->parent." AND ordering > ".$objectORD[0]->ordering. " ";
							$database->setQuery($queryLAST);
							$objectLAST = $database->loadObjectList();
	
							if (empty($objectSUB) && empty($objectLAST)) {
								$output .= '</div>' . "\n";
							}

						}
					}
				}
				//-----------------------------------------
				// SECOND AND OTHER LEVEL ONLY (SUBCATEGORIES) =  NO BEGINN AND END DIV
				//-----------------------------------------
				else
				{
					if ($same_level == 1)
					{
						$tab = $tab + 15;
					}
				
					$tabli = $tab - 18;
					$output .= '<div style="padding-left:'.$tab.'px;background: url(images/icon-li.gif) '.$tabli.'px -2px no-repeat;"><a style="'.$style_sub.'" title="" href="'.$href.'">' . $value->name . '</a></div>' ."\n";
					
					
					// LAST END NO SUBCATEGORIES
					// HAS SUBCATEGORIES - DON'T CLOSE IT
					$querySUB = "SELECT cid FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND parent=". $value->cid." ";
					$database->setQuery($querySUB);
					$objectSUB = $database->loadObjectList();
					
					// ORDERING
					$queryORD = "SELECT ordering FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND cid=". $value->cid." LIMIT 1";
					$database->setQuery($queryORD);
					$objectORD = $database->loadObjectList();
					
					// IT IS THE LAST IN THE SAME LEVEL
					$queryLAST = "SELECT cid FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND parent=". $value->parent." AND ordering > ".$objectORD[0]->ordering. " ";
					$database->setQuery($queryLAST);
					$objectLAST = $database->loadObjectList();
					
					// -----------------------------------------------
					// ARE THERE SOME ITEMS after it from PARENT LEVEL
					// ORDERING
				$queryORDPARENT = "SELECT parent, ordering FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND cid=". $value->parent." LIMIT 1";
					$database->setQuery($queryORDPARENT);
					$objectORDPARENT = $database->loadObjectList();
					
					
					
					//$closedTree = ClosedTree($database, $value->parent);
					
					$queryPARENT = "SELECT cid FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND parent=". $objectORDPARENT[0]->parent." AND ordering > ".$objectORDPARENT[0]->ordering. " ";
					

					$database->setQuery($queryPARENT);
					$objectPARENT = $database->loadObjectList();

					if (empty($objectPARENT)) {
					// if we are here, we know that it has no:
					// - subcategoris
					// - it is last
					// - parent is last
					// - but we must recognize if parent of parent there is...
					// tell me the level we have no checked

					$queryPARENTNOCH = "SELECT parent FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND cid=". $value->parent;
					$database->setQuery($queryPARENTNOCH);
					$objectPARENTNOCH = $database->loadObjectList();
					
					$objPar = 0;
					$objPar = $objectPARENTNOCH[0]->parent;
					if ( (int)$objPar > 0) {
						$closedTree = ClosedTree($database,$gid,$objPar, 0);
					}
					
						if ($closedTree == 0) {
							if (empty($objectSUB) && empty($objectLAST)) {
								$output .= '</div>' . "\n";
							}
						}
					}
				}
				
			}
			
			$same_level++;
				
			// RECURSIVE FUNCTION
			$output = showLink($Itemid, $output, $tree, $catid, $gid, $database, $object, $value->cid, $tab);

		//$end = 0;
		}
	}
	return ($output);
}

function ClosedTree($database, $gid, $parent, $return=0) {
	
	// we recognize the parent of our id
	// than we recognize if this parent have some subcategories,
	// it means categories on the same level as id
	// if this categories exists and have greater ordering, don't close id
		
	$queryORDPARENT = "SELECT name,cid, parent, ordering FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND cid=". $parent." LIMIT 1";
	$database->setQuery($queryORDPARENT);
	$objectORDPARENT = $database->loadObjectList();
	
	if ($objectORDPARENT[0]->parent > 0) {
		
		$queryCID = "SELECT cid FROM #__productbook_catg WHERE published=1 AND access <='$gid' AND parent=". $objectORDPARENT[0]->parent." AND ordering > ".$objectORDPARENT[0]->ordering. " ";
		$database->setQuery($queryCID);
		$objectCID = $database->loadObjectList();
		
		if (!empty($objectCID[0])) {
			$return = 1;
		}
		$return = ClosedTree($database,$gid, $objectORDPARENT[0]->parent, $return);
		return $return;
	} else {
		return $return;
	}
}
$output = showLink($Itemid, $output, $tree, $catid, $gid, $database, $object);
echo $output;
?>