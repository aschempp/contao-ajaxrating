<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Andreas Schempp 2008-2010
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 * @version    $Id$
 */


class FormAjaxRating extends Widget
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'form_widget';
	
	
	private $intRatingUnitWidth = 30;
	
	
	/**
	 * Make sure we know the ID for ajax upload session data
	 * @param array
	 */
	public function __construct($arrAttributes=false)
	{
		$this->strId = $arrAttributes['id'];
		$_SESSION['AJAX-FFL'][$this->strId] = array('type'=>'ajaxrating');
		
		parent::__construct($arrAttributes);
		
		$this->import('Database');
		$this->import('FrontendUser', 'User');
	}
	
	
	/**
	 * Store config for ajax upload.
	 * 
	 * @access public
	 * @param string $strKey
	 * @param mixed $varValue
	 * @return void
	 */
	public function __set($strKey, $varValue)
	{
		if (!is_object($varValue))
		{
			$_SESSION['AJAX-FFL'][$this->strId][$strKey] = $varValue;
		}
		
		switch( $strKey )
		{
			case 'ratingUnitWidth':
				$this->intRatingUnitWidth = intval($varValue);
				break;
				
			default:
				parent::__set($strKey, $varValue);
		}
	}
	
	
	public function validate()
	{
		return parent::validate();
	}
	
	
	public function generate()
	{
		if ($this->fromTable == '' || $this->pid == '')
		{
			throw new Exception('AjaxRating requires fromTable and pid');
		}
		
		// Javascript unavailable, rating trough URL
		if ($this->Input->get('rate') == $this->strName)
		{
			$this->generateAjax();
			$this->redirect(preg_replace('@(\?|&)rate=[^&]*&rating=[^&]*@', '', $this->Environment->request));
		}
		elseif ($this->ratingMode == 'rate')
		{
			$this->varValue = 0;
		}
		elseif (!$this->varValue)
		{
			$this->varValue = $this->Database->execute("SELECT AVG(rating) AS rating FROM tl_ajaxrating WHERE fromTable='{$this->fromTable}' AND pid={$this->pid}")->rating;
		}
		
		$GLOBALS['TL_CSS']['rating'] = 'system/modules/ajaxrating/html/rating.css';
		
		$intRatingWidth = $this->value * $this->intRatingUnitWidth;
		
		$strUrl = $this->Environment->request . (strpos($this->Environment->request, '?') === false ? '?' : '&');
		
		
		$return  = '<div class="rating block">';
		$return .= '<div id="unit_long'.$this->strId.'">';
		$return .= '<ul id="unit_ul'.$this->strId.'" class="unit-rating" style="width:'.$this->intRatingUnitWidth*$this->size.'px;">';
		$return .= '<li class="current-rating" style="width:'.$intRatingWidth.'px;">Currently '.$this->value.'/'.$this->size.'</li>';
		
		if ($this->canVote())
		{
			for( $i=1; $i<=$this->size; $i++)
			{
				$return .= '<li class="rater"><a href="' . ampersand($strUrl .'rate='.$this->strName.'&rating='.$i) . '" title="'.sprintf($GLOBALS['TL_LANG']['MSC']['valueOutOf'], $i, $this->size).'" class="r'.$i.'-unit rater" rel="nofollow" onclick="return false">'.$i.'</a></li>';
			}
		}
		
		$return .= '</ul></div></div>';
		
		$return .= "
<script type=\"text/javascript\">
<!--//--><![CDATA[//><!--
window.addEvent('domready', function()
{
	$$('#unit_ul" . $this->strId . " a.rater').each( function(el, i)
	{
		el.addEvent('click', function()
		{
			new Request(
			{
				url: ('ajax.php?action=ffl&id=" . $this->strId . "&rating='+(i+1)),
				onComplete: function(txt, xml)
				{
					$('unit_ul" . $this->strId . "').set('html', '<li class=\"current-rating\" style=\"width:' + (txt * " . $this->intRatingUnitWidth . ") + 'px;\">Currently ' + txt + '/".$this->size."</li>');
				}
			}).send();
			
			$('unit_ul" . $this->strId . "').set('html', '<div class=\"loading\"></div>');
		});
	});
});
//--><!]]>
</script>";
		
		return $return;
	}
	
	
	/**
	 * Store voting trough ajax call
	 */
	public function generateAjax()
	{
		if ($this->Input->get('rating') && $this->canVote())
		{
			$intRating = $this->Input->get('rating');
			
			// Make sure rating is not higher than allowed
			if ($intRating > $this->size)
				$intRating = $this->size;
			
			$this->Database->prepare("INSERT INTO tl_ajaxrating (pid, tstamp, fromTable, ipaddress, member, rating) VALUES (?, ?, ?, ?, ?, ?)")
						   ->execute($this->pid, time(), $this->fromTable, $this->Environment->ip, ($this->User->id ? $this->User->id : 0), $intRating);
						   
			$arrCookie = deserialize($this->Input->cookie('ajaxrating'), true);
			$arrCookie[$this->fromTable][] = $this->pid;
			$this->setCookie('ajaxrating', serialize($arrCookie), strtotime('+10 years'));
		}
		
		$intRating = $this->Database->execute("SELECT AVG(rating) AS rating FROM tl_ajaxrating WHERE fromTable='{$this->fromTable}' AND pid={$this->pid}")->rating;
		
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['ajaxRating']) && is_array($GLOBALS['TL_HOOKS']['ajaxRating']))
		{
			foreach ($GLOBALS['TL_HOOKS']['ajaxRating'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($intRating, $this->fromTable, $this->pid);
			}
		}
		
		return $intRating;
	}
	
	
	/**
	 * Check if the visitor is allowed to vote
	 * @todo allow to define timeout and number of votes
	 */
	private function canVote()
	{
		// Static rating mode
		if ($this->ratingMode == 'static' || $this->ratingMode == 'none')
			return false;
		
		// Cookie is set
		$arrCookie = deserialize($this->Input->cookie('ajaxrating'), true);
		if (is_array($arrCookie[$this->fromTable]) && in_array($this->pid, $arrCookie[$this->fromTable]))
			return false;
		
		// Cookie might be deleted, check IP address.
		$objVote = $this->Database->prepare("SELECT * FROM tl_ajaxrating WHERE pid={$this->pid} AND fromTable='{$this->fromTable}' AND tstamp>=? AND (ipaddress=? OR member=?)")->execute(strtotime('-1 day'), $this->Environment->ip, ($this->User->id ? $this->User->id : 0));
		if ($objVote->numRows >= 1)
			return false;
			
		// Visitor has not voted
		return true;
	}
}

